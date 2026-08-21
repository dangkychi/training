<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Tracking extends WFF_Controller
{

    /**
     * Tracking pixel email open - trả về ảnh GIF 1x1 trong suốt
     * @param string $token download_token
     */
    public function open($token = '')
    {
        if (empty($token)) {
            show_404();
            return;
        }

        $this->load->model('registration_model');
        $this->load->model('email_tracking_model');

        $registration = $this->registration_model->get_by_download_token($token);

        // log_message('error', 'check submit: open');

        if ($registration) {
            // Log event open
            $this->email_tracking_model->log_open(
                $registration['id'],
                $this->input->ip_address(),
                $this->input->user_agent()
            );
        }

        // Trả về ảnh GIF 1x1 pixel trong suốt
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        // 1x1 transparent GIF binary
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    /**
     * Tracking download - log event + redirect/stream file
     * @param string $token download_token
     */
    public function download($token = '')
    {
        if (empty($token)) {
            show_404();
            return;
        }

        $this->load->model('registration_model');
        $this->load->model('email_tracking_model');

        $registration = $this->registration_model->get_by_download_token($token);

        if (!$registration) {
            show_404();
            return;
        }

        // Log event download
        $this->email_tracking_model->log_download(
            $registration['id'],
            $this->input->ip_address(),
            $this->input->user_agent()
        );

        // Fallback: Nếu pixel bị chặn, tự động ghi log open khi người dùng click download
        if (!$this->email_tracking_model->has_open_event($registration['id'])) {
            $this->email_tracking_model->log_open(
                $registration['id'],
                $this->input->ip_address(),
                $this->input->user_agent()
            );
        }

        // Stream file download
        $file_path = FCPATH . 'uploads/sample_document.pdf';

        if (!file_exists($file_path)) {
            // Fallback: show thông báo nếu chưa có file
            $this->data['download_message'] = 'Tài liệu đang được chuẩn bị. Vui lòng thử lại sau.';
            $this->data['contents'] = "download_status";
            $this->smarty->layouts($this->data);
            return;
        }

        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="TaiLieu.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}
