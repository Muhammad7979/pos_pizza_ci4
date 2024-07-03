<?php

// use App\Libraries\AppData;
use App\Models\Appconfig;
use App\Models\Employee;
use App\Models\Item_taxes;
use App\Models\Store;

function get_sales_manage_table_headers()
{
   

    $headers = array(
        array('sale_id' => lang('common_lang.common_id')),
        array('sale_type' => 'Sale'),
        array('branch_code' => lang('common_lang.sale_branch_code')),
        array('sale_time' => lang('sales_lang.sales_sale_time')),
        array('exact_time' => 'Time'),
        //array('customer_name' => lang('customers_customer')),
        array('amount_tendered' => lang('sales_lang.sales_amount_tendered')),
        array('amount_due' => lang('sales_lang.sales_amount_due')),
        array('change_due' => lang('sales_lang.sales_change_due')),
        array('payment_type' => lang('sales_lang.sales_payment_type')),
        array('bill_number' => 'Bill Number'),
    );
     $appData =new Appconfig();
    if ($appData->get('invoice_enable') == TRUE) {
        $headers[] = array('invoice_number' => lang('sales_lang.sales_invoice_number'));
        $headers[] = array('invoice' => '&nbsp', 'sortable' => FALSE);
    }

    return transform_headers(array_merge($headers, array(array('receipt' => '&nbsp', 'sortable' => FALSE))));
}

/*
 Gets the html data rows for the sales.
 */
function get_sale_data_last_row($sales, $controller)
{
   
    $table_data_rows = '';
    $sum_amount_tendered = 0;
    $sum_amount_due = 0;
    $sum_change_due = 0;

    foreach ($sales->getResult() as $key => $sale) {
        $sum_amount_tendered += $sale->amount_tendered;
        $sum_amount_due += $sale->amount_due;
        $sum_change_due += $sale->change_due;
    }

    return array(
        'sale_id' => '-',
        'sale_time' => '<b>' . lang('sales_total') . '</b>',
        //'amount_tendered' => '<b>' . to_currency($sum_amount_tendered) . '</b>',
        'amount_due' => '<b>' . to_currency($sum_amount_due) . '</b>',
        //'change_due' => '<b>' . to_currency($sum_change_due) . '</b>'
    );
}

function get_sale_data_row($sale, $controller)
{
   
    // $controller_name = 'sales';
    $controller_name = service('uri')->getSegment(1);

    $sale_date_time = $sale->exact_time;

    if (is_null($sale_date_time)) {
        $sale_date_time = $sale->sale_date;
    } else {
        //$sale_date_time = date( $CI->config->item('dateformat') . ' ' . $CI->config->item('timeformat'), strtotime($sale->exact_time) );
        $sale_date = date('Y-m-d', strtotime($sale->exact_time));

        $today = date('Y-m-d');
        $appData = new Appconfig();
        if ($sale->sale_date == $sale_date) {
            $sale_date_time = date($appData->get('timeformat'), strtotime($sale->exact_time));
        } else {
            $sale_date_time = date($appData->get('dateformat') . ' ' . $appData->get('timeformat'), strtotime($sale->exact_time));
        }


    }

    $amount_due = to_currency($sale->amount_due);
    $sale_type = $sale->sale_type;
    $payment_type = $sale->payment_type;
    if($amount_due < 1){
        $sale_type .=" refund";
        $payment_type = $sale->sale_payment;
    }


    $row = array(
        'sale_id' => $sale->sale_id,
        'sale_type' => $sale_type,
        'branch_code' => $sale->branch_code,
        'exact_time' => $sale_date_time,
        'sale_time' => date($appData->get('dateformat'), strtotime($sale->sale_time)),
        //'customer_name' => $sale->customer_name,
        'amount_tendered' => to_currency($sale->amount_tendered),
        'amount_due' => to_currency($sale->amount_due),
        'change_due' => to_currency($sale->change_due),
        'payment_type' => $payment_type,
        'bill_number' => $sale->invoice_number,
    );

    if ($appData->get('invoice_enable')) {
        $row['invoice_number'] = $sale->invoice_number;
        $row['invoice'] = empty($sale->invoice_number) ? '' : anchor($controller_name . "/invoice/$sale->sale_id", '<span class="glyphicon glyphicon-list-alt"></span>',
            array('title' => lang('sales_show_invoice'))
        );
    }

    $row['receipt'] = anchor($controller_name . "/receipt/$sale->sale_id", '<span class="glyphicon glyphicon-usd"></span>',
        array('title' => lang('sales_show_receipt'))
    );
    $row['edit'] = anchor($controller_name . "/edit/$sale->sale_id", '<span class="glyphicon glyphicon-edit"></span>',
        array('class' => "modal-dlg print_hide", 'data-btn-delete' => lang('common_lang.common_delete'), 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('sales_lang.' . $controller_name . '_update'))
    );

    return $row;
}

