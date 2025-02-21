<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 当 WordPress REST API 初始化时执行以下操作
add_action('rest_api_init', function () {
    // 注册授权端点
    register_rest_route('adc-sso/v1', '/authorize', [
        'methods'  => 'GET',
        'callback' => function ($request) {
            // 获取请求中的参数
            $client_id = $request->get_param('client_id');
            $redirect_uri = $request->get_param('redirect_uri');
            $scope = $request->get_param('scope');
            $state = $request->get_param('state');

            // 检查必要参数是否存在
            if (!$client_id || !$redirect_uri || !$scope) {
                return new WP_REST_Response([
                    'error' => 'invalid_request',
                    'error_description' => 'Missing required parameters'
                ], 400);
            }

            // 检查客户端 ID 是否有效，这里简单模拟验证，实际应从数据库查询
            if ($client_id!== 'valid_client_id') {
                return new WP_REST_Response([
                    'error' => 'unauthorized_client',
                    'error_description' => 'Invalid client ID'
                ], 401);
            }

            // 检查用户是否已登录
            if (!is_user_logged_in()) {
                // 若未登录，重定向到登录页面，登录成功后再返回授权页面
                $login_url = wp_login_url(add_query_arg([
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope,
                    'state' => $state
                ], rest_url('adc-sso/v1/authorize')));
                return new WP_REST_Response([
                    'redirect' => $login_url
                ], 302);
            }

            // 显示授权页面，让用户确认授权
            $user = wp_get_current_user();
            $authorize_page = '<html><body>';
            $authorize_page.= '<h1>Authorize Access</h1>';
            $authorize_page.= '<p>Client '. esc_html($client_id).' is requesting access to your account with the following scopes: '. esc_html($scope). '</p>';
            $authorize_page.= '<form method="post" action="'. rest_url('adc-sso/v1/authorize'). '">';
            $authorize_page.= '<input type="hidden" name="client_id" value="'. esc_attr($client_id). '">';
            $authorize_page.= '<input type="hidden" name="redirect_uri" value="'. esc_attr($redirect_uri). '">';
            $authorize_page.= '<input type="hidden" name="scope" value="'. esc_attr($scope). '">';
            $authorize_page.= '<input type="hidden" name="state" value="'. esc_attr($state). '">';
            $authorize_page.= '<input type="submit" name="authorize" value="Authorize">';
            $authorize_page.= '<input type="submit" name="deny" value="Deny">';
            $authorize_page.= '</form>';
            $authorize_page.= '</body></html>';

            return new WP_REST_Response($authorize_page, 200);
        }
    ]);

    // 注册令牌端点
    register_rest_route('adc-sso/v1', '/token', [
        'methods'  => 'POST',
        'callback' => function ($request) {
            $grant_type = $request->get_param('grant_type');
            $code = $request->get_param('code');
            $client_id = $request->get_param('client_id');
            $client_secret = $request->get_param('client_secret');
            $redirect_uri = $request->get_param('redirect_uri');

            // 检查必要参数是否存在
            if (!$grant_type || (!$code && $grant_type === 'authorization_code') ||!$client_id ||!$client_secret ||!$redirect_uri) {
                return new WP_REST_Response([
                    'error' => 'invalid_request',
                    'error_description' => 'Missing required parameters'
                ], 400);
            }

            // 验证客户端凭证，这里简单模拟验证，实际应从数据库查询
            if ($client_id!== 'valid_client_id' || $client_secret!== 'valid_client_secret') {
                return new WP_REST_Response([
                    'error' => 'invalid_client',
                    'error_description' => 'Invalid client credentials'
                ], 401);
            }

            if ($grant_type === 'authorization_code') {
                // 验证授权码，这里简单模拟验证
                if ($code!== 'valid_authorization_code') {
                    return new WP_REST_Response([
                        'error' => 'invalid_grant',
                        'error_description' => 'Invalid authorization code'
                    ], 400);
                }

                // 生成访问令牌和刷新令牌
                $access_token = wp_generate_password(32, false);
                $refresh_token = wp_generate_password(32, false);

                return new WP_REST_Response([
                    'access_token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ], 200);
            }

            return new WP_REST_Response([
                'error' => 'unsupported_grant_type',
                'error_description' => 'Unsupported grant type'
            ], 400);
        }
    ]);

    // 注册用户信息端点
    register_rest_route('adc-sso/v1', '/userinfo', [
        'methods'  => 'GET',
        'callback' => function ($request) {
            // 从请求头中获取授权令牌
            $authorization = $request->get_header('Authorization');
            if (!$authorization || strpos($authorization, 'Bearer ')!== 0) {
                return new WP_REST_Response([
                    'error' => 'invalid_request',
                    'error_description' => 'Missing or invalid authorization header'
                ], 400);
            }
            $access_token = substr($authorization, 7);

            // 验证访问令牌，这里简单模拟验证
            if ($access_token!== 'valid_access_token') {
                return new WP_REST_Response([
                    'error' => 'invalid_token',
                    'error_description' => 'Invalid access token'
                ], 401);
            }

            // 获取当前用户信息
            $user = wp_get_current_user();
            $user_info = [
                'sub' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            ];

            return new WP_REST_Response($user_info, 200);
        },
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);
});