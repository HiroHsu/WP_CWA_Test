<?php
/**
 * Plugin Name: Taiwan Weather Alert
 * Description: Display CWA weather alerts using API W-C0033-001
 * Version: 1.0.0
 * Author: Hiro Hsu
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'CWA_VERSION', '1.0.0' );
define( 'CWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Enqueue styles
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'cwa-style', CWA_PLUGIN_URL . 'assets/css/cwa-weather-alert.css', array(), CWA_VERSION );
});

// Admin menu
add_action( 'admin_menu', function() {
    add_options_page(
        'Taiwan Weather Alert',
        'Weather Alert',
        'manage_options',
        'cwa-weather-alert',
        'cwa_render_settings_page'
    );
});

// Register settings
add_action( 'admin_init', function() {
    register_setting( 'cwa_settings', 'cwa_options' );
    
    add_settings_section( 'cwa_main', 'API Settings', '__return_null', 'cwa-weather-alert' );
    
    add_settings_field(
        'api_key',
        'API Key',
        'cwa_render_api_key_field',
        'cwa-weather-alert',
        'cwa_main'
    );
    
    add_settings_field(
        'cache_time',
        'Cache Time',
        'cwa_render_cache_time_field',
        'cwa-weather-alert',
        'cwa_main'
    );
});

function cwa_render_api_key_field() {
    $options = get_option( 'cwa_options', array() );
    $value = isset( $options['api_key'] ) ? $options['api_key'] : '';
    echo '<input type="text" name="cwa_options[api_key]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    echo '<p class="description">Get your API key from <a href="https://opendata.cwa.gov.tw/" target="_blank">CWA Open Data Platform</a></p>';
}

function cwa_render_cache_time_field() {
    $options = get_option( 'cwa_options', array() );
    $value = isset( $options['cache_time'] ) ? $options['cache_time'] : 1800;
    echo '<select name="cwa_options[cache_time]">';
    echo '<option value="300"' . selected( $value, 300, false ) . '>5 minutes</option>';
    echo '<option value="900"' . selected( $value, 900, false ) . '>15 minutes</option>';
    echo '<option value="1800"' . selected( $value, 1800, false ) . '>30 minutes</option>';
    echo '<option value="3600"' . selected( $value, 3600, false ) . '>1 hour</option>';
    echo '</select>';
}

function cwa_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Taiwan Weather Alert Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'cwa_settings' );
            do_settings_sections( 'cwa-weather-alert' );
            submit_button( 'Save Settings' );
            ?>
        </form>
        <hr>
        <h2>Usage</h2>
        <p>Use shortcode: <code>[cwa_weather_alert]</code></p>
        <p>With location: <code>[cwa_weather_alert location="Taipei"]</code></p>
    </div>
    <?php
}

// Shortcode
add_shortcode( 'cwa_weather_alert', 'cwa_shortcode_handler' );

function cwa_shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'location' => '',
        'show_empty' => 'yes',
    ), $atts );
    
    $options = get_option( 'cwa_options', array() );
    $api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
    
    if ( empty( $api_key ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<div class="cwa-weather-alert-error">Please configure API key in Settings &gt; Weather Alert</div>';
        }
        return '';
    }
    
    $cache_time = isset( $options['cache_time'] ) ? intval( $options['cache_time'] ) : 1800;
    $cache_key = 'cwa_alerts_' . md5( $atts['location'] );
    
    $alerts = get_transient( $cache_key );
    
    if ( false === $alerts ) {
        $alerts = cwa_fetch_alerts( $api_key, $atts['location'] );
        if ( ! is_wp_error( $alerts ) ) {
            set_transient( $cache_key, $alerts, $cache_time );
        }
    }
    
    if ( is_wp_error( $alerts ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<div class="cwa-weather-alert-error">' . esc_html( $alerts->get_error_message() ) . '</div>';
        }
        return '';
    }
    
    if ( empty( $alerts ) ) {
        if ( $atts['show_empty'] === 'yes' ) {
            return cwa_render_no_alerts();
        }
        return '';
    }
    
    return cwa_render_alerts( $alerts );
}

function cwa_fetch_alerts( $api_key, $location = '' ) {
    $url = 'https://opendata.cwa.gov.tw/api/v1/rest/datastore/W-C0033-001';
    $url = add_query_arg( array(
        'Authorization' => $api_key,
        'format' => 'JSON',
    ), $url );
    
    if ( ! empty( $location ) ) {
        $url = add_query_arg( 'locationName', $location, $url );
    }
    
    $response = wp_remote_get( $url, array( 'timeout' => 30 ) );
    
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return new WP_Error( 'api_error', 'API request failed with code: ' . $code );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['success'] ) || $data['success'] !== 'true' ) {
        return new WP_Error( 'api_failed', 'API returned error' );
    }
    
    return cwa_parse_alerts( $data );
}

function cwa_parse_alerts( $data ) {
    $alerts = array();
    
    if ( ! isset( $data['records']['location'] ) ) {
        return $alerts;
    }
    
    foreach ( $data['records']['location'] as $loc ) {
        $loc_name = isset( $loc['locationName'] ) ? $loc['locationName'] : '';
        
        if ( ! isset( $loc['hazardConditions']['hazards'] ) ) {
            continue;
        }
        
        $hazards = $loc['hazardConditions']['hazards'];
        if ( ! is_array( $hazards ) ) continue;
        
        if ( isset( $hazards['info'] ) ) {
            $hazards = array( $hazards );
        }
        
        foreach ( $hazards as $h ) {
            if ( ! isset( $h['info'] ) ) continue;
            $info = $h['info'];
            $phenomena = isset( $info['phenomena'] ) ? trim( $info['phenomena'] ) : '';
            if ( empty( $phenomena ) ) continue;
            
            $alerts[] = array(
                'location' => $loc_name,
                'phenomena' => $phenomena,
                'significance' => isset( $info['significance'] ) ? $info['significance'] : '',
            );
        }
    }
    
    return $alerts;
}

function cwa_render_no_alerts() {
    $html = '<div class="cwa-weather-alerts cwa-no-alerts">';
    $html .= '<div class="cwa-alerts-header"><h3 class="cwa-alerts-title">Weather Alerts</h3></div>';
    $html .= '<div class="cwa-no-alerts-message"><p>No active weather alerts</p></div>';
    $html .= '</div>';
    return $html;
}

function cwa_render_alerts( $alerts ) {
    $html = '<div class="cwa-weather-alerts">';
    $html .= '<div class="cwa-alerts-header"><h3 class="cwa-alerts-title">Weather Alerts</h3></div>';
    $html .= '<div class="cwa-alerts-list">';
    
    foreach ( $alerts as $alert ) {
        $html .= '<div class="cwa-alert-card">';
        $html .= '<div class="cwa-alert-header"><span class="cwa-alert-type">' . esc_html( $alert['phenomena'] . $alert['significance'] ) . '</span></div>';
        $html .= '<div class="cwa-alert-content"><strong>Location:</strong> ' . esc_html( $alert['location'] ) . '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    return $html;
}

// Activation
register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'cwa_options' ) ) {
        add_option( 'cwa_options', array( 'api_key' => '', 'cache_time' => 1800 ) );
    }
});