/*
Get the sales payments summary
*/
function get_sales_manage_payments_summary($payments, $sales, $controller)
{
    //return (string)$sales;
   
    $table = '<div id="report_summary">';

//    echo "<pre>";
//    print_r($payments);
//    echo "</pre>";
//    exit;

    foreach ($payments as $key => $payment) {
        $amount = $payment['payment_amount'];

        // WARNING: the strong assumption here is that if a change is due it was a cash transaction always
        // therefore we remove from the total cash amount any change due
        if ($payment['payment_type'] == lang('sales_lang.sales_cash')) {
            //return "";
            foreach ($sales->result_array() as $key => $sale) {
                $amount -= $sale['change_due'];
            }
        }
        $table .= '<div class="summary_row">' . $payment['payment_type'] . ': ' . to_currency($amount) . '</div>';
    }
    $table .= '</div>';

    return $table;
}

function transform_headers_readonly($array)
{
    
    $result = array();
    foreach ($array as $key => $value) {
        $result[] = array('field' => $key, 'title' => $value, 'sortable' => $value != '', 'switchable' => !preg_match('(^$|&nbsp)', $value));
    }
    return json_encode($result);
}

function transform_headers($array)
{
    $result = array();
    $array = array_merge(array(array('checkbox' => 'select', 'sortable' => FALSE)),
        $array, array(array('edit' => '')));
    foreach ($array as $element) {
        $result[] = array('field' => key($element),
            'title' => current($element),
            'switchable' => isset($element['switchable']) ?
                $element['switchable'] : !preg_match('(^$|&nbsp)', current($element)),
            'sortable' => isset($element['sortable']) ?
                $element['sortable'] : current($element) != '',
            'checkbox' => isset($element['checkbox']) ?
                $element['checkbox'] : FALSE,
            'class' => isset($element['checkbox']) || preg_match('(^$|&nbsp)', current($element)) ?
                'print_hide' : '');
    }
    return json_encode($result);
}

function get_people_manage_table_headers()
{
    // $CI =& get_instance();

    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('email' => lang('common_lang.common_email')),
        array('phone_number' => lang('common_lang.common_phone_number'))
    );
       $Employee = new Employee();
    if ($Employee->has_grant('messages', session()->get('person_id'))) {
        $headers[] = array('messages' => '', 'sortable' => false);
    }

    return transform_headers($headers);
}

