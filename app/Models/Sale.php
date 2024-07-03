<?php

namespace App\Models;

use App\Libraries\Gu;
use CodeIgniter\Model;
use Config\Database;
use App\Models\Item;
use App\Models\Item_quantity;
use App\Models\Item_taxes;
use App\Models\Inventory;
class Sale extends Model
{
    protected $gu;
    protected $appData;
    protected $table = 'ospos_sales';
    protected $Customer;
    protected $db;
    protected $Item;
    protected $Item_quantity;
    protected $Item_taxes;
    protected $Inventory;

    public function __construct()
    {
        $this->gu = new Gu();
        $this->appData= new Appconfig();
        $this->Customer = new Customer();
        $this->db = Database::connect();
        $this->Item = new Item();
        $this->Item_quantity = new Item_quantity();
        $this->Item_taxes = new Item_taxes();
        $this->Inventory = new Inventory();
    }
    public function get_info($sale_id)
    {
//        $this->db->select('customer_id, sale_type, branch_code, exact_time, customer_name, customer_first_name AS first_name, customer_last_name AS last_name, customer_email AS email, customer_comments AS comments,
//                      sale_payment_amount AS amount_tendered, SUM(total) AS amount_due, (sale_payment_amount - SUM(total)) AS change_due, payment_type,
//                      sale_id, sale_date, sale_time, comment, invoice_number, employee_id');
//        $this->db->from('sales_items_temp');
//
//        $this->db->where('sale_id', $sale_id);
//        $this->db->groupBy('sale_id');
//        $this->db->orderBy('sale_time', 'asc');
//
//        return $this->db->get();


        /**
         * NEW
         */
    
        $this->db = $this->gu->getReportingDb();

        // NOTE: temporary tables are created to speed up searches due to the fact that are ortogonal to the main query
        // create a temporary table to contain all the payments per sale item
        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_payments_temp') .
            '(
                SELECT payments.sale_id AS sale_id, 
                    IFNULL(SUM(payments.payment_amount), 0) AS sale_payment_amount,
                    GROUP_CONCAT(CONCAT(payments.payment_type, " ", payments.payment_amount) SEPARATOR ", ") AS payment_type
                FROM ' . $this->db->prefixTable('sales_payments') . ' AS payments
                INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                    ON sales.sale_id = payments.sale_id
                WHERE sales.sale_id = ' . $this->db->escape($sale_id) . '
                GROUP BY sale_id
            )'
        );

        // create a temporary table to contain all the sum of taxes per sale item
        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_items_taxes_temp') .
            '(
                SELECT sales_items_taxes.sale_id AS sale_id,
                    sales_items_taxes.item_id AS item_id,
                    SUM(sales_items_taxes.percent) AS percent
                FROM ' . $this->db->prefixTable('sales_items_taxes') . ' AS sales_items_taxes
                INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                    ON sales.sale_id = sales_items_taxes.sale_id
                INNER JOIN ' . $this->db->prefixTable('sales_items') . ' AS sales_items
                    ON sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.line = sales_items_taxes.line
                WHERE sales.sale_id = ' . $this->db->escape($sale_id) . '
                GROUP BY sales_items_taxes.sale_id, sales_items_taxes.item_id
            )'
        );

        if ($this->appData->get('tax_included')) {
            $sale_total = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100))';
            $sale_subtotal = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (100 / (100 + sales_items_taxes.percent)))';
            $sale_tax = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (1 - 100 / (100 + sales_items_taxes.percent)))';
        } else {
            $sale_total = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (1 + (sales_items_taxes.percent / 100)))';
            $sale_subtotal = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100))';
            $sale_tax = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (sales_items_taxes.percent / 100))';
        }

        $decimals = totals_decimals();
       $builder =  $this->db->table('sales_items AS sales_items')
                ->select('
                sales.sale_id AS sale_id,
                DATE(sales.sale_time) AS sale_date,
                sales.sale_time AS sale_time,
                sales.sale_payment,
                sales.sale_type, sales.branch_code, sales.exact_time,
                sales.comment AS comment,
                sales.invoice_number AS invoice_number,
                sales.fbr_invoice_number AS fbr_invoice_number,
                sales.employee_id AS employee_id,
                sales.customer_id AS customer_id,
                sales.fbr_fee AS fbr_fee,
                CONCAT(customer_p.first_name, " ", customer_p.last_name) AS customer_name,
                customer_p.first_name AS first_name,
                customer_p.last_name AS last_name,
                customer_p.email AS email,
                customer_p.comments AS comments,
                ' . "
                IFNULL(ROUND($sale_total, $decimals), ROUND($sale_subtotal, $decimals)) AS amount_due,
                payments.sale_payment_amount AS amount_tendered,
                (payments.sale_payment_amount - IFNULL(ROUND($sale_total, $decimals), ROUND($sale_subtotal, $decimals))) AS change_due,
                " . '
                payments.payment_type AS payment_type
        ')

        ->join('sales AS sales', 'sales_items.sale_id = sales.sale_id', 'inner')
        ->join('people AS customer_p', 'sales.customer_id = customer_p.person_id', 'left')
        ->join('customers AS customer', 'sales.customer_id = customer.person_id', 'left')
        ->join('sales_payments_temp AS payments', 'sales.sale_id = payments.sale_id', 'left')
        ->join('sales_items_taxes_temp AS sales_items_taxes', 'sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.item_id = sales_items_taxes.item_id', 'left')

        ->where('sales.sale_id', $sale_id)

        ->groupBy('sales.sale_id')
        ->orderBy('sales.sale_time', 'asc');
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

//        $this->db->select('sale_id, exact_time, sale_type, branch_code, sale_date, sale_time, SUM(quantity_purchased) AS items_purchased,
//                      customer_name, customer_company_name AS company_name,
//                      SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit,
//                      sale_payment_amount AS amount_tendered, SUM(total) AS amount_due, (sale_payment_amount - SUM(total)) AS change_due,
//                      payment_type, invoice_number');
//        $this->db->from('sales_items_temp');
//
//        $this->db->where('sale_date BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
//
//        /**
//         * sale mode / type filter
//         */
//        if ($filters['sale_mode'] != 'all') {
//            $this->db->where('sale_type', $filters['sale_mode']);
//        }
//
//
//        if (!$this->gu->isServer()) {
//            $this->db->where('branch_code', $branch);
//        }
//        else{
//            if($filters['branch_code'] != ""){
//                $this->db->where('branch_code', $filters['branch_code']);
//            }
//        }
//
//
//        if (!empty($search)) {
//            if ($filters['is_valid_receipt'] != FALSE) {
//                $pieces = explode(' ', $search);
//                $this->db->where('sale_id', $pieces[1]);
//            } else {
//                $this->db->group_start();
//                $this->db->like('customer_last_name', $search);
//                $this->db->orLike('customer_first_name', $search);
//                $this->db->orLike('customer_name', $search);
//                $this->db->orLike('customer_company_name', $search);
//                $this->db->orLike('branch_code', $search);
//                $this->db->orLike('sale_type', $search);
//                $this->db->orLike('invoice_number', $search);
//                $this->db->groupEnd();
//            }
//        }
//
//        if ($filters['location_id'] != 'all') {
//            $this->db->where('item_location', $filters['location_id']);
//        }
//
//        if ($filters['sale_type'] == 'sales') {
//            $this->db->where('quantity_purchased > 0');
//        } elseif ($filters['sale_type'] == 'returns') {
//            $this->db->where('quantity_purchased < 0');
//        }
//
//        if ($filters['only_invoices'] != FALSE) {
//            $this->db->where('invoice_number IS NOT NULL');
//        }
//
//
//        if ($filters['only_cash'] == TRUE && $filters['only_credit'] == TRUE) {
//            $filters['only_cash'] = FALSE;
//            $filters['only_credit'] = FALSE;
//        } else {
//            if ($filters['only_cash'] != FALSE) {
//                $this->db->group_start();
//                $this->db->like('payment_type', lang('sales_lang.sales_cash'), 'after');
//                $this->db->or_where('payment_type IS NULL');
//                $this->db->groupEnd();
//            }
//            if ($filters['only_credit'] != FALSE) {
//                $this->db->group_start();
//                $this->db->like('payment_type', lang('sales_lang.sales_credit'), 'after');
//                $this->db->groupEnd();
//            }
//        }
//
//
//        $this->db->groupBy('sale_id');
//        $this->db->orderBy($sort, $order);
//
//        if ($rows > 0) {
//            $this->db->limit($rows, $limit_from);
//        }
//
//        return $this->db->get();

        $branch = $this->gu->getStoreBranchCode();
        $this->db = $this->gu->getReportingDb();


        /**
         * NEW
         */

        // NOTE: temporary tables are created to speed up searches due to the fact that are ortogonal to the main query
        // create a temporary table to contain all the payments per sale item
        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_payments_temp') .
            ' (PRIMARY KEY(sale_id), INDEX(sale_id))
            (
                SELECT payments.sale_id AS sale_id, 
                    IFNULL(SUM(payments.payment_amount), 0) AS sale_payment_amount,
                    GROUP_CONCAT(CONCAT(payments.payment_type, " ", payments.payment_amount) SEPARATOR ", ") AS payment_type
                FROM ' . $this->db->prefixTable('sales_payments') . ' AS payments
                INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                    ON sales.sale_id = payments.sale_id
                WHERE DATE(sales.sale_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']) . '
                GROUP BY sale_id
            )'
        );

        // create a temporary table to contain all the sum of taxes per sale item
        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_items_taxes_temp') .
            ' (INDEX(sale_id), INDEX(item_id))
            (
                SELECT sales_items_taxes.sale_id AS sale_id,
                    sales_items_taxes.item_id AS item_id,
                    SUM(sales_items_taxes.percent) AS percent
                FROM ' . $this->db->prefixTable('sales_items_taxes') . ' AS sales_items_taxes
                INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                    ON sales.sale_id = sales_items_taxes.sale_id
                INNER JOIN ' . $this->db->prefixTable('sales_items') . ' AS sales_items
                    ON sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.line = sales_items_taxes.line
                WHERE DATE(sales.sale_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']) . '
                GROUP BY sales_items_taxes.sale_id, sales_items_taxes.item_id
            )'
        );

        if ($this->appData->get('tax_included')) {
            $sale_total = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100))';
            $sale_subtotal = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (100 / (100 + sales_items_taxes.percent)))';
            $sale_tax = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (1 - 100 / (100 + sales_items_taxes.percent)))';
        } else {
            $sale_total = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (1 + (sales_items_taxes.percent / 100)))';
            $sale_subtotal = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100))';
            $sale_tax = 'SUM(sales_items.item_unit_price * sales_items.quantity_purchased * (1 - sales_items.discount_percent / 100) * (sales_items_taxes.percent / 100))';
        }

        $sale_cost = 'SUM(sales_items.item_cost_price * sales_items.quantity_purchased)';

        $decimals = totals_decimals();

     $builder =   $this->db->table('sales_items AS sales_items')
        ->select('
                sales.sale_id AS sale_id,
                sales.fbr_fee AS fbr_fee,
                exact_time, sale_type, branch_code, sale_payment, 
                
                
                DATE(sales.sale_time) AS sale_date,
                sales.sale_time AS sale_time,
                sales.invoice_number AS invoice_number,
                sales.fbr_invoice_number AS fbr_invoice_number,
                SUM(sales_items.quantity_purchased) AS items_purchased,
                CONCAT(customer_p.first_name, " ", customer_p.last_name) AS customer_name,
                customer.company_name AS company_name,
                ' . "
                ROUND($sale_subtotal+sales.fbr_fee, $decimals) AS subtotal,
                IFNULL(ROUND($sale_tax, $decimals), 0) AS tax,
                IFNULL(ROUND($sale_total, $decimals), ROUND($sale_subtotal, $decimals)) AS total,
                ROUND($sale_cost, $decimals) AS cost,
                ROUND($sale_total - IFNULL($sale_tax, 0) - $sale_cost, $decimals) AS profit,
                IFNULL(ROUND($sale_total+sales.fbr_fee, $decimals), ROUND($sale_subtotal+sales.fbr_fee, $decimals)) AS amount_due,
                payments.sale_payment_amount AS amount_tendered,
                (payments.sale_payment_amount - IFNULL(ROUND($sale_total+sales.fbr_fee, $decimals), ROUND($sale_subtotal+sales.fbr_fee, $decimals))) AS change_due,
                " . '
                payments.payment_type AS payment_type
        ')

        ->join('sales AS sales', 'sales_items.sale_id = sales.sale_id', 'inner')
        ->join('people AS customer_p', 'sales.customer_id = customer_p.person_id', 'left')
        ->join('customers AS customer', 'sales.customer_id = customer.person_id', 'left')
        ->join('sales_payments_temp AS payments', 'sales.sale_id = payments.sale_id', 'left outer')
        ->join('sales_items_taxes_temp AS sales_items_taxes', 'sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.item_id = sales_items_taxes.item_id', 'left outer')

        ->where('DATE(sales.sale_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

        if (!empty($search)) {
            if ($filters['is_valid_receipt'] != FALSE) {
                $pieces = explode(' ', $search);
                $builder->where('sales.sale_id', $pieces[1]);
            } else {
                $builder->groupStart();
                // customer last name
                $builder->like('customer_p.last_name', $search);
                // customer first name
                $builder->orLike('customer_p.first_name', $search);
                // customer first and last name
                $builder->orLike('CONCAT(customer_p.first_name, " ", customer_p.last_name)', $search);
                // customer company name
                $builder->orLike('customer.company_name', $search);

                $builder->orLike('branch_code', $search);
                $builder->orLike('sale_type', $search);
                $builder->orLike('invoice_number', $search);

                $builder->groupEnd();
            }
        }


        if ($filters['location_id'] != 'all') {
            $builder->where('sales_items.item_location', $filters['location_id']);
        }

        if ($filters['sale_type'] == 'sales') {
            $builder->where('sales_items.quantity_purchased > 0');
        } elseif ($filters['sale_type'] == 'returns') {
            $builder->where('sales_items.quantity_purchased < 0');
        }

        if ($filters['only_invoices'] != FALSE) {
            $builder->where('sales.invoice_number IS NOT NULL');
        }

        /**
         * gu new
         */
        if ($filters['only_cash'] == TRUE && $filters['only_credit'] == TRUE && $filters['only_gift'] == TRUE) {
            $filters['only_cash'] = FALSE;
            $filters['only_credit'] = FALSE;
            $filters['only_gift'] = FALSE;
        }else{
            if ($filters['only_cash'] == TRUE) {
                $builder->groupStart();
                $builder->like('payments.payment_type', lang('sales_lang.sales_cash'));

                if ($filters['only_gift'] == TRUE) {
                    $builder->orLike('payments.payment_type', lang('sales_lang.sales_giftcard'));
                }
                if ($filters['only_credit'] == TRUE) {
                    $builder->orLike('payments.payment_type', lang('sales_lang.sales_credit'));
                }

                $builder->orWhere('payments.payment_type IS NULL');
                $builder->groupEnd();
            }
            if ($filters['only_credit'] == TRUE) {
                $builder->groupStart();
                $builder->like('payments.payment_type', lang('sales_lang.sales_credit'));
                if ($filters['only_gift'] == TRUE) {
                    $builder->orLike('payments.payment_type', lang('sales_lang.sales_giftcard'));
                }
                if ($filters['only_cash'] == TRUE) {
                    $builder->orLike('payments.payment_type', lang('sales_lang.sales_cash'));
                }
                $builder->orWhere('payments.payment_type IS NULL');
                $builder->groupEnd();
            }
            // if ($filters['only_gift'] == TRUE) {
            //     $builder->groupStart();
            //     $builder->like('payments.payment_type', lang('sales_lang.sales_giftcard'));
            //     if ($filters['only_cash'] == TRUE) {
            //         $builder->orLike('payments.payment_type', lang('sales_lang.sales_cash'));
            //     }
            //     if ($filters['only_credit'] == TRUE) {
            //         $builder->orLike('payments.payment_type', lang('sales_lang.sales_credit'));
            //     }
            //     $builder->orWhere('payments.payment_type IS NULL');
            //     $builder->groupEnd();
            // }
        }


        /**
         * sale mode / type filter
         */
        if ($filters['sale_mode'] != 'all') {
            $builder->where('sales.sale_type', $filters['sale_mode']);
        }


        if (!$this->gu->isServer()) {
            $builder->where('sales.branch_code', $branch);
        } else {
            if ($filters['branch_code'] != "all") {
                $builder->where('sales.branch_code', $filters['branch_code']);
            }
        }
        //////////////// end gu


        $builder->groupBy('sales.sale_id');
        // $builder->orderBy($sort, $order);s

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
        $builder = $this->db->table($this->table);
        $builder->select('sale_payment, payment_type, count(*) AS count, SUM(payment_amount) AS payment_amount');
        // $this->db->from('sales');
        $builder->join('sales_payments', 'sales_payments.sale_id = sales.sale_id');
        $builder->join('people', 'people.person_id = sales.customer_id', 'left');

        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        }

        $builder->where('DATE(sale_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

        /**
         * sale mode / type filter
         */
        if ($filters['sale_mode'] != 'all') {
            $builder->where('sale_type', $filters['sale_mode']);
        }

        if (!empty($search)) {
            if ($filters['is_valid_receipt'] != FALSE) {
                $pieces = explode(' ', $search);
                $builder->where('sales.sale_id', $pieces[1]);
            } else {
                $builder->groupStart();
                $builder->like('last_name', $search);
                $builder->orLike('first_name', $search);
                $builder->orLike('CONCAT(first_name, " ", last_name)', $search);
                $builder->groupEnd();
            }
        }

        if ($filters['sale_type'] == 'sales') {
            $builder->where('payment_amount > 0');
        } elseif ($filters['sale_type'] == 'returns') {
            $builder->where('payment_amount < 0');
        }


        if ($filters['only_invoices'] != FALSE) {
            $builder->where('invoice_number IS NOT NULL');
        }


        if ($filters['only_cash'] == TRUE && $filters['only_credit'] == TRUE && $filters['only_gift'] == TRUE) {
            $filters['only_cash'] = FALSE;
            $filters['only_credit'] = FALSE;
            $filters['only_gift'] = FALSE;
        } else {
            if ($filters['only_cash'] != FALSE) {
                $builder->llocation_idlocation_idike('payment_type', lang('sales_lang.sales_cash'), 'after');
            }
            if ($filters['only_credit'] != FALSE) {
                $builder->like('payment_type', lang('sales_lang.sales_credit'), 'after');
            }
            if ($filters['only_gift'] != FALSE) {
                $builder->like('payment_type', lang('sales_lang.sales_giftcard'), 'after');
            }
        }


        $builder->groupBy('payment_type');

        $payments = $builder->get()->getResultArray();


        //todo - if no payments
//        if(count($payments) < 1){
//            //sale_payment_amount
//        }

        // consider Gift Card as only one type of payment and do not show "Gift Card: 1, Gift Card: 2, etc." in the total
        $gift_card_count = 0;
        $gift_card_amount = 0;
        foreach ($payments as $key => $payment) {
            if (strstr($payment['payment_type'], lang('sales_lang.sales_giftcard')) != FALSE) {
                $gift_card_count += $payment['count'];
                $gift_card_amount += $payment['payment_amount'];

                // remove the "Gift Card: 1", "Gift Card: 2", etc. payment string
                unset($payments[$key]);
            }
        }

        if ($gift_card_count > 0) {
            $payments[] = array('payment_type' => lang('sales_lang.sales_giftcard'), 'count' => $gift_card_count, 'payment_amount' => $gift_card_amount);
        }

//        echo "<pre>";
//        print_r($payments);
//        echo "</pre>";
//        exit;

        return $payments;
    }

    /*
    Gets total of rows
    */
    public function get_total_rows()
    {
       $builder =  $this->db->table('$this->table');

        return $builder->countAllResults();
    }

    public function get_search_suggestions($search, $limit = 25)
    {
        $suggestions = array();

        if (!$this->sale_lib->is_valid_receipt($search)) {
            $builder = $this->db->table($this->db);
            $builder->distinct();
            $builder->select('first_name, last_name');
            // $builder->from('sales');
            $builder->join('people', 'people.person_id = sales.customer_id');
            $builder->like('last_name', $search);
            $builder->orLike('first_name', $search);
            $builder->orLike('CONCAT(first_name, " ", last_name)', $search);
            $builder->orLike('company_name', $search);
            $builder->orLike('branch_code', $search);
            $builder->orLike('sale_type', $search);
            $builder->orderBy('last_name', 'asc');

            foreach ($builder->get()->getResultArray() as $result) {
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
        
        $builder = $this->db->table($this->table)
        ->where('invoice_number','!==', null);

        return $builder->countAllResults();
    }

    public function get_sale_by_invoice_number($invoice_number)
    {
        // $this->db->from('sales');
        $builder = $this->db->table('sales');
        $builder->where('invoice_number', $invoice_number);

        return $builder->get();
    }

    public function get_invoice_number_for_year($year = '', $start_from = 0)
    {
        $year = $year == '' ? date('Y') : $year;
        $builder = $this->db->table($this->table);
        $builder->select('COUNT( 1 ) AS invoice_number_year');
        // $builder->from('sales');
        $builder->where('DATE_FORMAT(sale_time, "%Y" ) = ', $year);
        $builder->where('invoice_number IS NOT NULL');
        $result = $builder->get()->getRowArray();

        return ($start_from + $result['invoice_number_year']);
    }

    public function exists($sale_id)
    {
        $builder = $this->db->table($this->table);
        // $this->db->from('sales');
        $builder->where('sale_id', $sale_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function update_sale($sale_id, $sale_data, $payments)
    {
      $this->db=$this->gu->getReportingdb();
        $builder = $this->db->table($this->table);
        $builder->where('sale_id', $sale_id);
        $success = $builder->update($sale_data);
        // touch payment only if update sale is successful and there is a payments object otherwise the result would be to delete all the payments associated to the sale
        if ($success && !empty($payments)) {
            //Run these queries as a transaction, we want to make sure we do all or nothing
            $this->db->transStart();

            // first delete all payments
            $sales_payments = $this->db->table('sales_payments')
            ->where('sale_id', $sale_id);
            $sales_payments->delete();
            // $builder->delete('sales_payments', array('sale_id' => $sale_id));
            // add new payments
            foreach ($payments as $payment) {
                // $builder = $this->table($this->table);
                $sales_payments_data = array(
                    'sale_id' => $sale_id,
                    'payment_type' => $payment['payment_type'],
                    'payment_amount' => $payment['payment_amount']
                );

                $success = $sales_payments->insert($sales_payments_data);
            }

            $this->db->transComplete();

            $success &= $this->db->transStatus();
        }

        return $success;
    }

    public function save_sale($items, $customer_id, $employee_id, $comment, $invoice_number, $payments, $sale_type, $payment_type, $fbr_invoice_number,$cake_invoice = '',$fbr_fee = 0)
    {
        if ($this->appData->get('test_mode')) {
            return -2;
        }

        if (count($items) == 0) {
            return -1;
        }
        /**
         * Redis Config
         */
        $redis = new \Predis\Client();

        $sales_data = array(
            //TODO - ADD NEW COLUMN HERE and update sale_time
            'exact_time' => date('Y-m-d H:i:s'),
            'sale_time' => $this->gu->getStoreDate(),
            'customer_id' => $this->Customer->exists($customer_id) ? $customer_id : null,
            'employee_id' => $employee_id,
            'sale_type' => $sale_type,
            'sale_payment' => $payment_type,
            'branch_code' => $this->gu->getStoreBranchCode(),
            'comment' => $comment,
            'fbr_fee'=>$fbr_fee,
            'invoice_number' => $invoice_number,
            'fbr_invoice_number' => $fbr_invoice_number,
            'cake_invoice' => $cake_invoice,
        );

        //$redisId = count($redis->keys('sale:*')) + 1;
        $redisId = time();

        //save id of this sale record to redis
        $redis->hset('sale:' . $redisId, 'id', "sale:$redisId");

        // Run these queries as a transaction, we want to make sure we do all or nothing
        //$this->db->trans_start();

        //1ST SALES TABLE
//      $this->db->insert('sales', $sales_data);
//      $sale_id = $this->db->insert_id();

        //REDIS SHIT HERE
        //$redis->set('sale_data',json_encode($sales_data));
        $redis->hset('sale:' . $redisId, 'sale_data', json_encode($sales_data));
        $sale_id = $redisId;


        //REDIS PAYMENTS SAVE
        $redis->hset('sale:' . $redisId, 'payments', json_encode($payments));

        foreach ($payments as $payment_id => $payment) {
            if (substr($payment['payment_type'], 0, strlen(lang('sales_lang.sales_giftcard'))) == lang('sales_lang.sales_giftcard')) {
                // We have a gift card and we have to deduct the used value from the total value of the card.
                $splitpayment = explode(':', $payment['payment_type']);
                //$cur_giftcard_value = $this->Giftcard->get_giftcard_value($splitpayment[1]);
                $this->Giftcard->update_giftcard_status($splitpayment[1]);
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
        if($item['description']==null){
            $item['description']='';
        }
        if($item['serialnumber']==null){
            $item['serialnumber']='';
        }
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
//          $this->Item_quantity->save(array('quantity'     => $item_quantity->quantity - $item['quantity'],
//                                              'item_id'       => $item['item_id'],
//                                              'location_id'   => $item['item_location']), $item['item_id'], $item['item_location']);

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
//                  $this->db->insert('sales_items_taxes', array(
//                      'sale_id'   => $sale_id,
//                      'item_id'   => $item['item_id'],
//                      'line'      => $item['line'],
//                      'name'      => $row['name'],
//                      'percent'   => $row['percent']
//                  ));
                }
            }
        }

        //$this->db->trans_complete();

//      if($this->db->trans_status() === FALSE)
//      {
//          return -1;
//      }

        //SAVE STATUS INFO FLAG
        $redis->hset('sale:' . $redisId, 'status', 0);


        return $sale_id;
    }

    
    public function delete_list($sale_ids, $employee_id, $update_inventory = TRUE)
    {
        $result = TRUE;

        foreach ($sale_ids as $sale_id) {
            $result &= $this->delete_sale($sale_id, $employee_id, $update_inventory);
        }

        return $result;
    }

    public function delete_sale($sale_id, $employee_id, $update_inventory = true)
    { 
        $this->db = $this->gu->getReportingDb();
        // start a transaction to assure data integrity
        $this->db->transStart();
        $sales_payments = $this->db->table('sales_payments')->where('sale_id', $sale_id);
        // first delete all payments
        $sales_payments->delete();
        // then delete all taxes on items
        $sales_items_taxes = $this->db->table('sales_items_taxes')->where('sale_id' , $sale_id);

        $sales_items_taxes->delete();

        if ($update_inventory) {
            // defect, not all item deletions will be undone??
            // get array with all the items involved in the sale to update the inventory tracking
            $items = $this->get_sale_items($sale_id)->getResultArray();
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
                $this->Inventory->insertInventory($inv_data);
                // update quantities
                $this->Item_quantity->change_quantity($item['item_id'], $item['item_location'], $item['quantity_purchased']);
            }
        }

        // delete all items
        $sales_items = $this->db->table('sales_items');
        $sales_items->delete(array('sale_id' => $sale_id));
        // delete sale itself
        $builder = $this->db->table($this->table);
        $builder->delete(array('sale_id' => $sale_id));

        // execute transaction
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function get_sale_items($sale_id)
    {
        $sales_items = $this->db->table('sales_items');
        $sales_items->where('sale_id', $sale_id);
        return $sales_items->get();
    }

    public function get_sale_payments($sale_id)
    {
        $sales_payments = $this->db->table('sales_payments');
        // $this->db->from('sales_payments');
        $sales_payments->where('sale_id', $sale_id);

        return $sales_payments->get();
    }

    public function get_payment_options($giftcard = TRUE)
    {
        $payments = array();

        if ($this->appData->get('payment_options_order') == 'debitcreditcash') {
            //$payments[lang('sales_lang.sales_debit')] = lang('sales_lang.sales_debit');
            $payments[lang('sales_lang.sales_credit')] = lang('sales_lang.sales_credit');
            $payments[lang('sales_lang.sales_cash')] = lang('sales_lang.sales_cash');
        } elseif ($this->appData->get('payment_options_order') == 'debitcashcredit') {
            //$payments[lang('sales_lang.sales_debit')] = lang('sales_lang.sales_debit');
            $payments[lang('sales_lang.sales_cash')] = lang('sales_lang.sales_cash');
            $payments[lang('sales_lang.sales_credit')] = lang('sales_lang.sales_credit');
        } else // default: if($this->appData->get('payment_options_order') == 'cashdebitcredit')
        {
            $payments[lang('sales_lang.sales_cash')] = lang('sales_lang.sales_cash');
            //$payments[lang('sales_lang.sales_debit')] = lang('sales_lang.sales_debit');
            $payments[lang('sales_lang.sales_credit')] = lang('sales_lang.sales_credit');
        }

       // $payments[lang('sales_lang.sales_check')] = lang('sales_lang.sales_check');

        if ($giftcard) {
            $payments[lang('sales_lang.sales_giftcard')] = lang('sales_lang.sales_giftcard');
        }
        
        //$payments[lang('sales_lang.sales_cod')] = lang('sales_lang.sales_cod');

        return $payments;
    }

    public function get_customer($sale_id=null)
    {
        $builder = $this->db->table($this->table)
        ->where('sale_id', $sale_id);
        return $this->Customer->get_info($builder->get()->getRow()->customer_id);
    }

    public function invoice_number_exists($invoice_number, $sale_id = '')
    {
        // $this->db->from('sales');
        $builder = $this->db->table($this->table);
        $builder->where('invoice_number', $invoice_number);
        if (!empty($sale_id)) {
            $builder->where('sale_id !=', $sale_id);
        }

        return ($builder->get()->getNumRows() == 1);
    }

    public function get_giftcard_value($giftcardNumber)
    {
        if (!$this->Giftcard->exists($this->Giftcard->get_giftcard_id($giftcardNumber))) {
            return 0;
        }
         $giftcards = $this->db->table('giftcards');
        // $this->db->from('giftcards');
        $giftcards->where('giftcard_number', $giftcardNumber);

        return $giftcards->get()->getRow()->value;
    }

    //We create a temp table that allows us to do easy report/sales queries
    public function create_temp_table($db = null)
    {
        $this->gu->log('creating temp table ');
        if ($db) {
            $this->db = $db;
        }

        if ($this->appData->get('tax_included')) {
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


        /**
         * new
         */
        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_payments_temp') .
            ' (PRIMARY KEY(sale_id), INDEX(sale_id))
            (
                SELECT sale_id, 
                    IFNULL((SUM(payment_amount)), 0) AS sale_payment_amount,
                    GROUP_CONCAT(CONCAT(payment_type, " ", payment_amount) SEPARATOR ", ") AS payment_type,
                    GROUP_CONCAT(payment_amount) AS gift_payment_amount
                FROM ' . $this->db->prefixTable('sales_payments') . '
                GROUP BY sale_id
            )'
        );

        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('gift_payments_temp') .
            ' (PRIMARY KEY(sale_id), INDEX(sale_id))
            (
                SELECT sale_id, 
                    IFNULL(SUM(payment_amount), 0) AS sale_payment_amount,
                    GROUP_CONCAT(CONCAT(payment_type, " ", payment_amount) SEPARATOR ", ") AS payment_type,
                    GROUP_CONCAT(payment_amount) AS gift_payment_amount
                FROM ' . $this->db->prefixTable('sales_payments') . '
                WHERE payment_type LIKE "%Gift%"
                GROUP BY sale_id
            )'
        );
// "%'.$Gift.'%"

//        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_items_temp') .
//            ' (INDEX(sale_date), INDEX(sale_id))
//            (
//              SELECT
//                  DATE(sales.sale_time) AS sale_date,
//                  sales.sale_time,
//                  sales.sale_id,
//                  sales.sale_type AS sale_type,
//                  sales.branch_code,
//                  sales.exact_time,
//                  sales.comment,
//                  sales.invoice_number,
//                  sales.customer_id,
//                  CONCAT(customer_p.first_name, " ", customer_p.last_name) AS customer_name,
//                  customer_p.first_name AS customer_first_name,
//                  customer_p.last_name AS customer_last_name,
//                  customer_p.email AS customer_email,
//                  customer_p.comments AS customer_comments,
//                  customer.company_name AS customer_company_name,
//                  sales.employee_id,
//                  CONCAT(employee.first_name, " ", employee.last_name) AS employee_name,
//                  items.item_id,
//                  items.name,
//                  items.category,
//                  items.supplier_id,
//                  sales_items.quantity_purchased,
//                  sales_items.item_cost_price,
//                  sales_items.item_unit_price,
//                  sales_items.discount_percent,
//                  sales_items.line,
//                  sales_items.serialnumber,
//                  sales_items.item_location,
//                  sales_items.description,
//                  payments.payment_type,
//                  IFNULL(payments.sale_payment_amount, 0) AS sale_payment_amount,
//                  SUM(sales_items_taxes.percent) AS item_tax_percent,
//                  ' . "
//                  ROUND($sale_total * $total, $decimals) AS total,
//                  ROUND($sale_total * $tax, $decimals) AS tax,
//                  ROUND($sale_total * $subtotal, $decimals) AS subtotal,
//                  ROUND($sale_total - $sale_cost, $decimals) AS profit,
//                  ROUND($sale_cost, $decimals) AS cost
//                  " . '
//              FROM ' . $this->db->prefixTable('sales_items') . ' AS sales_items
//              INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
//                  ON sales_items.sale_id = sales.sale_id
//              INNER JOIN ' . $this->db->prefixTable('items') . ' AS items
//                  ON sales_items.item_id = items.item_id
//              LEFT OUTER JOIN (
//                              SELECT sale_id,
//                                  SUM(payment_amount) AS sale_payment_amount,
//                                  GROUP_CONCAT(CONCAT(payment_type, " ", payment_amount) SEPARATOR ", ") AS payment_type
//                              FROM ' . $this->db->prefixTable('sales_payments') . '
//                              GROUP BY sale_id
//                          ) AS payments
//                  ON sales_items.sale_id = payments.sale_id
//              LEFT OUTER JOIN ' . $this->db->prefixTable('suppliers') . ' AS supplier
//                  ON items.supplier_id = supplier.person_id
//              LEFT OUTER JOIN ' . $this->db->prefixTable('people') . ' AS customer_p
//                  ON sales.customer_id = customer_p.person_id
//              LEFT OUTER JOIN ' . $this->db->prefixTable('customers') . ' AS customer
//                  ON sales.customer_id = customer.person_id
//              LEFT OUTER JOIN ' . $this->db->prefixTable('people') . ' AS employee
//                  ON sales.employee_id = employee.person_id
//              LEFT OUTER JOIN ' . $this->db->prefixTable('sales_items_taxes') . ' AS sales_items_taxes
//                  ON sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.item_id = sales_items_taxes.item_id AND sales_items.line = sales_items_taxes.line
//              GROUP BY sales.sale_id, items.item_id, sales_items.line
//          )'
//        );

//        //Update null item_tax_percents to be 0 instead of null
//        $this->db->where('item_tax_percent IS NULL');
//        $this->db->update('sales_items_temp', array('item_tax_percent' => 0));
//
//        //Update null tax to be 0 instead of null
//        $this->db->where('tax IS NULL');
//        $this->db->update('sales_items_temp', array('tax' => 0));
//
//        //Update null subtotals to be equal to the total as these don't have tax
//        $this->db->query('UPDATE ' . $this->db->prefixTable('sales_items_temp') . ' SET total = subtotal WHERE total IS NULL');


        /**
         * NEW 24 jan
         */

        $this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->prefixTable('sales_items_temp') .
            '  (INDEX(sale_date), INDEX(sale_id))
            (
                SELECT
                    DATE(sales.sale_time) AS sale_date,
                    sales.sale_time,
                    sales.sale_id,
                    sales.sale_type AS sale_type,
                    sales.sale_payment,
                    sales.branch_code,
                    sales.exact_time,
                    sales.comment,
                    sales.invoice_number,
                    sales.fbr_fee,
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
                    payments.sale_payment_amount,
                    gpayments.gift_payment_amount,
                    IFNULL(SUM(sales_items_taxes.percent), 0) AS item_tax_percent,
                    ' . "
                    IFNULL(ROUND($sale_total * $total, $decimals), ROUND($sale_total * $subtotal, $decimals)) AS total,
                    IFNULL(ROUND($sale_total * $tax, $decimals), 0) AS tax,
                    ROUND($sale_total * $subtotal, $decimals) AS subtotal,
                    ROUND($sale_total - $sale_cost, $decimals) AS profit,
                    ROUND($sale_cost, $decimals) AS cost
                    " . '
                FROM ' . $this->db->prefixTable('sales_items') . ' AS sales_items
                INNER JOIN ' . $this->db->prefixTable('sales') . ' AS sales
                    ON sales_items.sale_id = sales.sale_id
                INNER JOIN ' . $this->db->prefixTable('items') . ' AS items
                    ON sales_items.item_id = items.item_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('sales_payments_temp') . ' AS payments
                    ON sales_items.sale_id = payments.sale_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('gift_payments_temp') . ' AS gpayments
                    ON sales_items.sale_id = gpayments.sale_id      
                LEFT OUTER JOIN ' . $this->db->prefixTable('suppliers') . ' AS supplier
                    ON items.supplier_id = supplier.person_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('people') . ' AS customer_p
                    ON sales.customer_id = customer_p.person_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('customers') . ' AS customer
                    ON sales.customer_id = customer.person_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('people') . ' AS employee
                    ON sales.employee_id = employee.person_id
                LEFT OUTER JOIN ' . $this->db->prefixTable('sales_items_taxes') . ' AS sales_items_taxes
                    ON sales_items.sale_id = sales_items_taxes.sale_id AND sales_items.item_id = sales_items_taxes.item_id AND sales_items.line = sales_items_taxes.line
                GROUP BY sales.sale_id, items.item_id, sales_items.line
            )'
        );


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
            $db->transStart();

            //TODO - 1ST SALES TABLE save

            if($db->database == 'tehzeeb_pos'){
                if($sales_data['cake_invoice']){
               $db->table('sales');
               $db->where(['cake_invoice'=> $sales_data['cake_invoice'], 'is_child' => '0']);
               $query = $db->get();
 
                if($query->getNumRows() > 0){
                 
                $result = $query->getRow();
                $sales_data['invoice_number_parent'] = $result->invoice_number;
                $sales_data['is_child'] = 1;
 
                 }
              }
              $db->table('sales')->insert($sales_data);
              $sale_id = $db->insertID();
 
              }else{
 
                $db->table('sales')->insert($sales_data);
                $sale_id = $db->insertID();
 
              }
            
            //TODO - db PAYMENTS SAVE get redis payments
            $payments = json_decode($sale['payments'], true);

            if(!$payments == null){
                $sale_payment_type = array_values($payments)[0]['payment_type'];
                $sale_payment_amount = array_values($payments)[0]['payment_amount'];
    
                // $sale_fbr_fee = array_values($payments)[1];
                foreach ($payments as $payment_id => $payment) {
    
                    if (substr($payment['payment_type'], 0, strlen(lang('sales_lang.sales_giftcard'))) == lang('sales_lang.sales_giftcard')) {
                        // We have a gift card and we have to deduct the used value from the total value of the card.
                        $splitpayment = explode(':', $payment['payment_type']);
                        //$cur_giftcard_value = $this->Giftcard->get_giftcard_value($splitpayment[1]);
                        //echo $this->Giftcard->update_giftcard_value($splitpayment[1]);
                        
                        $giftcard_number = strtoupper($splitpayment[1]);
                        $db->table('giftcards')->where('giftcard_number', $giftcard_number);
                        $db->update(array('status' => 1));
                    }
    
                    $sales_payments_data = array(
                        'sale_id' => $sale_id,
                        'payment_type' => $payment['payment_type'],
                        'payment_amount' => $payment['payment_amount']
                        //,'fbr_fee'=>$sale_fbr_fee
                    );
                    // return $sales_payments_data;
                    // exit;
                    //TODO - 2ND SALES_PAYMENTS TABLE
                    $db->table('sales_payments')->insert($sales_payments_data);
                }
    
            }

            //TODO - REDIS ITEMS GET
            $items = json_decode($sale['items'], true);
            foreach ($items as $line => $item) {

                if(is_null($item['item_id']) || $item['item_id'] == false){
                    continue;
                }

                $cur_item_info = $this->Item->get_info($item['item_id']);
                if($item['description']==null){
                    $item['description']='';
                }
                if($item['serialnumber']==null){
                    $item['serialnumber']='';
                }
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

                $db->table('sales_items')->insert($sales_items_data);
                // Update stock quantity
                $item_quantity = $this->Item_quantity->get_item_quantity($item['item_id'], $item['item_location']);
                 $this->Item_quantity->saveItemQuantities(array('quantity' => $item_quantity->quantity - $item['quantity'],
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
              $this->Inventory->insertInventory($inv_data);


                $customer = $this->Customer->get_info($sales_data['customer_id']);
                if ($sales_data['customer_id'] == -1 || $customer->taxable) {
                    foreach ($this->Item_taxes->get_info($item['item_id']) as $row) {

                       $db->table('sales_items_taxes')->insert(array(
                            'sale_id' => $sale_id,
                            'item_id' => $item['item_id'],
                            'line' => $item['line'],
                            'name' => $row['name'],
                            'percent' => $row['percent']
                        ));
                    }
                }
            }
            $db->transComplete();
            if ($db->transStatus() === false) {
                $db->transRollback();
                if ($this->gu->saleLog()) {
                    //TODO - TEMP SALE LOG

                    /**
                     * this for testing purpose
                     */
                    $host = $db->hostname;
                    if ($host != "localhost") {
                        $host = "server";

                        $log = date('d M Y h:i A') . " DB: " . $host . " Sale ID: "
                            . $bill_number . " Total: " . $sale_payment_amount
                            . " Payment Type: " . $sale_payment_type . " FAILED" . PHP_EOL;


                        file_put_contents(APPPATH . "logs/sales_fail.log", $log, FILE_APPEND);
                    }
                    /////////////------------------------------------------------------
                }
                return false;
            } else {
                //transaction success\
                
                if ($this->gu->saleLog()) {
                    //TODO - TEMP SALE LOG

                    /**
                     * this for testing purpose
                     */
                    $host = $db->hostname;

                    if ($host != "localhost") {
                        $host = "server";

                        $log = date('d M Y h:i A') . " DB: " . $host . " Sale ID: "
                            . $bill_number . " Total: " . $sale_payment_amount
                            . " Payment Type: " . $sale_payment_type;


                        $file = $this->gu->getStoreDate() ."_sales_after.log";

                        $this->gu->logInFile($file, $log);

                        //file_put_contents(APPPATH . "logs/sales_after.log", $log, FILE_APPEND);
                    }

                // return true;
                    /////////////------------------------------------------------------
                }
                return true;

            }

            //echo "<br/><code> Sale added ID: " . $sale_id . "</code>";
        } catch (\Exception $e) {
            $db->transRollback();
            echo " db up process failed. <br/>";

            if ($this->gu->saleLog()) {
                //TODO - TEMP SALE LOG

                /**
                 * this for testing purpose
                 */
                $host = $db->hostname;
                if ($host != "localhost") {
                    $host = "server";

                    $log = date('d M Y h:i A') . " DB: " . $host . " Sale ID: "
                        . $bill_number . " Total: " . $sale_payment_amount
                        . " Payment Type: " . $sale_payment_type . " FAILED2 "
                        .$e->getMessage() . PHP_EOL;


                    file_put_contents(APPPATH . "logs/sales_fail.log", $log, FILE_APPEND);
                }


                /////////////------------------------------------------------------
            }


            return false;
        }

    }

    /*
    Get number of rows for deleted sales
    */
    public function get_found_deleted_logs_rows($search, $filters)
    {
        return $this->search_deleted_logs($search, $filters)->getNumRows();
    }

    /*
    Perform a search on deleted sales
    */
    public function search_deleted_logs($search, $filters, $rows = 0, $limit_from = 0, $sort = 'items.name', $order = 'asc')
    {
        // $this->db->from('sales_items_deleted_log as sidl');
        $sidl = $this->db->table('sales_items_deleted_log as sidl');
        $sidl->join('items', 'items.item_id = sidl.item_id');
        $sidl->join('employees', 'employees.person_id = sidl.employee_id');

        $sidl->where('DATE_FORMAT(deleted_time, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));

        if (!empty($search)) {
            $sidl->groupStart();
            // employee username
            $sidl->like('employees.username', $search);
            // item name
            $sidl->orLike('items.name', $search);

            $sidl->groupEnd();
        }
        // order by name of item
        $sidl->orderBy($sort, $order);

        if($rows > 0) 
        {   
            $sidl->limit($rows, $limit_from);
        }

        return $sidl->get();
    }
    
    public function online_db_search_sale($cake_invoice){

        $online = Database::connect('online', true);
        $online->initialize();
  
        $search = $online->table('sales')->where('cake_invoice',$cake_invoice)->get();
      
        return $search;
      }
  
      public function get_sale_items_info($sale_id){
  
        $online = Database::connect('online', true);
        $online->initialize();

         $data = $online->table('sales_items')
                 ->where('sale_id', $sale_id)
                 ->join('items', 'sales_items.item_id = items.item_id', 'LEFT')->get();
  
          return $data;
      }

}

?>
