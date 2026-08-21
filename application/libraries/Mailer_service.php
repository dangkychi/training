<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mailer_service
 * 
 * Thư viện gửi email sử dụng PHPMailer
 * 
 * @author training
 */
class Mailer_service
{

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        // Load PHPMailer
        $phpmailer_path = FCPATH . 'Training_Samples/beanstalk_samples/phpmailer/PHPMailerAutoload.php';
        if (!class_exists('PHPMailer')) {
            require_once $phpmailer_path;
        }
    }

    /**
     * Tạo instance PHPMailer với cấu hình SMTP Gmail
     * @return PHPMailer
     */
    private function create_mailer()
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dangkychi@gmail.com';
        $mail->Password = 'feggxmssauyfwjjt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->setFrom('dangkychi@gmail.com', 'Alpha Training Center');
        return $mail;
    }

    /**
     * Gửi email kích hoạt (verification)
     * @param array $registration - row từ bảng registrations
     * @return array ['success' => bool, 'error' => string]
     */
    public function send_activation_email($registration)
    {
        try {
            $mail = $this->create_mailer();
            $mail->addAddress($registration['email'], $registration['fullname']);
            $mail->isHTML(true);

            $activation_url = base_url('register/activate/' . $registration['activation_token']);

            $mail->Subject = 'Xác minh email - Đăng ký nhận tài liệu';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #333;">Xin chào ' . htmlspecialchars($registration['fullname']) . ',</h2>
                    <p>Cảm ơn bạn đã đăng ký nhận tài liệu tại <strong>Alpha Training Center</strong>.</p>
                    <p>Để hoàn tất đăng ký, vui lòng nhấn vào nút bên dưới để xác minh email của bạn:</p>
                    <p style="text-align: center; margin: 30px 0;">
                        <a href="' . $activation_url . '" 
                           style="background-color: #4CAF50; color: white; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                           Xác minh Email
                        </a>
                    </p>
                    <p style="color: #666; font-size: 12px;">Nếu bạn không đăng ký, vui lòng bỏ qua email này.</p>
                    <hr style="border: none; border-top: 1px solid #eee;">
                    <p style="color: #999; font-size: 11px;">Alpha Training Center - Đào tạo AI/Machine Learning</p>
                </div>';

            $result = $mail->send();
            $mail->smtpClose();

            return array(
                'success' => $result,
                'error' => $result ? '' : $mail->ErrorInfo
            );
        } catch (Exception $e) {
            log_message('error', 'send_activation_email: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * Gửi email chứa link download tài liệu (có tracking pixel)
     * @param array $registration - row từ bảng registrations
     * @return array ['success' => bool, 'error' => string]
     */
    public function send_download_email($registration)
    {
        try {
            $mail = $this->create_mailer();
            $mail->addAddress($registration['email'], $registration['fullname']);
            $mail->isHTML(true);

            $download_url = base_url('tracking/download/' . $registration['download_token']);
            $tracking_pixel_url = base_url('tracking/open/' . $registration['download_token']);

            $mail->Subject = 'Tài liệu - Link Download';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #333;">Xin chào ' . htmlspecialchars($registration['fullname']) . ',</h2>
                    <p>Email của bạn đã được xác minh thành công!</p>
                    <p>Bạn có thể tải tài liệu <strong>"Ứng dụng trí tuệ nhân tạo AI và Machine Learning"</strong> tại link bên dưới:</p>
                    <p style="text-align: center; margin: 30px 0;">
                        <a href="' . $download_url . '" 
                           style="background-color: #2196F3; color: white; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                           Tải Tài Liệu
                        </a>
                    </p>
                    <p style="color: #666;">Chúc bạn học tập hiệu quả!</p>
                    <hr style="border: none; border-top: 1px solid #eee;">
                    <p style="color: #999; font-size: 11px;">Alpha Training Center - Đào tạo AI/Machine Learning</p>
                </div>
                <img src="' . $tracking_pixel_url . '" width="1" height="1" alt="" style="display:none;">';

            $result = $mail->send();
            $mail->smtpClose();

            return array(
                'success' => $result,
                'error' => $result ? '' : $mail->ErrorInfo
            );
        } catch (Exception $e) {
            log_message('error', 'send_download_email: ' . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
}
