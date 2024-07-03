<?php

namespace App\Controllers;

use App\Models\Item;
use App\Libraries\Gu;
use App\Models\Module;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Appconfig;
use App\Models\Inventory;
use App\Libraries\AppData;
use App\Libraries\ItemLib;
use App\Models\Item_taxes;

use CodeIgniter\Files\File;
use App\Libraries\BarcodeLib;
use App\Models\Item_quantity;
use App\Models\Stock_location;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Files\UploadedFile;

class Items extends SecureController
{

    public function __construct()
    {
        parent::__construct('items');
    }

    public function index($module_id = NULL, $submodule_id = Null)
    {

        $ItemLib = new ItemLib();
        $stockLocationModel = new Stock_location();
        $data = $this->data;
        $data['table_headers'] = get_items_manage_table_headers();
        $data['stock_location'] = $ItemLib->get_item_location();
        $data['stock_locations'] = $stockLocationModel->get_allowed_locations();

        // Filters that will be loaded in the multiselect dropdown
        $data['filters'] = [
            'empty_upc' => lang('items_lang.items_empty_upc_items'),
            'low_inventory' => lang('items_lang.items_low_inventory_items'),
            'is_serialized' => lang('items_lang.items_serialized_items'),
            'no_description' => lang('items_lang.items_no_description_items'),
            'search_custom' => lang('items_lang.items_search_custom_items'),
            'is_deleted' => lang('items_lang.items_is_deleted')
        ];

        return view('items/manage', $data);
    }

    public function sync()
    {
        try {
            if (!request()->isAJAX()) {
                return $this->index();
            } else {

                $onlineDB = \Config\Database::connect('online');
                $localDB = \Config\Database::connect();

                $online_items = $onlineDB->table('items')->get()->getResult();
                $local_items = $localDB->table('items')->get()->getResult();

                $onlineJson = json_encode($online_items);
                $localJson = json_encode($local_items);
                $diff = strcmp($localJson, $onlineJson);

                if ($diff) {
                    // echo "Giftcard update available. Updating giftcards record... ";

                    // Start transaction for the local database
                    $localDB->transStart();

                    $localDB->query("SET FOREIGN_KEY_CHECKS = 0");
                    $localDB->table('items')->truncate();

                    // Adding data to the giftcards table
                    foreach ($online_items as $item) {
                        if (!$localDB->table('items')->insert($item)) {
                            echo "Item error. Sync failed.";
                            $localDB->transRollback();
                            $localDB->query("SET FOREIGN_KEY_CHECKS = 1");
                            return false;
                        }
                    }

                    $localDB->query("SET FOREIGN_KEY_CHECKS = 1");

                    // Commit transaction
                    $localDB->transComplete();
                    echo "Items updated successfully.";
                } else {
                    echo "Items are already in sync. No updates required.";
                }
            }
        } catch (\Throwable $th) {
            echo "Error: " . $th->getMessage();
            return false;
        }
    }

    public function search()
    {
        $search = request()->getGet('search');
        $limit = request()->getGet('limit');
        $offset = request()->getGet('offset');
        $sort = request()->getGet('sort');
        $order = request()->getGet('order');

        $itemLib = new ItemLib();
        $itemLib->set_item_location(request()->getGet('stock_location'));

        $filters = [
            'start_date' => request()->getGet('start_date'),
            'end_date' => request()->getGet('end_date'),
            'stock_location_id' => $itemLib->get_item_location(),
            'empty_upc' => false,
            'low_inventory' => false,
            'is_serialized' => false,
            'no_description' => false,
            'search_custom' => false,
            'is_deleted' => false
        ];

        // Check if any filter is set in the multiselect dropdown
        $selectedFilters = request()->getGet('filters');
        if (!empty($selectedFilters)) {
            foreach ($selectedFilters as $filter) {
                $filters[$filter] = true;
            }
        }

        // Load the ItemModel (adjust the namespace accordingly)
        $itemModel = new Item();
        $limit = ($limit !== null) ? $limit : 10;
       $offset = ($offset !== null) ? $offset : 0;
   
       // Check if search term is empty, if so, set it to an empty string
       $search = ($search !== null) ? $search : '';
        $items = $itemModel->search($search, $filters, $limit, $offset, $sort, $order)->getResult();
        $totalRows = $itemModel->get_found_rows($search, $filters);
        $dataRows = [];
        foreach ($items as $item) {
            $dataRows[] = get_item_data_row($item, $this);
        }

        return $this->response->setJSON([
            'total' => $totalRows,
            'rows' => $dataRows
        ]);
    }

    public function pic_thumb($pic_id)
    {
        /* $this->load->helper('file');
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
        } */
    }

    public function suggest_search()
    {
        $this->request = service('request');
        $term = $this->request->getPostGet('term');
        $searchCustom = $this->request->getPost('search_custom');
        $isDeleted = $this->request->getPost('is_deleted') !== null;

        $ItemModel = new Item();
        $suggestions = $ItemModel->get_search_suggestions($term, [
            'search_custom' => $searchCustom,
            'is_deleted' => $isDeleted
        ], false);

        return $this->response->setJSON($suggestions);
    }