function get_person_data_row($person, $controller)
{
    // $CI =& get_instance();
    // $controller_name = strtolower('persons');
    $controllerPath = strtolower(service('router')->controllerName());
    $controller_name = substr(strrchr($controllerPath, '\\'), 1);
    return array(
        'people.person_id' => $person->person_id,
        'last_name' => $person->last_name,
        'first_name' => $person->first_name,
        'email' => empty($person->email) ? '' : mailto($person->email, $person->email),
        'phone_number' => $person->phone_number,
        'messages' => empty($person->phone_number) ? '' : anchor("Messages/view/$person->person_id", '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('messages_sms_send'))),
        'edit' => anchor($controller_name . "/view/$person->person_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('employees_lang.'.$controller_name . '_update'))
        ),
        'biometric' => anchor($controller_name . "/biometric/$person->person_id", '<span class="glyphicon glyphicon-fingerprint"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => 'Employees Biometric')
        )
    );
//     return [
//     'people.person_id' => $person->person_id,
//     'last_name' => $person->last_name,
//     'first_name' => $person->first_name,
//     'email' => empty($person->email) ? '' : mailto($person->email, $person->email),
//     'phone_number' => $person->phone_number,
//     'messages' => empty($person->phone_number) ? '' : anchor("Messages/view/$person->person_id", '<span class="glyphicon glyphicon-phone"></span>',
//         ['class' => 'modal-dlg', 'data-btn-submit' => lang('lang_common.common_submit'), 'title' => lang('messages_sms_send')]
//     ),
//     'edit' => anchor("$controller_name/view/$person->person_id", '<span class="glyphicon glyphicon-edit"></span>',
//         ['class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('employees_lang.'.$controller_name . '_update')]
//     ),
//     'biometric' => anchor("$controller_name/biometric/$person->person_id", '<span class="glyphicon glyphicon-fingerprint"></span>',
//         ['class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => 'Employees Biometric']
//     )
// ];

}

function get_suppliers_manage_table_headers()
{


    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('company_name' => lang('suppliers_lang.suppliers_company_name')),
        array('agency_name' => lang('suppliers_lang.suppliers_agency_name')),
        array('account_number' => lang('suppliers_lang.suppliers_account_number')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('last_name' => lang('common_lang.common_last_name')),
        // array('phone_number' => lang('common_lang.common_phone_number'))
    );

    // if ($CI->Employee->has_grant('messages', $CI->session->userdata('person_id'))) {
    //     $headers[] = array('messages' => '');
    // }

    return transform_headers($headers);
}

function get_supplier_data_row($supplier, $controller)
{

    // $controller_name = strtolower(get_class($CI));
    $controller_name = 'suppliers';

    return array(
        'people.person_id' => $supplier->id,
        'company_name' => $supplier->company_name,
        'agency_name' => $supplier->agency_name,
        'account_number' => $supplier->account_number,

        'first_name' => $supplier->first_name,
        'last_name' => $supplier->last_name,
        // 'email' => empty($supplier->email) ? '' : mailto($supplier->email, $supplier->email),
        // 'phone_number' => $supplier->phone_number,
        'messages' => empty($supplier->phone_number) ? '' : anchor(
            "Messages/view/$supplier->person_id",
            '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('messages_sms_send'))
        ),
        'edit' => anchor(
            $controller_name . "/view/$supplier->id",
            '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name . '_update'))
        )
    );
}


function get_items_manage_table_headers()
{
   

    $headers = array(
        array('items.item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('items_lang.items_item_number')),
        array('name' => lang('items_lang.items_name')),
        array('category' => lang('items_lang.items_category')),
        array('company_name' => lang('suppliers_lang.suppliers_company_name')),
        array('cost_price' => lang('items_lang.items_cost_price')),
        array('unit_price' => lang('items_lang.items_unit_price')),
        array('quantity' => lang('items_lang.items_quantity')),
        array('allow_discount' => 'Discount'),
        array('tax_percents' => lang('items_lang.items_tax_percents'), 'sortable' => FALSE),
        array('item_pic' => lang('items_lang.items_image'), 'sortable' => FALSE),
        array('inventory' => ''),
        array('stock' => '')
    );

    return transform_headers($headers);
}

function get_item_data_row($item, $controller)
{
   $Item_taxes = new Item_taxes();
    $item_tax_info = $Item_taxes->get_info($item->item_id);
    $tax_percents = '';
    foreach ($item_tax_info as $tax_info) {
        $tax_percents .= to_tax_decimals($tax_info['percent']) . '%, ';
    }
    // remove ', ' from last item
    $tax_percents = substr($tax_percents, 0, -2);
    $controller_name = strtolower('items');

    $image = '';
   
    return [
        'items.item_id' => $item->item_id,
        'item_number' => $item->item_number,
        'name' => $item->name,
        'category' => $item->category,
        'company_name' => $item->company_name,
        'cost_price' => to_currency($item->cost_price),
        'unit_price' => to_currency($item->unit_price),
        'quantity' => to_quantity_decimals($item->quantity),
        'allow_discount' => $item->custom1,
        'tax_percents' => !$tax_percents ? '-' : $tax_percents,
        'item_pic' => ($item->pic_id !== null) ? '<img width="50" height="50" src="' . base_url('uploads/item_pics/' . $item->pic_id) . '"/>' : 'No Image',
        'inventory' => anchor(
            $controller_name . "/inventory/$item->item_id", 
            '<span class="glyphicon glyphicon-pushpin"></span>',
            ['class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('items_lang.' .$controller_name . '_count')]
        ),
        'stock' => anchor($controller_name . "/count_details/$item->item_id", '<span class="glyphicon glyphicon-list-alt"></span>',
            array('class' => 'modal-dlg','data-btn-submit' => lang('common_lang.common_close'), 'title' => lang('items_lang.' .$controller_name . '_details_count'))
        ),
        'edit' => anchor($controller_name . "/view/$item->item_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('items_lang.' .$controller_name . '_update'))
        )
    ];
}

function get_giftcards_manage_table_headers()
{
   

    $headers = array(
        array('giftcard_id' => lang('common_lang.common_id')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('giftcard_number' => lang('giftcards_lang.giftcards_giftcard_number')),
        array('value' => lang('giftcards_lang.giftcards_card_value')),
        array('expired' => lang('giftcards_lang.giftcards_card_expired')),
        array('status' => lang('giftcards_lang.giftcards_card_status'))
    );

    return transform_headers($headers);
}

function get_giftcard_data_row($giftcard, $controller)
{
   
    $controller_name = 'giftcards';

    $status = '';
    if(strtotime('now') > strtotime($giftcard->expires_at)) {
        $status = '<label class="label label-danger">Expired</label>';
    }elseif($giftcard->status == 1){
        $status = '<label class="label label-success">Used</label>';
    }else{
        $status = '<label class="label label-info">Un-Used</label>';
    }

    return array(
        'giftcard_id' => $giftcard->giftcard_id,
        'last_name' => $giftcard->last_name,
        'first_name' => $giftcard->first_name,
        'giftcard_number' => $giftcard->giftcard_number,
        'value' => to_currency($giftcard->value),
        'expired' => $giftcard->expires_at,
        'status' => $status,
        'edit' => anchor($controller_name . "/view/$giftcard->giftcard_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('Update'.' '.ucfirst($controller_name)))
        ));
}

function get_item_kits_manage_table_headers()
{
   

    $headers = [
        array('item_kit_id' => lang('item_kits_lang.item_kits_kit')),
        array('name' => lang('item_kits_lang.item_kits_name')),
        array('description' => lang('item_kits_lang.item_kits_description')),
        array('cost_price' => lang('items_lang.items_cost_price'), 'sortable' => FALSE),
        array('unit_price' => lang('items_lang.items_unit_price'), 'sortable' => FALSE)
    ];

    return transform_headers($headers);
}

function get_item_kit_data_row($item_kit, $controller)
{
    $controller_name = strtolower('item_kits');

    return [
        'item_kit_id' => $item_kit->item_kit_id,
        'name' => $item_kit->name,
        'description' => $item_kit->description,
        'cost_price' => to_currency($item_kit->total_cost_price),
        'unit_price' => to_currency($item_kit->total_unit_price),
        'edit' => anchor(
            $controller_name . "/view/" . $item_kit->item_kit_id,
            '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('item_kits_lang.' . $controller_name . '_update'))
        )
    ];
}

function get_warehouses_manage_table_headers()
{

    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('company_name' => lang('warehouses_lang.warehouses_company_name')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('email' => lang('common_lang.common_email')),
        array('phone_number' => lang('common_lang.common_phone_number'))
    );

    $Employee = new Employee(); 
    if ($Employee->has_grant('messages', session()->get('person_id'))) {
        $headers[] = array('messages' => '');
    }

    return transform_headers($headers);
}

function get_warehouses_data_row($warehouse, $controller)
{
    $controller_name = strtolower('warehouses');

    return array(
        'people.person_id' => $warehouse->person_id,
        'company_name' => $warehouse->company_name,
        'agency_name' => $warehouse->agency_name,
        'last_name' => $warehouse->last_name,
        'first_name' => $warehouse->first_name,
        'email' => empty($warehouse->email) ? '' : mailto($warehouse->email, $warehouse->email),
        'phone_number' => $warehouse->phone_number,
        'messages' => empty($warehouse->phone_number) ? '' : anchor("Messages/view/$warehouse->person_id", '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('messages_lang.messages_sms_send'))),
        'edit' => anchor($controller_name . "/view/$warehouse->person_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('warehouses_lang.'.$controller_name . '_update')))
    );
}


function get_stores_manage_table_headers()
{

    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('company_name' => lang('stores_lang.stores_company_name')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('email' => lang('common_lang.common_email')),
        array('phone_number' => lang('common_lang.common_phone_number'))
    );

    $Employee = new Employee();
    if ($Employee->has_grant('messages', session()->get('person_id'))) {
        $headers[] = array('messages' => '');
    }

    return transform_headers($headers);
}

function get_stores_data_row($store, $controller)
{
    $controller_name = strtolower('stores');
    return array(
        'people.person_id' => $store->person_id,
        'company_name' => $store->company_name,
        'agency_name' => $store->agency_name,
        'last_name' => $store->last_name,
        'first_name' => $store->first_name,
        'email' => empty($store->email) ? '' : mailto($store->email, $store->email),
        'phone_number' => $store->phone_number,
        'messages' => empty($store->phone_number) ? '' : anchor("Messages/view/$store->person_id", '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('messages_lang.messages_sms_send'))),
        'edit' => anchor($controller_name . "/view/$store->person_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('stores_lang.'.$controller_name . '_update')))
    );
}

function get_categories_manage_table_headers()
{
    

    $headers = array(
        array('item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('categories_lang.category_number')),
        array('name' => lang('categories_lang.category_name')),
        array('category' => lang('categories_lang.category_title')),
        array('category_attribute' => lang('categories_lang.items_attribute_size'), 'sortable' => FALSE),
        array('item_pic' => lang('items_lang.items_image'), 'sortable' => FALSE),
        // array('category_extra_attribute' => lang('items_attribute_extra'), 'sortable' => FALSE),
        // array('description' => lang('category_description')),
    );

    return transform_headers($headers);
}

function get_category_data_row($category, $controller)
{
    $controller_name = strtolower('categories');
    $image = '';
    if (!empty($category->pic_id)) {
              
                 helper(['filesystem']);
                 $uploadPath = './uploads/item_pics/';
                 $map = directory_map($uploadPath, 1);
                $files = scandir(APPPATH.'../public/uploads/item_pics/');
                // Iterate over the files to find the matching filename with any extension
                foreach ($map as $file) {
                    if (pathinfo($file, PATHINFO_FILENAME) === $category->pic_id.'_thumb') {
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                    }else{
                        $extension = 'png'; 
                    }
                }

        $images = glob("uploads/item_pics/" . $category->pic_id . ".*");
        if (sizeof($images) > 0) {
            $image .= '<a class="rollover" href="' . base_url($images[0]) . '"><img src="' . base_url('uploads/item_pics/' . $category->pic_id.'_thumb.'.$extension) . '"></a>';
        }
    }

    return array(
        'item_id' => $category->item_id,
        'item_number' => $category->item_number,
        'name' => $category->name,
        'category' => $category->category,
        'category_attribute' => $category->category_attribute,
        // 'category_extra_attribute' => $category->category_extra_attribute,
        // 'description' => $category->description,
        'item_pic' => $image,
        'edit' => anchor($controller_name . "/view/$category->item_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('categories_lang.'.$controller_name . '_update'))
        ));
}

function get_vendors_manage_table_headers()
{

    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('company_name' => lang('vendors_lang.vendors_company_name')),
        array('agency_name' => lang('vendors_lang.vendors_agency_name')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('email' => lang('common_lang.common_email')),
        array('phone_number' => lang('common_lang.common_phone_number'))
    );

    $Employee = new Employee();
    if ($Employee->has_grant('messages', session()->get('person_id'))) {
        $headers[] = array('messages' => '');
    }

    return transform_headers($headers);
}

function get_vendor_data_row($vendor, $controller)
{
    $controller_name = strtolower('vendors');

    return array(
        'people.person_id' => $vendor->person_id,
        'company_name' => $vendor->company_name,
        'agency_name' => $vendor->agency_name,
        'last_name' => $vendor->last_name,
        'first_name' => $vendor->first_name,
        'email' => empty($vendor->email) ? '' : mailto($vendor->email, $vendor->email),
        'phone_number' => $vendor->phone_number,
        'messages' => empty($vendor->phone_number) ? '' : anchor("Messages/view/$vendor->person_id", '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('messages_lang.messages_sms_send'))),
        'edit' => anchor($controller_name . "/view/$vendor->person_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name.'_lang.'.$controller_name . '_update')))
    );
}

function get_vendor_items_manage_table_headers()
{
    $headers = array(
        array('raw_items.item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('items_lang.items_item_number')),
        array('name' => lang('items_lang.items_name')),
        array('category' => lang('items_lang.items_category')),
        array('raw_items.vendor_id' => lang('vendors_lang.vendors_company_name')),
        array('cost_price' => lang('items_lang.items_cost_price')),
        array('quantity' => lang('items_lang.items_quantity')),
        array('item_pic' => lang('items_lang.items_image'), 'sortable' => FALSE),
        // array('inventory' => ''),
        // array('stock' => '')
    );

    return transform_headers($headers);
}


function get_vendor_item_data_row($item, $controller)
{
    $controller_name = strtolower('vendor_items');

    $image = '';
    if (!empty($item->pic_id)) {
        $images = glob("uploads/item_pics/" . $item->pic_id . ".*");
        if (sizeof($images) > 0) {
            $image .= '<a class="rollover" href="' . base_url($images[0]) . '"><img src="' . base_url('uploads/item_pics/' . $item->pic_id.'_thumb.jpg') . '"></a>';
            // $image .= '<a class="rollover" href="' . base_url($images[0]) . '"><img src="' . base_url('raw_items/pic_thumb/' . $item->pic_id) . '"></a>';
        }
    }

    return array(
        'raw_items.item_id' => $item->item_id,
        'item_number' => $item->item_number,
        'name' => $item->name,
        'category' => $item->category,
        'raw_items.vendor_id' => $item->company_name,
        'cost_price' => to_currency($item->cost_price),
        'quantity' => to_quantity_decimals($item->quantity),
        'item_pic' => $image,
        // 'inventory' => anchor($controller_name . "/inventory/$item->item_id", '<span class="glyphicon glyphicon-pushpin"></span>',
        //     array('class' => 'modal-dlg', 'data-btn-submit' => $CI->lang->line('common_submit'), 'title' => $CI->lang->line($controller_name . '_count'))
        // ),
        // 'stock' => anchor($controller_name . "/count_details/$item->item_id", '<span class="glyphicon glyphicon-list-alt"></span>',
        //     array('class' => 'modal-dlg', 'title' => $CI->lang->line($controller_name . '_details_count'))
        // ),
        'edit' => anchor($controller_name . "/view/$item->item_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name.'_lang.'.$controller_name . '_update'))
        ));
}

function get_raw_items_manage_table_headers()
{

    $headers = array(
        array('raw_items.item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('items_lang.items_item_number')),
        array('name' => lang('items_lang.items_name')),
        array('category' => lang('items_lang.items_category')),
        // array('warehouse' => lang('warehouses_company_name')),
        array('cost_price' => lang('items_lang.items_cost_price')),
        array('quantity' => lang('items_lang.items_quantity')),
        array('item_pic' => lang('items_lang.items_image'), 'sortable' => FALSE),
        array('inventory' => ''),
        array('stock' => '')
    );

    return transform_headers($headers);
}

function get_raw_item_data_row($item, $controller)
{
    $Item_taxes = new Item_taxes();
    $item_tax_info = $Item_taxes->get_info($item->item_id);
    $tax_percents = '';
    foreach ($item_tax_info as $tax_info) {
        $tax_percents .= to_tax_decimals($tax_info['percent']) . '%, ';
    }
    // remove ', ' from last item
    $tax_percents = substr($tax_percents, 0, -2);
    $controller_name = strtolower('raw_items');

    $image = '';
    if (!empty($item->pic_id)) {

        $images = glob("uploads/item_pics/" . $item->pic_id . ".*");
        if (sizeof($images) > 0) {
            $image .= '<a class="rollover" href="' . base_url($images[0]) . '"><img src="' . base_url('raw_items/pic_thumb/' . $item->pic_id) . '"></a>';
        }
    }

    return array(
        'raw_items.item_id' => $item->item_id,
        'item_number' => $item->item_number,
        'name' => $item->name,
        'category' => $item->category,
        // 'warehouse' => $item->company_name,
        'cost_price' => to_currency($item->cost_price),
        'quantity' => to_quantity_decimals($item->quantity),
        'item_pic' => $image,
        'inventory' => anchor($controller_name . "/inventory/$item->item_id", '<span class="glyphicon glyphicon-pushpin"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name.'_lang.'.$controller_name . '_count'))
        ),
        'stock' => anchor($controller_name . "/count_details/$item->item_id", '<span class="glyphicon glyphicon-list-alt"></span>',
            array('class' => 'modal-dlg', 'title' => lang($controller_name.'_lang.'.$controller_name . '_details_count'))
        ),
        'edit' => anchor($controller_name . "/view/$item->item_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name.'_lang.'.$controller_name . '_update'))
        ));
}

function get_raw_orders_manage_table_headers()
{

    $headers = array(
        array('order_id' =>lang('common_lang.common_id')),
        array('created_at' =>lang('raw_order_lang.raw_orders_date')),
        array('description' =>lang('raw_order_lang.raw_orders_description')),
        array('category' =>lang('raw_order_lang.raw_orders_category')),
        array('person_id' =>lang('raw_order_lang.raw_orders_company_name')),
        array('order_quantity' =>lang('raw_order_lang.raw_orders_quantity')),
        array('order_status' =>lang('raw_order_lang.raw_orders_status')),
        array('delivered_description' =>lang('raw_order_lang.raw_orders_delivered_description')),
        array('delivered_quantity' =>lang('raw_order_lang.raw_orders_delivered_quantity')),
        array('receiving_description' =>lang('raw_order_lang.raw_orders_received_description')),
        array('receiving_quantity' =>lang('raw_order_lang.raw_orders_received_quantity')),
        array('print' =>lang('common_lang.common_print'), 'sortable' => FALSE),
    );

    return transform_headers($headers);
}


function get_raw_order_data_row($raw_order, $controller)
{
    $Store = new Store();
    $controller_name = strtolower('raw_orders');
    $store = $Store->exists(session()->get('person_id'));
    return array(
        'order_id' => $raw_order->order_id,
        'created_at' => $raw_order->created_at,
        'description' => $raw_order->description,

        'category' => ($raw_order->category==1 || $raw_order->category==2) ? ($raw_order->category==1) ? 'Vendor' : 'Warehouse' : 'Store',
        
        'person_id' => $raw_order->company_name,
        'order_quantity' => parse_decimals($raw_order->total_count)." (".$raw_order->order_quantity.")",
        'order_status' => '<span class="label label-default">'.$raw_order->order_status.'</span>',
        'delivered_description' => !$raw_order->delivered_description ? '-' : $raw_order->delivered_description,
        'delivered_quantity' => parse_decimals($raw_order->total_delivered_count)." (".$raw_order->delivered_quantity.")",
        'receiving_description' => !$raw_order->receiving_description ? '-' : $raw_order->receiving_description,
        'receiving_quantity' => parse_decimals($raw_order->total_received_count)." (".$raw_order->receiving_quantity.")", 
        'print' => anchor($controller_name . "/print_order/$raw_order->order_id", '<span class="glyphicon glyphicon-print"></span>',
            array('target' => '_blank', 'title' => lang('common_lang.common_print'))
        ),
        // 'edit' => anchor($controller_name . "/view/$raw_order->order_id", '<span class="glyphicon glyphicon-edit"></span>',
        //     array('class' => 'modal-dlg', 'data-btn-submit' => $CI->lang->line('common_submit'), 'title' => $CI->lang->line($controller_name . '_update'))
        // )
        'edit' => ($store&&$raw_order->order_status == 'Pending')? '':
        anchor($controller_name . "/view/$raw_order->order_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('raw_order_lang.'.$controller_name . '_update'))
        )
    );
}

function get_raw_item_print_data_row($item, $controller)
{
    $controller_name = strtolower('raw_orders');

    return array(
        'raw_items.item_id' => $item->item_id,
        'item_number' => isset($item->item_number) ? $item->item_number : '-',
        'name' => $item->name,
        'category' => isset($item->category) ? $item->category : '-',
        'quantity' => to_quantity_decimals($item->quantity),
        'edit' => '');
}

function get_raw_orders_items_print_table_headers()
{
    $headers = array(
        array('raw_items.item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('items_lang.items_item_number')),
        array('name' => lang('items_lang.items_name')),
        array('category' => lang('items_lang.items_category')),
        array('quantity' => lang('items_lang.items_quantity')),
    );

    return transform_headers($headers);
}

function get_store_items_manage_table_headers()
{
    $headers = array(
        array('item_id' => lang('common_lang.common_id')),
        array('item_number' => lang('items_lang.items_item_number')),
        array('name' => lang('items_lang.items_name')),
        array('category' => lang('items_lang.items_category')),
        // array('warehouse' => lang('warehouses_company_name')),
        array('cost_price' => lang('items_lang.items_cost_price')),
        array('quantity' => lang('items_lang.items_quantity')),
    );

    return transform_headers($headers);
}

function get_store_item_data_row($item, $controller)
{
    $controller_name = strtolower('store_items');

    return array(
        'item_id' => $item->item_id,
        'item_number' => isset($item->item_number) ? $item->item_number : '-',
        'name' => $item->name,
        'category' => isset($item->category) ? $item->category : 'Raw',
        // 'warehouse' => $item->company_name,
        'cost_price' => to_currency($item->cost_price),
        'quantity' => to_quantity_decimals($item->quantity),
        'edit' => '',
    );
}


function get_counters_manage_table_headers()
{

    $headers = array(
        array('people.person_id' => lang('common_lang.common_id')),
        array('company_name' => lang('counters_lang.counters_company_name')),
        array('category' => lang('counters_lang.counters_category')),
        array('last_name' => lang('common_lang.common_last_name')),
        array('first_name' => lang('common_lang.common_first_name')),
        array('email' => lang('common_lang.common_email')),
        array('phone_number' => lang('common_lang.common_phone_number')),
        array('items' => lang('counters_lang.counters_items_list'), 'sortable' => FALSE),
    );

    $Employee = new Employee();
    if ($Employee->has_grant('messages', session()->get('person_id'))) {
        $headers[] = array('messages' => '');
    }

    return transform_headers($headers);
}



function get_counters_data_row($store, $controller)
{
    $controller_name = strtolower('counters');

    return array(
        'people.person_id' => $store->person_id,
        'company_name' => $store->company_name,
        'category' => ($store->category==1) ? 'Counter' : 'Production',
        'last_name' => $store->last_name,
        'first_name' => $store->first_name,
        'email' => empty($store->email) ? '' : mailto($store->email, $store->email),
        'phone_number' => $store->phone_number,
        'messages' => empty($store->phone_number) ? '' : anchor("Messages/view/$store->person_id", '<span class="glyphicon glyphicon-phone"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' =>lang('common_lang.common_submit'), 'title' =>lang('messages_lang.messages_sms_send'))),
        'items' => ($store->category==2) ? anchor($controller_name . "/count_details/$store->person_id", '<span class="glyphicon glyphicon-list-alt"></span>',
            array('class' => 'modal-dlg', 'title' =>lang($controller_name.'_lang.'.$controller_name . '_details_count'))
        ) : 'N/A',
        'edit' => anchor($controller_name . "/view/$store->person_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => "modal-dlg", 'data-btn-submit' =>lang('common_lang.common_submit'), 'title' =>lang('counters_lang.'.$controller_name . '_update')))
    );
}


function get_counter_orders_manage_table_headers()
{

    $headers = array(
        array('order_id' => lang('common_lang.common_id')),
        array('created_at' => lang('counter_orders_lang.counter_orders_date')),
        array('description' => lang('counter_orders_lang.counter_orders_description')),
        array('counter_orders.category' => lang('counter_orders_lang.counter_orders_category')),
        array('company_name' => lang('counter_orders_lang.counter_orders_company_name')),
        array('order_quantity' => lang('counter_orders_lang.counter_orders_quantity')),
        array('order_status' => lang('counter_orders_lang.counter_orders_status')),
        array('delivered_description' => lang('counter_orders_lang.counter_orders_delivered_description')),
        array('delivered_quantity' => lang('counter_orders_lang.counter_orders_delivered_quantity')),
        array('receiving_description' => lang('counter_orders_lang.counter_orders_received_description')),
        array('receiving_quantity' => lang('counter_orders_lang.counter_orders_received_quantity')),
    );

    return transform_headers($headers);
}

function get_counter_order_data_row($counter_order, $controller)
{
    $controller_name = strtolower('counter_orders');

    if($counter_order->category==1){ $category = 'Counter'; }elseif($counter_order->category==11){ $category = 'Kitchen'; }elseif ($counter_order->category==12) { $category = 'Rangai'; }elseif ($counter_order->category==13) { $category = 'Cross'; }

    return array(
        'order_id' => $counter_order->order_id,
        'created_at' => $counter_order->created_at,
        'description' => $counter_order->description,
        'counter_orders.category' => ($counter_order->category==1) ? 'Counter' : 'Production',
        'company_name' => $counter_order->company_name,
        'order_quantity' => parse_decimals($counter_order->total_count)." (".$counter_order->order_quantity.")",
        'order_status' => '<span class="label label-default">'.$counter_order->order_status.'</span>',
        'delivered_description' => !$counter_order->delivered_description ? '-' : $counter_order->delivered_description,
        'delivered_quantity' => parse_decimals($counter_order->total_delivered_count)." (".$counter_order->delivered_quantity.")",
        'receiving_description' => !$counter_order->receiving_description ? '-' : $counter_order->receiving_description,
        'receiving_quantity' => parse_decimals($counter_order->total_received_count)." (".$counter_order->receiving_quantity.")",
        'edit' => anchor($controller_name . "/view/$counter_order->order_id", '<span class="glyphicon glyphicon-edit"></span>',
            array('class' => 'modal-dlg', 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang($controller_name . '_lang.'.$controller_name . '_update'))
        ));
}


function get_notifications_manage_table_headers()
{

    $headers = array(
        array('id' => lang('common_lang.common_id')),
        array('notification' => lang('notifications_lang.notification')),
        array('dateTime' => lang('notifications_lang.dateTime')),
    );

    return transform_headers($headers);
}

function get_notifications_data_row($notifications, $controller)
{
    $controller_name = strtolower('notifications');

    return array(
            'id' => $notifications->id,
            'notification' => '<a class="rollover text-primary" href="'.$notifications->url.'">'.$notifications->details.'</a>',
            'dateTime' => $notifications->created_at,
            'edit' => '',
        );
}



?>
