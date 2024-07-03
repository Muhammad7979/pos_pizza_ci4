<?php
namespace App\Libraries;

use App\Models\Cake_suspended;
use App\Models\Item;
use App\Models\Item_quantity;
use App\Models\Item_taxes;
use App\Models\Pizza_completed_order;
use App\Models\Stock_location;
use App\Models\Sale;
use Config\Services;
// use CodeIgniter\Session\Session;

class SaleLib
{
   protected $Item;
   protected $config;
   protected $stockLocation;
   protected $sale;
   protected $Item_taxes;
   protected $Item_quantity;
    public function __construct()
    {
        $this->config = new \App\Models\Appconfig();
        $this->stockLocation = new Stock_location();
        $this->sale = new Sale();
        $this->Item_taxes = new Item_taxes();
        $this->Item_quantity = new Item_quantity();
        $this->Item = new Item();
    }

    public function get_cart()
    {
        if (!session()->has('sales_cart')) {
            $this->set_cart([]);
        }

        return session()->get('sales_cart');
    }

    public function set_cart($cartData)
    {
        session()->set('sales_cart', $cartData);

    }

    public function empty_cart()
    {
        session()->remove('sales_cart');
    }

    public function save_in_session($var, $data)
    {
        session()->set($var, $data);
    }

    public function get_from_session($var)
    {
        $data = session()->get($var);

        return empty($data) ? '' : $data;
    }

    public function get_comment()
    {
        // avoid returning a NULL that results in a 0 in the comment if nothing is set/available
        $comment = session()->get('sales_comment');

        return empty($comment) ? '' : $comment;
    }


public function set_comment($comment)
{
    session()->set('sales_comment', $comment);
}

public function clear_comment()
{
    session()->remove('sales_comment');
}

public function get_invoice_number()
{
    return session()->get('sales_invoice_number');
}

public function set_invoice_number($invoice_number, $keep_custom = false)
{
    $current_invoice_number = session()->get('sales_invoice_number');
    if (!$keep_custom || empty($current_invoice_number)) {
        session()->set('sales_invoice_number', $invoice_number);
    }
}

public function clear_invoice_number()
{
    session()->remove('sales_invoice_number');
}

public function is_invoice_number_enabled()
{
    return (session()->get('sales_invoice_number_enabled') === 'true' ||
            session()->get('sales_invoice_number_enabled') === '1') &&
        config('App')->invoiceEnable === true;
}

public function set_invoice_number_enabled($invoice_number_enabled)
{
    return session()->set('sales_invoice_number_enabled', $invoice_number_enabled);
}

public function is_print_after_sale()
{
    return (session()->get('sales_print_after_sale') === 'true' ||
        session()->get('sales_print_after_sale') === '1');
}

public function set_print_after_sale($print_after_sale)
{
    return session()->set('sales_print_after_sale', $print_after_sale);
}

public function get_email_receipt()
{
    return session()->get('sales_email_receipt');
}

public function set_email_receipt($email_receipt)
{
    session()->set('sales_email_receipt', $email_receipt);
}

public function get_fbr_invoice_number()
{
    return session()->get('fbr_invoice_number');
}

public function set_fbr_invoice_number($invoice_number)
{
    session()->set('fbr_invoice_number', $invoice_number);
}

public function clear_email_receipt()
{
    session()->remove('sales_email_receipt');
}
    // Multiple Payments
    public function get_payments()
    {
        if (!session()->has('sales_payments')) {
            $this->set_payments([]);
        }

        return session()->get('sales_payments');
    }

    // Multiple Payments
    public function set_payments($payments_data)
    {
        session()->set('sales_payments', $payments_data);
    }

    // Multiple Payments
    public function add_payment($payment_id, $payment_amount)
    {
        $payments = $this->get_payments();
        $payment_amount = str_replace(',', '', $payment_amount);
        if (isset($payments[$payment_id])) {
            // payment_method already exists, add to payment_amount
            $payments[$payment_id]['payment_amount'] = bcadd($payments[$payment_id]['payment_amount'], $payment_amount);
        } else {
            // add to existing array
            $payment = [
                $payment_id => [
                    'payment_type' => $payment_id,
                    'payment_amount' => $payment_amount
                ]
            ];

            $payments += $payment;
        }

        $this->set_payments($payments);

        return true;
    }

