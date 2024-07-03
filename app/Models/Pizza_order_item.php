<?php
namespace App\Models;

use CodeIgniter\Model;

class Pizza_order_item extends Model
{
	/*
	Gets order items for a particular order
	*/
	public function get_info($order_id)
	{
		$this->db->from('pizza_order_items');
		$this->db->where('order_id', $order_id);
		
		//return an array of order items for an item
		return $this->db->get()->result_array();
	}

	public function get_total_sum($order_id)
	{
		$this->db->select('SUM(sub_total) AS order_price, SUM(quantity) AS order_quantity');
		$this->db->from('pizza_order_items');
		$this->db->where('order_id', $order_id);

		return $this->db->get()->row();
	}

	public function get_item_info($id)
	{
		$this->db->from('pizza_order_items');
		$this->db->where('id', $id);
		
		$query = $this->db->get();

		if($query->num_rows()==1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $order_id is NOT an item kit
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->list_fields('pizza_order_items') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Gets total rows for a particular order
	*/
	public function get_total_rows($order_id)
	{
		$this->db->from('pizza_order_items');
		$this->db->where('order_id', $order_id);
		
		//return an array of order items for an item
		return $this->db->get()->num_rows();
	}
	
	/*
	Gets total received rows for a particular order
	*/
	public function get_total_delivered_rows($order_id)
	{
		$this->db->from('pizza_order_items');
		$this->db->join('pizza_orders','pizza_orders.order_id = pizza_order_items.order_id');
		$this->db->where('pizza_orders.order_id', $order_id);
		$this->db->where('pizza_orders.is_delivered', '1');
		
		//return an array of order items for an item
		return $this->db->get()->num_rows();
	}

	/*
	Gets total received rows for a particular order
	*/
	public function get_total_received_rows($order_id)
	{
		$this->db->from('pizza_order_items');
		$this->db->join('pizza_orders','pizza_orders.order_id = pizza_order_items.order_id');
		$this->db->where('pizza_orders.order_id', $order_id);
		$this->db->where('pizza_orders.is_received', '1');
		
		//return an array of order items for an item
		return $this->db->get()->num_rows();
	}

	/*
	Gets company name rows for a particular order
	*/
	public function get_company_name($person_id,$table)
	{
		$this->db->from($table);
		$this->db->where('person_id', $person_id);
		
		//return an array of order items for an item
		return $this->db->get()->result_array();
	}

	public function get_counter_id($store_id, $table, $permission)
	{
		$builder = $this->db->table($table)
		         ->where('store_id', $store_id)
		         ->where('special_counter', 1);
		$data = $builder->get()->getRow();

		return $data->person_id;
		// $id = 0;
		// foreach ($this->db->get()->result() as $key => $value) {

		// 	$query = $this->db->get_where('grants', array('person_id' => $value->person_id, 'permission_id' => $permission), 1)->row();
		// 		if(!empty($query)){
		// 			$id = $query->person_id;
		// 		}
		// }
		// return $id;
	}

	public function get_item_number($size, $category1='', $category2='')
	{
		$builder = $this->db->table('pizza_item_list')
		         ->where('size', $size);

		if($category1!='' && $category2==''){
			$builder->like('category1', $category1);
		}else{
		           $builder->like('category1', $category1)
		            ->like('category2',$category2)
		            ->orLike('category1', $category2)
		            ->like('category2',$category1);
		}
		//return an array of order items for an item
		return $builder->get()->getRow();
	}
	
	/*
	Inserts or updates an order's items
	*/
	public function save_pizza_order_item(&$pizza_order_items)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

		$success &= $this->db->table('pizza_order_items')->insertBatch( $pizza_order_items);	
		$affected_rows = $this->db->affectedRows();
		
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		if($affected_rows>0){
			$success = TRUE;
		}
		return $success;
	}

	public function saveSingle(&$pizza_order_item)
	{
		$success = FALSE;
		
		if($this->db->insert('pizza_order_items', $pizza_order_item))
		{
			return TRUE;
		}

		return $success;
	}

	/*
	updates an order's items delivered quantity
	*/
	public function update_pizza_order_item(&$pizza_order_items, $order_id, $column)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

		//$this->delete($order_id);
		
		foreach($pizza_order_items as $row)
		{
			$row['order_id'] = $order_id;
			//$success = $this->db->update('pizza_order_items', $row);	
			$data = array(
		        $column => $row[$column],
			);
			$builder = $this->db->table('pizza_order_items')
			         ->where('order_id', $order_id)
			         ->where('item_id', $row['item_id'])
			         ->update($data);
		}
		
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Deletes order items given an order
	*/
	public function delete_pizza_order_item($order_id)
	{
		return $this->db->table('pizza_order_items')->delete(array('order_id' => $order_id)); 
	}

	public function deleteItem($id)
	{
		return $this->db->delete('pizza_order_items', array('id' => $id)); 
	}

	public function updateItem($id,$quantity, $price)
	{
		$this->db->where('id', $id);
		return $this->db->update('pizza_order_items', ['quantity' => $quantity, 'sub_total' => $price]);
	}
}
?>
