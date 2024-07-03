<?php

namespace App\Controllers;

use App\Controllers\SecureController;
use App\Libraries\BarcodeLib;
use App\Libraries\Ciqrcode;
use App\Libraries\EmailLib;
use App\Libraries\Gu;
use App\Libraries\SaleLib;
use App\Models\Appconfig;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Giftcard;
use App\Models\Item;
use App\Models\Item_kit;
use App\Models\Module;
use App\Models\Sale;
use App\Models\Sale_suspended;
use App\Models\Stock_location;
use App\Models\Cake_suspended;
use App\Models\Pizza_completed_order;

class Sales extends SecureController
{
    protected $Giftcard;
    protected $Customer;
    protected $Item;
    protected $sale_lib;
    protected $barcode_lib;
    protected $email_lib;
    protected $gu;
    protected $ciqrcode;
    protected $Employee;
    protected $appData;
    protected $stockLocation;
    protected $employeeModel;
    protected $Sale;
    protected $Sale_suspended;
    protected $Item_kit;
    protected $Cli;

    protected $Cake_suspended;
    public function __construct($module_id = null)
    {
        parent::__construct('sales');
        $this->stockLocation = new Stock_location();
        $this->sale_lib = new SaleLib();
        $this->barcode_lib = new BarcodeLib();
        $this->email_lib = new EmailLib();
        $this->gu = new Gu();
        $this->ciqrcode = new Ciqrcode();
        $this->Employee = new Employee();
        $this->appData = new Appconfig();
        $this->employeeModel = new Employee();
        $this->Sale = new Sale();
        $this->Sale_suspended = new Sale_suspended();
        $this->Item = new Item();
        $this->Item_kit = new Item_kit();
        $this->Customer = new Customer();
        $this->Giftcard = new Giftcard();
        $this->Cli = new Cli();
        $this->Cake_suspended = new Cake_suspended();

        //        $this->load->add_package_path(APPPATH.'third_party/debugbar');
        //        $this->load->library('console');
        //        $this->output->enable_profiler(TRUE);
        //
        //        $this->console->debug('Hello world !');
        
    }

    public function index($msg ='')
    {
        $this->sale_lib->set_type('normal');
        $this->sale_lib->set_payment_type('Cash');
        $data['warning'] = $msg;
         $this->_reload($data);
    }

    public function manage()
    {
        $person_id = session()->get('person_id');
        $data = $this->data;
        if (!$this->Employee->has_grant('reports_sales', $person_id)) {
            redirect('no_access/sales/reports_sales');
        } else {

            $data['table_headers'] = get_sales_manage_table_headers();

            // filters that will be loaded in the multiselect dropdown
            if ($this->appData->get('invoice_enable') == TRUE) {
                $data['filters'] = array(
                    'only_cash' => lang('sales_lang.sales_cash_filter'),
                    'only_invoices' => lang('sales_lang.sales_invoice_filter')
                );
            } else {
                $data['filters'] = array(
                    'only_cash' => lang('sales_lang.sales_cash_filter'),
                    'only_credit' => 'Credit Card',
                    'only_gift' => 'Gift Card',
                );
            }

            $data['sale_modes'] = $this->gu->getSaleTypesForFilter();

		// $logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		// $data['user_info'] = $logged_in_employee_info;
		// $appdata = $this->appconfigModel->get_all();
		// $data['appData']=$appdata;


        // $data['gu'] = $this->gu;
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		// $data['controller_name'] = 'sales';
		$data['print_after_sale']= false;
		$data['selected_printer']= 'takings_printer';
            return view('sales/manage', $data);
        }
    }

    public function get_row($row_id)
    {
        //$this->Sale->create_temp_table();

        $sale_info = $this->Sale->get_info($row_id)->getRow();

        $data_row = $this->xss_clean(get_sale_data_row($sale_info, $this));

        echo json_encode($data_row);
    }

    public function search()
    {
        //        $db = $this->gu->getReportingDb();
        //        $this->Sale->create_temp_table($db);
        $search = request()->getGet('search');
        $limit = request()->getGet('limit');
        $offset = request()->getGet('offset');
        $sort = request()->getGet('sort');
        $order = request()->getGet('order');
        $sale_mode = request()->getGet('sale_mode');
        $branch_code = request()->getGet('branch_code');

        if ($sale_mode == "sales") {
            $sale_type = "sales";
            $sale_mode = "normal";
        } elseif ($sale_mode == "returns") {
            $sale_type = "returns";
            $sale_mode = "normal";
        } else {
            $sale_type = "all";
        }
        //todo - 1
        $is_valid_receipt = !empty($search) ? $this->sale_lib->is_valid_receipt($search) : FALSE;

        $filters = array(
            'sale_type' => $sale_type,
            'sale_mode' => $sale_mode,
            'branch_code' => $branch_code,
            'location_id' => 'all',
            'start_date' => request()->getGet('start_date'),
            'end_date' => request()->getGet('end_date'),
            'only_cash' => FALSE,
            'only_credit' => FALSE,
            'only_invoices' =>  $this->appData->get('invoice_enable') && request()->getGet('only_invoices'),
            'is_valid_receipt' => $is_valid_receipt
        );

        // check if any filter is set in the multiselect dropdown
        $filledup = array_fill_keys(request()->getGet('filters'),true);
        $filters = array_merge($filters, $filledup);

        //todo - 2
        $sales = $this->Sale->search($search, $filters, $limit, $offset, $sort, $order);

        //todo - 3
        $total_rows = $this->Sale->get_found_rows($search, $filters);
        //todo - 4
        //$payments = $this->Sale->get_payments_summary($search, $filters);

        //$payment_summary = $this->xss_clean(get_sales_manage_payments_summary($payments, $sales, $this));
        $payment_summary = "";

        $data_rows = array();
        foreach ($sales->getResult() as $sale) {
            $data_rows[] = $this->xss_clean(get_sale_data_row($sale, $this));
        }

        if ($total_rows > 0) {
            $data_rows[] = $this->xss_clean(get_sale_data_last_row($sales, $this));
        }

        //      $db->close();
        //      $this->db->close();
        return json_encode(['total' => $total_rows, 'rows' => $data_rows,'payment_summary' => $payment_summary]);

    }

    public function item_search()
    {
        $suggestions = array();
        $receipt = $search = request()->getVar('term') != '' ? request()->getVar('term') : null;
        // $receipt = $search = request()->getGet('term') != '' ? request()->getGet('term') : NULL;

        if ($this->sale_lib->get_mode() == 'return' && $this->sale_lib->is_valid_receipt($receipt)) {
            // if a valid receipt or invoice was found the search term will be replaced with a receipt number (POS #)
            $suggestions[] = $receipt;
        }
        $search = trim($search);
        $suggestions = array_merge($suggestions, $this->Item->get_search_suggestions($search, array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));
        $suggestions = array_merge($suggestions, $this->Item_kit->get_search_suggestions($search));

        // to get Pizza order and items related to order 
        if (preg_match("/PN/", $search) || preg_match("/pn/", $search)) {
            $suggestions = array_merge($suggestions, $this->Pizza_order->get_search_suggestions($search));
        }
        $suggestions = $this->xss_clean($suggestions);
        echo json_encode($suggestions);
    }

    public function getId($search)
    {
        $suggestion = $this->xss_clean($this->Pizza_order->get_info_by_order_number($search));
        echo json_encode($suggestion);
    }

    public function suggest_search()
    {
        $search = request()->getPost('term') != '' ? request()->getPost('term') : NULL;

        $suggestions = $this->xss_clean($this->Sale->get_search_suggestions($search));

        echo json_encode($suggestions);
    }

