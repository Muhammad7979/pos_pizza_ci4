<?php
namespace App\Models\reports;
use App\Models\reports\Report;
class Detailed_sales extends Report
{
	protected $table = 'sales_items_temp'; 
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(
			'summary' => array(
				'id' => lang('reports_lang.reports_sale_id'),
				'branch_code' => lang('reports_lang.sale_branch_code'),
				'sale_type' => 'Sale Type',
				'sale_date' => lang('reports_lang.reports_date'),
				'quantity' => lang('reports_lang.reports_quantity'),
				'employee' => lang('reports_lang.reports_sold_by'),
				'customer' => lang('reports_lang.reports_sold_to'),
				'subtotal' => lang('reports_lang.reports_subtotal'),
				'total' => lang('reports_lang.reports_total'),
				'tax' => lang('reports_lang.reports_tax'),
				'cost' => lang('reports_lang.reports_cost'),
				'profit' => lang('reports_lang.reports_profit'),
				'payment_type' => lang('reports_lang.sales_amount_tendered'),
				'comment' => lang('reports_lang.reports_comments'),
				'edit' => ''),
			 'details' => array(
				 lang('reports_lang.reports_name'),
				 lang('reports_lang.reports_category'),
				 lang('reports_lang.reports_serial_number'),
				 lang('reports_lang.reports_description'),
				 lang('reports_lang.reports_quantity'),
				 lang('reports_lang.reports_subtotal'),
				 lang('reports_lang.reports_total'),
				 lang('reports_lang.reports_tax'),
				 lang('reports_lang.reports_cost'),
				 lang('reports_lang.reports_profit'),
				 lang('reports_lang.reports_discount'))
		);		
	}
	
	public function getDataBySaleId($sale_id)
	{
		$builder = $this->db->table($this->table)
        ->select('sale_id, sale_type, branch_code, sale_date, SUM(quantity_purchased) AS items_purchased, employee_name, customer_name, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit, payment_type, comment')
		// $this->db->from('sales_items_temp');
		->where('sale_id', $sale_id);

		return $builder->get()->getRowArray();
	}
	
	public function getData(array $inputs)
	{
		$builder = $this->db->table($this->table)
        ->select('sale_id, sale_type, branch_code, sale_date, SUM(quantity_purchased) AS items_purchased, employee_name, customer_name, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit, payment_type, comment')
		// $this->db->from('sales_items_temp');
		->where('sale_date BETWEEN '. $this->db->escape($inputs['start_date']). ' AND '. $this->db->escape($inputs['end_date']));

		if ($inputs['location_id'] != 'all')
		{
			$builder->where('item_location', $inputs['location_id']);
		}

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
			->select('name, category, quantity_purchased, item_location, serialnumber, description, subtotal, total, tax, cost, profit, discount_percent')
			// ->from('sales_items_temp');
			->where('sale_id', $value['sale_id']);
			$data['details'][$key] = $builder->get()->getResultArray();
		}
		
		return $data;
	}
	
	public function getSummaryData(array $inputs)
	{
		$builder = $this->db->table($this->table)
        ->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
		//$builder->select('SUM(total) AS total');
		// $builder->from('sales_items_temp');
		$builder->where('sale_date BETWEEN '. $this->db->escape($inputs['start_date']). ' AND '. $this->db->escape($inputs['end_date']));

		if ($inputs['location_id'] != 'all')
		{
			$builder->where('item_location', $inputs['location_id']);
		}

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