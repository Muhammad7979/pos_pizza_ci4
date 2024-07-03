<?php

namespace App\Models;

use App\Models\Person;
use Config\Database;

class Supplier extends Person
{
	/*
	Determines if a given person_id is a customer
	*/
	protected $table = 'ospos_suppliers';
	public function __construct()
	{
		$this->db = Database::connect(); // Load the database
	}
	public function exists($supplier_id)
	{
		$builder = $this->db->table($this->table)
			->join('people', 'people.person_id = suppliers.person_id')
			->where('id', $supplier_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$suppliers = $this->db->table($this->table)
			->where('deleted', 0);

		return $suppliers->countAllResults();
	}

	/*
	Returns all the suppliers
	*/
	public function get_all($limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->where('deleted', 0)
			->orderBy('company_name', 'asc');
		if ($rows > 0) {
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Gets information about a particular supplier
	*/

	public function get_info($supplier_id)
	{
		$builder = $this->db->table($this->table)
			->join('people', 'people.person_id = suppliers.person_id','left')
			->where('suppliers.id', $supplier_id)
			->where('deleted',0)
			->get();

		if ($builder->getNumRows() == 1) {
			return $builder->getRow();
		} else {
			//Get empty base parent object, as $supplier_id is NOT an supplier
			$person_obj = parent::get_info(-1);

			//Get all the fields from supplier table		
			//append those fields to base parent object, we we have a complete empty object
			foreach ($builder->getFieldNames($this->table) as $field) {
				$person_obj->$field = '';
			}

			return $person_obj;
		}
	}

	/*
	Gets information about multiple suppliers
	*/
	public function get_multiple_info($suppliers_ids)
	{
		$builder = $this->db->table($this->table)
			->join('people', 'people.person_id = suppliers.person_id')
			->whereIn('suppliers.person_id', $suppliers_ids)
			->orderBy('last_name', 'asc');

		return $builder->get();
	}

	/*
	Inserts or updates a suppliers
	*/
	// public function save_supplier(&$supplier_data, $supplier_id = FALSE)
	// {
	// 	$success = FALSE;

	// 	//Run these queries as a transaction, we want to make sure we do all or nothing
	// 	$this->db->transStart();
	// 	$builder = $this->db->table($this->table)
	// 		->join('people', 'people.person_id = suppliers.person_id');

	// 	$builder->set($supplier_data);
	// 	$builder->where('suppliers.person_id', $supplier_id);
	// 	$success = $builder->update();


	// 	$this->db->transComplete();

	// 	$success &= $this->db->transStatus();

	// 	return $success;
	// }
	// public function save_supplier(array &$supplierData, $supplierId = -1)
	// {
	// 	$builder = $this->db->table($this->table);
	   
	// 	if ($supplierId === -1) {
	// 		print_r("hello new insert");
	// 		exit();
			
	// 		$builder->insert([
	// 			'person_id' => $supplierData['person_id'],
	// 			'company_name' => $supplierData['company_name'],
	// 			'agency_name' => $supplierData['agency_name'],
	// 			'account_number' => $supplierData['account_number'],
	// 			'deleted' => $supplierData['deleted']
	// 		]);
	// 		return true;
	// 	} else {
	// 		print_r("hello update");
	// 		exit();
			
	// 		$builder = $this->db->table($this->table)
	// 			->join('people', 'people.person_id = suppliers.person_id');
	
	// 		$builder->where('suppliers.person_id', $supplierId);
	// 		$builder->update($supplierData);
	
	// 		return true;
	// 	}
	// }
	
	public function save_supplier(&$supplierData, $supplier_id = -1)
{
	
	
    $builder = $this->db->table($this->table);
    
    if ($supplier_id == -1)
    {
		
		
        if ($builder->insert($supplierData))
        {
            return true; // Inserted successfully
        }
        else
        {
            return false; // Insertion failed
        }
    }
    else
    {
	
        $builder->where('id', $supplier_id);
        if ($builder->update(['company_name'=>$supplierData['company_name'],'agency_name'=>$supplierData['agency_name'],'account_number'=>$supplierData['account_number'],'deleted'=>$supplierData['deleted']]))
        {
            return true; // Updated successfully
        }
        else
        {
            return false; // Update failed
        }
    }
}


	/*
	Deletes one supplier
	*/
	public function deleteSupplier($supplier_id)
	{
		$builder = $this->db->table($this->table)
			->where('person_id', $supplier_id);

		return $builder->update('suppliers', array('deleted' => 1));
	}

	/*save_supplier
	Deletes a list of suppliers
	*/
	public function delete_list($supplier_ids)
	{
		
		$builder = $this->db->table($this->table)
			->whereIn('id', $supplier_ids);

		return $builder->update(array('deleted' => 1));
	}

	/*
	Get search suggestions to find suppliers
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->where('deleted', 0)
			->like('company_name', $search)
			->orderBy('company_name', 'asc');
		foreach ($builder->get()->getResultArray() as $row) {
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->where('deleted', 0)
			->distinct()
			->like('agency_name', $search)
			->where('agency_name IS NOT NULL')
			->orderBy('agency_name', 'asc');
		foreach ($builder->get()->getResultArray() as $row) {
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->groupStart()
			->like('first_name', $search)
			->orLike('last_name', $search)
			->orLike('CONCAT(first_name, " ", last_name)', $search)
			->groupEnd()
			->where('deleted', 0)
			->orderBy('last_name', 'asc');
		foreach ($builder->get()->getResultArray() as $row) {
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name);
		}

		if (!$unique) {
			$builder = $this->db->table($this->table)
				->join('people', 'suppliers.person_id = people.person_id')
				->where('deleted', 0)
				->like('email', $search)
				->orderBy('email', 'asc');
			foreach ($builder->get()->getResultArray() as $row) {
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$builder = $this->db->table($this->table)
				->join('people', 'suppliers.person_id = people.person_id')
				->where('deleted', 0)
				->like('phone_number', $search)
				->orderBy('phone_number', 'asc');
			foreach ($builder->get()->getResultArray() as $row) {
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$builder = $this->db->table($this->table)
				->join('people', 'suppliers.person_id = people.person_id')
				->where('deleted', 0)
				->like('account_number', $search)
				->orderBy('account_number', 'asc');
			foreach ($builder->get()->getResultArray() as $row) {
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->account_number);
			}
		}

		//only return $limit suggestions
		if (count($suggestions) > $limit) {
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

	/*
	Gets rows
	*/
	public function get_found_rows($search)
	{
		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->groupStart()
			->like('first_name', $search)
			->orLike('last_name', $search)
			->orLike('company_name', $search)
			->orLike('agency_name', $search)
			->orLike('email', $search)
			->orLike('phone_number', $search)
			->orLike('account_number', $search)
			->orLike('CONCAT(first_name, " ", last_name)', $search)
			->groupEnd()
			->where('deleted', 0);

		return $builder->get()->getNumRows();
	}

	/*
	Perform a search on suppliers
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc')
	{
		$builder = $this->db->table($this->table)
			->join('people', 'suppliers.person_id = people.person_id')
			->groupStart()
			->like('first_name', $search)
			->orLike('last_name', $search)
			->orLike('company_name', $search)
			->orLike('agency_name', $search)
			->orLike('email', $search)
			->orLike('phone_number', $search)
			->orLike('account_number', $search)
			->orLike('CONCAT(first_name, " ", last_name)', $search)
			->groupEnd()
			->where('deleted', 0)

			->orderBy('last_name', $order);

		if ($rows > 0) {
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
}
