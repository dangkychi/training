<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Registration_model
 * 
 * Model quản lý bảng registrations (đăng ký nhận tài liệu)
 * 
 * @author training
 */
class Registration_model extends WFF_Model
{

    private $table = 'registrations';

    /**
     * Tạo bản ghi đăng ký mới
     * @param array $data
     * @return int|false
     */
    public function create_registration($data)
    {
        try {
            $data['activation_token'] = bin2hex(random_bytes(32));
            $data['download_token'] = bin2hex(random_bytes(32));
            $data['created_at'] = date('Y-m-d H:i:s');

            $this->db->insert($this->table, $data);
            return $this->db->insert_id();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Lấy bản ghi theo ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        try {
            $query = $this->db->get_where($this->table, array('id' => $id));
            return $query->row_array();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return null;
        }
    }

    /**
     * Lấy bản ghi theo email
     * @param string $email
     * @return array|null
     */
    public function get_by_email($email)
    {
        try {
            $query = $this->db->get_where($this->table, array('email' => $email));
            return $query->row_array();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return null;
        }
    }

    /**
     * Lấy bản ghi theo activation_token
     * @param string $token
     * @return array|null
     */
    public function get_by_activation_token($token)
    {
        try {
            $query = $this->db->get_where($this->table, array('activation_token' => $token));
            return $query->row_array();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return null;
        }
    }

    /**
     * Lấy bản ghi theo download_token
     * @param string $token
     * @return array|null
     */
    public function get_by_download_token($token)
    {
        try {
            $query = $this->db->get_where($this->table, array('download_token' => $token));
            return $query->row_array();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return null;
        }
    }

    /**
     * Kích hoạt (verify) email
     * @param string $token activation_token
     * @return bool
     */
    public function activate($token)
    {
        try {
            $this->db->where('activation_token', $token);
            $this->db->where('is_verified', 0);
            $this->db->update($this->table, array(
                'is_verified' => 1,
                'verified_at' => date('Y-m-d H:i:s')
            ));
            return $this->db->affected_rows() > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Đánh dấu đã gửi email download
     * @param int $id
     * @return bool
     */
    public function mark_download_sent($id)
    {
        try {
            $this->db->where('id', $id);
            $this->db->update($this->table, array(
                'is_download_sent' => 1,
                'download_sent_at' => date('Y-m-d H:i:s')
            ));
            return $this->db->affected_rows() > 0;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách registrations cho admin view
     * @param array $filters
     * @return array ['data' => [...], 'total' => int]
     */
    public function get_registrations_list($filters = array())
    {
        try {
            $this->db->select('r.*, 
                et_open.created_at as email_read_at, 
                et_dl.created_at as downloaded_at');
            $this->db->from($this->table . ' r');
            $this->db->join(
                'email_tracking et_open',
                "et_open.registration_id = r.id AND et_open.event_type = 'open' AND et_open.id = (SELECT MIN(id) FROM email_tracking WHERE registration_id = r.id AND event_type = 'open')",
                'left'
            );
            $this->db->join(
                'email_tracking et_dl',
                "et_dl.registration_id = r.id AND et_dl.event_type = 'download' AND et_dl.id = (SELECT MIN(id) FROM email_tracking WHERE registration_id = r.id AND event_type = 'download')",
                'left'
            );

            // Apply filters
            if (isset($filters['is_verified'])) {
                $this->db->where('r.is_verified', $filters['is_verified']);
            }
            if (isset($filters['is_email_read'])) {
                if ($filters['is_email_read'] == 1) {
                    $this->db->where('et_open.id IS NOT NULL');
                } else {
                    $this->db->where('et_open.id IS NULL');
                }
            }
            if (isset($filters['is_downloaded'])) {
                if ($filters['is_downloaded'] == 1) {
                    $this->db->where('et_dl.id IS NOT NULL');
                } else {
                    $this->db->where('et_dl.id IS NULL');
                }
            }

            // Total count (before limit)
            $count_query = clone $this->db;
            // For total, we need a separate query
            $total = $this->db->count_all_results('', false);

            // Pagination
            $limit = isset($filters['limit']) ? (int) $filters['limit'] : 20;
            $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;
            $this->db->limit($limit, $offset);

            // Sorting
            $sort_field = isset($filters['sort_field']) ? $filters['sort_field'] : 'r.created_at';
            $sort_dir = isset($filters['sort_dir']) ? $filters['sort_dir'] : 'DESC';
            $this->db->order_by($sort_field, $sort_dir);

            $query = $this->db->get();
            return array(
                'data' => $query->result_array(),
                'total' => $total
            );
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return array('data' => array(), 'total' => 0);
        }
    }

    /**
     * Reset activation token (cho resend)
     * @param int $id
     * @return string|false new token
     */
    public function reset_activation_token($id)
    {
        try {
            $new_token = bin2hex(random_bytes(32));
            $this->db->where('id', $id);
            $this->db->update($this->table, array(
                'activation_token' => $new_token,
                'is_verified' => 0,
                'verified_at' => null
            ));
            return ($this->db->affected_rows() > 0) ? $new_token : false;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            return false;
        }
    }
}
