<?php
namespace App\Models\reports;
use App\Models\reports\Report;

class Specific_customer extends Report
{
protected $table = 'sales_items_temp';
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array('summary' => array(lang('reports_lang.reports_sale_id'), lang('reports_lang.reports_date'), lang('reports_lang.reports_quantity'), lang('reports_lang.reports_sold_by'), lang('reports_lang.reports_subtotal'), lang('reports_lang.reports_total'), lang('reports_lang.reports_tax'), lang('reports_lang.reports_cost'), lang('reports_lang.reports_profit'), lang('reports_lang.reports_payment_type'), lang('reports_lang.reports_comments')),
					 'details' => array(lang('reports_lang.reports_name'), lang('reports_lang.reports_category'), lang('reports_lang.reports_serial_number'), lang('reports_lang.reports_description'), lang('reports_lang.reports_quantity'), lang('reports_lang.reports_subtotal'), lang('reports_lang.reports_total'), lang('reports_lang.reports_tax'), lang('reports_lang.reports_cost'), lang('reports_lang.reports_profit'), lang('reports_lang.reports_discount'))
		);
	}
	
	public function getData(array $inputs)
	{
		$builder = $this->db->table($this->table)
		->select('sale_id, sale_date, SUM(quantity_purchased) AS items_purchased, employee_name, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit, payment_type, comment')
		// ->from('sales_items_temp');
		->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']) . " AND customer_id=" . $this->db->escape($inputs['customer_id']));

		if ($inputs['sale_type'] == 'sales')
        {
            $builder->where('quantity_purchased > 0');
        }
        elseif ($inputs['sale_type'] == 'returns')
        {
            $builder->where('quantity_purchased < 0');
        }

		$builder->groupBy('sale_id');
		$builder->orderBy('sale_date');

		$data = array();
		$data['summary'] = $builder->get()->getResultArray();
		$data['details'] = array();
		
		foreach($data['summary'] as $key=>$value)
		{
			$builder = $this->db->table($this->table)
			 ->select('name, category, serialnumber, description, quantity_purchased, subtotal, total, tax, cost, profit, discount_percent')
			 ->where('sale_id', $value['sale_id']);
			$data['details'][$key] = $builder->get()->getResultArray();
		}

		return $data;
	}
	
	public function getSummaryData(array $inputs)
	{
		$builder = $this->db->table($this->table)
		->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit')
		// $this->db->from('sales_items_temp');
		->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']) . " AND customer_id=" . $this->db->escape($inputs['customer_id']));

		if ($inputs['sale_type'] == 'sales')
        {
            $builder->where('quantity_purchased > 0');
        }
        elseif ($inputs['sale_type'] == 'returns')
        {
            $builder->where('quantity_purchased < 0');
        }

		return $builder->get()->getRowArray();
	}
}
?>