<?php
namespace App\Models;
class Counter extends Person
{	
	/*
	Determines if a given person_id is a customer
	*/
	public function exists($person_id)
	{
		$builder = $this->db->table('counters')
		                    ->join('people', 'people.person_id = counters.person_id')
		                    ->where('counters.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('counters')
		                    ->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Returns all the counters
	*/
	public function get_all($limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('counters')
		                    ->join('people', 'counters.person_id = people.person_id')	
		                    ->where('deleted', 0)
		                    ->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular counter
	*/
	public function get_info($counter_id)
	{
		$builder = $this->db->table('counters')	
		                     ->join('people', 'people.person_id = counters.person_id')
		                     ->join('employees', 'employees.person_id = counters.person_id')
		                     ->where('counters.person_id', $counter_id);
		$query = $builder->get();
		
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $counter_id is NOT an counter
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from counter table		
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->getFieldNames('counters') as $field)
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
	Gets information about multiple counters
	*/
	public function get_multiple_info($counters_ids)
	{
		$builder = $this->db->table('counters')
		                    ->join('people', 'people.person_id = counters.person_id')
		                    ->whereIn('counters.person_id', $counters_ids)
		                    ->orderBy('last_name', 'asc');

		return $builder->get();
	}
	
	/*
	Inserts or updates a counters
	*/
	public function save_counter(&$person_data, &$counter_data, &$employee_data,  &$grants_data, $counter_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		if(parent::savePerson($person_data,$counter_id))
		{
			if(!$counter_id || !$this->exists($counter_id))
			{
				$counter_id = $employee_data['person_id'] = $counter_data['person_id'] = $person_data['person_id'];
				$success = $this->db->table('employees')->insert($employee_data);
				$success = $this->db->table('counters')->insert($counter_data);
			}
			else
			{	
				
				$builder_counters = $this->db->table('counters')->where('person_id', $counter_id);
				$success = $builder_counters->update($counter_data);
                $builder_employees = $this->db->table('employees')->where('person_id', $counter_id);
				$success = $builder_employees->update($employee_data);
			}
			if ($success) {
				$success = $this->db->table('grants')->where(array('person_id' => $counter_id))->delete();
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
                            'person_id' => $counter_id
                        ));
                    } else {
                        $success = false;
                    }

                }
            }
            
		}
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $counter_id;
	}
	
	/*
	Deletes one counter
	*/
	public function delete_counter($counter_id)
	{
		$this->db->where('person_id', $counter_id);

		return $this->db->update('counters', array('deleted' => 1));
	}
	
	/*
	Deletes a list of counters
	*/
	public function delete_list($counter_ids)
	{
		$builder = $this->db->table('counters')->whereIn('person_id', $counter_ids);

		return $builder->update(array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find counters
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('counters');
		$this->db->join('people', 'counters.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->like('company_name', $search);
		$this->db->order_by('company_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$this->db->from('counters');
		$this->db->join('people', 'counters.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->distinct();
		$this->db->like('agency_name', $search);
		$this->db->where('agency_name IS NOT NULL');
		$this->db->order_by('agency_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$this->db->from('counters');
		$this->db->join('people', 'counters.person_id = people.person_id');
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
			$this->db->from('counters');
			$this->db->join('people', 'counters.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('email', $search);
			$this->db->order_by('email', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$this->db->from('counters');
			$this->db->join('people', 'counters.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('phone_number', $search);
			$this->db->order_by('phone_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$this->db->from('counters');
			$this->db->join('people', 'counters.person_id = people.person_id');
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
		$builder = $this->db->table('counters')
		                     ->join('people', 'counters.person_id = people.person_id')
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
	Perform a search on counters
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'people.person_id', $order = 'asc', $store_id)
	{
		$builder = $this->db->table('counters')
		                    ->join('people', 'counters.person_id = people.person_id')
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
		                    ->where('store_id', $store_id)
		
		                    ->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Returns all the counters of specific store
	*/
	public function get_all_of_store($store_id =-1, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('counters')			
		                    ->select('person_id, company_name')
		                    ->where('deleted', 0)
		                    ->where('store_id', $store_id)
		                    ->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}

	/*
	Returns all pizza counters of specific store
	*/
	public function get_all_pizza_counters_of_store($store_id =-1, $limit_from = 0, $rows = 0)
	{
		$this->db->select('person_id');
		$this->db->from('counters');			
		$this->db->where('deleted', 0);
		$this->db->where('category', 3);
		$this->db->where('store_id', $store_id);
		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		$suggestions = [];
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = $row->person_id;
		}
		return $suggestions;
	}

	public function get_all_items($counter_id = -1)
    {
        $builder = $this->db->table('counter_items')
    	                    ->select('counter_items.item_id,counter_items.counter_id,items.name,items.category,counter_items.category as type')
                            ->join('items','items.item_id=counter_items.item_id')
                            ->where('counter_id', $counter_id)
                            ->where('counter_items.category', 1);

        return $builder->get();
    }

    public function get_all_sub_items($counter_id = -1)
    {

        $builder = $this->db->table('counter_items')
                            ->select('counter_items.item_id,counter_items.counter_id,raw_items.name as name,raw_items.category,counter_items.category as type')
                            ->join('raw_items','raw_items.item_id=counter_items.item_id')
                            ->where('counter_id', $counter_id)
                            ->where('counter_items.category', 2);

		return $builder->get();
    }

    public function save_counter_items($items_data='', $counter_id = -1)
    {
    	$success = true;

    	$this->delete_items($counter_id);

    	if(count($items_data)>0){
	    	$success = $this->db->insert_batch('counter_items', $items_data);
    	}
        return $success;
    }

    /*
	Deletes items
	*/
	public function delete_items($counter_id)
	{
		return $this->db->delete('counter_items', array('counter_id' => $counter_id)); 
	}

	public function get_items_search_suggestions($type, $search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		
		$suggestions = array();

		if($type==1){
			// suggest item from items table
			$builder = $this->db->table('items')
			                    ->select('item_id, name, item_number, category')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->like('name', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->name, 'category' => $row->category, 'type' => 1);
			}

			$builder = $this->db->table('items')
			                    ->select('item_id, item_number, name, category')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->like('item_number', $search)
			                    ->orderBy('item_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->item_number." ".$row->name, 'category' => $row->category, 'type' => 1);
			}
		}elseif($type==2){

			// suggest item from raw_items table
			$builder = $this->db->table('raw_items')
			                    ->select('item_id,category, name')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->where('item_type', 4)
			                    ->like('name', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{

				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->name, 'category' => $row->category, 'type' => 2);
			}

			$builder = $this->db->table('raw_items')
			                    ->select('item_id,category, name')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->where('item_type', 4)
			                    ->like('item_number', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{

				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->item_number." ".$row->name, 'category' => $row->category, 'type' => 2);
			}
		}else{
			// suggest item from items table
			$builder = $this->db->table('items')
			                    ->select('item_id, name, item_number, category')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->like('name', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->name, 'category' => $row->category, 'type' => 1);
			}

			$builder = $this->db->table('items')
			                    ->select('item_id, item_number, name, category')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->like('item_number', $search)
			                    ->orderBy('item_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->item_number." ".$row->name, 'category' => $row->category, 'type' => 1);
			}

			// suggest item from raw_items table
			$builder = $this->db->table('raw_items')
			                    ->select('item_id,category, name')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->where('item_type', 4)
			                    ->like('name', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{

				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->name, 'category' => $row->category, 'type' => 2);
			}

			$builder = $this->db->table('raw_items')
			                    ->select('item_id,category, name')
			                    ->where('deleted', $filters['is_deleted'])
			                    ->where('item_type', 4)
			                    ->like('item_number', $search)
			                    ->orderBy('name', 'asc');
			foreach($builder->get()->getResult() as $row)
			{

				$suggestions[] = array('value' => $row->item_id,
	                'label' => $row->item_number." ".$row->name, 'category' => $row->category, 'type' => 2);
			}

		}
		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

		return $suggestions;
	}


	public function get_all_pizza_modules()
	{
		$builder = $this->db->table('modules')
		                    ->like('module_id', 'pizza' ,'both')
		                    ->orLike('module_id', 'report' ,'both')
		                    ->orLike('module_id', 'production' ,'both')
		                    ->orderBy('sort', 'asc');

		return $builder->get();		
	}

	public function get_all_reports_subpermissions()
	{
		$builder = $this->db->table('permissions')
		                    ->join('modules', 'modules.module_id = permissions.module_id')
		// can't quote the parameters correctly when using different operators..
		                    ->like('permission_id', 'reports_counter_item' ,'both')
		                    ->orLike('permission_id', 'reports_pizza_stock' ,'both')
		                    ->orLike('permission_id', 'pizza_orders_items' ,'both')
		                    ->where($this->db->getPrefix().'modules.module_id!=', 'permission_id', FALSE);

		return $builder->get();
	}

}
?>
