<?php
namespace App\Controllers;

use App\Libraries\BarcodeLib;
use App\Libraries\Gu;
use App\Models\Stock_location;
use App\Models\Employee;
use App\Libraries\ItemLib;
use App\Models\Appconfig;
use App\Models\Item;
use App\Models\Raw_item_processing;
use App\Models\Raw_item_quantity;

class Raw_items_processing extends SecureController
{
    protected $Stock_location;
    protected $Employee;
    protected $item_lib;
    protected $Raw_item_processing;
    protected $Raw_item_quantity;
    public function __construct()
    {
        parent::__construct('raw_items_processing');
        $this->Stock_location = new Stock_location();
        $this->Employee = new Employee();
        $this->item_lib = new ItemLib();
        $this->Raw_item_processing = new Raw_item_processing();
        $this->Raw_item_quantity = new Raw_item_quantity();
    }

    public function index($data = null)
    {
        $data['table_headers'] = $this->xss_clean(get_raw_items_manage_table_headers());

        //$data['stock_location'] = $this->xss_clean($this->item_lib->get_item_location());
        $data['stock_locations'] = $this->xss_clean($this->Stock_location->get_allowed_locations('raw_items_processing'));

        // filters that will be loaded in the multiselect dropdown
        $data['filters'] = array('empty_upc' => lang('items_lang.items_empty_upc_items'),
            'low_inventory' => lang('items_lang.items_low_inventory_items'),
            'no_description' => lang('items_lang.items_no_description_items'),
            'search_custom' => lang('items_lang.items_search_custom_items'),
            'is_deleted' => lang('items_lang.items_is_deleted'));
            $data['appData'] = $this->appconfigModel->get_all();
            $data['gu'] = new Gu();
            $data['controller_name'] = 'raw_items_processing';
            $logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
            $data['user_info'] = $logged_in_employee_info;
        return view('raw_items_processing/manage', $data);
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

        $items = $this->Raw_item_processing->search($search, $filters, $limit, $offset, $sort, $order, $employee_id);
        $total_rows = $this->Raw_item_processing->get_found_rows($search, $filters, $employee_id);

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
    public function suggest()
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        $suggestions = $this->xss_clean($this->Raw_item_processing->get_items_search_suggestions(request()->getPostGet('term'), $employee_id, array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));

        echo json_encode($suggestions);
    }

    /*
    Gives search suggestions based on what is being searched for
    */
    public function suggest_category()
    {
        $suggestions = $this->xss_clean($this->Raw_item_processing->get_category_suggestions(request()->getGet('term')));

        echo json_encode($suggestions);
    }

    /*
     Gives search suggestions based on what is being searched for
    */
    public function suggest_location()
    {
        $suggestions = $this->xss_clean($this->Raw_item_processing->get_location_suggestions(request()->getGet('term')));

        echo json_encode($suggestions);
    }

    /*
     Gives search suggestions based on what is being searched for
    */
    public function suggest_custom()
    {
        $suggestions = $this->xss_clean($this->Raw_item_processing->get_custom_suggestions(request()->getPost('term'), request()->getPost('field_no')));

        echo json_encode($suggestions);
    }

    public function get_row($item_ids)
    {
        $item_infos = $this->Raw_item_processing->get_multiple_info(explode(":", $item_ids), $this->item_lib->get_item_location());

        $result = array();
        foreach ($item_infos->result() as $item_info) {
            $result[$item_info->item_id] = $this->xss_clean(get_raw_item_data_row($item_info, $this));
        }

        echo json_encode($result);
    }

    public function view($item_id = -1)
    {

        $item_info = $this->Raw_item_processing->get_info($item_id);
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

        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items_processing')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);

            $quantity = $this->xss_clean($this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id'])->quantity);
            $quantity = ($item_id == -1) ? 0 : $quantity;
            $location_array[$location['location_id']] = array('location_name' => $location['location_name'], 'quantity' => $quantity);
            $data['stock_locations'] = $location_array;
        }

        $items = [];
        foreach($this->Raw_item_processing->get_kit_items($item_id) as $raw_item_items)
        {
            
            $item['item_id'] = $this->xss_clean($raw_item_items['item_id']);
            $item['name'] = $this->xss_clean($raw_item_items['name']);
            $item['category'] = $this->xss_clean($raw_item_items['category']);
            $items[] = $item;
        }
        $data['raw_item_items'] = $items;

// echo "<pre>";
// print_r($items);
// echo "</pre>";
$data['appData'] = $this->appconfigModel->get_all();
$data['gu'] = new Gu();
$data['controller_name'] = 'raw_items_processing';

        return view('raw_items_processing/form', $data);
    }