    public function select_customer()
    {
        $customer_id = request()->getPost('customer');
        if ($this->Customer->exists($customer_id)) {
            $this->sale_lib->set_customer($customer_id);
        }
      $this->_reload();
    }

    /**
     * Change Sale Mode "Sales, Returns"
     */
    public function change_mode()
    {
        $stock_location = request()->getPost('stock_location');
        if (!$stock_location || $stock_location == $this->sale_lib->get_sale_location()) {
            $mode = request()->getPost('mode');
            $this->sale_lib->set_mode($mode);

            $this->sale_lib->set_type('normal');
        } elseif ($this->stockLocation->is_allowed_location($stock_location, 'sales')) {
            $this->sale_lib->set_sale_location($stock_location);
        }
       $this->_reload();

    }

    /**
     * Change Sale Type "Normal, Breakfast, Complementary, Burger, Cash Counter, Employee"
     */
    public function change_type()
    {
        $type = request()->getPost('type');
        $this->sale_lib->set_type($type);
        if ($type == "normal") {
            $mode = 'sale';
        } else {
            $mode = 'return';
        }
        $this->sale_lib->set_mode($mode);

        $this->_reload();
    }

    public function change_payment_type()
    {
        $type = request()->getGet('payment_type');

        $this->sale_lib->set_payment_type($type);

        $this->_reload();
    }

    public function set_comment()
    {
        $this->sale_lib->set_comment(request()->getPost('comment'));
    }

    public function set_invoice_number()
    {
        $this->sale_lib->set_invoice_number(request()->getPost('sales_invoice_number'));
    }

    public function set_invoice_number_enabled()
    {
        $this->sale_lib->set_invoice_number_enabled(request()->getPost('sales_invoice_number_enabled'));
    }

    public function set_print_after_sale()
    {
        $this->sale_lib->set_print_after_sale(request()->getPost('sales_print_after_sale'));
    }

    public function set_email_receipt()
    {
        $this->sale_lib->set_email_receipt(request()->getPost('email_receipt'));
    }

    // Multiple Payments
    public function add_payment()
    {
        $data = array();
        $validation = \Config\Services::validation();
        // $validation->setRules([
        //     'amount_tendered' => 'trim|required'
        // ]);
        // $validation->setRules(['amount_tendered', 'lang:sales_amount_tendered', 'trim|required']);

        $payment_type = request()->getPost('payment_type');
        // dd($validation);
        if ($validation->check('amount_tendered','trim|required') == false) {
            if ($payment_type == lang('sales_lang.sales_giftcard')) {
                $data['error'] = lang('sales_lang.sales_must_enter_numeric_giftcard');
            } else {
                $data['error'] = lang('sales_lang.sales_must_enter_numeric');
            }
        } else {

            if ($payment_type == lang('sales_lang.sales_giftcard')) {
                // in case of giftcard payment the register input amount_tendered becomes the giftcard number
                $giftcard_num = request()->getPost('amount_giftcard_tendered');

                $payments = $this->sale_lib->get_payments();
                $payment_type = $payment_type . ':' . $giftcard_num;
                $current_payments_with_giftcard = isset($payments[$payment_type]) ? $payments[$payment_type]['payment_amount'] : 0;
                $cur_giftcard_value = $this->Giftcard->get_giftcard_value($giftcard_num);

                if (($cur_giftcard_value - $current_payments_with_giftcard) <= 0) {
                    $data['error'] = lang('giftcards_lang.giftcards_remaining_balance'.$giftcard_num. to_currency($cur_giftcard_value));
                } else {
                    $new_giftcard_value = $this->Giftcard->get_giftcard_value($giftcard_num) - $this->sale_lib->get_amount_due();
                    $new_giftcard_value = $new_giftcard_value >= 0 ? $new_giftcard_value : 0;
                    $this->sale_lib->set_giftcard_remainder($new_giftcard_value);
                    $new_giftcard_value = str_replace('$', '\$', to_currency($new_giftcard_value));
                    $data['warning'] = lang('giftcards_lang.giftcards_remaining_balance'. $giftcard_num. $new_giftcard_value);
                    $amount_tendered = min($this->sale_lib->get_amount_due(), $this->Giftcard->get_giftcard_value($giftcard_num));

                    $this->sale_lib->add_payment($payment_type, $amount_tendered);
                    // echo json_encode(request()->getPost('amount_tendered'));
                    // exit();
                    // if amount is greater than giftcard amount and customer paid cash
                    if (request()->getPost('amount_tendered')) {
                        $amount_tendered = request()->getPost('amount_tendered');
                        $payment_type = lang('sales_lang'. request()->getPost('sales_payment_type'));

                        $this->sale_lib->add_payment($payment_type, $amount_tendered);
                    }
                }
            } else {
                $amount_tendered = request()->getPost('amount_tendered');
                // $amount_tendered = str_replace(',', '', $amount_tendered);
                $this->sale_lib->add_payment($payment_type, $amount_tendered);
            }
        }
        $this->_reload($data, true);
        return redirect()->to('sales/complete');

    }

    public function postDataApiFBR()
    {
        $data['cart'] = $this->sale_lib->get_cart();

        $data['subtotal'] = $this->sale_lib->get_subtotal(TRUE);
        $data['discounted_subtotal'] = $this->sale_lib->get_subtotal(TRUE);
        $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(TRUE, TRUE);
        // $data['taxes'] = $this->sale_lib->get_taxes();
        $data['discount'] = $this->sale_lib->get_discount();
        $data['total'] = $this->sale_lib->get_total();
        // $data['payments_total'] = $this->sale_lib->get_payments_total();
        $data['transaction_time'] = date("Y-m-d H:i");

        $customer_info = $this->_load_customer_data($this->sale_lib->get_customer(), $data, TRUE);
        $data['invoice_number'] = $this->_substitute_invoice_number($customer_info);
        $data['invoice_number_enabled'] = $this->sale_lib->is_invoice_number_enabled();

        $data['payment_type'] = $this->sale_lib->get_payment_type();
        $data['mode'] = $this->sale_lib->get_mode();

        $data['Payment_mode'] = 1;
        if ($data['payment_type'] == 'Cash') {
            $data['Payment_mode'] = 1;
        } elseif ($data['payment_type'] == 'Credit Card') {
            $data['Payment_mode'] = 2;
        } elseif ($data['payment_type'] == 'Check') {
            $data['Payment_mode'] = 6;
        } elseif ($data['payment_type'] == 'Gift Card') {
            $payments = $this->sale_lib->get_payments();
            if (count($payments) > 1) {
                $data['Payment_mode'] = 5;
            } else {
                $data['Payment_mode'] = 3;
            }
        }
        $data = $this->xss_clean($data);
        $fbr_pct_code = $this->appData->get('fbr_pct_code');
        $data['cartObject'] = [];
        $data['totalQuantity'] = 0;
        foreach ($data['cart'] as $key => $value) {

            if ($data['mode']=='return') 
            {
                // Remove - sign from array values in sale return
                $value = array_map(function($element) {
                    return $element > 0 ? $element : ltrim($element, '-');
                },$value);
            }

            $TaxCharged = 0;
            $TaxPercent = 0;
            if ($value['item_tax_percent']) {

                if ($value['discount']) {
                    $discounttedPrice = $value['cost_price'] - ($value['cost_price'] * $value['discount'] / 100);
                } else {
                    $discounttedPrice = $value['cost_price'];
                }

                $TaxPercent = $value['item_tax_percent'];
                $TaxCharged = $discounttedPrice * $value['quantity'] * ($value['item_tax_percent'] / 100);
            }
            $data['totalQuantity'] += $value['quantity'];
            $data['cartObject'][] = [
                'PCTCode' => $fbr_pct_code,
                'ItemCode' => $value['item_id'],
                'ItemName' => $value['name'],
                'Quantity' => $value['quantity'],
                'TaxRate' => $TaxPercent,
                'TotalAmount' => $value['discounted_total'],
                'TaxCharged' => $TaxCharged,
                'InvoiceType' => 1,
                'Discount' => $value['discount'],
                'SaleValue' => $value['discounted_total'] - $TaxCharged,
                'RefUSIN' => ''
            ];
        }
        unset($data['cart']);
        $data['cartObject'] = array_values($data['cartObject']);
        $data['cartObject'] = json_encode($data['cartObject']);

        // $this->generateFBRInvoice($data);
    }

