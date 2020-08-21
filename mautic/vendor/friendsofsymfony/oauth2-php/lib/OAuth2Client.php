<?php

namespace OAuth2;

/**
 * OAuth2.0 draft v10 client-side implementation.
 *
 * @author Originally written by Naitik Shah <naitik@facebook.com>.
 * @author Update to draft v10 by Edison Wong <hswong3i@pantarei-design.com>.
 *
 * @sa     <a href="https://github.com/facebook/php-sdk">Facebook PHP SDK</a>.
 */
abstract class OAuth2Client
{
    /**
     * The default Cache Lifetime (in seconds).
     *
     * @var int
     */
    const DEFAULT_EXPIRES_IN = 3600;

    /**
     * The default Base domain for the Cookie.
     *
     * @var string
     */
    const DEFAULT_BASE_DOMAIN = '';

    /**
     * Array of persistent variables stored.
     *
     * @var array
     */
    protected $conf = array();

    /**
     * Returns a persistent variable.
     *
     * To avoid problems, always use lower case for persistent variable names.
     *
     * @param string $name    The name of the variable to return.
     * @param mixed  $default The default value to use if this variable has never been set.
     *
     * @return mixed   The value of the variable.
     */
    public function getVariable($name, $default = null)
    {
        return isset($this->conf[$name]) ? $this->conf[$name] : $default;
    }

    /**
     * Sets a persistent variable.
     *
     * To avoid problems, always use lower case for persistent variable names.
     *
     * @param string $name  The name of the variable to set.
     * @param mixed  $value The value to set.
     *
     * @return $this The client (for chained calls of this method)
     */
    public function setVariable($name, $value)
    {
        $this->conf[$name] = $value;

        return $this;
    }

    // Stuff that should get overridden by subclasses.
    //
    // I don't want to make these abstract, because then subclasses would have
    // to implement all of them, which is too much work.
    //
    // So they're just stubs. Override the ones you need.

    /**
     * Initialize a Drupal OAuth2.0 Application.
     *
     * @param array $config An associative array as below:
     *                      - base_uri: The base URI for the OAuth2.0 endpoints.
     *                      - code: (optional) The authorization code.
     *                      - username: (optional) The username.
     *                      - password: (optional) The password.
     *                      - client_id: (optional) The application ID.
     *                      - client_secret: (optional) The application secret.
     *                      - authorize_uri: (optional) The end-user authorization endpoint URI.
     *                      - access_token_uri: (optional) The token endpoint URI.
     *                      - services_uri: (optional) The services endpoint URI.
     *                      - cookie_support: (optional) true to enable cookie support.
     *                      - base_domain: (optional) The domain for the cookie.
     *                      - file_upload_support: (optional) true if file uploads are enabled.
     */
    public function __construct($config = array())
    {
        // We must set base_uri first.
        $this->setVariable('base_uri', $config['base_uri']);
        unset($config['base_uri']);

        // Use predefined OAuth2.0 params, or get it from $_REQUEST.
        foreach (array('code', 'username', 'password') as $name) {
            if (isset($config[$name])) {
                $this->setVariable($name, $config[$name]);
            } else {
                if (isset($_REQUEST[$name]) && !empty($_REQUEST[$name])) {
                    $this->setVariable($name, $_REQUEST[$name]);
                }
            }
            unset($config[$name]);
        }

        // Endpoint URIs.
        foreach (array('authorize_uri', 'access_token_uri', 'services_uri') as $name) {
            if (isset($config[$name])) {
                if (substr($config[$name], 0, 4) == "http") {
                    $this->setVariable($name, $config[$name]);
                } else {
                    $this->setVariable($name, $this->getVariable('base_uri') . $config[$name]);
                }
            }
            unset($config[$name]);
        }

        // Other else configurations.
        foreach ($config as $name => $value) {
            $this->setVariable($name, $value);
        }
    }

