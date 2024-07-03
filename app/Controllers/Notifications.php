<?php 
namespace App\Controllers;

use App\Models\Employee;
use App\Models\Notification;

class Notifications extends SecureController
{
	protected $Notification;
	public function __construct()
	{
		parent::__construct();
		$this->Notification = new Notification();
	}

	public function index()
	{
        $data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_notifications_manage_table_headers());
		return view('notifications', $data);
	}


	/*
	Returns notifications table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');
		$search = ($search !== null) ? $search : '';
        $sort = ($sort !== null) ? $sort : 'created_at';
		$Employee = new Employee();
		$employee = $Employee->get_logged_in_employee_info()->person_id;
		
		// check employee account type like warehouse/store/counter
		if ($this->Notification->getEmployeeType($employee,'warehouses')) {
			$category = [2];
		}elseif ($this->Notification->getEmployeeType($employee,'stores')) {
			$category = [0,2,3,4];
		}elseif ($this->Notification->getEmployeeType($employee,'counters')) {
			$category = [0];
		}

		$notifications = $this->Notification->search($employee, $category, $search, $limit, $offset, $sort, $order);
		$total_rows = $this->Notification->get_found_rows($employee,$category,$search);

		$data_rows = array();
		foreach($notifications->getResult() as $notification)
		{
			if ($notification->category==0) {
				if ($notification->status==0) {
					$notification->url = base_url().'counter_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
				}else{
					$notification->url = base_url().'counter_orders/order/'.$notification->order_id;
				}
			}elseif($notification->category==2 || $notification->category==4) {
				if ($notification->status==0) {
					$notification->url = base_url().'raw_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
				}else{
					$notification->url = base_url().'raw_orders/order/'.$notification->order_id;
				}
			}elseif($notification->category==3) {
				if ($notification->status==0) {
					$notification->url = base_url().'store_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
				}else{
					$notification->url = base_url().'store_orders/order/'.$notification->order_id;
				}
			}
			
			if($Employee->has_module_grant('reports_counter_item', $employee)){
				$notification->url = 'javascript:';
			}
			$data_rows[] = get_notifications_data_row($notification, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	/*
	This deletes notification from the notifications table
	*/
	public function delete()
	{
		$notifications_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Notification->delete_list($notifications_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => lang('notifications_lang.notifications_successful_deleted').' '.
							count($notifications_to_delete).' '.lang('notifications_lang.notifications_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => lang('notifications_lang.notifications_cannot_be_deleted')));
		}
	}
}
?>