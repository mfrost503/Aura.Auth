<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

use Aura\Auth\Session\SessionDataInterface;
use Aura\Auth\Session\SessionManagerInterface;

/**
 *
 * Authentication manager.
 *
 * @package Aura.Auth
 *
 */
class Auth
{
    /**
     *
     * The user is anonymous/unauthenticated.
     *
     * @const string
     *
     */
    const ANON = 'ANON';

    /**
     *
     * The max time for authentication has expired.
     *
     * @const string
     *
     */
    const EXPIRED = 'EXPIRED';

    /**
     *
     * The authenticated user has been idle for too long.
     *
     * @const string
     *
     */
    const IDLE = 'IDLE';

    /**
     *
     * The user is authenticated and has not idled or expired.
     *
     * @const string
     *
     */
    const VALID = 'VALID';

    /**
     *
     * A session manager.
     *
     * @var Session
     *
     */
    protected $manager;

    /**
     *
     * Session data.
     *
     * @var SessionDataInterface
     *
     */
    protected $data;

    /**
     *
     * A idle/expire timer.
     *
     * @var Timer
     *
     */
    protected $timer;

    /**
     *
     * Constructor.
     *
     * @param SessionManagerInterface $manager A session manager.
     *
     * @param SessionDataInterface $data A session data store.
     *
     * @param Timer $timer An idle/expire timer.
     *
     * @return self
     *
     */
    public function __construct(
        SessionManagerInterface $manager,
        SessionDataInterface $data,
        Timer $timer
    ) {
        $this->manager = $manager;
        $this->data = $data;
        $this->timer = $timer;

        $this->manager->resume();
        $this->refresh();
    }

    /**
     *
     * Refreshes the "last active" time, logging out the user as idled or
     * expired if needed.
     *
     * Multiple calls to this method may result in idled or expired
     * authentication in the middle of script execution.
     *
     * @return bool Whether or not authentication is still valid.
     *
     * @see hasExpired()
     *
     * @see hasIdled()
     *
     * @see logout()
     *
     */
    public function refresh()
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->timer->hasExpired($this->data->initial)) {
            $this->data->status = self::EXPIRED;
            $this->manager->regenerateId();
            return false;
        }

        if ($this->timer->hasIdled($this->data->active)) {
            $this->data->status = self::IDLE;
            $this->manager->regenerateId();
            return false;
        }

        $this->data->active = time();
        return true;
    }

    /**
     *
     * Forces a successful login, bypassing the adapter.
     *
     * @param string $user The authenticated user.
     *
     * @param string $info Information about the user.
     *
     * @return null
     *
     */
    public function forceLogin($user, $info = array())
    {
        $this->manager->start();
        $this->manager->regenerateId();

        $this->data->status = self::VALID;
        $this->data->initial = time();
        $this->data->active = $this->data->initial;
        $this->data->user = $user;
        $this->data->info = $info;
    }

    /**
     *
     * Forces a successful logout, bypassing the adapter.
     *
     * @return null
     *
     */
    public function forceLogout($status = self::ANON)
    {
        $this->manager->regenerateId();

        $this->data->status = $status;
        unset($this->data->initial);
        unset($this->data->active);
        unset($this->data->user);
        unset($this->data->info);
    }

    /**
     *
     * Is the user authenticated?
     *
     * @return bool
     *
     */
    public function isValid()
    {
        return $this->getStatus() == self::VALID;
    }

    /**
     *
     * Is the user anonymous?
     *
     * @return bool
     *
     */
    public function isAnon()
    {
        return $this->getStatus() == self::ANON;
    }

    /**
     *
     * Has the user been idle for too long?
     *
     * @return bool
     *
     */
    public function isIdle()
    {
        return $this->getStatus() == self::IDLE;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function isExpired()
    {
        return $this->getStatus() == self::EXPIRED;
    }

    /**
     *
     * Gets the current authentication status.
     *
     * @return string
     *
     */
    public function getStatus()
    {
        $status = $this->data->status;
        if (! $status) {
            $status = self::ANON;
        }
        return $status;
    }

    /**
     *
     * Gets the initial authentication time.
     *
     * @return int
     *
     */
    public function getInitial()
    {
        return $this->data->initial;
    }

    /**
     *
     * Gets the last active time.
     *
     * @return int
     *
     */
    public function getActive()
    {
        return $this->data->active;
    }

    /**
     *
     * Gets the current user name.
     *
     * @return string
     *
     */
    public function getUser()
    {
        return $this->data->user;
    }

    /**
     *
     * Gets the current user information.
     *
     * @return array()
     *
     */
    public function getInfo()
    {
        $info = $this->data->info;
        if (! $info) {
            $info = array();
        }
        return $info;
    }
}