<?php
namespace App\Models;
use CodeIgniter\Model;

class Pizza_completed_order extends Model
{

    public function get_all()
    {
       $builder = $this->db->table('order_completed')
                       ->orderBy('order_completed.sale_id');

        return $builder->get();
    }

    public function get_last_sale()
    {
        $suspended_sale = $this->db->query("SELECT * FROM ospos_cake_suspended ORDER BY sale_id DESC LIMIT 1")->row_array();

        return $suspended_sale;
    }

    public function get_info($sale_id)
    {
       $builder = $this->db->table('order_completed')
                       ->where('sale_id', $sale_id)
                       ->join('people', 'people.person_id = order_completed.customer_id', 'LEFT');

        return $builder->get();
    }

        public function get_row($order_id)
    {

        $this->db->from('order_completed');
        $this->db->where('order_id', $order_id);
        return $this->db->get();
    }

    /*
    Gets total of invocie rows
    */
    public function get_invoice_count()
    {
        $this->db->from('cake_suspended');
        $this->db->where('invoice_number IS NOT NULL');

        return $this->db->count_all_results();
    }

    public function get_sale_by_invoice_number($invoice_number)
    {
        $this->db->from('cake_suspended');
        $this->db->where('invoice_number', $invoice_number);

        return $this->db->get();
    }

    public function exists($sale_id)
    {

        $this->db->from('order_completed');
        $this->db->where('sale_id', $sale_id);
        $this->db->or_where('order_id', $sale_id);

        return ($this->db->get()->num_rows() == 1);
    }

    public function update_pizza_completed_order($sale_data, $sale_id)
    {
        $builder = $this->db->table('cake_suspended')
                        ->where('sale_id', $sale_id);

        return $builder->update($sale_data);
    }

    public function save_pizza_completed_order($items, $customer_id, $employee_id, $comment, $invoice_number, $payments, $sale_id = FALSE)
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
        $this->db->trans_start();

        $this->db->insert('cake_suspended', $sales_data);
        $sale_id = $this->db->insert_id();

        foreach ($payments as $payment_id => $payment) {
            $sales_payments_data = array(
                'sale_id' => $sale_id,
                'payment_type' => $payment['payment_type'],
                'payment_amount' => $payment['payment_amount']
            );

            $this->db->insert('cake_suspended_payments', $sales_payments_data);
        }

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

            $this->db->insert('cake_suspended_items', $sales_items_data);

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

                    $this->db->insert('cake_suspended_items_taxes', $sales_items_taxes);
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return -1;
        }

        return $sale_id;
    }


      // Through cakeapp 
       public function delete_pizza_completed_order($sale_id)
    {
        $appConfig = new Appconfig();
        $pizza_app_ip = $appConfig->get('pizza_app_ip');
        $apiUrl = 'http://'.$pizza_app_ip.'/api/delete_pizza_completed_order/'.$sale_id;

         $response = file_get_contents($apiUrl);
         $data = json_decode($response, true);
         
         if($response === false){
              // Handle error
              echo "Connection failed.";
              return false;
          }

         // Check for errors
         if (isset($data['error'])){
          // Handle error
          echo "There occurs an error in pos pizza server!";
          return false;

         } else { 

           
         //Run these queries as a transaction, we want to make sure we do all or nothing
         $this->db->transStart();

         $this->db->table('order_completed')->delete(['sale_id' => $sale_id]);
         $this->db->table('order_completed_items')->delete(['sale_id' => $sale_id]);
         
         $this->db->transComplete();
         
         return true;

          }
        
    }

    // thrrough online database
    // public function delete($sale_id)
    // {
    //     $online = $this->load->database('online', TRUE);
    //     $online->trans_start();

    //     $online->delete('cake_suspended_payments', array('sale_id' => $sale_id));
    //     $online->delete('cake_suspended_items_taxes', array('sale_id' => $sale_id));
    //     $online->delete('cake_suspended_items', array('sale_id' => $sale_id));
    //     $online->delete('cake_suspended', array('sale_id' => $sale_id));

    //     $online->trans_complete();
    //     $response = $online->trans_status();
    //     //Run these queries as a transaction, we want to make sure we do all or nothing
    //     $this->db->trans_start();

    //     $this->db->delete('cake_suspended_payments', array('sale_id' => $sale_id));
    //     $this->db->delete('cake_suspended_items_taxes', array('sale_id' => $sale_id));
    //     $this->db->delete('cake_suspended_items', array('sale_id' => $sale_id));
    //     $this->db->delete('cake_suspended', array('sale_id' => $sale_id));

    //     $this->db->trans_complete();

    //     if($response){

    //     return $this->db->trans_status();

    //     }

    //     return $response;

    // }

    public function get_sale_items($sale_id)
    {
       $builder = $this->db->table('order_completed_items')
                       ->where('sale_id', $sale_id);

        return $builder->get();
    }

    public function get_sale_items_info($sale_id)
    {
     $builder = $this->db->table('order_completed_items')
                         ->where('sale_id', $sale_id);
        // $this->db->join('items', 'order_completed_items.item_id = items.item_id', 'LEFT');

        return $builder->get();
    }

    public function get_sale_payments($sale_id)
    {
       $builder = $this->db->table('cake_suspended_payments')
                            ->where('sale_id', $sale_id);

        return $builder->get();
    }

    public function invoice_number_exists($invoice_number, $sale_id = '')
    {
        $this->db->from('cake_suspended');
        $this->db->where('invoice_number', $invoice_number);
        if (!empty($sale_id)) {
            $this->db->where('sale_id !=', $sale_id);
        }

        return ($this->db->get()->num_rows() == 1);
    }

    public function get_comment($sale_id)
    {
        $this->db->from('cake_suspended');
        $this->db->where('sale_id', $sale_id);

        return $this->db->get()->row()->comment;
    }

    public function search($search){

      $builder = $this->db->table('order_completed')
                      ->like('pizza_invoice', $search);

        return $builder->get();
    }

    public function get_cake_invoice($sale_id)
    {
        $this->db->from('cake_suspended');
        $this->db->where('sale_id', $sale_id);

        return $this->db->get()->row();
    }

    public function get_cart_items($sale_id){

        $this->db->from('cake_suspended_items');
        $this->db->join('items', 'cake_suspended_items.item_id = items.item_id');
        // $this->db->select('items.name','cake_suspended_items.*');
        $this->db->where('sale_id', $sale_id);

       return  $this->db->get()->result();

    }

    public function update_order_status($cake_invoice){

        $cake_app_ip = $this->config->item('cake_app_ip');
        $apiUrl = 'http://'.$cake_app_ip.'/api/updateOrderStatus/'.$cake_invoice;

        $response = file_get_contents($apiUrl);

        $data = json_decode($response, true);
        
        if($response === false){
             // Handle error
             echo "Connection failed.";
             return false;
         }
         if (isset($data['error'])){
            // Handle error
            echo $data['error'] .' '. "There occurs an error in server!";
            return false;
  
           }

    }
}

?>
