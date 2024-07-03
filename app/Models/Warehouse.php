<?php
namespace App\Models;
class Warehouse extends Person
{	
	/*
	Determines if a given person_id is a customer
	*/
	public function exists($person_id)
	{
		$builder = $this->db->table('warehouses')	
		                    ->join('people', 'people.person_id = warehouses.person_id')
		                    ->where('warehouses.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('warehouses');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}
	
	/*
	Returns all the warehouses
	*/
	public function get_all($limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('warehouses')
		                    ->join('people', 'warehouses.person_id = people.person_id')			
		                    ->where('deleted', 0)
		                    ->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular warehouse
	*/
	public function get_info($warehouse_id)
	{
		$builder = $this->db->table('warehouses')	
		           ->join('people', 'people.person_id = warehouses.person_id')
		           ->join('employees', 'employees.person_id = warehouses.person_id')
		           ->where('warehouses.person_id', $warehouse_id);
		$query = $builder->get();
		
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $warehouse_id is NOT an warehouse
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from warehouse table		
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->getFieldNames('warehouses') as $field)
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
	Gets information about multiple warehouses
	*/
	public function get_multiple_info($warehouses_ids)
	{
		$this->db->from('warehouses');
		$this->db->join('people', 'people.person_id = warehouses.person_id');		
		$this->db->where_in('warehouses.person_id', $warehouses_ids);
		$this->db->order_by('last_name', 'asc');

		return $this->db->get();
	}
	
	/*
	Inserts or updates a warehouses
	*/
	public function save_warehouse(&$person_data, &$warehouse_data, &$employee_data, &$grants_data, $warehouse_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		if(parent::savePerson($person_data,$warehouse_id))
		{
			if(!$warehouse_id || !$this->exists($warehouse_id))
			{
				$warehouse_id = $employee_data['person_id'] = $warehouse_data['person_id'] = $person_data['person_id'];
				$success = $this->db->table('employees')->insert($employee_data);
				$success = $this->db->table('warehouses')->insert($warehouse_data);
			}
			else
			{	
				$warehouse = $this->db->table('warehouses')->where('person_id', $warehouse_id);
				$success = $warehouse->update($warehouse_data);
				$employees =$this->db->table('employees')->where('person_id', $warehouse_id);
				$success = $employees->update($employee_data);
			}

			if ($success) {
				$success = $this->db->table('grants')->delete(array('person_id' => $warehouse_id));
            } else {
                $success = false;
            }
            //Now insert the new grants
            if ($success) {
                foreach ($grants_data as $permission_id) {
                    //save new permissions to local
                    if ($success) {
                        $success = $this->db->table('grants')->insert(array(
                            'permission_id' => $permission_id,
                            'person_id' => $warehouse_id
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
	Deletes one warehouse
	*/
	public function delete_warehouse($warehouse_id)
	{
		$this->db->where('person_id', $warehouse_id);

		return $this->db->update('warehouses', array('deleted' => 1));
	}
	
	/*
	Deletes a list of warehouses
	*/
	public function delete_list($warehouse_ids)
	{
		$builder = $this->db->table('warehouses')->whereIn('person_id', $warehouse_ids);

		return $builder->update(array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find warehouses
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('warehouses')
		         ->join('people', 'warehouses.person_id = people.person_id')
		         ->where('deleted', 0)
		         ->like('company_name', $search)
		         ->orderBy('company_name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$this->db->from('warehouses');
		$this->db->join('people', 'warehouses.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->distinct();
		$this->db->like('agency_name', $search);
		$this->db->where('agency_name IS NOT NULL');
		$this->db->order_by('agency_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$this->db->from('warehouses');
		$this->db->join('people', 'warehouses.person_id = people.person_id');
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
			$this->db->from('warehouses');
			$this->db->join('people', 'warehouses.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('email', $search);
			$this->db->order_by('email', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$this->db->from('warehouses');
			$this->db->join('people', 'warehouses.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('phone_number', $search);
			$this->db->order_by('phone_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$this->db->from('warehouses');
			$this->db->join('people', 'warehouses.person_id = people.person_id');
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
		$builder = $this->db->table('warehouses')
		            ->join('people', 'warehouses.person_id = people.person_id');   
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search);
			$builder->orLike('company_name', $search);
			$builder->orLike('agency_name', $search);
			$builder->orLike('email', $search);
			$builder->orLike('phone_number', $search);
			$builder->orLike('account_number', $search);
			$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->groupEnd();
		$builder->where('deleted', 0);

		return $builder->get()->getNumRows();
	}
	
	/*
	Perform a search on warehouses
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort , $order )
	{
		$builder = $this->db->table('warehouses')
		                    ->join('people', 'warehouses.person_id = people.person_id');
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search);
			$builder->orLike('company_name', $search);
			$builder->orLike('agency_name', $search);
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


	public function get_all_warehouse_execpt($except = -1, $limit_from = 0, $rows = 0)
	{
		$this->db->from('warehouses');
		//$this->db->join('people', 'warehouses.person_id = people.person_id');			
		$this->db->where('deleted', 0);
		$this->db->where('warehouses.person_id !=', $except);
		$this->db->order_by('company_name', 'asc');
		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get()->result_array();		
	}
}
?>
