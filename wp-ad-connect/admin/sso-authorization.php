<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 注册 SSO 授权管理设置
function adc_sso_authorization_settings() {
    register_setting('adc-sso-settings-group', 'adc_sso_host');
    register_setting('adc-sso-settings-group', 'adc_sso_client_id');
    register_setting('adc-sso-settings-group', 'adc_sso_client_secret');
}
add_action('admin_init', 'adc_sso_authorization_settings');