    public function suggest()
    {
        $this->request = service('request');
        $term = $this->request->getPostGet('term');

        $itemModel = new Item();
        $suggestions = $itemModel->get_search_suggestions($term, ['search_custom' => false, 'is_deleted' => false], true);

        return $this->response->setJSON($suggestions);
    }

    public function suggest_category()
    {
        $Item = new Item();
        $suggestions = $Item->get_category_suggestions(request()->getVar('term'));

        return $this->response->setJSON($suggestions);
    }

    public function suggest_location()
    {
        $this->request = \Config\Services::request();
        $itemModel = new Item();

        $term = $this->request->getVar('term');
        $suggestions = $itemModel->get_location_suggestions($term);

        return $this->response->setJSON($suggestions);
    }

    public function suggest_custom()
    {
        $this->request = \Config\Services::request();
        $term = $this->request->getPost('term');
        $fieldNo = $this->request->getPost('field_no');

        $itemModel = new Item();
        $suggestions = $itemModel->get_custom_suggestions($term, $fieldNo);

        return $this->response->setJSON($suggestions);
    } 
    
    public function get_row($itemIds)
    {
        $itemModel = new Item();

        $itemLib = new itemLib();

        $itemInfos = $itemModel->get_multiple_info(explode(":", $itemIds), $itemLib->get_item_location());

        $result = [];
        foreach ($itemInfos->getResult() as $itemInfo) {
            $result[$itemInfo->item_id] = get_item_data_row($itemInfo, $this);
        }

        return $this->response->setJSON($result);
    }

    public function view($item_id = -1)
    {
        $Item_taxes = new Item_taxes();
        $data = $this->data;
        $data['item_tax_info'] = $this->xss_clean($Item_taxes->get_info($item_id));
        $data['default_tax_1_rate'] = '';
        $data['default_tax_2_rate'] = '';
        $Item = new Item();
        $item_info = $Item->get_info($item_id);
        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }

        if ($item_id == -1) {
            $appConfig = new Appconfig();
            $data['default_tax_1_rate'] = $appConfig->get('default_tax_1_rate');
            $data['default_tax_2_rate'] = $appConfig->get('default_tax_2_rate');

            $item_info->receiving_quantity = 0;
            $item_info->reorder_level = 0;
        }

        $data['item_info'] = $item_info;

        $suppliers = array('' => lang('items_lang.items_none'));
        $Supplier = new Supplier();
        foreach ($Supplier->get_all()->getResultArray() as $row) {
            $suppliers[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
        }
        $data['suppliers'] = $suppliers;

        $data['selected_supplier'] = $item_info->supplier_id;

        $data['logo_exists'] = ($item_info->pic_id != '') ? true : false;
        if ($data['logo_exists']) {
            $images = glob("./uploads/item_pics/" . $item_info->pic_id . ".*");
        } else {
            $images = [];
        }
        $data['image_path'] = sizeof($images) > 0 ? base_url($images[0]) : '';
        $Stock_location = new Stock_location();
        $stock_locations = $Stock_location->get_undeleted_all()->getResultArray();
        $Item_quantity = new Item_quantity();
        foreach ($stock_locations as $location) {
            $location = $this->xss_clean($location);

            $quantity = $this->xss_clean($Item_quantity->get_item_quantity($item_id, $location['location_id'])->quantity);
            $quantity = ($item_id == -1) ? 0 : $quantity;
            $location_array[$location['location_id']] = array('location_name' => $location['location_name'], 'quantity' => $quantity);
        }
        $data['stock_locations'] = $location_array;
        return view('items/form', $data);
    }

    public function inventory($item_id = -1)
    {
        $Item = new Item();

        $item_info = $Item->get_info($item_id);
        foreach ($item_info as $property => $value) {
            $item_info->{$property} = $value;
        }

        $data['item_info'] = $item_info;
        $data['stock_locations'] = [];
        $StockLocationModel = new Stock_location();
        $stock_locations = $StockLocationModel->get_undeleted_all()->getResultArray();

        foreach ($stock_locations as $location) {

            $item_quantity_model = new Item_quantity();
            $quantity = request()->getVar($item_quantity_model->get_item_quantity($item_id, $location['location_id'])->quantity);

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }

        return view('items/form_inventory', $data);
    }

