<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Registrations extends WFF_Controller
{

    private function style_scripts()
    {
        enqueue_styles(array(
            base_url("private/kendo/styles/kendo.default-v2.min.css")
        ));

        enqueue_scripts_footer(array(
            base_url("private/kendo/js/kendo.all.min.js"),
            base_url("public/vendors/js/extensions/sweetalert2.all.min.js")
        ));
    }

    /**
     * Trang quản lý danh sách đăng ký
     */
    public function index()
    {
        $this->style_scripts();
        $this->data['contents'] = "admin/registrations";
        $this->smarty->layouts($this->data);
    }

    /**
     * API Lấy danh sách registrations (Hỗ trợ Kendo UI Grid + Standard AJAX)
     */
    public function readdb()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            header('Content-Type: application/json');

            $raw = file_get_contents('php://input');
            $request = json_decode($raw, true);

            $filters = array();
            $limit = 10;
            $offset = 0;

            if ($request && (isset($request['take']) || isset($request['pageSize']))) {
                // Kendo UI Grid format
                $limit = isset($request['take']) ? (int)$request['take'] : (isset($request['pageSize']) ? (int)$request['pageSize'] : 10);
                $offset = isset($request['skip']) ? (int)$request['skip'] : 0;

                if (!empty($request['filter']['filters'])) {
                    foreach ($request['filter']['filters'] as $f) {
                        if (isset($f['field']) && isset($f['value'])) {
                            $field = $f['field'];
                            $val = $f['value'];
                            if ($val !== '' && $val !== null) {
                                $filters[$field] = (int)$val;
                            }
                        }
                    }
                }
            } else {
                // Standard POST format
                $limit = $this->input->post('limit') ? (int)$this->input->post('limit') : 10;
                $page = $this->input->post('page') ? (int)$this->input->post('page') : 1;
                $offset = ($page - 1) * $limit;

                if ($this->input->post('is_verified') !== null && $this->input->post('is_verified') !== '') {
                    $filters['is_verified'] = (int)$this->input->post('is_verified');
                }
                if ($this->input->post('is_email_read') !== null && $this->input->post('is_email_read') !== '') {
                    $filters['is_email_read'] = (int)$this->input->post('is_email_read');
                }
                if ($this->input->post('is_downloaded') !== null && $this->input->post('is_downloaded') !== '') {
                    $filters['is_downloaded'] = (int)$this->input->post('is_downloaded');
                }
            }

            $this->load->model('registration_model');
            $result = $this->registration_model->get_registrations_list($filters, $limit, $offset);

            echo json_encode(array(
                'data' => $result['data'],
                'total' => (int)$result['total']
            ));
        }
    }

    /**
     * Resend email kích hoạt
     */
    public function resend_activation()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            header('Content-Type: application/json');

            $id = $this->input->post('id');
            if (empty($id)) {
                echo json_encode(array('success' => false, 'message' => 'Thiếu ID.'));
                return;
            }

            $this->load->model('registration_model');
            $registration = $this->registration_model->get_by_id($id);

            if (!$registration) {
                echo json_encode(array('success' => false, 'message' => 'Không tìm thấy bản ghi.'));
                return;
            }

            if ($registration['is_verified'] == 1) {
                echo json_encode(array('success' => false, 'message' => 'Email đã được xác minh.'));
                return;
            }

            // Reset token mới và đẩy job gửi lại email kích hoạt vào Queue
            $this->registration_model->reset_activation_token($id);
            
            $this->load->library('queue_service');
            $this->queue_service->push_email_job('activation', $id);

            echo json_encode(array(
                'success' => true,
                'message' => 'Yêu cầu gửi lại email kích hoạt thành công.'
            ));
        }
    }

    /**
     * Resend email download tài liệu
     */
    public function resend_download()
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            header('Content-Type: application/json');

            $id = $this->input->post('id');
            if (empty($id)) {
                echo json_encode(array('success' => false, 'message' => 'Thiếu ID.'));
                return;
            }

            $this->load->model('registration_model');
            $registration = $this->registration_model->get_by_id($id);

            if (!$registration) {
                echo json_encode(array('success' => false, 'message' => 'Không tìm thấy bản ghi.'));
                return;
            }

            if ($registration['is_verified'] != 1) {
                echo json_encode(array('success' => false, 'message' => 'Email chưa được xác minh.'));
                return;
            }

            // Đẩy job gửi lại email download vào Queue
            $this->load->library('queue_service');
            $this->queue_service->push_email_job('download', $id);

            echo json_encode(array(
                'success' => true,
                'message' => 'Yêu cầu gửi lại email download thành công.'
            ));
        }
    }
}
