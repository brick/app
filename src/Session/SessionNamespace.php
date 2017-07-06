<?php

namespace Brick\App\Session;

/**
 * Wraps a session and automatically prefixes all keys.
 *
 * This class can be extended to provide application-specific
 * functionality around the session, such as flash messages.
 */
class SessionNamespace implements SessionInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param Session $session
     * @param string  $namespace
     */
    public function __construct(Session $session, $namespace)
    {
        $this->session   = $session;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->session->has($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->session->get($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->session->set($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->session->remove($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize($key, callable $function)
    {
        return $this->session->synchronize($this->getKey($key), $function);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getKey($key)
    {
        return $this->namespace .  '.' . $key;
    }
}
