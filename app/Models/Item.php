<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;
use stdClass;

class Item extends Model
{
    /*
	Determines if a given item_id is an item
	*/
	protected $table = 'ospos_items';
	
    public function __construct()
    {
		parent::__construct();

	}
	public function item_exists($item_id, $ignore_deleted = FALSE, $deleted = FALSE)
	{
		$builder = $this->table($this->table)
		                ->where('CAST(item_id AS CHAR) = ', $item_id);
		if($ignore_deleted == FALSE)
		{
			$builder->where('deleted', $deleted);
		}
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Determines if a given item_number exists
	*/
	public function item_number_exists($item_number, $item_id = '')
	{
		$builder = $this->db->table($this->table)

		                ->where('item_number', $item_number);
		if(!empty($item_id))
		{
			$builder->where('item_id !=', $item_id);
		}

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->table($this->table)

		                ->where('deleted', 0);

		return $builder->countAllResults();
	}

	/*
	Get number of rows
	*/
	public function get_found_rows($search, $filters)
	{
		return $this->search($search, $filters)->getNumRows();
	}

	/*
	Perform a search on items
	*/
	// public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'items.name', $order = 'asc')
	// {
	// 	$builder = $this->table($this->table)
	// 	                ->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left')
	// 	                ->join('inventory', 'inventory.trans_items = items.item_id');

	// 	if($filters['stock_location_id'] > -1)
	// 	{
	// 	$builder = $this->table($this->table)
	// 		            ->join('item_quantities', 'item_quantities.item_id = items.item_id')
	// 		            ->where('location_id', $filters['stock_location_id']);
	// 	}

	//     //  $builder->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $builder->escape($filters['start_date']) . ' AND ' . $builder->escape($filters['end_date']));
	// 	$builder->where("DATE_FORMAT(trans_date, '%Y-%m-%d') BETWEEN :start_date: AND :end_date:", [
	// 		'start_date' => $filters['start_date'],
	// 		'end_date' => $filters['end_date']
	// 	]);

	// 	if(!empty($search))
	// 	{
	// 		if ($filters['search_custom'] == FALSE) {
	// 			$builder = $this->db->table($this->table);
	// 			$builder->groupStart()
	// 				->like('name', $search)
	// 				->orLike('item_number', $search)
	// 				->orLike('items.item_id', $search)
	// 				->orLike('company_name', $search)
	// 				->orLike('category', $search)
	// 				->groupEnd();
	// 		}
	// 		else
	// 		{
	// 			$builder->groupStart()
    //                 ->like('custom1', $search)
    //                 ->orLike('custom2', $search)
    //                 ->orLike('custom3', $search)
    //                 ->orLike('custom4', $search)
    //                 ->orLike('custom5', $search)
    //                 ->orLike('custom6', $search)
    //                 ->orLike('custom7', $search)
    //                 ->orLike('custom8', $search)
    //                 ->orLike('custom9', $search)
    //                 ->orLike('custom10', $search)
    //                 ->groupEnd();

	// 		}
	// 	}

	// 	$builder->where('items.deleted', $filters['is_deleted']);

	// 	if($filters['empty_upc'] != FALSE)
	// 	{
	// 		$builder->where('item_number', NULL);
	// 	}
	// 	if($filters['low_inventory'] != FALSE)
	// 	{
	// 		$builder->where('quantity <=', 'reorder_level');
	// 	}
	// 	if($filters['is_serialized'] != FALSE)
	// 	{
	// 		$builder->where('is_serialized', 1);
	// 	}
	// 	if($filters['no_description'] != FALSE)
	// 	{
	// 		$builder->where('items.description', '');
	// 	}

	// 	// avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
	// 	$builder->group_by('items.item_id');
		
	// 	// order by name of item
	// 	$builder->order_by($sort, $order);

	// 	if($rows > 0) 
	// 	{	
	// 		$builder->limit($rows, $limit_from);
	// 	}

	// 	return $builder->get();
	// }

	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'items.name', $order = 'asc')
	{
		$builder = $this->db->table('items');
		$builder->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left');
		$builder->join('inventory', 'inventory.trans_items = items.item_id');

		if ($filters['stock_location_id'] > -1) {
			$builder->join('item_quantities', 'item_quantities.item_id = items.item_id');
			$builder->where('location_id', $filters['stock_location_id']);
		}

		$builder->where("DATE_FORMAT(trans_date, '%Y-%m-%d') BETWEEN '{$filters['start_date']}' AND '{$filters['end_date']}'");

		if (!empty($search)) {
			$builder->groupStart();
			if ($filters['search_custom'] == FALSE) {
				$builder->like('name', $search);
				$builder->orLike('item_number', $search);
				$builder->orLike('items.item_id', $search);
				$builder->orLike('company_name', $search);
				$builder->orLike('category', $search);
			} else {
				$builder->like('custom1', $search);
				$builder->orLike('custom2', $search);
				$builder->orLike('custom3', $search);
				$builder->orLike('custom4', $search);
				$builder->orLike('custom5', $search);
				$builder->orLike('custom6', $search);
				$builder->orLike('custom7', $search);
				$builder->orLike('custom8', $search);
				$builder->orLike('custom9', $search);
				$builder->orLike('custom10', $search);
			}
			$builder->groupEnd();
		}

		$builder->where('items.deleted', $filters['is_deleted']);

		if ($filters['empty_upc'] != FALSE) {
			$builder->where('item_number', NULL);
		}
		if ($filters['low_inventory'] != FALSE) {
			$builder->where('quantity <=', 'reorder_level');
		}
		if ($filters['is_serialized'] != FALSE) {
			$builder->where('is_serialized', 1);
		}
		if ($filters['no_description'] != FALSE) {
			$builder->where('items.description', '');
		}

		$builder->groupBy('items.item_id');

		$builder->orderBy('items.name', 'ASC');

		if ($rows > 0) {
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
	

	
	/*
	Returns all the items
	*/
	public function get_all($stock_location_id = -1, $rows = 0, $limit_from = 0)
{
    $builder = $this->db->table($this->table)
        ->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left');

    if ($stock_location_id > -1) {
        $builder->join('item_quantities', 'item_quantities.item_id = items.item_id');
        $builder->where('location_id', $stock_location_id);
    }

    $builder->where('ospos_items.deleted', 0);

    // Order by the name of the item
    $builder->orderBy('items.item_id', 'desc');

    if ($rows > 0) {
        $builder->limit($rows, $limit_from);
    }

    $result = $builder->get();
    $resultdata = $result->getResultArray();
    return $resultdata;
}

	/*
	Gets information about a particular item
	*/
	public function get_info($item_id)
	{
		$builder = $this->db->table('items')
		                ->select('items.*, items_taxes.percent,suppliers.company_name')
		                // ->select('suppliers.company_name')
		                // ->from('items')
		                ->join('items_taxes', 'items_taxes.item_id = items.item_id', 'left')
		                ->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left')
		                ->where('items.item_id', $item_id)
		                ->get();
						// dd($builder->getNumRows());
		if ($builder->getNumRows() >= 1) {
            return $builder->getRow();
        } else {
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->getFieldNames($this->table) as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Get an item id given an item number
	*/
	public function get_item_id($item_number)
	{
		$builder = $this->db->table($this->table)
		                    ->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left')
		                    ->where('item_number', $item_number)
		                    ->where('items.deleted', 0)
                            ->get();
		
		if ($builder->getNumRows() == 1) 
		{
			return $builder->getRow();
		}
		return FALSE;
	}

	/*
	Gets information about multiple items
	*/
	public function get_multiple_info($item_ids, $location_id)
	{
		$builder = $this->db->table($this->table)
		                    ->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left')
		                    ->join('item_quantities', 'item_quantities.item_id = items.item_id', 'left')
		                    ->where('location_id', $location_id)
		                    ->whereIn('items.item_id', $item_ids)
							->get();

		return $builder;
	}

	/*
	Inserts or updates a item
	*/
	public function saveItem(&$item_data, $item_id = FALSE)
	{
		$builder = $this->db->table($this->table);
		$item_exist = $this->item_exists($item_id, TRUE);
	    if(!$item_id || !$item_exist)
		{

			if($builder->insert($item_data))
			{
				$item_data['item_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}
		$builder->where('item_id', $item_id);
		return $builder->update($item_data);
	}

	/*
	Updates multiple items at once
	*/
	public function update_multiple($item_data, $item_ids)
	{
		$builder = $this->db->table($this->table);
		$gu = new \App\Libraries\Gu;
	    if(!$gu->isServer()){
	        $builder->where('custom10','no');
        }
		$builder->whereIn('item_id', explode(':', $item_ids));

		return $builder->update($item_data);
	}

	/*
	Deletes one item
	*/
	public function deleteItem($item_id)
	{
		$builder = $this->db->table($this->table);
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

		// set to 0 quantities
		$this->Item_quantity->reset_quantity($item_id);
		$builder->where('item_id', $item_id);
		$success = $builder->update('items', array('deleted'=>1));
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Undeletes one item
	*/
	public function undelete($item_id)
	{
		$builder = $this->db->table($this->table)

		                    ->where('item_id', $item_id);

		return $builder->update(array('deleted'=>0));
	}

	/*
	Deletes a list of items
	*/
	public function delete_list($item_ids)
	{
		$db = \Config\Database::connect();
        $builder = $db->table('items');

        // Run these queries as a transaction, we want to make sure we do all or nothing
        $db->transStart();

        // Set to 0 quantities
        $item_quantity = new \App\Models\Item_quantity();
        $item_quantity->reset_quantity_list($item_ids);

        // Update items as deleted
        $builder->whereIn('item_id', $item_ids)
            ->set('deleted', 1)
            ->update();

        $db->transComplete();

        $success = $db->transStatus();

        return $success;
 	}

	public function get_search_suggestions($search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		$suggestions = array();
		$builder = $this->db->table($this->table)
		       ->select('item_id, name, item_number, unit_price')
		       ->where('deleted', $filters['is_deleted'])
		       ->like('name', $search)
		       ->orderBy('name', 'asc');
			   foreach ($builder->get()->getResultArray() as $row) {
				$suggestions[] = array(
					'value' => $row['item_id'], // Use array syntax here
					'label' => $row['name'] . " [" . $row['item_number'] . "] (Rs. " . $row['unit_price'] . ")"
				);
			}

		$builder->select('item_id, item_number, name, unit_price')
		         ->where('deleted', $filters['is_deleted'])
		         ->like('item_number', $search)
		         ->orderBy('item_number', 'asc');
				 foreach ($builder->get()->getResultArray() as $row) {
					$suggestions[] = array(
						'value' => $row['item_id'], // Use array syntax here
						'label' => $row['item_number'] . " " . $row['name'] . " (Rs. " . $row['unit_price'] . ")"
					);
				}

		if(!$unique)
		{
			//Search by category
		$builder = $this->db->table($this->table);
        $builder->select('category')
                 ->where('deleted', $filters['is_deleted'])
                 ->distinct()
                 ->like('category', $search)
                 ->orderBy('category', 'asc');
			foreach($builder->get()->getResultArray() as $row)
			{
				$suggestions[] = array('label' => $row->category);
			}

			//Search by supplier
		$suppliers = $this->db->table('ospos_suppliers');
        $suppliers->select('company_name')
                 ->like('company_name', $search)
                 ->where('deleted', $filters['is_deleted'])
                 ->distinct()
                 ->orderBy('company_name', 'asc');
			foreach($suppliers->get()->getResultArray() as $row)
			{
				$suggestions[] = array('label' => $row->company_name);
			}

			//Search by description
        $builder->select('item_id, item_number, name, description, unit_price')
                ->where('deleted', $filters['is_deleted'])
                ->like('description', $search)
                ->orderBy('description', 'asc');

			foreach($builder->get()->getResultArray() as $row)
			{
				$entry = array('value' => $row->item_id, 'label' => $row->name);
				if(!array_walk($suggestions, function($value, $label) use ($entry) { return $entry['label'] != $label; } ))
				{
					$suggestions[] = $entry;
				}
			}

			//Search by custom fields
			if($filters['search_custom'] != FALSE)
			{
               $builder->groupStart()
                       ->like('custom1', $search)
                       ->orLike('custom2', $search)
                       ->orLike('custom3', $search)
                       ->orLike('custom4', $search)
                       ->orLike('custom5', $search)
                       ->orLike('custom6', $search)
                       ->orLike('custom7', $search)
                       ->orLike('custom8', $search)
                       ->orLike('custom9', $search)
                       ->orLike('custom10', $search)
                       ->groupEnd()
                       ->where('deleted', $filters['is_deleted']);
				foreach($builder->get()->getResultArray() as $row)
				{
					$suggestions[] = array('value' => $row->item_id, 'label' => $row->name);
				}
			}
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0,$limit);
		}

		return $suggestions;
	}

	public function get_category_suggestions($search)
	{
		$suggestions = array();
		$builder = $this->db->table($this->table)
		                    ->distinct()
                            ->select('category')
                            // ->from('items')
                            ->like('category', $search)
                            ->where('deleted', 0)
                            ->orderBy('category', 'asc');
		/* foreach($builder->get()->getResultArray() as $row)
		{
			$suggestions[] = array('label' => $row->category);
		} */
		$results = $builder->get()->getResult();

        foreach ($results as $row) {
            $suggestions[] = ['label' => $row->category];
        }

		return $suggestions;
	}
	
	public function get_location_suggestions($search)
	{
		$suggestions = array();
		$builder = $this->db->table($this->table)
                    	    ->distinct()
                            ->select('location')
                            ->from('items')
                            ->like('location', $search)
                            ->where('deleted', 0)
                            ->orderBy('location', 'asc');
		foreach($builder->get()->getResultArray() as $row)
		{
			$suggestions[] = array('label' => $row->location);
		}
	
		return $suggestions;
	}

	public function get_custom_suggestions($search, $field_no)
	{
		$suggestions = array();
		$builder = $this->db->table($this->table)
		                    ->distinct()
		                    ->select('custom'.$field_no)
                            ->from('items')
                            ->like('custom'.$field_no, $search)
                            ->where('deleted', 0)
                            ->orderBy('custom'.$field_no, 'asc');
		foreach($builder->get()->getResultArray() as $row)
		{
			$row_array = (array) $row;
			$suggestions[] = array('label' => $row_array['custom'.$field_no]);
		}
	
		return $suggestions;
	}

	public function get_categories()
	{
		$builder = $this->db->table($this->item);
        $builder->select('category')
        ->from('items')
        ->where('deleted', 0)
        ->distinct()
        ->orderBy('category', 'asc');

		return $builder->get();
	}

	/*
	 * changes the cost price of a given item
	 * calculates the average price between received items and items on stock
	 * $item_id : the item which price should be changed
	 * $items_received : the amount of new items received
	 * $new_price : the cost-price for the newly received items
	 * $old_price (optional) : the current-cost-price
	 *
	 * used in receiving-process to update cost-price if changed
	 * caution: must be used there before item_quantities gets updated, otherwise average price is wrong!
	 *
	 */
	public function change_cost_price($item_id, $items_received, $new_price, $old_price = null)
	{
		if($old_price === null)
		{
			$item_info = $this->get_info($item_id);
			$old_price = $item_info->cost_price;
		}

		$item_quantities = $this->db->table('ospos_item_quantities');
        $item_quantities->selectSum('quantity')
        ->where('item_id', $item_id)
        ->join('stock_locations', 'stock_locations.location_id=item_quantities.location_id')
        ->where('stock_locations.deleted', 0);

$result = $item_quantities->get()->getRow();
$old_total_quantity = $result->quantity;

		$total_quantity = $old_total_quantity + $items_received;
		$average_price = bcdiv(bcadd(bcmul($items_received, $new_price), bcmul($old_total_quantity, $old_price)), $total_quantity);

		$data = array('cost_price' => $average_price);

		return $this->save($data, $item_id);
	}
	
	//We create a temp table that allows us to do easy report queries
	public function create_temp_table()
	{
		// $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->dbprefix('items_temp') . 
		// 	'(
		// 		SELECT
		// 			items.name,
		// 			items.item_number,
		// 			items.description,
		// 			items.reorder_level,
		// 			item_quantities.quantity,
		// 			stock_locations.location_name,
		// 			stock_locations.location_id,
		// 			items.cost_price,
		// 			items.unit_price,
		// 			(items.cost_price * item_quantities.quantity) AS sub_total_value
		// 		FROM ' . $this->db->dbprefix('items') . ' AS items
		// 		INNER JOIN ' . $this->db->dbprefix('item_quantities') . ' AS item_quantities
		// 			ON items.item_id = item_quantities.item_id
		// 		INNER JOIN ' . $this->db->dbprefix('stock_locations') . ' AS stock_locations
		// 			ON item_quantities.location_id = stock_locations.location_id
		// 		WHERE items.deleted = 0
		// 	)'
		// );
		$query = 'CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->DBPrefix . 'items_temp' . 
    '(
        SELECT
            items.name,
            items.item_number,
            items.description,
            items.reorder_level,
            item_quantities.quantity,
            stock_locations.location_name,
            stock_locations.location_id,
            items.cost_price,
            items.unit_price,
            (items.cost_price * item_quantities.quantity) AS sub_total_value
        FROM ' . $this->db->DBPrefix . 'items' . ' AS items
        INNER JOIN ' . $this->db->DBPrefix . 'item_quantities' . ' AS item_quantities
            ON items.item_id = item_quantities.item_id
        INNER JOIN ' . $this->db->DBPrefix . 'stock_locations' . ' AS stock_locations
            ON item_quantities.location_id = stock_locations.location_id
        WHERE items.deleted = 0
    )';

$this->db->query($query);
	}

	public function delete_sale_item($data)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		// return "Asd";	
		$builder = $this->db->table($this->table);
	               $this->db->transStart();
		$success = $builder->insert('sales_items_deleted_log', $data);
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
	}
}
?>