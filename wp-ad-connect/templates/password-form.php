<?php
// 防止直接访问，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 确保用户已登录
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

// 获取当前登录用户信息
$user = wp_get_current_user();
$message = '';
$verification_code_sent = false;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_verification_code'])) {
        // 生成验证码
        $verification_code = wp_generate_password(6, false, false);
        // 存储验证码到会话中
        $_SESSION['adc_password_verification_code'] = $verification_code;
        $verification_code_sent = true;

        // 准备邮件内容
        $to = $user->user_email;
        $subject = 'Active Directory Password Reset Verification Code';
        $body = "Your verification code for resetting your Active Directory password is: $verification_code";
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // 发送邮件
        if (wp_mail($to, $subject, $body, $headers)) {
            $message = 'Verification code has been sent to your email. Please check your inbox.';
        } else {
            $message = 'Failed to send verification code. Please try again later.';
        }
    } elseif (isset($_POST['change_password'])) {
        // 获取用户输入的验证码和新密码
        $input_verification_code = sanitize_text_field($_POST['verification_code']);
        $new_password = sanitize_text_field($_POST['new_password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);

        // 验证验证码
        if ($input_verification_code!== $_SESSION['adc_password_verification_code']) {
            $message = 'Invalid verification code. Please try again.';
        } elseif ($new_password!== $confirm_password) {
            $message = 'New password and confirm password do not match. Please try again.';
        } else {
            // 这里需要添加实际修改 Active Directory 密码的逻辑
            // 假设存在一个修改 AD 密码的函数
            if (function_exists('modify_ad_password')) {
                $ad_modified = modify_ad_password($user->user_login, $new_password);
            } else {
                $ad_modified = false;
                error_log('modify_ad_password function not found');
            }

            if ($ad_modified) {
                $message = 'Your Active Directory password has been successfully changed.';
                unset($_SESSION['adc_password_verification_code']);
            } else {
                $message = 'Failed to change your Active Directory password. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Active Directory Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

       .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
        }

       .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

       .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <h1>Change Active Directory Password</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'successfully')!== false? 'success' : 'error'; ?>">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$verification_code_sent): ?>
        <form method="post">
            <button type="submit" name="send_verification_code">Send Verification Code</button>
        </form>
    <?php else: ?>
        <form method="post">
            <label for="verification_code">Verification Code:</label>
            <input type="text" id="verification_code" name="verification_code" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit" name="change_password">Change Password</button>
        </form>
    <?php endif; ?>
</body>

</html>