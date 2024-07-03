<?php
namespace App\Models\reports;
class Inventory_warehouse extends Report
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
                            ->select('name, item_number, category, cost_price, location_name, quantity')
                            ->join('raw_item_quantities','raw_item_quantities.item_id=raw_items.item_id')
                            ->join('raw_inventory','raw_inventory.trans_items=raw_items.item_id')
                            ->join('stock_locations','stock_locations.location_id=raw_item_quantities.location_id')
                            ->where("Date(trans_date) BETWEEN " . $this->db->escape($inputs['start_date']) . " AND " . $this->db->escape($inputs['end_date']))
                            ->where('raw_items.item_type', 2);
        if ($inputs['person_id'] != 'all' && $inputs['person_id'] != '0')
		{
			$builder->where('raw_items.person_id', $inputs['person_id']);
		}
        $builder->orderBy('name')
                ->groupBy('trans_items');

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