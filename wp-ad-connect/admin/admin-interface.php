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
        add_action('admin_init', array($this, 'handle_client_registration'));
        add_action('admin_init', array($this, 'create_sso_clients_table'));
    }

    // 初始化管理菜单
    public function init_admin_menu() {
        $this->add_main_menu();
        $this->add_sub_menus();
        $this->remove_duplicate_submenu();
    }

    // 创建SSO客户端表
    public function create_sso_clients_table() {
        global $wpdb;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->sso_clients_table}'") != $this->sso_clients_table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->sso_clients_table} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                client_id varchar(100) NOT NULL,
                client_secret varchar(255) NOT NULL,
                redirect_uri varchar(512) NOT NULL,
                scopes varchar(255) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    // 处理客户端注册
    public function handle_client_registration() {
        if (!current_user_can('manage_options') || !isset($_POST['register_client'])) {
            return;
        }

        global $wpdb;
        
        // 数据验证
        $redirect_uri = esc_url_raw($_POST['redirect_uri']);
        $scopes = isset($_POST['scopes']) ? array_map('sanitize_text_field', $_POST['scopes']) : [];
        
        if (empty($redirect_uri) || empty($scopes)) {
            add_settings_error(
                'adc_sso_clients',
                'missing_fields',
                '请填写所有必填字段',
                'error'
            );
            return;
        }

        // 生成客户端凭证
        $client_data = array(
            'client_id' => $this->generate_client_id(),
            'client_secret' => $this->generate_client_secret(),
            'redirect_uri' => $redirect_uri,
            'scopes' => implode(',', $scopes)
        );

        // 插入数据库
        $result = $wpdb->insert(
            $this->sso_clients_table,
            $client_data
        );

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

    // 生成客户端ID
    private function generate_client_id() {
        return 'CLIENT-' . bin2hex(random_bytes(8));
    }

    // 生成客户端密钥
    private function generate_client_secret() {
        return bin2hex(random_bytes(16));
    }

    // 主菜单设置
    private function add_main_menu() {
        add_menu_page(
            'AD 配置',
            'AD 连接',
            'manage_options',
            'adc-admin',
            array($this, 'ad_config_page'),
            'dashicons-admin-generic',
            6
        );
    }

    // 子菜单设置
    private function add_sub_menus() {
        $sub_menus = array(
            array(
                'parent' => 'adc-admin',
                'title'  => 'AD 配置',
                'slug'   => 'adc-admin-settings',
                'callback' => array($this, 'ad_config_page')
            ),
            array(
                'parent' => 'adc-admin',
                'title'  => 'SSO 服务器配置',
                'slug'   => 'adc-sso-server',
                'callback' => array($this, 'sso_server_page')
            ),
            array(
                'parent' => 'adc-admin',
                'title'  => 'SSO 客户端管理',
                'slug'   => 'adc-sso-clients',
                'callback' => array($this, 'sso_clients_page')
            ),
            array(
                'parent' => 'adc-admin',
                'title'  => 'SMTP 配置',
                'slug'   => 'adc-smtp',
                'callback' => array($this, 'smtp_page')
            ),
            array(
                'parent' => 'adc-admin',
                'title'  => '验证邮件模板',
                'slug'   => 'adc-email-template',
                'callback' => array($this, 'email_template_page')
            )
        );

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

    // 移除重复菜单
    private function remove_duplicate_submenu() {
        remove_submenu_page('adc-admin', 'adc-admin');
    }

    // AD配置页面
    public function ad_config_page() {
        // 保持原有AD配置内容...
    }

    // SSO客户端管理页面
    public function sso_clients_page() {
        global $wpdb;
        $clients = $wpdb->get_results("SELECT * FROM {$this->sso_clients_table} ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>SSO 客户端管理</h1>
            <?php settings_errors('adc_sso_clients'); ?>
            
            <div class="card">
                <h2>已注册客户端</h2>
                <?php if ($clients) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>客户端ID</th>
                                <th>客户端密钥</th>
                                <th>回调地址</th>
                                <th>权限范围</th>
                                <th>创建时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client) : ?>
                            <tr>
                                <td><code><?= esc_html($client->client_id) ?></code></td>
                                <td><code><?= esc_html($client->client_secret) ?></code></td>
                                <td><?= esc_url($client->redirect_uri) ?></td>
                                <td><?= esc_html($client->scopes) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($client->created_at)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>当前没有已注册的客户端</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>注册新客户端</h2>
                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th><label for="redirect_uri">回调地址</label></th>
                            <td>
                                <input type="url" name="redirect_uri" 
                                       id="redirect_uri" class="regular-text" 
                                       required pattern="https?://.+"
                                       placeholder="https://example.com/callback">
                                <p class="description">必须包含协议头（http:// 或 https://）</p>
                            </td>
                        </tr>
                        <tr>
                            <th>权限范围</th>
                            <td>
                                <fieldset>
                                    <label><input type="checkbox" name="scopes[]" value="openid" checked> OpenID</label>

                                    <label><input type="checkbox" name="scopes[]" value="profile"> 用户资料</label>

                                    <label><input type="checkbox" name="scopes[]" value="email"> 电子邮件</label>

                                    <label><input type="checkbox" name="scopes[]" value="ad_attrs"> AD属性</label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('注册客户端', 'primary', 'register_client'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    // 其他页面方法保持不变...
}

new ADC_Admin_Interface();