<?php
namespace App\Models;
use CodeIgniter\Model;
use stdClass;

class Counter_order extends Model
{
	/*
	Determines if a given item_id is an item kit
	*/
	public function exists($order_id)
	{
		$builder =$this->db->table('counter_orders')
		                   ->where('order_id', $order_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('counter_orders');

		return $builder->countAllResults();
	}
	
	/*
	Gets information about a particular item kit
	*/
	public function get_info($order_id)
	{
        $builder = $this->db->table('counter_orders')
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
			foreach($this->db->getFieldNames('counter_orders') as $field)
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
		$builder = $this->db->table('counter_orders')
		                     ->whereIn('order_id', $order_ids)
		                     ->orderBy('name', 'asc');

		return $builder->get();
	}

	/*
	Inserts or updates an item kit
	*/
	public function save_counter_order(&$counter_order_data, $order_id = FALSE)
	{
		if(!$order_id || !$this->exists($order_id))
		{
			if($this->db->insert('counter_orders', $counter_order_data))
			{
				$counter_order_data['order_id'] = $this->db->insert_id();

				return TRUE;
			}

			return FALSE;
		}

		$this->db->where('order_id', $order_id);

		return $this->db->update('counter_orders', $counter_order_data);
	}

	/*
	Deletes one item kit
	*/
	public function delete_counter_order($order_id)
	{
		return $this->db->delete('counter_orders', array('order_id' => $id)); 	
	}

	/*
	Deletes a list of item kits
	*/
	public function delete_list($order_ids)
	{
		$this->db->where_in('order_id', $order_ids);

		return $this->db->delete('counter_orders');		
 	}

	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('counter_orders');

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
	public function search($search, $rows=0, $limit_from=0, $sort='order_id', $order='desc', $store_id = -1, $person_id = -1, $order_id = -1)
	{
		$builder = $this->db->table('counter_orders')
		 ->join('counters','counters.person_id=counter_orders.person_id')
		 ->orLike('description', $search);
		
		if($store_id > 0)
		{
			$builder->where('counter_orders.store_id', $store_id);
		}

		if($person_id > 0)
		{
			$builder->where('counter_orders.person_id', $person_id);
		}

		$builder->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}
		if($order_id > 0)
		{
			$builder->where('counter_orders.order_id', $order_id);
		}

		return $builder->get();	
	}
	
	public function get_found_rows($store_id = -1, $order_id = -1, $search)
	{
		$builder = $this->db->table('counter_orders')
		                   ->orLike('description', $search);
		if($store_id > 0)
		{
			$builder->where('store_id', $store_id);
		}
		if($order_id > 0)
		{
			$builder->where('order_id', $order_id);
		}
		return $builder->get()->getNumRows();
	}
}
?>