    public function count_details($item_id = -1)
    {
        $Item = new Item();
        $item_info = $Item->get_info($item_id);

        foreach ($item_info as $property => $value) {
            $item_info->{$property} = $value;
        }

        $data['item_info'] = $item_info;

        $data['stock_locations'] = [];
        $stockLocationModel = new Stock_location();
        $stock_locations = $stockLocationModel->get_undeleted_all()->getResultArray();

        foreach ($stock_locations as $location) {
            $item_quantity_model = new Item_quantity();
            $quantity = $item_quantity_model->get_item_quantity($item_id, $location['location_id'])->quantity;

            $data['stock_locations'][$location['location_id']] = $location['location_name'];
            $data['item_quantities'][$location['location_id']] = $quantity;
        }
        $inventoryModel = new \App\Models\Inventory();
        $inventory_array = $inventoryModel->get_inventory_data_for_item($item_info->item_id)->getResult();

        $employee_name = [];

        foreach ($inventory_array as $row) {
            $employeeModel = new Employee();
            $employee = $employeeModel->get_info($row->trans_user);
            $employee_name[] = $employee->first_name . ' ' . $employee->last_name;
        }
        $data['employee_name'] = $employee_name;
        $data['inventory_array'] = $inventory_array;

        echo view('items/form_count_details', $data);
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
        $supplierModel = new Supplier();

        $suppliers = ['' => lang('items_lang.items_none')];
        foreach ($supplierModel->get_all()->getResultArray() as $row) {

            $suppliers[$row['person_id']] = $row['company_name'];
        }

        $data = [
            'suppliers' => $suppliers,
            'allow_alt_description_choices' => [
                '' => lang('items_lang.items_do_nothing'),
                1 => lang('items_lang.items_change_all_to_allow_alt_desc'),
                0 => lang('items_lang.items_change_all_to_not_allow_allow_desc')
            ],
            'serialization_choices' => [
                '' => lang('items_lang.items_do_nothing'),
                1 => lang('items_lang.items_change_all_to_serialized'),
                0 => lang('items_lang.items_change_all_to_unserialized')
            ]
        ];
        $appData = (new Appconfig())->get_all();
        $gu = new Gu();
        $data['appData'] = $appData;
        $data['gu'] = $gu;
        return view('items/form_bulk', $data);
    }
    
    public function save($item_id = -1)
    {
        
        $EmployeeModel = new Employee();
        $employee_id = $EmployeeModel->get_logged_in_employee_info()->person_id;
        $upload_data = request()->getFile('item_image');
        $Item = new Item();
        if(isset($upload_data))
        {
            $originalFilename = $upload_data->getName();
            $timestamp = time();
           $newFilename = $timestamp . '_' . $originalFilename;
    
           if ($upload_data && $upload_data->isValid() && !$upload_data->hasMoved()) {
               $destinationDirectory = './uploads/item_pics/';
               if (!is_dir($destinationDirectory)) {
                   mkdir($destinationDirectory, 0777, true); // Create directory recursively
               }
           
               // Original uploaded filename
               $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION); // Get the file extension
           
               // Generate a new filename with a timestamp
              
           
               $destination = $destinationDirectory . $newFilename;
               
               $itemid=$Item->get_info($item_id);
               $existingimage = $destinationDirectory . $itemid->pic_id;
   
               if(is_file($existingimage))
               {
                   unlink($existingimage);
               }
               // Check if the file already exists
               if (is_file($destination || is_file($existingimage) )) {
                   // Delete the previous file
                   unlink($destination);
                  
               }
           
               // Move the uploaded file to the new destination
               $upload_data->move($destinationDirectory, $newFilename);
           }
        }
       
