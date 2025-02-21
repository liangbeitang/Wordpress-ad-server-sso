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
        add_action('admin_menu', array($this, 'add_sso_admin_menu'), 11); // 调整优先级，确保在 add_ad_admin_menu 之后执行
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
}