    public function inventory($item_id = -1)
    {   
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $item_info = $this->Raw_item_processing->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }
        $data['item_info'] = $item_info;

        $data['stock_locations'] = array();
        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items_processing')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);
            $quantity = $this->xss_clean($this->Raw_item_quantity->get_order_item_quantity($item_id, $employee_id)->available_quantity);

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }

        $items = [];
        foreach($this->Raw_item_processing->get_kit_items($item_id) as $raw_item_items)
        {
            
            $item['item_id'] = $this->xss_clean($raw_item_items['item_id']);
            $item['name'] = $this->xss_clean($raw_item_items['name']);
            $item['category'] = $this->xss_clean($raw_item_items['category']);
            $item['available_quantity'] = $this->xss_clean($raw_item_items['available_quantity']);
            $items[] = $item;
        }
        $data['raw_item_items'] = $items;
        
        return view('raw_items_processing/form_inventory', $data);
    }

    public function count_details($item_id = -1)
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $item_info = $this->Raw_item_processing->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }
        $data['item_info'] = $item_info;

        $data['stock_locations'] = array();
        $stock_locations = $this->Stock_location->get_undeleted_all('raw_items_processing')->getResultArray();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);
            $quantity = $this->xss_clean($this->Raw_item_quantity->get_order_item_quantity($item_id, $employee_id)->available_quantity);

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }

        return view('raw_items_processing/form_count_details', $data);
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

    public function save($item_id = -1)
    {
        $upload_success = $this->_handle_image_upload();
        $upload_data = $this->upload->data();
        
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        //Save item data
        $item_data = array(
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description'),
            'category' => $this->input->post('category'),
            'person_id' => $employee_id,
            'item_number' => $this->input->post('item_number') == '' ? NULL : $this->input->post('item_number'),
            'cost_price' => parse_decimals($this->input->post('cost_price')),
            'reorder_level' => parse_decimals(0),
            'allow_alt_description' => $this->input->post('allow_alt_description') != NULL,
            'deleted' => $this->input->post('is_deleted') != NULL,
            'custom2' => $this->input->post('custom2') == NULL ? '' : $this->input->post('custom2'),
            'custom10' => $this->input->post('custom10') == NULL ? '' : $this->input->post('custom10'),
            'item_type' => 3,
            'item_processed' => 1,
            'item_attributes' => 0,
        );

        if (!empty($upload_data['orig_name'])) {
            // XSS file image sanity check
            //if ($this->xss_clean($upload_data['raw_name'], TRUE) === TRUE) {
                $item_data['pic_id'] = $upload_data['raw_name'];
            //}
        }
        
        $cur_item_info = $this->Raw_item_processing->get_info($item_id);

        

        if ($this->Raw_item_processing->save($item_data, $item_id)) {
            $success = TRUE;
            $new_item = FALSE;
            //New item
            if ($item_id == -1) {
                $item_id = $item_data['item_id'];
                $new_item = TRUE;
            }
            
            //Update stock quantity
            $item_quantity = $this->Raw_item_quantity->get_order_item_quantity($item_id, $employee_id);
            $item_quantity_data = array(
                'item_id' => $item_id,
                'store_id' => $employee_id,
                'available_quantity' => $item_quantity->available_quantity + 0
            );
            $this->Raw_item_quantity->save_items($item_quantity_data, $item_id, $employee_id);

            //Save item quantity
            $stock_locations = $this->Stock_location->get_undeleted_all('raw_items_processing')->result_array();
            foreach ($stock_locations as $location) {
                $updated_quantity = parse_decimals(0);
                $location_detail = array('item_id' => $item_id,
                    'location_id' => $location['location_id'],
                    'quantity' => $updated_quantity);
                $item_quantity = $this->Raw_item_quantity->get_item_quantity($item_id, $location['location_id']);
                if ($item_quantity->quantity != $updated_quantity || $new_item) {
                    $success &= $this->Raw_item_quantity->save($location_detail, $item_id, $location['location_id']);

                    // entry for new items only
                    if($new_item){
                        $inv_data = array(
                            'trans_date' => date('Y-m-d H:i:s'),
                            'trans_items' => $item_id,
                            'trans_user' => $employee_id,
                            'trans_location' => $location['location_id'],
                            'trans_comment' => ($new_item) ? $this->lang->line('raw_items_processing_manually_editing_of_quantity') : $this->lang->line('raw_items_processing_ordered_editing_of_quantity'),
                            'trans_inventory' => $updated_quantity - $item_quantity->quantity
                        );

                        $success &= $this->Raw_inventory->insert($inv_data);
                    }
                }
            }

            if($this->input->post('category_item') != NULL)
            {
                $item_kit_items = array();
                foreach($this->input->post('category_item') as $key => $quantity)
                {
                    $item_kit_items[] = array(
                        'item_id' => $key,
                        'quantity' => 0,
                    );

                    // update qty available at store/branch 
                    // $item_quantity = $this->Raw_item_quantity->get_order_item_quantity($key, $employee_id);

                    // $items_detail = array('item_id' => $key,
                    //     'store_id' => $employee_id,
                    //     'available_quantity' => $item_quantity->available_quantity - $quantity);
                
                    // $success &= $this->Raw_item_quantity->save_items($items_detail, $key, $employee_id);

                }
 
                $success &= $this->Raw_item_processing->save_kit_items($item_kit_items, $item_id);
            }

            if ($success && $upload_success) {
                $message = $this->xss_clean($this->lang->line('items_successful_' . ($new_item ? 'adding' : 'updating')) . ' ' . $item_data['name']);

                echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
            } else {
                $message = $this->xss_clean($upload_success ? $this->lang->line('items_error_adding_updating') . ' ' . $item_data['name'] : strip_tags($this->upload->display_errors()));

                echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => $item_id));
            }
        } else//failure
        {
            $message = $this->xss_clean($this->lang->line('items_error_adding_updating') . ' ' . $item_data['name']);

            echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => -1));
        }
    }

    public function check_item_number()
    {
        $exists = $this->Raw_item_processing->item_number_exists($this->input->post('item_number'), $this->input->post('item_id'));
        echo !$exists ? 'true' : 'false';
    }

    private function _handle_image_upload()
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

    public function remove_logo($item_id)
    {
        $item_data = array('pic_id' => NULL);
        $result = $this->Raw_item_processing->save($item_data, $item_id);

        echo json_encode(array('success' => $result));
    }

    public function save_inventory($item_id = -1)
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $cur_item_info = $this->Raw_item_processing->get_info($item_id);
        $location_id = $this->input->post('stock_location');
        $inv_data = array(
            'trans_date' => date('Y-m-d H:i:s'),
            'trans_items' => $item_id,
            'trans_user' => $employee_id,
            'trans_location' => $location_id,
            'trans_comment' => $this->input->post('trans_comment'),
            'trans_inventory' => parse_decimals($this->input->post('newquantity'))
        );

        $this->Raw_inventory->insert($inv_data);

        //Update stock quantity
        $item_quantity = $this->Raw_item_quantity->get_order_item_quantity($item_id, $employee_id);
        $item_quantity_data = array(
            'item_id' => $item_id,
            'store_id' => $employee_id,
            'available_quantity' => $item_quantity->available_quantity + parse_decimals($this->input->post('newquantity'))
        );

        if ($this->Raw_item_quantity->save_items($item_quantity_data, $item_id, $employee_id)) {

            $success = TRUE;

            if($this->input->post('category_item') != NULL)
            {
                $item_kit_items = array();
                foreach($this->input->post('category_item') as $key => $quantity)
                {
                    $item_kit_items[] = array(
                        'item_id' => $key,
                        'quantity' => $quantity,
                    );

                    // update qty available at store/branch 
                    $item_quantity = $this->Raw_item_quantity->get_order_item_quantity($key, $employee_id);

                    $items_detail = array('item_id' => $key,
                        'store_id' => $employee_id,
                        'available_quantity' => $item_quantity->available_quantity - $quantity);
                
                    $success &= $this->Raw_item_quantity->save_items($items_detail, $key, $employee_id);

                }
 
                $success &= $this->Raw_item_processing->update_kit_items($item_kit_items, $item_id);
            }

            $message = $this->xss_clean($this->lang->line('items_successful_updating') . ' ' . $cur_item_info->name);

            echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
        } else//failure
        {
            $message = $this->xss_clean($this->lang->line('items_error_adding_updating') . ' ' . $cur_item_info->name);

            echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => -1));
        }
    }

    public function delete()
    {
        $items_to_delete = $this->input->post('ids');
        if ($this->Raw_item_processing->delete_list($items_to_delete)) {
            $message = $this->lang->line('items_successful_deleted') . ' ' . count($items_to_delete) . ' ' . $this->lang->line('items_one_or_multiple');
            echo json_encode(array('success' => TRUE, 'message' => $message));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_cannot_be_deleted')));
        }
    }

}

?>