    /**
     * Try to get session object from custom method.
     *
     * By default we generate session object based on access_token response, or
     * if it is provided from server with $_REQUEST. For sure, if it is provided
     * by server it should follow our session object format.
     *
     * Session object provided by server can ensure the correct expires and
     * base_domain setup as predefined in server, also you may get more useful
     * information for custom functionality, too. BTW, this may require for
     * additional remote call overhead.
     *
     * You may wish to override this function with your custom version due to
     * your own server-side implementation.
     *
     * @param $accessToken (optional) A valid access token in associative array as below:
     *                     - accessToken: A valid accessToken generated by OAuth2.0 authorization endpoint.
     *                     - expires_in: (optional) A valid expires_in generated by OAuth2.0 authorization endpoint.
     *                     - refresh_token: (optional) A valid refresh_token generated by OAuth2.0 authorization endpoint.
     *                     - scope: (optional) A valid scope generated by OAuth2.0 authorization endpoint.
     *
     * @return string|null A valid session object in associative array for setup cookie, and null if not able to generate it with custom method.
     */
    protected function getSessionObject($accessToken = null)
    {
        $session = null;

        // Try generate local version of session cookie.
        if (!empty($accessToken) && isset($accessToken['access_token'])) {
            $session['access_token'] = $accessToken['access_token'];
            $session['base_domain'] = $this->getVariable('base_domain', self::DEFAULT_BASE_DOMAIN);
            $session['expires'] = isset($accessToken['expires_in']) ? time() + $accessToken['expires_in'] : time(
                ) + $this->getVariable('expires_in', self::DEFAULT_EXPIRES_IN);
            $session['refresh_token'] = isset($accessToken['refresh_token']) ? $accessToken['refresh_token'] : '';
            $session['scope'] = isset($accessToken['scope']) ? $accessToken['scope'] : '';
            $session['secret'] = md5(
                base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid()))
            );

