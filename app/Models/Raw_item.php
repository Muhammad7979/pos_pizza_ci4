<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;
use App\Models\Item_quantity;

class Raw_item extends Model
{
    /*
	Determines if a given item_id is an item
	*/
	public function exists($item_id, $ignore_deleted = FALSE, $deleted = FALSE)
	{
		$builder = $this->db->table('raw_items')
		                    ->where('CAST(item_id AS CHAR) = ', $item_id);
		if($ignore_deleted == FALSE)
		{
			$builder->where('deleted', $deleted);
		}

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Determines if a given item_number exists
	*/
	public function item_number_exists($item_number, $item_id = '')
	{
		$builder = $this->db->table('raw_items')
		                    ->where('item_number', $item_number);
		if(!empty($item_id))
		{
			$builder->where('item_id !=', $item_id);
		}

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('raw_items')
		                    ->where('deleted', 0);

		return $builder->countAllResults();
	}

	/*
	Get number of rows
	*/
	public function get_found_rows($search, $filters, $employee_id=-1, $vendor = 0)
	{
		return $this->search($search, $filters, $employee_id, $vendor)->getNumRows();
	}

	/*
	Perform a search on raw items
	*/
	public function search($search, $filters, $employee_id = -1, $vendor = 0, $rows = 0, $limit_from = 0, $sort = 'raw_items.name', $order = 'asc')
	{

		$builder = $this->db->table('raw_items')
		//$this->db->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left');
		                  ->join('raw_inventory', 'raw_inventory.trans_items = raw_items.item_id');

		if($vendor==1){
			$builder->join('vendors', 'vendors.person_id = raw_items.vendor_id', 'left')  
			        ->where('vendor_id !=', 0);
		}else{
			$builder->where('vendor_id', 0);
		}
		
		if($filters['stock_location_id'] > -1)
		{
			$builder->join('raw_item_quantities', 'raw_item_quantities.item_id = raw_items.item_id')
			         ->where('location_id', $filters['stock_location_id']);
		}

		$builder->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

		if(!empty($search))
		{
			if($filters['search_custom'] == FALSE)
			{
				$builder->groupStart()
					       ->like('name', $search)
					       ->orLike('item_number', $search)
					       ->orLike('raw_items.item_id', $search)
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
		        ->where('raw_items.person_id', $employee_id);

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
		$builder->groupBy('raw_items.item_id');
		
		// order by name of item
		$builder->orderBy($sort, $order);

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
		$builder = $this->db->table('raw_items')
		                    ->select('raw_items.*')
		                    ->select('vendors.company_name')
		                    ->join('vendors', 'vendors.person_id = raw_items.vendor_id', 'left')
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
	Get an item id given an item number
	*/
	public function get_item_id($item_number)
	{
		$this->db->from('raw_items');
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
	public function get_multiple_info($item_ids, $location_id=-1)
	{
		$builder = $this->db->table('raw_items')
		                    ->join('warehouses', 'warehouses.person_id = raw_items.person_id', 'left')
		                    ->join('raw_item_quantities', 'raw_item_quantities.item_id = raw_items.item_id', 'left');
		if($location_id!=-1){
			$builder->where('location_id', $location_id);
		}
		
		$builder->whereIn('raw_items.item_id', $item_ids);

		return $builder->get();
	}

	/*
	Inserts or updates a item
	*/
	public function save_raw_item(&$item_data, $item_id = FALSE)
	{
	    if(!$item_id || !$this->exists($item_id, TRUE))
		{
			if($this->db->table('raw_items')->insert($item_data))
			{
				$item_data['item_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}
		
		$builder = $this->db->table('raw_items')->where('item_id', $item_id);

		return $builder->update($item_data);
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
	public function delete_raw_item($item_id)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

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
		$this->db->transStart();

		// set to 0 quantities
		$Item_quantity =  new Item_quantity();
		$Item_quantity->reset_quantity_list($item_ids);
		$builder = $this->db->table('raw_items')->whereIn('item_id', $item_ids);
		$success = $builder->update(array('deleted'=>1));
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
 	}

	public function get_warehouse_search_suggestions($person_id, $search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('raw_items')
		                    ->select('raw_items.item_id, raw_items.name, raw_items.item_number, raw_item_quantities.quantity')
		                    ->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id')
		                    ->where('raw_items.deleted', $filters['is_deleted'])
		                    ->where('raw_items.person_id', $person_id)
		                    ->where('raw_items.item_type', 2)
		                    ->like('raw_items.name', $search)
		                    ->orderBy('raw_items.name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			// $suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->quantity),
   //              'label' => $row->name ." [" .$row->quantity ."]");
			$suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->quantity),
                'label' => $row->name);
		}

// 		$this->db->select('raw_items.item_id, raw_items.name, raw_items.item_number, raw_item_quantities.quantity');
// 		$this->db->from('raw_items');
// 		$this->db->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id');
// 		$this->db->where('raw_items.deleted', $filters['is_deleted']);
// 		$this->db->where('raw_items.person_id', $person_id);
// 		$this->db->where('raw_items.item_type', 2);
// 		$this->db->like('raw_items.item_number', $search);
// 		$this->db->order_by('raw_items.item_number', 'asc');
// 		foreach($this->db->get()->result() as $row)
// 		{
// 			$suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->quantity),
//                 'label' => $row->name);
// 			// $suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->quantity),
//    //              'label' => $row->name ." [" .$row->quantity ."]");
// 		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

       if($suggestions){

		return $suggestions;

        }else{

			 $suggestions[] = array('label' => 'No item');
			return $suggestions;

		}
	}


	public function get_vendor_search_suggestions($person_id, $search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('raw_items')
		                    ->select('raw_items.item_id, raw_items.name, raw_items.item_number')
		                    ->where('raw_items.deleted', $filters['is_deleted'])
		                    ->where('raw_items.vendor_id', $person_id)
		                    ->where('raw_items.item_type', 1)
		                    ->like('raw_items.name', $search)
		                    ->orderBy('raw_items.name', 'asc');	
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->item_id, 'max' => 99999999,
                'label' => $row->name);
		}

		// $this->db->select('raw_items.item_id, raw_items.name, raw_items.item_number');
		// $this->db->from('raw_items');
		// //$this->db->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id');
		// $this->db->where('raw_items.deleted', $filters['is_deleted']);
		// $this->db->where('raw_items.vendor_id', $person_id);
		// $this->db->where('raw_items.item_type', 1);
		// $this->db->like('raw_items.item_number', $search);
		// $this->db->order_by('raw_items.item_number', 'asc');
		// foreach($this->db->get()->result() as $row)
		// {
		// 	$suggestions[] = array('value' => $row->item_id, 'max' => 99999999,
        //         'label' => $row->name);
		// }

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

		if($suggestions){

			return $suggestions;
	
			}else{
	
				 $suggestions[] = array('label' => 'No item');
				return $suggestions;
	
			}
	}

	public function get_store_search_suggestions($person_id, $search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('raw_order_item_quantities as roiq')
		                    ->select('roiq.item_id, roiq.available_quantity, raw_items.name')
		                    ->join('raw_items','raw_items.item_id=roiq.item_id')
		                    ->where('roiq.store_id', $person_id)
		                    ->like('raw_items.name', $search)
		                    ->orderBy('raw_items.name', 'asc')
		                    ->groupBy('roiq.item_id');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->available_quantity),
                'label' => $row->name ." [" .$row->available_quantity ."]");
		}


		// $this->db->select('roiq.item_id, roiq.available_quantity, raw_items.name');
		// $this->db->from('raw_order_item_quantities as roiq');
		// $this->db->join('raw_items','raw_items.item_id=roiq.item_id');
		// $this->db->where('roiq.store_id', $person_id);
		// //$this->db->where('roiq.category', 2);
		// $this->db->like('raw_items.item_number', $search);
		// $this->db->order_by('raw_items.item_number', 'asc');
		// $this->db->group_by('roiq.item_id');
		// foreach($this->db->get()->result() as $row)
		// {
		// 	$suggestions[] = array('value' => $row->item_id, 'max' => parse_decimals($row->available_quantity),
        //         'label' => $row->name ." [" .$row->available_quantity ."]");
		// }

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

		if($suggestions){

			return $suggestions;
	
			}else{
	
				 $suggestions[] = array('label' => 'No item');
				return $suggestions;
	
			}
	}

	public function get_category_suggestions($search)
	{
		$suggestions = array();
		$builder = $this->db->table('raw_items')
	                    	->select('category')
		                    ->distinct()
		                    ->like('category', $search)
		                    ->where('deleted', 0)
		                    ->orderBy('category', 'asc');
		foreach($builder->get()->getResult() as $row)
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