    public function generateFBRInvoice($data)
    {
        $url = $this->appData->get('fbr_post_url');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $token = $this->appData->get('fbr_bearer_token');
        $pos_id = $this->appData->get('fbr_pos_id');
        $data['fbr_fee'] = $this->appData->get('fbr_fee');
        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $data['TotalTaxCharged'] = $data['total'] - $data['fbr_fee'] - $data['tax_exclusive_subtotal'];

        // Default invoiceType 1 for Normal Sale and 3 for Sale Return
        // Remove - sign from array values in sale return;
        $data['InvoiceType'] = 1;
        if ($data['mode']=='return') 
        {
            $data = array_map(function($value) {
                return $value > 0 ? $value : ltrim($value, '-');
            },$data);
            $data['InvoiceType'] = 3;
        }

        $data = '
        {
          "TotalBillAmount": ' . $data['total'] . ',
          "POSID": ' . $pos_id . ',
          "Discount": ' . $data['discount'] . ',
          "USIN": "THBKRY",
          "TotalQuantity": ' . $data['totalQuantity'] . ',
          "TotalTaxCharged": ' . $data['TotalTaxCharged'] . ',
          "TotalSaleValue": ' . $data['tax_exclusive_subtotal'] . ',
          "FurtherTax":' . $data['fbr_fee'] . ',
          "Items": ' . $data['cartObject'] . ',
          "DateTime": "' . $data['transaction_time'] . '",
          "PaymentMode": ' . $data['Payment_mode'] . ',
          "InvoiceType": '. $data['InvoiceType'] .',
          "RefUSIN":"",
          "InvoiceNumber":""
        }';

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $invoice = json_decode($resp)->InvoiceNumber;
      
        // $invoice  = preg_replace('/[^0-9]/', '', $invoice);
        $qr_image = $invoice . '.png';
        $params['data'] = $invoice;
        $params['level'] = 'H';
        $params['size'] = 4;
        $params['savename'] = "./uploads/qr_image/" . $qr_image;
      
        $this->ciqrcode->generate($params);

        $this->sale_lib->set_fbr_invoice_number($invoice);
    }


    public function giftcard_value($giftcard_num)
    {
        $giftcard_value = $this->Giftcard->get_giftcard_value($giftcard_num);
        echo json_encode(array('value' => parse_decimals($giftcard_value)));
    }

    // Multiple Payments
    public function delete_payment($payment_id)
    {
        $this->sale_lib->delete_payment($payment_id);

        ;
    }

    public function add()
    { 
        $data = array();
        $data['cart'] = $this->sale_lib->get_cart();
        $mode = $this->sale_lib->get_mode();
        $item_id_or_number_or_item_kit_or_receipt = request()->getVar('item');
        $editQuantity = 0;

        if ($item_id_or_number_or_item_kit_or_receipt[0] == "-") {
            $item_id_or_number_or_item_kit_or_receipt =
                str_replace("-", "", $item_id_or_number_or_item_kit_or_receipt);

            $editQuantity = 1;
        }

        // echo "<pre>";
        $item = $this->Item->get_info($item_id_or_number_or_item_kit_or_receipt);
        $item =  json_decode(json_encode($item), true);
        $item_cost = round((int)$item["unit_price"]);

        // if ($this->Item->item_number_exists($item_id_or_number_or_item_kit_or_receipt)) {
        //     $quantity = ($mode == 'return') ? -1 : 1;
        // }
        $test = 0;
        foreach ($data['cart'] as $value) {
            if ($value["item_id"] == $item_id_or_number_or_item_kit_or_receipt && $value['price'] != 1 && $item_cost != 1) {
                $test += 1;
                $value["quantity"] += 1;
                $this->sale_lib->edit_item($value["line"], $value["description"], $value["serialnumber"], $value["quantity"], $value["discount"], $value["price"]);
            }
        }

        if ($test == 0) {

            $barcode = str_split($item_id_or_number_or_item_kit_or_receipt);

            if (count($barcode) != 13 || !is_numeric($item_id_or_number_or_item_kit_or_receipt)) {
                $barcode = null;
            }
        
            if ($barcode) {

                $item_id_or_number_or_item_kit_or_receipt = "";

                //extract item code (2 to 6)
                for ($i = 2; $i < 7; $i++) {
                    $item_id_or_number_or_item_kit_or_receipt .= $barcode[$i];
                }


                $quantity1 = "";

                //extract item quantity 1
                for ($i = 7; $i < 9; $i++) {
                    $quantity1 .= $barcode[$i];
                }


                $quantity2 = "";
                //extract item quantity 2
                for ($i = 9; $i < 12; $i++) {
                    $quantity2 .= $barcode[$i];
                }


                $quantity = "$quantity1.$quantity2";

                if ($quantity == "00.001") {
                    $quantity = 1;
                }

                $quantity = ($mode == 'return') ? $quantity * -1 : $quantity;
            } else {
                $quantity = ($mode == 'return') ? -1 : 1;
            }
        }


        $item_location = $this->sale_lib->get_sale_location();
        if($item_location==null){
           $item_location = 1;
        }
        $discount = 0;

        // check if any discount is assigned to the selected customer
        $customer_id = $this->sale_lib->get_customer();
        if ($customer_id != -1) {
            // load the customer discount if any
            $discount = $this->Customer->get_info($customer_id)->discount_percent == '' ? 0 : $this->Customer->get_info($customer_id)->discount_percent;
        }

        // if the customer discount is 0 or no customer is selected apply the default sales discount
        if ($discount == 0) {
            $discount = $this->appData->get('default_sales_discount');
        }
        // if(preg_match("/PN/", $item_id_or_number_or_item_kit_or_receipt) || preg_match("/pn/", $item_id_or_number_or_item_kit_or_receipt))
        // {
        //     // if(preg_match("/PN /", $item_id_or_number_or_item_kit_or_receipt) || preg_match("/pn /", $item_id_or_number_or_item_kit_or_receipt))
        //     $this->sale_lib->add_item_pizza($item_id_or_number_or_item_kit_or_receipt, $item_location);
        // }else{
          $is_valid_receipt =  $this->sale_lib->is_valid_receipt($item_id_or_number_or_item_kit_or_receipt);
        if ($test != 0) {
            // $this->_reload($data);
            // echo "<pre>";
            // print_r($data);
            // exit();
        } elseif ($mode == 'return' &&  $is_valid_receipt) {
            $this->sale_lib->return_entire_sale($item_id_or_number_or_item_kit_or_receipt);
        } elseif ($this->sale_lib->is_valid_item_kit($item_id_or_number_or_item_kit_or_receipt)) {
            $this->sale_lib->add_item_kit($item_id_or_number_or_item_kit_or_receipt, $item_location);
        }; 
        $add_item = $this->sale_lib->add_item($item_id_or_number_or_item_kit_or_receipt, $quantity, $item_location, $discount, null, null, null, $mode);
        if (!$add_item) {
            $data['error'] = lang('sales_lang.sales_unable_to_add_item');
        }
        // }
        //$data['warning'] = $this->sale_lib->out_of_stock($item_id_or_number_or_item_kit_or_receipt, $item_location);
        $data['edit_quantity'] = $editQuantity;

       $this->_reload($data);
    }

