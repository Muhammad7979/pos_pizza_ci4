<?php
namespace App\Models;
use CodeIgniter\Model;
class Raw_inventory extends Model 
{	
	public function insert_raw_inventory($inventory_data)
	{
		return $this->db->table('raw_inventory')->insert($inventory_data);
	}
	
	public function get_inventory_data_for_item($item_id, $location_id = FALSE)
	{
		$builder = $this->db->table('raw_inventory')
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