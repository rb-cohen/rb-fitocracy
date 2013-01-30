<?php

class Fitocracy {

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';

    public $root = 'https://www.fitocracy.com';
    public $cookies = array();
    
    protected $_username;
    protected $_password;
    protected $_authenticated = false;

    public function __construct($username, $password){
        $this->_username = $username;
        $this->_password = $password;
    }
    
    public function getUser($username) {
        $this->authenticate();
        
        $path = sprintf('/get_user_json_from_username/%s/', urlencode($username));
        return $this->doJsonRequest($path, self::METHOD_GET);
    }
    
    public function authenticate() {
        if (!$this->_authenticated) {
            $csrf = $this->getCsrfToken();

            $data = array(
                'csrfmiddlewaretoken' => $csrf,
                'is_username' => 1,
                'json' => 1,
                'username' => $this->_username,
                'password' => $this->_password,
            );

            $options = array(
                'headers' => array(
                    'referer' => 'https://www.fitocracy.com/accounts/login/',
                    'user-agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:18.0) Gecko/20100101 Firefox/18.0',
                )
            );


            $response = $this->doJsonRequest('/accounts/login/', self::METHOD_POST, $data, $options);
            if (empty($response->success)) {
                throw new FitocracyException('Could not log in: ' . $response->error);
            }
            
            $this->_authenticated = true;
        }
        
        return $this;
    }

    public function getCsrfToken() {
        $response = $this->doRequest('/accounts/login/', self::METHOD_GET);
        if ($response['response']['code'] != 200) {
            throw new FitocracyException('Could not load login page');
        }

        $match = preg_match('/name=\'csrfmiddlewaretoken\' value=\'([a-zA-Z0-9]+)\'/', $response['body'], $matches);
        if ($match) {
            return $matches[1];
        }

        throw new FitocracyException('CSRF token not found');
    }

    public function doJsonRequest($path, $method, $data = array(), $options = array()) {
        $options['headers']['accepts'] = 'application/json';

        $response = $this->doRequest($path, $method, $data, $options);
        $json = @json_decode($response['body']);
        if (!$json) {
            throw new FitocracyException('Could not parse fitocracy response');
        }

        return $json;
    }

    public function doRequest($path, $method, array $data = array(), array $options = array()) {
        $options = array_merge($options, array(
            'body' => $data,
            'cookies' => $this->cookies,
                ));

        switch ($method) {
            case self::METHOD_GET:
                $response = wp_remote_get($this->root . $path, $options);
                break;
            case self::METHOD_POST:
                $response = wp_remote_post($this->root . $path, $options);
                break;
            default:
                throw new FitocracyException('Invalid method');
        }

        if (is_wp_error($response)) {
            throw new FitocracyException('Could not connect to fitocracy server');
        }

        foreach ($response['cookies'] as $cookie) {
            $this->cookies[$cookie->name] = $cookie;
        }

        return $response;
    }

}