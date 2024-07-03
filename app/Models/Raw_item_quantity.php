<?php
namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class Raw_item_quantity extends Model
{
    public function exists($item_id, $location_id)
    {
        $builder = $this->db->table('raw_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('location_id', $location_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function exists_item($item_id, $person_id)
    {
        $builder = $this->db->table('raw_order_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('store_id', $person_id);

        return ($builder->get()->getNumRows() == 1);
    }
    
    public function save_raw_item_quantity($location_detail, $item_id, $location_id)
    {
        if(!$this->exists($item_id, $location_id))
        {
            return $this->db->table('raw_item_quantities')->insert($location_detail);
        }
        $builder = $this->db->table('raw_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('location_id', $location_id);

        return $builder->update($location_detail);
    }

    public function save_items($items_detail, $item_id, $person_id)
    {
        //return $this->db->insert('raw_order_item_quantities', $items_detail);
        $builder = $this->db->table('raw_order_item_quantities');
        if(!$this->exists_item($item_id, $person_id))
        {
            return $builder->insert($items_detail);
        }

        $builder->where('item_id', $item_id)
                  ->where('store_id', $person_id);

        return $builder->update($items_detail);
    }
    
    public function get_item_quantity($item_id, $location_id)
    {     
        $builder = $this->db->table('raw_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('location_id', $location_id);
        $result = $builder->get()->getRow();
        if(empty($result) == TRUE)
        {
            //Get empty base parent object, as $item_id is NOT an item
            $result = new stdClass();

            //Get all the fields from items table (TODO to be reviewed)
            foreach($this->db->getFieldNames('raw_item_quantities') as $field)
            {
                $result->$field = '';
            }

            $result->quantity = 0;
        }
		
        return $result;   
    }

    public function get_order_item_quantity($item_id, $store_id, $person_id=-1)
    {     
        $builder = $this->db->table('raw_order_item_quantities')
                            ->where('item_id', $item_id)
                            ->where('store_id', $store_id);
        if($person_id!=-1){
            $builder->where('person_id', $person_id);
        }
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
	public function change_quantity($item_id, $location_id, $quantity_change)
	{
		$quantity_old = $this->get_item_quantity($item_id, $location_id);
		$quantity_new = $quantity_old->quantity + intval($quantity_change);
		$location_detail = array('item_id' => $item_id, 'location_id' => $location_id, 'quantity' => $quantity_new);

		return $this->save_raw_item_quantity($location_detail, $item_id, $location_id);
	}
	
	/*
	* Set to 0 all quantity in the given item
	*/
	public function reset_quantity($item_id)
	{
        $builder = $this->db->table('raw_item_quantities')->where('item_id', $item_id);

        return $builder->update( array('quantity' => 0));
	}
	
	/*
	* Set to 0 all quantity in the given list of items
	*/
	public function reset_quantity_list($item_ids)
	{
        $builder = $this->db->table('raw_item_quantities')->whereIn('item_id', $item_ids);

        return $builder->update( array('quantity' => 0));
	}
}
?>