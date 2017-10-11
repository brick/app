<?php

declare(strict_types=1);

namespace Brick\App\Session\Storage;

/**
 * Interface that session storage engines must implement.
 */
interface SessionStorage
{
    /**
     * Reads a specific key from the storage.
     *
     * @param string $id          The session id.
     * @param string $key         The key to read from.
     * @param mixed  $lockContext Boolean representing whether to keep a lock on the key for later writing.
     *                            Can be overridden by a storage-specific variable to keep track of the lock.
     *                            This variable will be passed as is to the subsequent call to write().
     *                            Must only be overridden when true.
     *
     * @return string|null The value read, or null if the key does not exist.
     */
    public function read(string $id, string $key, & $lockContext) : ?string;

    /**
     * Writes a specific key to the storage.
     *
     * @param string $id          The session id.
     * @param string $key         The key to write to.
     * @param string $value       The value to write.
     * @param mixed  $lockContext The lock context as set by read().
     *                            If not false, the context must be unlocked by this method.
     *
     * @return void
     */
    public function write(string $id, string $key, string $value, $lockContext) : void;

    /**
     * Unlocks the resources locked by an aborted synchronized read-write.
     *
     * In normal conditions, read() is called then write().
     * If an exception occurs, read() is called then unlock().
     *
     * @param mixed $lockContext The lock context as set by read().
     *
     * @return void
     */
    public function unlock($lockContext) : void;

    /**
     * Removes a specific key from the storage.
     *
     * Removing a non-existent key is a valid operation and must not throw an exception.
     *
     * @param string $id
     * @param string $key
     *
     * @return void
     */
    public function remove(string $id, string $key) : void;

    /**
     * Removes all the keys from the storage for the given session id.
     *
     * @param string $id
     *
     * @return void
     */
    public function clear(string $id) : void;

    /**
     * Removes all entries that have not been accessed for more than the given number of seconds.
     *
     * @param int $lifetime
     *
     * @return void
     */
    public function expire(int $lifetime) : void;

    /**
     * @param string $oldId
     * @param string $newId
     *
     * @return bool
     */
    public function updateId(string $oldId, string $newId) : bool;
}
