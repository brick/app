<?php

declare(strict_types=1);

namespace Brick\App\Session\Storage;

/**
 * File storage engine for storing sessions on the filesystem.
 */
class FileStorage implements SessionStorage
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $prefix;

    /**
     * The grace time during which the last access time of the session file is not updated, in seconds.
     *
     * On filesystems mounted with the noatime option, the access time of the session file is not updated when the file
     * is read. Filesystems mounted with the relatime option exhibit the same problem, as the access time is not updated
     * in real time, but at a regular interval (typically once a day). Same issue on Windows, where NTFS delays updates
     * to the last access time by up to 1 hour after the last access.
     *
     * If we wanted to precisely know the last access time of the session file, we would therefore have to touch() it on
     * every read, which could be a performance bottleneck.
     *
     * Instead, we configure a grace time during which a read will not update the access time, thus reducing the number
     * of writes.
     *
     * This can affect the lifetime of a session: for a 30 minutes session, a grace time of 5 minutes would make
     * the session actually last for between 25 and 30 minutes after the last read.
     *
     * @var int
     */
    private $accessGraceTime = 300;

    /**
     * Class constructor.
     *
     * @param string $directory
     * @param string $prefix
     */
    public function __construct(string $directory, string $prefix = '')
    {
        $this->directory = $directory;
        $this->prefix    = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id, string $key, Lock $lock = null) : ?string
    {
        $path = $this->getPath($id, $key);

        if (! file_exists($path)) {
            return null;
        }

        if (fileatime($path) < time() - $this->accessGraceTime) {
            touch($path);
        }

        $fp = fopen($path, $lock ? 'cb+' : 'rb');
        flock($fp, LOCK_EX);

        $data = stream_get_contents($fp);

        if ($lock) {
            // Keep the file open & locked, and remember the resource.
            $lock->context = $fp;
        } else {
            // Unlock immediately and close the file.
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $key, string $value, Lock $lock = null) : void
    {
        if ($lock) {
            $fp = $lock->context;

            if ($fp !== null) {
                ftruncate($fp, 0);
                fseek($fp, 0);
                fwrite($fp, $value);
                flock($fp, LOCK_UN);
                fclose($fp);

                return;
            }
        }

        $path = $this->getPath($id, $key);
        file_put_contents($path, $value, LOCK_EX);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(Lock $lock) : void
    {
        $fp = $lock->context;

        if ($fp !== null) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $id, string $key) : void
    {
        $path = $this->getPath($id, $key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $id) : void
    {
        $files = glob($this->directory . DIRECTORY_SEPARATOR . $this->prefix . $id . '_*');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function expire(int $lifetime) : void
    {
        $files = new \DirectoryIterator($this->directory);

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getATime() >= time() - $lifetime) {
                continue;
            }

            if ($this->prefix !== '' && strpos($file->getFilename(), $this->prefix) !== 0) {
                continue;
            }

            unlink($file->getPathname());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateId(string $oldId, string $newId) : bool
    {
        $prefix = $this->directory . DIRECTORY_SEPARATOR . $this->prefix;
        $prefixOldId = $prefix . $oldId;
        $prefixNewId = $prefix . $newId;
        $prefixOldIdLength = strlen($prefixOldId);

        $files = glob($prefixOldId . '_*');

        foreach ($files as $file) {
            $newFile = $prefixNewId . substr($file, $prefixOldIdLength);
            rename($file, $newFile);
        }

        return true;
    }

    /**
     * @param string $id
     * @param string $key
     *
     * @return string
     */
    private function getPath(string $id, string $key) : string
    {
        // Sanitize the session key: it may contain characters that could conflict with the filesystem.
        // We only allow the resulting file name to contain ASCII letters & digits, dashes, underscores and dots.
        // All other chars are hex-encoded, dot is used as an escape character.
        $key = preg_replace_callback('/[^A-Za-z0-9\-_]/', static function ($matches) {
            return '.' . bin2hex($matches[0]);
        }, $key);

        return $this->directory . DIRECTORY_SEPARATOR . $this->prefix . $id . '_' . $key;
    }
}
