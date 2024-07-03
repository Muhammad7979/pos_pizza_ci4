<?php 
namespace App\Controllers;

use App\Libraries\Gu;
use App\Models\Employee;
use App\Models\Raw_order_item;
use App\Models\Store;
use App\Libraries\AppData;
use App\Models\Notification;
use App\Models\Raw_item;
use App\Models\Raw_order;
class Store_orders extends SecureController
{
	protected $Employee;
	protected $appData;
	protected $Store;
	protected $Raw_order;
	public function __construct()
	{
		parent::__construct('store_orders');
		$this->Employee = new Employee();
		$this->Store = new Store();
	    $this->appData = new AppData();
		$this->Raw_order = new Raw_order();
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
		$Raw_order_item = new Raw_order_item();
		if($value = $Raw_order_item->get_total_rows($raw_order->order_id)){
			$raw_order->total_count = $value;
		}
		if($value = $Raw_order_item->get_total_delivered_rows($raw_order->order_id)){
			if($raw_order->category==3)
			$raw_order->total_delivered_count = $value;
		}
		if($value = $Raw_order_item->get_total_received_rows($raw_order->order_id)){
			$raw_order->total_received_count = $value;
		}
		if($value = $Raw_order_item->get_company_name($raw_order->store_id, 'stores')){
			$raw_order->company_name = $value[0]['company_name'];
		}
		return $raw_order;
	}
	
	public function index()
	{
	    $data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_raw_orders_manage_table_headers());
		$data['allow_add_new_order'] = 0;

		$data['order_time_filter'] = ['all' => 'All', 'morning' => 'Morning', 'evening' => 'Evening'];

		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		// get stores expect specific
		$stores = $this->Store->get_stores($person_id);
		$data['filters'] = [];
		foreach ($stores as $value) {
			$data['filters'][$value['store_id']] = $value['store_name'];
		}
		$data['displayStoresFilter'] = 1;
        $data['Employee'] = $this->Employee;
		return view('raw_orders/manage', $data);
	}

	public function print_order($id)
	{
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
		$Raw_order_item = new Raw_order_item();
		$data['total_order'] = $Raw_order_item->get_total_rows($info->order_id);
		$data['order_id'] = $info->order_id;
		$data['category'] = $info->category;

		return view('raw_orders/print', $data);
	}
	/*
	Returns Item kits table data rows. This will be called with AJAX.
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
		$search = $search !== null ? $search : '';
		$sort = ($sort !== null) ? $sort : 'order_id';
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
			$getStore = $person_id = $store_id = -1;
			$store_id = $this->Employee->get_logged_in_employee_info()->person_id;

			$raw_orders = $this->Raw_order->search($search, $store_id, $person_id, $order_id, $order_time_filter, $limit, $offset, $sort, $order, $filters);
			$total_rows = $this->Raw_order->get_found_rows($person_id, $store_id, $order_id, $order_time_filter, $search, $filters);


			$data_rows = array();
			foreach($raw_orders->getResult() as $raw_order)
			{
				// calculate the total cost and retail price of the Kit so it can be printed out in the manage table
				$raw_order = $this->_add_totals_to_raw_order($raw_order);
				$data_rows[] = get_raw_order_data_row($raw_order, $this );
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

			$Raw_order_item = new Raw_order_item();
			$Raw_item = new Raw_item();
			foreach($Raw_order_item->get_info($order_id) as $raw_order_item)
			{	

				// if($raw_order_item['is_vendor_item']==1){
				// 	$item['name'] = $this->xss_clean($this->Raw_item->get_info_vendor($raw_order_item['item_id'])->name);
				// }else{
				// 	$item['name'] = $this->xss_clean($this->Raw_item->get_info($raw_order_item['item_id'])->name);
				// }
				$item['name'] = $this->xss_clean($Raw_item->get_info($raw_order_item['item_id'])->name);
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

			$data['companies'] = $suggestions;
			$data['selected_company'] = $info->store_id;
			$data['category_selected'] = $info->category;

		}
		
		return view("raw_orders/update", $data);
	}
	
	public function updatenotification($order_id, $notification_id)
	{
		$Notification = new Notification();
		$Notification->updateNotification($notification_id);

		redirect('store_orders/order/' . $order_id);
	}

	public function order($order_id)
	{

		$data['table_headers'] = $this->xss_clean(get_raw_orders_manage_table_headers());

		$data['order_time_filter'] = ['all' => 'All', 'morning' => 'Morning', 'evening' => 'Evening'];
		
		//Allow User to Place New Order
		$data['allow_add_new_order'] = 0;
		$data['order_id'] = $order_id;
		$data['displayStoresFilter'] = 0;
		return view('raw_orders/manage', $data);
	}

}
?>