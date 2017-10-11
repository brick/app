<?php

declare(strict_types=1);

namespace Brick\App\Session;

/**
 * Common interface for Session and SessionNamespace.
 */
interface SessionInterface
{
    /**
     * Checks if the session contains the given key.
     *
     * If the NULL value is stored, this method will return false.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key) : bool;

    /**
     * Reads a key from the session.
     *
     * @param string $key The key to read from.
     *
     * @return mixed The value, null if the key does not exist.
     */
    public function get(string $key);

    /**
     * Writes a key/value pair to the session.
     *
     * @param string $key   The key to write to.
     * @param mixed  $value The value to write.
     *
     * @return void
     */
    public function set(string $key, $value) : void;

    /**
     * Removes a key from the session.
     *
     * @param string $key The key to remove.
     *
     * @return void
     */
    public function remove(string $key) : void;

    /**
     * Performs a synchronized read & write of a session key.
     *
     * Calls to this method for a given key will be processed sequentially across processes.
     *
     * @param string   $key      The key to read and write.
     * @param callable $function A function that will receive the value and return the new value to write.
     *
     * @return mixed The return value of the function.
     */
    public function synchronize(string $key, callable $function);
}
