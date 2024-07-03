<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;

class Item_taxes extends Model
{
	protected $table = 'ospos_items_taxes';

    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
    }

	/*
	Gets tax info for a particular item
	*/
	public function get_info($item_id)
	{
        $builder = $this->db->table($this->table)
		                    ->where('item_id',$item_id);

		//return an array of taxes for an item
		return $builder->get()->getResultArray();

	}
	
	/*
	Inserts or updates an item's taxes
	*/
	// public function saveItemTaxes(&$items_taxes_data, $item_id)
	// {
	// 	$success = TRUE;
    //     $builder = $this->db->table($this->table);
		
	// 	//Run these queries as a transaction, we want to make sure we do all or nothing
	// 	$this->db->transStart();

	// 	// $this->delete($item_id);
	// 	foreach($items_taxes_data as $row)
	// 	{
	// 		$row['item_id'] = $item_id;
	// 		$success &= $builder->insert($row);	
	// 	}
		
	// 	$this->db->transComplete();

	// 	$success &= $this->db->transStatus();

	// 	return $success;
	// }
	public function saveItemTaxes(&$items_taxes_data, $item_id)
	{
		$success = TRUE;
        $builder = $this->db->table('items_taxes');
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

		$this->deleteItemTaxes($item_id);

		foreach($items_taxes_data as $row)
		{
			$row['item_id'] = $item_id;
			$success &= $builder->insert($row);		
		}
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;
	}

	/*
	Saves taxes for multiple items
	*/
	public function save_multiple(&$items_taxes_data, $item_ids)
	{
		$success = TRUE;
        $builder = $this->db->table($this->table);
		
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		// foreach(explode(':', $item_ids) as $item_id)
		// {
		// 	$this->delete($item_id);

		// 	foreach($items_taxes_data as $row)
		// 	{
		// 		$row['item_id'] = $item_id;
		// 		$success &= $this->db->insert('items_taxes', $row);		
		// 	}
		// }
		foreach (explode(':', $item_ids) as $item_id) {
			$builder = $this->db->table($this->table);
			$builder->where('item_id', $item_id);
			$builder->delete();
		
			foreach ($items_taxes_data as $row) {
				$row['item_id'] = $item_id;
				$success &= $this->db->table('items_taxes')->insert($row);
			}
		}
		
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;
	}

	/*
	Deletes taxes given an item
	*/
	// public function deleteItemTaxes($item_id)
	// {    
	// 	$builder = $this->db->table($this->table);
	// 	return $builder->delete('items_taxes', array('item_id' => $item_id)); 
	// }
	public function deleteItemTaxes($item_id)
{    
    $builder = $this->db->table($this->table);
    $builder->where('item_id', $item_id);
    return $builder->delete();
}

}
?>
