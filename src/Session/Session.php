<?php

namespace Brick\App\Session;

use Brick\Http\Request;
use Brick\Http\Response;
use Brick\Http\Cookie;

use Brick\Packing\Packer;
use Brick\Packing\ObjectPacker;
use Brick\Packing\NullObjectPacker;

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
     * The object packer.
     *
     * @var \Brick\Packing\Packer
     */
    private $packer;

    /**
     * The session id, or null if the session has not been read yet.
     *
     * @var string|null
     */
    private $id = null;

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
     * @var integer
     */
    private $gcDividend = 1;

    /**
     * @var integer
     */
    private $gcDivisor = 100;

    /**
     * @var integer
     */
    private $lifetime = 1800;

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
     * @param \Brick\App\Session\Storage\SessionStorage $storage
     * @param \Brick\Packing\ObjectPacker|null      $packer
     */
    public function __construct(Storage\SessionStorage $storage, ObjectPacker $packer = null)
    {
        $this->storage      = $storage;
        $this->packer       = new Packer($packer ?: new NullObjectPacker());
        $this->cookieParams = self::$defaultCookieParams;
    }

    /**
     * Creates a session with default parameters, with a local filesystem storage.
     *
     * @return Session
     *
     * @throws \RuntimeException
     */
    public static function create()
    {
        $directory = session_save_path();
        $storage = new Storage\FileStorage($directory);

        return new Session($storage);
    }

    /**
     * @param string $namespace
     *
     * @return SessionNamespace
     */
    public function getNamespace($namespace)
    {
        return new SessionNamespace($this, $namespace);
    }

    /**
     * @param array $params
     *
     * @return Session
     */
    public function setCookieParams(array $params)
    {
        $this->cookieParams = $params + $this->cookieParams;

        return $this;
    }

    /**
     * Sets the probability for the garbage collection to be triggered on any given request.
     *
     * For example, setGcProbability(1, 100) gives a 1% chance for the gc to be triggered.
     *
     * @param integer $dividend
     * @param integer $divisor
     *
     * @return Session
     */
    public function setGcProbability($dividend, $divisor)
    {
        $this->gcDividend = $dividend;
        $this->gcDivisor = $divisor;

        return $this;
    }

    /**
     * @param integer $lifetime
     *
     * @return Session
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Reads the session cookie from the request.
     *
     * @param \Brick\Http\Request $request
     *
     * @return void
     */
    public function handleRequest(Request $request)
    {
        $sessionId = $request->getCookie($this->cookieParams['name']);
        $this->id = ($sessionId !== null) ? $sessionId : $this->generateId();

        if ($this->isTimeToCollectGarbage()) {
            $this->collectGarbage();
        }

        $this->data = [];
    }

    /**
     * Writes the session cookie to the Response.
     *
     * @param \Brick\Http\Response $response
     *
     * @return void
     */
    public function handleResponse(Response $response)
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
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $id = $this->getId();
        $lockContext = false;
        $value = $this->storage->read($id, $key, $lockContext);

        if ($value !== null) {
            $value = $this->packer->unserialize($value);
        }

        return $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if ($value === null) {
            $this->remove($key);

            return;
        }

        $id = $this->getId();
        $serialized = $this->packer->serialize($value);
        $this->storage->write($id, $key, $serialized, false);

        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $id = $this->getId();
        $this->storage->remove($id, $key);

        unset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize($key, callable $function)
    {
        $id = $this->getId();
        $lockContext = true;
        $serialized = $this->storage->read($id, $key, $lockContext);

        try {
            $value = ($serialized !== null) ? $this->packer->unserialize($serialized) : null;
            $value = $function($value);

            $serialized = $this->packer->serialize($value);
        } catch (\Exception $e) {
            $this->storage->unlock($lockContext);

            throw $e;
        }

        $this->storage->write($id, $key, $serialized, $lockContext);

        return $this->data[$key] = $value;
    }

    /**
     * @return Session
     */
    public function clear()
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
    public function regenerateId()
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
    private function getId()
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
     * Generates a unique session id based on the current time, the client IP address, and a random value.
     *
     * The session id is 32 hexadecimal chars long.
     *
     * @return string
     */
    private function generateId()
    {
        return md5($this->getRandomBytes(64));
    }

    /**
     * @param integer $length The length of the binary string to return.
     *
     * @return string A random binary string of the given length.
     */
    private function getRandomBytes($length)
    {
        if (extension_loaded('openssl')) {
            $bytes = openssl_random_pseudo_bytes($length);
            if ($bytes !== false) {
                return $bytes;
            }
        }

        if (file_exists('/dev/urandom')) {
            return file_get_contents('/dev/urandom', false, null, -1, $length);
        }

        $bytes = '';

        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0x00, 0xff));
        }

        return $bytes;
    }

    /**
     * @return boolean
     */
    private function isTimeToCollectGarbage()
    {
        return rand(0, $this->gcDivisor - 1) < $this->gcDividend;
    }
}
