<?php

declare(strict_types=1);

namespace Brick\App\Session;

use Brick\Http\Request;
use Brick\Http\Response;
use Brick\Http\Cookie;

use Brick\App\ObjectPacker\Packer;
use Brick\App\ObjectPacker\ObjectPacker;
use Brick\App\Session\Storage\Lock;

/**
 * Persists data between HTTP requests.
 */
class Session implements SessionInterface
{
    /**
     * The session storage mechanism.
     *
     * @var \Brick\App\Session\Storage\SessionStorage
     */
    private $storage;

    /**
     * The object packer, if any.
     *
     * @var \Brick\App\ObjectPacker\Packer|null
     */
    private $packer;

    /**
     * The session id, or null if the session has not been read yet.
     *
     * @var string|null
     */
    private $id;

    /**
     * A local cache of the data loaded from the storage.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $cookieParams;

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
     * @var int
     */
    private $idLength = 26;

    /**
     * @var array
     */
    private static $defaultCookieParams = [
        'name'      => 'SID', // The cookie name.
        'lifetime'  => 0,     // The cookie lifetime in seconds, or 0 to use a browser session cookie.
        'path'      => '/',
        'domain'    => null,
        'secure'    => false,
        'http-only' => true
    ];

    /**
     * Class constructor.
     *
     * @param Storage\SessionStorage $storage      The session storage, or null to use a default file storage.
     * @param ObjectPacker|null      $objectPacker An optional object packer to use when serializing the session data.
     */
    public function __construct(Storage\SessionStorage $storage = null, ObjectPacker $objectPacker = null)
    {
        if ($storage === null) {
            $storage = new Storage\FileStorage(session_save_path());
        }

        $this->storage      = $storage;
        $this->cookieParams = self::$defaultCookieParams;

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
     * @param array $params
     *
     * @return Session
     */
    public function setCookieParams(array $params) : Session
    {
        $this->cookieParams = $params + $this->cookieParams;

        return $this;
    }

    /**
     * Sets the probability for the garbage collection to be triggered on any given request.
     *
     * For example, setGcProbability(1, 100) gives a 1% chance for the gc to be triggered.
     *
     * @param int $dividend
     * @param int $divisor
     *
     * @return Session
     */
    public function setGcProbability(int $dividend, int $divisor) : Session
    {
        $this->gcDividend = $dividend;
        $this->gcDivisor = $divisor;

        return $this;
    }

    /**
     * @param int $lifetime
     *
     * @return Session
     */
    public function setLifetime(int $lifetime) : Session
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Sets the length of the session id.
     *
     * The default length of 26 is short enough and allows for 4e46 combinations,
     * which makes it very secure and highly unlikely to get a collision.
     *
     * Do not change this value unless you have a very good reason to do so.
     *
     * @param int $length
     *
     * @return Session
     */
    public function setIdLength(int $length) : Session
    {
        $this->idLength = $length;

        return $this;
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
        $sessionId = $request->getCookie($this->cookieParams['name']);

        if ($sessionId !== null && $this->checkSessionId($sessionId)) {
            $this->id = $sessionId;
        } else {
            $this->id = $this->generateId();
        }

        if ($this->isTimeToCollectGarbage()) {
            $this->collectGarbage();
        }

        $this->data = [];
    }

    /**
     * Checks the validity of a session ID sent in a cookie.
     *
     * This is a security measure to avoid forged session cookies,
     * that could be used for example to hack session adapters.
     *
     * @param string $id
     *
     * @return bool
     */
    private function checkSessionId(string $id) : bool
    {
        if (preg_match('/^[A-Za-z0-9]+$/', $id) !== 1) {
            return false;
        }

        if (strlen($id) !== $this->idLength) {
            return false;
        }

        return true;
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
        $lifetime = $this->cookieParams['lifetime'];
        $expires = ($lifetime == 0) ? 0 : time() + $lifetime;

        $cookie = new Cookie($this->cookieParams['name'], $this->id);

        $cookie
            ->setExpires($expires)
            ->setPath($this->cookieParams['path'])
            ->setDomain($this->cookieParams['domain'])
            ->setSecure($this->cookieParams['secure'])
            ->setHttpOnly($this->cookieParams['http-only']);

        $response->setCookie($cookie);
    }

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

        $id = $this->getId();
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
        $id = $this->getId();
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
     * @return Session
     */
    public function clear() : Session
    {
        $id = $this->getId();
        $this->storage->clear($id);

        $this->data = [];

        return $this;
    }

    /**
     * Regenerates the session id.
     *
     * This is a useful security measure that can be used after user login to even
     * further limit the risks of session fixation attacks.
     *
     * Not all storage engines support id regeneration.
     * If the storage engine does not support regeneration, this method will do nothing.
     *
     * @return Session
     */
    public function regenerateId() : Session
    {
        $id = $this->generateId();

        if ($this->storage->updateId($this->id, $id)) {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * @return void
     */
    public function collectGarbage()
    {
        $this->storage->expire($this->lifetime);
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    private function getId() : string
    {
        if ($this->id === null) {
            throw new \RuntimeException(
                'Trying to access a Session object that has not yet been loaded. ' .
                'This most likely means that you have not added the SessionPlugin to your application.'
            );
        }

        return $this->id;
    }

    /**
     * Generates a random, alphanumeric session id.
     *
     * @return string
     */
    private function generateId() : string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $id = '';

        for ($i = 0; $i < $this->idLength; $i++) {
            $id .= $chars[random_int(0, 61)];
        }

        return $id;
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
}
