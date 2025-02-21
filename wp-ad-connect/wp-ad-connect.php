<?php
/*
Plugin Name: WP AD Connect
Description: Integrate Active Directory with WordPress and provide SSO services.
Version: 1.0
Author: Your Name
*/

// 防止直接访问该文件，确保文件只能在 WordPress 环境中运行
if (!defined('ABSPATH')) {
    exit;
}

// 定义存储密钥文件的目录常量
define('ADC_KEY_DIR', plugin_dir_path(__FILE__) . 'keys');

class WP_AD_Connect {
    public function __construct() {
        // 注册插件激活时的钩子，调用 activate 方法
        register_activation_hook(__FILE__, array($this, 'activate'));
        // 注册插件加载时的钩子，调用 init 方法
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        global $wpdb;
        // 定义 SSO 客户端存储表的表名
        $table_name = $wpdb->prefix . 'adc_sso_clients';
        // 获取当前数据库的字符集和排序规则
        $charset_collate = $wpdb->get_charset_collate();

        // SQL 语句用于创建 SSO 客户端存储表
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id varchar(255) NOT NULL,
            client_secret varchar(255) NOT NULL,
            redirect_uri text NOT NULL,
            scopes text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 引入 WordPress 提供的数据库升级函数
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // 执行数据库表的创建或更新操作
        dbDelta($sql);

        // 添加插件的默认选项，设置 LDAP 主机地址
        add_option('adc_ldap_host', 'dc.example.com');
        // 添加插件的默认选项，设置默认用户角色
        add_option('adc_default_role', 'contributor');
    }

    public function init() {
        // 引入各个功能模块的类文件
        require_once dirname(__FILE__) . '/includes/class-ad-operator.php';
        require_once dirname(__FILE__) . '/includes/class-user-handler.php';
        require_once dirname(__FILE__) . '/includes/class-sso-server.php';
        require_once dirname(__FILE__) . '/includes/class-oauth-tokens.php';
        require_once dirname(__FILE__) . '/includes/class-mail-service.php';
        require_once dirname(__FILE__) . '/includes/settings-page.php';
        require_once dirname(__FILE__) . '/includes/sso-endpoints.php';
        require_once dirname(__FILE__) . '/includes/ad-sso-functions.php';

        // 检查文件是否存在后再引入管理界面相关的文件
        $admin_interface_file = dirname(__FILE__) . '/admin/admin-interface.php';
        if (file_exists($admin_interface_file)) {
            require_once $admin_interface_file;
        } else {
            error_log('admin-interface.php file not found');
        }

        $sso_clients_file = dirname(__FILE__) . '/admin/sso-clients.php';
        if (file_exists($sso_clients_file)) {
            require_once $sso_clients_file;
        } else {
            error_log('sso-clients.php file not found');
        }

        // 引入公共界面相关的文件
        require_once dirname(__FILE__) . '/public/user-profile.php';
        require_once dirname(__FILE__) . '/public/sso-consent-form.php';

        // 初始化各个功能模块的类实例
        new ADC_Settings_Page();
        new ADC_SSO_Server();
        new ADC_User_Handler();
    }
}

// 创建 WP_AD_Connect 类的实例，启动插件功能
new WP_AD_Connect();