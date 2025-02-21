<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

class ADC_Settings_Page {
    public function __construct() {
        // 当 WordPress 加载管理菜单时，调用 add_settings_page 方法添加设置页面
        add_action('admin_menu', array($this, 'add_settings_page'));
        // 当 WordPress 初始化管理设置时，调用 register_settings 方法注册设置项
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * 添加设置页面到 WordPress 管理菜单
     */
    public function add_settings_page() {
        add_submenu_page(
            'adc-admin', // 父菜单的唯一标识符
            'AD Connect Settings', // 页面标题
            'Settings', // 菜单标题
            'manage_options', // 访问该页面所需的权限
            'adc-settings', // 页面的唯一标识符
            array($this, 'settings_page_content') // 用于显示页面内容的回调函数
        );
    }

    /**
     * 注册插件的设置项
     */
    public function register_settings() {
        // 注册设置组 'adc-settings-group'，该组包含多个设置项
        register_setting('adc-settings-group', 'adc_ldap_host');
        register_setting('adc-settings-group', 'adc_default_role');
        register_setting('adc-settings-group', 'adc_mail_service');
    }

    /**
     * 显示设置页面的内容
     */
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1>AD Connect Settings</h1>
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
                                foreach ($roles as $role_name => $role_info) {
                                    // 为每个角色生成一个选项，并标记当前选中的角色
                                    $selected = selected($role_name, get_option('adc_default_role'), false);
                                    echo '<option value="' . esc_attr($role_name) . '" ' . $selected . '>' . esc_html(
                                            $role_info['name']
                                        ) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Mail Service</th>
                        <td>
                            <select name="adc_mail_service">
                                <!-- 邮件服务选项：WordPress 默认、阿里云、腾讯云 -->
                                <option value="wp" <?php selected('wp', get_option('adc_mail_service')); ?>>WordPress Default</option>
                                <option value="aliyun" <?php selected('aliyun', get_option('adc_mail_service')); ?>>Aliyun</option>
                                <option value="tencent" <?php selected('tencent', get_option('adc_mail_service')); ?>>Tencent</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php
                // 显示提交按钮
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// 创建 ADC_Settings_Page 类的实例，初始化设置页面功能
new ADC_Settings_Page();