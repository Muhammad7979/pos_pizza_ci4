<?php
namespace App\Models\reports;
use App\Libraries\Gu;
use App\Models\Appconfig;
use App\Models\reports\Report;
class Summary_items extends Report
{
    protected $table = 'sales_items_temp';
    protected $appData;
    protected $gu;
    function __construct()
    {
        parent::__construct();
        $this->gu=new Gu();
        $this->db = $this->gu->getReportingDb();
        $this->appData = new Appconfig();
    }

    public function getDataColumns()
    {
        return array(
            lang('reports_lang.reports_item'),
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

        //$this->db->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
       $builder = $this->db->table('sales_items_temp')
                            ->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(total) AS total')

        // $this->db->from('sales_items_temp');
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
        //if ($inputs['sale_mode'] != 'all') {
        $builder->where('sale_type', $inputs['sale_mode']);
        //}


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
        if (isset($inputs['payment_type']) && $inputs['payment_type'] == 'credit') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_credit'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        } elseif (isset($inputs['payment_type']) && $inputs['payment_type'] == 'cash') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cash'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        } elseif ( isset($inputs['payment_type']) && $inputs['payment_type'] == 'cod') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_cod'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_giftcard'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->groupEnd();
        } elseif (isset($inputs['payment_type']) && $inputs['payment_type'] == 'giftcard') {
            $builder->groupStart()
                    ->like('payment_type', lang('sales_lang.sales_giftcard'), 'after')
                    ->orWhere('payment_type IS NULL')
                    ->where('sale_payment !=', lang('sales_lang.sales_credit'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cash'))
                    ->where('sale_payment !=', lang('sales_lang.sales_cod'))
                    ->groupEnd();
        }


        $builder->groupBy('item_id');
        $builder->orderBy('name');
        return $builder->get()->getResultArray();
    }

    // public function getData(array $inputs)
    // {
    //     //gu branch
    //     $gu = new Gu();
    //     $branch = $gu->getStoreBranchCode();

    //     //$this->db->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
    //    $builder = $this->db->table($this->table)
    //     ->select('name, SUM(quantity_purchased) AS quantity_purchased, SUM(total) AS total')

    //     ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));
    //    dd($builder->get()->getResultArray());
    //     if ($inputs['location_id'] != 'all') {
    //         $builder->where('item_location', $inputs['location_id']);
    //     }

    //     if ($inputs['sale_type'] == 'sales') {
    //         $builder->where('quantity_purchased > 0');
    //     } elseif ($inputs['sale_type'] == 'returns') {
    //         $builder->where('quantity_purchased < 0');
    //     }


    //     //gu sale mode / type
    //     /**
    //      * sale mode / type filter
    //      */
    //     //if ($inputs['sale_mode'] != 'all') {



    //     $builder->where('sale_type', $inputs['sale_type']);




    //     //}


    //     //gu branch
        
    //     if (!$gu->isServer()) {
    //         $builder->where('branch_code', $branch);
    //     } else {
    //         //if server and branch code is set
    //         $branch_code = $this->appData->get('branch_code');
    //         if ($branch_code != 'all') {
    //             $builder->where('branch_code', $branch_code);
    //         }
    //     }

    //     //gu payment type
    //     if (isset($inputs['payment_type']) && $inputs['payment_type'] == 'credit') {
    //         $builder->groupStart();
    //         $builder->like('payment_type', lang('sales_lang.sales_credit'), 'after');
    //         $builder->orWhere('payment_type IS NULL');
    //         $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
    //         $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
    //         $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
    //         $builder->groupEnd();
    //     } elseif (isset($inputs['payment_type']) && $inputs['payment_type'] == 'cash') {
    //        $builder->groupStart();
    //        $builder->like('payment_type', lang('sales_lang.sales_cash'), 'after');
    //        $builder->orWhere('payment_type IS NULL');
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
    //        $builder->groupEnd();
    //     } elseif ( isset($inputs['payment_type']) && $inputs['payment_type'] == 'cod') {
    //        $builder->groupStart();
    //        $builder->like('payment_type', lang('sales_lang.sales_cod'), 'after');
    //        $builder->orWhere('payment_type IS NULL');
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_giftcard'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
    //        $builder->groupEnd();
    //     } elseif (isset($inputs['payment_type']) && $inputs['payment_type'] == 'giftcard') {
    //        $builder->groupStart();
    //        $builder->like('payment_type', lang('sales_lang.sales_giftcard'), 'after');
    //        $builder->orWhere('payment_type IS NULL');
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
    //        $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
    //        $builder->groupEnd();
    //     }


    //    $builder->groupBy('item_id');
    //    $builder->orderBy('name');
    //     return $builder->get()->getResultArray();
    // }

    public function getSummaryData(array $inputs)
    {
        //gu branch
        $branch = $this->gu->getStoreBranchCode();
        //$test =  $this->getGiftPayments($inputs);
        $result = $this->getFbrFee($inputs);

        $fbr_fee = 0;
        $gift_payments = 0;
        //$gift_payments_total = 0;

        //foreach ($test as $key => $value) {

            //$gift_payments_total = $gift_payments_total + $test[$key]['total'];
            //$gift_payments = $gift_payments + $test[$key]['gift_payment_amount'];
        //}
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
        // echo ($fbr_fee);
        // echo "<br>";
        // echo ($gift_payments_total);
        // echo "<br>";
        // echo ($gift_payments);
        // echo "<br>";
        // echo "asd";
        //$this->db->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
        $builder = $this->db->table('sales_items_temp')->select('(SUM(total) - SUM(gift_payment_amount)) AS cash_payments, SUM(gift_payment_amount) AS gift_payments, SUM(total) AS total')
                            ->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']) );
        //$this->db->not_like('payment_type', $this->lang->line('sales_giftcard'),'after');
        //$this->db->where('sale_payment !=', $this->lang->line('sales_giftcard'));

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
        //        if ($inputs['sale_mode'] != 'all') {
        $builder->where('sale_type', $inputs['sale_mode']);
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
         
        $data = $builder->get()->getRowArray();
        // $data['fbr_fee'] = $fbr_fee;

        $data['total'] = $data['total'] + $fbr_fee - $fbr_fee_loss;
        $temp['cash_payments'] = $data['total'] - $gift_payments;
        $temp['gift_payments'] = $gift_payments;
        $temp['fbr_fee'] = $fbr_fee + $fbr_fee_loss;
        $temp['total'] = $data['total'];
        return $temp;
        // $data =  $this->db->get()->row_array();
        
    }
    public function getFbrFee(array $inputs)
    {
        $gu = new Gu();
        $branch = $gu->getStoreBranchCode();
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
        /**echo "<br>";
         * sale mode / type filter
         */


        $builder->where('sale_type', $inputs['sale_type']);


        //gu branch
        $gu = new Gu();
        if (!$gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            //if server and branch code is set
            $branch_code = $this->appData->get('branch_code');
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
        }
        elseif ($inputs['payment_type'] == 'giftcard') {
            $builder->groupStart();
            $builder->like('payment_type', lang('sales_lang.sales_giftcard'), 'after');
            $builder->orWhere('payment_type IS NULL');
            $builder->where('sale_payment !=', lang('sales_lang.sales_credit'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cash'));
            $builder->where('sale_payment !=', lang('sales_lang.sales_cod'));
            $builder->groupEnd();
        }
        return $builder->get()->getResultArray();
    }
    public function getGiftPayments(array $inputs)
    {
        $gu = new Gu();
        $branch = $gu->getStoreBranchCode();
        $builder = $this->db->table($this->table);
        $builder->select('fbr_fee');
        $builder->select('SUM(total) AS total');
        $builder->select('gift_payment_amount');
        $builder->from('sales_items_temp');
        $builder->groupStart();
        $builder->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']) . " AND " . " gift_payment_amount > 0");
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
        $gu = new Gu();
        if (!$gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            //if server and branch code is set
            $branch_code = $this->appData->get('branch_code');
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
        }
        return $builder->get()->getResultArray();
    }
}
