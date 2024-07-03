<?php
namespace App\Models\reports;

class Inventory_discard_item extends Report
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
					lang('reports_lang.reports_quantity'),
					lang('reports_lang.reports_cost_price'),
					lang('reports_lang.reports_sub_total_value'),
                    lang('reports_lang.reports_discard_type'));
	}
	
    public function getData(array $inputs)
    {

    	$discard_type = [
            1 => 'Discard',
            2 => 'Waste',
            3 => 'Expired',
            4 => 'Damage',
            5 => 'Left Over',
        ];

        //$this->db->select('raw_items.item_number, raw_items.name, raw_items.cost_price, raw_items.category, cdi.*');
        $builder = $this->db->table('counter_discard_inventory as cdi')
        // $this->db->join('counter_order_items as coi','coi.order_id=counter_orders.order_id');
        //$this->db->join('raw_items','raw_items.item_id=cdi.trans_items');
        // $this->db->join('counters','counters.person_id=counter_orders.person_id');
        
                        ->where('DATE(trans_date) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');


        if ($inputs['counter_id'] != 'all' && $inputs['counter_id'] != '0')
		{
			$builder->where('cdi.trans_user', $inputs['counter_id']);
		}

		// $this->db->where('counter_orders.is_delivered', 1);
         // $this->db->order_by('raw_items.name');

        $data = $builder->get()->getResultArray();
        foreach ($data as $key => $value) {

        	if($value['item_type']==5){
        		$builer_items = $this->db->table('items')
        		                     ->select('items.item_number, items.name, items.cost_price, items.category')
        		                     ->where('items.item_id', $value['trans_items']);
        		$row = $builer_items->get()->getRow();
        	}else{
        		$builer_raw_items = $this->db->table('raw_items')
        		                             ->select('raw_items.item_number, raw_items.name, raw_items.cost_price, raw_items.category')
        		                             ->where('raw_items.item_id', $value['trans_items']);
        		$row = $builer_raw_items->get()->getRow();
        		
        	}
        	$data[$key]['cost_price'] = $row->cost_price;
        	$data[$key]['name'] = $row->name;
        	$data[$key]['item_number'] = $row->item_number;
        	$data[$key]['category'] = $row->category;
        	$data[$key]['sub_total_value'] = $value['trans_inventory']*$data[$key]['cost_price'];
        	$data[$key]['discard_type'] = $discard_type[$value['discard_type']];

        }
        return $data;
    }
	
	public function getSummaryData(array $inputs)
	{
		return array();
	}
}
?>