    // Multiple Payments
    public function edit_payment($payment_id, $payment_amount)
    {
        $payments = $this->get_payments();
        if (isset($payments[$payment_id])) {
            $payments[$payment_id]['payment_type'] = $payment_id;
            $payments[$payment_id]['payment_amount'] = $payment_amount;
            $this->set_payments($payments);

            return true;
        }

        return false;
    }

    // Multiple Payments
    public function delete_payment($payment_id)
    {
        $payments = $this->get_payments();
        unset($payments[urldecode($payment_id)]);
        $this->set_payments($payments);
    }

    // Multiple Payments
    public function empty_payments()
    {
        session()->remove('sales_payments');
    }

    // Multiple Payments
    public function get_payments_total()
    {
        $subtotal = 0;
        foreach ($this->get_payments() as $payment) {
            $subtotal = bcadd($payment['payment_amount'], $subtotal);
        }

        return $subtotal;
    }
      // Multiple Payments
      public function get_amount_due($fbr_fee = null, $str = null)
      {
          $payment_total = $this->get_payments_total();
          if ($fbr_fee && $str == "reciept") {
              $sales_total = $this->get_total($fbr_fee, "reciept");
          } elseif ($str == "reciept") {
              $sales_total = $this->get_total(null, "reciept");
          } else {
              $sales_total = $this->get_total();
          }
  
          $amount_due = bcsub($sales_total, $payment_total);
          $precision =$this->config->get('currency_decimals');
          $rounded_due = bccomp(round($amount_due, $precision, PHP_ROUND_HALF_EVEN), 0, $precision);

  
          // take care of rounding error introduced by round tripping payment amount to the browser
          return $rounded_due == 0 ? 0 : $amount_due;
      }
  
      public function get_customer()
      {
          if (!session()->has('sales_customer')) {
        
              $this->set_customer(-1);
          }
  
          return session()->get('sales_customer');
      }
  
      public function set_customer($customer_id)
      {
          session()->set('sales_customer', $customer_id);
      }
  
      public function remove_customer()
      {
          session()->remove('sales_customer');
      }
  
      /**
       * Sale Mode "Sales, Returns"
       *
       * @return mixed
       */
      public function get_mode()
      {
          if (!session()->has('sales_mode')) {
              $this->set_mode('sale');
          }
  
          return session()->get('sales_mode');
      }
  
      public function set_mode($mode)
      {
          session()->set('sales_mode', $mode);
      }
  
      public function clear_mode()
      {
          session()->remove('sales_mode');
      }


    /**
     * Sale Type: "Normal, Breakfast, Complementary, Burger, Cash Counter, Employee"
     *
     * @return mixed
     */
   
     public function get_type()
     {
         if (!session()->has('sales_type')) {
             $this->set_mode('normal');
         }
 
         return session()->get('sales_type');
     }
 
     public function set_type($type)
     {
         $items = $this->get_cart();
         if ($type != 'normal') {
             $multiple = -1;
         } else {
             $multiple = 1;
         }
         foreach ($items as $key => $item) {
            
            if ($item['quantity'] > 0) {
                $item['quantity'] = $item['quantity'] * $multiple;
            }
            else{
                if ($type == "normal"){
                    $item['quantity'] = $item['quantity'] * $multiple;
                }
            }
            $items[$key] = $item;
        }
         $this->set_cart($items);
         session()->set('sales_type', $type);
     }
 
     public function clear_type()
     {
         session()->remove('sales_type');
     }
 
     /**
      * Payment Type: "Cash, Credit etc"
      *
      * @return mixed
      */
     public function get_payment_type()
     {
         if (!session()->has('payment_type')) {
             $this->set_payment_type('cash');
         }
 
         return session()->get('payment_type');
     }
 
     public function set_payment_type($type)
     {
         session()->set('payment_type', $type);
     }
 
     public function clear_payment_type()
     {
         session()->remove('payment_type');
     }
 
     public function get_sale_location()
     {
         if (!session()->has('sales_location')) {

             $this->set_sale_location($this->stockLocation->get_default_location_id());
         }
         return session()->get('sales_location');
     }
 
     public function set_sale_location($location)
     {
         session()->set('sales_location', $location);
     }
 
