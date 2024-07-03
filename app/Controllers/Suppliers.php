<?php

namespace App\Controllers;

use App\Libraries\Gu;
use App\Models\Person;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Appconfig;
use CodeIgniter\Controller;

class Suppliers extends Persons
{
	protected $Supplier;

	protected $appData;
	public function __construct()
	{
		parent::__construct('suppliers');

		$this->Supplier = new Supplier();
	}

	public function index($module_id = null)
	{
		$data = $this->data;
		$data['table_headers'] = $this->xss_clean(get_suppliers_manage_table_headers());
		return view('people/manage', $data);
	}

	/*
	Gets one row for a supplier manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_supplier_data_row($this->Supplier->get_info($row_id), $this));

		echo json_encode($data_row);
	}

	function get_suppliers()
	{
		return $this->Supplier->get_all()->getResult();
	}
	/*
	Returns Supplier table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->request->getVar('search');
		$limit  = $this->request->getVar('limit');
		$offset = $this->request->getVar('offset');
		$sort   = $this->request->getVar('sort');
		$order  = $this->request->getVar('order');
		$total_rows = 0;
		if ($search == '') {
			$suppliersdata = $this->get_suppliers();
			$data_rows = [];
			foreach ($suppliersdata as $supplier) {
				$data_rows[] = get_supplier_data_row($supplier, $this);
				$total_rows++;
			}
			$data_rows = $this->xss_clean($data_rows);
			return json_encode(['total' => $total_rows, 'rows' => $data_rows]);
		} else {
			$suppliers = $this->Supplier->search($search, $limit, $offset, $sort, $order);
			$total_rows = $this->Supplier->get_found_rows($search);


			$data_rows = array();
			foreach ($suppliers->getResult() as $supplier) {
				$data_rows[] = get_supplier_data_row($supplier, $this);
			}

			$data_rows = $this->xss_clean($data_rows);

			echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
		}
	}

	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Supplier->get_search_suggestions(request()->getGet('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Supplier->get_search_suggestions(request()->getPost('term'), FALSE));

		echo json_encode($suggestions);
	}

	/*
	Loads the supplier edit form
	*/
	public function view($supplier_id = -1)
	{
		
		$info = $this->Supplier->get_info($supplier_id);
		foreach (get_object_vars($info) as $property => $value) {
			$info->$property = $this->xss_clean($value);
		}

		$data['selected_person_name'] = ($supplier_id > 0 && isset($info->person_id)) ? $info->first_name . ' ' . $info->last_name : '';
		$data['selected_person_id']   = $info->person_id;
		$data['person_id']          = $supplier_id;
		$data['company_name']       = $info->company_name;
		$data['agency_name']       = $info->agency_name;
		$data['account_number']       = $info->account_number;
      

		// $data['person_info'] = $info;
		$Person = new Person();

		$supplier_info_array = $Person->get_all()->getResultArray();

		foreach ($supplier_info_array as $si) {
			$supplier_info_all_data = $si;
		}
		$data['employees']=$supplier_info_array;

		$data['all_info'] = $supplier_info_all_data;
		$appData = $this->appconfigModel->get_all();
		$data['appData'] = $appData;
		$data = $this->xss_clean($data);
		return view("suppliers/form", $data);
	}

	/*
	Inserts/updates a supplier
	*/
	// public function save($supplier_id = -1)
	// {
	// 	$person_data = array(
	// 		'first_name' => $this->input->post('first_name'),
	// 		'last_name' => $this->input->post('last_name'),
	// 		'gender' => $this->input->post('gender'),
	// 		'email' => $this->input->post('email'),
	// 		'phone_number' => $this->input->post('phone_number'),
	// 		'address_1' => $this->input->post('address_1'),
	// 		'address_2' => $this->input->post('address_2'),
	// 		'city' => $this->input->post('city'),
	// 		'state' => $this->input->post('state'),
	// 		'zip' => $this->input->post('zip'),
	// 		'country' => $this->input->post('country'),
	// 		'comments' => $this->input->post('comments')
	// 	);
	// 	$supplier_data = array(
	// 		'company_name' => $this->input->post('company_name'),
	// 		'agency_name' => $this->input->post('agency_name'),
	// 		'account_number' => $this->input->post('account_number') == '' ? NULL : $this->input->post('account_number')
	// 	);

	// 	if ($this->Supplier->save_supplier($person_data, $supplier_data, $supplier_id)) {
	// 		$supplier_data = $this->xss_clean($supplier_data);

	// 		//New supplier
	// 		if ($supplier_id == -1) {
	// 			echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('suppliers_successful_adding') . ' ' .
	// 				$supplier_data['company_name'], 'id' => $supplier_data['person_id']));
	// 		} else //Existing supplier
	// 		{
	// 			echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('suppliers_successful_updating') . ' ' .
	// 				$supplier_data['company_name'], 'id' => $supplier_id));
	// 		}
	// 	} else //failure
	// 	{
	// 		$supplier_data = $this->xss_clean($supplier_data);

	// 		echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('suppliers_error_adding_updating') . ' ' .
	// 			$supplier_data['company_name'], 'id' => -1));
	// 	}
	// }
	public function save($supplier_id = -1)
	{
     

	
		$supplierData = [
		
			'person_id'=>$this->request->getPost('employee_id'),
			'company_name' => $this->request->getPost('company_name'),
			'agency_name' => $this->request->getPost('agency_name'),
			'account_number' => $this->request->getPost('account_number') ?: null,
			'deleted'=>0
		];
    
	
		if ($this->Supplier->save_supplier($supplierData, $supplier_id)) {
			// New supplier
			if ($supplier_id == -1) {
				echo json_encode([
					'success' => true,
					'message' => lang('suppliers_lang.suppliers_successful_adding') . ' ' . $supplierData['company_name'],
					'id' => $supplier_id,
				]);
			} else {
				// Existing supplier	
				echo json_encode([
					'success' => true,
					'message' => lang('suppliers_lang.suppliers_successful_updating') . ' ' . $supplierData['company_name'],
					'id' => $supplier_id,
				]);
			}
		} else {
			echo json_encode([
				'success' => false,
				'message' => lang('Suppliers.suppliers_error_adding_updating') . ' ' . $supplierData['company_name'],
				'id' => -1,
			]);
		}
	}

	/*
	This deletes suppliers from the suppliers table
	*/
	public function delete()
	{
		$suppliers_to_delete = $this->xss_clean($this->request->getPost('ids'));
		if ($this->Supplier->delete_list($suppliers_to_delete)) {
			echo json_encode(array('success' => TRUE, 'message' => lang('suppliers_lang.suppliers_successful_deleted') . ' ' .
				count($suppliers_to_delete) . ' ' . lang('suppliers_lang.suppliers_one_or_multiple')));
		} else {
			echo json_encode(array('success' => FALSE, 'message' => lang('suppliers_lang.suppliers_cannot_be_deleted')));
		}
	}
}
