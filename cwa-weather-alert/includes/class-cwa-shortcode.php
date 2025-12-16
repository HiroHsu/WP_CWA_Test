<?php
/**
 * CWA 短代碼類別
 *
 * @package CWA_Weather_Alert
 */

// 防止直接存取
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CWA_Shortcode 類別
 *
 * 處理短代碼渲染
 */
class CWA_Shortcode {

    /**
     * 單例實例
     *
     * @var CWA_Shortcode
     */
    private static $instance = null;

    /**
     * 取得單例實例
     *
     * @return CWA_Shortcode
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
        add_shortcode( 'cwa_weather_alert', array( $this, 'render_shortcode' ) );
    }

    /**
     * 渲染短代碼
     *
     * @param array  $atts    短代碼屬性
     * @param string $content 內容（未使用）
     * @return string HTML 輸出
     */
    public function render_shortcode( $atts, $content = null ) {
        $atts = shortcode_atts(
            array(
                'location'   => '',
                'show_empty' => 'yes',
                'class'      => '',
            ),
            $atts,
            'cwa_weather_alert'
        );

        $api = CWA_API::get_instance();

        // 處理多個縣市
        $location = trim( $atts['location'] );
        if ( ! empty( $location ) && strpos( $location, ',' ) !== false ) {
            // 多個縣市的情況
            $locations = array_map( 'trim', explode( ',', $location ) );
            $all_alerts = array();

            foreach ( $locations as $loc ) {
                $alerts = $api->get_weather_alerts( $loc );
                if ( ! is_wp_error( $alerts ) && ! empty( $alerts ) ) {
                    $all_alerts = array_merge( $all_alerts, $alerts );
                }
            }

            $alerts = $all_alerts;
        } else {
            // 單一縣市或全部
            $alerts = $api->get_weather_alerts( $location ?: null );
        }

        // 處理錯誤
        if ( is_wp_error( $alerts ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return sprintf(
                    '<div class="cwa-weather-alert-error">%s</div>',
                    esc_html( $alerts->get_error_message() )
                );
            }
            return '';
        }

        // 沒有警報的情況
        if ( empty( $alerts ) ) {
            if ( 'yes' === $atts['show_empty'] || 'true' === $atts['show_empty'] || '1' === $atts['show_empty'] ) {
                return $this->render_no_alerts( $atts );
            }
            return '';
        }

        return $this->render_alerts( $alerts, $atts );
    }

