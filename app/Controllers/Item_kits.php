<?php

namespace App\Controllers;

use App\Libraries\Gu;
use App\Libraries\BarcodeLib;
use App\Models\Appconfig;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Item_kit;
use App\Models\Item_kit_items;
use Config\Database;

class Item_kits extends SecureController
{
    public function __construct()
	{
		parent::__construct('item_kits');
	}

    private function _add_totals_to_item_kit($item_kit)
    {
        
        $item_kit->total_cost_price = 0;
        $item_kit->total_unit_price = 0;

        $Item_kit_items = new Item_kit_items();
        foreach ($Item_kit_items->get_info($item_kit->item_kit_id) as $key=> $item_kit_item) {

            $Item = new Item();
            $item_info = $Item->get_info($item_kit_item->item_id);
            foreach (get_object_vars($item_info) as $property => $value) {
                $item_info->$property = esc($value);
            }

            $item_kit->total_cost_price += $item_info->cost_price * $item_kit_item->quantity;
            $item_kit->total_unit_price += $item_info->unit_price * $item_kit_item->quantity;
        }

        return $item_kit;
    }

    public function index()
    {
   
        $data = $this->data;
        $data['table_headers'] = get_item_kits_manage_table_headers();
        return view('item_kits/manage', $data);
    }

    public function search()
    {
        $request = \Config\Services::request();

        $search = $request->getGet('search');
        $limit = $request->getGet('limit');
        $offset = $request->getGet('offset');
        $sort = $request->getGet('sort');
        $order = $request->getGet('order');


        $itemKitsModel = new \App\Models\Item_kit();
        $itemKits = $itemKitsModel->search($search, $limit, $offset, $sort, $order)->getResult();

        $totalRows = $itemKitsModel->get_found_rows($search);

        $dataRows = [];
        foreach ($itemKits as $item_kit) {
            // calculate the total cost and retail price of the Kit so it can be printed out in the manage table
            $item_kit = $this->_add_totals_to_item_kit($item_kit);
            $dataRows[] = get_item_kit_data_row($item_kit, $this);
        }

        return $this->response->setJSON(['total' => $totalRows, 'rows' => $dataRows]);
    }

    public function suggest_search()
    {
        $term = request()->getPost('term');
        $Item_kit = new Item_kit();
        $suggestions = $Item_kit->get_search_suggestions($term);
        return response()->setJSON($suggestions);
    }

    public function get_row($row_id)
    {

        $itemKitModel = new Item_kit();
        $itemKit = $itemKitModel->get_info($row_id);
        // Calculate the total cost and retail price of the Kit
        $item_kit = $this->_add_totals_to_item_kit($itemKit);

        return $this->response->setJSON(get_item_kit_data_row($item_kit, $this));
    }
    
