<?php
namespace App\Models;
use CodeIgniter\Model;
class Counter_order_item extends Model
{
	/*
	Gets order items for a particular order
	*/
	public function get_info($order_id)
	{
		$builder = $this->db->table('counter_order_items')
		                    ->where('order_id', $order_id);
		
		//return an array of order items for an item
		return $builder->get()->getResultArray();
	}

	/*
	Gets total rows for a particular order
	*/
	public function get_total_rows($order_id)
	{
		$builder = $this->db->table('counter_order_items')
		                    ->where('order_id', $order_id);
		
		//return an array of order items for an item
		return $builder->get()->getNumRows();
	}
	
	/*
	Gets total received rows for a particular order
	*/
	public function get_total_delivered_rows($order_id)
	{
		$builder= $this->db->table('counter_order_items')
		       ->join('counter_orders','counter_orders.order_id = counter_order_items.order_id')
		       ->where('counter_orders.order_id', $order_id)
		       ->where('counter_orders.is_delivered', '1');
		
		//return an array of order items for an item
		return $builder->get()->getNumRows();
	}

	/*
	Gets total received rows for a particular order
	*/
	public function get_total_received_rows($order_id)
	{
		$builder  = $this->db->table('counter_order_items')
		                     ->join('counter_orders','counter_orders.order_id = counter_order_items.order_id')
		                     ->where('counter_orders.order_id', $order_id)
		                     ->where('counter_orders.is_received', '1');
		
		//return an array of order items for an item
		return $builder->get()->getNumRows();
	}

	/*
	Gets company name rows for a particular order
	*/
	public function get_company_name($person_id,$table)
	{
		$builder = $this->db->table($table)
		                    ->where('person_id', $person_id);
		
		//return an array of order items for an item
		return $builder->get()->getResultArray();
	}
	
	/*
	Inserts or updates an order's items
	*/
	public function save_counter_order_item(&$counter_order_items_data, $order_id)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		$this->delete($order_id);
		
		foreach($counter_order_items_data as $row)
		{
			$row['order_id'] = $order_id;
			$success &= $this->db->insert('counter_order_items', $row);		
		}
		
		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	/*
	updates an order's items delivered quantity
	*/
	public function update_order_item(&$counter_order_items_data, $order_id, $column)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		//$this->delete($order_id);
		
		foreach($counter_order_items_data as $row)
		{
			$row['order_id'] = $order_id;
			//$success = $this->db->update('counter_order_items', $row);	
			$data = array(
		        $column => $row[$column],
			);
			$this->db->where('order_id', $order_id);
			$this->db->where('item_id', $row['item_id']);
			$this->db->update('counter_order_items', $data);
		}
		
		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}
	
	/*
	Deletes order items given an order
	*/
	public function delete_order_item($order_id)
	{
		return $this->db->delete('counter_order_items', array('order_id' => $order_id)); 
	}
}
?>
