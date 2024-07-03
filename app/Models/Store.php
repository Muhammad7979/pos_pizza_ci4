<?php
namespace App\Models;
class Store extends Person
{	
	/*
	Determines if a given person_id is a customer
	*/
	public function exists($person_id)
	{
		$builder = $this->db->table('stores')	
		                    ->join('people', 'people.person_id = stores.person_id')
		                    ->where('stores.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('stores')
		                   ->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Returns all the stores
	*/
	public function get_all($except = -1, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('stores')
		                    ->join('people', 'stores.person_id = people.person_id')			
		                    ->where('deleted', 0);

		if ($except>0) 
		$builder->where('stores.person_id !=', $except);

		$builder->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular store
	*/
	public function get_info($store_id)
	{
		$builder = $this->db->table('stores')	
		                    ->join('people', 'people.person_id = stores.person_id')
		                    ->join('employees', 'employees.person_id = stores.person_id')
		                    ->where('stores.person_id', $store_id);
		$query = $builder->get();
		
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $store_id is NOT an store
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from store table		
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->getFieldNames('stores') as $field)
			{
				$person_obj->$field = '';
			}
			foreach($this->db->getFieldNames('employees') as $field)
			{
				$person_obj->$field = '';
			}
			
			return $person_obj;
		}
	}
	
	/*
	Gets information about multiple stores
	*/
	public function get_multiple_info($stores_ids)
	{
		$this->db->from('stores');
		$this->db->join('people', 'people.person_id = stores.person_id');		
		$this->db->where_in('stores.person_id', $stores_ids);
		$this->db->order_by('last_name', 'asc');

		return $this->db->get();
	}
	
	/*
	Inserts or updates a stores
	*/
	public function save_store(&$person_data, &$store_data, &$employee_data, &$grants_data, $store_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		if(parent::savePerson($person_data,$store_id))
		{
			if(!$store_id || !$this->exists($store_id))
			{
				$store_id = $employee_data['person_id'] = $store_data['person_id'] = $person_data['person_id'];
				$success = $this->db->table('employees')->insert($employee_data);
				$success = $this->db->table('stores')->insert($store_data);
			}
			else
			{	
				
				$stores = $this->db->table('stores')->where('person_id', $store_id);
				$success = $stores->update($store_data);
				$employees = $this->db->table('employees')->where('person_id', $store_id);
				$success = $employees->update($employee_data);
			}
			if ($success) {
				$grants = $this->db->table('grants');
				$success = $grants->delete(array('person_id' => $store_id));
            } else {
                $success = false;
            }
            //Now insert the new grants
            if ($success) {
                foreach ($grants_data as $permission_id) {
                    //save new permissions to local
                    if ($success) {
						$grants = $this->db->table('grants');
                        $success = $grants->insert(array(
                            'permission_id' => $permission_id,
                            'person_id' => $store_id
                        ));
                    } else {
                        $success = false;
                    }

                }
            }
		}
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Deletes one store
	*/
	public function delete_store($store_id)
	{
		$this->db->where('person_id', $store_id);

		return $this->db->update('stores', array('deleted' => 1));
	}
	
	/*
	Deletes a list of stores
	*/
	public function delete_list($store_ids)
	{
		$builder = $this->db->table('stores')->whereIn('person_id', $store_ids);

		return $builder->update( array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find stores
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('stores');
		$this->db->join('people', 'stores.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->like('company_name', $search);
		$this->db->order_by('company_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$this->db->from('stores');
		$this->db->join('people', 'stores.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->distinct();
		$this->db->like('agency_name', $search);
		$this->db->where('agency_name IS NOT NULL');
		$this->db->order_by('agency_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$this->db->from('stores');
		$this->db->join('people', 'stores.person_id = people.person_id');
		$this->db->group_start();
			$this->db->like('first_name', $search);
			$this->db->or_like('last_name', $search); 
			$this->db->or_like('CONCAT(first_name, " ", last_name)', $search);
		$this->db->group_end();
		$this->db->where('deleted', 0);
		$this->db->order_by('last_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name);
		}

		if(!$unique)
		{
			$this->db->from('stores');
			$this->db->join('people', 'stores.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('email', $search);
			$this->db->order_by('email', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$this->db->from('stores');
			$this->db->join('people', 'stores.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('phone_number', $search);
			$this->db->order_by('phone_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$this->db->from('stores');
			$this->db->join('people', 'stores.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('account_number', $search);
			$this->db->order_by('account_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->account_number);
			}
		}

		//only return $limit suggestions
		if(count($suggestions > $limit))
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
		$builder = $this->db->table('stores')
		                    ->join('people', 'stores.person_id = people.person_id') 
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
	Perform a search on stores
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc')
	{
		$builder = $this->db->table('stores')
		                    ->join('people', 'stores.person_id = people.person_id')
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
		                    ->orderBy($sort, $order);
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Returns all the stores
	*/
	public function get_stores($except = -1, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('stores')		
		         ->select('person_id as store_id, company_name as store_name')
		         ->where('deleted', 0);
		
		if ($except>0) 
		$builder->where('stores.person_id !=', $except)
		        ->orderBy('stores.company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get()->getResultArray();		
	}
}
?>