     public function clear_sale_location()
     {
         session()->remove('sales_location');
     }
     public function set_giftcard_remainder($value)
{
    session()->set('sales_giftcard_remainder', $value);
}

public function get_giftcard_remainder()
{
    return session('sales_giftcard_remainder');
}

public function clear_giftcard_remainder()
{
    session()->remove('sales_giftcard_remainder');
}

public function add_item($item_id, $item_location, $quantity = 1, $discount = 0, $price = null, $description = null, $serialnumber = null, $mode = null, $oldSale = false, $pizza_item_name = null)
{
    // Get the Item model
    $itemModel = new Item();
    // Check if item id exists
    if (!$this->validate_item($item_id)) {
        // Special case for barcode with price
        $price_tmp = substr($item_id, -1) . $quantity;
        $item_id_tmp = substr($item_id, 0, 4);
        
        if (isset($price_tmp[4])) {
            $price_tmp[3] = $price_tmp[4];
            $price_tmp[4] = '.';
        }
        
        if ($this->validate_item($item_id_tmp)) {
            $item_info_tmp = $itemModel->get_info($item_id_tmp, $item_location);
            
            if ($item_info_tmp->custom2 == 'price') {
                $item_info = $item_info_tmp;
                $item_id = $item_id_tmp;
                $price = $price_tmp;
            }
        } else {
            return false;
        }
    }
    
    $item_info = $itemModel->get_info($item_id, $item_location);
    // Serialization and Description
    
    // Get all items in the cart so far
    $items = $this->get_cart();
    // Loop through all items in the cart
    $maxkey = 0;
    $itemalreadyinsale = false;
    $insertkey = 0;
    $updatekey = 0;
    
    // Barcode check
    $parity = false;

    if ($item_info->custom2 == '' || $item_info->custom2 == 'quantity') {
        $type = 'quantity';
        
        if ($quantity < 1 && $oldSale == false) {
            $quantity = (int)str_replace('.', '', $quantity);
        }

        if ($mode == 'return') {
            $quantity = -1;
        }

    } elseif ($item_info->custom2 == 'price') {
        $type = 'price';
        
        if ($mode == 'return') {
            $quantity = -1;
        } else {
            $quantity = 1;
        }
    } else {
        $type = 'scale';
    }
    foreach ($items as $item) {
        if ($maxkey <= $item['line']) {
            $maxkey = $item['line'];
        }
        
        if ($item['item_id'] == $item_id && $item['item_location'] == $item_location) {
            $itemalreadyinsale = true;
            $updatekey = $item['line'];
            
            if (!$item_info->is_serialized) {
                $quantity = bcadd($quantity, $items[$updatekey]['quantity']);
            }
        }
    }
    
    $insertkey = $maxkey + 1;
    $price = $price !== null ? $price : $item_info->unit_price;
    $total = $this->get_item_total($quantity, $price, $discount);
    $discounted_total = $this->get_item_total($quantity, $price, $discount, true);
    
    if (!$itemalreadyinsale || $item_info->is_serialized) {
        if ($item_info->cost_price != ($price - ($price * (100 / (100 + $item_info->percent))))) {
            $cost_price = $price - ($price - ($price * (100 / (100 + $item_info->percent))));
        }
        $pizza_item_name ? $name = $pizza_item_name : $name = $item_info->name;

        $item = [
            $insertkey => [
                'item_id' => $item_id,
                'item_tax_percent' => $item_info->percent,
                'item_location' => $item_location,
                'stock_name' => $this->stockLocation->get_location_name($item_location),
                'line' => $insertkey,
                'name' => $name,
                'item_number' => $item_info->item_number,
                'description' => $description !== null ? $description : $item_info->description,
                'serialnumber' => $serialnumber !== null ? $serialnumber : '',
                'allow_alt_description' => $item_info->allow_alt_description,
                'is_serialized' => $item_info->is_serialized,
                'quantity' => $quantity,
                'discount' => $discount,
                'in_stock' => $this->Item_quantity->get_item_quantity($item_id, $item_location)->quantity,
                'price' => $price,
                'cost_price' => $cost_price,
                'total' => $total,
                'discounted_total' => $discounted_total,
            ],
        ];
        
        // Add to existing array
        $items = array_merge($items, $item);
    } else {
        $line = &$items[$updatekey];
        $line['quantity'] = $quantity;
        $line['total'] = $total;
        $line['discounted_total'] = $discounted_total;
    }
    
    $this->set_cart($items);
    
    return true;
}
public function out_of_stock($item_id, $item_location)
{
    // Make sure item exists
    if (!$this->validate_item($item_id)) {
        return false;
    }
    
    $itemModel = new \App\Models\Item();
    $item_info = $itemModel->get_info($item_id);
    $item_quantity = $this->Item_quantity->get_item_quantity($item_id, $item_location)->quantity;
    $quantity_added = $this->get_quantity_already_added($item_id, $item_location);
    
    if ($item_quantity - $quantity_added < 0) {
        return lang('sales_lang.sales_quantity_less_than_zero');
    } elseif ($item_quantity - $quantity_added < $item_info->reorder_level) {
        return lang('sales_lang.sales_quantity_less_than_reorder_level');
    }
    
    return false;
}

public function get_quantity_already_added($item_id, $item_location)
{
    $items = $this->get_cart();
    $quantity_already_added = 0;
    
    foreach ($items as $item) {
        if ($item['item_id'] == $item_id && $item['item_location'] == $item_location) {
            $quantity_already_added += $item['quantity'];
        }
    }
    
    return $quantity_already_added;
}

public function get_item_id($line_to_get)
{
    $items = $this->get_cart();
    
    foreach ($items as $line => $item) {
        if ($line == $line_to_get) {
            return $item['item_id'];
        }
    }
    
    return -1;
}

public function edit_item($line, $description, $serialnumber, $quantity, $discount, $price)
{
    $items = $this->get_cart();
    if (isset($items[$line])) {
        $line = &$items[$line];
        $line['description'] = $description;
        $line['serialnumber'] = $serialnumber;
        $line['quantity'] = $quantity;
        
        $itemModel = new Item();
        $item = $itemModel->get_info($line['item_id']);
        
        $item_discount = $discount;
        if ($item && $item->custom1 == "no") {
            $item_discount = 0;
        }
        $line['discount'] = $item_discount;
        $line['price'] = $price;
        
        if ($line['price'] != $item->unit_price) {
            $temp = $price - ($price * (100 / (100 + $item->percent)));
            $line['cost_price'] = $price - $temp;
        }
        
        $line['total'] = $this->get_item_total($quantity, $price, $item_discount);
        $line['discounted_total'] = $this->get_item_total($quantity, $price, $item_discount, true);
        
        $this->set_cart($items);
        
    }
    
    $items = $this->get_cart();
    
    return false;
}

public function apply_discount($discount)
{
    $items = $this->get_cart();
    foreach ($items as &$line) {
        $itemModel = new Item();
        $item = $itemModel->get_info($line['item_id']);
        
        $item_discount = $discount;

        if ($item && $item->custom1 == "no") {
            $item_discount = 0;
        }
        $line['discount'] = $item_discount;
        $line['total'] = $this->get_item_total($line['quantity'], $line['price'], $item_discount);
        $line['discounted_total'] = $this->get_item_total($line['quantity'], $line['price'], $item_discount, true);
    }

    $this->set_cart($items);
}

