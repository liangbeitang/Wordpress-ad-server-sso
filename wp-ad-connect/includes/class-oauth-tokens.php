<?php
// 防止直接访问，确保在 WordPress 环境中运行
if (!defined('ABSPATH')) {
    exit;
}

// 引入 Firebase JWT 库用于 JWT 处理
use Firebase\JWT\JWT;

class ADC_OAuth_Tokens {

    /**
     * 生成 JWT 令牌
     *
     * @param int $user_id 用户 ID
     * @param string $client_id 客户端 ID
     * @param string $scopes 请求的权限范围
     * @return string 生成的 JWT 令牌
     */
    public function generate_jwt($user_id, $client_id, $scopes) {
        // 获取用户数据
        $user = get_userdata($user_id);

        // 构建 JWT 载荷
        $payload = [
            "iss" => get_site_url(), // 发行者，即当前网站的 URL
            "sub" => $user_id, // 主题，这里是用户 ID
            "aud" => $client_id, // 受众，即客户端 ID
            "exp" => time() + 3600, // 过期时间，设置为当前时间加 1 小时
            "iat" => time(), // 签发时间，即当前时间
            "auth_time" => strtotime($user->user_registered), // 用户注册时间
            "email" => $user->user_email, // 用户邮箱
            "preferred_username" => $user->user_login // 用户登录名
        ];

        // 如果请求的权限范围包含 'ad_attrs'，则添加 AD 属性到载荷
        if (strpos($scopes, 'ad_attrs')!== false) {
            $payload['ad_attrs'] = get_user_meta($user_id, 'ad_attributes', true);
        }

        // 动态选择签名算法
        $algorithm = $this->get_client_algorithm($client_id);

        // 获取签名密钥
        $key = $this->get_signing_key($algorithm, $client_id);

        // 生成 JWT 令牌
        return JWT::encode(
            $payload,
            $key,
            $algorithm
        );
    }

    /**
     * 获取签名密钥
     *
     * @param string $alg 签名算法
     * @param string $client_id 客户端 ID
     * @return string 签名密钥
     */
    private function get_signing_key($alg, $client_id) {
        if ($alg === 'RS256') {
            // 如果是 RS256 算法，从文件中读取私钥
            return file_get_contents(ADC_KEY_DIR. '/private.pem');
        }
        // 其他情况（如 HS256），获取客户端密钥
        return $this->get_client_secret($client_id);
    }

    /**
     * 获取客户端使用的签名算法
     *
     * @param string $client_id 客户端 ID
     * @return string 签名算法
     */
    private function get_client_algorithm($client_id) {
        // 这里需要实现根据客户端 ID 获取其配置的签名算法的逻辑
        // 目前简单返回 HS256，可根据实际情况修改
        return 'HS256';
    }

    /**
     * 获取客户端密钥
     *
     * @param string $client_id 客户端 ID
     * @return string 客户端密钥
     */
    private function get_client_secret($client_id) {
        // 这里需要实现根据客户端 ID 获取其密钥的逻辑
        // 目前简单返回一个示例密钥，可根据实际情况修改
        return 'example_client_secret';
    }
}