<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;
use App\Models\Customer;
class Sale_suspended extends Model
{

    protected $table = 'ospos_sales_suspended';
    protected $Customer;
    protected $Item;
    protected $Item_taxes;
    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
        $this->Item = new Item();
        $this->Customer = new Customer();
        $this->Item_taxes = new Item_taxes();
    }
    public function get_all()
    {
        $builder =$this->db->table($this->table)
                        //    ->from('sales_suspended')
                           ->orderBy('sale_id');

        return $builder->get();
    }

    public function get_last_sale()
    {
        $suspended_sale = $this->db->table($this->table)
        ->orderBy('sale_id', 'desc')
        ->limit(1)
        ->get()
        ->getRowArray();
        return $suspended_sale;
    }

    public function get_info($sale_id)
    {
        $builder = $this->db->table($this->table)
                            // ->from('sales_suspended')
                            ->where('sale_id', $sale_id)
                            ->join('people', 'people.person_id = sales_suspended.customer_id', 'LEFT');

        return $builder->get();
    }

    /*
    Gets total of invocie rows
    */
    public function get_invoice_count()
    { 
        $builder = $this->db->table($this->table)
                            ->where('invoice_number IS NOT NULL');

        return $builder->countAllResults();
    }

    public function get_sale_by_invoice_number($invoice_number)
    {
        $builder = $this->db->table($this->table)
                            ->where('invoice_number', $invoice_number);

        return $builder->get();
    }

    public function exists($sale_id)
    {
        $builder = $this->db->table($this->table)
                            ->where('sale_id', $sale_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function updateSaleSuspended($sale_data, $sale_id)
    {
        $builder = $this->db->table($this->table)
                           ->where('sale_id', $sale_id);

        return $builder->update($sale_data);
    }

    public function saveSaleSuspended($items, $customer_id, $employee_id, $comment, $invoice_number, $payments, $sale_id = FALSE)
    {
        if (count($items) == 0) {
            return -1;
        }

        $sales_data = array(
            'sale_time' => date('Y-m-d H:i:s'),
            'customer_id' => $this->Customer->exists($customer_id) ? $customer_id : null,
            'employee_id' => $employee_id,
            'comment' => $comment,
            'invoice_number' => $invoice_number
        );

        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();
        $builder = $this->db->table($this->table)
                            ->insert($sales_data);
        $sale_id = $this->db->insertID();

        foreach ($payments as $payment_id => $payment) {
            $sales_payments_data = array(
                'sale_id' => $sale_id,
                'payment_type' => $payment['payment_type'],
                'payment_amount' => $payment['payment_amount']
            );
        $salesSuspendedPayments = $this->db->table('ospos_sales_suspended_payments')
                                           ->insert($sales_payments_data);
        }

        foreach ($items as $line => $item) {
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
                'description' => character_limiter($item['serialnumber'], 30),
                'serialnumber' => character_limiter($item['serialnumber'], 30),
                'quantity_purchased' => $item['quantity'],
                'discount_percent' => $item['discount'],
                'item_cost_price' => $cur_item_info->cost_price,
                'item_unit_price' => $item['price'],
                'item_location' => $item['item_location']
            );
             $this->db->table('ospos_sales_suspended_items')
                                             ->insert($sales_items_data);

            $customer = $this->Customer->get_info($customer_id);
            if ($customer_id == -1 || $customer->taxable) {
                foreach ($this->Item_taxes->get_info($item['item_id']) as $row) {
                    $sales_items_taxes = array(
                        'sale_id' => $sale_id,
                        'item_id' => $item['item_id'],
                        'line' => $item['line'],
                        'name' => $row['name'],
                        'percent' => $row['percent']
                    );
                 $this->db->table('ospos_sales_suspended_items_taxes')
                                                ->insert($sales_items_taxes);
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return -1;
        }
        return $sale_id;
    }

    public function deleteSaleSuspended($sale_id)
    {
        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();
        $builder = $this->db->table($this->table);
        $salesSuspendedPayments = $this->db->table('ospos_sales_suspended_payments')
                                           ->delete(array('sale_id' => $sale_id));
        $salesSuspendedItemsTaxes = $this->db->table('ospos_sales_suspended_items_taxes')
                                             ->delete(array('sale_id' => $sale_id));
        $salesSuspendedItems = $this->db->table('ospos_sales_suspended_items')
                                        ->delete(array('sale_id' => $sale_id));
        $builder->delete(array('sale_id' => $sale_id));

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function get_sale_items($sale_id)
    {
        $salesSuspendedItems = $this->db->table('ospos_sales_suspended_items')
                                        ->where('sale_id', $sale_id);

        return $salesSuspendedItems->get();
    }

    public function get_sale_items_info($sale_id)
    {
        $salesSuspendedItems = $this->db->table('ospos_sales_suspended_items')
                                        ->where('sale_id', $sale_id)
                                        ->join('items', 'sales_suspended_items.item_id = items.item_id', 'LEFT');

        return $salesSuspendedItems->get();
    }

    public function get_sale_payments($sale_id)
    {
        $salesSuspendedPayments = $this->db->table('ospos_sales_suspended_payments')
                                           ->where('sale_id', $sale_id);

        return $salesSuspendedPayments->get();
    }

    public function invoice_number_exists($invoice_number, $sale_id = '')
    {
        $builder = $this->db->table($this->table)
                            ->from('sales_suspended')
                            ->where('invoice_number', $invoice_number);
        if (!empty($sale_id)) {
            $builder->where('sale_id !=', $sale_id);
        }

        return ($builder->get()->getNumRows() == 1);
    }

    public function get_comment($sale_id)
    {
        $builder = $this->db->table($this->table)
                            ->where('sale_id', $sale_id);

        return $builder->get()->getRow()->comment;
    }
}

?>
