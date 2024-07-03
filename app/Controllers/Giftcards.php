<?php
namespace App\Controllers;

use App\Controllers\SecureController;
use App\Libraries\AppData;
use App\Libraries\Barcode_lib;
use App\Libraries\Gu;
use App\Models\Appconfig;
use App\Models\Employee;
use App\Models\Giftcard;
use App\Models\Module;
use Config\Services;
use Config\Database;
use CodeIgniter\Language\Language;

class Giftcards extends SecureController
{
    protected $giftcardModel;
    protected $lang;
    protected $appData;
    protected $gdata;
    protected $gu;
    public function __construct()
    {

        helper('locale');
        parent::__construct('giftcards');
        $this->giftcardModel = new Giftcard();
        $this->lang = new Language('en');
        $this->gu = new Gu();


    }

    public function index($module_id = null)
    {
        $data = $this->data;
        $data['table_headers'] = $this->xss_clean(get_giftcards_manage_table_headers());
        return view('giftcards/manage', $data);
    }
    /*
       Returns Giftcards table data rows. This will be called with AJAX.
       */
    // public function search()
    // {

    // 	$search = $this->request->getVar('search');
    // 	$limit_from = $this->request->getVar('limit');
    // 	$offset = $this->request->getVar('offset');
    // 	$sort = 'giftcard_id';
    // 	$order = 'asc';
    // 	$rows = 10;
    // 	$total_rows=0;

    //    if($search=='')
    //    {
    // 	$giftcardsdata=$this->giftcardModel->getData()->getResult();
    // 	$data_rows=[];
    // 	foreach($giftcardsdata as $giftcard)
    // 	{	
    // 		// echo "<pre>";
    // 		// print_r($giftcard);
    // 		// echo "</pre>";
    // 		$data_rows[] = get_giftcard_data_row($giftcard, $this);
    // 		$total_rows++;
    // 	}
    // 	$data_rows = $this->xss_clean($data_rows);
    // 	echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
    //    }
    //    else
    //    {
    // 	$giftcards = $this->giftcardModel->search($search,$limit_from,$offset, $sort, $order);

    // 	$total_rows = $this->giftcardModel->get_found_rows($search);

    // 	$data_rows=[];
    // 	foreach($giftcards->getResult() as $giftcard)
    // 	{	
    // 		// echo "<pre>";
    // 		// print_r($giftcard);
    // 		// echo "</pre>";
    // 		$data_rows[] = get_giftcard_data_row($giftcard, $this);
    // 	}



    // 	$data_rows = $this->xss_clean($data_rows);

