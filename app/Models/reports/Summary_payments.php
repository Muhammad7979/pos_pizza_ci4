<?php
namespace App\Models\reports;
use App\Models\reports\Report;
use App\Libraries\Gu;
use Config\Database;
class Summary_payments extends Report
{
	protected $table = 'sales_items_temp';
	protected $gu;
	function __construct()
	{
		parent::__construct();
           $this->gu=new Gu();
	}
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_payment_type'), lang('reports_lang.reports_count'), lang('sales_lang.sales_amount_tendered'));
	}

    public function getData(array $inputs)
    {
        // gu branch
        $branch = $this->gu->getStoreBranchCode();

        $builder = $this->db->table('sales_payments');
        $builder->select('sales_payments.payment_type, count(*) AS count, SUM(payment_amount) AS payment_amount');
        $builder->join('sales', 'sales.sale_id = sales_payments.sale_id');
        $builder->where("date(sale_time) BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        if ($inputs['location_id'] !== 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('payment_amount > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('payment_amount < 0');
        }

        // gu branch
        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            // if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if ($branch_code !== 'all') {
                $builder->where('branch_code', $branch_code);
            }
        }

        $builder->groupBy("payment_type");

        $payments = $builder->get()->getResultArray();

        // consider Gift Card as only one type of payment and do not show "Gift Card: 1, Gift Card: 2, etc." in the total
        $gift_card_count = 0;
        $gift_card_amount = 0;
        foreach ($payments as $key => $payment) {
            if (strpos($payment['payment_type'], lang('sales_lang.sales_giftcard')) !== false) {
                $gift_card_count  += $payment['count'];
                $gift_card_amount += $payment['payment_amount'];

                // remove the "Gift Card: 1", "Gift Card: 2", etc. payment string
                unset($payments[$key]);
            }
        }

        if ($gift_card_count > 0) {
            $payments[] = array('payment_type' => lang('sales_lang.sales_giftcard'), 'count' => $gift_card_count, 'payment_amount' => $gift_card_amount);
        }

        return $payments;
    }
	
	public function getSummaryData(array $inputs)
    {
        // gu branch
        $branch = $this->gu->getStoreBranchCode();

        $builder = $this->db->table('sales_items_temp');
        $builder->select('SUM(total) AS total');
        $builder->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

        if ($inputs['location_id'] !== 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }

        // gu branch
        if (!$this->gu->isServer()) {
            $builder->where('branch_code', $branch);
        } else {
            // if server and branch code is set
            $branch_code = $inputs['branch_code'];
            if ($branch_code !== 'all') {
                $builder->where('branch_code', $branch_code);
            }
        }

        return $builder->get()->getRowArray();
    }	
}
?>