    public function view($item_kit_id = -1)
	{
        $Item_kit = new Item_kit();
		$info = $Item_kit->get_info($item_kit_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['item_kit_info']  = $info;
		
		$items = array();
        $Item_kit_items = new Item_kit_items();
		foreach($Item_kit_items->get_info($item_kit_id) as $item_kit_item)
		{
            $Item = new Item();
			$item['name'] = $this->xss_clean($Item->get_info($item_kit_item->item_id)->name);
			$item['item_id'] = $this->xss_clean($item_kit_item->item_id);
			$item['quantity'] = $this->xss_clean($item_kit_item->quantity);
			
			$items[] = $item;
		}
		$data['item_kit_items'] = $items;

		return view("item_kits/form", $data);
	}

    public function save($itemKitId = -1)
    {
        try {
            $item_kit_data = [
                'name' => request()->getPost('name'),
                'description' => request()->getPost('description'),
            ];

            $item_kitModel = new Item_kit();

            if ($item_kitModel->saveItemKits($item_kit_data, $itemKitId)) {
                $success = true;

                if ($itemKitId == -1) {
                    $itemKitId = $item_kit_data['item_kit_id'];
                }

                $item_kit_items = [];
                $item_kit_item_data = request()->getPost('item_kit_item');

                if (!empty($item_kit_item_data)) {
                    foreach ($item_kit_item_data as $item_id => $quantity) {
                        $item_kit_items[] = [
                            'item_id' => $item_id,
                            'quantity' => $quantity,
                        ];
                    }

                    $Item_kit_items = new Item_kit_items();
                    $success = $Item_kit_items->saveItemKitItems($item_kit_items, $itemKitId);
                }

                if ($success) {
                    $item_kit_data = esc($item_kit_data);

                    return $this->response->setJSON(
                        [
                            'success' => TRUE,
                            'message' => lang('item_kits_lang.item_kits_successful_adding') . ' ' . $item_kit_data['name'],
                            'id'      => $itemKitId
                        ]
                    );
                } else {
                    $item_kit_data = esc($item_kit_data);

                    return $this->response->setJSON(
                        [
                            'success' => FALSE,
                            'message' => lang('item_kits_lang.item_kits_error_adding_updating') . ' ' . $item_kit_data['name'],
                            'id'      => -1
                        ]
                    );
                }
            } else {
                throw new \Exception('Error saving item kit');
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(
                [
                    'success' => FALSE,
                    'message' => $e->getMessage(),
                    'id'      => -1
                ]
            );
        }
    }

    public function delete()
    {
        $Item_kit = new Item_kit();

        $itemKitsToDelete = request()->getPost('ids');

        if (!empty($itemKitsToDelete)) {
            $deletedCount = $Item_kit->delete_list($itemKitsToDelete);

            if ($deletedCount > 0) {
                $message = lang('item_kits_lang.item_kits_successful_deleted') . ' ' . count($itemKitsToDelete) . ' ' . lang('item_kits_lang.item_kits_one_or_multiple');
                $response = ['success' => true, 'message' => $message];
            } else {
                $response = ['success' => false, 'message' => lang('item_kits_lang.item_kits_cannot_be_deleted')];
            }
        } else {
            $response = ['success' => false, 'message' => lang('item_kits_lang.item_kits_no_items_selected')];
        }

        return $this->response->setJSON($response);
    }

    public function generate_barcodes($item_kit_ids)
    {
        $barcodeLib = new BarcodeLib();
        $result = [];

        $item_kit_ids = explode(':', $item_kit_ids);
        $Item_kitModel = new Item_kit();
        foreach ($item_kit_ids as $item_kit_id) {
            $item_kit = $this->_add_totals_to_item_kit($Item_kitModel->get_info($item_kit_id));
            $item_kit_id = 'KIT ' . urldecode($item_kit_id);

            $result[] = [
                'name' => $item_kit->name,
                'item_id' => $item_kit_id,
                'item_number' => $item_kit_id,
                'cost_price' => $item_kit->total_cost_price,
                'unit_price' => $item_kit->total_unit_price
            ];
        }

        $data['items'] = $result;
        $barcodeConfig = $barcodeLib->get_barcode_config();
        // Set default barcode type to Code128 if not Code39 or Code128
        if ($barcodeConfig['barcode_type'] != 'Code39' && $barcodeConfig['barcode_type'] != 'Code128') {
            $barcodeConfig['barcode_type'] = 'Code128';
        }
        $data['barcodeConfig'] = $barcodeConfig;
        $data['barcodeLib'] = $barcodeLib;

        // Display barcodes
        return view('barcodes/barcode_sheet', $data);
    }

    public function upload_item_kits(){

        $online = Database::connect('online', true);
        $online->initialize();
        if(true == $online->connID){
        $Item_kit = new Item_kit();
        $item_kit = $Item_kit->kits_upload($online);
      
        if($item_kit){
      
        echo "Successfully Uploaded";

        }else{

        echo "Not uploaded!.Some issues occure.";

        }
    

        }else{
        	echo 'Online Database is not connected';
        }
	
	}

}
