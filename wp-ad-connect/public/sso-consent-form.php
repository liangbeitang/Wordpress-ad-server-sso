<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 定义一个函数来显示 SSO 授权同意表单
function adc_sso_consent_form() {
    // 检查用户是否已登录
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }

    // 获取当前登录用户
    $user = wp_get_current_user();

    // 从请求参数中获取客户端 ID、重定向 URI、权限范围和状态参数
    $client_id = isset($_GET['client_id']) ? sanitize_text_field($_GET['client_id']) : '';
    $redirect_uri = isset($_GET['redirect_uri']) ? esc_url_raw($_GET['redirect_uri']) : '';
    $scope = isset($_GET['scope']) ? sanitize_text_field($_GET['scope']) : '';
    $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';

    // 检查必要参数是否存在
    if (!$client_id || !$redirect_uri || !$scope) {
        wp_die('Missing required parameters for SSO consent form.');
    }

    // 获取客户端信息（这里简单假设从数据库中获取客户端名称，实际需要实现具体逻辑）
    $client_name = 'Client Name'; // 需替换为实际从数据库获取客户端名称的逻辑

    // 开始输出 HTML 页面
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF - 8">
        <meta name="viewport" content="width=device-width, initial - scale=1.0">
        <title>SSO Consent Form</title>
        <style>
            body {
                font-family: Arial, sans - serif;
                padding: 20px;
            }

            h1 {
                color: #333;
            }

            form {
                margin-top: 20px;
            }

            button {
                padding: 10px 20px;
                margin - right: 10px;
            }
        </style>
    </head>

    <body>
        <h1>SSO Consent Form</h1>
        <p>Hello, <?php echo esc_html($user->display_name); ?>.</p>
        <p>The application <strong><?php echo esc_html($client_name); ?></strong> is requesting access to the following scopes:</p>
        <ul>
            <?php
            // 将权限范围字符串分割为数组并显示
            $scopes = explode(' ', $scope);
            foreach ($scopes as $single_scope) {
                echo '<li>' . esc_html($single_scope) . '</li>';
            }
            ?>
        </ul>
        <form method="post" action="<?php echo esc_url_raw(admin_url('admin - ajax.php')); ?>">
            <input type="hidden" name="action" value="adc_sso_consent">
            <input type="hidden" name="client_id" value="<?php echo esc_attr($client_id); ?>">
            <input type="hidden" name="redirect_uri" value="<?php echo esc_url($redirect_uri); ?>">
            <input type="hidden" name="scope" value="<?php echo esc_attr($scope); ?>">
            <input type="hidden" name="state" value="<?php echo esc_attr($state); ?>">
            <button type="submit" name="consent" value="yes">Authorize</button>
            <button type="submit" name="consent" value="no">Deny</button>
        </form>
    </body>

    </html>
    <?php
}

// 处理用户授权同意或拒绝的 AJAX 请求
add_action('wp_ajax_adc_sso_consent', 'adc_handle_sso_consent');
add_action('wp_ajax_nopriv_adc_sso_consent', 'adc_handle_sso_consent');

function adc_handle_sso_consent() {
    // 获取表单提交的数据
    $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
    $redirect_uri = isset($_POST['redirect_uri']) ? esc_url_raw($_POST['redirect_uri']) : '';
    $scope = isset($_POST['scope']) ? sanitize_text_field($_POST['scope']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $consent = isset($_POST['consent']) ? sanitize_text_field($_POST['consent']) : '';

    // 检查必要参数是否存在
    if (!$client_id || !$redirect_uri || !$scope ||!$consent) {
        wp_send_json_error('Missing required parameters.');
    }

    if ($consent === 'yes') {
        // 用户同意授权，这里可生成授权码等操作（需实现具体逻辑）
        $authorization_code = wp_generate_password(32, false);
        $redirect_uri = add_query_arg([
            'code' => $authorization_code,
            'state' => $state
        ], $redirect_uri);
    } else {
        // 用户拒绝授权，返回错误信息
        $redirect_uri = add_query_arg([
            'error' => 'access_denied',
            'error_description' => 'User denied access',
            'state' => $state
        ], $redirect_uri);
    }

    // 重定向用户到客户端的回调 URI
    wp_redirect($redirect_uri);
    exit;
}