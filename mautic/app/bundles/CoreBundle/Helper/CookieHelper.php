<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CookieHelper.
 */
class CookieHelper
{
    private $path     = null;
    private $domain   = null;
    private $secure   = false;
    private $httponly = false;

    /**
     * CookieHelper constructor.
     *
     * @param              $cookiePath
     * @param              $cookieDomain
     * @param              $cookieSecure
     * @param              $cookieHttp
     * @param RequestStack $requestStack
     */
    public function __construct($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, RequestStack $requestStack)
    {
        $this->path     = $cookiePath;
        $this->domain   = $cookieDomain;
        $this->secure   = $cookieSecure;
        $this->httponly = $cookieHttp;

        $this->request = $requestStack->getCurrentRequest();
        if (('' === $this->secure || null === $this->secure) && $this->request) {
            $this->secure = filter_var($requestStack->getCurrentRequest()->server->get('HTTPS', false), FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * @param      $name
     * @param      $value
     * @param int  $expire
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param bool $httponly
     */
    public function setCookie($name, $value, $expire = 1800, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if ($this->request == null) {
            return true;
        }

        setcookie(
            $name,
            $value,
            ($expire) ? time() + $expire : null,
            ($path == null) ? $this->path : $path,
            ($domain == null) ? $this->domain : $domain,
            ($secure == null) ? $this->secure : $secure,
            ($httponly == null) ? $this->httponly : $httponly
        );
    }

    /**
     * Deletes a cookie by expiring it.
     *
     * @param           $name
     * @param null      $path
     * @param null      $domain
     * @param null      $secure
     * @param bool|true $httponly
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setCookie($name, '', time() - 3600, $path, $domain, $secure, $httponly);
    }
}
