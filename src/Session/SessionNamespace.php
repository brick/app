<?php

declare(strict_types=1);

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
    private Session $session;

    /**
     * @var string
     */
    private string $namespace;

    /**
     * @param Session $session
     * @param string  $namespace
     */
    public function __construct(Session $session, string $namespace)
    {
        $this->session   = $session;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key) : bool
    {
        return $this->session->has($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        return $this->session->get($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value) : void
    {
        $this->session->set($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key) : void
    {
        $this->session->remove($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(string $key, callable $function)
    {
        return $this->session->synchronize($this->getKey($key), $function);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getKey(string $key) : string
    {
        return $this->namespace .  '.' . $key;
    }
}
