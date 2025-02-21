<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 检查是否已经添加了adc-admin菜单，如果没有则添加
if (!function_exists('add_ad_admin_menu')) {
    function add_ad_admin_menu() {
        add_menu_page(
            'AD Connect Admin', // 页面标题
            'AD Connect', // 菜单标题
            'manage_options', // 访问该页面所需的权限
            'adc-admin', // 页面的唯一标识符
            '', // 用于显示页面内容的回调函数
            'dashicons-admin-generic', // 菜单图标
            6 // 菜单位置
        );
    }
    add_action('admin_menu', 'add_ad_admin_menu');
}

class ADC_Admin_Interface {
    private $sso_clients_table;

    public function __construct() {
        global $wpdb;
        $this->sso_clients_table = $wpdb->prefix . 'adc_sso_clients';
        
        add_action('admin_menu', [$this, 'init_admin_menu']);
        add_action('admin_init', [$this, 'handle_admin_requests']); // 修正方法名
        add_action('admin_init', [$this, 'register_settings']);
    }

    // 初始化管理菜单
    public function init_admin_menu() {
        add_submenu_page(
            'adc-admin', // 父菜单的唯一标识符
            'SSO 客户端管理', // 页面标题
            'SSO 客户端管理', // 菜单标题
            'manage_options', // 访问该页面所需的权限
            'adc-sso-clients', // 页面的唯一标识符
            [$this, 'sso_clients_page'] // 用于显示页面内容的回调函数
        );
    }

    // 重命名处理方法
    public function handle_admin_requests() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // 处理客户端注册
        if (isset($_POST['register_client']) && wp_verify_nonce($_POST['adc_nonce'], 'adc_client_registration')) {
            $this->register_client();
        }

        // 处理其他管理请求
        if (isset($_GET['action'])) {
            $this->handle_client_actions();
        }
    }

    private function handle_client_actions() {
        global $wpdb;
        $client_id = sanitize_text_field($_GET['client_id'] ?? '');

        switch ($_GET['action']) {
            case 'delete':
                if ($client_id) {
                    $wpdb->delete($this->sso_clients_table, ['client_id' => $client_id]);
                }
                break;
                
            case 'reset_secret':
                if ($client_id) {
                    $new_secret = $this->generate_client_secret();
                    $wpdb->update(
                        $this->sso_clients_table,
                        ['client_secret' => $new_secret],
                        ['client_id' => $client_id]
                    );
                }
                break;
        }
    }

    private function register_client() {
        global $wpdb;

        // 数据验证
        $redirect_uri = esc_url_raw($_POST['redirect_uri'] ?? '');
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
        $client_data = [
            'client_id' => $this->generate_client_id(),
            'client_secret' => $this->generate_client_secret(),
            'redirect_uri' => $redirect_uri,
            'scopes' => implode(',', $scopes),
            'created_at' => current_time('mysql') // 添加创建时间
        ];

        // 插入数据库
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

    // 生成客户端ID
    private function generate_client_id() {
        return wp_generate_uuid4();
    }

    // 生成客户端密钥
    private function generate_client_secret() {
        return wp_generate_password(32, true, true);
    }

    // 注册设置
    public function register_settings() {
        // 这里可以添加注册设置的代码
    }

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
                                <th>操作</th>
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
                                <td>
                                    <a href="<?= add_query_arg([
                                        'page' => 'adc-sso-clients',
                                        'action' => 'reset_secret',
                                        'client_id' => $client->client_id
                                    ]) ?>" class="button button-secondary">重置密钥</a>
                                    
                                    <a href="<?= add_query_arg([
                                        'page' => 'adc-sso-clients',
                                        'action' => 'delete',
                                        'client_id' => $client->client_id
                                    ]) ?>" class="button button-link-delete" 
                                    onclick="return confirm('确定要删除该客户端吗？')">删除</a>
                                </td>
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
                    <?php wp_nonce_field('adc_client_registration', 'adc_nonce'); ?>
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
}

new ADC_Admin_Interface();