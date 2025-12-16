=== 台灣天氣警示 ===
Contributors: developer
Tags: weather, taiwan, cwa, alert, 天氣, 警報, 台灣, 中央氣象署
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

顯示中央氣象署天氣警特報資訊，使用 API W-C0033-001。

== Description ==

台灣天氣警示插件可讓您在 WordPress 網站上顯示中央氣象署發布的天氣警特報資訊。

**功能特色：**

* 自動取得最新天氣警報資料
* 支援所有台灣縣市
* 可自訂顯示特定縣市
* 內建快取機制，減少 API 請求
* 響應式設計，適合各種裝置
* 依據警報嚴重程度顯示不同顏色

**使用的 API：**

* W-C0033-001 - 天氣特報-各別縣市地區目前之天氣警特報情形

== Installation ==

1. 上傳 `cwa-weather-alert` 資料夾到 `/wp-content/plugins/` 目錄
2. 在 WordPress 後台「外掛」頁面啟用插件
3. 前往「設定」→「天氣警示」進行設定
4. 輸入您的 CWA API 授權碼
5. 在頁面或文章中使用短代碼 `[cwa_weather_alert]`

**取得 API 授權碼：**

1. 前往 [中央氣象署開放資料平台](https://opendata.cwa.gov.tw/)
2. 註冊會員帳號
3. 登入後在「會員資訊」頁面取得授權碼

== Frequently Asked Questions ==

= 如何取得 API 授權碼？ =

請前往 [中央氣象署開放資料平台](https://opendata.cwa.gov.tw/) 註冊會員，登入後即可在會員資訊頁面取得授權碼。

= 可以只顯示特定縣市的警報嗎？ =

可以，使用短代碼時加上 location 參數即可：
`[cwa_weather_alert location="臺北市"]`

也可以同時顯示多個縣市：
`[cwa_weather_alert location="臺北市,新北市,桃園市"]`

= 快取時間可以調整嗎？ =

可以，在後台設定頁面可以選擇 5 分鐘到 2 小時的快取時間。

= 沒有警報時會顯示什麼？ =

預設會顯示「目前沒有發布中的天氣警特報」的提示。如果不想顯示此提示，可以設定 `show_empty="no"`：
`[cwa_weather_alert show_empty="no"]`

== Screenshots ==

1. 天氣警報前台顯示
2. 後台設定頁面
3. 警報卡片各種嚴重程度

== Changelog ==

= 1.0.0 =
* 初始版本發佈
* 支援 W-C0033-001 API
* 後台設定頁面
* 短代碼支援
* 快取機制
* 響應式設計

== Upgrade Notice ==

= 1.0.0 =
初始版本，請確保已取得 CWA API 授權碼。
