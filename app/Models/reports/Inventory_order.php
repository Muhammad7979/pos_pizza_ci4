<?php
namespace App\Models\reports;

class Inventory_order extends Report
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('stock_reports_lang.reports_order_id'),
					lang('reports_lang.reports_category'),
					lang('stock_reports_lang.reports_description'),
					lang('stock_reports_lang.reports_order_quantity'),
					lang('stock_reports_lang.reports_order_quantity_received'),
					lang('stock_reports_lang.reports_order_time'),
					lang('stock_reports_lang.reports_order_status'));
	}
	
    public function getData(array $inputs)
    {
    	
    	// $this->db->select('raw_items.name, raw_items.item_number, raw_items.category, raw_items.cost_price, roiq.available_quantity as quantity');
    	$builder = $this->db->table('raw_orders as ro')
    	//$this->db->join('raw_order_items','raw_order_items.order_id=ro.order_id');
    	//$this->db->join('raw_order_item_quantities as roiq','roiq.item_id=raw_order_items.item_id');
    	//$this->db->join('raw_items','raw_items.item_id=roiq.item_id');
    	//$this->db->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id', 'left');

    	 ->where('DATE(ro.updated_at) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');

    	if ($inputs['store_id'] != 'all' && $inputs['store_id'] != '0')
		{
			$builder->where('ro.store_id', $inputs['store_id']);
		}

    	if($inputs['item_type'] != 'all'){
    		$builder->where('ro.category', $inputs['item_type']);
    	}

    	if($inputs['order_time'] != 'all'){
    		$builder->where('ro.order_time', $inputs['order_time']);
    	}

    	if ($inputs['item_from'] != 'all')
		{
			$builder->where('ro.person_id', $inputs['item_from']);
		}

		// $this->db->order_by('raw_items.name');
  //   	$this->db->group_by('raw_items.item_id');
        
        $data = $builder->get()->getResultArray();
        // foreach ($data as $key => $value) {
        // 	$data[$key]['sub_total_value'] = $value['quantity']*$value['cost_price'];
        // }
        return $data;
    }
	
	public function getSummaryData(array $inputs)
	{
		return array();
	}
}
?>