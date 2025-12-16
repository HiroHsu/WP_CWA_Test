/**
 * CWA Weather Alert - 後台管理腳本
 *
 * @package CWA_Weather_Alert
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // 測試 API 連線
        $('#cwa-test-connection').on('click', function () {
            var $button = $(this);
            var $result = $('#cwa-test-result');
            var apiKey = $('#cwa_api_key').val();

            if (!apiKey) {
                $result.removeClass('success').addClass('error').text('請輸入 API 授權碼');
                return;
            }

            $button.prop('disabled', true);
            $result.removeClass('success error').text(cwaAdmin.testing);

            $.ajax({
                url: cwaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cwa_test_connection',
                    nonce: cwaAdmin.nonce,
                    api_key: apiKey
                },
                success: function (response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data);
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data);
                    }
                },
                error: function () {
                    $result.removeClass('success').addClass('error').text('連線錯誤，請稍後再試');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        // 清除快取
        $('#cwa-clear-cache').on('click', function () {
            var $button = $(this);
            var $result = $('#cwa-cache-result');

            $button.prop('disabled', true);
            $result.removeClass('success error').text(cwaAdmin.clearing);

            $.ajax({
                url: cwaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cwa_clear_cache',
                    nonce: cwaAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(response.data);
                    } else {
                        $result.removeClass('success').addClass('error').text(response.data);
                    }
                },
                error: function () {
                    $result.removeClass('success').addClass('error').text('操作錯誤，請稍後再試');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
