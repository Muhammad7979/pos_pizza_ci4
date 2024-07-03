<?php 
namespace App\Controllers;

use App\Models\Employee;
use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Models\Counter_order;
use App\Models\Counter_order_item;
use App\Models\Notification;

class Counter_orders extends SecureController
{
	protected $Employee;
	protected $Counter_order;
	protected $appData;
	protected $Counter_order_item;
	public function __construct()
	{
		parent::__construct('counter_orders');
		$this->Employee = new Employee();
		$this->appData = new AppData();
		$this->Counter_order = new Counter_order();
		$this->Counter_order_item = new Counter_order_item();
	}
	
	/*
	Add the total cost and retail price to a passed items kit retrieving the data from each singular item part of the kit
	*/
	private function _add_totals_to_counter_order($counter_order)
	{
		$counter_order->total_count = 0;
		$counter_order->total_delivered_count = 0;
		$counter_order->total_received_count = 0;
		//$counter_order->company_name = '';
		if($value = $this->Counter_order_item->get_total_rows($counter_order->order_id)){
			$counter_order->total_count = $value;
		}
		if($value = $this->Counter_order_item->get_total_delivered_rows($counter_order->order_id)){
			$counter_order->total_delivered_count = $value;
		}
		if($value = $this->Counter_order_item->get_total_received_rows($counter_order->order_id)){
			$counter_order->total_received_count = $value;
		}
		if($value = $this->Counter_order_item->get_company_name($counter_order->person_id, 'counters')){
			$counter_order->company_name = $value[0]['company_name'];
		}
		return $counter_order;
	}
	
	public function index()
	{
	    
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_counter_orders_manage_table_headers());
		return view('counter_orders/manage', $data);
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

		$search = $search !== null ? $search : '';
		$sort = ($sort !== null) ? $sort : 'order_id'; 
		
		if($this->Employee->has_module_grant('reports_counter_item', session()->get('person_id'))){
			$store_id = -1;
			$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		}else{
			$store_id = $this->Employee->get_logged_in_employee_info()->person_id;
			$person_id = -1;
		}

		$counter_orders = $this->Counter_order->search($search, $limit, $offset, $sort, $order, $store_id, $person_id, $order_id);
		$total_rows = $this->Counter_order->get_found_rows($store_id, $order_id, $search);

		$data_rows = array();
		foreach($counter_orders->getResult() as $counter_order)
		{
			// calculate the total cost and retail price of the Kit so it can be printed out in the manage table
			$counter_order = $this->_add_totals_to_counter_order($counter_order);
			$data_rows[] = get_counter_order_data_row($counter_order, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Counter_order->get_search_suggestions($this->input->post('term')));

		echo json_encode($suggestions);
	}

	public function suggest_companies($category)
	{
		$suggestions[0] = $this->xss_clean('Select');
		if($category==1){
			$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
			foreach ($this->Vendor->get_stores_all($employee_id)->result_array() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
	        }

		}elseif ($category==2) {

			foreach ($this->Warehouse->get_all()->result_array() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
	        }
			
		};
		echo json_encode($suggestions);
	}


	public function get_row($row_id)
	{
		// calculate the total cost and retail price of the Kit so it can be added to the table refresh
		$counter_order = $this->_add_totals_to_counter_order($this->Counter_order->get_info($row_id));
		
		echo json_encode(get_counter_order_data_row($counter_order, $this));
	}

