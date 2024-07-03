<?php
require_once("Report.php");
class Summary_items extends Report
{
    function __construct()
    {
        parent::__construct();
    }
    
    public function getDataColumns()
    {
        return array(
            $this->lang->line('reports_item'),
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

        //$this->db->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
        $this->db->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(total) AS total, fbr_fee AS fbr_feeeee');

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
        //if ($inputs['sale_mode'] != 'all') {
            $this->db->where('sale_type', $inputs['sale_mode']);
        //}


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
        if (isset($inputs['payment_type']) && $inputs['payment_type'] == 'credit') {
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif(isset($inputs['payment_type']) && $inputs['payment_type'] == 'cash'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif(isset($inputs['payment_type']) && $inputs['payment_type'] == 'cod'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cod'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->group_end();
        }



        $this->db->group_by('item_id');
        $this->db->order_by('name');

        $data = $this->db->get()->result_array();
        print_r($this->db->last_query());        
        return $data;
    }
    
    public function getSummaryData(array $inputs)
    {
        //gu branch
        $branch = $this->gu->getStoreBranchCode();
        $result =$this->getFbrFee($inputs);
        $fbr_fee = 0;
        foreach($result as $key => $value){
            $fbr_fee = $fbr_fee + $result[$key]['fbr_fee'];
        }
        //$this->db->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
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
        if (isset($inputs['payment_type']) && $inputs['payment_type'] == 'credit') {
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_credit'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif(isset($inputs['payment_type']) && $inputs['payment_type'] == 'cash'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cash'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cod'));
            $this->db->group_end();
        }
        elseif(isset($inputs['payment_type']) && $inputs['payment_type'] == 'cod'){
            $this->db->group_start();
            $this->db->like('payment_type', $this->lang->line('sales_cod'), 'after');
            $this->db->or_where('payment_type IS NULL');
            $this->db->where('sale_payment !=',$this->lang->line('sales_credit'));
            $this->db->where('sale_payment !=',$this->lang->line('sales_cash'));
            $this->db->group_end();
        }
        $data = $this->db->get()->row_array();
        $data['fbr_fee']= $fbr_fee;
        $data['total'] =$data['total'] +$data['fbr_fee'];  
        $temp['cash_payments']=$data['cash_payments'];
        $temp['gift_payments']=$data['gift_payments'];
        $temp['fbr_fee']=$data['fbr_fee'];
        $temp['total']=$data['total'];        
        return $temp;
        // $data =  $this->db->get()->row_array();
        // print_r($data);
        // exit;
    }
    public function getFbrFee(array $inputs){
        $branch = $this->gu->getStoreBranchCode();
        
        $this->db->select('fbr_fee');
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