    public function edit_item($item_id)
    {
        $sale_type = request()->getPost('sale_type');
        $sale_mode = request()->getPost('sale_mode');
        if ($sale_type == "normal" && $sale_mode == "sale") {
            $negative = false;
        } else {
            $negative = true;
        }
        $data = array();
        $validation = \Config\Services::validation();

        // $validation()->setRules('price', 'lang:items_price', 'required|callback_numeric');
        // $validation()->setRules('quantity', 'lang:items_quantity', 'required|callback_numeric');
        // $validation()->setRules('discount', 'lang:items_discount', 'required|callback_numeric');
        // $validation->setRules([
        //                            'price'=>'required|callback_numeric',
        //                            'quantity'=> 'required|callback_numeric',
        //                            'discount'=>'required|callback_numeric'
        //                         ]);

        $description = request()->getPost('description');
        $serialnumber = request()->getPost('serialnumber');
        $price = parse_decimals(request()->getPost('price'));
        $quantity = parse_decimals(request()->getPost('quantity'));
        
        if ($price < 0) {
            $price *= -1;
        }
        if ($negative) {
           
            if ($quantity > 0) {
                $quantity *= -1;
            }
        }

        $discount = parse_decimals(request()->getPost('discount'));
        $item_location = request()->getPost('location');

        // if ($this->form_validation->run() != FALSE) {
        $this->sale_lib->edit_item($item_id, $description, $serialnumber, $quantity, $discount, $price);
        // } else {
        //     $data['error'] = lang('sales_lang.sales_error_editing_item');
        // }

        //$data['warning'] = $this->sale_lib->out_of_stock($this->sale_lib->get_item_id($item_id), $item_location);

       return $this->_reload($data);
    }

    public function discount()
    {
        $data = array();

        //$this->form_validation->set_rules('discount', 'lang:items_discount', 'required|callback_numeric');

        $discount = parse_decimals(request()->getGet('discount'));
        //if ($this->form_validation->run() != FALSE) {
        $this->sale_lib->apply_discount($discount);
        //        } else {
        //            $data['error'] = lang('sales_lang.sales_error_editing_item');
        //        }

      $this->_reload($data);
    }

    public function delete_item($item_number)
    {
        $this->sale_lib->delete_item($item_number);

        $this->_reload();
    }

    // Deleted log functionality
    // public function delete_item($item_number)
    // {
    //     $items = $this->sale_lib->get_cart();
        
    //     $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
    //     $data = array(
    //         'employee_id' => $employee_id,
    //         'item_id' => $items[$item_number]['item_id'],
    //         'quantity' => $items[$item_number]['quantity'],
    //         'price' => $items[$item_number]['price']
    //     );
    //     if ($this->Item->delete_sale_item($data)) {
    //         unset($items[$item_number]);
    //     }
    //     $this->sale_lib->set_cart($items);
    //     // $this->sale_lib->delete_item($item_number);

    //     ;
    // }

    public function remove_customer()
    {
        $this->sale_lib->clear_giftcard_remainder();
        $this->sale_lib->clear_invoice_number();
        $this->sale_lib->remove_customer();

        $this->_reload();
    }


    /**
     * Upload to db
     */
    public function upload()
    {
        $data = array();

        //$response = "<pre>";
        $data['success'] = exec('php index.php cli upload', $output, $error);


        // $response .= " " . print_r($output) . "</pre>";

        // $data['success'] = $response;

       return $this->_reload($data);
    }


