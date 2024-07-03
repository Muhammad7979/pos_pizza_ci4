<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class Store_item extends Model
{

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
		$builder = $this->db->table('vendors_items')
		                    ->select('vendors_items.*')
		                    ->select('vendors.company_name')
		                    ->join('vendors', 'vendors.person_id = vendors_items.person_id', 'left')
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
			foreach($this->db->getFieldNames('vendors_items') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Get number of rows
	*/
	public function get_found_rows($search, $employee_id=-1)
	{
		return $this->search($search, $employee_id)->getNumRows();
	}

	/*
	Perform a search on store items
	*/
	public function search($search, $employee_id = -1, $rows = 0, $limit_from = 0, $sort = 'name', $order = 'asc')
	{

		$builder = $this->db->table('raw_order_item_quantities as roiq')
		                   ->select('roiq.available_quantity as quantity, raw_items.item_number, raw_items.item_id, raw_items.name, raw_items.category, raw_items.cost_price')
		                   ->join('raw_items', 'raw_items.item_id = roiq.item_id');

		if(!empty($search))
		{
			$builder->groupStart()
				 ->like('raw_items.name', $search)
				 ->orLike('item_number', $search)
				 ->orLike('roiq.item_id', $search)
				 ->orLike('raw_items.category', $search)
			 ->groupEnd();
		}

		$builder->where('roiq.store_id', $employee_id);

		// avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
		$builder->groupBy('roiq.item_id');
		
		// order by name of item
		$builder->orderBy($sort, $order);

		if($rows > 0) 
		{	
			$builder->limit($rows, $limit_from);
		}

		return $data = $builder->get();

		//return $data->result();

	}
	



}
?>