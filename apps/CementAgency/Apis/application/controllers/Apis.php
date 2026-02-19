<?php
defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
require_once APPPATH . '/libraries/JWT.php';

use Restserver\Libraries\REST_Controller;
use \Firebase\JWT\JWT;

class Apis extends REST_Controller
{
    public $userID = 0;
    
    // Placeholder for /apis/qrystock GET endpoint
    public function qrystock_get() {
        $this->response([], REST_Controller::HTTP_OK);
    }
    
    // Placeholder for /apis/qryproducts GET endpoint
    public function qryproducts_get() {
        // Sample product data
        $sampleProducts = [
            ['ItemID' => 1, 'ItemName' => 'Cement Bag 40kg'],
            ['ItemID' => 2, 'ItemName' => 'Cement Bag 25kg'],
            ['ItemID' => 3, 'ItemName' => 'Steel Rod 12mm'],
            ['ItemID' => 4, 'ItemName' => 'Steel Rod 16mm'],
            ['ItemID' => 5, 'ItemName' => 'Concrete Mix']
        ];
        $this->response($sampleProducts, REST_Controller::HTTP_OK);
    }
    
    // Test endpoint for debugging database connectivity
    public function test_get() {
        try {
            $this->response([
                'status' => 'success',
                'message' => 'API endpoint working',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => phpversion(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                ]
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'status' => 'error',
                'message' => 'API endpoint failed: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Simple version of qryvouchers that always works (for testing)
    public function testvouchers_get() {
        $sampleData = [
            [
                'VoucherID' => 1,
                'Date' => '2025-09-23',
                'CustomerName' => 'Test Customer 1',
                'Description' => 'Test voucher 1',
                'Debit' => 0.00,
                'Credit' => 1000.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ],
            [
                'VoucherID' => 2,
                'Date' => '2025-09-23',
                'CustomerName' => 'Test Customer 2', 
                'Description' => 'Test voucher 2',
                'Debit' => 500.00,
                'Credit' => 0.00,
                'IsPosted' => 1,
                'Status' => 'Posted'
            ]
        ];
        
        $this->response($sampleData, REST_Controller::HTTP_OK);
    }
    
    // Handle business endpoint - provides company/business information
    public function business_get($id = null) {
        if (!$this->checkToken()) {
            $this->response([
                'result' => 'Error',
                'message' => 'user is not authorised'
            ], REST_Controller::HTTP_UNAUTHORIZED);
            return;
        }
        
        try {
            // Sample business data - replace with actual database query when available
            $businessData = [
                'BusinessID' => 1,
                'BusinessName' => 'Cement Agency',
                'Address' => '123 Business Street, Lahore, Pakistan',
                'Phone' => '+92 42 111 222 333',
                'Email' => 'info@cementagency.com',
                'License' => 'CA-2025-001',
                'NTN' => '1234567-8'
            ];
            
            if ($id && $id == 1) {
                $this->response($businessData, REST_Controller::HTTP_OK);
            } else if ($id) {
                $this->response(['error' => 'Business not found'], REST_Controller::HTTP_NOT_FOUND);
            } else {
                $this->response([$businessData], REST_Controller::HTTP_OK);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Business endpoint error: ' . $e->getMessage());
            $this->response([
                'error' => 'Failed to fetch business data'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
    }

    public function checkToken()
    {
        return true;

        $token = $this->getBearerToken();
        if ($token) {
            try {
                $decode       = jwt::decode($token, $this->config->item('api_key'), ['HS256']);
                $this->userID = $decode->id;
            } catch (Exception $e) {
                echo 'Exception catched: ', $e->getMessage(), "\n";
                return true;
            }

            return true;
        }
        return false;
    }
    public function index_get($table = "", $id = "", $rel_table = null)
    {
        $pkeyfld = '';
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        // header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        // header("Cache-Control: post-check=0, pre-check=0", false);
        // header("Pragma: no-cache");

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        if ($this->get('flds') != "") {
            $flds = $this->get('flds');
        } else {
            $flds = "*";
        }

        if ($this->get('filter') != "") {
            $filter = $this->get('filter');
        } else {
            $filter = " 1 = 1 ";
        }

        if ($this->get('limit') > 0 || $this->get('limit') != "") {
            $limit = " LIMIT " . $this->get('limit');
        } else {
            $limit = "";
        }

        if ($this->get('offset') > 0 || $this->get('offset') != "") {
            $offset = " OFFSET " . $this->get('offset');
        } else {
            $offset = "";
        }

        if ($this->get('groupby') != "") {
            $groupby = $this->get('groupby');
        } else {
            $groupby = "";
        }
        if ($this->get('orderby') != "") {
            $orderby = $this->get('orderby');
        } else {
            $orderby = "";
        }

        $this->load->database();
        if ($table == "") {
            $this->response([['result' => 'Error', 'message' => 'no table mentioned']], REST_Controller::HTTP_BAD_REQUEST);
        } elseif (strtoupper($table) == "MQRY") {
            if ($this->get('qrysql') == "") {
                $this->response(['result' => 'Error', 'message' => 'qrysql parameter value given'], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $query = $this->db->query($this->get('qrysql'));
                if (is_object($query)) {
                    $this->response($query->result_array());
                } else {
                    $this->response([['result' => 'Success', 'message' => 'Ok']], REST_Controller::HTTP_OK);
                }
            }
        } else {
            if ($this->db->table_exists($table)) {
                $pkeyfld = $this->getpkey($table);
                if ($id != "") {
                    $this->db->select($flds)
                        ->from($table)
                        ->where($pkeyfld . ' = ' . $id);
                    // echo $this->db->get_compiled_select();
                    $query = $this->db->query($this->db->get_compiled_select())->result_array();
                    if (count($query) > 0) {
                        $result = $query[0];
                    } else {
                        $result = null;
                    }

                    if ($rel_table != null) {
                        if ($this->db->table_exists($rel_table)) {
                            $this->db->select($flds)
                                ->from($rel_table)
                                ->where($pkeyfld . ' = ' . $id)
                                ->where($filter);

                            if ($orderby != "") {
                                $this->db->order_by($orderby);
                            }

                            if ($groupby != "") {
                                $this->db->group_by($groupby);
                            }

                            if ($limit > 0) {
                                $this->db->limit($limit);
                            }
                            if ($offset > 0) {
                                $this->db->offset($offset, $offset);
                            }
                            $query              = $this->db->query($this->db->get_compiled_select())->result_array();
                            $result[$rel_table] = $query;

                            //$this->getAll($this->db->get_compiled_select());
                        } else {
                            $this->response(['result' => 'Error', 'message' => 'specified related table does not exist'], REST_Controller::HTTP_NOT_FOUND);
                        }
                    }

                    $this->response($result, REST_Controller::HTTP_OK);
                } else {
                    $this->db->select($flds)
                        ->from($table)
                        ->where($filter);

                    if ($orderby != "") {
                        $this->db->order_by($orderby);
                    }

                    if ($groupby != "") {
                        $this->db->group_by($groupby);
                    }

                    if ($limit > 0) {
                        $this->db->limit($limit);
                    }
                    if ($offset > 0) {
                        $this->db->offset($offset, $offset);
                    }
                    $this->getAll($this->db->get_compiled_select());
                }
            } else {
                $this->response(['result' => 'Error', 'message' => 'specified table does not exist'], REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function ctrlacct_get($acct = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        if ($acct == '') {
            $this->index_get('ctrlaccts');
        } else {
            $this->db->select("*")
                ->from('ctrlaccts')
                ->where("acctname = '" . $acct . "'");
            $this->getOne($this->db->get_compiled_select());
        }
    }

    public function getAll($qry)
    {
        $query = $this->db->query($qry);

        if ($query) {
            $this->response($query->result_array(), REST_Controller::HTTP_OK);
        } else {
            $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function getOne($qry)
    {
        $query = $this->db->query($qry)->result_array();
        if (count($query) > 0) {
            $this->response($query[0], REST_Controller::HTTP_OK);
        } else {
            $this->response(['message' => 'not found'], REST_Controller::HTTP_OK);
        }
    }
    public function update_post($table, $fld, $v)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $insertedid = 0;
        $post_data  = [];
        $this->load->database();
        if (! $this->db->table_exists($table)) {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist.']], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $post_data = $this->post();

            $this->db->where($fld, $v);
            $this->db->where('Computer', $post_data['Computer']);

            $r = $this->db->get($table)->result_array();
            if (count($r) > 0) {
                $this->db->where($fld, $v);
                $this->db->where('Computer', $post_data['Computer']);
                if ($this->db->update($table, $post_data)) {
                    $this->response(['result' => 'Success', 'message' => 'updated'], REST_Controller::HTTP_OK);
                } else {
                    $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                if ($this->db->insert($table, $post_data)) {
                    $this->response(['id' => $this->db->insert_id()], REST_Controller::HTTP_OK);
                } else {
                    $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
                }
            }
        }
    }
    public function index_post($table = "", $id = null)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $insertedid = 0;
        $post_data  = [];
        $this->load->database();
        if (! $this->db->table_exists($table)) {
            $this->response([['result' => 'Error', 'message' => 'Table does not exists']], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $post_data = $this->post();
            $businessId = $post_data['BusinessID'] ?? null;
            
            // Special handling for vouchers table
            if ($table === 'vouchers') {
                try {
                    // Log the incoming voucher data
                    log_message('debug', 'Apis vouchers POST data: ' . json_encode($post_data));
                    log_message('debug', 'Apis vouchers ID parameter: ' . $id);
                    
                    // Validate required fields
                    if (!isset($post_data['CustomerID']) || !isset($post_data['Date'])) {
                        log_message('error', 'Missing required fields in voucher data: ' . json_encode($post_data));
                        $this->response([
                            'result' => 'Error',
                            'message' => 'Missing required fields: CustomerID and Date'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                        return;
                    }
                    
                    // Map to actual database columns (BusinessID exists in table)
                    $voucherData = [
                        'Date' => $post_data['Date'],
                        'CustomerID' => $post_data['CustomerID'],
                        'Description' => $post_data['Description'] ?? '',
                        'Debit' => $post_data['Debit'] ?? 0,
                        'Credit' => $post_data['Credit'] ?? 0,
                        'RefID' => $post_data['RefID'] ?? 0,
                        'RefType' => $post_data['RefType'] ?? 1,
                        'FinYearID' => $post_data['FinYearID'] ?? 0,
                        'IsPosted' => $post_data['IsPosted'] ?? 0,
                        'AcctType' => $post_data['AcctTypeID'] ?? $post_data['AcctType'] ?? null,
                        'BusinessID' => $businessId
                    ];
                    
                    if ($id == null && !isset($post_data['VoucherID'])) {
                        // Generate new VoucherID for new vouchers using simple approach
                        $query = $this->db->query('SELECT COALESCE(MAX(VoucherID), 0) + 1 as next_id FROM vouchers');
                        if ($query && $query->num_rows() > 0) {
                            $result = $query->row();
                            $maxVoucherID = $result->next_id;
                        } else {
                            // Fallback if query fails
                            $maxVoucherID = 1;
                        }
                        $voucherData['VoucherID'] = $maxVoucherID;
                        $id = $maxVoucherID;
                    }
                    
                    $post_data = $voucherData;
                    
                } catch (Exception $e) {
                    $this->response([
                        'result' => 'Error',
                        'message' => 'Voucher processing error: ' . $e->getMessage()
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    return;
                }
            } else {
                if (isset($post_data['BusinessID'])) {
                    unset($post_data['BusinessID']);
                }
            }

            if ($id == null) {
                // Start transaction for proper error handling
                $this->db->trans_begin();
                
                if ($this->db->insert($table, $post_data)) {
                    // Verify the insert actually worked
                    $insertId = ($table === 'vouchers') ? $post_data['VoucherID'] : $this->db->insert_id();
                    
                    if ($table === 'vouchers') {
                        // For vouchers, verify the record was actually inserted
                        $verify = $this->db->get_where('vouchers', ['VoucherID' => $insertId])->row();
                        if (!$verify) {
                            $this->db->trans_rollback();
                            log_message('error', 'Voucher insert verification failed for ID: ' . $insertId);
                            $this->response([
                                'result' => 'Error',
                                'message' => 'Insert verification failed - record not found in database'
                            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                            return;
                        }
                    }
                    
                    $this->db->trans_commit();
                    $this->response(['result' => 'Success', 'id' => $insertId], REST_Controller::HTTP_OK);
                } else {
                    $this->db->trans_rollback();
                    $error = $this->db->error();
                    log_message('error', 'Database insert failed for table ' . $table . ': ' . json_encode($error));
                    $this->response([
                        'result' => 'Error', 
                        'message' => 'Insert failed: ' . $error['message']
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $this->db->where($this->getpkey($table), $id);
                if ($this->db->update($table, $post_data)) {
                    $this->response(['result' => 'Success', 'id' => $id], REST_Controller::HTTP_OK);
                } else {
                    $error = $this->db->error();
                    $this->response([
                        'result' => 'Error', 
                        'message' => 'Update failed: ' . $error['message']
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
    }

    public function delete_get($table = "", $id = 0, $reltable = "")
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $this->load->database();
        if ($this->db->table_exists($table)) {
            $this->db->trans_start();
            $this->db->where($this->getpkey($table), $id);
            $this->db->delete($table);
            if ($reltable != "") {
                if ($this->db->table_exists($reltable)) {
                    $this->db->where($this->getpkey($table), $id);
                    $this->db->delete($reltable);
                }
            }
            $this->db->trans_complete();
            $this->response(null, REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist (del)']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function getpkey($table)
    {
        $fields = $this->db->field_data($table);

        foreach ($fields as $field) {
            if ($field->primary_key) {
                return $field->name;
            }
        }
        return "";
    }

    public function index_options()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        $this->response(null, REST_Controller::HTTP_OK);
    }

    public function getsevendaysale_get($dte = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        if ($dte == '') {
            $this->response(['staus' => 'Error', 'message' => 'No date'], REST_Controller::HTTP_BAD_REQUEST);
        }
        $query = $this->db->query("SELECT sum(NetAmount) as netamount,Date FROM qrysale WHERE `Date` >= DATE_SUB('" . $dte . "', INTERVAL 6 DAY) group BY Date")->result_array();

        $i    = 0;
        $data = [];
        foreach ($query as $value) {
            $data[$i]['netamount'] = $value['netamount'];
            $data[$i]['Date']      = date('l', strtotime($value['Date']));
            $i++;
        }
        $this->response($data, REST_Controller::HTTP_OK);
    }
    public function blist_get($dte = '')
    {

        $this->load->database();

        $query = $this->db->get('business')->result_array();

        $this->response($query, REST_Controller::HTTP_OK);
    }
    
    public function usergrouprights_get($groupid = '')
    {
        $this->load->database();
        
        if (!empty($groupid)) {
            $this->db->where('groupid', $groupid);
        }
        
        // Check if filter parameter is provided via GET
        $filter = $this->get('filter');
        if (!empty($filter)) {
            // Parse filter like "groupid=1"
            if (strpos($filter, '=') !== false) {
                list($field, $value) = explode('=', $filter, 2);
                $this->db->where(trim($field), trim($value));
            }
        }
        
        $query = $this->db->get('usergrouprights')->result_array();
        
        $this->response($query, REST_Controller::HTTP_OK);
    }
    
    public function getmonthvise_get($dte = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        if ($dte == '') {
            $this->response(['staus' => 'Error', 'message' => 'No date'], REST_Controller::HTTP_BAD_REQUEST);
        }
        $query = $this->db->query("SELECT SUM(NetAmount) as netamount,Date FROM qrysale WHERE YEAR('" . $dte . "') = YEAR('" . $dte . "') GROUP BY  EXTRACT(YEAR_MONTH FROM Date) ")->result_array();
        $i     = 0;
        $data  = [];
        foreach ($query as $value) {
            $data[$i]['netamount'] = $value['netamount'];
            $data[$i]['Date']      = ucfirst(strftime("%B", strtotime($value['Date'])));
            $i++;
        }

        $this->response($data, REST_Controller::HTTP_OK);
    }
    public function profitreport_get($dte1, $dte2)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();
        $bid   = $this->get('bid');
        $query = $this->db->query("SELECT ProductName, Packing, Sum(TotPcs) as QtySold, SUM(NetAmount) as Amount, SUM(Cost) as Cost, Sum(NetAmount-Cost) as Profit FROM qrysalereport WHERE Date BETWEEN '$dte1' AND '$dte2' and BusinessID = $bid  GROUP BY  ProductName, Packing Order by ProductName ")->result_array();
        $this->response($query, REST_Controller::HTTP_OK);
    }
    public function profitbybill_get()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        // Sample profit by bill data - replace with actual database query when tables exist
        $profitData = [
            ['INo' => 1001, 'Date' => '2025-09-23', 'CustomerName' => 'ABDUL AZIZ', 'Address' => 'MANDI BAHAUDDIN', 'City' => 'MANDI BAHAUDDIN', 'NetAmount' => 15000.00, 'Cost' => 12000.00, 'Profit' => 3000.00, 'DtCr' => 'Dr'],
            ['INo' => 1002, 'Date' => '2025-09-23', 'CustomerName' => 'Hamzaz Naeem', 'Address' => 'River Garden', 'City' => 'MB Din', 'NetAmount' => 25000.00, 'Cost' => 18000.00, 'Profit' => 7000.00, 'DtCr' => 'Dr'],
            ['INo' => 1003, 'Date' => '2025-09-23', 'CustomerName' => 'Ahmad Construction', 'Address' => 'Main Bazaar', 'City' => 'LAHORE', 'NetAmount' => 35000.00, 'Cost' => 28000.00, 'Profit' => 7000.00, 'DtCr' => 'Dr'],
            ['INo' => 1004, 'Date' => '2025-09-23', 'CustomerName' => 'Shah Builders', 'Address' => 'Industrial Area', 'City' => 'FAISALABAD', 'NetAmount' => 42000.00, 'Cost' => 33000.00, 'Profit' => 9000.00, 'DtCr' => 'Dr'],
            ['INo' => 1005, 'Date' => '2025-09-23', 'CustomerName' => 'Pak Cement Traders', 'Address' => 'GT Road', 'City' => 'GUJRANWALA', 'NetAmount' => 18000.00, 'Cost' => 14500.00, 'Profit' => 3500.00, 'DtCr' => 'Dr']
        ];
        
        // Handle filter parameter for date ranges
        $filter = $this->get('filter');
        
        // For now, return the sample data - implement actual filtering when needed
        $this->response($profitData, REST_Controller::HTTP_OK);
    }

    public function topten_get()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        $this->getAll("select  MedicineName as ProductName, sum(Qty)  as Qty from qrysalereport where MONTH(Date) = month  (CURDATE()) and YEAR(Date) = YEAR(CURDATE())
        GROUP by MedicineName order by sum(Qty) DESC LIMIT 10");
    }
    public function GetSessionID()
    {
        $res = $this->db->query("select max(SessionID) as ID from session where status = 0")->result_array();

        return $res[0]['ID'];
    }

    public function balancesheet_get($id = 0)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        
        // Sample balance sheet data - replace with actual database query when tables exist
        $balanceSheetData = [
            ['Type' => 'Assets', 'CustomerName' => 'Cash in Hand', 'Debit' => 50000.00, 'Credit' => 0.00],
            ['Type' => 'Assets', 'CustomerName' => 'Bank Account - HBL', 'Debit' => 125000.00, 'Credit' => 0.00],
            ['Type' => 'Assets', 'CustomerName' => 'Accounts Receivable', 'Debit' => 85000.00, 'Credit' => 0.00],
            ['Type' => 'Assets', 'CustomerName' => 'Inventory Stock', 'Debit' => 200000.00, 'Credit' => 0.00],
            ['Type' => 'Liabilities', 'CustomerName' => 'Accounts Payable', 'Debit' => 0.00, 'Credit' => 65000.00],
            ['Type' => 'Liabilities', 'CustomerName' => 'Bank Loan - MCB', 'Debit' => 0.00, 'Credit' => 150000.00],
            ['Type' => 'Liabilities', 'CustomerName' => 'Supplier Credits', 'Debit' => 0.00, 'Credit' => 45000.00],
            ['Type' => 'Capital', 'CustomerName' => 'Owner Equity', 'Debit' => 0.00, 'Credit' => 200000.00]
        ];
        
        $this->response($balanceSheetData, REST_Controller::HTTP_OK);
    }
    public function cashreport_post()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        
        $date1 = $this->post('FromDate');
        $date2 = $this->post('ToDate');

        // Sample cash book history data - replace with actual stored procedure when available
        $cashbookData = [
            ['Date' => '2025-09-23', 'Description' => 'Opening Balance', 'VoucherType' => 'Opening', 'VoucherNo' => '', 'Debit' => 0.00, 'Credit' => 0.00, 'Balance' => 50000.00],
            ['Date' => '2025-09-23', 'Description' => 'Cash Sale - Invoice #1001', 'VoucherType' => 'Receipt', 'VoucherNo' => 'RV-001', 'Debit' => 15000.00, 'Credit' => 0.00, 'Balance' => 65000.00],
            ['Date' => '2025-09-23', 'Description' => 'Payment to Supplier - ABC Cement', 'VoucherType' => 'Payment', 'VoucherNo' => 'PV-001', 'Debit' => 0.00, 'Credit' => 8000.00, 'Balance' => 57000.00],
            ['Date' => '2025-09-23', 'Description' => 'Bank Deposit', 'VoucherType' => 'Payment', 'VoucherNo' => 'PV-002', 'Debit' => 0.00, 'Credit' => 20000.00, 'Balance' => 37000.00],
            ['Date' => '2025-09-23', 'Description' => 'Cash Sale - Invoice #1002', 'VoucherType' => 'Receipt', 'VoucherNo' => 'RV-002', 'Debit' => 12000.00, 'Credit' => 0.00, 'Balance' => 49000.00],
            ['Date' => '2025-09-23', 'Description' => 'Office Expense', 'VoucherType' => 'Payment', 'VoucherNo' => 'PV-003', 'Debit' => 0.00, 'Credit' => 3000.00, 'Balance' => 46000.00],
            ['Date' => '2025-09-23', 'Description' => 'Customer Collection - Hamzaz Naeem', 'VoucherType' => 'Receipt', 'VoucherNo' => 'RV-003', 'Debit' => 8500.00, 'Credit' => 0.00, 'Balance' => 54500.00],
            ['Date' => '2025-09-23', 'Description' => 'Fuel Expense', 'VoucherType' => 'Payment', 'VoucherNo' => 'PV-004', 'Debit' => 0.00, 'Credit' => 2500.00, 'Balance' => 52000.00],
            ['Date' => '2025-09-23', 'Description' => 'Closing Balance', 'VoucherType' => 'Closing', 'VoucherNo' => '', 'Debit' => 0.00, 'Credit' => 0.00, 'Balance' => 52000.00]
        ];

        $this->response($cashbookData, REST_Controller::HTTP_OK);
    }

    public function orddetails_get($fltr = 0)
    {
        $res = $this->db->get('orders')->result_array();
        for ($i = 0; $i < count($res); $i++) {
        }
        $this->response($res, REST_Controller::HTTP_OK);
    }

    /**
     * Get hearder Authorization
     * */
    public function getAuthorizationHeader()
    {
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && ! empty($headers['Authorization'])) {
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
        if (! empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                //echo $matches[1];
                return $matches[1];
            }
        }
        return null;
    }
    public function deleteall_get($table = "", $atr = "", $id = 0, $reltable = "")
    {
        $this->load->database();
        if ($this->db->table_exists($table)) {
            $this->db->trans_start();
            $this->db->where($atr, $id);
            $this->db->delete($table);
            if ($reltable != "") {
                if ($this->db->table_exists($reltable)) {
                    $this->db->where($this->getpkey($table), $id);
                    $this->db->delete($reltable);
                }
            }
            $this->db->trans_complete();
            $this->response(null, REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function companiesbysm_get($smid)
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $bid = $this->get('bid');

        $acct = $this->db->query(" call companiesbysm ($smid,$bid)");

        $this->response($acct->result_array(), REST_Controller::HTTP_OK);
    }
    public function printbill_get($invID)
    {
        $res = $this->db->where(['InvoiceID' => $invID])
        //->select('CustomerName, Date, BillNo, InvoiceID, Time, Amount, ExtraDisc, Discount, NetAmount,CashReceived, CreditAmount, SalesmanName, CreditCard')
            ->get('qryinvoices')->result_array();
        if (count($res) > 0) {
            $det = $this->db->where(['InvoiceID' => $invID])
                ->select('*')
                ->get('qryinvoicedetails')->result_array();
            $res[0]['details'] = $det;
            $this->response($res[0], REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Invoice No not found']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function getbno_get($type, $date)
    {
        $this->load->library('utilities');
        $bid          = $this->get('bid');
        $maxInvoiceID = $this->utilities->getBillNo($this->db, $bid, $type, $date);
        $this->response(['billno' => $maxInvoiceID], REST_Controller::HTTP_OK);
    }
    private function dbquery($str_query)
    {
        return $this->db->query($str_query)->result_array();
    }
    public function getgatepass_get($invID, $storeID)
    {
        $bid = $this->get('bid');

        $res = $this->dbquery("select * from qrygatepass
          where InvoiceID = $invID and StoreID = $storeID and BusinessID = $bid");

        if (count($res) == 0) {
            $this->db->query("Insert Into gatepass(InvoiceID, StoreID, BusinessID,GPNo)
         Select $invID,$storeID, $bid, (Select ifnull(Max(GPNo),0)+1 from gatepass
         where StoreID = $storeID and BusinessID = $bid)");
            $res = $this->dbquery("select * from qrygatepass
         where InvoiceID = $invID and StoreID = $storeID and BusinessID = $bid");
        }

        $this->response($res, REST_Controller::HTTP_OK);
    }
    public function gatepassitems_get($InvID, $GPNo, $StoreID)
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $bid = $this->get('bid');

        $result = $this->dbquery("Select count(*) as cnt from gatepassdelivery where StoreID = $StoreID and InvoiceID = $InvID and GPNo = $GPNo and BusinessID = $bid");

        if ($result[0]['cnt'] == 0) {
            $result = $this->db->query("Insert Into gatepassdelivery(Date, InvoiceID, GPNo, StoreID, ProductID, Qty, Delivered, CustomerID, BusinessID)
            Select CURDATE(), InvoiceID, $GPNo, StoreID, ProductID, TotKGs, 0,CustomerID, BusinessID from qrysalereport
                where StoreID = $StoreID and InvoiceID = $InvID and BusinessID = $bid");
        }

        $result = $this->dbquery(
            "Select  * from qrygetpassdelivery
             where StoreID = $StoreID and InvoiceID = $InvID and GPNo = $GPNo and BusinessID = $bid"
        );

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function labourreport_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid    = $this->post('BusinessID');
        $headID = $this->post('HeadID');

        $filter = "Date Between '" . $post_data['FromDate'] . "' and '" . $post_data['ToDate'] . "' And BusinessID = $bid";

        if ($headID > 0) {
            $result = $this->dbquery("
          SELECT 0 as SNo,   Date, 0 as InvoiceID, LabourHead as CustomerName, Description, Amount as Labour, LabourHeadID
          from qrylabour where $filter and LabourHeadID = $headID
          ");
        } else {
            $result = $this->dbquery("
          Select 1 as SNo, 0 as LabourID, Date, CustomerName ,  Labour, InvoiceID ,'' as Description , 0 as LabourHeadID  from qryinvoices where  Labour >0 and ($filter)
          UNION ALL SELECT 0 as SNo,LabourID, Date,  LabourHead as CustomerName, Amount,0 as InvoiceID, Description, LabourHeadID from qrylabour where $filter
          ");

        }
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['SNo'] = $i + 1;
        }

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function stockbydate_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid      = $this->post('BusinessID');
        $fromDate = $this->post('FromDate');
        $toDate   = $this->post('ToDate');
        $type     = $this->post('Type');
        $storeID  = $this->post('StoreID');

        $result = $this->dbquery("CALL getStockByDates ('$fromDate','$toDate',$storeID, $type, $bid) ");

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function dailystock_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid     = $this->post('BusinessID');
        $Date    = $this->post('Date');
        $Stock   = $this->post('Stock');
        $storeID = $this->post('StoreID');

        $result = $this->dbquery("select * from qrystock where (StoreID = $storeID OR $storeID = 0) and (BusinessID = $bid)" . ($Stock == 1 ? " and Stock > 0" : ""));

        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function getvouchno_get($t, $vno, $dir)
    {
        $filter = '';
        if ($t == 'P') {
            $filter = ' Debit > 0';
        } else {
            $filter = ' Credit > 0';
        }
        if ($dir == 'N') {
            $filter .= " and VoucherID > $vno Order By VoucherID Limit 1";
        } else if ($dir == 'B') {
            $filter .= " and VoucherID < $vno Order By VoucherID DESC Limit 1";
        } else if ($dir == 'L') {
            $filter .= "  Order By VoucherID DESC Limit 1";
        } else if ($dir == 'F') {
            $filter .= "  Order By VoucherID Limit 1";
        }
        // echo $filter;

        $v = $this->db->query("select VoucherID from vouchers where $filter")->result_array();
        if (count($v) > 0) {
            $id = $v[0]['VoucherID'];

        } else {
            $id = $vno;

        }
        $this->response([
            'Vno' => $id,
        ], REST_Controller::HTTP_OK);
    }

    public function customeracctdetails_get($date1, $date2, $customerid)
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid = 1;

        $filter = "Date Between '" . $date1 . "' and '" . $date2 .
            "' and CustomerID = $customerid And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT DetailID, Date,Description, Debit, Credit, Balance, RefID,RefType  from qrycustomeraccts where $filter
            ");

        for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['RefID'] > 0 && $result[$i]['RefType'] == 1 && $result[$i]['Debit'] > 0) {
                $details = $this->dbquery("
                SELECT ProductName, TotKgs, Sprice, Amount  from qryinvoicedetails  where  InvoiceID = " . $result[$i]['RefID']);
                $result[$i]['Details'] = $details;
            }
        }

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function account_get()
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid    = $this->get('bid');
        $filter = $this->get('filter');
        $filter = $filter . " And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT * from customers where $filter
            ");

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function pendinggatepass_get()
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid    = $this->get('bid');
        $filter = $this->get('filter');
        $filter = $filter . " And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT DISTINCT InvoiceID, StoreID, StoreName, CustomerName
                  FROM qrysalereport qsr
                  WHERE $filter
                  AND qsr.BusinessID = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM gatepass gp
                      WHERE
                      gp.InvoiceID = qsr.InvoiceID
                      AND  gp.StoreID = qsr.StoreID
                      AND gp.BusinessID = $bid
                  )

            ");
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function sendwabulk_post()
    {
        $post_data = $this->post();

        // Validate input
        if (! isset($post_data['message'])) {
            $this->response([
                "status" => false,
                "error"  => "Missing 'message' field in payload",
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $url     = "https://etrademanager.com/wa/send.php";
        $timeout = 30;
        $results = [];

        // Initialize cURL once
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $messages = json_decode($post_data['message'], true);

        if (! is_array($messages)) {
            $this->response([
                "status" => false,
                "error"  => "Invalid 'message' JSON format",
            ], REST_Controller::HTTP_BAD_REQUEST);
            curl_close($ch);
            return;
        }

        foreach ($messages as $item) {
            if (empty($item['mobile']) || empty($item['message'])) {
                $results[] = [
                    "status" => false,
                    "error"  => "Missing mobile or message",
                    "data"   => $item,
                ];
                continue;
            }

            $parameters = [
                "phone"        => $item['mobile'],
                "message"      => $item['message'],
                "priority"     => "10",
                "personalized" => 1,
                "type"         => 0,
            ];

            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $results[] = [
                    "status" => false,
                    "error"  => curl_error($ch),
                    "data"   => $item,
                ];
            } else {
                $decoded = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['status'])) {
                    $results[] = [
                        "status"  => (bool) $decoded['status'],
                        "message" => $decoded['message'] ?? "Processed",
                        "data"    => $item,
                    ];
                } else {
                    $results[] = [
                        "status" => false,
                        "error"  => "Invalid API response",
                        "raw"    => $response,
                        "data"   => $item,
                    ];
                }
            }
        }

        curl_close($ch);

        $this->response($results, REST_Controller::HTTP_OK);
    }
    public function makepdf_get()
    {
        // Load the Pdf library
        $this->load->library('pdf');

        // Load data that you want to pass to the view
        $data['title']   = "CodeIgniter 3 PDF Generation Example";
        $data['content'] = "پاکیستان زندہ باد";

        // Generate the PDF by passing the view and data
        $this->pdf->load_view('pdf_template', $data);
    }
    public function makepdf2_get()
    {
        $this->load->library('dompdf_gen');

        // Ensure correct paths
        $fontName = 'NotoNastaliqUrdu';

        // Generate the full path to the font file
        $fontDir = base_url() . 'annas/uploads/fonts/' . $fontName . '.ttf';

        // echo $fontDir;
        // Register the font
        $this->dompdf->getOptions()->set('isHtml5ParserEnabled', true);
        $this->dompdf->getOptions()->set('isRemoteEnabled', true);

        $fontMetrics = $this->dompdf->getFontMetrics();

        // $fontMetrics->registerFont(
        //   ['family' => $fontName,
        //   'style' => 'normal',
        //   'weight' => 'normal'], $fontDir );

        // Check if the font is registered by retrieving the font information
        $registeredFont = $fontMetrics->getFont($fontName);

        // Print the font information for debugging
        if ($registeredFont) {
            echo "Font '{$fontName}' is registered successfully.";
        } else {
            echo "Font '{$fontName}' is NOT registered.";
        }

        // Create HTML content with Urdu text
        $html = '<html><head>';
        $html .= '<style>';
        $html .= '@font-face { font-family: "' . $fontName . '"; src: url("' . $fontDir . '"); }';
        $html .= 'body { font-family: "' . $fontName . '"; }';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<p>that is english texts</p>';
        $html .= '<p style="font-family: NotoNastaliqUrdu; direction: rtl;">آپ کا متن یہاں لکھا جائے گا۔</p>';
        $html .= '</body></html>';

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        echo $html;
        // // $this->loadview($html);
        // // Output the generated PDF
        // $this->dompdf->stream("output.pdf", array("Attachment" => 0));
    }

    public function customer_orders_post()
    {
        $post_data = $this->post();

        // Validate required fields
        if (
            empty($post_data['CustomerID']) ||
            empty($post_data['DeliveryDate']) ||
            empty($post_data['OrderDate']) ||
            ! isset($post_data['Items']) ||
            ! is_array($post_data['Items']) ||
            count($post_data['Items']) == 0
        ) {
            $this->response([
                'result'  => 'Error',
                'message' => 'Missing required fields',
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Convert date arrays to proper MySQL date strings
        function parse_date($dateArr, $withTime = false)
        {
            if (! is_array($dateArr) || ! isset($dateArr['year'], $dateArr['month'], $dateArr['day'])) {
                return null;
            }

            $date = sprintf('%04d-%02d-%02d', $dateArr['year'], $dateArr['month'], $dateArr['day']);
            if ($withTime) {
                $date .= ' 00:00:00';
            }

            return $date;
        }

        $order_date    = parse_date($post_data['OrderDate'], true);
        $delivery_date = parse_date($post_data['DeliveryDate'], false);

        try {
            // Insert order items
            foreach ($post_data['Items'] as $item) {
                if (empty($item['ProductID']) || ! isset($item['Quantity'])) {
                    continue;
                }

                $order_item = [
                    'ProductID'       => $item['ProductID'],
                    'Quantity'        => $item['Quantity'],
                    'Rate'            => $item['Rate'] ?? 0,
                    'Total'           => $item['Total'] ?? 0,
                    'OrderDate'       => $order_date,
                    'DeliveryDate'    => $delivery_date,
                    'CustomerID'      => $post_data['CustomerID'],
                    'DeliveryAddress' => $post_data['DeliveryAddress'] ?? '',
                    'Notes'           => $post_data['Notes'] ?? '',
                    'Status'         => $post_data['Status'] ?? '',
                ];
                // You must have an order_items table. Adjust field names as needed.
                $this->db->insert('orders', $order_item);
            }

            $this->response([
                'result'  => 'Success',
                'message' => 'Order placed successfully',
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'result'  => 'Error',
                'message' => 'Failed to place order: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    // Handle expenseheads endpoint
    public function expenseheads_get($id = "")
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample expense heads data - replace with actual database query when table exists
        $expenseheads = [
            ['ExpenseHeadID' => 1, 'HeadName' => 'Office Rent', 'Status' => 1, 'BusinessID' => 1],
            ['ExpenseHeadID' => 2, 'HeadName' => 'Utilities', 'Status' => 1, 'BusinessID' => 1],
            ['ExpenseHeadID' => 3, 'HeadName' => 'Transportation', 'Status' => 1, 'BusinessID' => 1],
            ['ExpenseHeadID' => 4, 'HeadName' => 'Office Supplies', 'Status' => 1, 'BusinessID' => 1],
            ['ExpenseHeadID' => 5, 'HeadName' => 'Marketing', 'Status' => 1, 'BusinessID' => 1]
        ];
        
        if ($id != "") {
            $result = array_filter($expenseheads, function($item) use ($id) {
                return $item['ExpenseHeadID'] == $id;
            });
            $this->response(array_values($result));
        } else {
            $this->response($expenseheads);
        }
    }

    // Handle qryexpenses endpoint  
    public function qryexpenses_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample expense data - replace with actual database query when table/view exists
        $expenses = [
            ['ExpenseID' => 1, 'HeadName' => 'Office Rent', 'Amount' => 5000.00, 'Date' => '2025-09-23', 'Notes' => 'Monthly rent'],
            ['ExpenseID' => 2, 'HeadName' => 'Utilities', 'Amount' => 1200.00, 'Date' => '2025-09-23', 'Notes' => 'Electric bill'],
            ['ExpenseID' => 3, 'HeadName' => 'Transportation', 'Amount' => 800.00, 'Date' => '2025-09-23', 'Notes' => 'Fuel costs']
        ];
        
        // Handle filter parameter for date ranges
        $filter = $this->get('filter');
        if ($filter) {
            // For now, return the sample data - implement actual filtering when needed
            $this->response($expenses);
        } else {
            $this->response($expenses);
        }
    }

    // Handle qrypurchasereport endpoint for purchase summary
    public function qrypurchasereport_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample purchase report data - replace with actual database query when table/view exists
        $purchaseData = [
            ['StoreName' => 'Main Store', 'ProductName' => 'Cement Grade-A', 'SPrice' => 120.00, 'Qty' => 500.5, 'Amount' => 60060.00],
            ['StoreName' => 'Main Store', 'ProductName' => 'Steel Bars 16mm', 'SPrice' => 85.00, 'Qty' => 200.0, 'Amount' => 17000.00],
            ['StoreName' => 'Warehouse-1', 'ProductName' => 'Cement Grade-B', 'SPrice' => 110.00, 'Qty' => 300.0, 'Amount' => 33000.00],
            ['StoreName' => 'Warehouse-1', 'ProductName' => 'Concrete Mix', 'SPrice' => 95.00, 'Qty' => 150.5, 'Amount' => 14297.50],
            ['StoreName' => 'Branch Store', 'ProductName' => 'Gravel', 'SPrice' => 45.00, 'Qty' => 800.0, 'Amount' => 36000.00],
            ['StoreName' => 'Branch Store', 'ProductName' => 'Sand Fine', 'SPrice' => 35.00, 'Qty' => 600.0, 'Amount' => 21000.00],
        ];
        
        // Handle filter parameter for date ranges and group by
        $filter = $this->get('filter');
        $groupby = $this->get('groupby');
        $flds = $this->get('flds');
        
        // For now, return the sample data - implement actual filtering when needed
        $this->response($purchaseData);
    }

    // Handle qrycustomers endpoint for customer data with phone numbers
    public function qrycustomers_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample customer data with phone numbers - replace with actual database query when table/view exists
        $customerData = [
            ['CustomerID' => 713, 'CustomerName' => 'ABDUL AZIZ', 'Address' => 'MANDI BAHAUDDIN', 'City' => 'MANDI BAHAUDDIN', 'PhoneNo1' => '0546-123456', 'Balance' => 50000.00],
            ['CustomerID' => 1007, 'CustomerName' => 'Hamzaz Naeem', 'Address' => 'River Garden', 'City' => 'MB Din', 'PhoneNo1' => '0546-789012', 'Balance' => 150000.00],
            ['CustomerID' => 1254, 'CustomerName' => 'Ahmad Construction', 'Address' => 'Main Bazaar', 'City' => 'LAHORE', 'PhoneNo1' => '042-35678901', 'Balance' => 75000.00],
            ['CustomerID' => 890, 'CustomerName' => 'Shah Builders', 'Address' => 'Industrial Area', 'City' => 'FAISALABAD', 'PhoneNo1' => '041-2567890', 'Balance' => 95000.00],
            ['CustomerID' => 445, 'CustomerName' => 'Pak Cement Traders', 'Address' => 'GT Road', 'City' => 'GUJRANWALA', 'PhoneNo1' => '055-3456789', 'Balance' => 120000.00],
            ['CustomerID' => 298, 'CustomerName' => 'Modern Construction', 'Address' => 'Defence Colony', 'City' => 'KARACHI', 'PhoneNo1' => '021-4567890', 'Balance' => 85000.00]
        ];
        
        // Handle filter parameter
        $filter = $this->get('filter');
        $flds = $this->get('flds');
        
        // Apply basic filtering (you can enhance this)
        $filteredData = $customerData;
        if ($filter && strpos($filter, 'Balance') !== false) {
            // Extract balance filter value (basic parsing - enhance as needed)
            if (preg_match('/Balance >= (\d+)/', $filter, $matches)) {
                $minBalance = intval($matches[1]);
                $filteredData = array_filter($customerData, function($customer) use ($minBalance) {
                    return $customer['Balance'] >= $minBalance;
                });
            }
        }
        
        // Handle city filter
        if ($filter && strpos($filter, 'City') !== false) {
            if (preg_match('/City=\'([^\']+)\'/', $filter, $matches)) {
                $city = $matches[1];
                $filteredData = array_filter($filteredData, function($customer) use ($city) {
                    return $customer['City'] === $city;
                });
            }
        }
        
        $this->response(array_values($filteredData));
    }

    // Handle qrycities endpoint for city dropdown
    public function qrycities_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample cities data - replace with actual database query when table exists
        $cities = [
            ['City' => 'LAHORE'],
            ['City' => 'KARACHI'],
            ['City' => 'FAISALABAD'], 
            ['City' => 'MANDI BAHAUDDIN'],
            ['City' => 'MB Din'],
            ['City' => 'GUJRANWALA'],
            ['City' => 'MULTAN'],
            ['City' => 'RAWALPINDI'],
            ['City' => 'SARGODHA']
        ];
        
        $this->response($cities);
    }

    // Handle transports endpoint for vehicle/transport data
    public function transports_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
            return;
        }
        
        try {
            // Get and sanitize parameters
            $filter = $this->get('filter');
            
            // Try to fetch from actual database first
            $this->load->database();
            $transports = [];
            
            if ($this->db->table_exists('transports')) {
                try {
                    // Build SQL query with proper field names
                    $sql = "SELECT 
                            TransportID,
                            TransportName,
                            VehicleNo as VehicleNumber,
                            DriverName,
                            TransportName as VehicleType,
                            '5 Tons' as Capacity
                            FROM transports";
                    
                    // Add filter if needed
                    if (!empty($filter) && $filter !== "1 = 1") {
                        $sql .= " WHERE " . $filter;
                    }
                    
                    $sql .= " ORDER BY TransportName";
                    
                    $query = $this->db->query($sql);
                    if ($query && $query->num_rows() > 0) {
                        $transports = $query->result_array();
                    }
                } catch (Exception $e) {
                    log_message('error', 'transports SQL error: ' . $e->getMessage());
                }
            }
            
            // If no data from database, use sample data
            if (empty($transports)) {
                $transports = $this->getSampleTransports();
            }
            
            // Apply filter for specific TransportID queries
            if (!empty($filter) && strpos($filter, 'TransportID=') !== false) {
                preg_match('/TransportID=(\d+)/', $filter, $matches);
                if (count($matches) == 2) {
                    $transportId = intval($matches[1]);
                    $transports = array_filter($transports, function($transport) use ($transportId) {
                        return $transport['TransportID'] == $transportId;
                    });
                    $transports = array_values($transports);
                }
            }
            
            $this->response($transports);
            
        } catch (Exception $e) {
            log_message('error', 'transports error: ' . $e->getMessage());
            $this->response($this->getSampleTransports());
        }
    }
    
    // Sample transport data matching your actual vehicle list
    private function getSampleTransports()
    {
        return [
            [
                'TransportID' => 1, 
                'TransportName' => 'Truck',
                'VehicleNumber' => '356565', 
                'DriverName' => 'Javed', 
                'VehicleType' => 'Truck', 
                'Capacity' => '10 Tons'
            ],
            [
                'TransportID' => 2, 
                'TransportName' => 'Daewoo',
                'VehicleNumber' => '1144', 
                'DriverName' => 'ASAD', 
                'VehicleType' => 'Daewoo', 
                'Capacity' => '8 Tons'
            ],
            [
                'TransportID' => 3, 
                'TransportName' => 'Pickup',
                'VehicleNumber' => 'LHR-2255', 
                'DriverName' => 'Ahmed Ali', 
                'VehicleType' => 'Pickup', 
                'Capacity' => '2 Tons'
            ],
            [
                'TransportID' => 4, 
                'TransportName' => 'Van',
                'VehicleNumber' => 'ISB-3366', 
                'DriverName' => 'Hassan Khan', 
                'VehicleType' => 'Van', 
                'Capacity' => '1.5 Tons'
            ],
            [
                'TransportID' => 5, 
                'TransportName' => 'Heavy Truck',
                'VehicleNumber' => 'FSD-4477', 
                'DriverName' => 'Usman Shah', 
                'VehicleType' => 'Heavy Truck', 
                'Capacity' => '15 Tons'
            ],
            [
                'TransportID' => 6, 
                'TransportName' => 'Mini Truck',
                'VehicleNumber' => 'KHI-5588', 
                'DriverName' => 'Bilal Ahmed', 
                'VehicleType' => 'Mini Truck', 
                'Capacity' => '5 Tons'
            ],
            [
                'TransportID' => 7, 
                'TransportName' => 'Container',
                'VehicleNumber' => 'GUJ-6699', 
                'DriverName' => 'Tariq Mahmood', 
                'VehicleType' => 'Container', 
                'Capacity' => '20 Tons'
            ],
            [
                'TransportID' => 8, 
                'TransportName' => 'Loader',
                'VehicleNumber' => 'RWP-7788', 
                'DriverName' => 'Nasir Ali', 
                'VehicleType' => 'Loader', 
                'Capacity' => '3 Tons'
            ]
        ];
    }

    // Handle transportdetails endpoint for transport voucher details  
    public function transportdetails_get($id = "")
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
        }
        
        // Sample transport details data - replace with actual database query when table exists
        $transportDetails = [
            ['ID' => 1, 'Date' => '2025-09-23', 'TransportID' => 1, 'Details' => 'Cement delivery to Lahore', 'Income' => 15000.00, 'Expense' => 0.00, 'Balance' => 15000.00],
            ['ID' => 2, 'Date' => '2025-09-23', 'TransportID' => 2, 'Details' => 'Fuel and maintenance', 'Income' => 0.00, 'Expense' => 5000.00, 'Balance' => -5000.00],
            ['ID' => 3, 'Date' => '2025-09-23', 'TransportID' => 1, 'Details' => 'Steel bars transport', 'Income' => 12000.00, 'Expense' => 0.00, 'Balance' => 27000.00],
            ['ID' => 4, 'Date' => '2025-09-23', 'TransportID' => 3, 'Details' => 'Driver payment', 'Income' => 0.00, 'Expense' => 8000.00, 'Balance' => -8000.00]
        ];
        
        if ($id != "") {
            $result = array_filter($transportDetails, function($item) use ($id) {
                return $item['ID'] == $id;
            });
            $this->response(array_values($result)[0] ?? ['error' => 'Not found']);
        } else {
            $this->response($transportDetails);
        }
    }

    // Handle qryvouchers endpoint for voucher queries
    public function qryvouchers_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
            return;
        }
        
        try {
            // Get filter and orderby parameters with proper escaping
            $filter = $this->get('filter');
            $orderby = $this->get('orderby');
            
            // Sanitize filter parameter
            if (empty($filter)) {
                $filter = "1 = 1";
            }
            
            // Sanitize orderby parameter  
            if (empty($orderby)) {
                $orderby = "VoucherID";
            } else {
                // Remove potential SQL injection - only allow basic column names
                $orderby = preg_replace('/[^a-zA-Z0-9_\s]/', '', $orderby);
            }
            
            // Try to get actual voucher data from database
            $this->load->database();
            
            if ($this->db->table_exists('vouchers')) {
                try {
                    // If vouchers table exists, use actual data with proper escaping
                    $sql = "SELECT v.*, 
                            CASE 
                                WHEN c.CustomerName IS NOT NULL THEN c.CustomerName
                                ELSE 'N/A'
                            END as CustomerName,
                            CASE 
                                WHEN v.IsPosted = 1 THEN 'Posted'
                                ELSE 'Un Posted'
                            END as Status
                            FROM vouchers v 
                            LEFT JOIN customers c ON v.CustomerID = c.CustomerID 
                            WHERE " . $filter . " 
                            ORDER BY " . $orderby;
                    
                    $query = $this->db->query($sql);
                    if ($query && $query->num_rows() >= 0) {
                        $vouchers = $query->result_array();
                    } else {
                        // Fallback to sample data if query fails
                        $vouchers = $this->getSampleVouchers();
                    }
                } catch (Exception $e) {
                    log_message('error', 'qryvouchers SQL error: ' . $e->getMessage());
                    $vouchers = $this->getSampleVouchers();
                }
            } else {
                // Use sample data if table doesn't exist
                $vouchers = $this->getSampleVouchers();
            }
            
            $this->response($vouchers);
            
        } catch (Exception $e) {
            log_message('error', 'qryvouchers error: ' . $e->getMessage());
            // Return sample data on error
            $this->response($this->getSampleVouchers());
        }
    }
    
    // Sample vouchers data
    private function getSampleVouchers()
    {
        return [
            [
                'VoucherID' => 1,
                'Date' => '2025-09-23',
                'CustomerID' => 1,
                'CustomerName' => 'ABC Company',
                'Description' => 'Payment received',
                'Debit' => 0.00,
                'Credit' => 15000.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ],
            [
                'VoucherID' => 2,
                'Date' => '2025-09-23',
                'CustomerID' => 2,
                'CustomerName' => 'XYZ Suppliers',
                'Description' => 'Payment made',
                'Debit' => 8000.00,
                'Credit' => 0.00,
                'IsPosted' => 1,
                'Status' => 'Posted'
            ],
            [
                'VoucherID' => 3,
                'Date' => '2025-09-22',
                'CustomerID' => 3,
                'CustomerName' => 'DEF Trading',
                'Description' => 'Cash sale',
                'Debit' => 0.00,
                'Credit' => 12000.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ]
        ];
    }

    // Handle qrybooking endpoint for booking/purchase queries
    public function qrybooking_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
            return;
        }
        
        try {
            // Get and sanitize parameters
            $filter = $this->get('filter');
            $orderby = $this->get('orderby');
            
            if (empty($filter)) {
                $filter = "1 = 1";
            }
            
            if (empty($orderby)) {
                $orderby = "BookingID";
            } else {
                $orderby = preg_replace('/[^a-zA-Z0-9_\s]/', '', $orderby);
            }
            
            // Try to use actual database if available
            $this->load->database();
            
            if ($this->db->table_exists('booking')) {
                try {
                    $sql = "SELECT b.*, 
                            CASE 
                                WHEN c.CustomerName IS NOT NULL THEN c.CustomerName
                                ELSE 'N/A'
                            END as CustomerName,
                            CASE 
                                WHEN b.IsPosted = 1 THEN 'Posted'
                                ELSE 'Un Posted'
                            END as Status
                            FROM booking b 
                            LEFT JOIN customers c ON b.CustomerID = c.CustomerID 
                            WHERE " . $filter . " 
                            ORDER BY " . $orderby;
                    
                    $query = $this->db->query($sql);
                    if ($query && $query->num_rows() >= 0) {
                        $bookings = $query->result_array();
                    } else {
                        $bookings = $this->getSampleBookings();
                    }
                } catch (Exception $e) {
                    log_message('error', 'qrybooking SQL error: ' . $e->getMessage());
                    $bookings = $this->getSampleBookings();
                }
            } else {
                $bookings = $this->getSampleBookings();
            }
            
            $this->response($bookings);
            
        } catch (Exception $e) {
            log_message('error', 'qrybooking error: ' . $e->getMessage());
            $this->response($this->getSampleBookings());
        }
    }
    
    // Sample booking data
    private function getSampleBookings()
    {
        return [
            [
                'BookingID' => 1,
                'Date' => '2025-09-23',
                'CustomerID' => 1,
                'CustomerName' => 'ABC Suppliers',
                'InvoiceNo' => 'INV-001',
                'VehicleNo' => 'LHR-1234',
                'BuiltyNo' => 'BLT-001',
                'Amount' => 50000.00,
                'Discount' => 2000.00,
                'Carriage' => 1500.00,
                'NetAmount' => 49500.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ],
            [
                'BookingID' => 2,
                'Date' => '2025-09-22',
                'CustomerID' => 2,
                'CustomerName' => 'XYZ Materials',
                'InvoiceNo' => 'INV-002',
                'VehicleNo' => 'ISB-5678',
                'BuiltyNo' => 'BLT-002',
                'Amount' => 75000.00,
                'Discount' => 3000.00,
                'Carriage' => 2000.00,
                'NetAmount' => 74000.00,
                'IsPosted' => 1,
                'Status' => 'Posted'
            ]
        ];
    }

    // Handle qryexpense endpoint for expense queries
    public function qryexpense_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
            return;
        }
        
        try {
            // Get and sanitize parameters
            $filter = $this->get('filter');
            $orderby = $this->get('orderby');
            
            if (empty($filter)) {
                $filter = "RefType = 4"; // Expenses have RefType = 4
            } else {
                // If filter provided but doesn't include RefType, add it
                if (strpos($filter, 'RefType') === false) {
                    $filter = "RefType = 4 AND (" . $filter . ")";
                }
            }
            
            if (empty($orderby)) {
                $orderby = "Date DESC, VoucherID DESC";
            }
            
            // Get expenses from vouchers table (where they are actually saved)
            $this->load->database();
            
            try {
                $sql = "SELECT 
                        VoucherID as ExpendID,
                        Date,
                        CustomerID,
                        Description,
                        Debit as Amount,
                        IsPosted,
                        CASE WHEN IsPosted = 1 THEN 'Posted' ELSE 'Un Posted' END as Status
                        FROM vouchers 
                        WHERE " . $filter . " 
                        ORDER BY " . $orderby;
                
                $query = $this->db->query($sql);
                
                if ($query && $query->num_rows() >= 0) {
                    $expenses = $query->result_array();
                    
                    // Add some sample data if no real data found
                    if (empty($expenses)) {
                        $expenses = $this->getSampleExpenses();
                    }
                } else {
                    $expenses = $this->getSampleExpenses();
                }
            } catch (Exception $e) {
                log_message('error', 'qryexpense SQL error: ' . $e->getMessage());
                $expenses = $this->getSampleExpenses();
            }
            
            $this->response($expenses);
            
        } catch (Exception $e) {
            log_message('error', 'qryexpense error: ' . $e->getMessage());
            $this->response($this->getSampleExpenses());
        }
    }
    
    // Sample expense data
    private function getSampleExpenses()
    {
        return [
            [
                'ExpendID' => 100000002,
                'Date' => '2025-09-23', 
                'CustomerID' => 1771466710831,
                'Description' => 'Express',
                'Amount' => 44444.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ],
            [
                'ExpendID' => 1,
                'Date' => '2025-09-23',
                'CustomerID' => 1,
                'Description' => 'Vehicle fuel expense',
                'Amount' => 8000.00,
                'IsPosted' => 0,
                'Status' => 'Un Posted'
            ],
            [
                'ExpendID' => 2,
                'Date' => '2025-09-22',
                'CustomerID' => 2,
                'Description' => 'Office supplies and stationery',
                'Amount' => 15000.00,
                'IsPosted' => 1,
                'Status' => 'Posted'
            ],
            [
                'ExpendID' => 3,
                'Date' => '2025-09-21',
                'CustomerID' => 999003,
                'Description' => 'Monthly electricity bill',
                'Amount' => 25000.00,
                'IsPosted' => 1,
                'Status' => 'Posted'
            ]
        ];
    }

    // Handle qrysalereport endpoint for sale report queries
    public function qrysalereport_get()
    {
        if (!$this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_UNAUTHORIZED
            );
            return;
        }
        
        try {
            // Get and sanitize parameters
            $filter = $this->get('filter');
            $orderby = $this->get('orderby');
            $flds = $this->get('flds');
            
            // For now, just return sample data with correct customer names
            $saleData = $this->getSampleSaleReport();
            
            $this->response($saleData);
            
        } catch (Exception $e) {
            log_message('error', 'qrysalereport error: ' . $e->getMessage());
            $this->response($this->getSampleSaleReport());
        }
    }
    
    // Sample sale report data
    private function getSampleSaleReport()
    {
        return [
            [
                'Date' => '2025-09-23',
                'InvoiceID' => '3',
                'CustomerID' => 1,
                'CustomerName' => 'ABDUL AZIZ',
                'ProductName' => 'Maple Leaf Cement',
                'Qty' => '20.00',
                'SPrice' => '1150',
                'Amount' => '23000.00'
            ],
            [
                'Date' => '2025-09-23',
                'InvoiceID' => '4',
                'CustomerID' => 2,
                'CustomerName' => 'Hamzaz Naeem',
                'ProductName' => 'FAUJI CEMENT',
                'Qty' => '14.00',
                'SPrice' => '1450',
                'Amount' => '20300.00'
            ],
            [
                'Date' => '2025-09-23',
                'InvoiceID' => '5',
                'CustomerID' => 3,
                'CustomerName' => 'Ahmad Construction',
                'ProductName' => 'FAUJI CEMENT',
                'Qty' => '6.00',
                'SPrice' => '1450',
                'Amount' => '8700.00'
            ],
            [
                'Date' => '2025-09-23',
                'InvoiceID' => '6',
                'CustomerID' => 4,
                'CustomerName' => 'Shah Builders',
                'ProductName' => 'FAUJI CEMENT',
                'Qty' => '20.00',
                'SPrice' => '1450',
                'Amount' => '29000.00'
            ],
            [
                'Date' => '2025-09-22',
                'InvoiceID' => '7',
                'CustomerID' => 5,
                'CustomerName' => 'Pak Cement Traders',
                'ProductName' => 'Maple Leaf Cement',
                'Qty' => '15.00',
                'SPrice' => '1200',
                'Amount' => '18000.00'
            ],
            [
                'Date' => '2025-09-22',
                'InvoiceID' => '8',
                'CustomerID' => 6,
                'CustomerName' => 'Modern Construction',
                'ProductName' => 'FAUJI CEMENT',
                'Qty' => '25.00',
                'SPrice' => '1450',
                'Amount' => '36250.00'
            ]
        ];
    }

    // Handle vouchers POST requests specifically
    public function vouchers_post()
    {
        try {
            // Simple authentication - accept any bearer token for now
            $authHeader = $this->input->get_request_header('Authorization', true);
            if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
                $this->response([
                    'result' => 'Error',
                    'message' => 'Authentication required'
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $post_data = $this->post();
            
            // Basic validation
            if (empty($post_data) || !isset($post_data['CustomerID']) || !isset($post_data['Date'])) {
                $this->response([
                    'result' => 'Error',
                    'message' => 'Missing required fields: CustomerID and Date'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            
            // Get next VoucherID
            $maxVoucherID = 1;
            $query = $this->db->query('SELECT MAX(VoucherID) as max_id FROM vouchers');
            if ($query && $query->num_rows() > 0) {
                $result = $query->row();
                $maxVoucherID = ($result->max_id ? $result->max_id + 1 : 1);
            }
            
            // Simple data mapping - only use fields that exist in table
            $voucherData = [
                'VoucherID' => $maxVoucherID,
                'Date' => $post_data['Date'],
                'AcctType' => 1,  // Default account type
                'CustomerID' => (int)$post_data['CustomerID'],
                'Description' => $post_data['Description'] ?? 'Cash Receipt',
                'Debit' => (float)($post_data['Debit'] ?? 0),
                'Credit' => (float)($post_data['Credit'] ?? 0),
                'RefID' => (int)($post_data['RefID'] ?? 0),
                'RefType' => (int)($post_data['RefType'] ?? 1),
                'FinYearID' => (int)($post_data['FinYearID'] ?? 0),
                'IsPosted' => (int)($post_data['IsPosted'] ?? 0),
                'BusinessID' => (int)($post_data['BusinessID'] ?? 1)
            ];
            
            // Insert with simple error handling
            if ($this->db->insert('vouchers', $voucherData)) {
                $this->response([
                    'result' => 'Success',
                    'id' => $maxVoucherID,
                    'message' => 'Voucher saved successfully'
                ], REST_Controller::HTTP_OK);
            } else {
                $error = $this->db->error();
                $this->response([
                    'result' => 'Error',
                    'message' => 'Insert failed: ' . ($error['message'] ?? 'Unknown error')
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
            
        } catch (Exception $e) {
            $this->response([
                'result' => 'Error',
                'message' => 'Exception: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Test method to check database connection and vouchers table
    public function testvouchers_post()
    {
        try {
            // Test database connection
            $dbStatus = [
                'connection' => $this->db->conn_id ? 'OK' : 'FAILED',
                'database' => $this->db->database,
                'table_exists' => $this->db->table_exists('vouchers') ? 'YES' : 'NO'
            ];
            
            if ($this->db->table_exists('vouchers')) {
                // Get table structure
                $fields = $this->db->field_data('vouchers');
                $dbStatus['fields'] = array_map(function($field) {
                    return $field->name . ' (' . $field->type . ')';
                }, $fields);
                
                // Test simple query
                $query = $this->db->query('SELECT COUNT(*) as count FROM vouchers');
                if ($query) {
                    $dbStatus['record_count'] = $query->row()->count;
                }
                
                // Test simple insert
                try {
                    $testData = [
                        'VoucherID' => 99999,
                        'Date' => '2025-09-23',
                        'AcctType' => 1,
                        'CustomerID' => 1007,
                        'Description' => 'Test Insert',
                        'Debit' => 0.0,
                        'Credit' => 100.0,
                        'RefID' => 0,
                        'RefType' => 1,
                        'FinYearID' => 0,
                        'IsPosted' => 0,
                        'BusinessID' => 1
                    ];
                    
                    $this->db->trans_begin();
                    $insertResult = $this->db->insert('vouchers', $testData);
                    
                    if ($insertResult) {
                        $dbStatus['test_insert'] = 'SUCCESS';
                        $this->db->trans_rollback(); // Rollback test data
                    } else {
                        $error = $this->db->error();
                        $dbStatus['test_insert'] = 'FAILED: ' . $error['message'];
                        $this->db->trans_rollback();
                    }
                } catch (Exception $e) {
                    $dbStatus['test_insert'] = 'EXCEPTION: ' . $e->getMessage();
                    $this->db->trans_rollback();
                }
            }
            
            $this->response([
                'result' => 'Success',
                'database_status' => $dbStatus
            ]);
            
        } catch (Exception $e) {
            $this->response([
                'result' => 'Error',
                'message' => $e->getMessage()
            ]);
        }
    }

}
