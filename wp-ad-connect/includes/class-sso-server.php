<?php
// 防止直接访问该文件，确保文件只能在 WordPress 环境中运行
if (!defined('ABSPATH')) {
    exit;
}

// 引入 Firebase JWT 库，用于处理 JWT 相关操作
use Firebase\JWT\JWT;

class ADC_SSO_Server {
    // 定义支持的授权类型
    const GRANT_TYPES = [
        'authorization_code',
        'refresh_token'
    ];

    /**
     * 构造函数，注册 REST API 路由
     */
    public function __construct() {
        // 当 REST API 初始化时，调用 register_sso_routes 方法注册路由
        add_action('rest_api_init', array($this, 'register_sso_routes'));
    }

    /**
     * 注册 SSO 相关的 REST API 路由
     */
    public function register_sso_routes() {
        // 注册 OpenID Connect 发现文档端点
        register_rest_route('adc-sso/v1', '/.well-known/openid-configuration', [
            'methods'  => 'GET',
            'callback' => array($this, 'discovery_endpoint')
        ]);

        // 注册 JWKS 端点
        register_rest_route('adc-sso/v1', '/.well-known/jwks.json', [
            'methods'  => 'GET',
            'callback' => array($this, 'jwks_endpoint')
        ]);
    }

    /**
     * 处理 OpenID Connect 发现文档端点的请求
     *
     * @return array 返回 OpenID Connect 发现文档的 JSON 数据
     */
    public function discovery_endpoint() {
        return [
            "issuer"                 => get_site_url(),
            "authorization_endpoint" => rest_url('adc-sso/v1/authorize'),
            "token_endpoint"         => rest_url('adc-sso/v1/token'),
            "userinfo_endpoint"      => rest_url('adc-sso/v1/userinfo'),
            "jwks_uri"               => rest_url('adc-sso/v1/.well-known/jwks.json'),
            "scopes_supported"       => ["openid", "profile", "email", "ad_attrs"],
            "response_types_supported" => ["code", "token", "id_token"],
            "id_token_signing_alg_values_supported" => ["HS256", "RS256"]
        ];
    }

    /**
     * 处理 JWKS 端点的请求
     *
     * @return array 返回 JWKS 的 JSON 数据，目前仅返回空数组，需后续完善
     */
    public function jwks_endpoint() {
        // 此处应实现返回 JWKS 的具体逻辑，目前仅返回空数组
        return [];
    }
}

// 创建 ADC_SSO_Server 类的实例，启动 SSO 服务路由注册
new ADC_SSO_Server();