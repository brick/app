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
     * @param string    $id   The session id.
     * @param string    $key  The key to read from.
     * @param Lock|null $lock If not null, a lock must be acquired on the key for later writing.
     *                        A storage-specific context can be stored in the Lock object to keep track of the lock.
     *                        The same Lock object will be passed to the subsequent call to `write()` or `unlock()`.
     *
     * @return string|null The value read, or null if the key does not exist.
     */
    public function read(string $id, string $key, Lock|null $lock = null) : string|null;

    /**
     * Writes a specific key to the storage.
     *
     * @param string    $id    The session id.
     * @param string    $key   The key to write to.
     * @param string    $value The value to write.
     * @param Lock|null $lock  If not null, a lock set by `read()` is held on the key, and must be freed after writing.
     *                         The Lock object may contain a context set by `read()`.
     */
    public function write(string $id, string $key, string $value, Lock|null $lock = null) : void;

    /**
     * Unlocks the resources locked by an aborted synchronized read-write.
     *
     * In normal conditions, `read()` is called then `write()`.
     * If an exception occurs, `read()` is called then `unlock()`.
     *
     * @param Lock $lock The lock object, that may contain a context set by `read()`.
     */
    public function unlock(Lock $lock) : void;

    /**
     * Removes a specific key from the storage.
     *
     * Removing a non-existent key is a valid operation and must not throw an exception.
     */
    public function remove(string $id, string $key) : void;

    /**
     * Removes all the keys from the storage for the given session id.
     */
    public function clear(string $id) : void;

    /**
     * Removes all entries that have not been accessed for more than the given number of seconds.
     */
    public function expire(int $lifetime) : void;

    public function updateId(string $oldId, string $newId) : bool;
}