    /**
     * GU FUNC: update sale type
     *
     */
    public function update_sale_type()
    {
        // $items = $this->get_cart();
        // for($i=1; $i<=count($items); $i++)
        // {
        //     if (isset($items[$i])) {
        //         $line = &$items[$i];
        //         $this->CI->load->model('item');
        //         $item = $this->CI->item->get_info($line['item_id']);
        //         $item_discount = $discount;
        //         if($item && $item->custom1 == "no"){
        //             $item_discount = 0;
        //         }
        //         $line['discount'] = $item_discount;
        //         $line['total'] = $this->get_item_total($line['quantity'], $line['price'], $discount);
        //         $line['discounted_total'] = $this->get_item_total($line['quantity'], $line['price'], $discount, true);
        //         $this->set_cart($items);
        //     }
        // }
    }
    
    public function delete_item($line)
    {
        $items = $this->get_cart();
        unset($items[$line]);
        $this->set_cart($items);
    }
    
    // public function is_valid_receipt(&$receipt_sale_id)
    // {
    //     $pieces = explode(' ', $receipt_sale_id);
    
    //     if (count($pieces) == 2 && strtolower($pieces[0]) == 'pos') {
    //         $sale = new Sale();
    //         return $sale->exists($pieces[1]);
    //     } elseif ($this->config->get('invoice_enable') == true) {
    //         $sale = new Sale();
    //         $sale_info = $sale->get_sale_by_invoice_number($receipt_sale_id);
    //         if ($sale_info && $sale_info->getNumRows() > 0) {
    //             $receipt_sale_id = 'POS ' . $sale_info->getRow()->sale_id;
    //             return true;
    //         }
    //     }
    
