<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends WFF_Controller
{

    private function style_scripts()
    {
        enqueue_scripts_footer(array(
            base_url("public/vendors/js/extensions/sweetalert2.all.min.js")
        ));
    }

    /**
     * Hiển thị form đăng ký
     */
    public function index()
    {
        $this->style_scripts();
        $this->data['contents'] = "register";
        $this->smarty->layouts($this->data);
    }

    /**
     * Xử lý form submit đăng ký
     */
    public function submit()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            header('Content-Type: application/json');

            // log_message('error', 'check submit: ' . json_encode($this->input->post(), JSON_UNESCAPED_UNICODE));

            // Validate input
            $email = trim($this->input->post('email', TRUE));
            $fullname = trim($this->input->post('fullname', TRUE));
            $gender = $this->input->post('gender', TRUE);
            $birthday = $this->input->post('birthday', TRUE);
            $occupation = trim($this->input->post('occupation', TRUE));
            $address = trim($this->input->post('address', TRUE));
            $latitude = $this->input->post('latitude', TRUE);
            $longitude = $this->input->post('longitude', TRUE);

            // Validation
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(array('success' => false, 'message' => 'Email không hợp lệ.'));
                return;
            }
            if (empty($fullname)) {
                echo json_encode(array('success' => false, 'message' => 'Vui lòng nhập họ tên.'));
                return;
            }
            if (empty($gender)) {
                echo json_encode(array('success' => false, 'message' => 'Vui lòng chọn giới tính.'));
                return;
            }
            if (empty($birthday)) {
                echo json_encode(array('success' => false, 'message' => 'Vui lòng nhập ngày sinh.'));
                return;
            }
            if (empty($occupation)) {
                echo json_encode(array('success' => false, 'message' => 'Vui lòng nhập nghề nghiệp.'));
                return;
            }

            $this->load->model('registration_model');

            // Kiểm tra email đã tồn tại chưa
            $existing = $this->registration_model->get_by_email($email);
            if ($existing) {
                echo json_encode(array('success' => false, 'message' => 'Email này đã được đăng ký trước đó.'));
                return;
            }

            // Insert bản ghi
            $data = array(
                'email' => $email,
                'fullname' => $fullname,
                'gender' => $gender,
                'birthday' => $birthday,
                'occupation' => $occupation,
                'address' => $address,
                'latitude' => !empty($latitude) ? $latitude : null,
                'longitude' => !empty($longitude) ? $longitude : null
            );

            $insert_id = $this->registration_model->create_registration($data);

            if (!$insert_id) {
                echo json_encode(array('success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.'));
                return;
            }

            // Lấy bản ghi vừa tạo (có token)
            $registration = $this->registration_model->get_by_id($insert_id);

            // Đẩy công việc gửi email kích hoạt vào Beanstalkd Queue (xử lý bất đồng bộ)
            $this->load->library('queue_service');
            $this->queue_service->push_email_job('activation', $insert_id);

            echo json_encode(array(
                'success' => true,
                'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt.'
            ));
        }
    }

    /**
     * Xử lý khi user click link kích hoạt từ email
     * @param string $token activation_token
     */
    public function activate($token = '')
    {
        if (empty($token)) {
            show_404();
            return;
        }

        $this->load->model('registration_model');

        // Kiểm tra token
        $registration = $this->registration_model->get_by_activation_token($token);

        if (!$registration) {
            $this->data['activate_status'] = 'invalid';
            $this->data['activate_message'] = 'Link kích hoạt không hợp lệ hoặc đã hết hạn.';
        } elseif ($registration['is_verified'] == 1) {
            $this->data['activate_status'] = 'already';
            $this->data['activate_message'] = 'Email của bạn đã được kích hoạt trước đó.';
        } else {
            // Kích hoạt
            $activated = $this->registration_model->activate($token);

            if ($activated) {
                // Đẩy công việc gửi email download vào Beanstalkd Queue (xử lý bất đồng bộ)
                $this->load->library('queue_service');
                $this->queue_service->push_email_job('download', $registration['id']);

                $this->data['activate_status'] = 'success';
                $this->data['activate_message'] = 'Xác minh email thành công! Link tải tài liệu đang được gửi tới email của bạn trong giây lát.';
            } else {
                $this->data['activate_status'] = 'error';
                $this->data['activate_message'] = 'Có lỗi xảy ra trong quá trình kích hoạt. Vui lòng thử lại.';
            }
        }

        $this->data['contents'] = "activate";
        $this->smarty->layouts($this->data);
    }
}