    /**
     * 渲染警報列表
     *
     * @param array $alerts 警報資料
     * @param array $atts   短代碼屬性
     * @return string HTML 輸出
     */
    public function render_alerts( $alerts, $atts = array() ) {
        $class = isset( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

        $output = '<div class="cwa-weather-alerts' . $class . '">';
        $output .= '<div class="cwa-alerts-header">';
        $output .= '<h3 class="cwa-alerts-title">';
        $output .= '<span class="cwa-icon">⚠️</span> ';
        $output .= esc_html__( '天氣警特報', 'cwa-weather-alert' );
        $output .= '</h3>';
        $output .= '<span class="cwa-update-time">';
        $output .= sprintf(
            /* translators: %s: update time */
            esc_html__( '更新時間：%s', 'cwa-weather-alert' ),
            esc_html( current_time( 'Y-m-d H:i' ) )
        );
        $output .= '</span>';
        $output .= '</div>';

        $output .= '<div class="cwa-alerts-list">';

        foreach ( $alerts as $alert ) {
            $output .= $this->render_single_alert( $alert );
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * 渲染單一警報卡片
     *
     * @param array $alert 警報資料
     * @return string HTML 輸出
     */
    private function render_single_alert( $alert ) {
        $phenomena    = isset( $alert['phenomena'] ) ? $alert['phenomena'] : '';
        $significance = isset( $alert['significance'] ) ? $alert['significance'] : '';
        $location     = isset( $alert['location'] ) ? $alert['location'] : '';

        // 決定警報等級樣式
        $severity_class = $this->get_severity_class( $phenomena, $significance );

        $output = '<div class="cwa-alert-card ' . esc_attr( $severity_class ) . '">';

        // 警報標題
        $output .= '<div class="cwa-alert-header">';
        $output .= '<span class="cwa-alert-icon">' . $this->get_alert_icon( $phenomena ) . '</span>';
        $output .= '<span class="cwa-alert-type">';
        $output .= esc_html( $phenomena . $significance );
        $output .= '</span>';
        $output .= '</div>';

        // 警報內容
        $output .= '<div class="cwa-alert-content">';

        // 地點
        $output .= '<div class="cwa-alert-location">';
        $output .= '<strong>' . esc_html__( '發布地區：', 'cwa-weather-alert' ) . '</strong>';
        $output .= esc_html( $location );
        $output .= '</div>';

        // 影響區域
        if ( ! empty( $alert['affected_areas'] ) ) {
            $output .= '<div class="cwa-alert-affected">';
            $output .= '<strong>' . esc_html__( '影響區域：', 'cwa-weather-alert' ) . '</strong>';
            $output .= esc_html( implode( '、', $alert['affected_areas'] ) );
            $output .= '</div>';
        }

        // 有效時間
        if ( ! empty( $alert['effective_time'] ) || ! empty( $alert['expire_time'] ) ) {
            $output .= '<div class="cwa-alert-time">';
            if ( ! empty( $alert['effective_time'] ) ) {
                $output .= '<span class="cwa-time-start">';
                $output .= '<strong>' . esc_html__( '生效時間：', 'cwa-weather-alert' ) . '</strong>';
                $output .= esc_html( $this->format_datetime( $alert['effective_time'] ) );
                $output .= '</span>';
            }
            if ( ! empty( $alert['expire_time'] ) ) {
                $output .= ' <span class="cwa-time-end">';
                $output .= '<strong>' . esc_html__( '結束時間：', 'cwa-weather-alert' ) . '</strong>';
                $output .= esc_html( $this->format_datetime( $alert['expire_time'] ) );
                $output .= '</span>';
            }
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * 渲染無警報提示
     *
     * @param array $atts 短代碼屬性
     * @return string HTML 輸出
     */
    private function render_no_alerts( $atts = array() ) {
        $class = isset( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

        $output = '<div class="cwa-weather-alerts cwa-no-alerts' . $class . '">';
        $output .= '<div class="cwa-alerts-header">';
        $output .= '<h3 class="cwa-alerts-title">';
        $output .= '<span class="cwa-icon">✅</span> ';
        $output .= esc_html__( '天氣警特報', 'cwa-weather-alert' );
        $output .= '</h3>';
        $output .= '</div>';
        $output .= '<div class="cwa-no-alerts-message">';
        $output .= '<p>' . esc_html__( '目前沒有發布中的天氣警特報', 'cwa-weather-alert' ) . '</p>';
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * 取得警報嚴重程度樣式類別
     *
     * @param string $phenomena    現象
     * @param string $significance 重要性
     * @return string CSS 類別
     */
    private function get_severity_class( $phenomena, $significance ) {
        // 根據警報類型決定嚴重程度
        $high_severity = array( '豪雨', '大豪雨', '超大豪雨', '颱風', '海上颱風', '海上陸上颱風', '地震' );
        $medium_severity = array( '大雨', '雷雨', '強風', '低溫', '高溫' );

        foreach ( $high_severity as $type ) {
            if ( strpos( $phenomena, $type ) !== false ) {
                return 'cwa-severity-high';
            }
        }

        foreach ( $medium_severity as $type ) {
            if ( strpos( $phenomena, $type ) !== false ) {
                return 'cwa-severity-medium';
            }
        }

        return 'cwa-severity-low';
    }

    /**
     * 取得警報圖示
     *
     * @param string $phenomena 現象
     * @return string 圖示 emoji
     */
    private function get_alert_icon( $phenomena ) {
        $icons = array(
            '颱風'   => '🌀',
            '豪雨'   => '🌧️',
            '大雨'   => '🌧️',
            '雷雨'   => '⛈️',
            '強風'   => '💨',
            '低溫'   => '🥶',
            '高溫'   => '🥵',
            '濃霧'   => '🌫️',
            '地震'   => '📳',
            '海嘯'   => '🌊',
        );

        foreach ( $icons as $type => $icon ) {
            if ( strpos( $phenomena, $type ) !== false ) {
                return $icon;
            }
        }

        return '⚠️';
    }

    /**
     * 格式化日期時間
     *
     * @param string $datetime 日期時間字串
     * @return string 格式化後的日期時間
     */
    private function format_datetime( $datetime ) {
        if ( empty( $datetime ) ) {
            return '';
        }

        $timestamp = strtotime( $datetime );
        if ( false === $timestamp ) {
            return $datetime;
        }

        return wp_date( 'Y-m-d H:i', $timestamp );
    }
}