	public function save($order_id = -1)
	{
		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

		$qty = $i = 0;
		$counter_order_items = [];
		$counter_order_item = $this->input->post('counter_order_item');
		if($this->input->post('counter_order_item') != NULL)
		{
			foreach ($counter_order_item as $key => $value) {
				$qty += $value;
				$counter_order_items[$i]['quantity'] = $value;
				$counter_order_items[$i]['item_id'] = $key;
				$i++;
			}
		}
		$counter_order_data = array(
			'category' => $this->input->post('category'),
			'person_id' => $this->input->post('person_id'),
			'store_id' => $employee_id,
			'description' => $this->input->post('description'),
			'order_quantity' => $qty,
			'order_status' => 'Pending',
		);

		if($this->input->post('category')==1){
			$counter_order_data['is_delivered'] = 1;
		}

		if($this->Counter_order->save($counter_order_data, $order_id))
		{
			$success = TRUE;
			//New item ordered
			if ($order_id == -1)
			{
				$order_id = $counter_order_data['order_id'];
			}

			if($this->input->post('counter_order_item') != NULL)
			{
				$success = $this->Counter_order_item->save($counter_order_items, $order_id);
			}

			$counter_order_data = $this->xss_clean($counter_order_data);

			echo json_encode(array('success' => $success,
								'message' => $this->lang->line('counter_orders_successful_adding'), 'id' => $order_id));
		}
		else//failure
		{
			$counter_order_data = $this->xss_clean($counter_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => $this->lang->line('counter_orders_error_adding_updating'), 'id' => -1));
		}
	}
	
	public function delete()
	{
		$counter_orders_to_delete = $this->xss_clean($this->input->post('ids'));
		
		if($this->Counter_order->delete_list($counter_orders_to_delete))
		{
			echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('counter_orders_successful_deleted').' '.count($counter_orders_to_delete).' '.$this->lang->line('counter_orders_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,
								'message' => $this->lang->line('counter_orders_cannot_be_deleted')));
		}
	}

	public function view($order_id = -1)
	{
		$info = $this->Counter_order->get_info($order_id);
		// echo "<pre>";
		// print_r($info);
		// exit();
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['counter_order_info']  = $info;
		
		$items = array();
		$data['counter_order_items'] = $items;

		$data['companies'] = 'None';
		$data['selected_company'] = $data['category_selected'] = 0;
		
		if ($order_id>0) {
			$suggestions[0] = $this->xss_clean('Select');

			foreach($this->Counter_order_item->get_info($order_id) as $counter_order_item)
			{
				if ($this->xss_clean($this->Store_item->get_info($counter_order_item['item_id'])->name)) {
					$item['name'] = $this->xss_clean($this->Store_item->get_info($counter_order_item['item_id'])->name);
				}else{
					$item['name'] = $this->xss_clean($this->Store_item->get_info_vendor($counter_order_item['item_id'])->name);
				}
				$item['item_id'] = $this->xss_clean($counter_order_item['item_id']);
				$item['quantity'] = $this->xss_clean($counter_order_item['quantity']);
				$item['received_quantity'] = $this->xss_clean($counter_order_item['received_quantity']);
				$items[] = $item;
			}
			$data['counter_order_items'] = $items;
			$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
			foreach ($this->Vendor->get_stores_all($employee_id)->result_array() as $row) {
	            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
	        }
			$data['companies'] = $suggestions;
			$data['selected_company'] = $info->person_id;
			$data['category_selected'] = $info->category;

		}
		
		$this->load->view("counter_orders/update", $data);
	}

	public function update($order_id = -1)
	{

		$Dqty = $i = 0;
		$counter_order_items = [];
		$counter_order_item = $this->input->post('counter_order_item_update');
		if($this->input->post('counter_order_item_update') != NULL)
		{
			foreach ($counter_order_item as $key => $value) {
				$Dqty += $value;
				$counter_order_items[$i]['delivered_quantity'] = $value;
				$counter_order_items[$i]['item_id'] = $key;
				$i++;
			}
		}
		$counter_order_data = array(
			'delivered_description' => $this->input->post('delivered_description'),
			'delivered_quantity' => $Dqty,
			'order_status' => 'Delivered',
			'is_delivered' => '1',
		);

		if($this->Counter_order->save($counter_order_data, $order_id))
		{
			$success = TRUE;

			if($this->input->post('counter_order_item_update') != NULL)
			{
				$success = $this->Counter_order_item->update($counter_order_items, $order_id, 'delivered_quantity');
			}

			$counter_order = $this->Counter_order->get_info($order_id);
			$store_id = $counter_order->store_id;
			$person_id = $counter_order->person_id;
			// Expo Notification
			$this->saveAndSendExpoNotification('Order Delivered', $store_id, $person_id, $order_id);

			$counter_order_data = $this->xss_clean($counter_order_data);

			echo json_encode(array('success' => $success,
								'message' => $this->lang->line('counter_orders_successful_adding'), 'id' => $order_id));
		}
		else//failure
		{
			$counter_order_data = $this->xss_clean($counter_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => $this->lang->line('counter_orders_error_adding_updating'), 'id' => -1));
		}
	}

	public function updatenotification($order_id, $notification_id)
	{
		$Notification = new Notification();
		$order_id = $Notification->updateNotification($notification_id);

		return redirect()->to('counter_orders/order/' . $order_id);
	}

	public function order($order_id)
	{
        $data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_counter_orders_manage_table_headers());

		//Allow User to Place New Order
		$data['order_id'] = $order_id;

		return view('counter_orders/manage', $data);
	}
	
}
?>