<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 使用 LDAP 连接到 Active Directory 并验证用户凭据
 *
 * @param string $username 用户的 AD 用户名
 * @param string $password 用户的 AD 密码
 * @return bool 如果验证成功返回 true，否则返回 false
 */
function adc_validate_user_credentials($username, $password) {
    $ldap_host = get_option('adc_ldap_host');
    $ldap_dn = get_option('adc_ldap_dn');

    $ldap_connection = ldap_connect($ldap_host);
    if ($ldap_connection) {
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

        $user_dn = "uid={$username},{$ldap_dn}";
        if (@ldap_bind($ldap_connection, $user_dn, $password)) {
            ldap_close($ldap_connection);
            return true;
        }
        ldap_close($ldap_connection);
    }
    return false;
}

/**
 * 从 Active Directory 获取用户属性
 *
 * @param string $username 用户的 AD 用户名
 * @param array $attributes 需要获取的属性列表
 * @return array|bool 如果成功获取属性则返回属性数组，否则返回 false
 */
function adc_get_user_attributes($username, $attributes = []) {
    $ldap_host = get_option('adc_ldap_host');
    $ldap_dn = get_option('adc_ldap_dn');
    $ldap_bind_user = get_option('adc_ldap_bind_user');
    $ldap_bind_password = get_option('adc_ldap_bind_password');

    $ldap_connection = ldap_connect($ldap_host);
    if ($ldap_connection) {
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

        if (@ldap_bind($ldap_connection, $ldap_bind_user, $ldap_bind_password)) {
            $filter = "(uid={$username})";
            $search_result = ldap_search($ldap_connection, $ldap_dn, $filter, $attributes);
            if ($search_result) {
                $entries = ldap_get_entries($ldap_connection, $search_result);
                if ($entries['count'] > 0) {
                    $user_attributes = [];
                    foreach ($attributes as $attr) {
                        if (isset($entries[0][strtolower($attr)][0])) {
                            $user_attributes[$attr] = $entries[0][strtolower($attr)][0];
                        }
                    }
                    ldap_close($ldap_connection);
                    return $user_attributes;
                }
            }
        }
        ldap_close($ldap_connection);
    }
    return false;
}

/**
 * 将 Active Directory 属性映射到 WordPress 用户属性
 *
 * @param array $ad_attributes 从 AD 获取的用户属性数组
 * @return array 映射后的 WordPress 用户属性数组
 */
function adc_map_ad_attributes_to_wp($ad_attributes) {
    $mapping = [
        'employeeId' => 'user_login',
        'mail' => 'user_email',
        'cn' => 'nickname'
    ];

    $wp_attributes = [];
    foreach ($mapping as $ad_attr => $wp_attr) {
        if (isset($ad_attributes[$ad_attr])) {
            $wp_attributes[$wp_attr] = $ad_attributes[$ad_attr];
        }
    }

    return $wp_attributes;
}