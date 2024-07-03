<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class Raw_order extends Model
{
	/*
	Determines if a given item_id is an item kit
	*/
	public function exists($order_id)
	{
		$builder = $this->db->table('raw_orders')
		                    ->where('order_id', $order_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('raw_orders');

		return $this->db->count_all_results();
	}
	
	/*
	Gets information about a particular item kit
	*/
	public function get_info($order_id)
	{
		$builder = $this->db->table('raw_orders')
		                ->where('order_id', $order_id);
		
		$query = $builder->get();

		if($query->getNumRows()==1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $order_id is NOT an item kit
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->getFieldNames('raw_orders') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Gets information about multiple item kits
	*/
	public function get_multiple_info($order_ids)
	{
		$this->db->from('raw_orders');
		$this->db->where_in('order_id', $order_ids);
		$this->db->order_by('name', 'asc');

		return $this->db->get();
	}

	/*
	Inserts or updates an item kit
	*/
	public function save_raw_order(&$raw_order_data, $order_id = FALSE)
	{
		if(!$order_id || !$this->exists($order_id))
		{
			if($this->db->table('raw_orders')->insert($raw_order_data))
			{
				$raw_order_data['order_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}

		$builder = $this->db->table('raw_orders')->where('order_id', $order_id);

		return $builder->update($raw_order_data);
	}

	/*
	Deletes one item kit
	*/
	public function delete_raw_order($order_id)
	{
		return $this->db->delete('raw_orders', array('order_id' => $order_id)); 	
	}

	/*
	Deletes a list of item kits
	*/
	public function delete_list($order_ids)
	{
		$builder = $this->db->table('raw_orders')->whereIn('order_id', $order_ids);

		return $builder->delete();		
 	}

	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('raw_orders');

		//KIT #
		if(stripos($search, 'KIT ') !== FALSE)
		{
			$this->db->like('order_id', str_ireplace('KIT ', '', $search));
			$this->db->order_by('order_id', 'asc');

			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => 'KIT '. $row->order_id, 'label' => 'KIT ' . $row->order_id);
			}
		}
		else
		{
			//$this->db->like('name', $search);
			$this->db->order_by('name', 'asc');

			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => 'KIT ' . $row->order_id, 'label' => $row->name);
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
	Perform a search on items
	*/
	public function search($search, $person_id = -1, $store_id = -1, $order_id = -1, $order_time_filter = 'all', $rows=0, $limit_from=0, $sort='order_id', $order='desc', $filters)
	{
		$builder = $this->db->table('raw_orders');
		//$this->db->like('name', $search);
		if(!empty($search))
		{
			$builder->orLike('description', $search);
		}

		$builder->where('DATE_FORMAT(created_at, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));


		if($order_time_filter!='all')
		{
			$builder->where(' 	order_time', $order_time_filter);
		}


		if($person_id > 0)
		{
			$builder->where('person_id', $person_id);
		}
		
		if($store_id > 0)
		{
			$builder->where('store_id', $store_id);
		}

		if($order_id > 0)
		{
			$builder->where('order_id', $order_id);
		}

		if($filters['store_ids'])
		{
			$builder->whereIn('store_id', $filters['store_ids']);
		}

		$builder->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();	
	}
	
	public function get_found_rows($person_id = -1, $store_id = -1, $order_id = -1, $order_time_filter = 'all', $search, $filters)
	{
		$builder = $this->db->table('raw_orders');
		//$this->db->like('name', $search);
		
		if(!empty($search))
		{
			$builder->orLike('description', $search);
		}

		$builder->where('DATE_FORMAT(created_at, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

		if($person_id > 0)
		{
			$builder->where('person_id', $person_id);
		}
		
		if($store_id > 0)
		{
			$builder->where('store_id', $store_id);
		}
		if($order_id > 0)
		{
			$builder->where('order_id', $order_id);
		}
		if($filters['store_ids'])
		{
			$builder->whereIn('store_id', $filters['store_ids']);
		}

		return $builder->get()->getNumRows();
	}

	public function get_items_found_rows($search, $order_id, $category)
	{
		return $this->items_search($search, $order_id, $category)->getNumRows();
	}

	/*
	Perform a search on raw items
	*/
	public function items_search($search, $order_id, $category, $rows = 0, $limit_from = 0, $sort = 'name', $order = 'asc')
	{


		$builder = $this->db->table('raw_order_items')
		                    ->join('raw_items', 'raw_items.item_id = raw_order_items.item_id');
		if(!empty($search))
		{
			$builder->groupStart() 
				    ->like('name', $search);
				
				if ($category==1) {
					$builder->orLike('vendors_items.item_id', $search);
				}else{
					$builder->orLike('item_number', $search)
					        ->orLike('raw_items.item_id', $search)
					        ->orLike('category', $search);
				}
				
			$builder->groupEnd();
		}

		$builder->where('raw_order_items.order_id', $order_id)

		// avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
		 ->groupBy('raw_items.item_id')
		// order by name of item
		 ->orderBy($sort, $order);

		if($rows > 0) 
		{	
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
}
?>