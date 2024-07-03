<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class Category extends Model
{
	/*
	Determines if a given item_id is an category
	*/
	public function exists($item_id)
	{
		$builder = $this->db->table('raw_items')
		         ->where('item_id', $item_id)
		         ->where('deleted', 0);

		return ($builder->get()->getNumRows() == 1);
	}
	
	public function get_category_suggestions($search)
	{	
		$suggestions = array();
		$query = $this->db->query("SELECT DISTINCT category FROM ospos_items WHERE deleted=0 AND category LIKE '%$search%' UNION SELECT DISTINCT category FROM ospos_raw_items WHERE deleted=0 AND category LIKE '%$search%' ORDER BY category ASC");
		foreach($query->getResult() as $row)
		{
			$suggestions[] = array('label' => $row->category);
		}
		return $suggestions;

		// $this->db->distinct();
		// $this->db->select('category');
		// $this->db->from('items');
		// $this->db->like('category', $search);
		// $this->db->where('deleted', 0);
		// $query1 = $this->db->get_compiled_select();

		// $this->db->distinct();
		// $this->db->select('category');
		// $this->db->from('raw_items');
		// $this->db->like('category', $search);
		// $this->db->where('deleted', 0);
		// $query2 = $this->db->get_compiled_select();

		// $query = $this->db->query($query1 . ' UNION ' . $query2);
		// foreach($query->result() as $row)
		// {
		// 	$suggestions[] = array('label' => $row->category);
		// }
		// return $suggestions;

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
	Gets information about a particular category
	*/
	public function get_info($item_id)
	{
		$builder = $this->db->table('raw_items')
		                    ->where('item_id', $item_id)
		                    ->where('deleted', 0);

		$query = $builder->get();

		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an category
			$category_obj = new stdClass();

			//Get all the fields from raw_items table
			foreach($this->db->getFieldNames('raw_items') as $field)
			{
				$category_obj->$field = '';
			}

			return $category_obj;
		}
	}

	/*
	Gets an category id given an category number
	*/
	// public function get_category_id($category_number)
	// {
	// 	$this->db->from('raw_items');
	// 	$this->db->where('item_number', $category_number);
	// 	$this->db->where('deleted', 0);

	// 	$query = $this->db->get();

	// 	if($query->num_rows() == 1)
	// 	{
	// 		return $query->row()->item_id;
	// 	}

	// 	return FALSE;
	// }

	/*
	Gets information about multiple raw_items
	*/
	public function get_multiple_info($item_ids)
	{
		$this->db->from('raw_items');
		$this->db->where_in('item_id', $item_ids);
		$this->db->where('deleted', 0);
		$this->db->order_by('category_number', 'asc');

		return $this->db->get();
	}

	/*
	Inserts or updates a category
	*/
	public function save_category(&$category_data, $item_id = -1)
	{
		if($item_id == -1 || !$this->exists($item_id))
		{
			if($this->db->table('raw_items')->insert($category_data))
			{
				$category_data['item_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}

		$builder = $this->db->table('raw_items')->where('item_id', $item_id);

		return $builder->update($category_data);
	}

	public function delete_attributes($item_id)
    {
       $builder = $this->db->table('raw_item_attributes')->where('item_id', $item_id);

		return $builder->update(array('deleted' => 1));

    }

    public function save_attributes($attributes)
    {
    	$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		foreach($attributes as $row)
		{
			//$row['item_kit_id'] = $item_kit_id;
			$item_id = $row['item_id'];
			$attribute_id = $row['attribute_id'];
			$attribute_title = $row['attribute_title'];
			$attribute_price = $row['attribute_price'];
			$attribute_category = $row['attribute_category'];
			$builder = $this->db->table('raw_item_attributes');

			if ($this->item_attribute_exists($attribute_id, $item_id, $attribute_category)) {
				$builder->where('attribute_id', $attribute_id)
				         ->where('attribute_category', $attribute_category)
				         ->where('item_id', $item_id);
				$success &= $builder->update(array('deleted'=>0,'attribute_title'=>$attribute_title,'attribute_price'=>$attribute_price));
			}else{
				$success &= $builder->insert($row);
			}		
		}
		
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;

    	//return $this->db->insert_batch('raw_item_attributes', $attributes);
    }

    /*
	Determines if a given item_number exists
	*/
	public function item_attribute_exists($attribute_id, $item_id, $attribute_category)
	{
		$builder = $this->db->table('raw_item_attributes')
		                   ->where('attribute_id', $attribute_id)
		                   ->where('attribute_category', $attribute_category)
		                   ->where('item_id', $item_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Updates multiple raw_items at once
	*/
	public function update_multiple($category_data, $item_ids)
	{
		$this->db->where_in('item_id', $item_ids);

		return $this->db->update('raw_items', $category_data);
	}

	/*
	Deletes one category
	*/
	public function delete_category($item_id)
	{
		$builder = $this->db->table('raw_items')->where('item_id', $item_id);

		return $builder->update(array('deleted' => 1));
	}

	/*
	Deletes a list of raw_items
	*/
	public function delete_list($item_ids)
	{
		$builder = $this->db->table('raw_items')
		                     ->whereIn('item_id', $item_ids);

		return $builder->update(array('deleted' => 1));
 	}

 	/*
	Get search suggestions to find raw_items
	*/
	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('raw_items')
		         ->like('category', $search)
		         ->where('deleted', 0)
		         ->orderBy('category', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[]=array('label' => $row->category);
		}			

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

	/*
	Performs a search on raw_items
	*/
	public function search($search, $filters, $employee_id = -1, $rows = 0, $limit_from = 0, $sort = 'item_id', $order = 'asc')
	{
		$builder = $this->db->table('raw_items')
		                    ->join('raw_inventory', 'raw_inventory.trans_items = raw_items.item_id')
		                    ->groupStart()
			                     ->orLike('category', $search)
			                     ->orLike('name', $search)
			                     ->orLike('raw_items.item_id', $search)
		                     ->groupEnd()
		                     ->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']))

		                     ->where('raw_items.deleted', 0)
		                     ->where('raw_items.item_type', 4)
		                     ->orderBy($sort, $order);
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
	
	/*
	Gets gift cards
	*/
	public function get_found_rows($search, $filters, $employee_id = -1)
	{
		$builder = $this->db->table('raw_items')
		                    ->join('raw_inventory', 'raw_inventory.trans_items = raw_items.item_id')
		                    ->groupStart()
			                    ->like('category', $search)
			                    ->orLike('name', $search)
			                    ->orLike('raw_items.item_id', $search)
		                    ->groupEnd()
		                     ->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']))
		                     ->where('raw_items.deleted', 0)
		                     ->where('raw_items.item_type', 4);
		// $this->db->where('raw_inventory.trans_user', $employee_id);
		return $builder->get()->getNumRows();
	}
	
	public function get_attributes_all($item_id = -1, $category = -1)
    {
       $builder = $this->db->table('raw_item_attributes')
                           ->where('item_id', $item_id)
                           ->where('deleted', 0);
        if($category!=-1)
        $builder->where('attribute_category', $category);
        return $builder->get();
    }
}
?>
