<?php 
namespace App\Controllers;

use App\Libraries\Gu;
use App\Libraries\AppData;
use App\Models\Employee;
use App\Models\Counter;
use CodeIgniter\HTTP\Request;

class Counters extends Persons
{
	protected $appData;
    protected $Employee;
	protected $Counter;

	public function __construct()
	{
		parent::__construct('counters');
		$this->appData = new AppData();
        $this->Employee = new Employee();
        $this->Counter = new Counter();
	}
	
	public function index()
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_counters_manage_table_headers());
		return view('people/manage', $data);
	}
	
	/*
	Gets one row for a counter manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_counters_data_row($this->Counter->get_info($row_id), $this));

		echo json_encode($data_row);
	}
	
	/*
	Returns counter table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');
		$search = $search !== null ? $search : '';
		$sort = ($sort !== null) ? $sort : 'people.person_id';
		$store_id = $this->Employee->get_logged_in_employee_info()->person_id;

		$counters = $this->Counter->search($search, $limit, $offset, $sort, $order, $store_id);
		$total_rows = $this->Counter->get_found_rows($search);

		$data_rows = array();
		foreach($counters->getResult() as $counter)
		{
			$data_rows[] = get_counters_data_row($counter, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}
	
	/*
	Gives search suggestions based on what is being searched for
	*/

	public function suggest($type=-1)
    {
    	$suggestions = $this->xss_clean($this->Counter->get_items_search_suggestions($type, request()->getPostGet('term'), array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));

        echo json_encode($suggestions);
    }

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Counter->get_search_suggestions(request()->getPost('term'), FALSE));

		echo json_encode($suggestions);
	}
	
	/*
	Loads the counter edit form
	*/
	public function view($counter_id = -1)
	{

		$person_info = $this->Counter->get_info($counter_id);
        foreach (get_object_vars($person_info) as $property => $value) {
            $person_info->$property = $this->xss_clean($value);
        }
        $data['person_info'] = $person_info;

        $data['selected_items'] = [];

		if($person_info->person_id){
			$selected_items = [];
			foreach ($this->Counter->get_all_items($counter_id)->getResultArray() as $row) {
	            $selected_items[] = $this->xss_clean($row);
	        }
	        
	        foreach ($this->Counter->get_all_sub_items($counter_id)->getResultArray() as $row) {
	            $selected_items[] = $this->xss_clean($row);
	        }

	        $data['selected_items'] = $selected_items;
		}

		$modules = [];
		foreach ($this->Counter->get_all_pizza_modules()->getResult() as $module) {
            $module->module_id = $this->xss_clean($module->module_id);

            if($person_info->person_id){
            	$module->grant = $this->xss_clean($this->Employee->has_grant($module->module_id, $person_info->person_id));
        	}else{
	            $module->grant = '';
	        }

	        if($module->module_id=='reports'){
            	$module->grant = 1;
            }

            $modules[] = $module;
        }
        $data['all_modules'] = $modules;

        $permissions = array();
        foreach ($this->Counter->get_all_reports_subpermissions()->getResult() as $permission) {
            $permission->module_id = $this->xss_clean($permission->module_id);
            $permission->permission_id = $this->xss_clean($permission->permission_id);

            if($person_info->person_id){
	            $permission->grant = $this->xss_clean($this->Employee->has_grant($permission->permission_id, $person_info->person_id));
	        }else{
	            $permission->grant = '';
	        }

	        if($permission->permission_id=='reports_counter_item'){
            	$permission->grant = 1;
            }

            $permissions[] = $permission;
        }
        $data['all_subpermissions'] = $permissions;

// echo "<pre>";

// print_r($permissions);

// echo "</pre>";

// exit();

		return view("counters/form", $data);
	}

    public function count_details($counter_id = -1)
    {
    	// get all pos items list
        $item_info = $this->Counter->get_all_items($counter_id);

        foreach (get_object_vars($item_info) as $property => $value) {
            $item_info->$property = $this->xss_clean($value);
        }
        $array = $item_info->getResultArray();

        // get all sub categories items list

        $rItem_info = $this->Counter->get_all_sub_items($counter_id);
        
        foreach (get_object_vars($rItem_info) as $property => $value) {
            $rItem_info->$property = $this->xss_clean($value);
        }
        $array1 = $rItem_info->getResultArray();

        $data['item_info'] = array_merge($array,$array1);

        return view('counters/form_count_details', $data);
    }

	/*
	Inserts/updates a counter
	*/
	public function save($counter_id = -1)
	{

		$store_id = $this->Employee->get_logged_in_employee_info()->person_id;

		$person_data = array(
			'first_name' => request()->getPost('first_name'),
			'last_name' => request()->getPost('last_name'),
			'gender' => request()->getPost('gender'),
			'email' => request()->getPost('email'),
			'phone_number' => request()->getPost('phone_number'),
			'comments' => request()->getPost('comments')
		);

		$is_production = 0;
		$items_data = [];
		if(request()->getPost('category')==2){
			$is_production = 1;

			$items_input = request()->getPost('category_item');

		}

		$counter_data = array(
			'company_name' => request()->getPost('company_name'),
			'agency_name' => request()->getPost('company_name'),
			'store_id' => $store_id,
			'category' => request()->getPost('category'),
			'special_counter' => (request()->getPost('special_counter')) ? request()->getPost('special_counter') : 0,
			//'is_production' => $is_production,
		);	

		$grants_data = request()->getPost('grants') != NULL ? request()->getPost('grants') : array();

		//Password has been changed OR first time password set
        if (request()->getPost('password') != '') {
            $employee_data = array(
                'username' => request()->getPost('username'),
                'password' => password_hash(request()->getVar('password'), PASSWORD_DEFAULT),
                'hash_version' => 2
            );
        } else //Password not changed
        {
            $employee_data = array('username' => request()->getPost('username'));
        }

		if($this->Counter->save_counter($person_data, $counter_data, $employee_data, $grants_data, $counter_id))
		{
			$counter_data = $this->xss_clean($counter_data);
			if($counter_id == -1)
			{
				$counter_id = $counter_data['person_id'];
			}
			//save counter items
			if($is_production == 1 && $items_input){
				foreach ($items_input as $key => $value) {
					$items_data[] = [
						'category' => $value,
						'item_id' => $key,
						'counter_id' => $counter_id,
					];
				}
				$this->Counter->save_counter_items($items_data, $counter_id);
			}
			//New counter
			if($counter_id == -1)
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('counters_lang.counters_successful_adding').' '.
								$counter_data['company_name'], 'id' => $counter_data['person_id']));
			}
			else //Existing counter
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('counters_lang.counters_successful_updating').' '.
								$counter_data['company_name'], 'id' => $counter_id));
			}
		}
		else//failure
		{
			$counter_data = $this->xss_clean($counter_data);

			echo json_encode(array('success' => FALSE, 'message' => lang('counters_lang.counters_error_adding_updating').' '.
							$counter_data['company_name'], 'id' => -1));
		}
	}

	/*
	This deletes counters from the counter table
	*/
	public function delete()
	{
		$counters_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Counter->delete_list($counters_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => lang('counters_lang.counters_successful_deleted').' '.
							count($counters_to_delete).' '.lang('counters_lang.counters_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => lang('counters_lang.counters_cannot_be_deleted')));
		}
	}
	
}
?>