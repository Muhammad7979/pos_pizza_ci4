<?php
namespace App\Models\reports;
use App\Models\reports\Report;

class Summary_employees extends Report
{
	protected $table = 'sales_items_temp';
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_employee'), lang('reports_lang.reports_quantity'), lang('reports_lang.reports_subtotal'), lang('reports_lang.reports_total'), lang('reports_lang.reports_tax'), lang('reports_lang.reports_cost'), lang('reports_lang.reports_profit'));
	}
	
	public function getData(array $inputs)
	{
		$builder = $this->db->table($this->table)
		->select('employee_name AS employee, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit')
		// $this->db->from('sales_items_temp');
		->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

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

		$builder->groupBy('employee_id');
		$builder->orderBy('employee_name');

		return $builder->get()->getResultArray();		
	}
	
	public function getSummaryData(array $inputs)
	{
		$builder = $this->db->table($this->table)

		->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit')
		// $this->db->from('sales_items_temp');
		->where("sale_date BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']));

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