    //     return false;
    // }

    public function is_valid_receipt(&$receipt_sale_id)
    {
        //POS #
        $pieces = explode(' ', $receipt_sale_id);
        if (count($pieces) == 2 && strtolower($pieces[0]) == 'pos') {
            $sale = new Sale();
            return $sale->exists($pieces[1]);
        } elseif ($this->config->get('invoice_enable') == TRUE) {
            $sale = new Sale();
            $sale_info = $sale->get_sale_by_invoice_number($receipt_sale_id);
            if ($sale_info->getNumRows() > 0) {
                $receipt_sale_id = 'POS ' . $sale_info->getRow()->sale_id;

                return TRUE;
            }
        }
        return FALSE;
    }

    public function is_valid_item_kit($item_kit_id)
    {
        $pieces = explode(' ', $item_kit_id);
    
        if (count($pieces) == 2) {
            $itemKitModel = new \App\Models\Item_kit();
            return $itemKitModel->exists($pieces[1]);
        }
    
        return false;
    }
    
    public function return_entire_sale($receipt_sale_id)
    {
        $pieces = explode(' ', $receipt_sale_id);
        $sale_id = $pieces[1];
    
        $this->empty_cart();
        $this->remove_customer();
    
        $sale = new Sale();
        $saleItems = $sale->get_sale_items($sale_id)->getResultArray();
        foreach ($saleItems as $row) {
            $this->add_item($row->item_id, -$row->quantity_purchased, $row->item_location, $row->discount_percent, $row->item_unit_price, $row->description, $row->serialnumber, true);
        }
    
        $this->set_customer($sale->get_customer($sale_id)->person_id);
    }
    
    public function add_item_kit($external_item_kit_id, $item_location)
    {
        $pieces = explode(' ', $external_item_kit_id);
        $item_kit_id = $pieces[1];
    
        $itemKitItemsModel = new \App\Models\item_kit_items();
        $itemKitItems = $itemKitItemsModel->get_info($item_kit_id);
    
        foreach ($itemKitItems as $item_kit_item) {
            $this->add_item($item_kit_item['item_id'], $item_kit_item['quantity'], $item_location);
        }
    }

    public function add_item_pizza($external_item_kit_id, $item_location)
    {
        $item_kit_id = $this->CI->Pizza_order->get_info_by_order_number($external_item_kit_id);
        $item_kit_id = $item_kit_id->order_id;
        $this->CI->Pizza_order->update_read_status($item_kit_id);
    
        $pizzaOrderModel = new \App\Models\PizzaOrderModel();
        $pizzaItems = $pizzaOrderModel->get_pizza_item_info($item_kit_id);
    
        foreach ($pizzaItems as $item_kit_item) {
            $this->add_item($item_kit_item['item_id'], $item_kit_item['quantity'], $item_location, 0, $item_kit_item['price']);
        }
    }
    
    public function copy_entire_sale($sale_id)
    {
        $this->empty_cart();
        $this->remove_customer();
    
        $sale = new \App\Models\Sale();
        $saleItems = $sale->get_sale_items($sale_id)->getResult();
        foreach ($saleItems as $row) {
            $this->add_item($row->item_id, $row->quantity_purchased, $row->item_location, $row->discount_percent, $row->item_unit_price, $row->description, $row->serialnumber, null, true);
        }
    
        $salePayments = $sale->get_sale_payments($sale_id)->getResult();
        foreach ($salePayments as $row) {
            $this->add_payment($row->payment_type, $row->payment_amount);
        }
    
       return $this->set_customer($sale->get_customer($sale_id)->person_id);
    }
    
    public function copy_entire_suspended_sale($sale_id)
    {
        $this->empty_cart();
        $this->remove_customer();
    
        $suspendedSale = new \App\Models\Sale_suspended();
        $saleItems = $suspendedSale->get_sale_items($sale_id)->getResultArray();
        foreach ($saleItems as $row) {
            $this->add_item($row['item_id'], $row['quantity_purchased'], $row['item_location'], $row['discount_percent'], $row['item_unit_price'], $row['description'], $row['serialnumber'], null, true);
        }
    
        $salePayments = $suspendedSale->get_sale_payments($sale_id)->getResultArray();
        foreach ($salePayments as $row) {
            $this->add_payment($row->payment_type, $row->payment_amount);
        }
    
        $suspendedSaleInfo = $suspendedSale->get_info($sale_id)->getRow();
        $this->set_customer($suspendedSaleInfo->person_id);
        $this->set_comment($suspendedSaleInfo->comment);
        $this->set_invoice_number($suspendedSaleInfo->invoice_number);
    }

