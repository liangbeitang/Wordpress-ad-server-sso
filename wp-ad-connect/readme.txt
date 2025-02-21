=== WordPress AD Connect with SSO ===

Contributors: Liangbeitang，website:www.liangbeitang.com
Tags: Active Directory, SSO, WordPress integration
Requires at least: WordPress 5.6
Tested up to: WordPress [Latest Version]
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

The WordPress AD Connect with SSO plugin enables seamless integration between Active Directory (AD) and WordPress, providing a single sign - on (SSO) solution for users. It supports LDAP/LDAPS protocols, user attribute mapping, two - factor authentication, and OAuth 2.0 & OpenID Connect for SSO services.

== File Directory Structure ==

wp - ad - connect/
├── wp - ad - connect.php            // 主插件文件
├── readme.txt                   // 使用文档
├── languages/                   // 多语言支持
├── includes/
│   ├── class - ad - operator.php    // AD操作核心
│   ├── class - user - handler.php   // 用户同步处理
│   ├── class - sso - server.php     // SSO服务核心
│   ├── class - oauth - tokens.php   // 令牌管理
│   ├── class - mail - service.php   // 邮件服务
│   ├── settings - page.php        // 基础设置
│   ├── sso - endpoints.php        // REST API端点
│   └── ad - sso - functions.php     // 通用函数
├── admin/
│   ├── admin - interface.php      // 基础设置界面
│   └── sso - clients.php          // SSO客户端管理
├── public/
│   ├── user - profile.php         // 用户资料扩展
│   └── sso - consent - form.php     // 授权同意界面
└── templates/                   // 前端模板
    ├── password - form.php
    └── sso - redirect.php

== Features ==

### Core Function Modules
1. **AD Integration Module**
    - Connect using LDAP/LDAPS protocols.
    - Map user attributes (e.g., EmployeeID to username, mail to email).
    - Support two - factor authentication (AD first, fallback to WP).
    - Allow AD password modification with email verification code.
    - Automatically assign roles (configurable default role).
2. **SSO Service Module**
    - Support OAuth 2.0 & OpenID Connect protocols.
    - Issue and verify JWT tokens.
    - Provide a client management system.
    - Implement a user authorization consent process.
    - Offer a user information endpoint with AD attributes.
3. **Security Module**
    - Store encrypted tokens.
    - Protect against CSRF attacks.
    - Keep audit logs.
    - Apply rate limits.
    - Automatically rotate keys.
4. **Extended Features**
    - Support multiple mail services (e.g., Alibaba Cloud, Tencent Cloud).
    - Allow custom field mapping.
    - Provide webhook support.
    - Track synchronization logs.

== Installation ==

1. **Server Requirements**
    - PHP 7.4+ with ldap and openssl extensions enabled.
    - WordPress 5.6+
    - HTTPS must be enforced.

2. **Configuration Steps**
    1. Upload the plugin files to the `/wp - content/plugins/` directory.
    2. Activate the plugin through the 'Plugins' menu in WordPress.
    3. Navigate to 'Settings' -> 'AD Connect' to configure:
        - AD server connection parameters.
        - Field mapping rules.
        - Default user role.
    4. Register third - party systems in the 'SSO Clients' section.

== SSO Integration Process ==

1. **Client Registration**
    - Obtain a client_id and client_secret.
    - Configure the callback URL and authorization scope.

2. **Standard OAuth Process**
    - The third - party system redirects the user to the authorization endpoint.
    - WordPress displays the login/authorization page.
    - The user logs in and agrees to the authorization.
    - WordPress returns an authorization code to the third - party system.
    - The third - party system exchanges the authorization code for a token.
    - WordPress returns a JWT token.
    - The third - party system uses the token to obtain user information.

== Developer API ==

### Available Hooks
| Hook Name              | Description                         |
|-----------------------|------------------------------------|
| adc_before_user_sync  | Modify AD data before user synchronization. |
| adc_after_user_create | Perform actions after a new user is created. |
| adc_jwt_payload       | Modify the JWT token content.       |

### REST Endpoint Examples
```bash
# Discovery Document
GET /wp - json/adc - sso/v1/.well - known/openid - configuration

# User Information Request
GET /wp - json/adc - sso/v1/userinfo
Authorization: Bearer <access_token>