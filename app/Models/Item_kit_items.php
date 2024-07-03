<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;

class Item_kit_items extends Model
{
	/*
	Gets item kit items for a particular item kit
	*/
	protected $table = 'ospos_item_kit_items';

    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
    }
	public function get_info($item_kit_id)
	{
		$builder = $this->db->table($this->table)
		                    ->where('item_kit_id', $item_kit_id)
		                    ->get();
		//return an array of item kit items for an item
		return $builder->getResult();
	}
	
	/*
	Inserts or updates an item kit's items
	*/
	public function saveItemKitItems(&$item_kit_items_data, $item_kit_id)
	{
		$success = TRUE;
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
        $builder = $this->db->table($this->table);
		$this->deleteItemKitItems($item_kit_id);
		
		foreach($item_kit_items_data as $row)
		{
			$row['item_kit_id'] = $item_kit_id;
			$success &= $builder->insert($row);		
		}
		
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Deletes item kit items given an item kit
	*/
	public function deleteItemKitItems($item_kit_id)
	{
        $builder = $this->db->table($this->table)
		                    ->where('item_kit_id', $item_kit_id);
		return $builder->delete(); 
	}
}
