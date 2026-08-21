<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Worker extends WFF_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Cho phép chạy từ CLI hoặc HTTP GET
        $this->load->library('queue_service');
        $this->load->library('mailer_service');
        $this->load->model('registration_model');
    }

    /**
     * CLI Worker Process - Lắng nghe tube `email_queue` trên Beanstalkd
     * Đơn giản: php index.php cli/worker/process
     */
    public function process($max_jobs = 0)
    {
        echo "[" . date('Y-m-d H:i:s') . "] Email Worker started listening on tube 'email_queue'...\n";

        $processed = 0;

        while (true) {
            // Reserve job với timeout 5 giây
            $job = $this->queue_service->reserve_job(5);

            if (!$job) {
                // Không có job trong queue, tiếp tục chờ
                if ($max_jobs > 0 && $processed >= $max_jobs) {
                    echo "[" . date('Y-m-d H:i:s') . "] Reached max jobs limit ({$max_jobs}). Worker exiting.\n";
                    break;
                }
                continue;
            }

            $job_id = $job->getId();
            $data = json_decode($job->getData(), true);

            echo "[" . date('Y-m-d H:i:s') . "] Received Job #{$job_id}: " . json_encode($data) . "\n";

            if (empty($data['type']) || empty($data['registration_id'])) {
                echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} invalid payload. Burying job.\n";
                $this->queue_service->bury_job($job);
                continue;
            }

            $type = $data['type'];
            $registration_id = $data['registration_id'];
            $registration = $this->registration_model->get_by_id($registration_id);

            if (!$registration) {
                echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} registration ID {$registration_id} not found. Deleting job.\n";
                $this->queue_service->delete_job($job);
                continue;
            }

            $success = false;

            if ($type === 'activation') {
                $result = $this->mailer_service->send_activation_email($registration);
                $success = $result['success'];
                if (!$success) {
                    echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} send_activation_email failed: {$result['error']}\n";
                }
            } else if ($type === 'download') {
                $result = $this->mailer_service->send_download_email($registration);
                $success = $result['success'];
                if ($success) {
                    $this->registration_model->mark_download_sent($registration_id);
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} send_download_email failed: {$result['error']}\n";
                }
            }

            if ($success) {
                echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} executed successfully. Deleting job.\n";
                $this->queue_service->delete_job($job);
                $processed++;
            } else {
                echo "[" . date('Y-m-d H:i:s') . "] Job #{$job_id} failed. Burying job for review.\n";
                $this->queue_service->bury_job($job);
            }

            if ($max_jobs > 0 && $processed >= $max_jobs) {
                echo "[" . date('Y-m-d H:i:s') . "] Reached max jobs limit ({$max_jobs}). Worker exiting.\n";
                break;
            }
        }
    }
}
