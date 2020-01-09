<?php

declare(strict_types=1);

namespace Brick\App\Session;

use Brick\Http\Request;
use Brick\Http\Response;

use Brick\App\ObjectPacker\Packer;
use Brick\App\ObjectPacker\ObjectPacker;
use Brick\App\Session\Storage\Lock;

/**
 * Persists data between HTTP requests.
 */
abstract class Session implements SessionInterface
{
    /**
     * The session storage mechanism.
     *
     * @var \Brick\App\Session\Storage\SessionStorage
     */
    protected $storage;

    /**
     * The object packer, if any.
     *
     * @var \Brick\App\ObjectPacker\Packer|null
     */
    private $packer;

    /**
     * The session id, or null if not available yet.
     *
     * The session id may not be available if the session id has not been read from the request yet, or if the request
     * is not associated with a session AND no data has been written to the session yet.
     *
     * If other words, for a request that is not associated with a session, the session id is created just in time when
     * a write occurs, to avoid sending a cookie with a ghost session id that would not map to an actual entry in the
     * session storage.
     *
     * @var string|null
     */
    protected $id = null;

    /**
     * Whether we're in the middle of a request/response cycle.
     *
     * i.e. handleRequest() has been called, handleResponse() has not yet been called.
     *
     * @var bool
     */
    protected $inRequest = false;

    /**
     * A local cache of the data loaded from the storage.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var int
     */
    private $gcDividend = 1;

    /**
     * @var int
     */
    private $gcDivisor = 100;

    /**
     * @var int
     */
    private $lifetime = 1800;

    /**
     * Class constructor.
     *
     * @param Storage\SessionStorage $storage      The session storage, or null to use a default file storage.
     * @param ObjectPacker|null      $objectPacker An optional object packer to use when serializing the session data.
     */
    public function __construct(?Storage\SessionStorage $storage = null, ?ObjectPacker $objectPacker = null)
    {
        if ($storage === null) {
            $storage = new Storage\FileStorage(session_save_path());
        }

        $this->storage = $storage;

        if ($objectPacker !== null) {
            $this->packer = new Packer($objectPacker);
        }
    }

    /**
     * @param string $namespace
     *
     * @return SessionNamespace
     */
    public function getNamespace(string $namespace) : SessionNamespace
    {
        return new SessionNamespace($this, $namespace);
    }

    /**
     * Sets the probability for the garbage collection to be triggered on any given request.
     *
     * For example, setGcProbability(1, 100) gives a 1% chance for the gc to be triggered.
     *
     * @param int $dividend
     * @param int $divisor
     *
     * @return void
     */
    public function setGcProbability(int $dividend, int $divisor) : void
    {
        $this->gcDividend = $dividend;
        $this->gcDivisor = $divisor;
    }

    /**
     * @param int $lifetime
     *
     * @return void
     */
    public function setLifetime(int $lifetime) : void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Reads the session cookie from the request.
     *
     * @param \Brick\Http\Request $request
     *
     * @return void
     */
    public function handleRequest(Request $request) : void
    {
        $this->reset();
        $this->readSessionId($request);

        if ($this->isTimeToCollectGarbage()) {
            $this->collectGarbage();
        }

        $this->inRequest = true;
    }

    /**
     * Writes the session cookie to the Response.
     *
     * @param \Brick\Http\Response $response
     *
     * @return void
     */
    public function handleResponse(Response $response) : void
    {
        if ($this->id === null) {
            // The request was not associated with an existing session, and no session writes occurred.
            // Sending a session cookie is unnecessary.
            return;
        }

        // Note: we re-send the session cookie even if it was part of the request, to refresh the expiration time.

        $this->writeSessionId($response);
        $this->reset();
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    abstract protected function readSessionId(Request $request) : void;

    /**
     * @param Response $response
     *
     * @return void
     */
    abstract protected function writeSessionId(Response $response) : void;

    /**
     * Generates a random session id.
     *
     * This is only relevant to cookie sessions.
     * IP sessions should throw an exeption here, as this method should never be called.
     *
     * @return string
     */
    abstract protected function generateSessionId() : string;

    /**
     * {@inheritdoc}
     */
    public function has(string $key) : bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $id = $this->getOptionalId();

        if ($id === null) {
            return null;
        }

        $value = $this->storage->read($id, $key);

        if ($value !== null) {
            $value = $this->unserialize($value);
        }

        return $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value) : void
    {
        if ($value === null) {
            $this->remove($key);

            return;
        }

        $id = $this->getId();
        $serialized = $this->serialize($value);
        $this->storage->write($id, $key, $serialized);

        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key) : void
    {
        $id = $this->getOptionalId();

        if ($id === null) {
            return;
        }

        $this->storage->remove($id, $key);

        unset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(string $key, callable $function)
    {
        $id = $this->getId();
        $lock = new Lock();
        $serialized = $this->storage->read($id, $key, $lock);

        try {
            $value = ($serialized !== null) ? $this->unserialize($serialized) : null;
            $value = $function($value);

            $serialized = $this->serialize($value);
        } catch (\Throwable $e) {
            $this->storage->unlock($lock);

            throw $e;
        }

        $this->storage->write($id, $key, $serialized, $lock);

        return $this->data[$key] = $value;
    }

    /**
     * @return void
     */
    public function clear() : void
    {
        $id = $this->getOptionalId();

        if ($id === null) {
            return;
        }

        $this->storage->clear($id);

        $this->data = [];
    }

    /**
     * @return void
     */
    public function collectGarbage()
    {
        $this->storage->expire($this->lifetime);
    }

    /**
     * @return string|null
     */
    private function getOptionalId() : ?string
    {
        $this->checkInRequest();

        return $this->id;
    }

    /**
     * @return string
     */
    private function getId() : string
    {
        $this->checkInRequest();

        if ($this->id === null) {
            $this->id = $this->generateSessionId();
        }

        return $this->id;
    }

    /**
     * @return void
     *
     * @throws \RuntimeException
     */
    private function checkInRequest() : void
    {
        if (! $this->inRequest) {
            throw new \RuntimeException(
                'Trying to access a Session object that has not yet been loaded. ' .
                'This most likely means that you have not added the SessionPlugin to your application.'
            );
        }
    }

    /**
     * @return bool
     */
    private function isTimeToCollectGarbage() : bool
    {
        return random_int(0, $this->gcDivisor - 1) < $this->gcDividend;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function serialize($value) : string
    {
        if ($this->packer !== null) {
            $value = $this->packer->pack($value);
        }

        return serialize($value);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function unserialize(string $data)
    {
        $value = unserialize($data);

        if ($this->packer !== null) {
            return $this->packer->unpack($value);
        }

        return $value;
    }

    /**
     * Resets the session status.
     *
     * @return void
     */
    private function reset() : void
    {
        $this->id = null;
        $this->data = [];
        $this->inRequest = false;
    }
}
