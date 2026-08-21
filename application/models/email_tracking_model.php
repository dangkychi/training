<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email_tracking_model
 * 
 * Model quản lý bảng email_tracking (theo dõi mở email / download)
 * 
 * @author training
 */
class Email_tracking_model extends WFF_Model
{

    private $table = 'email_tracking';

    /**
     * Ghi log event mở email (pixel tracking)
     * @param int $registration_id
     * @param string $ip
     * @param string $user_agent
     * @return bool
     */
    public function log_open($registration_id, $ip = '', $user_agent = '')
    {
        try {
            $this->db->insert($this->table, array(
                'registration_id' => $registration_id,
                'event_type' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $ip,
                'user_agent' => $user_agent
            ));
            return $this->db->insert_id() > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Ghi log event download tài liệu
     * @param int $registration_id
     * @param string $ip
     * @param string $user_agent
     * @return bool
     */
    public function log_download($registration_id, $ip = '', $user_agent = '')
    {
        try {
            $this->db->insert($this->table, array(
                'registration_id' => $registration_id,
                'event_type' => 'download',
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $ip,
                'user_agent' => $user_agent
            ));
            return $this->db->insert_id() > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra đã có event open chưa (chỉ log lần đầu nếu muốn)
     * @param int $registration_id
     * @return bool
     */
    public function has_open_event($registration_id)
    {
        try {
            $this->db->where('registration_id', $registration_id);
            $this->db->where('event_type', 'open');
            return $this->db->count_all_results($this->table) > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra đã có event download chưa
     * @param int $registration_id
     * @return bool
     */
    public function has_download_event($registration_id)
    {
        try {
            $this->db->where('registration_id', $registration_id);
            $this->db->where('event_type', 'download');
            return $this->db->count_all_results($this->table) > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }
}
