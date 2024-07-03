<?php

namespace App\Models;

use App\Libraries\Gu;
use CodeIgniter\Model;
use Config\Database;
use stdClass;

class Item_quantity extends Model
{
    protected $table = 'ospos_item_quantities';
    protected $gu;
    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
        $this->gu = new Gu();
    }
    public function quantity_exists($item_id, $location_id)
    {
        $builder = $this->db->table($this->table)
            ->where('item_id', $item_id)
            ->where('location_id', $location_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function saveItemQuantities($location_detail, $item_id, $location_id)
    {
        $builder = $this->db->table($this->table);
        if (!$this->quantity_exists($item_id, $location_id)) {
            return $builder->insert($location_detail);
        }

        $builder->where('item_id', $item_id)
            ->where('location_id', $location_id);
        return $builder->update($location_detail);
    }

    public function get_item_quantity($item_id, $location_id)
    {
        $builder = $this->db->table('ospos_item_quantities')
            ->where('item_id', $item_id)
            ->where('location_id', $location_id);
        $result =  $builder->get()->getRow();
        if (empty($result) == TRUE) {
            //Get empty base parent object, as $item_id is NOT an item
            $result = new stdClass();

            //Get all the fields from items table (TODO to be reviewed)
            foreach ($this->db->getFieldNames($this->table) as $field) {
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

        return $this->saveItemQuantities($location_detail, $item_id, $location_id);
    }

    /*
	* Set to 0 all quantity in the given item
	*/
    // public function reset_quantity($item_id)
    // {
    //     $this->db->where('item_id', $item_id);

    //     return $this->db->update('item_quantities', array('quantity' => 0));
    // }
    public function reset_quantity($item_id)
    {
        $builder = $this->db->table($this->table)
            ->where('item_id', $item_id)
            ->set('quantity', 0);

        return $builder->update();
    }

    /*
	* Set to 0 all quantity in the given list of items
	*/
    // public function reset_quantity_list($item_ids)
    // {
    //     $this->db->where_in('item_id', $item_ids);

    //     return $this->db->update('item_quantities', array('quantity' => 0));
    // }
    public function reset_quantity_list($itemIds)
    {
        $builder = $this->db->table($this->table)
            ->whereIn('item_id', $itemIds)
            ->update(['quantity' => 0]);

        return $builder;
    }
}
