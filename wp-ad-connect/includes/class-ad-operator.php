<?php
// 防止直接访问该文件，确保文件只能在 WordPress 环境中运行
if (!defined('ABSPATH')) {
    exit;
}

class ADC_AD_Operator {
    private $ldap_connection;

    /**
     * 构造函数，初始化 LDAP 连接
     */
    public function __construct() {
        // 从 WordPress 选项中获取 LDAP 主机地址
        $ldap_host = get_option('adc_ldap_host');
        // 尝试建立 LDAP 连接
        $this->ldap_connection = ldap_connect($ldap_host);
        if ($this->ldap_connection) {
            // 设置 LDAP 协议版本为 3
            ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            // 禁用引用，避免在搜索时跟随引用
            ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
        }
    }

    /**
     * 执行 LDAP 绑定操作，用于验证用户身份
     *
     * @param string $username 要绑定的用户名
     * @param string $password 对应的密码
     * @return bool 绑定成功返回 true，失败返回 false
     */
    public function bind($username, $password) {
        return ldap_bind($this->ldap_connection, $username, $password);
    }

    /**
     * 在 AD 中执行搜索操作
     *
     * @param string $base_dn 搜索的基础 DN（Distinguished Name）
     * @param string $filter 搜索过滤器，用于指定搜索条件
     * @param array $attributes 可选参数，指定要返回的属性列表
     * @return array|bool 搜索成功返回结果数组，失败返回 false
     */
    public function search($base_dn, $filter, $attributes = []) {
        // 执行 LDAP 搜索操作
        $search_result = ldap_search($this->ldap_connection, $base_dn, $filter, $attributes);
        if ($search_result) {
            // 获取搜索结果的条目
            return ldap_get_entries($this->ldap_connection, $search_result);
        }
        return false;
    }

    /**
     * 关闭 LDAP 连接
     */
    public function close() {
        ldap_close($this->ldap_connection);
    }
}