<?php
if (!defined('ABSPATH')) {
    exit;
}

class ADC_Admin_Interface {
    private $sso_clients_table;

    public function __construct() {
        global $wpdb;
        $this->sso_clients_table = $wpdb->prefix . 'adc_sso_clients';
        
        add_action('admin_menu', array($this, 'init_admin_menu'));
        add_action('admin_init', array($this, 'handle_client_registration')); // 修正为正确的方法名
        add_action('admin_init', array($this, 'register_settings'));
    }

    // 注册所有设置项
    public function register_settings() {
        register_setting('adc-settings-group', 'adc_ldap_host');
        register_setting('adc-settings-group', 'adc_default_role');
        register_setting('adc-settings-group', 'adc_mail_service');
        register_setting('adc-settings-group', 'adc_admin_user');
        register_setting('adc-settings-group', 'adc_admin_password');
        register_setting('adc-settings-group', 'adc_domain_to_sync');
        register_setting('adc-settings-group', 'adc_dn');
    }

    // 初始化菜单
    public function init_admin_menu() {
        $this->add_main_menu();
        $this->add_sub_menus();
        $this->remove_duplicate_submenu();
    }

    private function add_main_menu() {
        add_menu_page(
            'AD 配置',
            'AD 连接',
            'manage_options',
            'adc-admin',
            array($this, 'main_settings_page'),
            'dashicons-admin-generic',
            6
        );
    }

    private function add_sub_menus() {
        $sub_menus = [
            [
                'parent' => 'adc-admin',
                'title'  => '基础配置',
                'slug'   => 'adc-admin',
                'callback' => array($this, 'main_settings_page')
            ],
            [
                'parent' => 'adc-admin',
                'title'  => 'SSO 服务端',
                'slug'   => 'adc-sso-server',
                'callback' => array($this, 'sso_server_page')
            ],
            [
                'parent' => 'adc-admin',
                'title'  => '客户端管理',
                'slug'   => 'adc-sso-clients',
                'callback' => array($this, 'sso_clients_page')
            ],
            [
                'parent' => 'adc-admin',
                'title'  => '邮件配置',
                'slug'   => 'adc-smtp',
                'callback' => array($this, 'smtp_page')
            ]
        ];

        foreach ($sub_menus as $menu) {
            add_submenu_page(
                $menu['parent'],
                $menu['title'],
                $menu['title'],
                'manage_options',
                $menu['slug'],
                $menu['callback']
            );
        }
    }

    private function remove_duplicate_submenu() {
        remove_submenu_page('adc-admin', 'adc-admin');
    }

    // 处理客户端注册
    public function handle_client_registration() {
        if (!isset($_POST['register_client'])) {
            return;
        }

        if (!current_user_can('manage_options') || 
            !wp_verify_nonce($_POST['_wpnonce'], 'adc_client_registration')) {
            wp_die('安全验证失败');
        }

        global $wpdb;
        
        $redirect_uri = esc_url_raw($_POST['redirect_uri']);
        $scopes = isset($_POST['scopes']) ? 
                 array_map('sanitize_text_field', $_POST['scopes']) : [];

        if (empty($redirect_uri) || empty($scopes)) {
            add_settings_error(
                'adc_sso_clients',
                'missing_fields',
                '请填写所有必填字段',
                'error'
            );
            return;
        }

        $client_data = [
            'client_id' => $this->generate_client_id(),
            'client_secret' => $this->generate_client_secret(),
            'redirect_uri' => $redirect_uri,
            'scopes' => implode(',', $scopes)
        ];

        $result = $wpdb->insert($this->sso_clients_table, $client_data);

        if ($result) {
            add_settings_error(
                'adc_sso_clients',
                'client_registered',
                '客户端注册成功',
                'success'
            );
        } else {
            add_settings_error(
                'adc_sso_clients',
                'registration_failed',
                '客户端注册失败，请重试',
                'error'
            );
        }
    }

    private function generate_client_id() {
        return 'CLIENT-' . bin2hex(random_bytes(8));
    }

    private function generate_client_secret() {
        return bin2hex(random_bytes(16));
    }

    public function main_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('权限不足');
        }
        ?>
        <div class="wrap">
            <h1>AD 基础配置</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('adc-settings-group');
                do_settings_sections('adc-settings-group');
                ?>
                <table class="form-table">
                    <!-- 保持原有配置字段 -->
                </table>
                <?php submit_button('保存配置'); ?>
            </form>
        </div>
        <?php
    }

    public function sso_clients_page() {
        global $wpdb;
        $clients = $wpdb->get_results("SELECT * FROM {$this->sso_clients_table}");
        ?>
        <div class="wrap">
            <h1>SSO 客户端管理</h1>
            <?php settings_errors('adc_sso_clients'); ?>
            
            <div class="card">
                <h2>注册新客户端</h2>
                <form method="post">
                    <?php wp_nonce_field('adc_client_registration'); ?>
                    <table class="form-table">
                        <!-- 保持原有表单字段 -->
                    </table>
                    <?php submit_button('注册客户端', 'primary', 'register_client'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    // 其他页面方法...
}

new ADC_Admin_Interface();