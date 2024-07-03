<?php
require_once("Report.php");
class Summary_sales extends Report
{
    function __construct()
    {
        parent::__construct();
    }

    public function getDataColumns()
    {
        return array(
            $this->lang->line('reports_date'),
            $this->lang->line('reports_quantity'),
            //            $this->lang->line('reports_subtotal'),
            $this->lang->line('reports_total'),
            //            $this->lang->line('reports_tax'),
            //            $this->lang->line('reports_cost'),
            //            $this->lang->line('reports_profit')
        );
    }
    
    public function getData(array $inputs)
    {
        //gu branch
        $branch = $this->gu->getStoreBranchCode();
        //$this->db->select('sale_date, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');

        $this->db->select('sale_date, sale_type, SUM(quantity_purchased) AS quantity_purchased, SUM(total) AS total , SUM(gift_payment_amount) AS gift_payments');

        $this->db->from('sales_items_temp');
        $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        // $this->db->select('DATE(sale_time) AS sale_date,sale_type, SUM(quantity_purchased) AS quantity_purchased, sum(payment_amount) AS totalsales.sale_id');
        // $this->db->from('sales as sales');
        // $this->db->join('sales_items AS items', 'sales.sale_id = items.sale_id');
        // $this->db->join('sales_payments AS payments', 'sales.sale_id = payments.sale_id');
        // $this->db->where('DATE(sales.sale_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
        if ($inputs['location_id'] != 'all')
        {
            $this->db->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales')
        {
            $this->db->where('quantity_purchased > 0');
            //$this->db->where('sale_type','normal');
        }
        elseif ($inputs['sale_type'] == 'returns')
        {
            $this->db->where('quantity_purchased < 0');
        }

        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        //        if ($inputs['sale_mode'] != 'all') {
            $this->db->where('sale_type', $inputs['sale_mode']);
        //        }
        //        else{
        //            $this->db->where('sale_type', 'normal');
        //        }


        //gu branch
        if (!$this->gu->isServer()) {
            $this->db->where('branch_code', $branch);
        }
        else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if($branch_code != 'all'){
                $this->db->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cash'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cod'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cod'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->group_end();
        }
        $this->db->group_by('sale_time');
        // $this->db->group_by('sales.sale_id');
        $this->db->order_by('sale_time');
        
        // print_r($this->db->last_query());     
        $data =  $this->db->get()->result_array();
        foreach ($data as $key => $value) {
            $data[$key]['total'] = $data[$key]['total']-$data[$key]['gift_payment_amount'];
        }
        return $data;
    }
    
    public function getSummaryData(array $inputs)
    {
        //gu branch
        $branch = $this->gu->getStoreBranchCode();
        //print_r ($this->getFbrFee($inputs));
        //exit();

        //$this->db->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
        // $this->db->select('SUM(total) AS total');
        //$this->db->select('count(DISTINCT(invoice_number))')->distinctinvoice_number('invoice_number');
        
        //  $this->db->distinct('invoice_number');
        // $this->db->select('*');gift_payment_amount
        // $this->db->from('sales_items_temp');gift_payment_amount
        // $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        // $sub_query = $this->db->get_compiled_select();
        // $sub_query = $this->db->select('fbr_fee')->from('sales_items_temp')
        // ->group_start()
        //     ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']))
        //     ->group_by('sale_id')
        // ->group_end()
        // ->get();

        //$sub_query = $this->db->get('sales_items_temp');
        // foreach ($sub_query->result() as $row)
        // {
        //         echo $row->title;
        // }
        //echo "<pre>";
        $result =$this->getFbrFee($inputs);
        $fbr_fee = 0;
        $gift_payments = 0;
        foreach($result as $key => $value){
            //  echo $key;
            // echo $result[$key]['fbr_fee'];
            $gift_payments = $gift_payments+$result[$key]['gift_payment_amount'];
            $fbr_fee = $fbr_fee + $result[$key]['fbr_fee'];
        }
        // echo $fbr_fee;
        // exit();
        //$this->db->select( 'count(DISTINCT(sale_id)) , SUM(fbr_fee) ')->distinct('invoice_number');
        //$this->db->select_sum('fbr_fee');
        //$this->db->select_sum('fbr_fee')->distinct('invoice_number');

        
        $this->db->select('(SUM(total) - SUM(gift_payment_amount)) AS cash_payments, SUM(gift_payment_amount) AS gift_payments, SUM(total) AS total');
        $this->db->from('sales_items_temp');
        $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        if ($inputs['location_id'] != 'all')
        {
            $this->db->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales')
        {
            $this->db->where('quantity_purchased > 0');
        }
        elseif ($inputs['sale_type'] == 'returns')
        {
            $this->db->where('quantity_purchased < 0');
        }



        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        //        if ($inputs['sale_mode'] != 'all') {
            $this->db->where('sale_type', $inputs['sale_mode']);
        //        }
        //        else{
        //            $this->db->where('sale_type', 'normal');
        //        }


        //gu branch
        if (!$this->gu->isServer()) {
            $this->db->where('branch_code', $branch);
        }
        else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if($branch_code != 'all'){
                $this->db->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cash'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cod'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cod'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->group_end();
        }

        // $this->db->select('(SUM(fbr_fee)');
        // $this->db->from('sales_items_temp');
        // $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        // $this->db->group_by('sale_id');

        // $this->db->get()->row_array();
        // $this->db->from('sales_items_temp');
        // $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        $data = $this->db->get()->row_array();
        $data['fbr_fee']= $fbr_fee;
        $data['total'] =$data['total'];  
        $temp['cash_payments']=$data['total'] - $gift_payments;
        $temp['gift_payments']=$gift_payments;
        $temp['fbr_fee']=$data['fbr_fee'];
        $temp['total']=$data['total'];        
        return $temp;       
    }

    public function getFbrFee(array $inputs){
        $branch = $this->gu->getStoreBranchCode();
        
        $this->db->select('fbr_fee');
        $this->db->select('gift_payment_amount');
        $this->db->from('sales_items_temp');
        $this->db->group_start();
            $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
            $this->db->group_by('sale_id');
        $this->db->group_end();
        
        
        if ($inputs['location_id'] != 'all')
        {
            $this->db->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales')
        {
            $this->db->where('quantity_purchased > 0');
        }
        elseif ($inputs['sale_type'] == 'returns')
        {
            $this->db->where('quantity_purchased < 0');
        }



        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        $this->db->where('sale_type', $inputs['sale_mode']);
     

        //gu branch
        if (!$this->gu->isServer()) {
            $this->db->where('branch_code', $branch);
        }
        else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if($branch_code != 'all'){
                $this->db->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cash'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif($inputs['payment_type'] == 'cod'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cod'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_giftcard'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->group_end();
        }
        return $this->db->get()->result_array();
    }

}
?>