            // Provide our own signature.
            $sig = self::generateSignature(
                $session,
                $this->getVariable('client_secret')
            );
            $session['sig'] = $sig;
        }

        // Try loading session from $_REQUEST.
        if (!$session && isset($_REQUEST['session'])) {
            $session = json_decode(
                get_magic_quotes_gpc()
                    ? stripslashes($_REQUEST['session'])
                    : $_REQUEST['session'],
                true
            );
        }

        return $session;
    }

    /**
     * Make an API call.
     *
     * Support both OAuth2.0 or normal GET/POST API call, with relative
     * or absolute URI.
     *
     * If no valid OAuth2.0 access token found in session object, this function
     * will automatically switch as normal remote API call without "oauth_token"
     * parameter.
     *
     * Assume server reply in JSON object and always decode during return. If
     * you hope to issue a raw query, please use makeRequest().
     *
     * @param string $path   The target path, relative to base_path/service_uri or an absolute URI.
     * @param string $method (optional) The HTTP method (default 'GET').
     * @param array  $params (optional The GET/POST parameters.
     *
     * @return string The JSON decoded response object.
     *
     * @throws OAuth2Exception
     */
    public function api($path, $method = 'GET', $params = array())
    {
        if (is_array($method) && empty($params)) {
            $params = $method;
            $method = 'GET';
        }

        // json_encode all params values that are not strings.
        foreach ($params as $key => $value) {
            if (!is_string($value)) {
                $params[$key] = json_encode($value);
            }
        }

        $result = json_decode(
            $this->makeOAuth2Request(
                $this->getUri($path),
                $method,
                $params
            ),
            true
        );

        // Results are returned, errors are thrown.
        if (is_array($result) && isset($result['error'])) {
            $e = new OAuth2Exception($result);
            switch ($e->getType()) {
                // OAuth 2.0 Draft 10 style.
                case 'invalid_token':
                    $this->setSession(null); // TODO: what is this?!?!
                    break;
                default:
                    $this->setSession(null);
            }
            throw $e;
        }

        return $result;
    }

    // End stuff that should get overridden.

    /**
     * Default options for cURL.
     *
     * @var array
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'oauth2-draft-v10',
        CURLOPT_HTTPHEADER => array("Accept: application/json"),
    );

    /**
     * Set the Session.
     *
     * @param $session     (optional) The session object to be set. null if hope to frush existing session object.
     * @param $writeCookie (optional) true if a cookie should be written. This value is ignored if cookie support has been disabled.
     *
     * @return self The current OAuth2.0 client-side instance.
     */
    public function setSession($session = null, $writeCookie = true)
    {
        $this->setVariable('_session', $this->validateSessionObject($session));
        $this->setVariable('_session_loaded', true);
        if ($writeCookie) {
            $this->setCookieFromSession($this->getVariable('_session'));
        }

        return $this;
    }

    /**
     * Get the session object.
     *
     * This will automatically look for a signed session via custom method, OAuth2.0 grant type with authorization_code,
     * OAuth2.0 grant type with password, or cookie that we had already setup.
     *
     * @return array|null The valid session object with OAuth2.0 information, and null if not able to discover any cases.
     */
    public function getSession()
    {
        if (!$this->getVariable('_session_loaded')) {
            $session = null;
            $write_cookie = true;

            // Try obtain login session by custom method.
            $session = $this->getSessionObject(null);
            $session = $this->validateSessionObject($session);

            // grant_type == authorization_code.
            if (!$session && $this->getVariable('code')) {
                $access_token = $this->getAccessTokenFromAuthorizationCode($this->getVariable('code'));
                $session = $this->getSessionObject($access_token);
                $session = $this->validateSessionObject($session);
            }

            // grant_type == password.
            if (!$session && $this->getVariable('username') && $this->getVariable('password')) {
                $access_token = $this->getAccessTokenFromPassword(
                    $this->getVariable('username'),
                    $this->getVariable('password')
                );
                $session = $this->getSessionObject($access_token);
                $session = $this->validateSessionObject($session);
            }

            // Try loading session from cookie if necessary.
            if (!$session && $this->getVariable('cookie_support')) {
                $cookie_name = $this->getSessionCookieName();
                if (isset($_COOKIE[$cookie_name])) {
                    $session = array();
                    parse_str(
                        trim(
                            get_magic_quotes_gpc()
                                ? stripslashes($_COOKIE[$cookie_name])
                                : $_COOKIE[$cookie_name],
                            '"'
                        ),
                        $session
                    );
                    $session = $this->validateSessionObject($session);
                    // Write only if we need to delete a invalid session cookie.
                    $write_cookie = empty($session);
                }
            }

            $this->setSession($session, $write_cookie);
        }

        return $this->getVariable('_session');
    }

    /**
     * Gets an OAuth2.0 access token from session.
     *
     * This will trigger getSession() and so we MUST initialize with required
     * configuration.
     *
     * @return string|null The valid OAuth2.0 access token, and null if not exists in session.
     */
    public function getAccessToken()
    {
        $session = $this->getSession();

        return isset($session['access_token']) ? $session['access_token'] : null;
    }

    /**
     * Get access token from OAuth2.0 token endpoint with authorization code.
     *
     * This function will only be activated if both access token URI, client
     * identifier and client secret are setup correctly.
     *
     * @param string $code Authorization code issued by authorization server's authorization endpoint.
     *
     * @return string A valid OAuth2.0 JSON decoded access token in associative array, and null if not enough parameters or JSON decode failed.
     */
    private function getAccessTokenFromAuthorizationCode($code)
    {
        if ($this->getVariable('access_token_uri') && $this->getVariable('client_id') && $this->getVariable(
                'client_secret'
            )
        ) {
            return json_decode(
                $this->makeRequest(
                    $this->getVariable('access_token_uri'),
                    'POST',
                    array(
                        'grant_type' => 'authorization_code',
                        'client_id' => $this->getVariable('client_id'),
                        'client_secret' => $this->getVariable('client_secret'),
                        'code' => $code,
                        'redirect_uri' => $this->getCurrentUri(),
                    )
                ),
                true
            );
        }

        return null;
    }

    /**
     * Get access token from OAuth2.0 token endpoint with basic user credentials.
     *
     * This function will only be activated if both username and password are setup correctly.
     *
     * @param string $username Username to be check with.
     * @param string $password Password to be check with.
     *
     * @return array|null A valid OAuth2.0 JSON decoded access token in associative array, and null if not enough parameters or JSON decode failed.
     */
    private function getAccessTokenFromPassword($username, $password)
    {
        if ($this->getVariable('access_token_uri') && $this->getVariable('client_id') && $this->getVariable(
                'client_secret'
            )
        ) {
            return json_decode(
                $this->makeRequest(
                    $this->getVariable('access_token_uri'),
                    'POST',
                    array(
                        'grant_type' => 'password',
                        'client_id' => $this->getVariable('client_id'),
                        'client_secret' => $this->getVariable('client_secret'),
                        'username' => $username,
                        'password' => $password,
                    )
                ),
                true
            );
        }

        return null;
    }

    /**
     * Make an OAuth2.0 Request.
     *
     * Automatically append "oauth_token" in query parameters if not yet
     * exists and able to discover a valid access token from session. Otherwise
     * just ignore setup with "oauth_token" and handle the API call AS-IS, and
     * so may issue a plain API call without OAuth2.0 protection.
     *
     * @param string $path   The target path, relative to base_path/service_uri or an absolute URI.
     * @param string $method (optional) The HTTP method (default 'GET').
     * @param array  $params (optional The GET/POST parameters.
     *
     * @return string The JSON decoded response object.
     *
     * @throws OAuth2Exception
     */
    protected function makeOAuth2Request($path, $method = 'GET', $params = array())
    {
        if ((!isset($params['oauth_token']) || empty($params['oauth_token'])) && $oauth_token = $this->getAccessToken()
        ) {
            $params['oauth_token'] = $oauth_token;
        }

        return $this->makeRequest($path, $method, $params);
    }

    /**
     * Makes an HTTP request.
     *
     * This method can be overriden by subclasses if developers want to do
     * fancier things or use something other than cURL to make the request.
     *
     * @param string        $path   The target path, relative to base_path/service_uri or an absolute URI.
     * @param string        $method (optional) The HTTP method (default 'GET').
     * @param array         $params (optional The GET/POST parameters.
     * @param resource|null $ch     (optional) An initialized curl handle
     *
     * @throws OAuth2Exception
     * @return string The JSON decoded response object.
     */
    protected function makeRequest($path, $method = 'GET', $params = array(), $ch = null)
    {
        if (!$ch) {
            $ch = curl_init();
        }

        $opts = self::$CURL_OPTS;
        if ($params) {
            switch ($method) {
                case 'GET':
                    $path .= '?' . http_build_query($params, null, '&');
                    break;
                // Method override as we always do a POST.
                default:
                    if ($this->getVariable('file_upload_support')) {
                        $opts[CURLOPT_POSTFIELDS] = $params;
                    } else {
                        $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
                    }
            }
        }
        $opts[CURLOPT_URL] = $path;

        // Disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $existing_headers = $opts[CURLOPT_HTTPHEADER];
            $existing_headers[] = 'Expect:';
            $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        } else {
            $opts[CURLOPT_HTTPHEADER] = array('Expect:');
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);

        if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
            error_log('Invalid or no certificate authority found, using bundled information');
            curl_setopt(
                $ch,
                CURLOPT_CAINFO,
                dirname(__FILE__) . '/fb_ca_chain_bundle.crt'
            );
            $result = curl_exec($ch);
        }

        if ($result === false) {
            $e = new OAuth2Exception(array(
                'code' => curl_errno($ch),
                'message' => curl_error($ch),
            ));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);

        // Split the HTTP response into header and body.
        list($headers, $body) = explode("\r\n\r\n", $result);
        $headers = explode("\r\n", $headers);

        // We catch HTTP/1.1 4xx or HTTP/1.1 5xx error response.
        if (strpos($headers[0], 'HTTP/1.1 4') !== false || strpos($headers[0], 'HTTP/1.1 5') !== false) {
            $result = array(
                'code' => 0,
                'message' => '',
            );

            if (preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches)) {
                $result['code'] = $matches[1];
                $result['message'] = $matches[2];
            }

            // In case retrun with WWW-Authenticate replace the description.
            foreach ($headers as $header) {
                if (preg_match("/^WWW-Authenticate:.*error='(.*)'/", $header, $matches)) {
                    $result['error'] = $matches[1];
                }
            }

            return json_encode($result);
        }

        return $body;
    }

    /**
     * The name of the cookie that contains the session object.
     *
     * @return string The cookie name.
     */
    private function getSessionCookieName()
    {
        return 'oauth2_' . $this->getVariable('client_id');
    }

    /**
     * Set a JS Cookie based on the _passed in_ session.
     *
     * It does not use the currently stored session - you need to explicitly
     * pass it in.
     *
     * @param array|null $session The session to use for setting the cookie.
     */
    protected function setCookieFromSession($session = null)
    {
        if (!$this->getVariable('cookie_support')) {
            return;
        }

        $cookie_name = $this->getSessionCookieName();
        $value = 'deleted';
        $expires = time() - 3600;
        $base_domain = $this->getVariable('base_domain', self::DEFAULT_BASE_DOMAIN);
        if ($session) {
            $value = '"' . http_build_query($session, null, '&') . '"';
            $base_domain = isset($session['base_domain']) ? $session['base_domain'] : $base_domain;
            $expires = isset($session['expires']) ? $session['expires'] : time() + $this->getVariable(
                    'expires_in',
                    self::DEFAULT_EXPIRES_IN
                );
        }

        // Prepend dot if a domain is found.
        if ($base_domain) {
            $base_domain = '.' . $base_domain;
        }

        // If an existing cookie is not set, we dont need to delete it.
        if ($value == 'deleted' && empty($_COOKIE[$cookie_name])) {
            return;
        }

        if (headers_sent()) {
            error_log('Could not set cookie. Headers already sent.');
        } else {
            setcookie($cookie_name, $value, $expires, '/', $base_domain);
        }
    }

    /**
     * Validates a session_version = 3 style session object.
     *
     * @param array $session The session object.
     *
     * @return array|null The session object if it validates, null otherwise.
     */
    protected function validateSessionObject($session)
    {
        // Make sure some essential fields exist.
        if (is_array($session) && isset($session['access_token']) && isset($session['sig'])) {
            // Validate the signature.
            $sessionWithoutSig = $session;
            unset($sessionWithoutSig['sig']);

            $expected_sig = self::generateSignature(
                $sessionWithoutSig,
                $this->getVariable('client_secret')
            );

            if ($session['sig'] != $expected_sig) {
                error_log('Got invalid session signature in cookie.');
                $session = null;
            }
        } else {
            $session = null;
        }

        return $session;
    }

    /**
     * Since $_SERVER['REQUEST_URI'] is only available on Apache, we
     * generate an equivalent using other environment variables.
     */
    public function getRequestUri()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            if (isset($_SERVER['argv'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
            } elseif (isset($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
            } else {
                $uri = $_SERVER['SCRIPT_NAME'];
            }
        }
        // Prevent multiple slashes to avoid cross site requests via the Form API.
        $uri = '/' . ltrim($uri, '/');

        return $uri;
    }

    /**
     * Returns the Current URL.
     *
     * @return string The current URL.
     */
    protected function getCurrentUri()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
            ? 'https://'
            : 'http://';
        $current_uri = $protocol . $_SERVER['HTTP_HOST'] . $this->getRequestUri();
        $parts = parse_url($current_uri);

        $query = '';
        if (!empty($parts['query'])) {
            $params = array();
            parse_str($parts['query'], $params);
            $params = array_filter($params);
            if (!empty($params)) {
                $query = '?' . http_build_query($params, null, '&');
            }
        }

        // Use port if non default.
        $port = isset($parts['port']) &&
        (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443))
            ? ':' . $parts['port'] : '';

        // Rebuild.
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }

    /**
     * Build the URL for given path and parameters.
     *
     * @param $path   (optional) The path.
     * @param $params (optional) The query parameters in associative array.
     *
     * @return string The URL for the given parameters.
     */
    protected function getUri($path = '', $params = array())
    {
        $url = $this->getVariable('services_uri') ? $this->getVariable('services_uri') : $this->getVariable('base_uri');

        if (!empty($path)) {
            if (substr($path, 0, 4) == "http") {
                $url = $path;
            } else {
                $url = rtrim($url, '/') . '/' . ltrim($path, '/');
            }
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params, null, '&');
        }

        return $url;
    }

    /**
     * Generate a signature for the given params and secret.
     *
     * @param array  $params The parameters to sign.
     * @param string $secret The secret to sign with.
     *
     * @return string The generated signature
     */
    protected function generateSignature($params, $secret)
    {
        // Work with sorted data.
        ksort($params);

        // Generate the base string.
        $base_string = '';
        foreach ($params as $key => $value) {
            $base_string .= $key . '=' . $value;
        }
        $base_string .= $secret;

        return md5($base_string);
    }
}
