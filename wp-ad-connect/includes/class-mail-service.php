<?php
// 防止直接访问该文件，确保在 WordPress 环境下运行
if (!defined('ABSPATH')) {
    exit;
}

// 将阿里云 SDK 相关的 use 语句移到文件开头
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

// 将腾讯云 SDK 相关的 use 语句移到文件开头
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Ses\V20201002\SesClient;
use TencentCloud\Ses\V20201002\Models\SendEmailRequest;

class ADC_Mail_Service {

    /**
     * 发送邮件的方法
     *
     * @param string $to 收件人的邮箱地址
     * @param string $subject 邮件的主题
     * @param string $message 邮件的内容
     * @return bool 发送成功返回 true，失败返回 false
     */
    public function send_email($to, $subject, $message) {
        // 从 WordPress 选项中获取配置的邮件服务
        $mail_service = get_option('adc_mail_service');

        switch ($mail_service) {
            case 'aliyun':
                // 调用阿里云邮件服务的发送逻辑
                return $this->send_email_aliyun($to, $subject, $message);
            case 'tencent':
                // 调用腾讯云邮件服务的发送逻辑
                return $this->send_email_tencent($to, $subject, $message);
            default:
                // 默认使用 WordPress 自带的 wp_mail 函数发送邮件
                return wp_mail($to, $subject, $message);
        }
    }

    /**
     * 使用阿里云邮件服务发送邮件
     *
     * @param string $to 收件人的邮箱地址
     * @param string $subject 邮件的主题
     * @param string $message 邮件的内容
     * @return bool 发送成功返回 true，失败返回 false
     */
    private function send_email_aliyun($to, $subject, $message) {
        // 这里需要实现使用阿里云邮件服务发送邮件的具体逻辑
        // 以下是一个示例，需要替换为真实的阿里云 SDK 调用
        // 引入阿里云 SDK 相关文件
        require_once('aliyun-sdk/autoload.php');

        // 配置阿里云客户端
        AlibabaCloud::accessKeyClient('your-access-key-id', 'your-access-key-secret')
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dm')
                ->scheme('https')
                ->version('2015-11-23')
                ->action('SingleSendMail')
                ->method('POST')
                ->host('dm.aliyuncs.com')
                ->options([
                    'query' => [
                        'AccountName' => 'your-account-name',
                        'FromAlias' => 'your-from-alias',
                        'AddressType' => 1,
                        'ReplyToAddress' => 'true',
                        'ToAddress' => $to,
                        'Subject' => $subject,
                        'HtmlBody' => $message
                    ]
                ])
                ->request();
            return true;
        } catch (ClientException $e) {
            error_log('阿里云邮件发送失败：'. $e->getErrorMessage());
            return false;
        } catch (ServerException $e) {
            error_log('阿里云邮件发送失败：'. $e->getErrorMessage());
            return false;
        }
    }

    /**
     * 使用腾讯云邮件服务发送邮件
     *
     * @param string $to 收件人的邮箱地址
     * @param string $subject 邮件的主题
     * @param string $message 邮件的内容
     * @return bool 发送成功返回 true，失败返回 false
     */
    private function send_email_tencent($to, $subject, $message) {
        // 这里需要实现使用腾讯云邮件服务发送邮件的具体逻辑
        // 以下是一个示例，需要替换为真实的腾讯云 SDK 调用
        // 引入腾讯云 SDK 相关文件
        require_once('tencentcloud-sdk/autoload.php');

        $cred = new Credential("your-secret-id", "your-secret-key");
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("ses.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new SesClient($cred, "ap-guangzhou", $clientProfile);

        $req = new SendEmailRequest();
        $params = [
            "FromEmailAddress" => "your-sender-email",
            "Destination" => [$to],
            "Subject" => $subject,
            "HtmlContent" => $message
        ];
        $req->fromJsonString(json_encode($params));

        try {
            $resp = $client->SendEmail($req);
            return true;
        } catch (Exception $e) {
            error_log('腾讯云邮件发送失败：'. $e->getMessage());
            return false;
        }
    }
}