         // Save item data
        $item_data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'category' => $this->request->getPost('category'),
            'supplier_id' => $this->request->getPost('supplier_id') === '' ? null : $this->request->getPost('supplier_id'),
            'item_number' => $this->request->getPost('item_number') === '' ? null : $this->request->getPost('item_number'),
            'cost_price' => ($this->request->getPost('cost_price')),
            'unit_price' => ($this->request->getPost('unit_price')),
            'pic_id'=>(isset($newFilename)) ? $newFilename : null,
            'reorder_level' => ($this->request->getPost('reorder_level')),
            'receiving_quantity' => ($this->request->getPost('receiving_quantity')),
            'allow_alt_description' => $this->request->getPost('allow_alt_description') !== null,
            'is_serialized' => $this->request->getPost('is_serialized') !== null,
            'deleted' => $this->request->getPost('is_deleted') !== null,
            'custom1' => $this->request->getPost('custom1') === null ? '' : $this->request->getPost('custom1'),
            'custom2' => $this->request->getPost('custom2') === null ? '' : $this->request->getPost('custom2'),
            'custom3' => $this->request->getPost('custom3') === null ? '' : $this->request->getPost('custom3'),
            'custom4' => $this->request->getPost('custom4') === null ? '' : $this->request->getPost('custom4'),
            'custom5' => $this->request->getPost('custom5') === null ? '' : $this->request->getPost('custom5'),
            'custom6' => $this->request->getPost('custom6') === null ? '' : $this->request->getPost('custom6'),
            'custom7' => $this->request->getPost('custom7') === null ? '' : $this->request->getPost('custom7'),
            'custom8' => $this->request->getPost('custom8') === null ? '' : $this->request->getPost('custom8'),
            'custom9' => $this->request->getPost('custom9') === null ? '' : $this->request->getPost('custom9'),
            'custom10' => $this->request->getPost('custom10') === null ? '' : $this->request->getPost('custom10')
        ];
        if ($Item->saveItem($item_data, $item_id)) {
            $success = true;
            $new_item = false;
            // New item
            if ($item_id == -1) {
                $item_id = $item_data['item_id'];
                $new_item = true;
            }

            $items_taxes_data = [];
            $tax_names = $this->request->getPost('tax_names');
            $tax_percents = $this->request->getPost('tax_percents');
            $count = count($tax_percents);
            for ($k = 0; $k < $count; ++$k) {
                $tax_percentage = ($tax_percents[$k]);
                if (is_numeric($tax_percentage)) {
                    $items_taxes_data[] = ['name' => $tax_names[$k], 'percent' => $tax_percentage];
                }
            }

            $Item_taxes = new Item_taxes();
            $success &= $Item_taxes->saveItemTaxes($items_taxes_data, $item_id);
            // Save item quantity
            $stock_location_model = new Stock_location();

            $stockLocations = $stock_location_model->get_undeleted_all()->getResultArray();

            foreach ($stockLocations as $location) {
                $updatedQuantity = $this->request->getPost('quantity_' . $location['location_id']);
                $locationDetail = [
                    'item_id' => $item_id,
                    'location_id' => $location['location_id'],
                    'quantity' => $updatedQuantity
                ];

                $Item_quantityModel = new Item_quantity();

                $itemQuantity = $Item_quantityModel->get_item_quantity($item_id, $location['location_id']);

                if ($itemQuantity->quantity != $updatedQuantity || $new_item) {
                    $success &= $Item_quantityModel->saveItemQuantities($locationDetail, $item_id, $location['location_id']);

                    $invData = [
                        'trans_date' => date('Y-m-d H:i:s'),
                        'trans_items' => $item_id,
                        'trans_user' => $employee_id,
                        'trans_location' => $location['location_id'],
                        'trans_comment' => lang('items_lang.items_manually_editing_of_quantity'),
                        'trans_inventory' => $updatedQuantity - $itemQuantity->quantity
                    ];

                    $InventoryModel = new Inventory();

                    $success &= $InventoryModel->insertInventory($invData);
                }
            }

            if ($success) {
                $message = lang('items_lang.items_successful_' . ($new_item ? 'adding' : 'updating')) . ' ' . $item_data['name'];
                return $this->response->setJSON(['success' => true, 'message' => $message, 'id' => $item_id]);
            } 
        } else {
            $message = lang('items_lang.items_error_adding_updating') . ' ' . $item_data['name'];

            return $this->response->setJSON(['success' => FALSE, 'message' => $message, 'id' => -1]);
        }
    }

    public function check_item_number()
    {
        $Item = new Item();
        $exists = $Item->item_number_exists(request()->getPost('item_number'), request()->getPost('item_id'));
        echo !$exists ? 'true' : 'false';
    }

    private function _handle_image_upload()
    {
        /* helper('filesystem');
        // Get the uploaded file instance
        $uploadedFile = request()->getFile('item_image');

        // Get the file info
        $upload_data = [
            'name' => $uploadedFile->getName(),
            'type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'tmp_name' => $uploadedFile->getTempName(),
            'error' => $uploadedFile->getError(),
        ];
        $upload = request()->getFile('item_image')->store('item_pics/', $upload_data['name']); // To store the file
    if ($upload) {
        return TRUE;
    } else {
        return FALSE;
    } */
        return TRUE;
    }

    public function remove_logo($item_id,$pic_id)
    {
        
        $itemModel = new Item(); 
        $item_record=$itemModel->get_info($item_id);
        $destination='uploads/item_pics/'.$pic_id;
        unlink($destination);
        $item_data = ['pic_id' => NULL];
        $result = $itemModel->saveItem($item_data,$item_id);

        return $this->response->setJSON(['success' => $result]);
    }

    public function save_inventory($item_id = -1)
    {
        $EmployeeModel = new Employee();
        $employee_id = $EmployeeModel->get_logged_in_employee_info()->person_id;
        $ItemModel = new Item();
        $cur_item_info = $ItemModel->get_info($item_id);
        $location_id = request()->getPost('stock_location');

        $inv_data = [
            'trans_date' => date('Y-m-d H:i:s'),
            'trans_items' => $item_id,
            'trans_user' => $employee_id,
            'trans_location' => $location_id,
            'trans_comment' => request()->getPost('trans_comment'),
            'trans_inventory' => parse_decimals(request()->getPost('newquantity'))
        ];

        $InventoryModel = new Inventory();
        $InventoryModel->insertInventory($inv_data);

        // Update stock quantity
        $ItemQuantityModel = new Item_quantity();
        $item_quantity = $ItemQuantityModel->get_item_quantity($item_id, $location_id);
        $item_quantity_data = [
            'item_id' => $item_id,
            'location_id' => $location_id,
            'quantity' => $item_quantity->quantity + parse_decimals(request()->getPost('newquantity'))
        ];

        if ($ItemQuantityModel->saveItemQuantities($item_quantity_data, $item_id, $location_id)) {
            $message = esc(lang('items_lang.items_successful_updating') . ' ' . $cur_item_info->name);

            echo json_encode(['success' => true, 'message' => $message, 'id' => $item_id]);
        } else {
            $message = esc(lang('items_lang.items_error_adding_updating') . ' ' . $cur_item_info->name);

            echo json_encode(['success' => false, 'message' => $message, 'id' => -1]);
            

        }
    }

    public function bulk_update()
    {
        $itemsToUpdate = request()->getPost('item_ids');
        $itemData = [];

        foreach (request()->getPost() as $key => $value) {
            // This field is nullable, so treat it differently
            if ($key == 'supplier_id' && $value !== '') {
                $itemData[$key] = $value;
            } elseif ($value !== '' && !in_array($key, ['item_ids', 'tax_names', 'tax_percents'])) {
                $itemData[$key] = $value;
            }
        }

        // Item data could be empty if tax information is being updated
        $Item = new Item();
        if (empty($itemData) || $Item->update_multiple($itemData, $itemsToUpdate)) {
            $itemsTaxesData = [];
            $taxNames = request()->getPost('tax_names');
            $taxPercents = request()->getPost('tax_percents');
            $taxUpdated = false;
            $count = count($taxPercents);
            // print_r($count); exit();

            for ($k = 0; $k < $count; ++$k) {
                if (!empty($taxNames[$k]) && is_numeric($taxPercents[$k])) {
                    $taxUpdated = true;

                    $itemsTaxesData[] = [
                        'name' => $taxNames[$k],
                        'percent' => $taxPercents[$k]
                    ];
                }
            }

            $Item_taxes = new Item_taxes();
            if ($taxUpdated) {
                $Item_taxes->save_multiple($itemsTaxesData, $itemsToUpdate);
            }

            $response = [
                'success' => true,
                'message' => lang('items_lang.items_successful_bulk_edit'),
                'id' => esc($itemsToUpdate)
            ];
        } else {
            $response = [
                'success' => false,
                'message' => lang('items_lang.items_error_updating_multiple')
            ];
        }

        return $this->response->setJSON($response);
    }

    public function delete()
    {
        $itemModel = new Item();
        $items_to_delete = request()->getPost('ids');
        if ($itemModel->delete_list($items_to_delete)) {
            $message = lang('items_lang.items_successful_deleted') . ' ' . count($items_to_delete) . ' ' . lang('items_lang.items_one_or_multiple');
            return $this->response->setJSON(['success' => true, 'message' => $message]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => lang('items_lang.items_cannot_be_deleted')]);
        }
    }

    public function excel()
    {
        $name = 'import_items.csv';
        $path = FCPATH . $name; // Assuming the file is in the root directory

        return $this->response
            ->download($path, null)
            ->setFileName($name)
            ->setHeader('Cache-Control', 'public')
            ->setHeader('Content-Description', 'File Transfer')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setHeader('Content-Type', 'application/csv')
            ->setHeader('Content-Transfer-Encoding', 'binary')
            ->setHeader('Expires', '0')
            ->setHeader('Pragma', 'public')
            ->setHeader('Content-Length', filesize($path));
        // ->setOutput($data); // $data is not specified in your original code, so you might need to retrieve it
    }

    public function excel_import()
    {
        return view('items/form_excel_import');
    }

    // public function do_excel_import()
    // {
    //     helper(['form', 'url', 'security']);
        
    //     $file = request()->getFile('file_path');
    //     if ($file->getError() !== UPLOAD_ERR_OK) {
    //         return $this->response->setJSON(['success' => FALSE, 'message' => lang('items_lang.items_excel_import_failed')]);
    //     }
    
    //     $handle = fopen($file->getTempName(), 'r');
    //     if (!$handle) {
    //         return $this->response->setJSON(['success' => FALSE, 'message' => 'Unable to open the uploaded file']);
    //     }
    
    //     // Skip the first row as it's the table description
    //     fgetcsv($handle);
    //     $i = 1;
    //     $failCodes = [];
    
    //     while (($data = fgetcsv($handle)) !== FALSE) {
    //         // XSS file data sanity check
    //         $sanitized_data = $this->xss_clean($data);
        
             
    //         if (count($sanitized_data) >=0) {
    //             // Your existing code to populate item_data
    //               $supplierModel = new Supplier();
    //               $item_data = [
    //                 'name' => $data[1],
    //                 'category' => $data[2],
    //                 'supplier_id' => $supplierModel->exists($data[3]) ? $data[3] : NULL,
    //                 'description' => $data[5],
    //                 'pic_id' => $data[10],
    //                 'allow_alt_description' => $data[12] != '' ? '1' : '0',
    //                 'is_serialized' => $data[13] != '' ? '1' : '0',
    //                 'deleted' => isset($data[14]) ? $data[14] : '',
    //                 'custom1' => isset($data[15]) ? $data[15] : '',
    //                 'custom2' => isset($data[16]) ? $data[16] : '',
    //                 'custom3' => isset($data[17]) ? $data[17] : '',
    //                 'custom4' => isset($data[18]) ? $data[18] : '',
    //                 'custom5' => isset($data[19]) ? $data[19] : '',
    //                 'custom6' => isset($data[20]) ? $data[20] : '',
    //                 'custom7' => isset($data[21]) ? $data[21] : '',
    //                 'custom8' => isset($data[22]) ? $data[22] : '',
    //                 'custom9' => isset($data[23]) ? $data[23] : '',
    //                 'custom10' => isset($data[24]) ? $data[24] : '',
    //             ];
                 
    //                 $item_number = $data[0];
              
    //                 $invalidated = FALSE;

    //             if (!empty($item_number)) {
                    
    //                 $item_data['item_number'] = $item_number;
    //                 $Item = new Item();
    
    //                 if (!$Item->item_number_exists($item_number) && $Item->saveItem($item_data)) {
    //                     // Your existing code for taxes, quantities, and inventory
    
    //                 } else {
    //                     $failCodes[] = $i;
    //                 }
    //             }
    //         } else {
    //             $failCodes[] = $i;
    //         }
    
    //         ++$i;
    //     }
    
    //     fclose($handle);
    
    //     if (count($failCodes) > 0) {
    //         $message = lang('items_lang.items_excel_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);
    //         return $this->response->setJSON(['success' => FALSE, 'message' => $message]);
    //     } else {
    //         return $this->response->setJSON(['success' => TRUE, 'message' => lang('items_lang.items_excel_import_success')]);
    //     }
    // }
    // public function do_excel_import()
    // {
    //     if ($_FILES['file_path']['error'] != UPLOAD_ERR_OK) {
    //         echo json_encode(array('success' => FALSE, 'message' => lang('items_excel_import_failed')));
    //     } else {
    //         $Supplier = new Supplier();
    //         $Item = new Item();
    //         $Item_taxes = new Item_taxes();
    //         $Employee = new Employee();
    //         $Stock_location = new Stock_location();
    //         $Item_quantity = new Item_quantity();
    //         $Inventory = new Inventory();
    //         if (($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE) {
    //             // Skip the first row as it's the table description
    //             fgetcsv($handle);
    //             $i = 1;

    //             $failCodes = array();

    //             while (($data = fgetcsv($handle)) !== FALSE) {
    //                 // XSS file data sanity check
    //                 $data = $this->xss_clean($data);
    //                 if (sizeof($data) <= 23) {
    //                     $item_data = array(
    //                         // 'item_id' =>$data[0],
    //                         'name' => $data[1],
    //                         'description' => $data[11],
    //                         'category' => $data[2],
    //                         'cost_price' => $data[4],
    //                         'unit_price' => $data[5],
    //                         'reorder_level' => $data[10],
    //                         'supplier_id' => $Supplier->exists($data[3]) ? $data[3] : NULL,
    //                         'allow_alt_description' => $data[12] != '' ? '1' : '0',
    //                         'is_serialized' => $data[13] != '' ? '1' : '0',
    //                         'custom1' => $data[14],
    //                         'custom2' => $data[15],
    //                         'custom3' => $data[16],
    //                         'custom4' => isset($data[17]),
    //                         'custom5' => isset($data[18]),
    //                         'custom6' => isset($data[19]),
    //                         'custom7' => isset($data[20]),
    //                         'custom8' => isset($data[21]),
    //                         'custom9' => isset($data[22]),
    //                         'custom10' => isset($data[23])
    //                     );
    //                     $item_number = $data[0];
    //                     $invalidated = FALSE;
    //                     if ($item_number != '') {
    //                         $item_data['item_number'] = $item_number;
    //                         $invalidated = $Item->item_number_exists($item_number);
    //                     }
    //                 } else {
    //                     $invalidated = TRUE;
    //                 }

    //                 if (!$invalidated && $Item->saveItem($item_data)) {
    //                     $items_taxes_data = [];
    //                     //tax 1
    //                     if ($data[7] && $data[6] != '') {
    //                         $items_taxes_data[] = array('name' => $data[6], 'percent' => $data[7]);
    //                     }

    //                     //tax 2
    //                     if ($data[9] && $data[8] != '') {
    //                         $items_taxes_data[] = array('name' => $data[8], 'percent' => $data[9]);
    //                     }
    //                     // save tax values
    //                     if ($items_taxes_data !== []) {
    //                         $Item_taxes->saveItemTaxes($items_taxes_data, $item_data['item_id']);
    //                     }

    //                     // quantities & inventory Info
    //                     $employee_id = $Employee->get_logged_in_employee_info()->person_id;
    //                     $emp_info = $Employee->get_info($employee_id);
    //                     $comment = 'Qty CSV Imported';

    //                     $cols = count($data);

    //                     // array to store information if location got a quantity
    //                     $allowed_locations = $Stock_location->get_allowed_locations();
    //                     for ($col = 24; $col < $cols; $col = $col + 2) {
    //                         $location_id = $data[$col];
    //                         if (array_key_exists($location_id, $allowed_locations)) {
    //                             $item_quantity_data = array(
    //                                 'item_id' => $item_data['item_id'],
    //                                 'location_id' => $location_id,
    //                                 'quantity' => $data[$col + 1],
    //                             );
    //                             $Item_quantity->saveItemQuantities($item_quantity_data, $item_data['item_id'], $location_id);

    //                             $excel_data = array(
    //                                 'trans_items' => $item_data['item_id'],
    //                                 'trans_user' => $employee_id,
    //                                 'trans_comment' => $comment,
    //                                 'trans_location' => $data[$col],
    //                                 'trans_inventory' => $data[$col + 1]
    //                             );

    //                             $Inventory->insertInventory($excel_data);
    //                             unset($allowed_locations[$location_id]);
    //                         }
    //                     }

    //                     /*
    //                      * now iterate through the array and check for which location_id no entry into item_quantities was made yet
    //                      * those get an entry with quantity as 0.
    //                      * unfortunately a bit duplicate code from above...
    //                      */
    //                     foreach ($allowed_locations as $location_id => $location_name) {
    //                         $item_quantity_data = array(
    //                             'item_id' => $item_data['item_id'],
    //                             'location_id' => $location_id,
    //                             'quantity' => 0,
    //                         );
    //                         $Item_quantity->saveItemQuantities($item_quantity_data, $item_data['item_id'], 1);

    //                         $excel_data = array(
    //                             'trans_items' => $item_data['item_id'],
    //                             'trans_user' => $employee_id,
    //                             'trans_comment' => $comment,
    //                             'trans_location' => $location_id,
    //                             'trans_inventory' => 0
    //                         );

    //                         $Inventory->insertInventory($excel_data);
    //                     }
    //                 } else //insert or update item failure
    //                 {
    //                     $failCodes[] = $i;
    //                 }

    //                 ++$i;
    //             }

    //             if (count($failCodes) > 0) {
    //                 $message = lang('items_excel_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

    //                 echo json_encode(array('success' => FALSE, 'message' => $message));
    //             } else {
    //                 echo json_encode(array('success' => TRUE, 'message' => lang('items_excel_import_success')));
    //             }
    //         } else {
    //             echo json_encode(array('success' => FALSE, 'message' => lang('items_excel_import_nodata_wrongformat')));
    //         }
    //     }
    // }
    

    public function do_excel_import()
    {
        // $db=Database::connect();
        // $db->table('ospos_items')->truncate();
        if ($_FILES['file_path']['error'] != UPLOAD_ERR_OK) {
            echo json_encode(array('success' => FALSE, 'message' => lang('items_excel_import_failed')));
        } else {
            $Supplier = new Supplier();
            $Item = new Item();
            $Item_taxes = new Item_taxes();
            $Employee = new Employee();
            $Stock_location = new Stock_location();
            $Item_quantity = new Item_quantity();
            $Inventory = new Inventory();
            if (($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE) {
                // Skip the first row as it's the table description
                fgetcsv($handle);
                $i = 1;

                $failCodes = array();

                while (($data = fgetcsv($handle)) !== FALSE) {
                    // XSS file data sanity check
                    $data = $this->xss_clean($data);
                    if (sizeof($data) <= 23) {
                        $item_data = array(
                            // 'item_id' =>$data[0],
                            'name' => $data[1],
                            'description' => $data[11],
                            'category' => $data[2],
                            'cost_price' => $data[4],
                            'unit_price' => $data[5],
                            'reorder_level' => $data[10],
                            'supplier_id' => $Supplier->exists($data[3]) ? $data[3] : NULL,
                            'allow_alt_description' => $data[12] != '' ? '1' : '0',
                            'is_serialized' => $data[13] != '' ? '1' : '0',
                            'custom1' => $data[14],
                            'custom2' => $data[15],
                            'custom3' => $data[16],
                            'custom4' => isset($data[17]),
                            'custom5' => isset($data[18]),
                            'custom6' => isset($data[19]),
                            'custom7' => isset($data[20]),
                            'custom8' => isset($data[21]),
                            'custom9' => isset($data[22]),
                            'custom10' => isset($data[23])
                        );
                        $item_number = $data[0];
                        $invalidated = FALSE;
                        if ($item_number != '') {
                            $item_data['item_number'] = $item_number;
                            $invalidated = $Item->item_number_exists($item_number);
                        }
                    } else {
                        $invalidated = TRUE;
                    }
                    $itemdata=$Item->get_item_id($item_number);
                    if($itemdata)
                    {

                        $item_id=$itemdata->item_id;
                    }
                    else
                    {
                        $item_id=-1;
                    }
                    
                    if ($Item->saveItem($item_data,$item_id)) {
                        $items_taxes_data = [];
                        //tax 1
                        if ($data[7] && $data[6] != '') {
                            $items_taxes_data[] = array('name' => $data[6], 'percent' => $data[7]);
                        }

                        //tax 2
                        if ($data[9] && $data[8] != '') {
                            $items_taxes_data[] = array('name' => $data[8], 'percent' => $data[9]);
                        }
                        // save tax values
                        if ($items_taxes_data !== []) {
                            $Item_taxes->saveItemTaxes($items_taxes_data, $item_data['item_id']);
                        }

                        // quantities & inventory Info
                        $employee_id = $Employee->get_logged_in_employee_info()->person_id;
                        $emp_info = $Employee->get_info($employee_id);
                        $comment = 'Qty CSV Imported';

                        $cols = count($data);

                        // array to store information if location got a quantity
                        $allowed_locations = $Stock_location->get_allowed_locations();
                        for ($col = 24; $col < $cols; $col = $col + 2) {
                            $location_id = $data[$col];
                            if (array_key_exists($location_id, $allowed_locations)) {
                                $item_quantity_data = array(
                                    'item_id' => $item_id,
                                    'location_id' => $location_id,
                                    'quantity' => $data[$col + 1],
                                );
                                $Item_quantity->saveItemQuantities($item_quantity_data, $item_data['item_id'], $location_id);

                                $excel_data = array(
                                    'trans_items' => $item_id,
                                    'trans_user' => $employee_id,
                                    'trans_comment' => $comment,
                                    'trans_location' => $data[$col],
                                    'trans_inventory' => $data[$col + 1]
                                );

                                $Inventory->insertInventory($excel_data);
                                unset($allowed_locations[$location_id]);
                            }
                        }

                        /*
                         * now iterate through the array and check for which location_id no entry into item_quantities was made yet
                         * those get an entry with quantity as 0.
                         * unfortunately a bit duplicate code from above...
                         */
                        foreach ($allowed_locations as $location_id => $location_name) {
                            $item_quantity_data = array(
                                'item_id' => $item_id,
                                'location_id' => $location_id,
                                'quantity' => 0,
                            );
                            $Item_quantity->saveItemQuantities($item_quantity_data, $item_id, 1);

                            $excel_data = array(
                                'trans_items' => $item_id,
                                'trans_user' => $employee_id,
                                'trans_comment' => $comment,
                                'trans_location' => $location_id,
                                'trans_inventory' => 0
                            );

                            $Inventory->insertInventory($excel_data);
                        }
                    } else //insert or update item failure
                    {
                        $failCodes[] = $i;
                    }

                    ++$i;
                }

                if (count($failCodes) > 0) {
                    $message = lang('items_excel_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

                    echo json_encode(array('success' => FALSE, 'message' => $message));
                } else {
                    echo json_encode(array('success' => TRUE, 'message' => lang('items_lang.items_excel_import_success')));
                }
            } else {
                echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_excel_import_nodata_wrongformat')));
            }
        }
    }

    public function excel_import_update_prices()
    {
        return view('items/form_excel_import_update_prices');
    }
    
    public function do_excel_import_update_prices()
    {
        if ($_FILES['file_path']['error'] != UPLOAD_ERR_OK) {
            echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_excel_import_failed')));
        } else {
            $Item = new Item();
            $Item_taxes = new Item_taxes();
            if (($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE) {
                // Skip the first row as it's the table description
                fgetcsv($handle);
                $i = 1;

                $failCodes = array();
                if(request()->getPost('import_type')=='default'){
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        // XSS file data sanity check
                        $data = $this->xss_clean($data);

                        if (sizeof($data) == 5) {

                            $item_data = array(
                                'name' => $data[1],
                                'cost_price' => $data[2],
                                'unit_price' => $data[3]
                            );
                            $item_number = $data[0];
                            $invalidated = FALSE;
                            if ($item_number != '') {
                                $item_data['item_number'] = $item_number;
                                $invalidated = $Item->item_number_exists($item_number);
                                if($itemId = $Item->get_item_id($item_number)->item_id){
                                    $item_data['item_id'] = $itemId;
                                }
                            }
                        } else {
                            $invalidated = FALSE;
                        }

                        if ($invalidated && $Item->saveItem($item_data, $item_data['item_id'])) {
                            // echo json_encode(array('success' => FALSE, 'message' =>$invalidated, "message2" => $data));
                            $items_taxes_data = [];
                            //tax 1                    exit();
                            if (is_numeric($data[4]) && $data[4] != '') {
                                $items_taxes_data[] = array('name' => 'Total Tax', 'percent' => $data[4]);
                            }

                            // save tax values
                            if (count($items_taxes_data) > 0) {
                                $Item_taxes->saveItemTaxes($items_taxes_data, $item_data['item_id']);
                            }
                        } else //insert or update item failure
                        {
                            $failCodes[] = $i;
                        }

                        ++$i;
                    }
                }
              
                if (count($failCodes) > 0) {
                    $message = lang('items_lang.items_excel_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

                    echo json_encode(array('success' => FALSE, 'message' => $message));
                } else {
                    echo json_encode(array('success' => TRUE, 'message' => lang('items_lang.items_excel_import_success')));
                }
            } else {
                echo json_encode(array('success' => FALSE, 'message' => lang('items_lang.items_excel_import_nodata_wrongformat')));
            }
        }
    }





    public function check_numeric()
    {
        $result = true;

        foreach (request()->getGet() as $str) {
            $result = parse_decimals($str);
        }

        echo $result !== false ? 'true' : 'false';
    }

}
