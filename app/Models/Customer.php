<?php
namespace App\Models;
use App\Models\Person;
use Config\Database;

class Customer extends Person
{	
	/*
	Determines if a given person_id is a customer
	*/
	protected $db;
    protected $table = 'ospos_customers';

	public function __construct()
    {
		parent::__construct();

		$this->db = \Config\Database::connect();
    }

	public function exists($person_id)
	{
		$builder = $this->db->table($this->table);
			
		$builder->join('people', 'people.person_id = customers.person_id');
		$builder->where('customers.person_id', $person_id);
		
		return ($builder->countAllResults() == 1);
	}

	/*
	Checks if account number exists
	*/
	public function account_number_exists($account_number, $person_id = '')
	{
	    $builder = $this->db->table($this->table);
		$builder->where('account_number', $account_number);

		if(!empty($person_id))
		{
			$builder->where('person_id !=', $person_id);
		}

		return ($builder->countAllResults() == 1);
	}	

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder= $this->db->table($this->table);
		$builder->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Returns all the customers
	*/
	public function get_all($rows = 0, $limit_from = 0)
	{
		$builder= $this->db->table($this->table);
		$builder->join('people', 'customers.person_id = people.person_id');			
		$builder->where('deleted', 0);
		$builder->orderBy('last_name', 'asc');

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular customer
	*/
	public function get_info($customer_id)
	{
		$builder = $this->db->table($this->table);
		$builder->join('people', 'people.person_id = customers.person_id');
		$builder->where('customers.person_id', $customer_id);
		
		if($builder->countAllResults() == 1)
		{
			return $builder->get()->getRow();
		}
		else
		{
			//Get empty base parent object, as $customer_id is NOT a customer
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from customer table
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->getFieldNames($this->table) as $field)
			{
				$person_obj->$field = '';
			}
			
			return $person_obj;
		}
	}
	
	/*
	Gets total about a particular customer
	*/
	public function get_totals($customer_id)
	{
		$builder = $this->db->table('sales')
		                    ->select('SUM(payment_amount) AS total')
		                    ->join('sales_payments', 'sales.sale_id = sales_payments.sale_id')
		                    ->where('sales.customer_id', $customer_id);

		return $builder->get()->getRow();
	}
	
	/*
	Gets information about multiple customers
	*/
	public function get_multiple_info($customer_ids)
	{
		$builder = $this->db->table($this->table);
		$builder->join('people', 'people.person_id = customers.person_id');		
		$builder->whereIn('customers.person_id', $customer_ids);
		$builder->orderBy('last_name', 'asc');

		return $builder->get();
	}
	
	/*
	Inserts or updates a customer
	*/
	public function save_customer(&$person_data, &$customer_data, $customer_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		$builder = $this->db->table($this->table);
		if(parent::save($person_data, $customer_id))
		{
			if(!$customer_id || !$this->exists($customer_id))
			{
				$customer_data['person_id'] = $person_data['person_id'];
				$success = $builder->insert($customer_data);
			}
			else
			{
				$builder->where('person_id', $customer_id);
				$success = $builder->update($customer_data);
			}
		}
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Deletes one customer
	*/
	public function deleteCustomer($customer_id)
	{
		$builder = $this->db->table($this->table)

		                    ->where('person_id', $customer_id);

		return $builder->update(array('deleted' => 1));
	}
	
	/*
	Deletes a list of customers
	*/
	public function delete_list($customer_ids)
	{
		$builder = $this->db->table($this->table)

		                   ->whereIn('person_id', $customer_ids);

		return $builder->update(array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find customers
	*/
	public function get_search_suggestions($search, $unique = TRUE, $limit = 25)
	{
		$suggestions = array();
		
		$builder = $this->db->table($this->table)
		                    ->join('people', 'customers.person_id = people.person_id')
		                    ->groupStart()
			                    ->like('first_name', $search)
			                    ->orLike('last_name', $search) 
			                    ->orLike('CONCAT(first_name, " ", last_name)', $search)
		                    ->groupEnd()
		                    ->where('deleted', 0)
		                    ->orderBy('last_name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name.' '.$row->last_name);
		}

		if(!$unique)
		{
			$builder = $this->db->table($this->table);
			$builder->join('people', 'customers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('email', $search);
			$builder->orderBy('email', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$builder = $this->db->table($this->table);
			$builder->join('people', 'customers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('phone_number', $search);
			$builder->orderBy('phone_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$builder = $this->db->table($this->table);
			$builder->join('people', 'customers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('account_number', $search);
			$builder->orderBy('account_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->account_number);
			}
		}
		
		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

 	/*
	Gets rows
	*/
	public function get_found_rows($search)
	{
		$builder = $this->db->table($this->table);
		$builder->join('people', 'customers.person_id = people.person_id');
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search);
			$builder->orLike('email', $search);
			$builder->orLike('phone_number', $search);
			$builder->orLike('account_number', $search);
			$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->groupEnd();
		$builder->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Performs a search on customers
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc')
	{
		$builder = $this->db->table($this->table);
		$builder->join('people', 'customers.person_id = people.person_id');
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search);
			$builder->orLike('email', $search);
			$builder->orLike('phone_number', $search);
			$builder->orLike('account_number', $search);
			$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->groupEnd();
		$builder->where('deleted', 0);
		$builder->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();	
	}
}
?>
