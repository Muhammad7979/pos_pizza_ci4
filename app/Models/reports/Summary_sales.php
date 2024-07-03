<?php
namespace App\Models\reports;

use App\Libraries\Gu;
use App\Models\reports\Report;
class Summary_sales extends Report
{
    protected $gu;
    protected $table = 'sales_items_temp';
    function __construct()
    {
        parent::__construct();
        $this->gu = new Gu();
    }

    public function getDataColumns()
    {
        return array(
            lang('reports_lang.reports_date'),
            lang('reports_lang.reports_quantity'),
            //            lang('reports_lang.reports_subtotal'),
            lang('reports_lang.reports_total'),
            //            lang('reports_lang.reports_tax'),
            //            lang('reports_lang.reports_cost'),
            //            lang('reports_lang.reports_profit')
        );
    }

    public function getData(array $inputs)
    {
        //gu branch
        $branch = $this->gu->getStoreBranchCode();
        //$this->db->select('sale_date, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');

      $builder =  $this->db->table('sales_items_temp')->select('sale_date, sale_type, SUM(quantity_purchased) AS quantity_purchased, SUM(total) AS total , SUM(gift_payment_amount) AS gift_payments')

                 ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        // $this->db->select('DATE(sale_time) AS sale_date,sale_type, SUM(quantity_purchased) AS quantity_purchased, sum(payment_amount) AS totalsales.sale_id');
        // $this->db->from('sales as sales');
        // $this->db->join('sales_items AS items', 'sales.sale_id = items.sale_id');
        // $this->db->join('sales_payments AS payments', 'sales.sale_id = payments.sale_id');
        // $this->db->where('DATE(sales.sale_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
        if ($inputs['location_id'] != 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
            //$this->db->where('sale_type','normal');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }

        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        //        if ($inputs['sale_type'] != 'all') {
        $builder->where('sale_type', $inputs['sale_mode']);
        //        }
        //        else{
        //            $this->db->where('sale_type', 'normal');
        //        }


        //gu branch
        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if ($branch_code != 'all') {
                $builder->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
           $builder->groupStart()
                   ->like('payment_type', lang('sales_lang.sales_credit'), 'after')
                   ->orWhere('payment_type IS NULL')
                   ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                   ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                   ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                   ->groupEnd();
        } elseif ($inputs['payment_type'] == 'cash') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cash'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        } elseif ($inputs['payment_type'] == 'cod') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cod'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->groupEnd();
        } elseif ($inputs['payment_type'] == 'giftcard') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_giftcard'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        }
        $builder->groupBy('sale_time');
        // $builder->group_by('sales.sale_id');
        $builder->orderBy('sale_time');

        // print_r($builder->last_query());     
        $data =  $builder->get()->getResultArray();
        foreach ($data as $key => $value) {
            if ($inputs['payment_type'] == 'giftcard') {
                $data[$key]['total'] = $data[$key]['total'];
            } else {
                $data[$key]['total'] = $data[$key]['total'];
            }
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
        // ->groupStart()
        //     ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']))
        //     ->group_by('sale_id')
        // ->groupEnd()
        // ->get();

        //$sub_query = $this->db->get('sales_items_temp');
        // foreach ($sub_query->result() as $row)
        // {
        //         echo $row->title;
        // }README.md
        //echo "<pre>";
        $result = $this->getFbrFee($inputs);
        $fbr_fee = 0;
        $gift_payments = 0;

        $fbr_fee_loss = 0;
        foreach ($result as $key => $value) {
            // $fbr_fee = $fbr_fee + $result[$key]['fbr_fee'];
            $gift_payments = $gift_payments + $result[$key]['gift_payment_amount'];

            if($value['quantity_purchased']>0){
                $fbr_fee = $fbr_fee + $result[$key]['fbr_fee'];
            }else{
                $fbr_fee_loss = $fbr_fee_loss + $result[$key]['fbr_fee'];
            }
        }
        // echo $fbr_fee;
        // echo "<br>";
        // echo $gift_payments;
        // exit();
        //$this->db->select( 'count(DISTINCT(sale_id)) , SUM(fbr_fee) ')->distinct('invoice_number');
        //$this->db->select_sum('fbr_fee');
        //$this->db->select_sum('fbr_fee')->distinct('invoice_number');


       $builder = $this->db->table('sales_items_temp')->select(' SUM(gift_payment_amount) AS gift_payments, SUM(total) AS total')
        ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        if ($inputs['location_id'] != 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }



        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        //        if ($inputs['sale_type'] != 'all') {
        $builder->where('sale_type', $inputs['sale_mode']);
        //        }
        //        else{
        //            $builder->where('sale_type', 'normal');
        //        }


        //gu branch
        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if ($branch_code != 'all') {
                $builder->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_credit'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        } elseif ($inputs['payment_type'] == 'cash') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cash'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        } elseif ($inputs['payment_type'] == 'cod') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cod'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->groupEnd();
        } elseif ($inputs['payment_type'] == 'giftcard') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_giftcard'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        }

        // $this->db->select('(SUM(fbr_fee)');
        // $this->db->from('sales_items_temp');
        // $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        // $this->db->group_by('sale_id');

        // $this->db->get()->row_array();
        // $this->db->from('sales_items_temp');
        // $this->db->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        $data = $builder->get()->getRowArray();


        // if ($inputs['payment_type'] == 'giftcard') {
        //     $data['total'] =  $data['total'] + $fbr_fee;
        // } else {
        //     $data['total'] = $data['total'] + $fbr_fee - $data['gift_payments'];
        //     $temp['cash_payments'] = $data['total'] - $gift_payments;
        // }

        // $temp['gift_payments'] = $gift_payments;
        // $temp['fbr_fee'] = $data['fbr_fee'];
        // $temp['total'] = $data['total'];
        // return $temp;
        // $data['fbr_fee'] = $fbr_fee;
        $data['total'] = $data['total'] + $fbr_fee - $fbr_fee_loss;

        $temp['cash_payments'] = $data['total'] - $gift_payments;
        $temp['gift_payments'] = $gift_payments;
        $temp['fbr_fee'] = $fbr_fee+$fbr_fee_loss;
        $temp['total'] = $data['total'];
        return $temp;
    }

