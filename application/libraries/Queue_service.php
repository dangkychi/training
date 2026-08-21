<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Require Pheanstalk v3 autoloader
if (!class_exists('Pheanstalk\Pheanstalk')) {
    $pheanstalk_path = FCPATH . 'Training_Samples/beanstalk_samples/pheanstalk3/autoload.php';
    if (file_exists($pheanstalk_path)) {
        require_once $pheanstalk_path;
    }
}

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;

class Queue_service
{
    private $pheanstalk;
    private $tube = 'email_queue';
    private $host = '127.0.0.1';
    private $port = 11300;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('pheanstalk', TRUE, TRUE);
        $cfg = $CI->config->item('wff_pheanstalk');

        if (!empty($cfg['hostname'])) {
            $this->host = $cfg['hostname'];
        }
        if (!empty($cfg['port'])) {
            $this->port = (int)$cfg['port'];
        }

        try {
            $this->pheanstalk = new Pheanstalk($this->host, $this->port);
        } catch (Exception $e) {
            log_message('error', 'Queue_service init error: ' . $e->getMessage());
        }
    }

    /**
     * Push email job to Beanstalkd queue
     * @param string $type ('activation' | 'download')
     * @param int $registration_id
     * @return bool|int Job ID or false
     */
    public function push_email_job($type, $registration_id)
    {
        if (!$this->pheanstalk) {
            return false;
        }

        try {
            $payload = json_encode(array(
                'type' => $type,
                'registration_id' => $registration_id,
                'created_at' => date('Y-m-d H:i:s')
            ));

            $job_id = $this->pheanstalk
                ->useTube($this->tube)
                ->put($payload, PheanstalkInterface::DEFAULT_PRIORITY, PheanstalkInterface::DEFAULT_DELAY, 60);

            log_message('error', "Queue_service: Pushed job #{$job_id} [{$type}] for registration ID {$registration_id}");
            return $job_id;
        } catch (Exception $e) {
            log_message('error', 'Queue_service push_email_job error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reserve next job from email_queue
     * @param int $timeout Timeout in seconds
     * @return \Pheanstalk\Job|false
     */
    public function reserve_job($timeout = 5)
    {
        if (!$this->pheanstalk) {
            return false;
        }

        try {
            return $this->pheanstalk
                ->watch($this->tube)
                ->ignore('default')
                ->reserve($timeout);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete job from queue after successful execution
     */
    public function delete_job($job)
    {
        if ($this->pheanstalk && $job) {
            try {
                $this->pheanstalk->delete($job);
            } catch (Exception $e) {
                log_message('error', 'Queue_service delete_job error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Bury job if it fails permanently
     */
    public function bury_job($job)
    {
        if ($this->pheanstalk && $job) {
            try {
                $this->pheanstalk->bury($job);
            } catch (Exception $e) {
                log_message('error', 'Queue_service bury_job error: ' . $e->getMessage());
            }
        }
    }
}
