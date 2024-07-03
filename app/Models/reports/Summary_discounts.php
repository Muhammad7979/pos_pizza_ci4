<?php
namespace App\Models\reports;
use App\Models\reports\Report;
use App\Libraries\Gu;
use Config\Database;
class Summary_discounts extends Report
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
		return array(lang('reports_lang.reports_discount_percent'), lang('reports_lang.reports_count'));
	}
	
	// Assuming this function is part of a model class
	public function getData(array $inputs)
    {
        $builder = $this->db->table($this->table);
        $builder->select('CONCAT(discount_percent, "%") AS discount_percent, COUNT(*) AS count');
        $builder->where('sale_date BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
        $builder->where('discount_percent > 0');

        if ($inputs['location_id'] !== 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }

        $builder->groupBy('sales_items_temp.discount_percent');
        $builder->orderBy('discount_percent');

        return $builder->get()->getResultArray();
    }

	
	// Assuming this function is part of a model class
    public function getSummaryData(array $inputs)
    {
        $builder = $this->db->table($this->table);
        $builder->select('SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(tax) AS tax, SUM(cost) AS cost, SUM(profit) AS profit');
        $builder->where('sale_date BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));

        if ($inputs['location_id'] !== 'all') {
            $builder->where('item_location', $inputs['location_id']);
        }

        if ($inputs['sale_type'] == 'sales') {
            $builder->where('quantity_purchased > 0');
        } elseif ($inputs['sale_type'] == 'returns') {
            $builder->where('quantity_purchased < 0');
        }

        return $builder->get()->getRowArray();
    }
}
?>