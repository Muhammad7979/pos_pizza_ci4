<?php 
namespace App\Controllers;

use App\Libraries\EmailLib;
use App\Libraries\Gu;
use App\Models\Notification;
use App\Models\Store;
use App\Models\Employee;
use App\Models\Raw_inventory;
use App\Models\Raw_order;
use App\Models\Raw_order_item;
use App\Models\Raw_item;
use App\Models\Raw_item_quantity;
use App\Models\Warehouse;
use App\Models\Vendor;
class Raw_orders extends SecureController
{
	protected $Store;
	protected $Employee;
	protected $Raw_order;
	protected $Raw_order_item;
	protected $Raw_item;
	protected $Warehouse;

	protected $Vendor;
	public function __construct()
	{
		parent::__construct('raw_orders');
		$this->Store = new Store();
		$this->Employee = new Employee();
		$this->Raw_order = new Raw_order();
		$this->Raw_order_item = new Raw_order_item();
		$this->Raw_item = new Raw_item();
		$this->Warehouse = new Warehouse();
		$this->Vendor = new Vendor();	
	}
	
	/*
	Add the total cost and retail price to a passed items kit retrieving the data from each singular item part of the kit
	*/
	private function _add_totals_to_raw_order($raw_order,$getStore=-1)
	{
		$raw_order->total_count = 0;
		$raw_order->total_delivered_count = 0;
		$raw_order->total_received_count = 0;
		$raw_order->company_name = '';
		if($value = $this->Raw_order_item->get_total_rows($raw_order->order_id)){
			$raw_order->total_count = $value;
		}
		if($value = $this->Raw_order_item->get_total_delivered_rows($raw_order->order_id)){
			if($raw_order->category==3)
			$raw_order->total_delivered_count = $value;
		}
		if($value = $this->Raw_order_item->get_total_received_rows($raw_order->order_id)){
			$raw_order->total_received_count = $value;
		}
		if($raw_order->category==1){
			if($value = $this->Raw_order_item->get_company_name($raw_order->person_id, 'vendors')){
				$raw_order->company_name = $value[0]['company_name'];
			}
		}elseif($raw_order->category==2){
			if($getStore==1){
				if($value = $this->Raw_order_item->get_company_name($raw_order->store_id, 'stores')){
					$raw_order->company_name = $value[0]['company_name'];
				}
			}
			if($getStore==2){
				if($value = $this->Raw_order_item->get_company_name($raw_order->person_id, 'warehouses')){
					$raw_order->company_name = $value[0]['company_name'];
				}
			}

		}elseif($raw_order->category==3){
			if($value = $this->Raw_order_item->get_company_name($raw_order->person_id, 'stores')){
				$raw_order->company_name = $value[0]['company_name'];
			}
		}
		return $raw_order;
	}
	
	public function index()
	{
	
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_raw_orders_manage_table_headers());
	
		// get stores
		$stores = $this->Store->get_stores();
		$data['filters'] = [];
		foreach ($stores as $value) {
			$data['filters'][$value['store_id']] = $value['store_name'];
		}

		$data['order_time_filter'] = ['all' => 'All', 'morning' => 'Morning', 'evening' => 'Evening'];
		
		//Allow User to Place New Order
		$data['allow_add_new_order'] = 1;

