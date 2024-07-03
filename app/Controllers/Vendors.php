<?php

namespace App\Controllers;

use App\Libraries\Gu;
use App\Models\Employee;
use App\Models\Store;
use App\Models\Vendor;

class Vendors extends Persons
{
	protected $employeeModel;
	protected $Vendor;
	protected $Store;
	public function __construct()
	{
		parent::__construct('vendors');
        $this->employeeModel=new Employee();
		$this->Vendor=new Vendor();
		$this->Store=new Store();

	}
	
	public function index()
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_vendors_manage_table_headers());
		return view('people/manage', $data);
	}
	
	/*
	Gets one row for a vendor manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_vendor_data_row($this->Vendor->get_info($row_id), $this));

		echo json_encode($data_row);
	}
	
	/*
	Returns Vendor table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');

		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		$search = ($search !== null) ? $search : '';
		$sort = ($sort !== null) ? $sort : 'last_name';

		$vendors = $this->Vendor->search($search, $limit, $offset, $sort, $order, $employee_id);
		$total_rows = $this->Vendor->get_found_rows($search);

		$data_rows = array();
		foreach($vendors->getResult() as $vendor)
		{
			$data_rows[] = get_vendor_data_row($vendor, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}
	
	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Vendor->get_search_suggestions(request()->getGet('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Vendor->get_search_suggestions(request()->getPost('term'), FALSE));

		echo json_encode($suggestions);
	}
	
	/*
	Loads the vendor edit form
	*/
	public function view($vendor_id = -1)
	{
		$info = $this->Vendor->get_info($vendor_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['person_info'] = $info;

		$data['all_items'] = '';
		$data['selected_stores'] = '';
		$suggestions = [];
		foreach ($this->Store->get_all()->getResultArray() as $row) {
            $suggestions[$this->xss_clean($row['person_id'])] = $this->xss_clean($row['company_name']);
        }

		$data['stores'] = $suggestions;
		

		if($info->person_id){
			$selected_stores = [];
			foreach ($this->Vendor->get_all_stores($vendor_id)->getResultArray() as $row) {
	            $selected_stores[] = $this->xss_clean($row['store_id']);
	        }
	        $data['selected_stores'] = $selected_stores;
		}

		$array_items = [];
		$all_items = $this->Vendor->get_all_items($vendor_id)->getResultArray();

        foreach ($all_items as $item) {
            $item = $this->xss_clean($item);
            $array_items[] = $item['name'];
        }
        $data['all_items'] = implode(",",$array_items);

        // get unique items list for items suggestions list
        $data['vendor_items'] = json_encode($this->Vendor->get_vendor_items());
		$data['appData'] = $this->appconfigModel->get_all();

		return view("vendors/form", $data);
	}
	
	/*
	Inserts/updates a vendor
	*/
	public function save($vendor_id = -1)
	{

		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;

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
		$vendor_data = array(
			'company_name' => request()->getPost('company_name'),
			'agency_name' => request()->getPost('company_name'),
			'account_number' => request()->getPost('account_number') == '' ? NULL : request()->getPost('account_number'),
			'warehouse_id' => $employee_id,
		);

		$vendor_items_string = request()->getVar('items_list');
		$vendor_items_data = array_map('trim', explode(',', $vendor_items_string));
		$vendor_stores_data = request()->getPost('store_ids');

		if($this->Vendor->save_vendor($person_data, $vendor_data, $vendor_items_data, $vendor_stores_data, $vendor_id))
		{
			$vendor_data = $this->xss_clean($vendor_data);

			//New vendor
			if($vendor_id == -1)
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('vendors_lang.vendors_successful_adding').' '.
								$vendor_data['company_name'], 'id' => $vendor_data['person_id']));
			}
			else //Existing vendor
			{
				echo json_encode(array('success' => TRUE, 'message' => lang('vendors_lang.vendors_successful_updating').' '.
								$vendor_data['company_name'], 'id' => $vendor_id));
			}
		}
		else//failure
		{
			$vendor_data = $this->xss_clean($vendor_data);

			echo json_encode(array('success' => FALSE, 'message' =>  lang('vendors_lang.vendors_error_adding_updating').' '.
							$vendor_data['company_name'], 'id' => -1));
		}
	}
	
	/*
	This deletes vendors from the vendor table
	*/
	public function delete()
	{
		$vendors_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Vendor->delete_list($vendors_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => lang('vendors_lang.vendors_successful_deleted').' '.
							count($vendors_to_delete).' '.lang('vendors_lang.vendors_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => lang('vendors_lang.vendors_cannot_be_deleted')));
		}
	}
	
}
?>