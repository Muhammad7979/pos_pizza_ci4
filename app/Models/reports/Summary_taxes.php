<?php

namespace App\Models\reports;

use App\Models\Appconfig;
use App\Models\reports\Report;
class Summary_taxes extends Report
{
	protected $table = "sales_items_temp";
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_tax_percent'), lang('reports_lang.reports_count'), lang('reports_lang.reports_subtotal'), lang('reports_lang.reports_total'), lang('reports_lang.reports_tax'));
	}
	
	public function getData(array $inputs)
	{
		$appData = new Appconfig();
		$quantity_cond = '';
		if ($inputs['sale_type'] == 'sales')
		{
			$quantity_cond = 'and quantity_purchased > 0';
		}
		elseif ($inputs['sale_type'] == 'returns')
		{
			$quantity_cond = 'and quantity_purchased < 0';
		}

		if ($inputs['location_id'] != 'all')
		{
			$quantity_cond .= 'and item_location = '. $this->db->escape($inputs['location_id']);
		}

		if ($appData->get('tax_included'))
		{
			$total    = "1";
			$subtotal = "(100/(100+percent))";
			$tax      = "(1 - (100/(100 +percent)))";
		}
		else
		{
			$tax      = "(percent/100)";
			$total    = "(1+(percent/100))";
			$subtotal = "1";
		}
		
		$decimals = totals_decimals();

		$query = $this->db->query("SELECT percent, count(*) AS count, SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax
			FROM (SELECT name, CONCAT(ROUND(percent, $decimals), '%') AS percent,
			ROUND((item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent /100) * $subtotal, $decimals) AS subtotal,
			ROUND((item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent /100) * $total, $decimals) AS total,
			ROUND((item_unit_price * quantity_purchased - item_unit_price * quantity_purchased * discount_percent /100) * $tax, $decimals) AS tax
			FROM ".$this->db->prefixTable('sales_items_taxes')."
			JOIN ".$this->db->prefixTable('sales_items')." ON "
			.$this->db->prefixTable('sales_items').'.sale_id='.$this->db->prefixTable('sales_items_taxes').'.sale_id'." AND "
			.$this->db->prefixTable('sales_items').'.item_id='.$this->db->prefixTable('sales_items_taxes').'.item_id'." AND "
			.$this->db->prefixTable('sales_items').'.line='.$this->db->prefixTable('sales_items_taxes').'.line'
			." JOIN ".$this->db->prefixTable('sales')." ON ".$this->db->prefixTable('sales_items_taxes').".sale_id=".$this->db->prefixTable('sales').".sale_id
			WHERE date(sale_time) BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']) . " $quantity_cond) AS temp_taxes
			GROUP BY percent");

		return $query->getResultArray();
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