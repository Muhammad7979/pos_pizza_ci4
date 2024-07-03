<?php
namespace App\Models\reports;
class Inventory_store extends Report
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
					lang('reports_lang.reports_current_quantity'),
					lang('reports_lang.reports_order_quantity'),
					lang('reports_lang.reports_cost_price'),
					lang('reports_lang.reports_sub_total_value'));
	}
	
    public function getData(array $inputs)
    {
    	
    	$builder = $this->db->table('raw_orders as ro')
    	         ->select('raw_items.name, raw_items.item_number, raw_items.category, raw_items.cost_price, roiq.available_quantity as quantity, SUM(roi.quantity) as qty')
    	         ->join('raw_order_items as roi','roi.order_id=ro.order_id')
    	         ->join('raw_order_item_quantities as roiq','roiq.item_id=roi.item_id')
    	         ->join('raw_items','raw_items.item_id=roiq.item_id')
    	//$this->db->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id', 'left');
    	         ->where('DATE(ro.updated_at) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');

    	if ($inputs['store_id'] != 'all' && $inputs['store_id'] != '0')
		{
			$builder->where('ro.store_id', $inputs['store_id'])
			       ->where('roiq.store_id', $inputs['store_id']);
		}

    	if($inputs['item_type'] != 'all'){
    		$builder->where('raw_items.item_type', $inputs['item_type']);
    	}

    	if ($inputs['item_from'] != 'all')
		{
			$builder->where('ro.person_id', $inputs['item_from']);
		}

		$builder->orderBy('raw_items.name')
    	        ->groupBy('raw_items.item_id');
        
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