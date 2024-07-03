<?php
namespace App\Models;
use CodeIgniter\Model;

class Store_item_quantity extends Model
{
    public function exists($item_id, $store_id)
    {
       $builder = $this->db->table('raw_order_item_quantities')
                           ->where('item_id', $item_id)
                           ->where('store_id', $store_id);

        return ($builder->get()->getNumRows() == 1);
    }
    
    public function save_store_item_quantity($location_detail, $item_id, $store_id)
    {
        if(!$this->exists($item_id, $store_id))
        {
            return $this->db->table('raw_order_item_quantities')->insert( $location_detail);
        }

        $builder = $this->db->table('raw_order_item_quantities')->where('item_id', $item_id)
                            ->where('store_id', $store_id);

        return $builder->update($location_detail);
    }
    
    public function get_item_quantity($item_id, $store_id)
    {     
        $builder = $this->db->table('raw_order_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('store_id', $store_id);
        $result = $builder->get()->getRow();
        if(empty($result) == TRUE)
        {
            //Get empty base parent object, as $item_id is NOT an item
            $result = new stdClass();

            //Get all the fields from items table (TODO to be reviewed)
            foreach($this->db->getFieldNames('raw_order_item_quantities') as $field)
            {
                $result->$field = '';
            }

            $result->quantity = 0;
        }
		
        return $result;   
    }
	
	/*
	 * changes to quantity of an item according to the given amount.
	 * if $quantity_change is negative, it will be subtracted,
	 * if it is positive, it will be added to the current quantity
	 */
	public function change_quantity($item_id, $person_id, $quantity_change)
	{
		$quantity_old = $this->get_item_quantity($item_id, $person_id);
		$quantity_new = $quantity_old->quantity + intval($quantity_change);
		$location_detail = array('item_id' => $item_id, 'store_id' => $person_id, 'quantity' => $quantity_new);

		return $this->save_store_item_quantity($location_detail, $item_id, $person_id);
	}
	
	/*
	* Set to 0 all quantity in the given item
	*/
	public function reset_quantity($item_id)
	{
       $builder = $this->db->table('raw_order_item_quantities')->where('item_id', $item_id);

        return $builder->update(array('quantity' => 0));
	}
	
	/*
	* Set to 0 all quantity in the given list of items
	*/
	public function reset_quantity_list($item_ids)
	{
        $builder = $this->db->table('raw_order_item_quantities')->whereIn('item_id', $item_ids);

        return $builder->update(array('quantity' => 0));
	}
}
?>