<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;


class SaleBak extends Model
{
    protected $table = 'sales_items_temp';
    
    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
    }

    public function get_info($sale_id)
{
    $builder = $this->db->table($this->table);
    $builder->select('customer_id, sale_type, branch_code, exact_time, customer_name, customer_first_name AS first_name, customer_last_name AS last_name, customer_email AS email, customer_comments AS comments,
        sale_payment_amount AS amount_tendered, SUM(total) AS amount_due, (sale_payment_amount - SUM(total)) AS change_due, payment_type,
        sale_id, sale_date, sale_time, comment, invoice_number, employee_id');
    $builder->where('sale_id', $sale_id);
    $builder->groupBy('sale_id');
    $builder->orderBy('sale_time', 'asc');

    return $builder->get();
}


    /*
     Get number of rows for the takings (sales/manage) view
    */
    public function get_found_rows($search, $filters)
    {
        return $this->search($search, $filters)->getNumRows();
    }

    /*
     Get the sales data for the takings (sales/manage) view
    */
    public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'sale_date', $order = 'desc')
    {
        $branch = $this->gu->getStoreBranchCode();

        $builder = $this->db->table($this->table);
$builder->select('sale_id, exact_time, sale_type, branch_code, sale_date, sale_time, SUM(quantity_purchased) AS items_purchased,
    customer_name, customer_company_name AS company_name,
    SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit,
    sale_payment_amount AS amount_tendered, SUM(total) AS amount_due, (sale_payment_amount - SUM(total)) AS change_due, 
    payment_type, invoice_number');
$builder->where('sale_date BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
$builder->from('sales_items_temp');
        /**
         * sale mode / type filter
         */
        if ($filters['sale_mode'] != 'all') {
            $builder->where('sale_type', $filters['sale_mode']);
        }


        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        }
        else{
            if($filters['branch_code'] != ""){
                $builder->where('branch_code', $filters['branch_code']);
            }
        }


        if (!empty($search)) {
            if ($filters['is_valid_receipt'] != FALSE) {
                $pieces = explode(' ', $search);
                $builder->where('sale_id', $pieces[1]);
            } else {
                $builder->groupStart();
                $builder->like('customer_last_name', $search);
                $builder->orLike('customer_first_name', $search);
                $builder->orLike('customer_name', $search);
                $builder->orLike('customer_company_name', $search);
                $builder->orLike('branch_code', $search);
                $builder->orLike('sale_type', $search);
                $builder->orLike('invoice_number', $search);
                $builder->groupEnd();
            }
        }

        if ($filters['location_id'] != 'all') {
            $builder->where('item_location', $filters['location_id']);
        }

        if ($filters['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($filters['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }

        if ($filters['only_invoices'] != FALSE) {
            $builder->where('invoice_number IS NOT NULL');
        }


        if ($filters['only_cash'] == TRUE && $filters['only_credit'] == TRUE) {
            $filters['only_cash'] = FALSE;
            $filters['only_credit'] = FALSE;
        } else {
            if ($filters['only_cash'] != FALSE) {
                $builder->groupStart();
                $builder->like('payment_type', $this->lang->line('sales_cash'), 'after');
                $builder->orWhere('payment_type IS NULL');
                $builder->groupEnd();
            }
            if ($filters['only_credit'] != FALSE) {
                $builder->groupStart();
                $builder->like('payment_type', $this->lang->line('sales_credit'), 'after');
                $builder->groupEnd();
            }
        }


        $builder->groupBy('sale_id');
        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }

    /*
     Get the payment summary for the takings (sales/manage) view
    */
    public function get_payments_summary($search, $filters)
    {
        $branch = $this->gu->getStoreBranchCode();

        // get payment summary
        $builder = $this->db->table($this->table)
                            ->select('payment_type, count(*) AS count, SUM(payment_amount) AS payment_amount')
                            ->from('sales')
                            ->join('sales_payments', 'sales_payments.sale_id = sales.sale_id')
                            ->join('people', 'people.person_id = sales.customer_id', 'left');

        if (!$this->gu->isServer()) {
            $this->db->where('branch_code', $branch);
        }

        $this->db->where('DATE(sale_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

        /**
         * sale mode / type filter
         */
        if ($filters['sale_mode'] != 'all') {
            $this->db->where('sale_type', $filters['sale_mode']);
        }

        if (!empty($search)) {
            if ($filters['is_valid_receipt'] != FALSE) {
                $pieces = explode(' ', $search);
                $this->db->where('sales.sale_id', $pieces[1]);
            } else {
                $this->db->groupStart();
                $this->db->like('last_name', $search);
                $this->db->orLike('first_name', $search);
                $this->db->orLike('CONCAT(first_name, " ", last_name)', $search);
                $this->db->groupEnd();
            }
        }

        if ($filters['sale_type'] == 'sales') {
            $this->db->where('payment_amount > 0');
        } elseif ($filters['sale_type'] == 'returns') {
            $this->db->where('payment_amount < 0');
        }


        if ($filters['only_invoices'] != FALSE) {
            $this->db->where('invoice_number IS NOT NULL');
        }


        if ($filters['only_cash'] == TRUE && $filters['only_credit'] == TRUE) {
            $filters['only_cash'] = FALSE;
            $filters['only_credit'] = FALSE;
        } else {
            if ($filters['only_cash'] != FALSE) {
                $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            }
            if ($filters['only_credit'] != FALSE) {
                $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            }
        }


        $this->db->groupBy('payment_type');

        $payments = $this->db->get()->result_array();


        //todo - if no payments
//        if(count($payments) < 1){
//            //sale_payment_amount
//        }

        // consider Gift Card as only one type of payment and do not show "Gift Card: 1, Gift Card: 2, etc." in the total
        $gift_card_count = 0;
        $gift_card_amount = 0;
        foreach ($payments as $key => $payment) {
            if (strstr($payment['payment_type'], $this->lang->line('sales_giftcard')) != FALSE) {
                $gift_card_count += $payment['count'];
                $gift_card_amount += $payment['payment_amount'];

                // remove the "Gift Card: 1", "Gift Card: 2", etc. payment string
                unset($payments[$key]);
            }
        }

        if ($gift_card_count > 0) {
            $payments[] = array('payment_type' => $this->lang->line('sales_giftcard'), 'count' => $gift_card_count, 'payment_amount' => $gift_card_amount);
        }

        return $payments;
    }

    /*
    Gets total of rows
    */
    public function get_total_rows()
    {
        $this->db->from('sales');

        return $this->db->count_all_results();
    }

    public function get_search_suggestions($search, $limit = 25)
    {
        $suggestions = array();

        if (!$this->sale_lib->is_valid_receipt($search)) {
            $this->db->distinct();
            $this->db->select('first_name, last_name');
            $this->db->from('sales');
            $this->db->join('people', 'people.person_id = sales.customer_id');
            $this->db->like('last_name', $search);
            $this->db->orLike('first_name', $search);
            $this->db->orLike('CONCAT(first_name, " ", last_name)', $search);
            $this->db->orLike('company_name', $search);
            $this->db->orLike('branch_code', $search);
            $this->db->orLike('sale_type', $search);
            $this->db->orderBy('last_name', 'asc');

            foreach ($this->db->get()->result_array() as $result) {
                $suggestions[] = array('label' => $result['first_name'] . ' ' . $result['last_name']);
            }
        } else {
            $suggestions[] = array('label' => $search);
        }

        return $suggestions;
    }

    /*
    Gets total of invoice rows
    */
    public function get_invoice_count()
    {
        $this->db->from('sales');
        $this->db->where('invoice_number IS NOT NULL');

        return $this->db->count_all_results();
    }

    public function get_sale_by_invoice_number($invoice_number)
    {
        $this->db->from('sales');
        $this->db->where('invoice_number', $invoice_number);

        return $this->db->get();
    }

    public function get_invoice_number_for_year($year = '', $start_from = 0)
    {
        $year = $year == '' ? date('Y') : $year;
        $this->db->select('COUNT( 1 ) AS invoice_number_year');
        $this->db->from('sales');
        $this->db->where('DATE_FORMAT(sale_time, "%Y" ) = ', $year);
        $this->db->where('invoice_number IS NOT NULL');
        $result = $this->db->get()->row_array();

        return ($start_from + $result['invoice_number_year']);
    }

    public function exists($sale_id)
    {
        $this->db->from('sales');
        $this->db->where('sale_id', $sale_id);

        return ($this->db->get()->num_rows() == 1);
    }

    public function update($sale_id, $sale_data, $payments)
    {
        $this->db->where('sale_id', $sale_id);
        $success = $this->db->update('sales', $sale_data);

        // touch payment only if update sale is successful and there is a payments object otherwise the result would be to delete all the payments associated to the sale
        if ($success && !empty($payments)) {
            //Run these queries as a transaction, we want to make sure we do all or nothing
            $this->db->trans_start();

            // first delete all payments
            $this->db->delete('sales_payments', array('sale_id' => $sale_id));

            // add new payments
            foreach ($payments as $payment) {
                $sales_payments_data = array(
                    'sale_id' => $sale_id,
                    'payment_type' => $payment['payment_type'],
                    'payment_amount' => $payment['payment_amount']
                );

                $success = $this->db->insert('sales_payments', $sales_payments_data);
            }

            $this->db->trans_complete();

            $success &= $this->db->trans_status();
        }

        return $success;
    }

    public function save($items, $customer_id, $employee_id, $comment, $invoice_number, $payments, $sale_type, $sale_id = FALSE)
    {
        if($this->config->item('test_mode')){
            return -2;
        }

        if (count($items) == 0) {
            return -1;
        }
        /**
         * Redis Config
         */
        $redis = new Predis\Client();

        $sales_data = array(
            //TODO - ADD NEW COLUMN HERE and update sale_time
            'exact_time' => date('Y-m-d H:i:s'),
            'sale_time' => $this->gu->getStoreDate(),
            'customer_id' => $this->Customer->exists($customer_id) ? $customer_id : null,
            'employee_id' => $employee_id,
            'sale_type' => $sale_type,
            'branch_code' => $this->gu->getStoreBranchCode(),
            'comment' => $comment,
            'invoice_number' => $invoice_number
        );

        //$redisId = count($redis->keys('sale:*')) + 1;
        $redisId = time();

        //save id of this sale record to redis
        $redis->hset('sale:' . $redisId, 'id', "sale:$redisId");

        // Run these queries as a transaction, we want to make sure we do all or nothing
        //$this->db->trans_start();

        //1ST SALES TABLE
//		$this->db->insert('sales', $sales_data);
//		$sale_id = $this->db->insert_id();

        //REDIS SHIT HERE
        //$redis->set('sale_data',json_encode($sales_data));
        $redis->hset('sale:' . $redisId, 'sale_data', json_encode($sales_data));
        $sale_id = $redisId;


        //REDIS PAYMENTS SAVE
        $redis->hset('sale:' . $redisId, 'payments', json_encode($payments));

        foreach ($payments as $payment_id => $payment) {
            if (substr($payment['payment_type'], 0, strlen($this->lang->line('sales_giftcard'))) == $this->lang->line('sales_giftcard')) {
                // We have a gift card and we have to deduct the used value from the total value of the card.
                $splitpayment = explode(':', $payment['payment_type']);
                $cur_giftcard_value = $this->Giftcard->get_giftcard_value($splitpayment[1]);
                $this->Giftcard->update_giftcard_value($splitpayment[1], $cur_giftcard_value - $payment['payment_amount']);
            }

            $sales_payments_data = array(
                'sale_id' => $sale_id,
                'payment_type' => $payment['payment_type'],
                'payment_amount' => $payment['payment_amount']
            );
            //2ND SALES_PAYMENTS TABLE
            //$this->db->insert('sales_payments', $sales_payments_data);
        }

        //REDIS ITEMS SAVE
        $redis->hset('sale:' . $redisId, 'items', json_encode($items));

        foreach ($items as $line => $item) {
            $cur_item_info = $this->Item->get_info($item['item_id']);

            $sales_items_data = array(
                'sale_id' => $sale_id,
                'item_id' => $item['item_id'],
                'line' => $item['line'],
                'description' => character_limiter($item['description'], 30),
                'serialnumber' => character_limiter($item['serialnumber'], 30),
                'quantity_purchased' => $item['quantity'],
                'discount_percent' => $item['discount'],
                'item_cost_price' => $cur_item_info->cost_price,
                'item_unit_price' => $item['price'],
                'item_location' => $item['item_location']
            );

            //3RD SALES_ITEMS TABLE
            //$this->db->insert('sales_items', $sales_items_data);

            //4TH ITEM_QUANTITY TABLE
            // Update stock quantity
            $item_quantity = $this->Item_quantity->get_item_quantity($item['item_id'], $item['item_location']);
//			$this->Item_quantity->save(array('quantity'		=> $item_quantity->quantity - $item['quantity'],
//                                              'item_id'		=> $item['item_id'],
//                                              'location_id'	=> $item['item_location']), $item['item_id'], $item['item_location']);

            // if an items was deleted but later returned it's restored with this rule
            if ($item['quantity'] < 0) {
                $this->Item->undelete($item['item_id']);
            }

            // Inventory Count Details
            $sale_remarks = 'POS ' . $sale_id;
            $inv_data = array(
                'trans_date' => date('Y-m-d H:i:s'),
                'trans_items' => $item['item_id'],
                'trans_user' => $employee_id,
                'trans_location' => $item['item_location'],
                'trans_comment' => $sale_remarks,
                'trans_inventory' => -$item['quantity']
            );
            //5TH INVENTORY TABLE
            //$this->Inventory->insert($inv_data);

            //REDIS INVENTORY SAVE

            $customer = $this->Customer->get_info($customer_id);
            if ($customer_id == -1 || $customer->taxable) {
                foreach ($this->Item_taxes->get_info($item['item_id']) as $row) {
                    //TODO - 6TH SALES ITEM TAXES TABLE
//					$this->db->insert('sales_items_taxes', array(
//						'sale_id' 	=> $sale_id,
//						'item_id' 	=> $item['item_id'],
//						'line'      => $item['line'],
//						'name'		=> $row['name'],
//						'percent' 	=> $row['percent']
//					));
                }
            }
        }

        //$this->db->trans_complete();

//		if($this->db->trans_status() === FALSE)
//		{
//			return -1;
//		}

        //SAVE STATUS INFO FLAG
        $redis->hset('sale:' . $redisId, 'status', 0);


        return $sale_id;
    }

    public function delete_list($sale_ids, $employee_id, $update_inventory = TRUE)
    {
        $result = TRUE;

        foreach ($sale_ids as $sale_id) {
            $result &= $this->delete($sale_id, $employee_id, $update_inventory);
        }

        return $result;
    }

    public function delete($sale_id, $employee_id, $update_inventory = TRUE)
    {
        // start a transaction to assure data integrity
        $this->db->trans_start();

        // first delete all payments
        $this->db->delete('sales_payments', array('sale_id' => $sale_id));
        // then delete all taxes on items
        $this->db->delete('sales_items_taxes', array('sale_id' => $sale_id));

        if ($update_inventory) {
            // defect, not all item deletions will be undone??
            // get array with all the items involved in the sale to update the inventory tracking
            $items = $this->get_sale_items($sale_id)->result_array();
            foreach ($items as $item) {
                // create query to update inventory tracking
                $inv_data = array(
                    'trans_date' => date('Y-m-d H:i:s'),
                    'trans_items' => $item['item_id'],
                    'trans_user' => $employee_id,
                    'trans_comment' => 'Deleting sale ' . $sale_id,
                    'trans_location' => $item['item_location'],
                    'trans_inventory' => $item['quantity_purchased']
                );
                // update inventory
                $this->Inventory->insert($inv_data);

                // update quantities
                $this->Item_quantity->change_quantity($item['item_id'], $item['item_location'], $item['quantity_purchased']);
            }
        }

        // delete all items
        $this->db->delete('sales_items', array('sale_id' => $sale_id));
        // delete sale itself
        $this->db->delete('sales', array('sale_id' => $sale_id));

        // execute transaction
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_sale_items($sale_id)
    {
        $this->db->from('sales_items');
        $this->db->where('sale_id', $sale_id);

        return $this->db->get();
    }

    public function get_sale_payments($sale_id)
    {
        $this->db->from('sales_payments');
        $this->db->where('sale_id', $sale_id);

        return $this->db->get();
    }

    public function get_payment_options($giftcard = TRUE)
    {
        $payments = array();

        if ($this->config->item('payment_options_order') == 'debitcreditcash') {
            $payments[$this->lang->line('sales_debit')] = $this->lang->line('sales_debit');
            $payments[$this->lang->line('sales_credit')] = $this->lang->line('sales_credit');
            $payments[$this->lang->line('sales_cash')] = $this->lang->line('sales_cash');
        } elseif ($this->config->item('payment_options_order') == 'debitcashcredit') {
            $payments[$this->lang->line('sales_debit')] = $this->lang->line('sales_debit');
            $payments[$this->lang->line('sales_cash')] = $this->lang->line('sales_cash');
            $payments[$this->lang->line('sales_credit')] = $this->lang->line('sales_credit');
        } else // default: if($this->config->item('payment_options_order') == 'cashdebitcredit')
        {
            $payments[$this->lang->line('sales_cash')] = $this->lang->line('sales_cash');
            $payments[$this->lang->line('sales_debit')] = $this->lang->line('sales_debit');
            $payments[$this->lang->line('sales_credit')] = $this->lang->line('sales_credit');
        }

        $payments[$this->lang->line('sales_check')] = $this->lang->line('sales_check');

        if ($giftcard) {
            $payments[$this->lang->line('sales_giftcard')] = $this->lang->line('sales_giftcard');
        }

        return $payments;
    }

    public function get_customer($sale_id)
    {
        $this->db->from('sales');
        $this->db->where('sale_id', $sale_id);

        return $this->Customer->get_info($this->db->get()->row()->customer_id);
    }

    public function invoice_number_exists($invoice_number, $sale_id = '')
    {
        $this->db->from('sales');
        $this->db->where('invoice_number', $invoice_number);
        if (!empty($sale_id)) {
            $this->db->where('sale_id !=', $sale_id);
        }

        return ($this->db->get()->num_rows() == 1);
    }

    public function get_giftcard_value($giftcardNumber)
    {
        if (!$this->Giftcard->exists($this->Giftcard->get_giftcard_id($giftcardNumber))) {
            return 0;
        }

        $this->db->from('giftcards');
        $this->db->where('giftcard_number', $giftcardNumber);

        return $this->db->get()->row()->value;
    }

    //We create a temp table that allows us to do easy report/sales queries
    public function create_temp_table($db = null)
    {
        if($db){
            $this->db = $db;
        }

        if ($this->config->item('tax_included')) {
            $total = '1';
            $subtotal = '(1 - (SUM(1 - 100 / (100 + sales_items_taxes.percent))))';
            $tax = '(SUM(1 - 100 / (100 + sales_items_taxes.percent)))';
        } else {
            $tax = '(SUM(sales_items_taxes.percent) / 100)';
            $total = '(1 + (SUM(sales_items_taxes.percent / 100)))';
            $subtotal = '1';
        }

        $sale_total = '(sales_items.item_unit_price * sales_items.quantity_purchased - sales_items.item_unit_price * sales_items.quantity_purchased * sales_items.discount_percent / 100)';
        $sale_cost = '(sales_items.item_cost_price * sales_items.quantity_purchased)';

        $decimals = totals_decimals();

        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->dbprefix('sales_items_temp') .
            '(
				SELECT
					DATE(sales.sale_time) AS sale_date,
					sales.sale_time,
					sales.sale_id,
					sales.sale_type AS sale_type,
					sales.branch_code,
					sales.exact_time,
					sales.comment,
					sales.invoice_number,
					sales.customer_id,
					CONCAT(customer_p.first_name, " ", customer_p.last_name) AS customer_name,
					customer_p.first_name AS customer_first_name,
					customer_p.last_name AS customer_last_name,
					customer_p.email AS customer_email,
					customer_p.comments AS customer_comments, 
					customer.company_name AS customer_company_name,
					sales.employee_id,
					CONCAT(employee.first_name, " ", employee.last_name) AS employee_name,
					items.item_id,
					items.name,
					items.category,
					items.supplier_id,
					sales_items.quantity_purchased,
					sales_items.item_cost_price,
					sales_items.item_unit_price,
					sales_items.discount_percent,
					sales_items.line,
					sales_items.serialnumber,
					sales_items.item_location,
					sales_items.description,
					payments.payment_type,
					IFNULL(payments.sale_payment_amount, 0) AS sale_payment_amount,
					SUM(sales_items_taxes.percent) AS item_tax_percent,
					' . "
					ROUND($sale_total * $total, $decimals) AS total,
					ROUND($sale_total * $tax, $decimals) AS tax,
					ROUND($sale_total * $subtotal, $decimals) AS subtotal,
					ROUND($sale_total - $sale_cost, $decimals) AS profit,
					ROUND($sale_cost, $decimals) AS cost
					" . '
				FROM ' . $this->db->dbprefix('sales_items') . ' AS sales_items
				INNER JOIN ' . $this->db->dbprefix('sales') . ' AS sales
					ON sales_items.sale_id = sales.sale_id
				INNER JOIN ' . $this->db->dbprefix('items') . ' AS items
					ON sales_items.item_id = items.item_id
				LEFT OUTER JOIN (
								SELECT sale_id, 
									SUM(payment_amount) AS sale_payment_amount,
									GROUP_CONCAT(CONCAT(payment_type, " ", payment_amount) SEPARATOR ", ") AS payment_type
								FROM ' . $this->db->dbprefix('sales_payments') . '
								GROUP BY sale_id
							) AS payments
					ON sales_items.sale_id = payments.sale_id		
				LEFT OUTER JOIN ' . $this->db->dbprefix('suppliers') . ' AS supplier
					ON items.supplier_id = supplier.person_id
				LEFT OUTER JOIN ' . $this->db->dbprefix('people') . ' AS customer_p
					ON sales.customer_id = customer_p.person_id
				LEFT OUTER JOIN ' . $this->db->dbprefix('customers') . ' AS customer
					ON sales.customer_id = customer.person_id
				LEFT OUTER JOIN ' . $this->db->dbprefix('people') . ' AS employee
					ON sales.employee_id = employee.person_id
				LEFT OUTER JOIN ' . $this->db->dbprefix('sales_items_taxes') . ' AS sales_items_taxes
					ON sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.item_id = sales_items_taxes.item_id AND sales_items.line = sales_items_taxes.line
				GROUP BY sales.sale_id, items.item_id, sales_items.line
			)'
        );

        //Update null item_tax_percents to be 0 instead of null
        $this->db->where('item_tax_percent IS NULL');
        $this->db->update('sales_items_temp', array('item_tax_percent' => 0));

        //Update null tax to be 0 instead of null
        $this->db->where('tax IS NULL');
        $this->db->update('sales_items_temp', array('tax' => 0));

        //Update null subtotals to be equal to the total as these don't have tax
        $this->db->query('UPDATE ' . $this->db->dbprefix('sales_items_temp') . ' SET total = subtotal WHERE total IS NULL');
    }


    /**
     * Upload sale data to db from sale object of redis
     *
     * @param $db
     * @param $sale
     * @return bool
     */
    public function dbUp($db, $sale)
    {
        try {
            $sales_data = json_decode($sale['sale_data'], true);
            $bill_number = $sales_data['invoice_number'];

            // Run these queries as a transaction, we want to make sure we do all or nothing
            $db->trans_start();

            //TODO - 1ST SALES TABLE save
            $db->insert('sales', $sales_data);
            $sale_id = $db->insert_id();


            //TODO - db PAYMENTS SAVE get redis payments
            $payments = json_decode($sale['payments'], true);

            $sale_payment_type = array_values($payments)[0]['payment_type'];
            $sale_payment_amount = array_values($payments)[0]['payment_amount'];

            foreach ($payments as $payment_id => $payment) {
                if (substr($payment['payment_type'], 0, strlen($this->lang->line('sales_giftcard'))) == $this->lang->line('sales_giftcard')) {
                    // We have a gift card and we have to deduct the used value from the total value of the card.
                    $splitpayment = explode(':', $payment['payment_type']);
                    $cur_giftcard_value = $this->Giftcard->get_giftcard_value($splitpayment[1]);
                    $this->Giftcard->update_giftcard_value($splitpayment[1], $cur_giftcard_value - $payment['payment_amount']);
                }

                $sales_payments_data = array(
                    'sale_id' => $sale_id,
                    'payment_type' => $payment['payment_type'],
                    'payment_amount' => $payment['payment_amount']
                );
                //TODO - 2ND SALES_PAYMENTS TABLE
                $db->insert('sales_payments', $sales_payments_data);
            }

            //TODO - REDIS ITEMS GET
            $items = json_decode($sale['items'], true);

            foreach ($items as $line => $item) {
                $cur_item_info = $this->Item->get_info($item['item_id']);

                $sales_items_data = array(
                    'sale_id' => $sale_id,
                    'item_id' => $item['item_id'],
                    'line' => $item['line'],
                    'description' => character_limiter($item['description'], 30),
                    'serialnumber' => character_limiter($item['serialnumber'], 30),
                    'quantity_purchased' => $item['quantity'],
                    'discount_percent' => $item['discount'],
                    'item_cost_price' => $cur_item_info->cost_price,
                    'item_unit_price' => $item['price'],
                    'item_location' => $item['item_location']
                );

                $db->insert('sales_items', $sales_items_data);

                // Update stock quantity
                $item_quantity = $this->Item_quantity->get_item_quantity($item['item_id'], $item['item_location']);
                $this->Item_quantity->save(array('quantity' => $item_quantity->quantity - $item['quantity'],
                    'item_id' => $item['item_id'],
                    'location_id' => $item['item_location']), $item['item_id'], $item['item_location']);

                // if an items was deleted but later returned it's restored with this rule
                if ($item['quantity'] < 0) {
                    $this->Item->undelete($item['item_id']);
                }

                // Inventory Count Details
                $sale_remarks = 'POS ' . $sale_id;
                $inv_data = array(
                    'trans_date' => date('Y-m-d H:i:s'),
                    'trans_items' => $item['item_id'],
                    'trans_user' => $sales_data['employee_id'],
                    'trans_location' => $item['item_location'],
                    'trans_comment' => $sale_remarks,
                    'trans_inventory' => -$item['quantity']
                );

                $this->Inventory->insert($inv_data);


                $customer = $this->Customer->get_info($sales_data['customer_id']);
                if ($sales_data['customer_id'] == -1 || $customer->taxable) {
                    foreach ($this->Item_taxes->get_info($item['item_id']) as $row) {

                        $db->insert('sales_items_taxes', array(
                            'sale_id' => $sale_id,
                            'item_id' => $item['item_id'],
                            'line' => $item['line'],
                            'name' => $row['name'],
                            'percent' => $row['percent']
                        ));
                    }
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === FALSE)
            {
                if($this->gu->saleLog()){
                    //TODO - TEMP SALE LOG

                    /**
                     * this for testing purpose
                     */
                    $host = $db->hostname;
                    if($host != "localhost"){
                        $host = "server";

                        $log = date('d M Y h:i A') ." DB: " .$host ." Sale ID: "
                            .$bill_number ." Total: ".$sale_payment_amount
                            ." Payment Type: " .$sale_payment_type ." FAILED" .PHP_EOL;


                        file_put_contents(APPPATH . "logs/sales_fail.log", $log, FILE_APPEND);
                    }
                    /////////////------------------------------------------------------
                }
                return false;
            }
            else{
                //transaction success

                if($this->gu->saleLog()){
                    //TODO - TEMP SALE LOG

                    /**
                     * this for testing purpose
                     */
                    $host = $db->hostname;
                    if($host != "localhost"){
                        $host = "server";

                        $log = date('d M Y h:i A') ." DB: " .$host ." Sale ID: "
                            .$bill_number ." Total: ".$sale_payment_amount
                            ." Payment Type: " .$sale_payment_type .PHP_EOL;


                        file_put_contents(APPPATH . "logs/sales_after.log", $log, FILE_APPEND);
                    }


                    /////////////------------------------------------------------------
                }

            }

            echo "<br/><code> Sale added ID: " . $sale_id . "</code>";
            return true;
        } catch (Exception $e) {
            $db->trans_rollback();
            echo " db up process failed. <br/>";

            if($this->gu->saleLog()){
                //TODO - TEMP SALE LOG

                /**
                 * this for testing purpose
                 */
                $host = $db->hostname;
                if($host != "localhost"){
                    $host = "server";

                    $log = date('d M Y h:i A') ." DB: " .$host ." Sale ID: "
                        .$bill_number ." Total: ".$sale_payment_amount
                        ." Payment Type: " .$sale_payment_type ." FAILED" .PHP_EOL;


                    file_put_contents(APPPATH . "logs/sales_fail.log", $log, FILE_APPEND);
                }


                /////////////------------------------------------------------------
            }


            return false;
        }


    }

}

?>
