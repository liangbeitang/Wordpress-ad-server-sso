<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 检查是否是 POST 请求，一般授权同意或拒绝的表单提交是 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取客户端 ID
    $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
    // 获取重定向 URI
    $redirect_uri = isset($_POST['redirect_uri']) ? esc_url_raw($_POST['redirect_uri']) : '';
    // 获取权限范围
    $scope = isset($_POST['scope']) ? sanitize_text_field($_POST['scope']) : '';
    // 获取状态参数
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    // 获取用户的授权选择（同意或拒绝）
    $consent = isset($_POST['consent']) ? sanitize_text_field($_POST['consent']) : '';

    // 检查必要参数是否存在
    if (!$client_id || !$redirect_uri ||!$scope ||!$consent ||!$state) {
        wp_die('Missing required parameters for SSO redirection.');
    }

    if ($consent === 'yes') {
        // 用户同意授权
        // 这里可以生成授权码，实际应用中需要实现更安全的生成逻辑
        $authorization_code = wp_generate_password(32, false);

        // 在重定向 URI 中添加授权码和状态参数
        $redirect_uri = add_query_arg([
            'code' => $authorization_code,
            'state' => $state
        ], $redirect_uri);
    } else {
        // 用户拒绝授权
        // 在重定向 URI 中添加错误信息和状态参数
        $redirect_uri = add_query_arg([
            'error' => 'access_denied',
            'error_description' => 'User denied access',
            'state' => $state
        ], $redirect_uri);
    }

    // 执行重定向操作
    wp_redirect($redirect_uri);
    exit;
} else {
    // 如果不是 POST 请求，给出错误提示
    wp_die('Invalid request method for SSO redirection.');
}