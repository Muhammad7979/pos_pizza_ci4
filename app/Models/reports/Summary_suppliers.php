<?php
namespace App\Models\reports;
use App\Models\reports\Report;
class Summary_suppliers extends Report
{
	protected $table = "sales_items_temp";
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_supplier'), lang('reports_lang.reports_quantity'), lang('reports_lang.reports_subtotal'), lang('reports_lang.reports_total'), lang('reports_lang.reports_tax'), lang('reports_lang.reports_cost'), lang('reports_lang.reports_profit'));
	}
	
	public function getData(array $inputs)
	{
		$builder = $this->db->table($this->table) 
		->select('CONCAT(company_name, " (", first_name, " ", last_name, ")") AS supplier, SUM(quantity_purchased) AS quantity_purchased, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit')
		->join('suppliers', 'suppliers.person_id = sales_items_temp.supplier_id')
		->join('people', 'suppliers.person_id = people.person_id')
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

		$builder->groupBy('supplier_id');
		$builder->orderBy('last_name');
		
		return $builder->get()->getResultArray();
	}
	
	public function getSummaryData(array $inputs)
	{
		$builder = $this->db->table($this->table)
		 ->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit')
		//  ->from('sales_items_temp');
		 ->join('suppliers', 'suppliers.person_id = sales_items_temp.supplier_id')
		 ->join('people', 'suppliers.person_id = people.person_id')
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