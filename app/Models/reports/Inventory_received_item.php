<?php
namespace App\Models\reports;

class Inventory_received_item extends Report
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
                    lang('stock_reports_lang.reports_counter_store'),
                    lang('reports_lang.reports_quantity'),
                    lang('reports_lang.reports_cost_price'),
                    lang('reports_lang.reports_sub_total_value'));
    }
    
    public function getData(array $inputs)
    {
        $builder = $this->db->table('counter_orders')
                            ->select('SUM(coi.received_quantity) as quantity, coi.item_id, coi.type, counter_orders.person_id, counter_orders.store_id')
                            ->join('counter_order_items as coi','coi.order_id=counter_orders.order_id')
        //$this->db->join('raw_items','raw_items.item_id=coi.item_id');

                            ->where('DATE(updated_at) BETWEEN "'. date('Y-m-d', strtotime($inputs['start_date'])). '" AND "'. date('Y-m-d', strtotime($inputs['end_date'])).'"');

        if ($inputs['counter_id'] != 'all' && $inputs['counter_id'] != '0')
        {
            $builder->where('counter_orders.person_id', $inputs['counter_id']);
        }
        $builder->where('counter_orders.is_delivered', 1);
        //$this->db->order_by('raw_items.name');
        $builder->groupBy('coi.item_id');

        $data = $builder->get()->getResultArray();
        foreach ($data as $key => $value) {

            if($value['type']==5){
                $builder_item = $this->db->table('items')
                                         ->select('items.item_number, items.name, items.cost_price, items.category')
                                         ->where('items.item_id', $value['item_id']);
                $row = $builder_item->get()->getRow();
            }else{
                $builder_raw_items = $this->db->table('raw_items')
                                          ->select('raw_items.item_number, raw_items.name, raw_items.cost_price, raw_items.category')
                                          ->where('raw_items.item_id', $value['item_id']);
                $row = $builder_raw_items->get()->getRow();
                
            }
            $data[$key]['cost_price'] = $row->cost_price;
            $data[$key]['name'] = $row->name;
            $data[$key]['item_number'] = $row->item_number;
            $data[$key]['category'] = $row->category;

            $data[$key]['sub_total_value'] = $value['quantity']*$data[$key]['cost_price'];
        }
        return $data;
    }
    
    public function getSummaryData(array $inputs)
    {
        return array();
    }
}
?>