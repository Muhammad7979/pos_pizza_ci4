<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;

class Inventory extends Model 
{	
    protected $table = 'ospos_inventory';
	public function __construct()
	{
        $this->db = Database::connect(); // Load the database
    }

	public function insertInventory($inventory_data)
	{
        $builder = $this->db->table($this->table);
		return $builder->insert($inventory_data);
	}
	
	public function get_inventory_data_for_item($item_id, $location_id = FALSE)
	{    
        $builder = $this->db->table($this->table)
		                    ->where('trans_items', $item_id);
        if($location_id != FALSE)
        {
            $builder->where('trans_location', $location_id);
        }
		$builder->orderBy('trans_date', 'desc');

		return $builder->get();		
	}
}
?>