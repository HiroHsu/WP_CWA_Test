<?php
/**
 * CWA 後台管理類別
 *
 * @package CWA_Weather_Alert
 */

// 防止直接存取
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CWA_Admin 類別
 *
 * 處理後台設定頁面
 */
class CWA_Admin {

    /**
     * 選項名稱
     *
     * @var string
     */
    private const OPTION_NAME = 'cwa_weather_alert_options';

    /**
     * 選項群組
     *
     * @var string
     */
    private const OPTION_GROUP = 'cwa_weather_alert_settings';

    /**
     * 單例實例
     *
     * @var CWA_Admin
     */
    private static $instance = null;

    /**
     * 取得單例實例
     *
     * @return CWA_Admin
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'wp_ajax_cwa_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_cwa_clear_cache', array( $this, 'ajax_clear_cache' ) );
    }

    /**
     * 載入後台樣式
     *
     * @param string $hook 當前頁面 hook
     */
    public function enqueue_admin_styles( $hook ) {
        if ( 'settings_page_cwa-weather-alert' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'cwa-weather-alert-admin',
            CWA_WEATHER_ALERT_PLUGIN_URL . 'assets/css/cwa-weather-alert.css',
            array(),
            CWA_WEATHER_ALERT_VERSION
        );

        wp_enqueue_script(
            'cwa-weather-alert-admin',
            CWA_WEATHER_ALERT_PLUGIN_URL . 'assets/js/cwa-admin.js',
            array( 'jquery' ),
            CWA_WEATHER_ALERT_VERSION,
            true
        );

        wp_localize_script(
            'cwa-weather-alert-admin',
            'cwaAdmin',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'cwa_admin_nonce' ),
                'testing'   => __( '測試中...', 'cwa-weather-alert' ),
                'clearing'  => __( '清除中...', 'cwa-weather-alert' ),
            )
        );
    }

    /**
     * 新增管理選單
     */
    public function add_admin_menu() {
        add_options_page(
            __( '台灣天氣警示設定', 'cwa-weather-alert' ),
            __( '天氣警示', 'cwa-weather-alert' ),
            'manage_options',
            'cwa-weather-alert',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * 註冊設定
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_options' ),
                'default'           => array(
                    'api_key'    => '',
                    'cache_time' => 1800,
                    'locations'  => array(),
                ),
            )
        );

        // API 設定區段
        add_settings_section(
            'cwa_api_section',
            __( 'API 設定', 'cwa-weather-alert' ),
            array( $this, 'render_api_section' ),
            'cwa-weather-alert'
        );

        // API Key 欄位
        add_settings_field(
            'api_key',
            __( 'API 授權碼', 'cwa-weather-alert' ),
            array( $this, 'render_api_key_field' ),
            'cwa-weather-alert',
            'cwa_api_section'
        );

        // 快取時間欄位
        add_settings_field(
            'cache_time',
            __( '快取時間', 'cwa-weather-alert' ),
            array( $this, 'render_cache_time_field' ),
            'cwa-weather-alert',
            'cwa_api_section'
        );

        // 使用說明區段
        add_settings_section(
            'cwa_usage_section',
            __( '使用說明', 'cwa-weather-alert' ),
            array( $this, 'render_usage_section' ),
            'cwa-weather-alert'
        );
    }

    /**
     * 清理選項
     *
     * @param array $input 輸入值
     * @return array 清理後的值
     */
    public function sanitize_options( $input ) {
        $sanitized = array();

        if ( isset( $input['api_key'] ) ) {
            $sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
        }

        if ( isset( $input['cache_time'] ) ) {
            $sanitized['cache_time'] = absint( $input['cache_time'] );
            // 最小 5 分鐘，最大 24 小時
            $sanitized['cache_time'] = max( 300, min( 86400, $sanitized['cache_time'] ) );
        }

        if ( isset( $input['locations'] ) && is_array( $input['locations'] ) ) {
            $sanitized['locations'] = array_map( 'sanitize_text_field', $input['locations'] );
        } else {
            $sanitized['locations'] = array();
        }

        return $sanitized;
    }

    /**
     * 渲染 API 設定區段說明
     */
    public function render_api_section() {
        echo '<p>';
        printf(
            /* translators: %s: CWA registration URL */
            esc_html__( '請先到 %s 註冊帳號並取得 API 授權碼。', 'cwa-weather-alert' ),
            '<a href="https://opendata.cwa.gov.tw/userLogin" target="_blank">中央氣象署開放資料平台</a>'
        );
        echo '</p>';
    }

    /**
     * 渲染 API Key 輸入欄位
     */
    public function render_api_key_field() {
        $options = get_option( self::OPTION_NAME, array() );
        $api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
        ?>
        <input type="text" 
               name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]" 
               id="cwa_api_key"
               value="<?php echo esc_attr( $api_key ); ?>" 
               class="regular-text"
               placeholder="<?php esc_attr_e( '輸入您的 API 授權碼', 'cwa-weather-alert' ); ?>"
        />
        <button type="button" id="cwa-test-connection" class="button button-secondary">
            <?php esc_html_e( '測試連線', 'cwa-weather-alert' ); ?>
        </button>
        <span id="cwa-test-result"></span>
        <p class="description">
            <?php esc_html_e( '從中央氣象署開放資料平台取得的授權碼', 'cwa-weather-alert' ); ?>
        </p>
        <?php
    }

    /**
     * 渲染快取時間欄位
     */
    public function render_cache_time_field() {
        $options    = get_option( self::OPTION_NAME, array() );
        $cache_time = isset( $options['cache_time'] ) ? $options['cache_time'] : 1800;
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_time]" id="cwa_cache_time">
            <option value="300" <?php selected( $cache_time, 300 ); ?>>
                <?php esc_html_e( '5 分鐘', 'cwa-weather-alert' ); ?>
            </option>
            <option value="900" <?php selected( $cache_time, 900 ); ?>>
                <?php esc_html_e( '15 分鐘', 'cwa-weather-alert' ); ?>
            </option>
            <option value="1800" <?php selected( $cache_time, 1800 ); ?>>
                <?php esc_html_e( '30 分鐘', 'cwa-weather-alert' ); ?>
            </option>
            <option value="3600" <?php selected( $cache_time, 3600 ); ?>>
                <?php esc_html_e( '1 小時', 'cwa-weather-alert' ); ?>
            </option>
            <option value="7200" <?php selected( $cache_time, 7200 ); ?>>
                <?php esc_html_e( '2 小時', 'cwa-weather-alert' ); ?>
            </option>
        </select>
        <button type="button" id="cwa-clear-cache" class="button button-secondary">
            <?php esc_html_e( '清除快取', 'cwa-weather-alert' ); ?>
        </button>
        <span id="cwa-cache-result"></span>
        <p class="description">
            <?php esc_html_e( 'API 資料的快取時間，較短的時間會更即時但會增加 API 請求次數', 'cwa-weather-alert' ); ?>
        </p>
        <?php
    }

    /**
     * 渲染使用說明區段
     */
    public function render_usage_section() {
        ?>
        <div class="cwa-usage-info">
            <h4><?php esc_html_e( '短代碼使用方式', 'cwa-weather-alert' ); ?></h4>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( '短代碼', 'cwa-weather-alert' ); ?></th>
                        <th><?php esc_html_e( '說明', 'cwa-weather-alert' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[cwa_weather_alert]</code></td>
                        <td><?php esc_html_e( '顯示所有縣市的天氣警報', 'cwa-weather-alert' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[cwa_weather_alert location="臺北市"]</code></td>
                        <td><?php esc_html_e( '顯示特定縣市的天氣警報', 'cwa-weather-alert' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[cwa_weather_alert location="臺北市,新北市"]</code></td>
                        <td><?php esc_html_e( '顯示多個縣市的天氣警報（以逗號分隔）', 'cwa-weather-alert' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[cwa_weather_alert show_empty="yes"]</code></td>
                        <td><?php esc_html_e( '即使沒有警報也顯示提示訊息', 'cwa-weather-alert' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h4><?php esc_html_e( '可用縣市名稱', 'cwa-weather-alert' ); ?></h4>
            <p class="description">
                <?php
                $locations = array(
                    '臺北市', '新北市', '桃園市', '臺中市', '臺南市', '高雄市',
                    '基隆市', '新竹市', '嘉義市', '新竹縣', '苗栗縣', '彰化縣',
                    '南投縣', '雲林縣', '嘉義縣', '屏東縣', '宜蘭縣', '花蓮縣',
                    '臺東縣', '澎湖縣', '金門縣', '連江縣',
                );
                echo esc_html( implode( '、', $locations ) );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * 渲染設定頁面
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( 'cwa-weather-alert' );
                submit_button( __( '儲存設定', 'cwa-weather-alert' ) );
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( '目前警報預覽', 'cwa-weather-alert' ); ?></h2>
            <div id="cwa-preview">
                <?php
                $api    = CWA_API::get_instance();
                $alerts = $api->get_weather_alerts();

                if ( is_wp_error( $alerts ) ) {
                    echo '<div class="notice notice-error"><p>' . esc_html( $alerts->get_error_message() ) . '</p></div>';
                } elseif ( empty( $alerts ) ) {
                    echo '<div class="notice notice-info"><p>' . esc_html__( '目前沒有任何天氣警報', 'cwa-weather-alert' ) . '</p></div>';
                } else {
                    echo '<div class="cwa-alerts-preview">';
                    $shortcode = CWA_Shortcode::get_instance();
                    echo $shortcode->render_alerts( $alerts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX：測試 API 連線
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'cwa_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '權限不足', 'cwa-weather-alert' ) );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( __( '請輸入 API 授權碼', 'cwa-weather-alert' ) );
        }

        $api    = CWA_API::get_instance();
        $result = $api->test_connection( $api_key );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( __( '連線成功！', 'cwa-weather-alert' ) );
    }

    /**
     * AJAX：清除快取
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'cwa_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '權限不足', 'cwa-weather-alert' ) );
        }

        $api = CWA_API::get_instance();
        $api->clear_cache();

        wp_send_json_success( __( '快取已清除！', 'cwa-weather-alert' ) );
    }
}
