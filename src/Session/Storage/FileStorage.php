<?php

namespace Brick\App\Session\Storage;

use Brick\FileSystem\File;
use Brick\FileSystem\FileSystem;
use Brick\FileSystem\Path;
use Brick\FileSystem\RecursiveFileIterator;

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
     * @todo Currently unused
     *
     * @var integer
     */
    private $mode;

    /**
     * The grace time during which the last access time of the session file is not updated.
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
     * @var integer
     */
    private $accessGraceTime = 300;

    /**
     * @var \Brick\FileSystem\FileSystem
     */
    private $fs;

    /**
     * Class constructor.
     *
     * @param string  $directory
     * @param string  $prefix
     * @param integer $mode
     */
    public function __construct($directory, $prefix = '', $mode = 0700)
    {
        $this->directory = $directory;
        $this->prefix    = $prefix;
        $this->mode      = $mode;
        $this->fs        = new FileSystem();
    }

    /**
     * {@inheritdoc}
     */
    public function read($id, $key, & $lockContext)
    {
        $path = new Path($this->getPath($id, $key));

        if (! $path->exists()) {
            return null;
        }

        if ($path->getFileInfo()->getATime() < time() - $this->accessGraceTime) {
            $path->touch();
        }

        $file = $this->openFile($id, $key);

        $file->lock($lockContext);
        $data = $file->read();

        if ($lockContext) {
            // Keep the file locked and store the file object.
            $lockContext = $file;
        } else {
            // Unlock immediately and discard the file object.
            $file->unlock();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write($id, $key, $value, $lockContext)
    {
        if ($lockContext) {
            /** @var File $file */
            $file = $lockContext;
        } else {
            $this->fs->tryCreateDirectory($this->getPath($id));
            $file = $this->openFile($id, $key);
            $file->lock();
        }

        $file->truncate(0);
        $file->seek(0);
        $file->write($value);
        $file->unlock();
    }

    /**
     * {@inheritdoc}
     */
    public function unlock($lockContext)
    {
        /** @var File $lockContext */
        $lockContext->unlock();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, $key)
    {
        $this->fs->tryDelete($this->getPath($id, $key));
    }

    /**
     * {@inheritdoc}
     */
    public function clear($id)
    {
        $this->fs->tryDelete($this->getPath($id));
    }

    /**
     * {@inheritdoc}
     */
    public function expire($lifetime)
    {
        /** @var \SplFileInfo[] $files */
        $files = new RecursiveFileIterator($this->directory);

        foreach ($files as $file) {
            if ($file->isFile() && $file->getATime() < time() - $lifetime) {
                $this->fs->tryDelete($file);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateId($oldId, $newId)
    {
        return $this->fs->tryMove(
            $this->getPath($oldId),
            $this->getPath($newId)
        );
    }

    /**
     * Opens the session file for reading and writing, pointer at the beginning.
     *
     * @param string $id
     * @param string $key
     *
     * @return \Brick\FileSystem\File
     *
     * @throws \Brick\FileSystem\FileSystemException
     */
    private function openFile($id, $key)
    {
        return new File($this->getPath($id, $key), 'cb+');
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function sanitize($fileName)
    {
        $charBlacklist = '\/:*?"<>|';
        $escapeChar = '%';

        $result = '';
        $length = strlen($fileName);

        for ($i = 0; $i < $length; $i++) {
            $char = $fileName[$i];
            $ord = ord($char);

            if ($ord <= 32 || $ord >= 127 || strpos($charBlacklist, $char) !== false) {
                $result .= $escapeChar . bin2hex($char);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * @param string $id
     * @param string $key
     *
     * @return string
     */
    private function getPath($id, $key = null)
    {
        $directoryPath = $this->directory . DIRECTORY_SEPARATOR . $this->prefix . $this->sanitize($id);

        if ($key === null) {
            return $directoryPath;
        }

        return $directoryPath . DIRECTORY_SEPARATOR . $this->sanitize($key);
    }
}
