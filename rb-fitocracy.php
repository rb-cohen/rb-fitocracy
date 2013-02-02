<?php
/*
Plugin Name: RB Fitocracy
Plugin URI: http://www.arronwoods.com/rb-fitocracy/
Description: Publish your latest Fitocracy stats on your Wordpress blog
Version: 1.0
Author: Arron Woods
Author URI: http://arronwoods.com
*/

define(RBFITOCRACY_DIR, realpath(__DIR__));
define(RBFITOCRACY_FILE, __FILE__);

require_once RBFITOCRACY_DIR . '/src/RbFitocracy.php';
$rbf = new RbFitocracy();

add_action('init', array($rbf, 'init'));
add_action('admin_init', array($rbf, 'admin_init'));
add_action('admin_menu', array($rbf, 'admin_menu'));
add_action('admin_notices', array($rbf, 'admin_notices'));
register_sidebar_widget('RB Fitocracy Widget', array($rbf, 'render_widget'));
register_widget_control('RB Fitocracy Widget', array($rbf, 'render_widget_controls'));