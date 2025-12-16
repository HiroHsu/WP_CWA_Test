<?php
/**
 * Plugin Name: 台灣天氣警示
 * Plugin URI: https://github.com/example/cwa-weather-alert
 * Description: 顯示中央氣象署天氣警特報資訊，使用 API W-C0033-001
 * Version: 1.0.0
 * Author: WordPress Developer
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cwa-weather-alert
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// 防止直接存取
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 定義插件常數
define( 'CWA_WEATHER_ALERT_VERSION', '1.0.0' );
define( 'CWA_WEATHER_ALERT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWA_WEATHER_ALERT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CWA_WEATHER_ALERT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * 主要插件類別
 */
class CWA_Weather_Alert {

    /**
     * 單例實例
     *
     * @var CWA_Weather_Alert
     */
    private static $instance = null;

    /**
     * 取得單例實例
     *
     * @return CWA_Weather_Alert
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 建構函式
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * 載入相依檔案
     */
    private function load_dependencies() {
        require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-api.php';
        require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-admin.php';
        require_once CWA_WEATHER_ALERT_PLUGIN_DIR . 'includes/class-cwa-shortcode.php';
    }

    /**
     * 初始化掛鉤
     */
    private function init_hooks() {
        // 載入前台樣式
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );

        // 初始化後台管理
        if ( is_admin() ) {
            CWA_Admin::get_instance();
        }

        // 初始化短代碼
        CWA_Shortcode::get_instance();
    }

    /**
     * 載入前台樣式
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'cwa-weather-alert',
            CWA_WEATHER_ALERT_PLUGIN_URL . 'assets/css/cwa-weather-alert.css',
            array(),
            CWA_WEATHER_ALERT_VERSION
        );
    }

    /**
     * 插件啟用時執行
     */
    public static function activate() {
        // 設定預設選項
        $default_options = array(
            'api_key'      => '',
            'cache_time'   => 1800, // 30 分鐘
            'locations'    => array(),
        );

        if ( ! get_option( 'cwa_weather_alert_options' ) ) {
            add_option( 'cwa_weather_alert_options', $default_options );
        }
    }

    /**
     * 插件停用時執行
     */
    public static function deactivate() {
        // 清除快取
        delete_transient( 'cwa_weather_alert_data' );
    }

    /**
     * 插件刪除時執行
     */
    public static function uninstall() {
        // 刪除所有選項
        delete_option( 'cwa_weather_alert_options' );
        delete_transient( 'cwa_weather_alert_data' );
    }
}

// 註冊啟用/停用掛鉤
register_activation_hook( __FILE__, array( 'CWA_Weather_Alert', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CWA_Weather_Alert', 'deactivate' ) );

// 初始化插件
add_action( 'plugins_loaded', array( 'CWA_Weather_Alert', 'get_instance' ) );