    public function complete()
    {
        $data = array();
        $data = $this->data;
        $data['cake_invoice'] = null;
        $data['second_payment'] = null;
        $data['cart'] = $this->sale_lib->get_cart();
        $data['subtotal'] = $this->sale_lib->get_subtotal();
        $data['discounted_subtotal'] = $this->sale_lib->get_subtotal(TRUE);
        $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(TRUE, TRUE);
        $data['taxes'] = $this->sale_lib->get_taxes();
        $data['total'] = $this->sale_lib->get_total();

        if(session()->has('cake_invoice'))
        {
            $data['cake_invoice'] = session()->get('cake_invoice');
            $data['second_payment'] = session()->get('second_payment');
         }
        $data['modes'] = array('sale' => lang('sales_lang.sales_sale'), 'return' => lang('sales_lang.sales_return'));
        $data['mode'] = $this->sale_lib->get_mode();
        $data['stock_locations'] = $this->stockLocation->get_allowed_locations('sales');
        $data['stock_location'] = $this->sale_lib->get_sale_location();
        $data['types'] = $this->gu->getSaleTypes();
        $data['type'] = $this->sale_lib->get_type();
        $data['comment'] = $this->sale_lib->get_comment();
        $data['email_receipt'] = $this->sale_lib->get_email_receipt();
        $data['payments_total'] = $this->sale_lib->get_payments_total();
        $data['payment_options'] = $this->Sale->get_payment_options();
        
        if ((int)$data['total'] == 0) {
            $this->cancel('Something went wrong with last sale. Please try again.');
        }

        $data['discount'] = $this->sale_lib->get_discount();
        $data['receipt_title'] = lang('sales_lang.sales_receipt');
        $data['transaction_time'] = date($this->appData->get('dateformat') . ' ' . $this->appData->get('timeformat'));
        $data['transaction_date'] = date($this->appData->get('dateformat'));
        $data['show_stock_locations'] = $this->stockLocation->show_locations('sales');
        $data['comments'] = $this->sale_lib->get_comment();
        $data['payments'] = $this->sale_lib->get_payments();
        $data['amount_change'] = $this->sale_lib->get_amount_due() * -1;
        $data['amount_due'] = $this->sale_lib->get_amount_due();
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $employee_info = $this->Employee->get_info($employee_id);
        $data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;
        $data['company_info'] = implode("\n", array(
            $this->appData->get('address'),
            $this->appData->get('phone'),
            $this->appData->get('account_number')
        ));
        $data['branch_code'] = $this->gu->getStoreBranchCode();
        $data['branch'] = $this->gu->getStoreInfoByBranchCode($data['branch_code']);

        $data['payment_type'] = $this->sale_lib->get_payment_type();

        $data['bill_number'] = $this->gu->generateBillNumber();

        $data['payments_cover_total'] = $this->sale_lib->get_amount_due() <= 0;
        $data['last_total'] = $this->sale_lib->get_from_session('last_total');
        $data['last_amount_change'] = $this->sale_lib->get_from_session('last_amount_change');
        $data['last_amount_due'] = $this->sale_lib->get_from_session('last_amount_due');
        $data['last_payments_total'] = $this->sale_lib->get_from_session('last_payments_total');
        //$data['giftcard_checkout_limit'] = $this->appData->get('default_giftcard_limit');

        $customer_id = $this->sale_lib->get_customer();
        $customer_info = $this->_load_customer_data($customer_id, $data);
        //$invoice_number = $this->_substitute_invoice_number($customer_info);
        $invoice_number = $data['bill_number'];

        $data['mode'] = $this->sale_lib->get_mode();
        $data['type'] = $this->sale_lib->get_type();
        $data['fbr_invoice_number']  = '';
        // Post data to FBR API and generate invoice code
        // Check if mode is normal sale or return
        if ($data['mode'] == 'return' || 
            ($data['mode'] == 'sale' && $data['type'] == 'normal')) 
        {
            $this->postDataApiFBR();
            $data['fbr_invoice_number'] = $this->sale_lib->get_fbr_invoice_number();
            $data['fbr_qrcode'] = $data['fbr_invoice_number'] . '.png';
            $data["fbr_fee"] = $this->appData->get('fbr_fee');
            $data['fbr_barcode'] = $this->barcode_lib->generate_sale_barcode($data['fbr_invoice_number']);

            if ($data['mode'] == 'return'){
                $data['total'] -= $data['fbr_fee'];
                $data['amount_change'] += $data['fbr_fee'];
                $data['fbr_fee'] = '-'.$data['fbr_fee'];
            }
        }

        //WE HAVE DISABLED INVOICE NUMBER SO THIS IF WONT BE FUNCTIONAL, WE ARE USING IT TO STORE BILL NUMBER
        if ($this->sale_lib->is_invoice_number_enabled() && $this->Sale->invoice_number_exists($invoice_number)) {
            $data['error'] = lang('sales_lang.sales_invoice_number_duplicate');

          return $this->_reload($data);
        } else {
            //$invoice_number = $this->sale_lib->is_invoice_number_enabled() ? $invoice_number : NULL;
            //$invoice_number = $data['bill_number'];
            $data['invoice_number'] = $invoice_number;

            // sale is complete. creating log now
            if ($this->gu->saleLog()) {
                if ($data['payments']) {
                    $sale_payment_type = array_values($data['payments'])[0]['payment_type'];
                } else {
                    $sale_payment_type = '';
                }
                //TODO - TEMP SALE LOG

                /**
                 * this for testing purpose
                 */
                $log = date('d M Y h:i A') . " Sale ID: "
                    . $invoice_number . " Total: " . $data['total']
                    . " Payment Type: " . $sale_payment_type
                    . " FBR Invoice: " . $data['fbr_invoice_number'];

                $file = $this->gu->getStoreDate() . "_sales_before.log";

                $this->gu->logInFile($file, $log);

                //file_put_contents(APPPATH . "logs/sales_before.log", $log, FILE_APPEND);

                /////////////------------------------------------------------------
            }



            /**
             * Save the sale
             */

            if ($data["fbr_fee"]) {
                $data['sale_id_num'] = $this->Sale->save_sale(
                    $data['cart'],
                    $customer_id,
                    $employee_id,
                    $data['comments'],
                    $invoice_number,
                    $data['payments'],
                    $this->sale_lib->get_type(),
                    $data['payment_type'],
                    $data['fbr_invoice_number'],
                    $data['cake_invoice'],
                    abs($data["fbr_fee"])
                );
            } else {
                $data['sale_id_num'] = $this->Sale->save_sale(
                    $data['cart'],
                    $customer_id,
                    $employee_id,
                    $data['comments'],
                    $invoice_number,
                    $data['payments'],
                    $this->sale_lib->get_type(),
                    $data['payment_type'],
                    $data['fbr_invoice_number'],
                    $data['cake_invoice']

                );
            }

            $data['sale_id'] = 'POS ' . $data['sale_id_num'];
    
            $data = $this->xss_clean($data);

            if ($data['sale_id_num'] == -1) {
                $data['error_message'] = lang('sales_lang.sales_transaction_failed');
            } else {
                ////////// TODO - GU CUSTOM BARCODE
                //$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['sale_id']);

                //                $data['bill_number'] = $this->gu->generateBillNumber();
                //
                //                $data['barcode'] = $this->barcode_lib->generate_sale_barcode($data['bill_number']);


            }


            $data['barcode'] = $this->barcode_lib->generate_sale_barcode($data['bill_number']);

            $data['cur_giftcard_value'] = $this->sale_lib->get_giftcard_remainder();
            $data['print_after_sale'] = $this->sale_lib->is_print_after_sale();
            $data['email_receipt'] = $this->sale_lib->get_email_receipt();

            $data['sale_mode'] = $this->sale_lib->get_type();
            //$data['sale_type'] = $this->sale_lib->get_type();

            /**
             * *************** save last sale
             */

            $this->sale_lib->save_in_session('last_total', (int)$data['total']);
            $this->sale_lib->save_in_session('last_amount_change', to_currency($data['amount_change']));
            $this->sale_lib->save_in_session('last_amount_due', to_currency($data['amount_due']));
            $this->sale_lib->save_in_session('last_payments_total', (int)$this->sale_lib->get_payments_total());

            /**
             * *******------- save last complete sale
             */


             $data['paid_cart'] = '';
             if($data['cake_invoice'] && $data['second_payment']){

              $suspended_sale = $this->Cake_suspended->get_sale_by_invoice_number($data['cake_invoice']);
              $data['paid_cart'] = $this->get_prev_reciept_data($data['cake_invoice']);

               }
               
            $this->sale_lib->save_in_session('last_receipt', $data);

            if(session()->has('cake_invoice')){
                session()->remove(['cake_invoice', 'second_payment']);
                 }

            //////////////////////////////////////////////////////

            if ($this->sale_lib->is_invoice_number_enabled()) {
                return view('sales/invoice', $data);
            } else {
        // $logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		// $data['user_info'] = $logged_in_employee_info;
		// $appdata = $this->appconfigModel->get_all();
		// $data['appData']=$appdata;

        $data['employees'] = $this->Employee;
        // $data['gu'] = $this->gu;
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		// $data['controller_name'] = 'sales';
        $data['sale_lib'] = $this->sale_lib;
        $data['receipt_title'] = lang('sales_lang.sales_receipt');
        if (!isset($suspended_sale['sale_id'])) {
            $data['last_suspended_sale'] = null;
        } else {
            $data['last_suspended_sale'] = $suspended_sale['sale_id'];
        }
                //                return view('sales/receipt', $data);
                $cli = new Cli();
                $data['cli'] = $cli;
                $this->sale_lib->clear_all();
                return view('sales/register', $data);
            }

            // redirect(base_url('/sales'), 'refresh');
        }
    }

    public function uploadData(){
        $this->Cli->upload();
        return redirect('home');
     
    }
    public function send_invoice($sale_id)
    {
        $sale_data = $this->_load_sale_data($sale_id);

        $result = FALSE;
        $message = lang('sales_lang.sales_invoice_no_email');

        if (!empty($sale_data['customer_email'])) {
            $to = $sale_data['customer_email'];
            $subject = lang('sales_lang.sales_invoice') . ' ' . $sale_data['invoice_number'];

            $text = $this->appData->get('invoice_email_message');
            $text = str_replace('$INV', $sale_data['invoice_number'], $text);
            $text = str_replace('$CO', 'POS ' . $sale_data['sale_id'], $text);
            $text = $this->_substitute_customer($text, (object)$sale_data);

            // generate email attachment: invoice in pdf format
            $html = view('sales/invoice_email', $sale_data);
            // load pdf helper
            helper(array('dompdf', 'file'));
            $file_content = pdf_create($html, '', FALSE);
            $filename = sys_get_temp_dir() . '/' . lang('sales_lang.sales_invoice') . '-' . str_replace('/', '-', $sale_data['invoice_number']) . '.pdf';
            write_file($filename, $file_content);

            $result = $this->email_lib->sendEmail($to, $subject, $text, $filename);

            $message = lang($result ? 'sales_lang.sales_invoice_sent' : 'sales_lang.sales_invoice_unsent') . ' ' . $to;
        }

        echo json_encode(array('success' => $result, 'message' => $message, 'id' => $sale_id));

        $this->sale_lib->clear_all();

        return $result;
    }

    public function send_receipt($sale_id)
    {
        $sale_data = $this->_load_sale_data($sale_id);

        $result = FALSE;
        $message = lang('sales_lang.sales_receipt_no_email');

        if (!empty($sale_data['customer_email'])) {
            $data['bill_number'] = $this->gu->generateBillNumber();

            $data['barcode'] = $this->barcode_lib->generate_sale_barcode($data['bill_number']);
            //$sale_data['barcode'] = $this->barcode_lib->generate_receipt_barcode($sale_data['sale_id']);

            $to = $sale_data['customer_email'];
            $subject = lang('sales_lang.sales_receipt');

            $text = view('sales/receipt_email', $sale_data);

            $result = $this->email_lib->sendEmail($to, $subject, $text);

            $message = lang($result ? 'sales_lang.sales_receipt_sent' : 'sales_lang.sales_receipt_unsent') . ' ' . $to;
        }

        echo json_encode(array('success' => $result, 'message' => $message, 'id' => $sale_id));

        $this->sale_lib->clear_all();

        return $result;
    }

