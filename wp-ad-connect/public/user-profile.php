<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 在用户资料编辑页面显示额外的 AD 信息
add_action('show_user_profile', 'adc_show_user_ad_info');
add_action('edit_user_profile', 'adc_show_user_ad_info');

/**
 * 在用户资料页面显示 AD 相关信息
 *
 * @param WP_User $user 当前编辑的用户对象
 */
function adc_show_user_ad_info($user) {
    // 从用户元数据中获取 AD 的 GUID
    $ad_guid = get_user_meta($user->ID, 'ad_guid', true);
    // 从用户元数据中获取 AD 的其他属性
    $ad_attributes = get_user_meta($user->ID, 'ad_attributes', true);

    // 显示 AD 信息的标题
    ?>
    <h3>AD Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="ad_guid">AD GUID</label></th>
            <td>
                <!-- 显示 AD GUID，使用 disabled 属性使其不可编辑 -->
                <input type="text" name="ad_guid" id="ad_guid" value="<?php echo esc_attr($ad_guid); ?>"
                       class="regular-text" disabled>
            </td>
        </tr>
        <?php
        // 如果存在 AD 属性，则遍历显示每个属性
        if ($ad_attributes) {
            foreach ($ad_attributes as $key => $value) {
                ?>
                <tr>
                    <th><label for="ad_<?php echo esc_attr($key); ?>"><?php echo esc_html(ucfirst($key)); ?></label></th>
                    <td>
                        <!-- 显示 AD 属性，使用 disabled 属性使其不可编辑 -->
                        <input type="text" name="ad_<?php echo esc_attr($key); ?>"
                               id="ad_<?php echo esc_attr($key); ?>"
                               value="<?php echo esc_attr($value); ?>" class="regular-text" disabled>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </table>
    <?php
}

// 当用户资料更新时，可用于保存额外信息（目前无实际保存逻辑，仅作扩展预留）
add_action('personal_options_update', 'adc_save_user_ad_info');
add_action('edit_user_profile_update', 'adc_save_user_ad_info');

/**
 * 保存用户 AD 相关信息（目前为空，可根据需求扩展）
 *
 * @param int $user_id 当前更新的用户 ID
 */
function adc_save_user_ad_info($user_id) {
    // 此处可添加保存 AD 信息的逻辑，例如处理用户可能修改的 AD 相关信息
    // 目前由于显示信息为不可编辑状态，所以暂不添加保存逻辑
}