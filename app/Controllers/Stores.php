<?php 
namespace App\Controllers;

use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Models\Store;
use App\Models\Module;
use App\Models\Employee;
class Stores extends Persons{
	protected $Store;
	protected $Module;
	protected $Employee;
	protected $appData;

	public function __construct()
	{

		parent::__construct('stores');
		$this->Store = new Store();
		$this->Module = new Module();
		$this->Employee = new Employee();
		$this->appData = new AppData();

	}
	
	public function index($module_id = null)
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_stores_manage_table_headers());
		return view('people/manage', $data);
	}
	
	/*
	Gets one row for a store manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_stores_data_row($this->Store->get_info($row_id), $this));

		echo json_encode($data_row);
	}
	
	/*
	Returns store table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');
		$search = $search !== null ? $search : '';
		$order = ($order !== null) ? $order : 'asc';
		$sort = ($sort !== null) ? $sort : 'last_name';
		$stores = $this->Store->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Store->get_found_rows($search);

		$data_rows = array();
		foreach($stores->getResult() as $store)
		{
			$data_rows[] = get_stores_data_row($store, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}
	
	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Store->get_search_suggestions(request()->getGet('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Store->get_search_suggestions(request()->getPost('term'), FALSE));

		echo json_encode($suggestions);
	}
	
	/*
	Loads the store edit form
	*/
	public function view($store_id = -1)
	{
		$person_info = $this->Store->get_info($store_id);
        foreach (get_object_vars($person_info) as $property => $value) {
            $person_info->$property = $this->xss_clean($value);
        }
        $data['person_info'] = $person_info;

		$modules = array();
        foreach ($this->Module->get_all_modules()->getResult() as $module) {
            $module->module_id = $this->xss_clean($module->module_id);

            if($person_info->person_id){
            	$module->grant = $this->xss_clean($this->Employee->has_grant($module->module_id, $person_info->person_id));
        	}else{
	            $module->grant = '';
	            // if($module->module_id=='store_items' || $module->module_id=='raw_orders' || $module->module_id=='store_orders' || $module->module_id=='counters' || $module->module_id=='counter_orders'){
	            // 	$module->grant = 1;
	            // }
	        }
            $modules[] = $module;
        }
        $data['all_modules'] = $modules;

        $permissions = array();
        foreach ($this->Module->get_all_subpermissions()->GetResult() as $permission) {
            $permission->module_id = $this->xss_clean($permission->module_id);
            $permission->permission_id = $this->xss_clean($permission->permission_id);
            
            if($person_info->person_id){
	            $permission->grant = $this->xss_clean($this->Employee->has_grant($permission->permission_id, $person_info->person_id));
	        }else{
	            $permission->grant = '';
	            // if($permission->permission_id=='store_items_stock'){
	            // 	$permission->grant = 1;
	            // }
	        }

            $permissions[] = $permission;
        }
        $data['all_subpermissions'] = $permissions;
		$appData = $this->appData->getAppData();
        $data['appData'] = $appData;
        $data['all_subpermissions'] = $permissions;
		return view("stores/form", $data);
	}
	
	/*
	Inserts/updates a store
	*/
	public function save($store_id = -1)
	{
		$person_data = array(
			'first_name' => request()->getPost('first_name'),
			'last_name' => request()->getPost('last_name'),
			'gender' => request()->getPost('gender'),
			'email' => request()->getPost('email'),
			'phone_number' => request()->getPost('phone_number'),
			'address_1' => request()->getPost('address_1'),
			'address_2' => request()->getPost('address_2'),
			'city' => request()->getPost('city'),
			'state' => request()->getPost('state'),
			'zip' => request()->getPost('zip'),
			'country' => request()->getPost('country'),
			'comments' => request()->getPost('comments')
		);
		$store_data = array(
			'company_name' => request()->getPost('company_name'),
			'agency_name' => request()->getPost('company_name'),
			'account_number' => request()->getPost('account_number') == '' ? NULL : request()->getPost('account_number')
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

		if($this->Store->save_store($person_data, $store_data, $employee_data, $grants_data, $store_id))
		{
			$store_data = $this->xss_clean($store_data);

			//New store
			if($store_id == -1)
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('stores_lang.stores_successful_adding').' '.
								$store_data['company_name'], 'id' => $store_data['person_id']));
			}
			else //Existing store
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('stores_lang.stores_successful_updating').' '.
								$store_data['company_name'], 'id' => $store_id));
			}
		}
		else//failure
		{
			$store_data = $this->xss_clean($store_data);

			echo json_encode(array('success' => FALSE, 'message' => lang('stores_lang.stores_error_adding_updating').' '.
							$store_data['company_name'], 'id' => -1));
		}
	}
	
	/*
	This deletes stores from the store table
	*/
	public function delete()
	{
		$stores_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Store->delete_list($stores_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => lang('stores_lang.stores_successful_deleted').' '.
							count($stores_to_delete).' '.lang('stores_lang.stores_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => lang('stores_lang.stores_cannot_be_deleted')));
		}
	}
	
}
?>