    private function _substitute_variable($text, $variable, $object, $function)
    {
        // don't query if this variable isn't used
        if (strstr($text, $variable)) {
            $value = call_user_func(array($object, $function));
            $text = str_replace($variable, $value, $text);
        }

        return $text;
    }

    private function _substitute_customer($text, $customer_info)
    {
        // substitute customer info
        $customer_id = $this->sale_lib->get_customer();
        if ( $customer_id= '' && $customer_id != -1 && $customer_info != '') {
            $text = str_replace('$CU', $customer_info->first_name . ' ' . $customer_info->last_name, $text);
            $words = preg_split("/\s+/", trim($customer_info->first_name . ' ' . $customer_info->last_name));
            $acronym = '';
            foreach ($words as $w) {
                $acronym .= $w[0];
            }
            $text = str_replace('$CI', $acronym, $text);
        }

        return $text;
    }

    private function _is_custom_invoice_number($customer_info)
    {
        $invoice_number = $this->appData->get('sales_invoice_format');
        $invoice_number = $this->_substitute_variables($invoice_number, $customer_info);

        return $this->sale_lib->get_invoice_number() != $invoice_number;
    }

    private function _substitute_variables($text, $customer_info)
    {
        $text = $this->_substitute_variable($text, '$YCO', $this->Sale, 'get_invoice_number_for_year');
        $text = $this->_substitute_variable($text, '$CO', $this->Sale, 'get_invoice_count');
        $text = $this->_substitute_variable($text, '$SCO', $this->Sale_suspended, 'get_invoice_count');
        $text = strftime($text);
        $text = $this->_substitute_customer($text, $customer_info);

        return $text;
    }

    private function _substitute_invoice_number($customer_info)
    {
        $invoice_number = $this->appData->get('sales_invoice_format');
        $invoice_number = $this->_substitute_variables($invoice_number, $customer_info);
        $this->sale_lib->set_invoice_number($invoice_number, TRUE);

        return $this->sale_lib->get_invoice_number();
    }

    private function _load_customer_data($customer_id, &$data, $totals = FALSE)
    {
        $customer_info = '';

        if ($customer_id != -1) {
            $customer_info = $this->Customer->get_info($customer_id);
            if (isset($customer_info->company_name)) {
                $data['customer'] = $customer_info->company_name;
            } else {
                $data['customer'] = $customer_info->first_name . ' ' . $customer_info->last_name;
            }
            $data['first_name'] = $customer_info->first_name;
            $data['last_name'] = $customer_info->last_name;
            $data['customer_email'] = $customer_info->email;
            $data['customer_address'] = $customer_info->address_1;
            if (!empty($customer_info->zip) or !empty($customer_info->city)) {
                $data['customer_location'] = $customer_info->zip . ' ' . $customer_info->city;
            } else {
                $data['customer_location'] = '';
            }
            $data['customer_account_number'] = $customer_info->account_number;
            $data['customer_discount_percent'] = $customer_info->discount_percent;
            if ($totals) {
                $cust_totals = $this->Customer->get_totals($customer_id);

                $data['customer_total'] = $cust_totals->total;
            }
            $data['customer_info'] = implode("\n", array(
                $data['customer'],
                $data['customer_address'],
                $data['customer_location'],
                $data['customer_account_number']
            ));
        }

        return $customer_info;
    }

    private function _load_sale_data($sale_id)
    {
        //$this->Sale->create_temp_table($this->gu->getReportingDb());
        $this->sale_lib->clear_all();
        $sale_info = $this->Sale->get_info($sale_id)->getRowArray();
        // print_r($sale_info);
        // exit();
        $this->sale_lib->copy_entire_sale($sale_id);
        $data = array();
        $data['cart'] = $this->sale_lib->get_cart();

        $data['payments'] = $this->sale_lib->get_payments();
        $data['subtotal'] = $this->sale_lib->get_subtotal();
        $data['discounted_subtotal'] = $this->sale_lib->get_subtotal(TRUE);
        $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(TRUE, TRUE);
        $data['taxes'] = $this->sale_lib->get_taxes();
        $data['fbr_fee'] = $sale_info['fbr_fee'];
        if (isset($data['fbr_fee'])) {
            $data['total'] = $this->sale_lib->get_total($data['fbr_fee'], "reciept");
            $data['amount_change'] = $this->sale_lib->get_amount_due($data['fbr_fee'], "reciept") * -1;
            $data['amount_due'] = $this->sale_lib->get_amount_due($data['fbr_fee'], "reciept");
        } else {
            $data['total'] = $this->sale_lib->get_total();
            $data['amount_change'] = $this->sale_lib->get_amount_due(FALSE, "reciept") * -1;
            $data['amount_due'] = $this->sale_lib->get_amount_due(FALSE, "reciept");
        }
        $data['discount'] = $this->sale_lib->get_discount();
        $data['receipt_title'] = lang('sales_lang.sales_receipt');
        $data['transaction_time'] = date($this->appData->get('dateformat') . ' ' . $this->appData->get('timeformat'), strtotime($sale_info['exact_time']));
        $data['transaction_date'] = date($this->appData->get('dateformat'), strtotime($sale_info['sale_time']));
        $data['show_stock_locations'] = $this->stockLocation->show_locations('sales');

        //$employee_info = $this->Employee->get_info($this->Employee->get_logged_in_employee_info()->person_id);
        $employee_info = $this->Employee->get_info($sale_info['employee_id']);
        $data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;
        $customer_info = $this->_load_customer_data($this->sale_lib->get_customer(), $data);

        $data['sale_id_num'] = $sale_id;
        $data['branch_code'] = $sale_info['branch_code'];
        $data['branch'] = $this->gu->getStoreInfoByBranchCode($data['branch_code']);

        $data['salreceipte_id'] = $data['branch_code'] . ' ' . $sale_id;

        $data['sale_mode'] = $sale_info['sale_type'];
        $data['payment_type'] = $sale_info['sale_payment'];


        $data['comments'] = $sale_info['comment'];
        $data['invoice_number'] = $sale_info['invoice_number'];
        $data['fbr_invoice_number'] = $sale_info['fbr_invoice_number'];
        $data['fbr_fee'] = $sale_info['fbr_fee'];

        if (file_exists('./uploads/qr_image/' . $data['fbr_invoice_number'] . '.png')) {
            $data['fbr_qrcode'] = $data['fbr_invoice_number'] . '.png';
        } else {
            $qr_image = $data['fbr_invoice_number'] . '.png';
            $params['data'] = $data['fbr_invoice_number'];
            $params['level'] = 'H';
            $params['size'] = 4;
            $params['savename'] = "./uploads/qr_image/" . $qr_image;
            $this->ciqrcode->generate($params);
            $data['fbr_qrcode'] = $data['fbr_invoice_number'] . '.png';
        }

        $data['company_info'] = implode("\n", array(
            $this->appData->get('address'),
            $this->appData->get('phone'),
            $this->appData->get('account_number')
        ));

        if ($data['invoice_number'] != "") {
            $data['bill_number'] = $data['invoice_number'];
        } else {
            $data['bill_number'] = $data['sale_id'];
        }
        $data['barcode'] = $this->barcode_lib->generate_sale_barcode($data['bill_number']);


        //$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['sale_id']);
        $data['print_after_sale'] = FALSE;

        return $this->xss_clean($data);
    }

