<?php
namespace App\Controllers;

use App\Models\Employee;
use App\Models\Pizza_order;

class Pizza_orders_status extends SecureController
{
	protected $Employee;
	protected $Pizza_order;
	public function __construct()
	{
		parent::__construct('pizza_orders_status');
		$this->Employee = new Employee();
		$this->Pizza_order = new Pizza_order();
	}
	
	public function index()
	{		
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;

		$data['processed_orders'] = $processed = $this->Pizza_order->get_orders($store_id, 'Inprocess');
		//$data['processed_orders'] = array_reverse($processed->result());

		$data['completed_orders'] = $completed = $this->Pizza_order->get_orders($store_id, 'Completed');
		//$data['completed_orders'] = array_reverse($completed->result());

		//$data = [];
		return view('pizza_orders/status', $data);
	}

	public function get_updated_data($microtime, $status='')
	{
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;

		$data = $this->Pizza_order->get_updated_row($microtime, $status, $store_id);
		echo json_encode($data);
	}

	public function update_status($id='', $status='')
	{
		
	     $this->Pizza_order->update_status($id, $status);

		if($status === 'Completed'){
		
	        $pizza_order_data = $this->Pizza_order->get_pizza_order_info($id);
			if($pizza_order_data){
				
				$this->Pizza_order->redis_save($pizza_order_data['pizza_order'],$pizza_order_data['pizza_order_items']);

				$this->Pizza_order->pizza_order_completed($pizza_order_data['pizza_order'],$pizza_order_data['pizza_order_items']);
			}


			}
	 return redirect()->to('pizza_orders_status');
	}
}
?>