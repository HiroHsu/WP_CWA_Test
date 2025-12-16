<?php
/**
 * CWA API 處理類別
 *
 * @package CWA_Weather_Alert
 */

// 防止直接存取
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CWA_API 類別
 *
 * 處理中央氣象署 API 請求與資料解析
 */
class CWA_API {

    /**
     * API 基礎網址
     *
     * @var string
     */
    private const API_BASE_URL = 'https://opendata.cwa.gov.tw/api/v1/rest/datastore/W-C0033-001';

    /**
     * 快取鍵名
     *
     * @var string
     */
    private const CACHE_KEY = 'cwa_weather_alert_data';

    /**
     * 單例實例
     *
     * @var CWA_API
     */
    private static $instance = null;

    /**
     * 取得單例實例
     *
     * @return CWA_API
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
        // 私有建構函式
    }

    /**
     * 取得 API Key
     *
     * @return string
     */
    private function get_api_key() {
        $options = get_option( 'cwa_weather_alert_options', array() );
        return isset( $options['api_key'] ) ? $options['api_key'] : '';
    }

    /**
     * 取得快取時間
     *
     * @return int 快取時間（秒）
     */
    private function get_cache_time() {
        $options = get_option( 'cwa_weather_alert_options', array() );
        return isset( $options['cache_time'] ) ? intval( $options['cache_time'] ) : 1800;
    }

    /**
     * 從 API 取得天氣警報資料
     *
     * @param string|null $location 指定縣市（可選）
     * @return array|WP_Error 警報資料或錯誤
     */
    public function get_weather_alerts( $location = null ) {
        $api_key = $this->get_api_key();

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'no_api_key',
                __( '請先在設定中輸入 CWA API 授權碼', 'cwa-weather-alert' )
            );
        }

        // 嘗試從快取取得資料
        $cache_key = self::CACHE_KEY;
        if ( $location ) {
            $cache_key .= '_' . md5( $location );
        }

        $cached_data = get_transient( $cache_key );
        if ( false !== $cached_data ) {
            return $cached_data;
        }

        // 建立 API 請求網址
        $url = add_query_arg(
            array(
                'Authorization' => $api_key,
                'format'        => 'JSON',
            ),
            self::API_BASE_URL
        );

        if ( $location ) {
            $url = add_query_arg( 'locationName', $location, $url );
        }

        // 發送 API 請求
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP response code */
                    __( 'API 請求失敗，HTTP 狀態碼：%d', 'cwa-weather-alert' ),
                    $response_code
                )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error(
                'json_error',
                __( '無法解析 API 回應', 'cwa-weather-alert' )
            );
        }

        if ( ! isset( $data['success'] ) || 'true' !== $data['success'] ) {
            return new WP_Error(
                'api_failed',
                __( 'API 回應表示請求失敗', 'cwa-weather-alert' )
            );
        }

        // 解析警報資料
        $alerts = $this->parse_alerts( $data );

        // 儲存到快取
        set_transient( $cache_key, $alerts, $this->get_cache_time() );

        return $alerts;
    }

    /**
     * 解析 API 回應中的警報資料
     *
     * @param array $data API 回應資料
     * @return array 解析後的警報列表
     */
    private function parse_alerts( $data ) {
        $alerts = array();

        if ( ! isset( $data['records']['location'] ) ) {
            return $alerts;
        }

        foreach ( $data['records']['location'] as $location ) {
            $location_name = isset( $location['locationName'] ) ? $location['locationName'] : '';

            // 檢查是否有警報
            if ( ! isset( $location['hazardConditions']['hazards'] ) ) {
                continue;
            }

            $hazards = $location['hazardConditions']['hazards'];

            // 確保 hazards 是陣列
            if ( ! is_array( $hazards ) ) {
                continue;
            }

            // 處理單一警報或多個警報
            if ( isset( $hazards['info'] ) ) {
                // 單一警報
                $hazards = array( $hazards );
            }

            foreach ( $hazards as $hazard ) {
                if ( ! isset( $hazard['info'] ) ) {
                    continue;
                }

                $info = $hazard['info'];
                
                // 檢查是否為空警報（無生效中的警報）
                $phenomena = isset( $info['phenomena'] ) ? trim( $info['phenomena'] ) : '';
                if ( empty( $phenomena ) ) {
                    continue;
                }

                $alert = array(
                    'location'       => $location_name,
                    'phenomena'      => $phenomena,
                    'significance'   => isset( $info['significance'] ) ? $info['significance'] : '',
                    'language'       => isset( $info['language'] ) ? $info['language'] : '',
                    'effective_time' => isset( $hazard['validTime']['startTime'] ) ? $hazard['validTime']['startTime'] : '',
                    'expire_time'    => isset( $hazard['validTime']['endTime'] ) ? $hazard['validTime']['endTime'] : '',
                    'hazard_info'    => $info,
                );

                // 提取更多詳細資訊
                if ( isset( $info['affectedAreas']['location'] ) ) {
                    $affected_locations = $info['affectedAreas']['location'];
                    if ( ! is_array( $affected_locations ) ) {
                        $affected_locations = array( $affected_locations );
                    }
                    $alert['affected_areas'] = array_map( function( $loc ) {
                        return isset( $loc['locationName'] ) ? $loc['locationName'] : '';
                    }, $affected_locations );
                }

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * 清除快取
     *
     * @return bool
     */
    public function clear_cache() {
        global $wpdb;

        // 刪除所有相關的 transient
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::CACHE_KEY . '%',
                '_transient_timeout_' . self::CACHE_KEY . '%'
            )
        );

        return true;
    }

    /**
     * 測試 API 連線
     *
     * @param string $api_key API 授權碼
     * @return bool|WP_Error
     */
    public function test_connection( $api_key ) {
        $url = add_query_arg(
            array(
                'Authorization' => $api_key,
                'format'        => 'JSON',
                'limit'         => 1,
            ),
            self::API_BASE_URL
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP response code */
                    __( 'API 連線測試失敗，HTTP 狀態碼：%d', 'cwa-weather-alert' ),
                    $response_code
                )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['success'] ) && 'true' === $data['success'] ) {
            return true;
        }

        return new WP_Error(
            'api_failed',
            __( 'API 授權碼無效或已過期', 'cwa-weather-alert' )
        );
    }
}