    private function _reload($data = array(), $direct = false)
    {
        if(session()->has('cake_invoice')){
            $data['cake_invoice'] = session()->get('cake_invoice');
             }
        $data = $this->data;
        $data['cart'] = $this->sale_lib->get_cart();
        $data['modes'] = array('sale' => lang('sales_lang.sales_sale'), 'return' => lang('sales_lang.sales_return'));
        $data['mode'] = $this->sale_lib->get_mode();
        $data['stock_locations'] = $this->stockLocation->get_allowed_locations('sales');
        $data['stock_location'] = $this->sale_lib->get_sale_location();
        $data['subtotal'] = $this->sale_lib->get_subtotal(TRUE);
        $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(TRUE, TRUE);
        $data['taxes'] = $this->sale_lib->get_taxes();
        $data['discount'] = $this->sale_lib->get_discount();
        $data['total'] = $this->sale_lib->get_total();
        $data['comment'] = $this->sale_lib->get_comment();
        $data['email_receipt'] = $this->sale_lib->get_email_receipt();
        $data['payments_total'] = $this->sale_lib->get_payments_total();
        $data['amount_due'] = $this->sale_lib->get_amount_due();
        $data['payments'] = $this->sale_lib->get_payments();
        $data['payment_options'] = $this->Sale->get_payment_options();
        // $data['giftcard_checkout_limit'] = $this->appData->get('default_giftcard_limit');
        $data['items_module_allowed'] = $this->Employee->has_grant('items', session('person_id'));
        // $data['items_module_allowed'] = $this->Employee->has_grant('items', $this->Employee->get_logged_in_employee_info()->person_id);
        $data['items_returns_allowed'] = $this->Employee->has_grant('sales_returns', session('person_id'));
        // $data['items_returns_allowed'] = $this->Employee->has_grant('sales_returns', $this->Employee->get_logged_in_employee_info()->person_id);
        $data['customers_module_allowed'] = $this->Employee->has_grant('customers', session('person_id'));
        // $data['customers_module_allowed'] = $this->Employee->has_grant('customers', $this->Employee->get_logged_in_employee_info()->person_id);

        $customer_info = $this->_load_customer_data($this->sale_lib->get_customer(), $data, TRUE);
        $data['invoice_number'] = $this->_substitute_invoice_number($customer_info);
        $data['fbr_invoice_number'] = $this->sale_lib->get_fbr_invoice_number();
        $data['invoice_number_enabled'] = $this->sale_lib->is_invoice_number_enabled();
        $data['print_after_sale'] = $this->sale_lib->is_print_after_sale();
        $data['payments_cover_total'] = $this->sale_lib->get_amount_due() <= 0;

        $data['types'] = $this->gu->getSaleTypes();
        $data['type'] = $this->sale_lib->get_type();

        $data['payment_type'] = $this->sale_lib->get_payment_type();

        $suspended_sale = $this->Sale_suspended->get_last_sale();
        if (!isset($suspended_sale['sale_id'])) {
            $data['last_suspended_sale'] = null;
        } else {
            $data['last_suspended_sale'] = $suspended_sale['sale_id'];
        }

        $data['direct'] = $direct;

        if ($direct) {
            $data['bill_print'] = true;
        } else {
            $data['bill_print'] = false;
        }

        $data['last_total'] = $this->sale_lib->get_from_session('last_total');
        $data['last_amount_change'] = $this->sale_lib->get_from_session('last_amount_change');
        $data['last_amount_due'] = $this->sale_lib->get_from_session('last_amount_due');
        $data['last_payments_total'] = $this->sale_lib->get_from_session('last_payments_total');

        $data['last_receipt'] = $this->sale_lib->get_from_session('last_receipt');

        $data = $this->xss_clean($data);
        if (request()->isAJAX()) {
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
        $data['employee'] = $this->employeeModel;
        $data['sale_lib'] = $this->sale_lib;
        echo view("sales/register_partial", $data);
        } else {
        $data['sale_lib'] = $this->sale_lib;
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
        $data['employees'] = $this->Employee;
        
         echo view('sales/register', $data);

        }
    }

    public function receipt($sale_id)
    {
        $data = $this->_load_sale_data($sale_id);
        $data['receipt_view'] = true;
        $logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		$data['user_info'] = $logged_in_employee_info;
		$appdata = $this->appconfigModel->get_all();
		$data['appData']=$appdata;
        $data['sale_lib'] = $this->sale_lib;

        $data['gu'] = $this->gu;
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data['controller_name'] = 'sales';
        $this->sale_lib->clear_all();

        return view('sales/receipt', $data);


    }

    public function invoice($sale_id)
    {
        
        $data = $this->_load_sale_data($sale_id);
        $this->sale_lib->clear_all();
        return view('sales/invoice', $data);

    }

    public function edit($sale_id)
    {
        
        $data = array();

        $data['employees'] = array();
        foreach ($this->Employee->get_all()->getResult() as $employee) {
            foreach (get_object_vars($employee) as $property => $value) {
                $employee->$property = $this->xss_clean($value);
            }

            $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
        }

        //$this->Sale->create_temp_table();

        $sale_info = $this->xss_clean($this->Sale->get_info($sale_id)->getRowArray());
        if($sale_info['customer_name']==null){
            $sale_info['customer_name']='';
        }
        $data['selected_customer_name'] = $sale_info['customer_name'];
        if($sale_info['customer_id']==null){
            $sale_info['customer_id']='';
        }
        $data['selected_customer_id'] = $sale_info['customer_id'];
        $data['sale_info'] = $sale_info;

        $data['payments'] = array();
        foreach ($this->Sale->get_sale_payments($sale_id)->getResult() as $payment) {
            foreach (get_object_vars($payment) as $property => $value) {
                $payment->$property = $this->xss_clean($value);
            }

            $data['payments'][] = $payment;
        }

        // don't allow gift card to be a payment option in a sale transaction edit because it's a complex change
        $data['payment_options'] = $this->xss_clean($this->Sale->get_payment_options(FALSE));

        $logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		$data['user_info'] = $logged_in_employee_info;
		$appdata = $this->appconfigModel->get_all();
		$data['appData']=$appdata;


        $data['gu'] = $this->gu;
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data['controller_name'] = 'sales';
        

        return view('sales/form', $data);
    }

    public function delete($sale_id = -1, $update_inventory = TRUE)
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $sale_ids = $sale_id == -1 ? request()->getPost('ids') : array($sale_id);

