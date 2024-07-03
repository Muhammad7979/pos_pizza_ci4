<?php
namespace App\Models;

class Vendor extends Person
{	
	/*
	Determines if a given person_id is a customer
	*/
	public function exists($person_id)
	{
		$builder = $this->db->table('vendors')	
		                    ->join('people', 'people.person_id = vendors.person_id')
		                    ->where('vendors.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('vendors')
		                    ->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Returns all the vendors
	*/
	public function get_all($limit_from = 0, $rows = 0)
	{
	$builder = $this->db->table('vendors')
		         ->join('people', 'vendors.person_id = people.person_id')		
		         ->where('deleted', 0)
		         ->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}

	/*
	Returns stores the vendors
	*/
	public function get_stores_all($employee_id, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('vendors')		
		                    ->select('vendors.company_name, vendors.person_id')
		                    ->select('vendors_store.store_id')
		                    ->join('vendors_store', 'vendors.person_id = vendors_store.vendor_id', 'left')
		                    ->where('vendors.deleted', 0)
		                    ->where('vendors_store.store_id', $employee_id)
		                    ->orderBy('vendors.company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular vendor
	*/
	public function get_info($vendor_id)
	{
		$builder = $this->db->table('vendors')
		                    ->join('people', 'people.person_id = vendors.person_id')
		                    ->where('vendors.person_id', $vendor_id);
		$query = $builder->get();
		
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $vendor_id is NOT an vendor
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from vendor table		
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->getFieldNames('vendors') as $field)
			{
				$person_obj->$field = '';
			}
			
			return $person_obj;
		}
	}
	
	/*
	Gets information about multiple vendors
	*/
	public function get_multiple_info($vendors_ids)
	{
		$this->db->from('vendors');
		$this->db->join('people', 'people.person_id = vendors.person_id');		
		$this->db->where_in('vendors.person_id', $vendors_ids);
		$this->db->order_by('last_name', 'asc');

		return $this->db->get();
	}
	
	/*
	Inserts or updates a vendors
	*/
	public function save_vendor(&$person_data, &$vendor_data, &$vendor_items_data, &$vendor_stores_data, $vendor_id = FALSE)
	{
		$success = FALSE;
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		if(parent::savePerson($person_data,$vendor_id))
		{
			if(!$vendor_id || !$this->exists($vendor_id))
			{
				$vendor_id = $vendor_data['person_id'] = $person_data['person_id'];
				$success = $this->db->table('vendors')->insert($vendor_data);
			}
			else
			{
				$builder = $this->db->table('vendors')->where('person_id', $vendor_id);
				$success = $builder->update($vendor_data);
			}

			if ($success) {
				$success = $this->delete_stores($vendor_id);
            } else {
                $success = false;
            }
            
            //Now insert the new stores
            if ($success) {
                foreach ($vendor_stores_data as $stores_data) {
                    //save new permissions to local
                    if ($success) {
                        $success = $this->db->table('vendors_store')->insert(array(
                            'store_id' => $stores_data,
                            'vendor_id' => $vendor_id,
                        ));
                    } else {
                        $success = false;
                    }
                }
            }
            if ($success) {
				$success = $this->delete_vendors($vendor_id);
            } else {
                $success = false;
            }
            //Now insert the new items
            if ($success) {
                foreach ($vendor_items_data as $items_data) {
                    //save new permissions to local
                    if ($success) {
                        $success = $this->db->table('raw_items')->insert(array(
                            'vendor_id' => $vendor_id,
                            'name' => $items_data,
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
	Deletes stores
	*/
	public function delete_stores($vendor_id)
	{
		return $this->db->table('vendors_store')->delete(array('vendor_id' => $vendor_id)); 
	}

	/*
	Deletes stores
	*/
	public function delete_vendors($vendor_id)
	{
		return $this->db->table('raw_items')->delete( array('vendor_id' => $vendor_id)); 
	}

	/*
	Deletes one vendor
	*/
	public function delete_vendor($vendor_id)
	{
		$builder = $this->db->table('vendors')->where('person_id', $vendor_id);

		return $builder->update( array('deleted' => 1));
	}
	
	/*
	Deletes a list of vendors
	*/
	public function delete_list($vendor_ids)
	{
		$builder = $this->db->table('vendors')->whereIn('person_id', $vendor_ids);

		return $builder->update( array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find vendors
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('vendors');
		$this->db->join('people', 'vendors.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->like('company_name', $search);
		$this->db->order_by('company_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$this->db->from('vendors');
		$this->db->join('people', 'vendors.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->distinct();
		$this->db->like('agency_name', $search);
		$this->db->where('agency_name IS NOT NULL');
		$this->db->order_by('agency_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$this->db->from('vendors');
		$this->db->join('people', 'vendors.person_id = people.person_id');
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
			$this->db->from('vendors');
			$this->db->join('people', 'vendors.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('email', $search);
			$this->db->order_by('email', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$this->db->from('vendors');
			$this->db->join('people', 'vendors.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('phone_number', $search);
			$this->db->order_by('phone_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$this->db->from('vendors');
			$this->db->join('people', 'vendors.person_id = people.person_id');
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
		$builder = $this->db->table('vendors')
		                   ->join('people', 'vendors.person_id = people.person_id')
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
	Perform a search on vendors
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc', $person_id)
	{
		$builder = $this->db->table('vendors')
		                ->join('people', 'vendors.person_id = people.person_id')
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
		                ->where('warehouse_id', $person_id)
		                ->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	public function get_all_items($vendor_id = -1)
    {
        $builder = $this->db->table('raw_items')
                            ->where('vendor_id', $vendor_id);

        return $builder->get();
    }

    public function get_vendor_items()
    {
       $builder = $this->db->table('raw_items')
    	                   ->select('name')
                           ->orderBy('name', 'asc')
                           ->groupBy('name');
        $data = $builder->get();
        $dArray = [];
        foreach ($data->getResultArray() as $value) {
        	$dArray[] = $value['name'];
        }
        return $dArray;
    }

    public function get_all_stores($vendor_id = -1)
    {
        $builder = $this->db->table('vendors_store')
                            ->where('vendor_id', $vendor_id);

        return $builder->get();
    }

    /*
	Returns all the vendors of specific store
	*/
	public function get_all_of_store($store_id = -1, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('vendors')
	             ->join('vendors_store', 'vendors_store.vendor_id = vendors.person_id')			
	             ->where('deleted', 0);
	
		if($store_id>0){
			$builder->where('vendors_store.store_id', $store_id);
		}
	
		$builder->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}

	public function get_all_vendors_except($except = -1, $limit_from = 0, $rows = 0)
	{
		$this->db->from('vendors');
		// $this->db->join('people', 'vendors.person_id = people.person_id');	
		$this->db->where('vendors.person_id !=', $except);		
		$this->db->where('deleted', 0);
		$this->db->order_by('company_name', 'asc');
		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get()->result_array();		
	}
}
?>
