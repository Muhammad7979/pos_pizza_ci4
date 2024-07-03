<?php
namespace App\Models\reports;
class Inventory_counter extends Report
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_item_name'),
					lang('reports_lang.reports_item_number'),
					lang('reports_lang.reports_category'),
					lang('reports_lang.reports_stock_location'),
					lang('reports_lang.reports_quantity'),
					lang('reports_lang.reports_cost_price'),
					lang('reports_lang.reports_sub_total_value'));
	}
	
    public function getData(array $inputs)
    {
        $builder = $this->db->table('raw_items')
                             ->select('name, item_number, raw_items.category, cost_price, location_name, total_quantity as quantity')
                             ->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id')
                             ->join('stock_locations','stock_locations.location_id=raw_item_quantities.location_id')
                             ->join('counter_order_item_quantities','counter_order_item_quantities.item_id=raw_items.item_id');

        //echo $inputs['store_id'];
        if ($inputs['store_id'] != 'all' && $inputs['store_id'] != '0')
		{
			$builder->join('counters','counters.person_id=counter_order_item_quantities.store_id')
			        ->where('counters.store_id', $inputs['store_id']);
		}

        $builder->where('DATE(created_at) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');


        if ($inputs['counter_id'] != 'all' && $inputs['counter_id'] != '0')
		{
			$builder->where('counter_order_item_quantities.store_id', $inputs['counter_id']);
		}
        $builder->orderBy('name');

        $data = $builder->get()->getResultArray();
        foreach ($data as $key => $value) {
        	$data[$key]['sub_total_value'] = $value['quantity']*$value['cost_price'];
        }
        return $data;
    }
	
	public function getSummaryData(array $inputs)
	{
		return array();
	}
}
?>