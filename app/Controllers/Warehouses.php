<?php 
namespace App\Controllers;
use App\Controllers\Persons;
use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Models\Warehouse;
use App\Models\Employee;
use App\Models\Module;
class Warehouses extends Persons
{
	protected $Warehouse;
	protected $Employee;
	protected $Module;
	protected $appData;

	public function __construct()
	{
		parent::__construct('warehouses');
		$this->Warehouse = new Warehouse();
		$this->Employee = new Employee();
		$this->Module = new Module();
		$this->appData = new AppData();

	}
	
	public function index($module_id=null)
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_warehouses_manage_table_headers());
		return view('people/manage', $data);
	}
	
	/*
	Gets one row for a warehouse manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_warehouses_data_row($this->Warehouse->get_info($row_id), $this));

		echo json_encode($data_row);
	}
	
	/*
	Returns warehouse table data rows. This will be called with AJAX.
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
		$warehouses = $this->Warehouse->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Warehouse->get_found_rows($search);

		$data_rows = array();
		foreach($warehouses->getResult() as $warehouse)
		{
			$data_rows[] = get_warehouses_data_row($warehouse, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}
	
	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Warehouse->get_search_suggestions(request()->getGet('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Warehouse->get_search_suggestions(request()->getPost('term'), FALSE));

		echo json_encode($suggestions);
	}
	
	/*
	Loads the warehouse edit form
	*/
	public function view($warehouse_id = -1)
	{
		$person_info = $this->Warehouse->get_info($warehouse_id);
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
	            // if($module->module_id=='vendors' || $module->module_id=='raw_orders' || $module->module_id=='raw_items'){
	            // 	$module->grant = 1;
	            // }
	        }

            $modules[] = $module;
        }
        $data['all_modules'] = $modules;

        $permissions = array();
        foreach ($this->Module->get_all_subpermissions()->getResult() as $permission) {
            $permission->module_id = $this->xss_clean($permission->module_id);
            $permission->permission_id = $this->xss_clean($permission->permission_id);

            if($person_info->person_id){
	            $permission->grant = $this->xss_clean($this->Employee->has_grant($permission->permission_id, $person_info->person_id));
	        }else{
	            $permission->grant = '';
	            // if($permission->permission_id=='raw_items_stock'){
	            // 	$permission->grant = 1;
	            // }
	        }

            $permissions[] = $permission;
        }
		$appData = $this->appData->getAppData();
        $data['appData'] = $appData;
        $data['all_subpermissions'] = $permissions;
		return view("warehouses/form", $data);
	}
	
	/*
	Inserts/updates a warehouse
	*/
	public function save($warehouse_id = -1)
	{
		$person_data = array(
			'first_name' => request()->getVar('first_name'),
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
		$warehouse_data = array(
			'company_name' => request()->getPost('company_name'),
			'agency_name' => request()->getPost('company_name'),
			'account_number' => request()->getPost('account_number') == '' ? NULL : request()->getPost('account_number')
		);	
		$grants_data = request()->getPost('grants') != NULL ? request()->getPost('grants') : array();

		//Password has been changed OR first time password set
        if (request()->getPost('password') != '') {
            $employee_data = array(
                'username' => request()->getPost('username'),
                'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
                'hash_version' => 2
            );
        } else //Password not changed
        {
            $employee_data = array('username' => request()->getPost('username'));
        }
		if($this->Warehouse->save_warehouse($person_data, $warehouse_data, $employee_data, $grants_data, $warehouse_id))
		{
			$warehouse_data = $this->xss_clean($warehouse_data);

			//New warehouse
			if($warehouse_id == -1)
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('warehouses_lang.warehouses_successful_adding').' '.
								$warehouse_data['company_name'], 'id' => $warehouse_data['person_id']));
			}
			else //Existing warehouse
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('warehouses_lang.warehouses_successful_updating').' '.
								$warehouse_data['company_name'], 'id' => $warehouse_id));
			}
		}
		else//failure
		{
			$warehouse_data = $this->xss_clean($warehouse_data);

			echo json_encode(array('success' => FALSE, 'message' => lang('warehouses_lang.warehouses_error_adding_updating').' '.
							$warehouse_data['company_name'], 'id' => -1));
		}
	}
	
	/*
	This deletes warehouses from the warehouse table
	*/
	public function delete()
	{
		$warehouses_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Warehouse->delete_list($warehouses_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => lang('warehouses_lang.warehouses_successful_deleted').' '.
							count($warehouses_to_delete).' '.lang('warehouses_lang.warehouses_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => lang('warehouses_lang.warehouses_cannot_be_deleted')));
		}
	}
	
}
?>