        if ($this->Sale->delete_list($sale_ids, $employee_id, $update_inventory)) {
            echo json_encode(array('success' => TRUE, 'message' => lang('sales_lang.sales_successfully_deleted') . ' ' .
                count($sale_ids) . ' ' . lang('sales_lang.sales_one_or_multiple'), 'ids' => $sale_ids));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => lang('sales_lang.sales_unsuccessfully_deleted')));
        }
    }

    public function save($sale_id = -1)
    {
        $newdate = request()->getVar('date');

        $date_formatter = date_create_from_format($this->appData->get('dateformat') . ' ' . $this->appData->get('timeformat'), $newdate);

        $sale_data = array(
            'sale_time' => $date_formatter->format('Y-m-d H:i:s'),
            'customer_id' => request()->getPost('customer_id') != '' ? request()->getPost('customer_id') : NULL,
            'employee_id' => request()->getPost('employee_id'),
            'comment' => request()->getPost('comment'),
            //TODO - FIX THIS INVOICE NUMBER
            'invoice_number' => request()->getPost('invoice_number') != '' ? request()->getPost('invoice_number') : NULL
        );

        // go through all the payment type input from the form, make sure the form matches the name and iterator number
        $payments = array();
        $number_of_payments = request()->getPost('number_of_payments');
        for ($i = 0; $i < $number_of_payments; ++$i) {
            $payment_amount = request()->getPost('payment_amount_' . $i);
            $payment_type = request()->getPost('payment_type_' . $i);
            // remove any 0 payment if by mistake any was introduced at sale time
            if ($payment_amount != 0) {
                // search for any payment of the same type that was already added, if that's the case add up the new payment amount
                $key = FALSE;
                if (!empty($payments)) {
                    // search in the multi array the key of the entry containing the current payment_type
                    // NOTE: in PHP5.5 the array_map could be replaced by an array_column
                    $key = array_search($payment_type, array_map(function ($v) {
                        return $v['payment_type'];
                    }, $payments));
                }

                // if no previous payment is found add a new one
                if ($key === FALSE) {
                    $payments[] = array('payment_type' => $payment_type, 'payment_amount' => $payment_amount);
                } else {
                    // add up the new payment amount to an existing payment type
                    $payments[$key]['payment_amount'] += $payment_amount;
                }
            }
        }
        if ($this->Sale->update_sale($sale_id, $sale_data, $payments)) {
            echo json_encode(array('success' => TRUE, 'message' => lang('sales_lang.sales_successfully_updated'), 'id' => $sale_id));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => lang('sales_lang.sales_unsuccessfully_updated'), 'id' => $sale_id));
        }
    }

    public function cancel($message = null)
    {
        $this->sale_lib->clear_all();

        //return view('sales/register', $data);

        $url = "sales";
        if ($message) {
            $url .= "?message=$message";
        }

    //   return redirect($url, 'refresh');
           return redirect()->to('sales');
        // $this->_reload($data);
    }

    public function suspend()
    {
        $cart = $this->sale_lib->get_cart();
        $payments = $this->sale_lib->get_payments();
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        $customer_id = $this->sale_lib->get_customer();
        $customer_info = $this->Customer->get_info($customer_id);
        $invoice_number = $this->_is_custom_invoice_number($customer_info) ? $this->sale_lib->get_invoice_number() : null;
        $comment = $this->sale_lib->get_comment();

        //SAVE sale to database
        $data = array();
        if ($this->Sale_suspended->saveSaleSuspended($cart, $customer_id, $employee_id, $comment, $invoice_number, $payments) == '-1') {
            $data['error'] = lang('sales_lang.sales_unsuccessfully_suspended_sale');
        } else {
            $data['success'] = lang('sales_lang.sales_successfully_suspended_sale');
        }

        $this->sale_lib->clear_all();

       return $this->_reload($data);
    }

    public function suspended()
    {
        $data = array();
        $data['suspended_sales'] = $this->xss_clean($this->Sale_suspended->get_all()->getResultArray());

        return view('sales/suspended', $data);
    }

    public function suspendedWithItems()
    {
        $data = array();
        $suspended_sales = $this->Sale_suspended->get_all()->getResult();

        foreach ($suspended_sales as $key => $sale) {
            $suspended_sales[$key]->items = $this->Sale_suspended->get_sale_items_info($sale->sale_id)->getResult();
        }


        $data['suspended_sales'] = $this->xss_clean($suspended_sales);

        return view('sales/suspended_sales', $data);
    }

    
    public function cakeSuspendedWithItems()
    {
        $data = array();
        $suspended_sales = $this->Cake_suspended->get_all()->getResult();
        foreach ($suspended_sales as $key => $sale) {
            $suspended_sales[$key]->items = $this->Cake_suspended->get_sale_items_info($sale->sale_id)->getResult();
        }

        $data['suspended_sales'] = $this->xss_clean($suspended_sales);

        return view('sales/cake_suspended_sales', $data);
    }

    public function cakeSuspendedSearch(){

            $search = $this->request->getGet('search');
            $suspended_sales = $this->Cake_suspended->search($search)->getResult();
             foreach ($suspended_sales as $key => $sale) {
            $suspended_sales[$key]->items = $this->Cake_suspended->get_sale_items_info($sale->sale_id)->getResult();
            }

           $data['suspended_sales'] = $this->xss_clean($suspended_sales);
           
            echo json_encode($data);

    }

    public function shortcuts()
    {
        return view('partial/shortcuts');
    }

    public function unsuspend()
    {
        $sale_id = request()->getPost('suspended_sale_id');

        $this->sale_lib->clear_all();
        $this->sale_lib->copy_entire_suspended_sale($sale_id);
        $this->Sale_suspended->deleteSaleSuspended($sale_id);
        $this->_reload();
        
    }

    public function check_invoice_number()
    {
        $sale_id = request()->getPost('sale_id');
        $invoice_number = request()->getPost('invoice_number');
        $exists = !empty($invoice_number) && $this->Sale->invoice_number_exists($invoice_number, $sale_id);

        echo !$exists ? 'true' : 'false';
    }


    public function cakeUnsuspend($cake_sale_id = '')
    {
        if($cake_sale_id !== ''){
            $sale_id = $cake_sale_id;
        }else{
            $sale_id = request()->getPost('suspended_sale_id');
        }
        $this->sale_lib->clear_all();
        $this->sale_lib->copy_entire_cake_suspended_sale($sale_id);
        $cake_suspended_info = $this->Cake_suspended->get_cake_invoice($sale_id);
        $cake_invoice = $cake_suspended_info->cake_invoice;
        $second_payment = $cake_suspended_info->second_payment;
        session()->set([
            'cake_invoice'=> $cake_invoice,
            'second_payment'=> $second_payment
         ]);
        $this->Cake_suspended->delete_cake_suspended($sale_id);
        $this->_reload();
    }

     public function get_prev_reciept_data($cake_invoice){

        $cake_invoice_exist = $this->Sale->online_db_search_sale($cake_invoice);
      

        if($cake_invoice_exist->getNumRows() > 0){
            
             $sales_info = $this->Sale->online_db_search_sale($cake_invoice)->getResult();
 
           foreach ($sales_info as $key => $sale_info) {

              $sale_id = $sale_info->sale_id;
              $data[$key] = $this->_load_sale_data($sale_id);

          }
        
          $this->Cake_suspended->update_order_status($cake_invoice);
        

         return $this->xss_clean($data);

    }
  }


    public function pizzaSuspendedWithItems()
    {
        $data = array();
        $pizza_completed_order = new Pizza_completed_order();
        $completed_orders = $pizza_completed_order->get_all()->getResult();
        foreach ($completed_orders as $key => $order) {
            $completed_orders[$key]->items = $pizza_completed_order->get_sale_items_info($order->sale_id)->getResult();
        }

        $data['completed_orders'] = $this->xss_clean($completed_orders);

        return view('sales/pizza_order_completed', $data);
    }


    public function pizzaCompletedOrderSearch(){
        $search = $this->request->getGet('search');
        $pizza_completed_order = new Pizza_completed_order();
        $completed_orders = $pizza_completed_order->search($search)->getResult();
         foreach ($completed_orders as $key => $sale) {
        $completed_orders[$key]->items = $pizza_completed_order->get_sale_items_info($sale->sale_id)->getResult();
        }

       $data['completed_orders'] = $this->xss_clean($completed_orders);
       
        echo json_encode($data);

}

public function pizzaOrderUnsuspend($pizza_sale_id = '')
{
    if($pizza_sale_id !== ''){
        $sale_id = $pizza_sale_id;
    }else{
        $sale_id = request()->getPost('pizza_sale_id');
    }
    
    $this->sale_lib->clear_all();
    $this->sale_lib->copy_entire_pizza_order($sale_id);
    $pizza_completed_order = new Pizza_completed_order();
    $pizza_completed_order->delete_pizza_completed_order($sale_id);
    $this->_reload();
}


}