    public function copy_entire_cake_suspended_sale($sale_id)
    {
        $this->empty_cart();
        $this->remove_customer();
        $cake_suspended = new Cake_suspended();
        foreach ($cake_suspended->get_sale_items($sale_id)->getResult() as $row) {
            $this->add_item($row->item_id, $row->quantity_purchased, $row->item_location,
                $row->discount_percent, $row->item_unit_price, $row->description,
                $row->serialnumber, null, true);
        }
        foreach ($cake_suspended->get_sale_payments($sale_id)->getResult() as $row) {
            $this->add_payment($row->payment_type, $row->payment_amount);
        }

        $suspended_sale_info = $cake_suspended->get_info($sale_id)->getRow();
        $this->set_customer($suspended_sale_info->person_id);
        $this->set_comment($suspended_sale_info->comment);
        $this->set_invoice_number($suspended_sale_info->invoice_number);
    }


    public function copy_entire_pizza_order($sale_id)
    {
        $this->empty_cart();
        $this->remove_customer();
        $pizza_completed_order = new Pizza_completed_order();
        foreach ($pizza_completed_order->get_sale_items($sale_id)->getResult() as $row) {
            $this->add_item(4, $row->quantity_purchased, $row->item_location,
                $row->discount_percent, $row->item_unit_price, $row->description,
                $row->serialnumber, null, true,$row->item_name);
        }
        foreach ($pizza_completed_order->get_sale_payments($sale_id)->getResult() as $row) {
            $this->add_payment($row->payment_type, $row->payment_amount);
        }

        $suspended_sale_info = $pizza_completed_order->get_info($sale_id)->getRow();
        $this->set_customer($suspended_sale_info->person_id);
        $this->set_comment($suspended_sale_info->comment);
        $this->set_invoice_number($suspended_sale_info->invoice_number);
    }

    
    public function clear_all()
    {
        $this->set_invoice_number_enabled(false);
        $this->clear_mode();
        $this->empty_cart();
        $this->clear_comment();
        $this->clear_email_receipt();
        $this->clear_invoice_number();
        $this->clear_giftcard_remainder();
        $this->empty_payments();
        $this->remove_customer();
    }
    
    public function is_customer_taxable()
    {
        $customer_id = $this->get_customer();
        $customerModel = new \App\Models\Customer();
        $customer = $customerModel->get_info($customer_id);
        return $customer->taxable or $customer_id == -1;
        // Default to not taxable if customer is not found
        return false;
    }
    
    
    public function get_taxes()
    {
        $taxes = [];
    
        // Do not charge sales tax if we have a customer that is not taxable
        if ($this->is_customer_taxable()) {
            foreach ($this->get_cart() as $line => $item) {
                $tax_info = $this->Item_taxes->get_info($item['item_id']);
    
                foreach ($tax_info as $tax) {
                    $name = $tax['name'];
                    $tax_amount = $this->get_item_tax($item['quantity'], $item['price'], $item['discount'], $tax['percent']);
    
                    if (!isset($taxes[$name])) {
                        $taxes[$name] = '0';
                    }
    
                    $taxes[$name] = bcadd($taxes[$name], $tax_amount, 2);
                }
            }
        }
    
        return $taxes;
    }
    
    public function get_discount()
    {
        $discount = '0';
        foreach ($this->get_cart() as $line => $item) {
            if ($item['discount'] > 0) {
                $item_discount = $this->get_item_discount($item['quantity'], $item['price'], $item['discount']);
                $discount = bcadd($discount, $item_discount, 2);
            }
        }
    
        return $discount;
    }
    
    public function get_subtotal($include_discount = false, $exclude_tax = false)
    {
        $subtotal = $this->calculate_subtotal($include_discount, $exclude_tax);
        return $subtotal;
    }
    
    public function get_item_total_tax_exclusive($item_id, $quantity, $price, $discount_percentage, $include_discount = false)
    {
        $tax_info = $this->Item_taxes->get_info($item_id);
        $item_price = $this->get_item_total($quantity, $price, $discount_percentage, $include_discount);
    
        foreach ($tax_info as $tax) {
            $tax_percentage = $tax['percent'];
            $item_price = bcsub($item_price, $this->get_item_tax($quantity, $price, $discount_percentage, $tax_percentage), 2);
        }
    
        return $item_price;
    }
    
