<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;


class Receiving extends Model
{
	protected $table = 'ospos_receivings';
    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
    }
	public function get_info($receiving_id)
	{	
		$builder = $this->db->table($this->table)
		                    ->join('people', 'people.person_id = receivings.supplier_id', 'LEFT')
		                    ->join('suppliers', 'suppliers.person_id = receivings.supplier_id', 'LEFT')
		                    ->where('receiving_id', $receiving_id);

		return $builder->get();
	}

	public function get_receiving_by_reference($reference)
	{
		$builder = $this->db->table($this->table)
		                    ->where('reference', $reference);

		return $builder->get();
	}

	public function exists($receiving_id)
	{
		$builder = $this->db->table($this->table)
		                    ->where('receiving_id',$receiving_id);

		return ($builder->get()->getNumRows() == 1);
	}
	
	public function updateReceiving($receiving_data, $receiving_id)
	{
		$builder = $this->db->table($this->table)

		                     ->where('receiving_id', $receiving_id);

		return $builder->update('receivings', $receiving_data);
	}

	public function saveReceiving($items, $supplier_id, $employee_id, $comment, $reference, $payment_type, $receiving_id = FALSE)
	{
		$builder = $this->db->table($this->table);

		if(count($items) == 0)
		{
			return -1;
		}

		$receivings_data = array(
			'supplier_id' => $this->Supplier->exists($supplier_id) ? $supplier_id : NULL,
			'employee_id' => $employee_id,
			'payment_type' => $payment_type,
			'comment' => $comment,
			'reference' => $reference
		);

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();

		$builder->insert($receivings_data);
		$receiving_id = $this->db->insertID();

		foreach($items as $line=>$item)
		{
			$cur_item_info = $this->Item->get_info($item['item_id']);

			$receivings_items_data = array(
				'receiving_id' => $receiving_id,
				'item_id' => $item['item_id'],
				'line' => $item['line'],
				'description' => $item['description'],
				'serialnumber' => $item['serialnumber'],
				'quantity_purchased' => $item['quantity'],
				'receiving_quantity' => $item['receiving_quantity'],
				'discount_percent' => $item['discount'],
				'item_cost_price' => $cur_item_info->cost_price,
				'item_unit_price' => $item['price'],
				'item_location' => $item['item_location']
			);

	     	$receiving_items = $this->db->table('ospos_receivings_items');

			 $receiving_items->insert($receivings_items_data);

			$items_received = $item['receiving_quantity'] != 0 ? $item['quantity'] * $item['receiving_quantity'] : $item['quantity'];

			// update cost price, if changed AND is set in config as wanted
			if($cur_item_info->cost_price != $item['price'] && $this->config->item('receiving_calculate_average_price') != FALSE)
			{
				$this->Item->change_cost_price($item['item_id'], $items_received, $item['price'], $cur_item_info->cost_price);
			}

			//Update stock quantity
			$item_quantity = $this->Item_quantity->get_item_quantity($item['item_id'], $item['item_location']);
            $this->Item_quantity->save(array('quantity' => $item_quantity->quantity + $items_received, 'item_id' => $item['item_id'],
                                              'location_id' => $item['item_location']), $item['item_id'], $item['item_location']);

			$recv_remarks = 'RECV ' . $receiving_id;
			$inv_data = array(
				'trans_date' => date('Y-m-d H:i:s'),
				'trans_items' => $item['item_id'],
				'trans_user' => $employee_id,
				'trans_location' => $item['item_location'],
				'trans_comment' => $recv_remarks,
				'trans_inventory' => $items_received
			);

			$this->Inventory->insert($inv_data);

			$supplier = $this->Supplier->get_info($supplier_id);
		}

		$this->db->transComplete();
		
		if($this->db->transStatus() === FALSE)
		{
			return -1;
		}

		return $receiving_id;
	}
	
	public function delete_list($receiving_ids, $employee_id, $update_inventory = TRUE)
	{
		$success = TRUE;

		// start a transaction to assure data integrity
		$this->db->transStart();

		foreach($receiving_ids as $receiving_id)
		{
			$success &= $this->delete($receiving_id, $employee_id, $update_inventory);
		}

		// execute transaction
		$this->db->transComplete();

		$success &= $this->db->transStatus();

		return $success;
	}
	
	public function deleteReceiving($receiving_id, $employee_id, $update_inventory = TRUE)
	{
		// start a transaction to assure data integrity
		$this->db->transStart();

		if($update_inventory)
		{
			// defect, not all item deletions will be undone??
			// get array with all the items involved in the sale to update the inventory tracking
			$items = $this->get_receiving_items($receiving_id)->getResultArray();
			foreach($items as $item)
			{
				// create query to update inventory tracking
				$inv_data = array(
					'trans_date' => date('Y-m-d H:i:s'),
					'trans_items' => $item['item_id'],
					'trans_user' => $employee_id,
					'trans_comment' => 'Deleting receiving ' . $receiving_id,
					'trans_location' => $item['item_location'],
					'trans_inventory' => $item['quantity_purchased'] * -1
				);
				// update inventory
				$this->Inventory->insert($inv_data);

				// update quantities
				$this->Item_quantity->change_quantity($item['item_id'], $item['item_location'], $item['quantity_purchased'] * -1);
			}
		}
		$receiving_items = $this->db->table('ospos_receivings_items');
		// delete all items
		$receiving_items->delete(array('receiving_id' => $receiving_id));
		// delete sale itself
		$builder = $this->db->table($this->table);

		$builder->delete(array('receiving_id' => $receiving_id));

		// execute transaction
		$this->db->transComplete();
	
		return $this->db->transStatus();
	}

	public function get_receiving_items($receiving_id)
	{
		$receiving_items = $this->db->table('ospos_receivings_items')
		                            ->where('receiving_id', $receiving_id);

		return $receiving_items->get();
	}
	
	public function get_supplier($receiving_id)
	{    $builder = $this->db->table($this->table)
		                     ->where('receiving_id', $receiving_id);

		return $this->Supplier->get_info($builder->get()->getRow()->supplier_id);
	}

	public function get_payment_options()
	{
		return array(
			$this->lang->line('sales_cash') => $this->lang->line('sales_cash'),
			$this->lang->line('sales_check') => $this->lang->line('sales_check'),
			$this->lang->line('sales_debit') => $this->lang->line('sales_debit'),
			$this->lang->line('sales_credit') => $this->lang->line('sales_credit')
		);
	}

	/*
	We create a temp table that allows us to do easy report/receiving queries
	*/
	// public function create_temp_table()
	// {
	// 	$this->db->query("CREATE TEMPORARY TABLE IF NOT EXISTS " . $this->db->dbprefix('receivings_items_temp') . "
	// 		(SELECT 
	// 			date(receiving_time) AS receiving_date,
	// 			" . $this->db->dbprefix('receivings_items') . " . receiving_id,
	// 			comment,
	// 			item_location,
	// 			reference,
	// 			payment_type,
	// 			employee_id, 
	// 			" . $this->db->dbprefix('items') . " . item_id,
	// 			" . $this->db->dbprefix('receivings') . " . supplier_id,
	// 			quantity_purchased,
	// 			" . $this->db->dbprefix('receivings_items') . " . receiving_quantity,
	// 			item_cost_price,
	// 			item_unit_price,
	// 			discount_percent,
	// 			(item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) AS subtotal,
	// 			" . $this->db->dbprefix('receivings_items') . " . line AS line,
	// 			serialnumber,
	// 			" . $this->db->dbprefix('receivings_items') . " . description AS description,
	// 			(item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) AS total,
	// 			(item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) - (item_cost_price * quantity_purchased) AS profit,
	// 			(item_cost_price * quantity_purchased) AS cost
	// 		FROM " . $this->db->dbprefix('receivings_items') . "
	// 		INNER JOIN " . $this->db->dbprefix('receivings') . " ON " . $this->db->dbprefix('receivings_items') . '.receiving_id=' . $this->db->dbprefix('receivings') . '.receiving_id' . "
	// 		INNER JOIN " . $this->db->dbprefix('items') . " ON " . $this->db->dbprefix('receivings_items') . '.item_id=' . $this->db->dbprefix('items') . '.item_id' . "
	// 		GROUP BY receiving_id, item_id, line)"
	// 	);
	// }
	public function create_temp_table()
{
    $query = "CREATE TEMPORARY TABLE IF NOT EXISTS " . $this->db->prefixTable('receivings_items_temp') . " 
              (SELECT 
                DATE(receiving_time) AS receiving_date,
                " . $this->db->prefixTable('receivings_items') . ".receiving_id,
                comment,
                item_location,
                reference,
                payment_type,
                employee_id, 
                " . $this->db->prefixTable('items') . ".item_id,
                " . $this->db->prefixTable('receivings') . ".supplier_id,
                quantity_purchased,
                " . $this->db->prefixTable('receivings_items') . ".receiving_quantity,
                item_cost_price,
                item_unit_price,
                discount_percent,
                (item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) AS subtotal,
                " . $this->db->prefixTable('receivings_items') . ".line AS line,
                serialnumber,
                " . $this->db->prefixTable('receivings_items') . ".description AS description,
                (item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) AS total,
                (item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent / 100) - (item_cost_price * quantity_purchased) AS profit,
                (item_cost_price * quantity_purchased) AS cost
              FROM " . $this->db->prefixTable('receivings_items') . "
              INNER JOIN " . $this->db->prefixTable('receivings') . " ON " . $this->db->prefixTable('receivings_items') . ".receiving_id = " . $this->db->prefixTable('receivings') . ".receiving_id
              INNER JOIN " . $this->db->prefixTable('items') . " ON " . $this->db->prefixTable('receivings_items') . ".item_id = " . $this->db->prefixTable('items') . ".item_id
              GROUP BY receiving_id, item_id, line)";

    $this->db->query($query);
}

}
?>
