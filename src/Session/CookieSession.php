<?php

declare(strict_types=1);

namespace Brick\App\Session;

use Brick\Http\Cookie;
use Brick\Http\Request;
use Brick\Http\Response;

/**
 * A session based on a session cookie.
 */
class CookieSession extends Session
{
    /**
     * The default cookie parameters.
     */
    private const DEFAULT_COOKIE_PARAMS = [
        'name'      => 'SID', // The cookie name.
        'lifetime'  => 0,     // The cookie lifetime in seconds, or 0 to use a browser session cookie.
        'path'      => '/',
        'domain'    => null,
        'secure'    => false,
        'http-only' => true
    ];

    /**
     * @var array
     */
    private $cookieParams = self::DEFAULT_COOKIE_PARAMS;

    /**
     * @var int
     */
    private $idLength = 26;

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
     * @inheritdoc
     */
    protected function readSessionId(Request $request) : void
    {
        $sessionId = $request->getCookie($this->cookieParams['name']);

        if ($sessionId !== null && $this->checkSessionId($sessionId)) {
            $this->id = $sessionId;
        } else {
            $this->id = $this->generateId();
        }
    }

    /**
     * @inheritdoc
     */
    protected function writeSessionId(Response $response) : void
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
}
