<?php
/**
 * Plugin Name: Taiwan Weather Alert
 * Plugin URI: https://github.com/HiroHsu/WP_CWA_Test
 * Description: Display CWA weather alerts using API W-C0033-001
 * Version: 1.0.0
 * Author: Hiro Hsu
 * Author URI: https://github.com/HiroHsu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-cwa-test
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CWA_WEATHER_ALERT_VERSION', '1.0.0' );
define( 'CWA_WEATHER_ALERT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWA_WEATHER_ALERT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-api.php';
require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-admin.php';
require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-shortcode.php';

/**
 * Initialize the plugin
 */
function cwa_weather_alert_init() {
    // Register shortcode
    CWA_Shortcode::get_instance();
    
    // Initialize admin if in admin area
    if ( is_admin() ) {
        CWA_Admin::get_instance();
    }
}
add_action( 'plugins_loaded', 'cwa_weather_alert_init' );

/**
 * Enqueue frontend styles
 */
function cwa_weather_alert_enqueue_styles() {
    wp_enqueue_style(
        'cwa-weather-alert',
        CWA_WEATHER_ALERT_PLUGIN_URL . 'assets/css/cwa-weather-alert.css',
        array(),
        CWA_WEATHER_ALERT_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'cwa_weather_alert_enqueue_styles' );

/**
 * Plugin activation
 */
function cwa_weather_alert_activate() {
    $default_options = array(
        'api_key'    => '',
        'cache_time' => 1800,
        'locations'  => array(),
    );

    if ( ! get_option( 'cwa_weather_alert_options' ) ) {
        add_option( 'cwa_weather_alert_options', $default_options );
    }
}
register_activation_hook( __FILE__, 'cwa_weather_alert_activate' );

/**
 * Plugin deactivation
 */
function cwa_weather_alert_deactivate() {
    delete_transient( 'cwa_weather_alert_data' );
}
register_deactivation_hook( __FILE__, 'cwa_weather_alert_deactivate' );