    public function get_item_total($quantity, $price, $discount_percentage, $include_discount = false)
    {
        $total = bcmul($quantity, $price, 2);
        if ($include_discount) {
            $discount_amount = $this->get_item_discount($quantity, $price, $discount_percentage);
            return bcsub($total, $discount_amount, 2);
        }
    
        return $total;
    }
    
    public function get_item_discount($quantity, $price, $discount_percentage)
    {
        $total = bcmul($quantity, $price, 2);
        $discount_fraction = bcdiv($discount_percentage, '100', 2);
    
        return bcmul($total, $discount_fraction, 2);
    }

    public function get_item_tax($quantity, $price, $discount_percentage, $tax_percentage)
    {
        $price = $this->get_item_total($quantity, $price, $discount_percentage, true);
    
        if ($this->config->get('tax_included')) {
            $tax_fraction = bcadd(100, $tax_percentage);
            $tax_fraction = bcdiv($tax_fraction, 100, 2);
            $price_tax_excl = bcdiv($price, $tax_fraction, 2);
    
            return bcsub($price, $price_tax_excl, 2);
        }
    
        $tax_fraction = bcdiv($tax_percentage, 100, 2);
    
        return bcmul($price, $tax_fraction, 2);
    }
    
    public function calculate_subtotal($include_discount = false, $exclude_tax = false)
    {
        $subtotal = 0;
    
        foreach ($this->get_cart() as $item) {
            if ($exclude_tax && $this->config->get('tax_included')) {
                $subtotal = bcadd($subtotal, $this->get_item_total_tax_exclusive($item['item_id'], $item['quantity'], $item['price'], $item['discount'], $include_discount), 2);
            } else {
                $subtotal = bcadd($subtotal, $this->get_item_total($item['quantity'], $item['price'], $item['discount'], $include_discount), 2);
            }
        }
    
        return $subtotal;
    }

    // public function get_total($fbr_fee = FALSE,$str=NULL)
    // {
    //     $total = $this->calculate_subtotal(TRUE);
    //     if (!$this->CI->config->config['tax_included']) {
    //         foreach ($this->get_taxes() as $tax) {
    //             $total = bcadd($total, $tax);
    //         }
    //     }
    //     $resp = $this->CI->appconfig->get('fbr_fee');
    //     if ($fbr_fee && $str =="reciept") {
    //         $total =$total + $fbr_fee;
    //     }
    //     elseif ($str =="reciept") {
    //         $total =$total;
    //     }

    //     elseif($total>$resp){
    //         $payment_type = $this->get_payment_type();
    //         $mode = $this->get_mode();
    //         $type = $this->get_type();
    //         $resp = $this->CI->appconfig->get('fbr_fee');
    //         if ($payment_type!=='Cod'  && $mode == 'sale' && $type == 'normal' && $resp) {
    //             $total =$total + $resp;
    //         }
    //     }
    
    //     return $total;
    // }

    public function get_total($fbr_fee = false, $str = null)
    {
        $total = $this->calculate_subtotal(true);
        if (!$this->config->get('tax_included')) {
            foreach ($this->get_taxes() as $tax) {
                $total = bcadd($total, $tax);

            }
        }
    
        $resp = $this->config->get('fbr_fee');
        if ($fbr_fee && $str == "receipt") {
            // $total = bcadd($total, $fbr_fee, 2);
            $total =$total + $fbr_fee;
        } elseif ($str == "receipt") {
            $total = $total;
        } elseif ($total > $resp) {
            $payment_type = $this->get_payment_type();
            $mode = $this->get_mode();
            $type = $this->get_type();
            $resp = $this->config->get('fbr_fee');
            if ($payment_type !== 'Cod' && $mode == 'sale' && $type == 'normal' && $resp) {
                  $total =$total + $resp;
            }
        }
        return $total;
    }

    public function validate_item(&$item_id)
    {
        // Make sure item exists
        if (!$this->Item->item_exists($item_id)) {
            // Try to get item id given an item_number
             $this->get_mode();
            $item_id = $this->Item->get_item_id($item_id);
    
            if (!$item_id) {
                return false;
            }
        }
    
        return true;
    }
    
}

?>
