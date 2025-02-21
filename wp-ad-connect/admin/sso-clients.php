<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

class ADC_SSO_Clients {
    public function __construct() {
        // 当 WordPress 加载管理菜单时，调用 add_sso_clients_page 方法添加 SSO 客户端管理页面
        add_action('admin_menu', array($this, 'add_sso_clients_page'));
        // 当 WordPress 初始化管理设置时，调用 handle_client_registration 方法处理客户端注册操作
        add_action('admin_init', array($this, 'handle_client_registration'));
    }

    /**
     * 添加 SSO 客户端管理页面到 WordPress 管理菜单
     */
    public function add_sso_clients_page() {
        add_submenu_page(
            'adc-admin', // 父菜单的唯一标识符
            'SSO Clients', // 页面标题
            'SSO Clients', // 菜单标题
            'manage_options', // 访问该页面所需的权限
            'adc-sso-clients', // 页面的唯一标识符
            array($this, 'sso_clients_page_content') // 用于显示页面内容的回调函数
        );
    }

    /**
     * 处理客户端注册操作
     */
    public function handle_client_registration() {
        global $wpdb;
        // 检查是否提交了客户端注册表单
        if (isset($_POST['register_client'])) {
            // 获取表单提交的数据
            $redirect_uri = sanitize_text_field($_POST['redirect_uri']);
            $scopes = implode(',', array_map('sanitize_text_field', $_POST['scopes']));

            // 生成客户端 ID 和客户端密钥
            $client_id = wp_generate_password(20, false);
            $client_secret = wp_generate_password(40, false);

            // 定义 SSO 客户端存储表的表名
            $table_name = $wpdb->prefix . 'adc_sso_clients';

            // 将客户端信息插入到数据库表中
            $wpdb->insert(
                $table_name,
                array(
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri' => $redirect_uri,
                    'scopes' => $scopes
                )
            );
        }
    }

    /**
     * 显示 SSO 客户端管理页面的内容
     */
    public function sso_clients_page_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adc_sso_clients';
        $clients = $wpdb->get_results("SELECT * FROM $table_name");
        ?>
        <div class="wrap">
            <h1>SSO Clients Management</h1>

            <h2>Registered Clients</h2>
            <?php if (!empty($clients)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Redirect URI</th>
                        <th>Scopes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clients as $client) : ?>
                        <tr>
                            <td><?php echo esc_html($client->client_id); ?></td>
                            <td><?php echo esc_html($client->redirect_uri); ?></td>
                            <td><?php echo esc_html($client->scopes); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No registered clients yet.</p>
            <?php endif; ?>

            <h2>Register New Client</h2>
            <form method="post">
                <label for="redirect_uri">Redirect URI:</label>
                <input type="text" name="redirect_uri" id="redirect_uri" required>
                <br>
                <label for="scopes">Scopes:</label>
                <select multiple name="scopes[]">
                    <option value="openid">openid</option>
                    <option value="profile">profile</option>
                    <option value="email">email</option>
                    <option value="ad_attrs">ad_attrs</option>
                </select>
                <br>
                <input type="submit" name="register_client" value="Register Client">
            </form>
        </div>
        <?php
    }
}

// 创建 ADC_SSO_Clients 类的实例，初始化 SSO 客户端管理功能
new ADC_SSO_Clients();