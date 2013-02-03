<?php

require_once(__DIR__ . '/Fitocracy.php');
require_once(__DIR__ . '/FitocracyException.php');

class RbFitocracy {

    const CACHE_TRY = 1;
    const CACHE_SET = 2;
    const CACHE_DEFAULT = 3;

    public $pluginName = 'RB Fitocracy';
    public $availableOptions = array(
        'rb-fitocracy-username',
        'rb-fitocracy-password',
    );
    protected $_options;
    protected $_fitocracy;

    public function init() {
        if (version_compare(PHP_VERSION, '5.2.4', '<')) {
            deactivate_plugins(basename(RBFITOCRACY_FILE));
            wp_die(__("RB Fitocracy requires PHP 5.2.4 or higher. Ask your host how to enable PHP 5 as the default on your servers.", 'rb-fitocracy'));
        }

        if (!function_exists('mcrypt_create_iv')) {
            deactivate_plugins(basename(RBFITOCRACY_FILE));
            wp_die(__("RB Fitocracy requires mcrypt. Ask your host how to enable mcrypt for PHP on your servers.", 'rb-fitocracy'));
        }

        if (!wp_next_scheduled('rb-fitocracy-update-user')) {
            wp_schedule_event(time(), 'hourly', 'rb-fitocracy-update-user');
        }
    }

    public function admin_menu() {
        add_options_page(
                __('RB Fitocracy Options', 'rb-fitocracy'), __('RB Fitocracy', 'rb-fitocracy'), 'manage_options', basename(RBFITOCRACY_FILE), array(
            $this,
            'admin_options_form'
                )
        );
    }

    public function admin_options_save() {
        if (!empty($_POST['rbfitocracy_submit'])) {
            $options = array(
                'rb-fitocracy-username' => sanitize_text_field($_POST['rb-fitocracy-username']),
            );

            if (!empty($_POST['rb-fitocracy-password'])) {
                $options['rb-fitocracy-password'] = $this->encrypt($_POST['rb-fitocracy-password'], AUTH_KEY);
            }

            $this->save_options($options)
                    ->cache_clear('rb-fitocracy-user_' . $options['rb-fitocracy-username'])
                    ->get_options(true);

            wp_redirect(admin_url('options-general.php?page=rb-fitocracy.php&rb-saved=true'));
        }
    }

    public function admin_options_form() {
        $options = $this->get_options();
        $updateSuccess = (bool) $_GET['rb-saved'];
        include_once(RBFITOCRACY_DIR . '/src/templates/admin-options.php');
    }

    public function get_options($refresh = false) {
        if ($this->_options === null || $refresh) {
            $this->_options = array();
            foreach ($this->availableOptions as $option) {
                $this->_options[$option] = get_option($option);
            }
        }

        return $this->_options;
    }

    public function get_option($key) {
        $options = $this->get_options();
        return array_key_exists($key, $options) ? $options[$key] : false;
    }

    public function save_options(array $options) {
        foreach ($options as $key => $value) {
            update_option($key, $value);
        }

        return $this;
    }

    public function admin_notices() {
        if (current_user_can('manage_options') or current_user_can('publish_posts')) {
            try {
                $validUsername = $this->confirm_user_valid();
            } catch (FitocracyException $e) {
                $validUsername = false;
                $error = $e->getMessage();
            }

            if ($validUsername === false) {
                $url = add_query_arg(array('page' => basename(RBFITOCRACY_FILE)), admin_url('options-general.php'));
                $message = sprintf(__('RB Fitocracy:  %s [<a href="%s">Settings</a>]', 'rb-fitocracy'), $error, $url);
                echo '<div class="error"><p>' . $message . '</p></div>';
            }
        }
    }

    public function confirm_user_valid() {
        $username = $this->get_option('rb-fitocracy-username');
        $password = $this->decrypt($this->get_option('rb-fitocracy-username'), AUTH_KEY);

        if (empty($username) || empty($password)) {
            throw new FitocracyException('Username or password not set');
        }

        $user = $this->get_user($username);
        return !empty($user->username);
    }

    public function get_fitocracy() {
        if ($this->_fitocracy === null) {
            $username = $this->get_option('rb-fitocracy-username');
            $password = $this->decrypt($this->get_option('rb-fitocracy-password'), AUTH_KEY);

            $this->_fitocracy = new Fitocracy($username, $password);
        }

        return $this->_fitocracy;
    }

    public function get_user($username, $cacheStrategy = self::CACHE_DEFAULT) {
        $cacheKey = 'rb-fitocracy-user_' . $username;

        if ($cacheStrategy & self::CACHE_TRY || !$user = $this->cache_get($cacheKey)) {
            $fitocracy = $this->get_fitocracy();
            $user = $fitocracy->getUser($username);

            if ($cacheStrategy & self::CACHE_SET) {
                $this->cache_set($cacheKey, $user, 86400);
            }
        }

        return $user;
    }

    public function update_user_cache() {
        try {
            $username = $this->get_option('rb-fitocracy-username');
            $this->get_user($username, self::CACHE_SET);
        } catch (Exception $e) {
            echo $e;
        }
        
        return $this;
    }

    public function cache_set($key, $data, $timeout = 86400) {
        set_transient($key, $data, $timeout);
        return $this;
    }

    public function cache_get($key) {
        return get_transient($key);
    }

    public function cache_clear($key) {
        delete_transient($key);
        return $this;
    }

    public function render_widget($args) {
        extract($args);
        $options = $this->get_widget_options();

        echo $before_widget;
        echo $before_title . $options['title'] . $after_title;

        try {
            $user = $this->get_user($this->get_option('rb-fitocracy-username'));
            $progressPercent = ($user->points / $user->points_levelup) * 100;

            include_once(RBFITOCRACY_DIR . '/src/templates/widget.php');
        } catch (FitocracyException $e) {
            echo '<p>' . $e->getMessage() . '</p>';
        }

        echo $after_widget;
    }

    public function get_widget_options() {
        $options = get_option('rb-fitocracy-widget');
        if (!is_array($options)) {
            $options = array();
        }

        $defaults = array(
            'title' => 'Fitocracy stats',
        );

        $options = array_merge($defaults, $options);
        $options['title'] = htmlspecialchars($options['title'], ENT_QUOTES);

        return $options;
    }

    public function render_widget_controls() {
        $options = $this->get_widget_options();

        if (isset($_POST['rb-fitocracy-action']) && $_POST['rb-fitocracy-action'] == 'rb-update-widget-options') {
            $options['title'] = strip_tags(stripslashes($_POST['rb-fitocracy-widget-title']));
            update_option('rb-fitocracy-widget', $options);
        }

        print('<p style="text-align:right;"><label for="rb-fitocracy-widget-title">' . __('Title:') . ' <input style="width: 200px;" id="rb-fitocracy-widget-title" name="rb-fitocracy-widget-title" type="text" value="' . $title . '" /></label></p>'
                . '<input type="hidden" id="rb-fitocracy-action" name="rb-fitocracy-action" value="rb-update-widget-options" />');
    }

    function encrypt($input_string, $key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key = hash('sha256', $key, TRUE);
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv));
    }

    function decrypt($encrypted_input_string, $key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key = hash('sha256', $key, TRUE);
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
    }

    public function deactivation() {
        wp_clear_scheduled_hook('rb-fitocracy-update-user');
    }

}