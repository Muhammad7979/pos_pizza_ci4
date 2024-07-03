<?php
namespace App\Models\reports;

class Inventory_pizza extends Report
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getDataColumns()
	{
		return array(lang('reports_lang.reports_item_name'),
					lang('reports_lang.reports_item_number'),
					lang('stock_reports_lang.reports_size'),
					lang('reports_lang.reports_quantity'),
					lang('reports_lang.reports_cost_price'),
					lang('reports_lang.reports_sub_total_value'),
					lang('stock_reports_lang.reports_order_status'));
	}
	
    public function getData(array $inputs)
    {
    	$builder = $this->db->table('pizza_order_items as poi')
    	                    ->select('raw_items.name, raw_items.item_number, raw_items.category, poi.size, poi.price as cost_price, SUM(poi.quantity) as qty, po.order_status') 
    	                     ->join('pizza_orders as po','po.order_id=poi.order_id')
    	                     ->join('raw_items','raw_items.item_id=poi.item_id')
    	//$this->db->join('raw_inventory as ri','ri.trans_items=raw_items.item_id');
    	                      ->where('raw_items.item_type', 4)
    	                      ->where('raw_items.is_pizza', 1)
    	                      ->where('po.deleted', 0);

    	if ($inputs['item_type'] != 'All' && $inputs['item_type'] != '0')
		{
    		$builder->where('po.order_status', $inputs['item_type']);
		}

		if ($inputs['branch_code'] != 'all' )
		{
    		$builder->where('po.branch_code', $inputs['branch_code']);
		}
		
  		$builder->where('DATE(po.updated_at) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');

    	if ($inputs['store_id']>0 && $inputs['store_id'] != 'all' && $inputs['store_id'] != '0')
		{
			$builder->where('po.store_id', $inputs['store_id']);
		}

		if ($inputs['counter_id']>0 && $inputs['counter_id'] != 'all' && $inputs['counter_id'] != '0')
		{
			$builder->where('po.person_id', $inputs['counter_id']);
		}

		$builder->orderBy('raw_items.name')
		        ->groupBy('po.order_status')
		        ->groupBy('poi.size')
		// $this->db->group_by('poi.dough');
		// $this->db->group_by('poi.layer');
		        ->groupBy('poi.is_half')
		        ->groupBy('poi.add_item_id')
    	        ->groupBy('raw_items.item_id');
        
        $data = $builder->get()->getResultArray();

        $get_size = ['','Mini','Small','Medium','Large','Xlarge'];
        $get_crust = ['Thick','Thin'];
        $get_dough = ['Plain','W WH'];
        $get_isHalf = ['No','Yes'];

        foreach ($data as $key => $value) {
        	$data[$key]['sub_total_value'] = $value['qty']*$value['cost_price'];
        	$data[$key]['size'] = $get_size[$value['size']];


        }
        return $data;

    }
	
	public function getSummaryData(array $inputs)
	{
		return array();
	}
}
?>