		//display stores filter to warehouse only
		if($this->Employee->has_module_grant('raw_items_stock', session()->get('person_id')))
		{
			$data['displayStoresFilter'] = 1;
		}else{
			$data['displayStoresFilter'] = 0;
		}
		$data['Employee'] = new Employee();
		return view('raw_orders/manage', $data);
	}

	public function print_order($id)
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_raw_orders_items_print_table_headers());

		$info = $this->Raw_order->get_info($id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['raw_order_info']  = $info;

		$data['order_from'] = $this->Employee->get_company_name($info->store_id,'stores')->company_name;
		if ($info->category==1) {
			$data['order_to'] = $this->Employee->get_company_name($info->person_id,'vendors')->company_name;
		}elseif ($info->category==2) {
			$data['order_to'] = $this->Employee->get_company_name($info->person_id,'warehouses')->company_name;
		}elseif ($info->category==3) {
			$data['order_to'] = $this->Employee->get_company_name($info->person_id,'stores')->company_name;
		}

		$data['total_order'] = $this->Raw_order_item->get_total_rows($info->order_id);
		$data['order_id'] = $info->order_id;
		$data['category'] = $info->category;
		
		return view('raw_orders/print', $data);
	}

	/*
	Returns orders table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');
		$order_id  = request()->getGet('order_id');
		$print_order  = request()->getGet('print_order');
		$category  = request()->getGet('category');
		$order_time_filter  = request()->getGet('order_time_filter');
        $sort = ($sort !== null) ? $sort : 'name';
		
		// if manage orders
		if(!$print_order){

			$filters = array(
				'start_date' => request()->getGet('start_date'),
	            'end_date' => request()->getGet('end_date'),
	            'store_ids' => [],
	        );
			if(!empty(request()->getGet('filters')[0])){
				$filters['store_ids'] = request()->getGet('filters');
			}
	        //check if any filter is set in the multiselect dropdown
	        //$filledup = array_fill_keys($this->input->get('filters'), TRUE);
	        //$filters = array_merge($filters, $filledup);


			$getStore = $person_id = $store_id = -1;
			if($this->Employee->has_module_grant('raw_items_stock', session()->get('person_id'))){
				$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
				$getStore = 1;
			}else{
				$store_id = $this->Employee->get_logged_in_employee_info()->person_id;
				$getStore = 2;
			}
			$raw_orders = $this->Raw_order->search($search, $person_id, $store_id, $order_id, $order_time_filter, $limit, $offset, $sort, $order, $filters);
			$total_rows = $this->Raw_order->get_found_rows($person_id, $store_id, $order_id, $order_time_filter, $search, $filters);

			$data_rows = array();
			foreach($raw_orders->getResult() as $raw_order)
			{
				// calculate the total cost and retail price of the Kit so it can be printed out in the manage table
				$raw_order = $this->_add_totals_to_raw_order($raw_order,$getStore);
				$data_rows[] = get_raw_order_data_row($raw_order, $this);
			}
		}else{
			// else for print order and items
			
	        $items = $this->Raw_order->items_search($search, $order_id, $category, $limit, $offset, $sort, $order);
	        $total_rows = $this->Raw_order->get_items_found_rows($search, $order_id, $category);

	        $data_rows = array();
	        foreach ($items->getResult() as $item) {
	            $data_rows[] = $this->xss_clean(get_raw_item_print_data_row($item, $this));
	        }
		}
		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Raw_order->get_search_suggestions(request()->getPost('term')));

		echo json_encode($suggestions);
	}

	public function suggest_companies($category)
	{
		$suggestions[0] = ['name'=>$this->xss_clean('Select')];

		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
		if($category==1){
			
			foreach ($this->Vendor->get_stores_all($employee_id)->getResultArray() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = 
				['name'=> $this->xss_clean($row['company_name']),'email'=>isset($row['email']) && $this->xss_clean($row['email'])];

	        }

		}elseif ($category==2) {

			foreach ($this->Warehouse->get_all()->getResultArray() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = 
				['name'=> $this->xss_clean($row['company_name']),'email'=>isset($row['email']) && $this->xss_clean($row['email'])];
	        }
			
		}elseif ($category==3) {

			foreach ($this->Store->get_all($employee_id)->getResultArray() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = 
				['name'=> $this->xss_clean($row['company_name']),'email'=>isset($row['email']) && $this->xss_clean($row['email'])];
	        }
			
		}
		echo json_encode($suggestions);
	}

	public function suggest()
    {
        $suggestions = [];
        if(request()->getPostGet('category')==2){
            $suggestions = $this->xss_clean($this->Raw_item->get_warehouse_search_suggestions(request()->getPostGet('person_id'),request()->getPostGet('term'),
            array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
        }elseif(request()->getPostGet('category')==1){
            $suggestions = $this->xss_clean($this->Raw_item->get_vendor_search_suggestions(request()->getPostGet('person_id'),request()->getPostGet('term'),
            array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
        }elseif(request()->getPostGet('category')==3){
            $suggestions = $this->xss_clean($this->Raw_item->get_store_search_suggestions(request()->getPostGet('person_id'),request()->getPostGet('term'),
            array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
        }
	
        echo json_encode($suggestions);

	

    }

	public function get_row($row_id)
	{	
		$getStore = -1;
		if($this->Employee->has_module_grant('raw_items_stock', session()->get('person_id'))){
			$getStore = 1;
		}else{
			$getStore = 2;
		}

		// calculate the total cost and retail price of the Kit so it can be added to the table refresh
		$raw_order = $this->_add_totals_to_raw_order($this->Raw_order->get_info($row_id),$getStore);
		
		echo json_encode(get_raw_order_data_row($raw_order, $this));
	}
	
	public function view($order_id = -1)
	{
		$info = $this->Raw_order->get_info($order_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['raw_order_info']  = $info;
		
		$items = array();
		$data['raw_order_items'] = $items;

		$data['companies'] = 'None';
		$data['selected_company'] = $data['category_selected'] = 0;
			
		if ($order_id>0) {
			$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
			$suggestions[0] = $this->xss_clean('Select');
			if($info->category==1){

				foreach($this->Raw_order_item->get_info($order_id) as $raw_order_item)
				{
					$item['name'] = $this->xss_clean($this->Raw_item->get_info_vendor($raw_order_item['item_id'])->name);
					$item['item_id'] = $this->xss_clean($raw_order_item['item_id']);
					$item['quantity'] = $this->xss_clean($raw_order_item['quantity']);
					$item['received_quantity'] = $this->xss_clean($raw_order_item['received_quantity']);
					$items[] = $item;
				}
				$data['raw_order_items'] = $items;
				
				foreach ($this->Vendor->get_stores_all($employee_id)->getResultArray() as $row) {
		            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
		        }

			}elseif ($info->category==2) {

				foreach($this->Raw_order_item->get_info($order_id) as $raw_order_item)
				{
					$item['name'] = $this->xss_clean($this->Raw_item->get_info($raw_order_item['item_id'])->name);
					$item['item_id'] = $this->xss_clean($raw_order_item['item_id']);
					$item['quantity'] = $this->xss_clean($raw_order_item['quantity']);
					$item['received_quantity'] = $this->xss_clean($raw_order_item['received_quantity']);
					$item['delivered_quantity'] = $this->xss_clean($raw_order_item['delivered_quantity']);
					
					$items[] = $item;
				}
				$data['raw_order_items'] = $items;

				foreach ($this->Warehouse->get_all()->getResultArray() as $row) {
		            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
		        }
				
			}elseif ($info->category==3) {

				foreach($this->Raw_order_item->get_info($order_id) as $raw_order_item)
				{
					// if($raw_order_item['is_vendor_item']==1){
					// 	$item['name'] = $this->xss_clean($this->Raw_item->get_info_vendor($raw_order_item['item_id'])->name);
					// }else{
					// 	$item['name'] = $this->xss_clean($this->Raw_item->get_info($raw_order_item['item_id'])->name);
					// }
					$item['name'] = $this->xss_clean($this->Raw_item->get_info($raw_order_item['item_id'])->name);
					$item['item_id'] = $this->xss_clean($raw_order_item['item_id']);
					$item['quantity'] = $this->xss_clean($raw_order_item['quantity']);
					$item['received_quantity'] = $this->xss_clean($raw_order_item['received_quantity']);
					$item['delivered_quantity'] = $this->xss_clean($raw_order_item['delivered_quantity']);
					
					$items[] = $item;
				}
				$data['raw_order_items'] = $items;

				foreach ($this->Store->get_all($employee_id)->getResultArray() as $row) {
		            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
		        }
				
			};
			$data['companies'] = $suggestions;
			$data['selected_company'] = $info->person_id;
			$data['category_selected'] = $info->category;

		}
		
		
		if($this->Employee->has_module_grant('raw_items_stock', session()->get('person_id'))){
			// delivery by warehouse
	          echo view("raw_orders/update", $data);
		}else{
			if ($order_id>0 && $info->is_delivered==1 && $info->category==2) {
				// receiving for warehouse items
				echo view("raw_orders/receiving", $data);
			}elseif ($order_id>0 && $info->is_delivered==1 && $info->category==1) {
				// receiving for vendor items
				echo view("raw_orders/receive_items", $data);
			}elseif ($order_id>0 && $info->is_delivered==1 && $info->category==3) {
				// receiving for store items
				echo view("raw_orders/receive_items", $data);
			}else{
				// new order form
				echo view("raw_orders/form", $data);
			}
		}
	}

	public function save($order_id = -1)
	{	 

		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $file_name = '';
		$qty = $i = $e = 0;
		$raw_order_items = [];
		$raw_order_items_detail = [];
		$raw_order_item = request()->getPost('raw_order_item');
		

		if(request()->getPost('raw_order_item') != NULL)
		{
			foreach ($raw_order_item as $key => $value) {
				
				$qty += $value;

				$raw_order_items[$i]['quantity'] = $value;
				$raw_order_items[$i]['item_id'] = $key;
				// $raw_order_items[$i]['item_name'] = $item_name->name;
				$i++;
			}
		}
		if($raw_order_items){

			foreach ($raw_order_item as $key => $value) {

				$item_name = $this->Raw_item->get_info($key);
				$raw_order_items_detail[$e]['quantity'] = $value;
				$raw_order_items_detail[$e]['item_id'] = $key;
				$raw_order_items_detail[$e]['item_name'] = $item_name->name;
				$e++;
			}

			helper('dompdf');
			$view_data = array('pdf_content' => $raw_order_items_detail);
			$html = view('email_pdf', $view_data);
	
			// Generate a file name with the current date and time
			$current_datetime = date('YmdHis');
			$file_name = 'Items_list_pdf_' . $current_datetime . '.pdf';
	
			// Generate PDF and save it to a file
			$pdf_content = pdf_create($html, $file_name, FALSE);
	
			// Save the PDF file to the server
			$file_path = FCPATH .'/images/email-pdfs/'; // Change this to the desired directory
			file_put_contents($file_path . $file_name, $pdf_content);


		}

		$order_time = '';
			
		$time = date("H");
		$timezone = date("e");

		if ($time < "12") {
        	$order_time = 'morning';
    	}else{
    		$order_time = 'evening';
    	}

		$raw_order_data = array(
			'category' => request()->getPost('category'),
			'person_id' => request()->getPost('person_id'),
			'store_id' => $employee_id,
			'description' => request()->getPost('description'),
			'order_quantity' => $qty,
			'order_status' => 'Pending',
			'order_time' => $order_time,
		);

		// Remove this when warehouse and other karkhane using pos
		if(request()->getPost('category')==2){
			$raw_order_data['is_delivered'] = 1;
		}

		// vendor have no acount thats y is_delivered = 1
		if(request()->getPost('category')==1){
			$raw_order_data['is_delivered'] = 1;
		}

		if($this->Raw_order->save_raw_order($raw_order_data, $order_id))
		{
			$success = TRUE;
			//New item ordered
			if ($order_id == -1)
			{
				$order_id = $raw_order_data['order_id'];
			}

			if(request()->getPost('raw_order_item') != NULL)
			{
				$success = $this->Raw_order_item->save_raw_order_item($raw_order_items, $order_id);
			}

			//web push notification start
				$from_id = $employee_id;
				$to_id = request()->getPost('person_id');
				$category = request()->getPost('category');
				$company_name = $this->Employee->get_company_name($employee_id,'stores')->company_name;
				$message = 'New Order From '.$company_name;

				$this->saveAndSendPusherNotification($order_id, $to_id, $from_id, $message, $category);
			//web push notification end

			$raw_order_data = $this->xss_clean($raw_order_data);

			if($success){
			$company_mailTo = request()->getPost('company_email');

	  	    if($company_mailTo==''){
			      $company_mailTo = 'muhammad.masood@unitedsol.net';
		     }
                $location = 'store';
				$emassege = $this->mail($location , $file_name , $company_mailTo , $company_name );
			
			}

			echo json_encode(array('success' => $success,
								'message' => lang('raw_order_lang.raw_orders_successful_adding').'. '.$emassege, 'id' => $order_id));
		}
		else//failure
		{
			$raw_order_data = $this->xss_clean($raw_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => lang('raw_order_lang.raw_orders_error_adding_updating'), 'id' => -1));
		}
	}
	
	public function delete()
	{
		$raw_orders_to_delete = $this->xss_clean(request()->getPost('ids'));
		
		if($this->Raw_order->delete_list($raw_orders_to_delete))
		{
			echo json_encode(array('success' => TRUE,
								'message' => lang('raw_order_lang.raw_orders_successful_deleted').' '.count($raw_orders_to_delete).' '.lang('raw_order_lang.raw_orders_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,
								'message' => lang('raw_order_lang.raw_orders_cannot_be_deleted')));
		}
	}

	public function update($order_id = -1)
	{

		$emessage = '';
		$Dqty = $i = 0;
		$raw_order_items = [];
		$raw_order_item = request()->getPost('raw_order_item_update');
		if(request()->getPost('raw_order_item_update') != NULL)
		{
			foreach ($raw_order_item as $key => $value) {
				$Dqty += $value;
				$raw_order_items[$i]['delivered_quantity'] = $value;
				$raw_order_items[$i]['item_id'] = $key;
				$i++;
			}
		}
		$raw_order_data = array(
			'delivered_description' => request()->getPost('delivered_description'),
			'delivered_quantity' => $Dqty,
			'order_status' => 'Delivered',
			'is_delivered' => '1',
		);

		if($this->Raw_order->save_raw_order($raw_order_data, $order_id))
		{
			$success = TRUE;

			if(request()->getPost('raw_order_item_update') != NULL)
			{
				$success = $this->Raw_order_item->update_raw_order_item($raw_order_items, $order_id, 'delivered_quantity');

				$raw_order = $this->Raw_order->get_info($order_id);
				$store_id = $raw_order->store_id;
				$person_id = $raw_order->person_id;
				$category = $raw_order->category;

				// Uncomment this if warehouse deliver order
				// foreach ($raw_order_items as $order_items) {

				// 	$item_id = $order_items['item_id'];
				// 	$updated_quantity = $order_items['delivered_quantity'];

				// 	$inv_data = array(
				//             'trans_date' => date('Y-m-d H:i:s'),
				//             'trans_items' => $item_id,
				//             'trans_user' => $store_id,
				//             'trans_location' => 1,
				//             'trans_comment' => $this->lang->line('raw_items_ordered_editing_of_quantity'),
				//             'trans_inventory' => -$updated_quantity
				//         );

				//     $success &= $this->Raw_inventory->insert($inv_data);
				// }
			}

			//web push notification start
				$from_id = $person_id;
				$to_id = $store_id;
				if($category==2){
					$company_name = $this->Employee->get_company_name($person_id,'warehouses')->company_name;
				}elseif($category==3){
					$category = 4;
					$company_name = $this->Employee->get_company_name($person_id,'stores')->company_name;
				}
				$message = 'Order Delivered From '.$company_name;
				$this->saveAndSendPusherNotification($order_id, $to_id, $from_id, $message, $category);
			//web push notification end

			$raw_order_data = $this->xss_clean($raw_order_data);

			if($success){
				$store_obj = $this->Employee->get_info($to_id);
				$file_name = '';
                $company_mailTo = $store_obj->email;
				if($company_mailTo==''){
                  $company_mailTo = 'muhammad.masood@unitedsol.net';
				}
				$location = 'warehouse';
				$emessage = $this->mail($location , $file_name , $company_mailTo , $company_name);

			}
			echo json_encode(array('success' => $success,
								'message' => lang('raw_order_lang.raw_orders_successful_adding').'. '.$emessage, 'id' => $order_id));
		}
		else//failure
		{
			$raw_order_data = $this->xss_clean($raw_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => lang('raw_order_lang.raw_orders_error_adding_updating'), 'id' => -1));
		}
	}

	public function update_receiving($order_id = -1)
	{
		$Rqty = $i = 0;
		$raw_order_items = [];
		$Raw_item_quantity = new Raw_item_quantity();
		$raw_order_item = request()->getPost('raw_order_item_received');
		if(request()->getPost('raw_order_item_received') != NULL)
		{
			foreach ($raw_order_item as $key => $value) {
				$Rqty += $value;
				$raw_order_items[$i]['received_quantity'] = $value;
				$raw_order_items[$i]['item_id'] = $key;
				$i++;
			}
		}
		$raw_order_data = array(
			'receiving_description' => request()->getPost('receiving_description'),
			'receiving_quantity' => $Rqty,
			'order_status' => 'Received',
			'is_received' => '1',
		);

		if($this->Raw_order->save_raw_order($raw_order_data, $order_id))
		{
			$success = TRUE;

			if(request()->getPost('raw_order_item_received') != NULL)
			{
				$success = $this->Raw_order_item->update_raw_order_item($raw_order_items, $order_id, 'received_quantity');

				// order to id
				$person_id = $this->Raw_order->get_info($order_id)->person_id;
				// order from id
				$store_id = $this->Raw_order->get_info($order_id)->store_id;
				$category = $this->Raw_order->get_info($order_id)->category;

				foreach ($raw_order_items as $order_items) {

					$item_id = $order_items['item_id'];
					$updated_quantity = $order_items['received_quantity'];

					//$item_type = $this->Raw_item->get_item_type($item_id);
					
					// category 1 for vendor
					if($category!=1)
					{
						// category 3 is for store
						if($category==3)
						{
							$item_quantity = $Raw_item_quantity->get_order_item_quantity($item_id, $person_id);

							$items_detail = array('item_id' => $item_id,
						        'store_id' => $person_id,
						        'available_quantity' => $item_quantity->available_quantity - $updated_quantity);
						
							$success &= $Raw_item_quantity->save_items($items_detail, $item_id, $person_id);
						}
						// else category for warehouse
						else
						{
							// comment it when warehouse deliver order
								$inv_data = array(
							            'trans_date' => date('Y-m-d H:i:s'),
							            'trans_items' => $item_id,
							            'trans_user' => $store_id,
							            'trans_location' => 1,
							            'trans_comment' => lang('raw_items_lang.raw_items_ordered_editing_of_quantity'),
							            'trans_inventory' => -$updated_quantity
							        );
                                $Raw_inventory = new Raw_inventory();
							    $success &= $Raw_inventory->insert_raw_inventory($inv_data);
						    // comment it when warehouse deliver order

							$item_quantity = $Raw_item_quantity->get_item_quantity($item_id, 1);

							$location_detail = array('item_id' => $item_id,
						        'location_id' => 1,
						        'quantity' => $item_quantity->quantity - $updated_quantity);

							$success &= $Raw_item_quantity->save_raw_item_quantity($location_detail, $item_id, 1);
						}
					}	
					$old_quantity = $Raw_item_quantity->get_order_item_quantity($item_id, $store_id);

					$items_detail = array('item_id' => $item_id,
				        'store_id' => $store_id,
				        'available_quantity' => (int)$old_quantity->available_quantity + (int)$updated_quantity);

					$success &= $Raw_item_quantity->save_items($items_detail, $item_id, $store_id);

				}

				//web push notification start
					$from_id = $store_id;
					$to_id = $person_id;
					$company_name = $this->Employee->get_company_name($store_id,'stores')->company_name;
					$message = 'Order Received By '.$company_name;

					$this->saveAndSendPusherNotification($order_id, $to_id, $from_id, $message, $category);
				//web push notification end
			}


			$raw_order_data = $this->xss_clean($raw_order_data);

			echo json_encode(array('success' => $success,
								'message' => lang('raw_order_lang.raw_orders_successful_adding'), 'id' => $order_id));
		}
		else//failure
		{
			$raw_order_data = $this->xss_clean($raw_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => lang('raw_order_lang.raw_orders_error_adding_updating'), 'id' => -1));
		}
	}
		
	public function updatenotification($order_id, $notification_id)
	{
		$Notification = new Notification();
		$Notification->updateNotification($notification_id);

		return redirect()->to('raw_orders/order/' . $order_id);
	}

	public function order($order_id)
	{
        $data = $this->data;
		$data['Employee'] = new Employee();
		$data['table_headers'] = $this->xss_clean(get_raw_orders_manage_table_headers());

		$data['order_time_filter'] = ['all' => 'All', 'morning' => 'Morning', 'evening' => 'Evening'];
		
		//Allow User to Place New Order
		$data['allow_add_new_order'] = 1;
		$data['order_id'] = $order_id;
		$data['displayStoresFilter'] = 0;
		return view('raw_orders/manage', $data);
	}

	public function mail($loc , $file_name = null , $mailTo = '', $company_name = 'Tehzeeb' ){
		 $mailTo='muhammad.masood@unitedsol.net';
				
		     $email_lib = new EmailLib();

            if($loc == 'store'){

				$to=$mailTo;
                $subject= 'Order from ' . $company_name;
                $message="$company_name has placed the order. Please check the pdf mention below.";
				// $attachment = base_url('/images/email-pdfs/'.$file_name.'.pdf');
				$attachment = base_url('/images/email-pdfs/'.$file_name);

                if($email_lib->sendEmail($to, $subject, $message, $attachment)){
					return 'Email successfully sent';
				}else{
				return 	'Email not sent';
				}

			}elseif($loc == 'warehouse'){

				$to=$mailTo;
                $subject=$company_name;
                $message='Order displaced. You will recieved soon';
				// $attachment = base_url('/images/email-pdfs/'.$file_name);

                if($email_lib->sendEmail($to, $subject, $message)){
					return 'Email successfully sent';
				}else{
				return 	'Email not sent';
				}

			}
              

	}
}
?>