<?php
/*
Plugin Name: WP·AD·SSO互联
Description: Integrate Active Directory with WordPress and provide SSO services.
Version: 2.0
Author: 梁北棠 <contact@liangbeitang.com>
Author URI: https://www.liangbeitang.com
*/

if (!defined('ABSPATH')) {
    exit;
}

// 定义常量
define('ADC_KEY_DIR', plugin_dir_path(__FILE__) . 'keys');
define('ADC_ADMIN_DIR', plugin_dir_path(__FILE__) . 'admin/');
define('ADC_INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes/');

class WP_AD_Connect {
    private $modules = [
        'core' => [
            'AD_Operator'       => 'class-ad-operator.php',
            'User_Handler'      => 'class-user-handler.php',
            'SSO_Server'        => 'class-sso-server.php',
            'OAuth_Tokens'      => 'class-oauth-tokens.php',
            'Mail_Service'      => 'class-mail-service.php',
            'SSO_Endpoints'     => 'sso-endpoints.php',
            'AD_SSO_Functions'  => 'ad-sso-functions.php'
        ],
        'public' => [
            'User_Profile'      => 'user-profile.php',
            'SSO_Consent_Form'  => 'sso-consent-form.php'
        ]
    ];

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        $this->create_key_directory();
    }

    private function create_tables() {
        global $wpdb;
        
        $tables = [
            'sso_clients' => "CREATE TABLE {$wpdb->prefix}adc_sso_clients (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                client_id varchar(255) NOT NULL,
                client_secret varchar(255) NOT NULL,
                redirect_uri text NOT NULL,
                scopes text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) {$wpdb->get_charset_collate()};"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    private function set_default_options() {
        $defaults = [
            'adc_ldap_host'      => 'dc.example.com',
            'adc_default_role'   => 'contributor',
            'adc_mail_service'   => 'wp'
        ];

        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
    }

    private function create_key_directory() {
        if (!file_exists(ADC_KEY_DIR)) {
            wp_mkdir_p(ADC_KEY_DIR);
            file_put_contents(ADC_KEY_DIR . '.htaccess', 'Deny from all');
        }
    }

    public function init() {
        $this->load_modules();
        $this->init_admin();
        $this->init_public();
    }

    private function load_modules() {
        foreach ($this->modules['core'] as $class => $file) {
            $path = ADC_INCLUDES_DIR . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function init_admin() {
        if (is_admin()) {
            $admin_files = [
                'Admin_Interface' => 'admin-interface.php'
            ];

            foreach ($admin_files as $class => $file) {
                $path = ADC_ADMIN_DIR . $file;
                if (file_exists($path)) {
                    require_once $path;
                }
            }

            new ADC_Admin_Interface();
        }
    }

    private function init_public() {
        foreach ($this->modules['public'] as $class => $file) {
            $path = plugin_dir_path(__FILE__) . 'public/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
}

new WP_AD_Connect();