    // 	echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
    // }
    // }
    public function search()
    {
        $search = $this->request->getGet('search');
        $limit = $this->request->getGet('limit');
        $offset = $this->request->getGet('offset');
        $sort = $this->request->getGet('sort') ?? 'giftcard_number'; // Provide a default value if $sort is null
        $order = $this->request->getGet('order') ?? 'asc'; // Provide a default value if $order is null

        // If limit and offset are not provided in the request, set default values
        $limit = ($limit !== null) ? $limit : 10;
        $offset = ($offset !== null) ? $offset : 0;

        // Check if search term is empty, if so, set it to an empty string
        $search = ($search !== null) ? $search : '';
        $giftcards = $this->giftcardModel->search($search, $limit, $offset, $sort, $order);

        $total_rows = $this->giftcardModel->get_found_rows($search);

        $data_rows = [];
        foreach ($giftcards->getResult() as $giftcard) {
            // echo "<pre>";
            // print_r($giftcard);
            // echo "</pre>";
            $data_rows[] = get_giftcard_data_row($giftcard, $this);
        }



        $data_rows = $this->xss_clean($data_rows);

        echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));

    }
    /*
       Gives search suggestions based on what is being searched for
       */
    public function suggest_search()
    {
        $suggestions = $this->xss_clean($this->Giftcard->get_search_suggestions($this->request->getPost('term')));

        echo json_encode($suggestions);
    }

    public function get_row($row_id)
    {
        $data_row = $this->xss_clean(get_giftcard_data_row($this->giftcardModel->get_info($row_id), $this));

        echo json_encode($data_row);
    }

    public function check_giftcard_number()
    {
        $exists = $this->giftcardModel->giftcard_number_exists($this->request->getPost('giftcard_number'), $this->request->getPost('giftcard_id'));
        echo !$exists ? 'true' : 'false';
    }

    /**
     * Sync db giftcard
     */
    // public function sync_giftcards()
    // {
    //     $data = array();
    //     $data['success'] = shell_exec('php index.php cli sync_giftcards');

    //     // if (!$this->input->is_ajax_request()) {
    //     //     $this->index($data);
    //     // }
    // 	if (!$this->request->isAJAX()) {
    // 		return $this->index($data);
    // 	}

    //     echo $data['success'];

    // }

    public function view($giftcard_id = -1, $module_id = null)
    {
        $giftcard_info = $this->giftcardModel->get_info($giftcard_id);

        $data['selected_person_name'] = ($giftcard_id > 0 && isset($giftcard_info->person_id)) ? $giftcard_info->first_name . ' ' . $giftcard_info->last_name : '';
        $data['selected_person_id'] = $giftcard_info->person_id;
        $data['giftcard_number'] = $giftcard_id > 0 ? $giftcard_info->giftcard_number : '';
        $data['giftcard_id'] = $giftcard_id;
        $data['giftcard_value'] = $giftcard_info->value;
        $data['giftcard_expires'] = $giftcard_info->expires_at;
        $data['giftcard_status'] = $giftcard_info->status;
        $this->appData = $this->appconfigModel->get_all();
        $this->employeeModel = new Employee();
        $this->module = new Module();
        $data['appData'] = $this->appData;
        $data['controller_name'] = 'giftcards';
        $data = $this->xss_clean($data);

        return view("giftcards/form", $data);
    }

    public function save($giftcard_id = -1)
    {

        $giftcard_data = array(
            'record_time' => date('Y-m-d H:i:s'),
            'giftcard_number' => $this->request->getPost('giftcard_number'),
            'value' => parse_decimals($this->request->getPost('value')),
            'expires_at' => $this->request->getPost('expires_at'),
            'person_id' => $this->request->getPost('person_id') == '' ? NULL : $this->request->getPost('person_id')
        );

        if ($this->giftcardModel->saveGiftcard($giftcard_data, $giftcard_id)) {
            $giftcard_data = $this->xss_clean($giftcard_data);

            //New giftcard
            if ($giftcard_id == -1) {

                echo json_encode(array('success' => TRUE, 'message' => lang('giftcards_lang.giftcards_successful_adding') . ' ' . $giftcard_data['giftcard_number'], 'id' => $giftcard_id));
            } else //Existing giftcard
            {
                // session()->setFlashdata('reload', true);
                echo json_encode(array('success' => TRUE, 'message' => lang('giftcards_lang.giftcards_successful_updating') . ' ' . $giftcard_data['giftcard_number'], 'id' => $giftcard_id));
                header("Location: /giftcards");


            }
        } else //failure
        {
            $giftcard_data = $this->xss_clean($giftcard_data);


            echo json_encode(array('success' => FALSE, 'message' => lang('giftcards_lang.giftcards_error_adding_updating') . ' ' . $giftcard_data['giftcard_number'], 'id' => $giftcard_id));


        }
    }

    public function delete()
    {

        $giftcards_to_delete = $this->xss_clean($this->request->getPost('ids'));

        if ($this->giftcardModel->delete_list($giftcards_to_delete)) {
            echo json_encode(
                array(
                    'success' => TRUE,
                    'message' => lang('giftcards_lang.giftcards_successful_deleted') . ' ' .
                        count($giftcards_to_delete) . ' ' . lang('giftcards_lang.giftcards_one_or_multiple')
                )
            );
        } else {
            echo json_encode(array('success' => FALSE, 'message' => lang('giftcards_lang.giftcards_cannot_be_deleted')));
        }
    }

    /*
       Items import from excel spreadsheet
       */
    public function excel()
    {
        $name = 'import_items.csv';
        $data = file_get_contents('../' . $name);
        $this->response->download($name, $data);
    }

    public function excel_import()
    {
        return view('giftcards/form_excel_import');
    }

    public function do_excel_import()
    {
        if ($_FILES['file_path']['error'] != UPLOAD_ERR_OK) {
            echo json_encode(array('success' => FALSE, 'message' => $this->lang->getLine('items_excel_import_failed')));
        } else {
            $Employee = new Employee();
            if (($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE) {
                // Skip the first row as it's the table description
                fgetcsv($handle);
                $i = 1;

                $failCodes = array();
                $employee_id = $Employee->get_logged_in_employee_info()->person_id;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // XSS file data sanity check
                    $data = $this->xss_clean($data);

                    if (sizeof($data) >= 1) {

                        $date = date('y-m-d 23:59:59', strtotime($data[2]));

                        $item_data = array(
                            'expires_at' => $date,
                            'giftcard_number' => $data[0],
                            'value' => parse_decimals($data[1]),
                            // 'person_id' => $data[0] == '' ? NULL : $data[0]
                        );
                        $invalidated = FALSE;
                    } else {
                        $invalidated = TRUE;
                    }

                    if (!$invalidated && $this->Giftcard->save($item_data)) {

                        $comment = 'Qty CSV Imported';

                    } else //insert or update item failure
                    {
                        $failCodes[] = $i;
                    }

                    ++$i;
                }

                if (count($failCodes) > 0) {
                    $message = $this->lang->getLine('items_excel_import_partially_failed') . ' (' . count($failCodes) . ') ';

                    echo json_encode(array('success' => FALSE, 'message' => $message));
                } else {
                    echo json_encode(array('success' => TRUE, 'message' => $this->lang->getLine('items_excel_import_success')));
                }
            } else {
                echo json_encode(array('success' => FALSE, 'message' => $this->lang->getLine('items_excel_import_nodata_wrongformat')));
            }
        }
    }

    public function generate_barcodes($giftcard_ids)
    {
        $Appconfig = new Appconfig();
        $barcode_lib = new Barcode_lib();
        // $this->load->library('barcode_lib');

        $giftcard_ids = explode(':', $giftcard_ids);

        $result = $this->Giftcard->get_multiple_info($giftcard_ids)->getResultArray();
        $config = $barcode_lib->get_barcode_config();
        $config['barcode_type'] = 'Code128';
        $data['barcode_config'] = $config;

        // check the list of items to see if any item_number field is empty
        foreach ($result as &$item) {
            $item = $this->xss_clean($item);
            $item['name'] = 'Gift Voucher';
            $item['item_id'] = $item['giftcard_number'];
            $item['unit_price'] = '';
            // update the UPC/EAN/ISBN field if empty / NULL with the newly generated barcode
            if (empty($item['giftcard_number']) && $Appconfig->get('barcode_generate_if_empty')) {
                // get the newly generated barcode
                $barcode_instance = Barcode_lib::barcode_instance($item, $config);
                $item['giftcard_number'] = $barcode_instance->getData();

                $save_item = array('giftcard_number' => $item['giftcard_number']);

                // update the item in the database in order to save the UPC/EAN/ISBN field
                $this->Giftcard->save($save_item, $item['giftcard_id']);
            }
        }
        $data['items'] = $result;

        // display barcodes
        return View('barcodes/giftcards_barcode_sheet', $data);
    }



    // public function sync_giftcards()
    // {
    //   try {
    // 	$gu=new Gu();

    // 	if (!$this->request->isAJAX()) {
    // 		return $this->index($data);
    // 	}
    //     else

    // 	{


    //     // $isOnlineServer = $gu->isServer();

    //     // if($isOnlineServer)
    //     // {
    //     //     echo "Sync not needed here. ";
    //     //     exit;
    //     // }

    //     // $local = Database::connect('default');;
    //     // $online = Database::connect('online');;


    //     // if (FALSE === $online->conn_id) {
    //     //     echo $log = date('d M Y h:i A') . " can't connect to online server. ";

    //     //     file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
    //     //     exit;
    //     // }


    //     $onlineDB = Database::connect('online')->table('giftcards');
    // $localDB = Database::connect('default')->table('giftcards');

    // $online_giftcards = $onlineDB->get()->getResult();
    // $local_giftcards = $localDB->get()->getResult();

    // $onlineJson = json_encode($online_giftcards);
    // $localJson = json_encode($local_giftcards);
    // $diff = strcmp($localJson, $onlineJson);

    // if ($diff) {

    //     // echo "Giftcard update available. Updating giftcards record... ";

    //     try {
    //         // Truncate local giftcards table
    //         $localDB->transStart();

    //         $localDB->query("SET FOREIGN_KEY_CHECKS = 0");
    //         $localDB->truncate();

    //         // Adding data to the giftcards table
    //         foreach ($online_giftcards as $item) {
    //             if (!$localDB->insert($item)) {
    //                 echo "Item error. Sync failed.";
    //                 return false;
    //             }
    //         }

    //         $localDB->query("SET FOREIGN_KEY_CHECKS = 1");

    //         // Close transaction
    //         $localDB->transComplete();
    //         echo "Giftcard updated successfully.";
    //     } catch (Exception $e) {
    //         $localDB->transRollback();
    //         $localDB->query("SET FOREIGN_KEY_CHECKS = 1");
    //         echo "Giftcard sync process failed. <br/>";
    //         return false;
    //     }
    // } else {
    //     echo "Giftcards are already in sync. No updates required.";
    // }

    //   } 
    // }
    // //   catch (\Throwable $th) {
    // // 	echo "Giftcard database already up to date.";
    // // 	return true;
    // //   }
    // catch (\Throwable $th) {
    // 	echo "Error: " . $th->getMessage();
    // 	return false;
    // }
    // }


    public function sync_giftcards()
    {
        try {
            $gu = new Gu();

            if (!$this->request->isAJAX()) {
                return $this->index($data);
            } else {

                // $isOnlineServer = $gu->isServer();

                // if($isOnlineServer)
                // {
                //     echo "Sync not needed here. ";
                //     exit;
                // }

                // $local = Database::connect('default');
                // $online = Database::connect('online');

                // if (FALSE === $online->conn_id) {
                //     echo $log = date('d M Y h:i A') . " can't connect to the online server. ";
                //     file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
                //     exit;
                // }

                $onlineDB = \Config\Database::connect('online');
                $localDB = \Config\Database::connect();

                $online_giftcards = $onlineDB->table('giftcards')->get()->getResult();
                $local_giftcards = $localDB->table('giftcards')->get()->getResult();

                $onlineJson = json_encode($online_giftcards);
                $localJson = json_encode($local_giftcards);
                $diff = strcmp($localJson, $onlineJson);

                if ($diff) {
                    // echo "Giftcard update available. Updating giftcards record... ";

                    // Start transaction for the local database
                    $localDB->transStart();

                    $localDB->query("SET FOREIGN_KEY_CHECKS = 0");
                    $localDB->table('giftcards')->truncate();

                    // Adding data to the giftcards table
                    foreach ($online_giftcards as $item) {
                        if (!$localDB->table('giftcards')->insert($item)) {
                            echo "Item error. Sync failed.";
                            $localDB->transRollback();
                            $localDB->query("SET FOREIGN_KEY_CHECKS = 1");
                            return false;
                        }
                    }

                    $localDB->query("SET FOREIGN_KEY_CHECKS = 1");

                    // Commit transaction
                    $localDB->transComplete();
                    echo "Giftcard updated successfully.";
                } else {
                    echo "Giftcards are already in sync. No updates required.";
                }
            }
        } catch (\Throwable $th) {
            echo "Error: " . $th->getMessage();
            return false;
        }
    }

}

?>