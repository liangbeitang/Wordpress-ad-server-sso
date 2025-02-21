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
    public function __construct() {
        // 修改菜单名称并确保只添加一个菜单
        add_action('admin_menu', array($this, 'add_admin_menu'), 11); // 调整优先级，确保在 add_ad_admin_menu 之后执行
        add_action('admin_menu', array($this, 'add_sso_admin_menu'), 11); // 调整优先级，确保在 add_ad_admin_menu 之后执行
    }

    public function add_admin_menu() {
        global $menu;
        // 检查菜单是否已经存在
        $menu_exists = false;
        foreach ($menu as $item) {
            if ($item[2] === 'adc-admin') {
                $menu_exists = true;
                break;
            }
        }
        if (!$menu_exists) {
            add_menu_page(
                'LDAP 配置', // 页面标题
                'LDAP 配置', // 菜单标题
                'manage_options', // 访问该页面所需的权限
                'adc-admin', // 页面的唯一标识符
                array($this, 'admin_page_content') // 用于显示页面内容的回调函数
            );
        }
    }

    public function add_sso_admin_menu() {
        global $menu;
        // 检查菜单是否已经存在
        $menu_exists = false;
        foreach ($menu as $item) {
            if ($item[2] === 'adc-sso-admin') {
                $menu_exists = true;
                break;
            }
        }
        if (!$menu_exists) {
            add_menu_page(
                'SSO Connect Admin', // 页面标题
                'SSO Connect', // 菜单标题
                'manage_options', // 访问该页面所需的权限
                'adc-sso-admin', // 页面的唯一标识符
                array($this, 'sso_admin_page_content') // 用于显示页面内容的回调函数
            );
        }
    }

    public function admin_page_content() {
        ?>
        <div class="wrap">
            <h1>AD Connect Admin Interface</h1>
            <p>Welcome to the AD Connect admin interface. Here you can manage various settings related to Active Directory integration and Single Sign - On (SSO) services.</p>

            <h2>General Settings</h2>
            <form method="post" action="options.php">
                <?php
                // 输出设置表单所需的隐藏字段和安全验证信息
                settings_fields('adc-settings-group');
                // 显示设置组内各设置项的默认设置区域
                do_settings_sections('adc-settings-group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">AD Server Host</th>
                        <td>
                            <!-- 输入框用于设置 AD 服务器的主机地址 -->
                            <input type="text" name="adc_ldap_host"
                                   value="<?php echo esc_attr(get_option('adc_ldap_host')); ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Default User Role</th>
                        <td>
                            <select name="adc_default_role">
                                <?php
                                // 获取可编辑的用户角色列表
                                $roles = get_editable_roles();
                                foreach ($roles as $role => $details) {
                                    $selected = (get_option('adc_default_role') == $role) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($role) . '" ' . $selected . '>' . esc_html($details['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * SSO 授权管理页面内容
     */
    public function sso_admin_page_content() {
        ?>
        <div class="wrap">
            <h1>SSO Authorization Management</h1>
            <p>Here you can manage SSO authorization settings.</p>
            <!-- 这里可以添加具体的 SSO 授权管理表单 -->
            <form method="post" action="options.php">
                <?php
                // 输出设置表单所需的隐藏字段和安全验证信息
                settings_fields('adc-sso-settings-group');
                // 显示设置组内各设置项的默认设置区域
                do_settings_sections('adc-sso-settings-group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">SSO Server Host</th>
                        <td>
                            <input type="text" name="adc_sso_host"
                                   value="<?php echo esc_attr(get_option('adc_sso_host')); ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">SSO Client ID</th>
                        <td>
                            <input type="text" name="adc_sso_client_id"
                                   value="<?php echo esc_attr(get_option('adc_sso_client_id')); ?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">SSO Client Secret</th>
                        <td>
                            <input type="text" name="adc_sso_client_secret"
                                   value="<?php echo esc_attr(get_option('adc_sso_client_secret')); ?>"/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// 创建 ADC_Admin_Interface 类的实例，初始化管理界面功能
new ADC_Admin_Interface();