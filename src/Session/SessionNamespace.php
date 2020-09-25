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
    private Session $session;

    private string $namespace;

    public function __construct(Session $session, string $namespace)
    {
        $this->session   = $session;
        $this->namespace = $namespace;
    }

    public function has(string $key) : bool
    {
        return $this->session->has($this->getKey($key));
    }

    public function get(string $key) : mixed
    {
        return $this->session->get($this->getKey($key));
    }

    public function set(string $key, mixed $value) : void
    {
        $this->session->set($this->getKey($key), $value);
    }

    public function remove(string $key) : void
    {
        $this->session->remove($this->getKey($key));
    }

    public function synchronize(string $key, callable $function) : mixed
    {
        return $this->session->synchronize($this->getKey($key), $function);
    }

    private function getKey(string $key) : string
    {
        return $this->namespace .  '.' . $key;
    }
}
