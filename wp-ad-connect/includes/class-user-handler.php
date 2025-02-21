<?php
// 防止直接访问该文件，确保文件只能在 WordPress 环境中运行
if (!defined('ABSPATH')) {
    exit;
}

class ADC_User_Handler {
    // 存储当前处理的 AD 数据
    public $current_ad_data;

    /**
     * 根据 AD 数据创建 WordPress 用户
     *
     * @param array $ad_data 从 Active Directory 获取的用户数据
     * @return int|WP_Error 创建成功返回用户 ID，失败返回 WP_Error 对象
     */
    public function create_wp_user($ad_data) {
        // 将当前处理的 AD 数据存储到类属性中
        $this->current_ad_data = $ad_data;

        // 准备要插入的用户数据
        $user_data = [
            'user_login' => $ad_data['employeeId'],
            'user_pass'  => wp_generate_password(),
            'user_email' => $ad_data['mail'],
            'nickname'   => $ad_data['cn'],
            'role'       => $this->get_assigned_role()
        ];

        // 插入用户数据到 WordPress 数据库
        $user_id = wp_insert_user($user_data);

        // 检查用户是否创建成功
        if (!is_wp_error($user_id)) {
            // 存储 AD 的 GUID 作为用户元数据
            add_user_meta($user_id, 'ad_guid', $ad_data['objectGUID']);
            // 存储 AD 的其他属性作为用户元数据
            add_user_meta($user_id, 'ad_attributes', [
                'department'  => $ad_data['department'],
                'title'       => $ad_data['title'],
                'employeeType' => $ad_data['employeeType']
            ]);

            // 触发自定义钩子，允许其他插件在用户创建后执行操作
            do_action('adc_user_created', $user_id, $ad_data);
        }

        return $user_id;
    }

    /**
     * 获取要分配给用户的角色
     *
     * @return string 分配的用户角色名称
     */
    private function get_assigned_role() {
        // 使用 apply_filters 允许其他插件过滤角色分配
        return apply_filters('adc_user_role', 
            get_option('adc_default_role'), 
            $this->current_ad_data
        );
    }
}