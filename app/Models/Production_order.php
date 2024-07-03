<?php
namespace App\Models;

use CodeIgniter\Model;

class Production_order extends Model
{
	/*
	Perform a search on items
	*/
	public function search($person_id = -1, $status = 'Pending', $order_id = -1, $rows=0, $limit_from=0, $sort='microtime', $order='asc')
	{ 

		$date = date("Y-m-d");

		$builder_counter_orders = $this->db->table('counter_orders as co')
		                    ->select('co.*, counters.company_name')
		                    ->join('counters','counters.person_id=co.person_id')
		                    ->where('co.store_id', $person_id)
		                    ->where('co.deleted', 0);
		
		if($order_id>0){
			$builder_counter_orders->where('co.order_id', $order_id);
		}

		$builder_counter_orders->where('co.order_status', $status)
		        ->where('DATE(co.updated_at)', $date)
		        ->orderBy($sort, $order);

		$data = $builder_counter_orders->get();	

		foreach ($data->getResult() as $key => $value) {
			
			$builer_counter_order_items = $this->db->table('counter_order_items as coi')
			                                       ->where('coi.order_id', $value->order_id);
			foreach ($builer_counter_order_items->get()->getResult() as $key2 => $value2) {
				if ($value2->type==5) {
					$builder =  $this->db->table('counter_order_items as coi')
					                     ->select('coi.*, ri.name')
					                     ->join('items as ri','ri.item_id=coi.item_id')
					                     ->where('coi.order_id', $value->order_id);
					$value->items = $builder->get()->getResult();
				}else{
					$builder =$this->db->table('counter_order_items as coi')
					                    ->select('coi.*, ri.name')
					                    ->join('raw_items as ri','ri.item_id=coi.item_id')
					                    ->where('coi.order_id', $value->order_id);
					$value->items = $builder->get()->getResult();
				}
			}
				
		}

		return $data;
	}

	public function skipping_order($order_id, $status)
	{

		$builder = $this->db->table('counter_orders')
		                   ->select('microtime')
		                   ->where('order_id >', $order_id)
		                   ->limit(1);
		$time =  $builder->get()->getRow();

		if($time){
			$microtime = $time->microtime+1;
		}else{
			$microtime = round(microtime(true) * 1000);
		}

		$this->db->table('counter_orders')->where('order_id', $order_id)

		         ->update(['order_status' => $status, 'microtime' => $microtime]);
	
	}

	public function delete_order($order_id, $status)
	{
		//$microtime = round(microtime(true) * 1000);

		$this->db->table('counter_orders')->where('order_id', $order_id)

		         ->update(['order_status' => $status, 'deleted' => 1]);
	}

	public function get_order_data($order_id='')
	{
		$builder = $this->db->table('counter_order_items as coi')
		                     ->where('coi.order_id', $order_id);
		$data = [];
		foreach ($builder->get()->getResult() as $key => $value) {
			$data['item_ids'][] = $value->item_id;
			$data['quantity'][] = $value->quantity;
			$data['type'][] = $value->type;
		}	
		return $data;
	}

	/*
    Determines if a given order_id is already received
    */
    public function checkOrderReceiving($order_id)
    {
       $builder = $this->db->table('counter_orders')
                           ->where('order_id', $order_id)
                           ->where('is_received', 1);

        return ($builder->get()->getNumRows() == 1);
    }

    /*
    Determines if a given order_id is delivered
    */
    public function checkOrderDelivery($order_id)
    {
        $builder = $this->db->table('counter_orders')
                             ->where('order_id', $order_id)
                             ->where('is_delivered', 1);

        return ($builder->get()->getNumRows() == 1);
    }

    /*
    Inserts or Updates Counter Order
    */
    public function save_production_order(&$counter_order_data, $order_id = FALSE)
    {
        if(!$order_id || !$this->exists($order_id))
        {
            if($this->db->table('counter_orders')->insert($counter_order_data))
            {
                $counter_order_data['order_id'] = $this->db->insertID();

                return TRUE;
            }

            return FALSE;
        }

       $builder = $this->db->table('counter_orders')->where('order_id', $order_id);

        return $builder->update($counter_order_data);
    }

    /*
    updates an order's items received quantity
    */
    public function update_production_order(&$raw_order_items_data, $order_id, $column)
    {
        $success = TRUE;
        
        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();
        
        foreach($raw_order_items_data as $row)
        {
            $row['order_id'] = $order_id;    
            $data = array(
                $column => $row[$column],
            );
            $builder = $this->db->table('counter_order_items')->where('order_id', $order_id)
                                ->where('item_id', $row['item_id'])
                                ->where('type', $row['type'])
                                ->update($data);
        }
        
        $this->db->transComplete();

        $success &= $this->db->transStatus();

        return $success;
    }

    /*
    Determines if a given order_id is an order
    */
    public function exists($order_id)
    {
        $builder = $this->db->table('counter_orders')
                            ->where('order_id', $order_id);

        return ($builder->get()->getNumRows() == 1);
    }
}
?>