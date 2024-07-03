<?php
namespace App\Controllers;  
use App\Controllers\SecureController;
use App\Libraries\BarcodeLib;
use App\Libraries\Gu;
use App\Libraries\ItemLib;
use App\Models\Appconfig;
use App\Models\Raw_inventory;
use App\Models\Stock_location;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Raw_item;
use App\Models\Raw_item_quantity;
class Raw_items extends SecureController
{
    protected $Stock_location;
    protected $Employee;
    protected $item_lib;
    protected $Raw_item;
    protected $Raw_item_quantity;
    public function __construct()
    {
        parent::__construct('raw_items');
        $this->Stock_location = new Stock_location();
        $this->Employee = new Employee();  
        $this->item_lib = new ItemLib();
        $this->Raw_item = new Raw_item();
        $this->Raw_item_quantity = new Raw_item_quantity(); 
        
    }

    public function index($data = null)
    {
        $data = $this->data;
        $data['table_headers'] = $this->xss_clean(get_raw_items_manage_table_headers());
        $data['stock_locations'] = $this->xss_clean($this->Stock_location->get_allowed_locations('raw_items'));
        $data['filters'] = array('empty_upc' => lang('items_lang.items_empty_upc_items'),
            'low_inventory' => lang('items_lang.items_low_inventory_items'),
            'no_description' => lang('items_lang.items_no_description_items'),
            'search_custom' => lang('items_lang.items_search_custom_items'),
            'is_deleted' => lang('items_lang.items_is_deleted'));
        return view('raw_items/manage', $data);
    }

    /**
     * Sync db
     */
    // public function sync()
    // {
    //     $data = array();
    //     $data['success'] = shell_exec('php index.php cli sync');

    //     if (!$this->input->is_ajax_request()) {
    //         $this->index($data);
    //     }

    //     echo $data['success'];

    // }


    /*
    Returns Items table data rows. This will be called with AJAX.
    */
    public function search()
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        $search = request()->getGet('search');
        $limit = request()->getGet('limit');
        $offset = request()->getGet('offset');
        $sort = request()->getGet('sort');
        $order = request()->getGet('order');
        $search = ($search !== null) ? $search : '';
        $sort = ($sort !== null) ? $sort : 'raw_items.name';

        $this->item_lib->set_item_location(request()->getGet('stock_location'));

        $filters = array('start_date' => request()->getGet('start_date'),
            'end_date' => request()->getGet('end_date'),
            'stock_location_id' => $this->item_lib->get_item_location(),
            'empty_upc' => FALSE,
            'low_inventory' => FALSE,
            'no_description' => FALSE,
            'search_custom' => FALSE,
            'is_deleted' => FALSE);

        // check if any filter is set in the multiselect dropdown
        $filledup = array_fill_keys(request()->getGet('filters'), TRUE);
        $filters = array_merge($filters, $filledup);

        // vendor 0 -> to get warehouse products only from raw items db
        $vendor = 0;

        $items = $this->Raw_item->search($search, $filters, $employee_id, $vendor, $limit, $offset, $sort, $order);
        $total_rows = $this->Raw_item->get_found_rows($search, $filters, $employee_id, $vendor);

        $data_rows = array();
        foreach ($items->getResult() as $item) {
            $data_rows[] = $this->xss_clean(get_raw_item_data_row($item, $this));
        }

        echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
    }

    public function pic_thumb($pic_id)
    {
        $this->load->helper('file');
        $this->load->library('image_lib');
        $base_path = "./uploads/item_pics/" . $pic_id;
        $images = glob($base_path . ".*");
        if (sizeof($images) > 0) {
            $image_path = $images[0];
            $ext = pathinfo($image_path, PATHINFO_EXTENSION);
            $thumb_path = $base_path . $this->image_lib->thumb_marker . '.' . $ext;
            if (sizeof($images) < 2) {
                $config['image_library'] = 'gd2';
                $config['source_image'] = $image_path;
                $config['maintain_ratio'] = TRUE;
                $config['create_thumb'] = TRUE;
                $config['width'] = 52;
                $config['height'] = 32;
                $this->image_lib->initialize($config);
                $image = $this->image_lib->resize();
                $thumb_path = $this->image_lib->full_dst_path;
            }
            $this->output->set_content_type(get_mime_by_extension($thumb_path));
            $this->output->set_output(file_get_contents($thumb_path));
        }
    }

    /*
    Gives search suggestions based on what is being searched for
    */
    // public function suggest_search()
    // {
    //     $suggestions = $this->xss_clean($this->Raw_item->get_search_suggestions($this->input->post_get('term'),
    //         array('search_custom' => $this->input->post('search_custom'), 'is_deleted' => $this->input->post('is_deleted') != NULL), FALSE));

    //     echo json_encode($suggestions);
    // }

    // public function suggest()
    // {
    //     $suggestions = [];
    //     if($this->input->post_get('category')==2){
    //         $suggestions = $this->xss_clean($this->Raw_item->get_warehouse_search_suggestions($this->input->post_get('person_id'),$this->input->post_get('term'),
    //         array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
    //     }elseif($this->input->post_get('category')==1){
    //         $suggestions = $this->xss_clean($this->Raw_item->get_vendor_search_suggestions($this->input->post_get('person_id'),$this->input->post_get('term'),
    //         array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
    //     }
    //     echo json_encode($suggestions);
    // }

    /*
    Gives search suggestions based on what is being searched for
    */
    public function suggest_category()
    {
        $suggestions = $this->xss_clean($this->Raw_item->get_category_suggestions(request()->getGet('term')));

        echo json_encode($suggestions);
    }

    /*
     Gives search suggestions based on what is being searched for
    */
    public function suggest_location()
    {
        $suggestions = $this->xss_clean($this->Raw_item->get_location_suggestions(request()->getGet('term')));

        echo json_encode($suggestions);
    }

    /*
     Gives search suggestions based on what is being searched for
    */
    public function suggest_custom()
    {
        $suggestions = $this->xss_clean($this->Raw_item->get_custom_suggestions(request()->getPost('term'), request()->getPost('field_no')));

        echo json_encode($suggestions);
    }

    public function get_row($item_ids)
    {
        $item_infos = $this->Raw_item->get_multiple_info(explode(":", $item_ids), $this->item_lib->get_item_location());

        $result = array();
        foreach ($item_infos->getResult() as $item_info) {
            $result[$item_info->item_id] = $this->xss_clean(get_item_data_row($item_info, $this));
        }

        echo json_encode($result);
    }

    public function view($item_id = -1)
    {

        $item_info = $this->Raw_item->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }

        $data['item_info'] = $item_info;
        
        $data['logo_exists'] = ($item_info->pic_id != '') ? true : false;
        if($data['logo_exists']){
            $images = glob("./uploads/item_pics/" . $item_info->pic_id . ".*");
        }
        else{
            $images = [];
        }
        $data['image_path'] = sizeof($images) > 0 ? base_url($images[0]) : '';

        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);

            $quantity = $this->xss_clean($this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id'])->quantity);
            $quantity = ($item_id == -1) ? 0 : $quantity;
            $location_array[$location['location_id']] = array('location_name' => $location['location_name'], 'quantity' => $quantity);
            $data['stock_locations'] = $location_array;
        }
        $data['appData'] = $this->appconfigModel->get_all();
        $data['gu'] = new Gu();
        $data['controller_name'] = 'raw_items';
        return  view('raw_items/form', $data);
    }

    public function inventory($item_id = -1)
    {
        $item_info = $this->Raw_item->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }
        $data['item_info'] = $item_info;

        $data['stock_locations'] = array();
        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);
            $quantity = $this->xss_clean($this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id'])->quantity);

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }

        return view('raw_items/form_inventory', $data);
    }

    public function count_details($item_id = -1)
    {
        $item_info = $this->Raw_item->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }
        $data['item_info'] = $item_info;

        $data['stock_locations'] = array();
        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);
            $quantity = $this->xss_clean($this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id'])->quantity);

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }

        $Raw_inventory = new Raw_inventory();
        $data['raw_inventory_data'] = $Raw_inventory->get_inventory_data_for_item($item_info->item_id)->getResultArray();
        $data['Employee'] = new Employee();
        // $data['employee_name'] = array();

		// foreach($data['raw_inventory_data'] as $row)
		// {
		// 	$employee = $this->Employee->get_info($row['trans_user']);
		// 	array_push($data['employee_name'], $employee->first_name . ' ' . $employee->last_name);   
		// }
        return view('raw_items/form_count_details', $data);
    }


    public function generate_barcodes($item_ids)
    {
        $barcodeLib = new BarcodeLib();
        $itemLib = new ItemLib();
        $itemModel = new Item();
        $item_loc = $itemLib->get_item_location();
        $appConfig = new Appconfig();
        $appData = $appConfig->get_all();

        $itemIds = explode(':', $item_ids);
        $result = $itemModel->get_multiple_info($itemIds, $item_loc);
        $barcodeConfig = $barcodeLib->get_barcode_config();

        $data['barcodeConfig'] = $barcodeConfig;

        // Check the list of items to see if any item_number field is empty
        foreach ($result->getResultArray() as &$item) {

            // Update the UPC/EAN/ISBN field if empty / NULL with the newly generated barcode
            if (empty($item->item_number) && $appData['barcode_generate_if_empty']) {
                // Get the newly generated barcode
                $barcodeInstance = BarcodeLib::barcode_instance($item, $barcodeConfig);
                $item['item_number'] = $barcodeInstance->getData();

                $saveItem = ['item_number' => $item['item_number']];

                // Update the item in the database in order to save the UPC/EAN/ISBN field
                $itemModel->saveItem($saveItem, $item['item_id']);
            }
        }
        $data['items'] = $result->getResultArray();
        $data['barcodeLib'] = $barcodeLib;

        // Display barcodes
        return view('barcodes/barcode_sheet', $data);
    }


    public function bulk_edit()
    {
        $data['allow_alt_description_choices'] = array(
            '' => lang('items_lang.items_do_nothing'),
            1 => lang('items_lang.items_change_all_to_allow_alt_desc'),
            0 => lang('items_lang.items_change_all_to_not_allow_allow_desc'));
            $data['appData'] = $this->appconfigModel->get_all();
            $data['gu'] = new Gu();
        return view('raw_items/form_bulk', $data);
    }

    public function save($item_id = -1)
    {
        $file = $this->request->getFile('item_image');
        $upload_success = $this->_handle_image_upload();
             if(!empty($file)){
                 $upload_data = $file->move($upload_success['upload_path'], $upload_success['file_name'].'.'.$file->getClientExtension());
                 $item_data['pic_id'] = $upload_success['file_name'];
             }
       
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        //Save item data
        $item_data = array(
            'name' => request()->getPost('name'),
            'description' => request()->getPost('description'),
            'category' => request()->getPost('category'),
            'person_id' => $employee_id,
            'item_number' => request()->getPost('item_number') == '' ? NULL : request()->getPost('item_number'),
            'cost_price' => parse_decimals(request()->getPost('cost_price')),
            'reorder_level' => parse_decimals(request()->getPost('reorder_level')),
            'allow_alt_description' => request()->getPost('allow_alt_description') != NULL,
            'deleted' => request()->getPost('is_deleted') != NULL,
            'custom2' => request()->getPost('custom2') == NULL ? '' : request()->getPost('custom2'),
            'custom10' => request()->getPost('custom10') == NULL ? '' : request()->getPost('custom10'),
            'item_type' => 2,
            'item_processed' => 0,
            'item_attributes' => 0,
        );

        // item type 2 is for warehouse items only
        // item processed 1 if for processed items only 
        // item attributes 1 is for sub type items only
        
        $cur_item_info = $this->Raw_item->get_info($item_id);

        if ($this->Raw_item->save_raw_item($item_data, $item_id)) {
            $success = TRUE;
            $new_item = FALSE;
            //New item
            if ($item_id == -1) {
                $item_id = $item_data['item_id'];
                $new_item = TRUE;
            }

            //Save item quantity
            $stock_locations = $this->Stock_location->get_undeleted_all('raw_items')->getResultArray();
            foreach ($stock_locations as $location) {
                $updated_quantity = parse_decimals(request()->getPost('quantity_' . $location['location_id']));
                $location_detail = array('item_id' => $item_id,
                    'location_id' => $location['location_id'],
                    'quantity' => $updated_quantity);
                $item_quantity = $this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id']);
                if ($item_quantity->quantity != $updated_quantity || $new_item) {
                    $success &= $this->Raw_item_quantity->save_raw_item_quantity($location_detail, $item_id, $location['location_id']);

                    $inv_data = array(
                        'trans_date' => date('Y-m-d H:i:s'),
                        'trans_items' => $item_id,
                        'trans_user' => $employee_id,
                        'trans_location' => $location['location_id'],
                        'trans_comment' => ($new_item) ? lang('raw_items_lang.raw_items_manually_editing_of_quantity') : lang('raw_items_lang.raw_items_ordered_editing_of_quantity'),
                        'trans_inventory' => $updated_quantity - $item_quantity->quantity
                    );
                    $Raw_inventory = new Raw_inventory();
                    $success &= $Raw_inventory->insert_raw_inventory($inv_data);
                }
            }

            if ($success && $upload_success) {
                $message = $this->xss_clean(lang('items_lang.items_successful_' . ($new_item ? 'adding' : 'updating')) . ' ' . $item_data['name']);

                echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
            } else {
                $message = $this->xss_clean($upload_success ? lang('items_lang.items_error_adding_updating') . ' ' . $item_data['name'] : 'Error in uploading image.');

                echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => $item_id));
            }
        } else//failure
        {
            $message = $this->xss_clean(lang('items_lang.items_error_adding_updating') . ' ' . $item_data['name']);

            echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => -1));
        }
    }

    public function check_item_number()
    {
        $exists = $this->Raw_item->item_number_exists(request()->getPost('item_number'), request()->getPost('item_id'));
        echo !$exists ? 'true' : 'false';
    }

    private function handle_image_upload()
    {
        $this->load->helper('directory');

        $map = directory_map('./uploads/item_pics/', 1);

        // load upload library
        $config = array('upload_path' => './uploads/item_pics/',
            'allowed_types' => 'gif|jpg|jpeg|png',
            'max_size' => '100',
            'max_width' => '640',
            'max_height' => '480',
            'file_name' => sizeof($map) + 1
        );
        $this->load->library('upload', $config);
        $this->upload->do_upload('item_image');

        return strlen($this->upload->display_errors()) == 0 || !strcmp($this->upload->display_errors(), '<p>' . $this->lang->line('upload_no_file_selected') . '</p>');
    }
    private function _handle_image_upload()
    {
        // Load the File helper (if not autoloaded)
        helper(['filesystem', 'url']);

        $map = directory_map('./uploads/item_pics/', 1);
        $file = $this->request->getFile('item_image');
        if($file){
         $newName = sizeof($map) + 1;
         $config['upload_path'] = './uploads/item_pics/';
        //  $config['allowed_types'] = 'gif|jpg|jpeg|png';
        //  $config['max_size'] = 100;
        //  $config['max_width'] = 640;
        //  $config['max_height'] = 480;
         $config['file_name'] = $newName;
         if ($file->isValid() && !$file->hasMoved()) {
                return $config; 
              } else {
                return false;
              }

         } else {
           return true;
         }
       
    }

    public function remove_logo($item_id)
    {
        $item_data = array('pic_id' => NULL);
        $result = $this->Raw_item->save_raw_item($item_data, $item_id);

        echo json_encode(array('success' => $result));
    }

    public function save_inventory($item_id = -1)
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $cur_item_info = $this->Raw_item->get_info($item_id);
        $location_id = request()->getPost('stock_location');
        $inv_data = array(
            'trans_date' => date('Y-m-d H:i:s'),
            'trans_items' => $item_id,
            'trans_user' => $employee_id,
            'trans_location' => $location_id,
            'trans_comment' => request()->getPost('trans_comment'),
            'trans_inventory' => parse_decimals(request()->getPost('newquantity'))
        );
        $Raw_inventory = new Raw_inventory();
        $Raw_inventory->insert_raw_inventory($inv_data);

        //Update stock quantity
        $item_quantity = $this->Raw_item_quantity->get_item_quantity($item_id, $location_id);
        $item_quantity_data = array(
            'item_id' => $item_id,
            'location_id' => $location_id,
            'quantity' => $item_quantity->quantity + parse_decimals(request()->getPost('newquantity'))
        );

        if ($this->Raw_item_quantity->save_raw_item_quantity($item_quantity_data, $item_id, $location_id)) {
            $message = $this->xss_clean(lang('items_lang.items_successful_updating') . ' ' . $cur_item_info->name);

            echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
        } else//failure
        {
            $message = $this->xss_clean(lang('items_lang.items_error_adding_updating') . ' ' . $cur_item_info->name);

            echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => -1));
        }
    }

    public function bulk_update()
    {
        $items_to_update = request()->getPost('item_ids');
        $item_data = array();

        foreach ($_POST as $key => $value) {
            //This field is nullable, so treat it differently
            if ($value != '' && !(in_array($key, array('item_ids')))) {
                $item_data["$key"] = $value;
            }
        }

        //Item data could be empty if tax information is being updated
        if (empty($item_data) || $this->Raw_item->update_multiple($item_data, $items_to_update)) {

            echo json_encode(array('success' => TRUE, 'message' => lang('items_lang.items_successful_bulk_edit'), 'id' => $this->xss_clean($items_to_update)));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_error_updating_multiple')));
        }
    }

    public function delete()
    {
        $items_to_delete = request()->getPost('ids');
        if ($this->Raw_item->delete_list($items_to_delete)) {
            $message = lang('items_lang.items_successful_deleted') . ' ' . count($items_to_delete) . ' ' . lang('items_lang.items_one_or_multiple');
            echo json_encode(array('success' => TRUE, 'message' => $message));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_cannot_be_deleted')));
        }
    }

    /*
	Items import from excel spreadsheet
	*/
    public function excel()
    {
        $name = 'import_items.csv';
        $data = file_get_contents('../' . $name);
        force_download($name, $data);
    }

    public function excel_import()
    {
        return view('raw_items/form_excel_import');
    }

    public function do_excel_import()
    {
        if ($_FILES['file_path']['error'] != UPLOAD_ERR_OK) {
            echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_excel_import_failed')));
        } else {
            if (($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE) {
                // Skip the first row as it's the table description
                fgetcsv($handle);
                $i = 1;

                $failCodes = array();
                $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // XSS file data sanity check
                    $data = $this->xss_clean($data);

                    if (sizeof($data) >= 9) {
                        $item_data = array(
                            'name' => $data[1],
                            'description' => $data[5],
                            'category' => $data[2],
                            'person_id' => $employee_id,
                            'cost_price' => $data[3],
                            'reorder_level' => $data[4],
                            'allow_alt_description' => $data[6] != '' ? '1' : '0',
                            'custom2' => $data[7],
                            'custom10' => $data[8]
                        );
                        $item_number = $data[0];
                        $invalidated = FALSE;
                        if ($item_number != '') {
                            $item_data['item_number'] = $item_number;
                            $invalidated = $this->Raw_item->item_number_exists($item_number);
                        }
                    } else {
                        $invalidated = TRUE;
                    }

                    if (!$invalidated && $this->Raw_item->save($item_data)) {

                        // quantities & inventory Info
                        
                        $emp_info = $this->Employee->get_info($employee_id);
                        $comment = 'Qty CSV Imported';

                        $cols = count($data);
                        $col = 9;
                        // array to store information if location got a quantity
                        $allowed_locations = $this->Stock_location->get_allowed_locations('raw_items');
                        //for ($col = 10; $col < $cols; $col = $col + 2) {
                            $location_id = $data[$col];
                            if (array_key_exists($location_id, $allowed_locations)) {
                                $item_quantity_data = array(
                                    'item_id' => $item_data['item_id'],
                                    'location_id' => $location_id,
                                    'quantity' => $data[$col + 1],
                                );
                                $this->Raw_item_quantity->save($item_quantity_data, $item_data['item_id'], $location_id);

                                $excel_data = array(
                                    'trans_items' => $item_data['item_id'],
                                    'trans_user' => $employee_id,
                                    'trans_comment' => $comment,
                                    'trans_location' => $data[$col],
                                    'trans_inventory' => $data[$col + 1]
                                );

                                $this->Raw_inventory->insert($excel_data);
                                unset($allowed_locations[$location_id]);
                            }
                        //}

                        /*
                         * now iterate through the array and check for which location_id no entry into item_quantities was made yet
                         * those get an entry with quantity as 0.
                         * unfortunately a bit duplicate code from above...
                         */
                        foreach ($allowed_locations as $location_id => $location_name) {
                            $item_quantity_data = array(
                                'item_id' => $item_data['item_id'],
                                'location_id' => $location_id,
                                'quantity' => 0,
                            );
                            $this->Raw_item_quantity->save($item_quantity_data, $item_data['item_id'], $data[$col]);

                            $excel_data = array(
                                'trans_items' => $item_data['item_id'],
                                'trans_user' => $employee_id,
                                'trans_comment' => $comment,
                                'trans_location' => $location_id,
                                'trans_inventory' => 0
                            );

                            $this->Raw_inventory->insert($excel_data);
                        }
                    } else //insert or update item failure
                    {
                        $failCodes[] = $i;
                    }

                    ++$i;
                }

                if (count($failCodes) > 0) {
                    $message = $this->lang->line('items_excel_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

                    echo json_encode(array('success' => FALSE, 'message' => $message));
                } else {
                    echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('items_excel_import_success')));
                }
            } else {
                echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_excel_import_nodata_wrongformat')));
            }
        }
    }
}

?>
