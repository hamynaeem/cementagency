<?php
defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
require_once APPPATH . '/libraries/JWT.php';

use Restserver\Libraries\REST_Controller;
use \Firebase\JWT\JWT;

class Reports extends REST_Controller
{
    private $userID = 0;
    
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        parent::__construct();

        $this->load->database();
    }
    public function index_get()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers, authorization');
        $this->response(array('result'=>'Ok'), REST_Controller::HTTP_OK);
    }
    public function index_options()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers, authorization');
        $this->response(null, REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers, authorization');
        $this->response(true, REST_Controller::HTTP_OK);
    }

    public function salesummary_get()
    {
        try {
            $filter = $this->get('filter');
            
            // Check if the view exists first
            if (!$this->db->table_exists('qrysalereport')) {
                $this->response([
                    'error' => 'Sales report view not found',
                    'message' => 'The qrysalereport view does not exist in the database'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }
            
            $result = $this->db->select('CompanyName, UrduName, Packing, truncate(sum(TotPcs)/Packing, 0) as Packs,  MOD(sum(TotPcs),  Packing)  as Pcs')
            ->from('qrysalereport')
            ->where($filter? $filter: '1=1')
            ->group_by('CompanyName,UrduName, Packing')
            ->order_by('CompanyName,UrduName')
            ->get()->result_array();
            
            $this->response($result, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $this->response([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getAuthorizationHeader()
    {
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
            return $headers['Authorization'];
        } else {
            return null;
        }
    }
  
    /**
     *
     * get access token from header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                //echo $matches[1];
                return $matches[1];
            }
        }
        return null;
    }
    public function checkToken()
    {
        $token = $this->getBearerToken();
        if ($token) {
            try {
                $decode = JWT::decode($token, $this->config->item('api_key'), array('HS256'));
                $this->userID = $decode->id;
                return true;
            } catch (Exception $e) {
                // Token is invalid or expired
                return false;
            }
        }
        return false;
    }
    
    public function test_get()
    {
        $this->response(['message' => 'Reports controller working', 'timestamp' => date('Y-m-d H:i:s')], REST_Controller::HTTP_OK);
    }
}
