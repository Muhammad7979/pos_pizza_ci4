<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;
class Raw_item_processing extends Model
{
    /*
	Determines if a given item_id is an item
	*/
	public function exists($item_id, $ignore_deleted = FALSE, $deleted = FALSE)
	{
		$this->db->from('raw_items');
		$this->db->where('CAST(item_id AS CHAR) = ', $item_id);
		if($ignore_deleted == FALSE)
		{
			$this->db->where('deleted', $deleted);
		}

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	Determines if a given item_number exists
	*/
	public function item_number_exists($item_number, $item_id = '')
	{
		$this->db->from('raw_items');
		$this->db->where('item_number', $item_number);
		if(!empty($item_id))
		{
			$this->db->where('item_id !=', $item_id);
		}

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('raw_items');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}

	/*
	Get number of rows
	*/
	public function get_found_rows($search, $filters, $employee_id=-1)
	{
		return $this->search($search, $filters, $employee_id)->getNumRows();
	}

	/*
	Perform a search on raw items
	*/
	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'raw_items.name', $order = 'asc', $employee_id = -1)
	{
		$builder = $this->db->table('raw_items')
		                    ->select('raw_items.*, raw_inventory.*, roiq.available_quantity as quantity')
		                    ->join('raw_inventory', 'raw_inventory.trans_items = raw_items.item_id')
		                    ->join('raw_order_item_quantities as roiq', 'roiq.item_id = raw_items.item_id')
		                    ->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

		if(!empty($search))
		{
			if($filters['search_custom'] == FALSE)
			{
				$builder->groupStart()
					    ->like('name', $search)
					    ->orLike('item_number', $search)
					    ->orLike('raw_items.item_id', $search)
					    ->orLike('company_name', $search)
					    ->orLike('category', $search)
				    ->groupEnd();
			}
			else
			{
				$builder->groupStart()
					    ->orLike('custom2', $search)
				        ->groupEnd();
			}
		}

		$builder->where('raw_items.deleted', $filters['is_deleted'])
		        ->where('raw_inventory.trans_user', $employee_id)
		        ->where('raw_items.person_id', $employee_id)
		        ->where('roiq.store_id', $employee_id);
		

		if($filters['empty_upc'] != FALSE)
		{
			$builder->where('item_number', NULL);
		}
		if($filters['low_inventory'] != FALSE)
		{
			$builder->where('quantity <=', 'reorder_level');
		}
		if($filters['no_description'] != FALSE)
		{
			$builder->where('raw_items.description', '');
		}

		// avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
		$builder->groupBy('raw_items.item_id')
		
		// order by name of item
		        ->orderBy($sort, $order);

		if($rows > 0) 
		{	
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
	
	/*
	Returns all the raw items
	*/
	public function get_all($stock_location_id = -1, $rows = 0, $limit_from = 0)
	{
		$this->db->from('raw_items');
		$this->db->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left');

		if($stock_location_id > -1)
		{
			$this->db->join('raw_item_quantities', 'raw_item_quantities.item_id=raw_items.item_id');
			$this->db->where('location_id', $stock_location_id);
		}

		$this->db->where('raw_items.deleted', 0);

		// order by name of item
		$this->db->order_by('raw_items.name', 'asc');

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	/*
	Gets information about a particular item
	*/
	public function get_info($item_id)
	{
		$builder = $this->db->table('raw_items')
		                    ->select('raw_items.*')
		                    ->select('warehouses.company_name')
		                    ->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left')
		                    ->where('item_id', $item_id);

		$query = $builder->get();

		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->getFieldNames('raw_items') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Gets information about a particular item
	*/
	public function get_info_vendor($item_id)
	{
		$this->db->select('vendors_items.*');
		$this->db->select('vendors.company_name');
		$this->db->from('vendors_items');
		$this->db->join('vendors', 'vendors.person_id = vendors_items.person_id', 'left');
		$this->db->where('item_id', $item_id);

		$query = $this->db->get();

		if($query->num_rows() == 1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->list_fields('vendors_items') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Get an item id given an item number
	*/
	public function get_item_id($item_number)
	{
		$this->db->from('raw_items');
		$this->db->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left');
		$this->db->where('item_number', $item_number);
		$this->db->where('raw_items.deleted', 0);
        
		$query = $this->db->get();

		if($query->num_rows() == 1)
		{
			return $query->row()->item_id;
		}

		return FALSE;
	}

	/*
	Gets information about multiple items
	*/
	public function get_multiple_info($item_ids, $location_id)
	{
		$this->db->select('raw_items.*, raw_inventory.*, roiq.available_quantity as quantity');
		$this->db->from('raw_items');
		//$this->db->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left');
		$this->db->join('raw_inventory', 'raw_inventory.trans_items = raw_items.item_id');
		$this->db->join('raw_order_item_quantities as roiq', 'roiq.item_id = raw_items.item_id');

		// $this->db->from('raw_items');
		// $this->db->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left');
		// $this->db->join('raw_item_quantities', 'raw_item_quantities.item_id = raw_items.item_id', 'left');
		//$this->db->where('location_id', $location_id);
		$this->db->where_in('raw_items.item_id', $item_ids);

		return $this->db->get();
	}

	/*
	Inserts or updates a item
	*/
	public function save_raw_item_processing(&$item_data, $item_id = FALSE)
	{
	    if(!$item_id || !$this->exists($item_id, TRUE))
		{
			if($this->db->insert('raw_items', $item_data))
			{
				$item_data['item_id'] = $this->db->insert_id();

				return TRUE;
			}

			return FALSE;
		}
		
		$this->db->where('item_id', $item_id);

		return $this->db->update('raw_items', $item_data);
	}

	/*
	Inserts or updates an item kit's items
	*/
	public function save_kit_items(&$item_kit_items_data, $item_kit_id)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		$this->delete_kit($item_kit_id);
		
		foreach($item_kit_items_data as $row)
		{
			$row['item_kit_id'] = $item_kit_id;
			$item_id = $row['item_id'];
			if ($this->item_kit_exists($item_kit_id, $item_id)) {
				$this->db->where('item_kit_id', $item_kit_id);
				$this->db->where('item_id', $item_id);
				$success &= $this->db->update('raw_item_kit_items', array('deleted'=>0));
			}else{
				$success &= $this->db->insert('raw_item_kit_items', $row);
			}		
		}
		
		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	/*
	Inserts or updates an item kit's items
	*/
	public function update_kit_items(&$item_kit_items_data, $item_kit_id)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();
		
		foreach($item_kit_items_data as $row)
		{
			$row['item_kit_id'] = $item_kit_id;
			$item_id = $row['item_id'];
			$quantity = $row['quantity'];

			$this->db->where('item_kit_id', $item_kit_id);
			$this->db->where('item_id', $item_id);
			$success &= $this->db->update('raw_item_kit_items', array('quantity'=>$quantity));		
		}
		
		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	/*
	Determines if a given item_number exists
	*/
	public function item_kit_exists($item_kit_id, $item_id)
	{
		$this->db->from('raw_item_kit_items');
		$this->db->where('item_kit_id', $item_kit_id);
		$this->db->where('item_id', $item_id);

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	deletes all item kit item
	*/
	public function delete_kit($item_kit_id)
	{
		$this->db->where('item_kit_id', $item_kit_id);

		return $this->db->update('raw_item_kit_items', array('deleted'=>1));
	}

	/*
	Gets order items for a particular order
	*/
	public function get_kit_items($item_kit_id)
	{
		$builder = $this->db->table('raw_item_kit_items as rikt')
		                    ->join('raw_items','raw_items.item_id=rikt.item_id')
		                    ->join('raw_order_item_quantities as roiq','roiq.item_id=rikt.item_id')
		                    ->where('rikt.item_kit_id', $item_kit_id)
		                    ->where('roiq.store_id', 237)
		                    ->where('rikt.deleted', 0);
		//return an array of order items for an item
		return $builder->get()->getResultArray();
	}

	/*
	Updates multiple raw items at once
	*/
	public function update_multiple($item_data, $item_ids)
	{
	    // if(!$this->gu->isServer()){
	    //     $this->db->where('custom10','no');
     //    }
		$this->db->where_in('item_id', explode(':', $item_ids));

		return $this->db->update('raw_items', $item_data);
	}

	/*
	Deletes one item
	*/
	public function delete_raw_item_processing($item_id)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		// set to 0 quantities
		$this->Item_quantity->reset_quantity($item_id);
		$this->db->where('item_id', $item_id);
		$success = $this->db->update('raw_items', array('deleted'=>1));
		
		$this->db->trans_complete();
		
		$success &= $this->db->trans_status();

		return $success;
	}
	
	/*
	Undeletes one item
	*/
	public function undelete($item_id)
	{
		$this->db->where('item_id', $item_id);

		return $this->db->update('raw_items', array('deleted'=>0));
	}

	/*
	Deletes a list of raw items
	*/
	public function delete_list($item_ids)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		// set to 0 quantities
		$this->Item_quantity->reset_quantity_list($item_ids);
		$this->db->where_in('item_id', $item_ids);
		$success = $this->db->update('raw_items', array('deleted'=>1));
		
		$this->db->trans_complete();
		
		$success &= $this->db->trans_status();

		return $success;
 	}

	public function get_items_search_suggestions($search, $employee_id, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		
		$suggestions = array();

		$builder = $this->db->table('raw_order_item_quantities as roiq')
		                   ->select('raw_items.item_id, raw_items.name, raw_items.item_number, raw_items.category, roiq.available_quantity as quantity')
		                   ->join('raw_items','raw_items.item_id=roiq.item_id')
		                   ->where('roiq.store_id', $employee_id)
		                   ->whereIn('raw_items.item_type', [1,2])
		                   ->where('raw_items.deleted', $filters['is_deleted'])
		                   ->like('raw_items.name', $search)
		                   ->orderBy('raw_items.name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->item_id, 'max' => $row->quantity,
                'label' => $row->name, 'category' => $row->category, 'type' => 1);
		}

		$builder = $this->db->table('raw_order_item_quantities as roiq')
		                   ->select('raw_items.item_id, raw_items.name, raw_items.item_number, raw_items.category, roiq.available_quantity as quantity')
		                   ->join('raw_items','raw_items.item_id=roiq.item_id')
		                   ->where('roiq.store_id', $employee_id)
		                   ->whereIn('raw_items.item_type', [1,2])
		                   ->where('raw_items.deleted', $filters['is_deleted'])
		                   ->like('item_number', $search)
		                   ->orderBy('item_number', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->item_id, 'max' => $row->quantity,
                'label' => $row->item_number." ".$row->name, 'category' => $row->category, 'type' => 1);
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

		return $suggestions;
	}

	public function get_category_suggestions($search)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select('category');
		$this->db->from('raw_items');
		$this->db->like('category', $search);
		$this->db->where('deleted', 0);
		$this->db->order_by('category', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('label' => $row->category);
		}

		return $suggestions;
	}
	
	public function get_location_suggestions($search)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select('location');
		$this->db->from('raw_items');
		$this->db->like('location', $search);
		$this->db->where('deleted', 0);
		$this->db->order_by('location', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('label' => $row->location);
		}
	
		return $suggestions;
	}

	public function get_custom_suggestions($search, $field_no)
	{
		$suggestions = array();
		$this->db->distinct();
		$this->db->select('custom'.$field_no);
		$this->db->from('raw_items');
		$this->db->like('custom'.$field_no, $search);
		$this->db->where('deleted', 0);
		$this->db->order_by('custom'.$field_no, 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$row_array = (array) $row;
			$suggestions[] = array('label' => $row_array['custom'.$field_no]);
		}
	
		return $suggestions;
	}

	public function get_categories()
	{
		$this->db->select('category');
		$this->db->from('raw_items');
		$this->db->where('deleted', 0);
		$this->db->distinct();
		$this->db->order_by('category', 'asc');

		return $this->db->get();
	}

	/*
	 * changes the cost price of a given item
	 * calculates the average price between received items and items on stock
	 * $item_id : the item which price should be changed
	 * $items_received : the amount of new items received
	 * $new_price : the cost-price for the newly received items
	 * $old_price (optional) : the current-cost-price
	 *
	 * used in receiving-process to update cost-price if changed
	 * caution: must be used there before item_quantities gets updated, otherwise average price is wrong!
	 *
	 */
	public function change_cost_price($item_id, $items_received, $new_price, $old_price = null)
	{
		if($old_price === null)
		{
			$item_info = $this->get_info($item_id);
			$old_price = $item_info->cost_price;
		}

		$this->db->from('raw_item_quantities');
		$this->db->select_sum('quantity');
        $this->db->where('item_id', $item_id);
		$this->db->join('stock_locations', 'stock_locations.location_id=raw_item_quantities.location_id');
        $this->db->where('stock_locations.deleted', 0);
		$old_total_quantity = $this->db->get()->row()->quantity;

		$total_quantity = $old_total_quantity + $items_received;
		$average_price = bcdiv(bcadd(bcmul($items_received, $new_price), bcmul($old_total_quantity, $old_price)), $total_quantity);

		$data = array('cost_price' => $average_price);

		return $this->save($data, $item_id);
	}
	
	//We create a temp table that allows us to do easy report queries
	public function create_temp_table()
	{
		$this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->dbprefix('items_temp') . 
			'(
				SELECT
					raw_items.name,
					raw_items.item_number,
					raw_items.description,
					raw_items.reorder_level,
					raw_item_quantities.quantity,
					stock_locations.location_name,
					stock_locations.location_id,
					raw_items.cost_price,
					raw_items.unit_price,
					(raw_items.cost_price * raw_item_quantities.quantity) AS sub_total_value
				FROM ' . $this->db->dbprefix('raw_items') . ' AS items
				INNER JOIN ' . $this->db->dbprefix('raw_item_quantities') . ' AS raw_item_quantities
					ON raw_items.item_id = raw_item_quantities.item_id
				INNER JOIN ' . $this->db->dbprefix('stock_locations') . ' AS stock_locations
					ON raw_item_quantities.location_id = stock_locations.location_id
				WHERE raw_items.deleted = 0
			)'
		);
	}
}
?>