    public function getFbrFee(array $inputs)
    {
        $branch = $this->gu->getStoreBranchCode();
        $builder = $this->db->table($this->table);
        $builder->select('fbr_fee');
        $builder->select('gift_payment_amount');
        $builder->select('quantity_purchased');
        // $builder->from('sales_items_temp');
        $builder->groupStart();
        $builder->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
        $builder->groupBy('sale_id');
        $builder->groupEnd();


        if ($inputs['location_id'] != 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }



        //gu sale mode / type
        /**
         * sale mode / type filter
         */
        $builder->where('sale_type', $inputs['sale_type']);


        //gu branch
        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            //if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if ($branch_code != 'all') {
                $builder->where('branch_code', $branch_code);
            }
        }

        //gu payment type
        if ($inputs['payment_type'] == 'credit') {
            $builder->groupStart();
            $builder->like('payment_type', lang('sales_lang.sales_credit'), 'after');
            $builder->orWhere('payment_type IS NULL');
            $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
            $builder->groupEnd();
        } elseif ($inputs['payment_type'] == 'cash') {
            $builder->groupStart();
            $builder->like('payment_type', lang('sales_lang.sales_cash'), 'after');
            $builder->orWhere('payment_type IS NULL');
            $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
            $builder->groupEnd();
        } elseif ($inputs['payment_type'] == 'cod') {
            $builder->groupStart();
            $builder->like('payment_type', lang('sales_lang.sales_cod'), 'after');
            $builder->orWhere('payment_type IS NULL');
            $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
            $builder->groupEnd();
        } elseif ($inputs['payment_type'] == 'giftcard') {
            $builder->groupStart();
            $builder->like('payment_type', lang('sales_lang.sales_giftcard'), 'after');
            $builder->orWhere('payment_type IS NULL');
            $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
            $builder->groupEnd();
        }
        return $builder->get()->getResultArray();
        // $data = $this->db->get()->getRowArray();
        // echo "<pre>";
        // print_r($data);
        // return $data;
    }
}
