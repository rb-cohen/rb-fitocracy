<?php

require_once(__DIR__ . '/Fitocracy.php');
require_once(__DIR__ . '/FitocracyException.php');

class RbFitocracy {

    public $pluginName = 'RB Fitocracy';
    public $availableOptions = array(
        'rb-fitocracy-username'
    );
    protected $_options;

    public function init() {
        if (version_compare(PHP_VERSION, '5.2.4', '<')) {
            deactivate_plugins(basename(RBFITOCRACY_FILE));
            wp_die(__("RB Fitocracy requires PHP 5.2.4 or higher. Ask your host how to enable PHP 5 as the default on your servers.", 'rb-fitocracy'));
        }
    }

    public function admin_init() {
        
    }

    public function admin_menu() {
        add_options_page(
                __('RB Fitocracy Options', 'rb-fitocracy'), __('RB Fitocracy', 'rb-fitocracy'), 'manage_options', basename(RBFITOCRACY_FILE), array(
            $this,
            'admin_options_form'
                )
        );
    }

    public function admin_options_form() {
        if (!empty($_POST['rbfitocracy_submit'])) {
            $this->save_options(array(
                'rb-fitocracy-username' => sanitize_text_field($_POST['rb-fitocracy-username']),
            ));

            $updateSuccess = true;
        }

        $options = $this->get_options();
        include_once(RBFITOCRACY_DIR . '/src/templates/admin-options.php');
    }

    public function get_options() {
        if ($this->_options === null) {
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
        if (empty($username)) {
            throw new FitocracyException('Username not set');
        }

        $fitocracy = new Fitocracy();
        $user = $fitocracy->getUser($username);
        return !empty($user->username);
    }

}