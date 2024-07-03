<?php
namespace App\Models;
use CodeIgniter\Model;
use stdClass;
use App\Models\Appconfig;
use Predis\Client;
class Pizza_order extends Model
{
	/*
	Determines if a given item_id is an item kit
	*/
	public function exists($order_id)
	{
		$builder = $this->db->table('pizza_orders')
	                    	->where('order_id', $order_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('pizza_orders');

		return $this->db->count_all_results();
	}
	
	/*
	Gets information about a particular item kit
	*/
	public function get_info($order_id)
	{
		$builder = $this->db->table('pizza_orders')
		                    ->where('order_id', $order_id);
		
		$query = $builder->get();

		if($query->getNumRows()==1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $order_id is NOT an item kit
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->getFieldNames('pizza_orders') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	public function get_info_by_order_number($order_number)
	{
		$date = date("Y-m-d");
		$this->db->from('pizza_orders');
		$this->db->where('order_number', $order_number);
		$this->db->where('DATE(deliver_at)', $date);
		return $query = $this->db->get()->row();
	}

	/*
	Gets information about multiple item kits
	*/
	public function get_multiple_info($order_ids)
	{
		$this->db->from('pizza_orders');
		$this->db->where_in('order_id', $order_ids);
		$this->db->order_by('name', 'asc');

		return $this->db->get();
	}

	/*
	Inserts or updates an item kit
	*/
	public function save_pizza_order(&$pizza_order_data, $order_id = FALSE)
	{
		if(!$order_id || !$this->exists($order_id))
		{
			$builder = $this->db->table('pizza_orders');
			if($builder->insert($pizza_order_data))
			{
				$pizza_order_data['order_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}

		$this->db->where('order_id', $order_id);

		return $this->db->update('pizza_orders', $pizza_order_data);
	}

	public function redis_save($pizza_order_data,$pizza_order_items){
      
		$config = new Appconfig();
		if ($config->get('test_mode')) {
            return -2;
        }

        if (count($pizza_order_items) == 0) {
            return -1;
        }

        $redis = new Client();

		$redisId = time();

        //save id of this sale record to redis
        $redis->hset('pizza_sale:' . $redisId, 'id', "pizza_sale:$redisId");

		$redis->hset('pizza_sale:' . $redisId, 'pizza_sale_data', json_encode($pizza_order_data));

		$redis->hset('pizza_sale:' . $redisId, 'pizza_sale_items', json_encode($pizza_order_items));

        $redis->hset('pizza_sale:' . $redisId, 'status', 0);


	}

	/*
	Deletes one item kit
	*/
	public function delete_pizza_order($order_id)
	{
		$builder = $this->db->table('pizza_orders');
		return $builder->delete(array('order_id' => $order_id)); 	
	}

	/*
	Deletes a list of item kits
	*/
	public function delete_list($order_ids)
	{
		$this->db->where_in('order_id', $order_ids);

		return $this->db->delete('pizza_orders');		
 	}

	/*
	Perform a search on items
	*/
	public function search($store_id = -1, $status = 'Pending', $order_id = -1, $rows=0, $limit_from=0, $sort='microtime', $order='asc')
	{
		$date_today = date("Y-m-d");
		$date = date("Y-m-d H:i:s", strtotime('+30 minutes'));
		// $microtime = round(microtime($date) * 1000);
		// $this->db->select('po.store_id, po.order_id, po.order_status, po.count, po.order_number, po.deliver_at, poi.quantity, poi.price, poi.layer, poi.extras1, poi.extras2, poi.item_id, poi.add_item_id, poi.size, ri.name, ri2.name as other_item');
		$builder = $this->db->table('pizza_orders as po')
		//$this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		//$this->db->join('raw_items as ri','ri.item_id=poi.item_id');
		//$this->db->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left');

		          ->where('po.store_id', $store_id)
		          ->where('po.deleted', 0)
		          ->where('po.reordered', 0);
		
		if($order_id>0){
			$builder->where('po.order_id', $order_id);
		}

		$builder->where('po.order_status', $status)
		// $this->db->where('po.microtime <=', $microtime);
		        ->where('CAST(po.deliver_at as Date) <=', $date)
		        ->where('DATE(po.deliver_at)', $date_today)
		        ->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		$data = $builder->get();	

		$get_size = ['','Mini','Small','Medium','Large','Xlarge'];
		foreach ($data->getResult() as $key => $value) {
			
			$builder_pizza_order_items = $this->db->table('pizza_order_items as poi')
		                                          ->select('poi.*, ri.name, ri2.name as flavor2')
			                                      ->join('raw_items as ri','ri.item_id=poi.item_id')
			                                      ->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left')
			                                      ->where('poi.order_id', $value->order_id);
			$value->items = $builder_pizza_order_items->get()->getResult();

			foreach ($value->items as $key2 => $value2) {
				$value2->layer = ($value2->layer == 0) ? 'Thick' : 'Thin';
				$value2->dough = ($value2->dough == 0) ? 'Plain' : 'W WH';
				$value2->is_half = ($value2->is_half == 0) ? 'No' : 'Yes';
				$value2->size = $get_size[$value2->size];
			}
				
		}

		return $data;
	}

	public function searchLatest($store_id = -1, $status = 'Pending', $order_id = -1, $rows=0, $limit_from=0, $sort='microtime', $order='asc')
	{

		$date_today = date("Y-m-d");
		$date = date("Y-m-d H:i:s", strtotime('+30 minutes'));
		// $microtime = round(microtime($date) * 1000);
		// $this->db->select('po.store_id, po.order_id, po.order_status, po.count, po.order_number, po.deliver_at, poi.quantity, poi.price, poi.layer, poi.extras1, poi.extras2, poi.item_id, poi.add_item_id, poi.size, ri.name, ri2.name as other_item');
		$builder = $this->db->table('pizza_orders as po')
		//$this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		//$this->db->join('raw_items as ri','ri.item_id=poi.item_id');
		//$this->db->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left');

		                      ->where('po.store_id', $store_id)
		                      ->where('po.deleted', 0)
		                      ->where('po.reordered', 0);
		
		if($order_id>0){
			$builder->where('po.order_id', $order_id);
		}

		$builder->where('po.order_status', $status)
		// $this->db->where('po.microtime <=', $microtime);
		// $this->db->where('po.deliver_at >', $current_date);
		// $this->db->where('po.deliver_at <=', $plus_date);
		        ->where('CAST(po.deliver_at as Date) <=', $date)
		        ->where('DATE(po.deliver_at)', $date_today)
		        ->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		$data = $builder->get();	

		$get_size = ['','Mini','Small','Medium','Large','Xlarge'];
		foreach ($data->getResult() as $key => $value) {
			
			$builder = $this->db->table('pizza_order_items as poi')
			                    ->select('poi.*, ri.name, ri2.name as flavor2')
			                    ->join('raw_items as ri','ri.item_id=poi.item_id')
			                    ->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left')
			                    ->where('poi.order_id', $value->order_id);
			$value->items = $builder->get()->getResult();

			foreach ($value->items as $key2 => $value2) {
				$value2->layer = ($value2->layer == 0) ? 'Thick' : 'Thin';
				$value2->dough = ($value2->dough == 0) ? 'Plain' : 'W WH';
				$value2->is_half = ($value2->is_half == 0) ? 'No' : 'Yes';
				$value2->size = $get_size[$value2->size];
			}
				
		}

		return $data;
	}
	
	public function get_found_rows($store_id = -1, $order_id = -1, $search)
	{
		$this->db->from('pizza_orders');
		//$this->db->like('name', $search);
		$this->db->or_like('description', $search);
		if($store_id > 0)
		{
			$this->db->where('store_id', $store_id);
		}
		if($order_id > 0)
		{
			$this->db->where('order_id', $order_id);
		}
		return $this->db->get()->num_rows();
	}

	public function exists_order_today($date)
    {
       $builder = $this->db->table('pizza_orders')
                           ->select('count')
                           ->where('DATE(deliver_at)', $date)
                           ->orderBy('order_id', 'desc')
                           ->limit(1);
        return $builder->get()->getRow();
    }

    /*
	Perform a search on items
	*/
	public function get_pizza_menu()
	{
		$builder = $this->db->table('raw_items')
		                    ->where('raw_items.item_type', 4)
		                    ->where('raw_items.is_pizza', 1)
		                    ->orderBy('item_id', 'asc');
		$data =  $builder->get();	

		foreach ($data->getResult() as $key => $value) {
			$raw_item_attributes = $this->db->table('raw_item_attributes')
			         ->where('raw_item_attributes.item_id', $value->item_id)
			         ->where('raw_item_attributes.attribute_category', 0)
			         ->where('raw_item_attributes.attribute_price >', 0)
			         ->where('raw_item_attributes.deleted', 0);
			$sizes =  $raw_item_attributes->get();
			$value->sizes = $sizes->getResult();

			$raw_item_attributes_ingredients = $this->db->table('raw_item_attributes')
			                   ->where('raw_item_attributes.item_id', $value->item_id)
			                   ->where('raw_item_attributes.attribute_category', 2)
			                   ->where('raw_item_attributes.deleted', 0);
			$ingredients =  $raw_item_attributes_ingredients->get();
			$value->ingredients = $ingredients->getResult();

			$string = '';
			$ids = '';
			foreach ($ingredients->getResult() as $key => $ingredient) {
				$string .= $ingredient->attribute_title.", ";
				$ids .= $ingredient->attribute_id.", ";
			}
			$value->ingredients = rtrim($string, ', ');
			$value->ingredient_ids = rtrim($ids, ', ');
		}

		return $data;
	}

	public function get_pExtras($item_id='', $extras='')
	{
		$this->db->select('attribute_title as label');
		$this->db->from('raw_item_attributes');
		$this->db->where('raw_item_attributes.item_id', $item_id);
		$this->db->where_in('raw_item_attributes.attribute_id', $extras);
		$this->db->where('raw_item_attributes.attribute_category', 1);
		$this->db->where('raw_item_attributes.deleted', 0);
		return $this->db->get()->result();
	}


	public function get_sizes($item_id='', $size='')
	{
		$this->db->select('attribute_title as label');
		$this->db->from('raw_item_attributes');
		$this->db->where('raw_item_attributes.item_id', $item_id);
		$this->db->where('raw_item_attributes.attribute_id', $size);
		$this->db->where('raw_item_attributes.attribute_category', 0);
		$this->db->where('raw_item_attributes.deleted', 0);
		return $this->db->get()->row();
	}

	public function get_extras($item_id='')
	{
		$builder = $this->db->table('raw_item_attributes')
		                    ->select('attribute_id as value, attribute_title as label, attribute_price as price')
		                    ->where('raw_item_attributes.item_id', $item_id)
		                    ->where('raw_item_attributes.attribute_category', 1)
		                    ->where('raw_item_attributes.deleted', 0);
		$extras =  $builder->get();
		
		foreach ($extras->getResult() as $key => $value) {
			$value->price = parse_decimals($value->price);
		}
		return $extras->getResult();
	}

	public function get_ingredients($item_id='')
	{
		$builder = $this->db->table('raw_item_attributes')
		           ->select('attribute_id as value, attribute_title as label, attribute_price as price')
		           ->where('raw_item_attributes.item_id', $item_id)
		           ->where('raw_item_attributes.attribute_category', 2)
		           ->where('raw_item_attributes.deleted', 0);
		$ingredients =  $builder->get();
		
		foreach ($ingredients->getResult() as $key => $value) {
			$value->price = parse_decimals($value->price);
		}
		return $ingredients->getResult();
	}

	public function get_items($item_id=-1, $size=-1)
	{
        $builder = $this->db->table('raw_items')
		         ->select('item_id, name, category')
                 ->where('item_id !=', $item_id)
                 ->where('item_type', 4)
                 ->where('is_pizza', 1)
                 ->where('deleted', 0);
        $item_list =  $builder->get();
        
        //$items = [];
        foreach ($item_list->getResult() as $key => $value) {
            $builder = $this->db->table('raw_item_attributes')
                                ->where('item_id', $value->item_id)
                                ->where('attribute_id', $size)
                                ->where('attribute_category', 0);
            $attr =  $builder->get()->getRow();
            $value->price = parse_decimals($attr->attribute_price);
        }
        return $item_list->getResult();
	}

	public function update_status($id, $status)
	{
		$microtime = round(microtime(true) * 1000);

		$builder = $this->db->table('pizza_orders')->where('order_id', $id);

		return $builder->update(['order_status' => $status, 'microtime' => $microtime]);
	}

	public function update_read_status($id)
	{
		$this->db->where('order_id', $id);

		return $this->db->update('pizza_orders', ['read_status' => 0]);
	}

	public function skipping_order($order_id, $status)
	{
		$this->db->select('microtime');
		$this->db->from('pizza_orders');
		$this->db->where('order_id >', $order_id);
		$this->db->limit(1);
		$time =  $this->db->get()->row();

		if($time){
			$microtime = $time->microtime+1;
		}else{
			$microtime = round(microtime(true) * 1000);
		}
		$this->db->where('order_id', $order_id);

		return $this->db->update('pizza_orders', ['order_status' => $status, 'microtime' => $microtime]);
		
	}

	public function delete_order($order_id, $status)
	{
		$microtime = round(microtime(true) * 1000);

		$this->db->where('order_id', $order_id);

		return $this->db->update('pizza_orders', ['order_status' => $status, 'microtime' => $microtime, 'deleted' => 0]);
	}

	public function get_found_item($order_id = -1)
	{	
		$builder = $this->db->table('pizza_order_items as poi')
		                    ->select('ri.item_id, ri.name')
		                    ->join('raw_items as ri','ri.item_id=poi.item_id');
		if($order_id > 0)
		{
			$builder->where('poi.order_id', $order_id);
		}
		return $builder->get()->getRow();
	}

	public function get_orders($store_id='', $status='')
	{

		$date = date("Y-m-d");

		$builder = $this->db->table('pizza_orders as po')
		                    ->select('po.microtime, po.order_id, po.order_number')
		// $this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		// $this->db->join('raw_items as ri','ri.item_id=poi.item_id');
		                    ->where('po.store_id', $store_id)
		                    ->where('po.order_status', $status);
		if($status!=='Completed'){
			$builder->where('po.reordered', 0);
		}
		$builder->where('po.deleted', 0)
		        ->where('DATE(po.deliver_at)', $date)
		        ->orderBy('po.microtime', 'desc');
		// $this->db->limit(10);

		$data = $builder->get();	

		$data = array_reverse($data->getResult());

		$get_size = ['','Mini','Small','Medium','Large','Xlarge'];
		foreach ($data as $key => $value) {
			
			$builder = $this->db->table('pizza_order_items as poi')
			                    ->select('poi.*, ri.name, ri2.name as flavor2')
			                    ->join('raw_items as ri','ri.item_id=poi.item_id')
			                    ->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left')
			                    ->where('poi.order_id', $value->order_id);
			$value->items = $builder->get()->getResult();

			foreach ($value->items as $key2 => $value2) {
				$value2->layer = ($value2->layer == 0) ? 'Thick' : 'Thin';
				$value2->dough = ($value2->dough == 0) ? 'Plain' : 'W WH';
				$value2->is_half = ($value2->is_half == 0) ? 'No' : 'Yes';
				$value2->size = $get_size[$value2->size];

				$value2->ingredients1_Array = [];
				if($value2->ingredients1_title!='')
				$value2->ingredients1_Array = explode(', ',$value2->ingredients1_title);

				$value2->ingredients2_Array = [];
				if($value2->ingredients2_title!='')
				$value2->ingredients2_Array = explode(', ',$value2->ingredients2_title);

				$value2->extras1_Array = [];
				if($value2->extras1_title!='')
				$value2->extras1_Array = explode(', ',$value2->extras1_title);

				$value2->extras2_Array = [];
				if($value2->extras2_title!='')
				$value2->extras2_Array = explode(', ',$value2->extras2_title);
			}
				
		}

		return $data;
	}

	public function get_canceled_orders($store_id='', $status='')
	{

		$date = date("Y-m-d");

		$this->db->select('po.microtime, po.order_id, po.order_number');
		$this->db->from('pizza_orders as po');
		// $this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		// $this->db->join('raw_items as ri','ri.item_id=poi.item_id');

		$this->db->where('po.store_id', $store_id);
	
		$this->db->where('po.order_status', $status);
		$this->db->where('DATE(po.deliver_at)', $date);
		$this->db->order_by('po.microtime', 'desc');
		// $this->db->limit(10);

		$data = $this->db->get();	

		$data = array_reverse($data->result());

		$get_size = ['','Mini','Small','Medium','Large','Xlarge'];
		$dataItems = [];
		foreach ($data as $key => $value) {
			
			$this->db->select('poi.*, ri.name, ri2.name as flavor2');
			$this->db->from('pizza_order_items as poi');
			$this->db->join('raw_items as ri','ri.item_id=poi.item_id');
			$this->db->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left');
			$this->db->where('poi.order_id', $value->order_id);
			$value->items = $this->db->get()->result();

			foreach ($value->items as $key2 => $value2) {
				$value2->layer = ($value2->layer == 0) ? 'Thick' : 'Thin';
				$value2->dough = ($value2->dough == 0) ? 'Plain' : 'W WH';
				$value2->is_half = ($value2->is_half == 0) ? 'No' : 'Yes';
				$value2->size = $get_size[$value2->size];
				$dataItems[] = $value2;
			}
				
		}
		
		return $dataItems;
	}

	public function get_updated_row($microtime, $status, $store_id)
	{

		$date = date("Y-m-d");

	$builder = $this->db->table('pizza_orders as po')
		         ->select('po.microtime, po.order_id, po.order_number')
		// $this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		// $this->db->join('raw_items as ri','ri.item_id=poi.item_id');

		        ->where('po.store_id', $store_id)
	
		        ->where('po.order_status', $status)
		        ->where('po.microtime >', $microtime)
		        ->where('DATE(po.deliver_at)', $date)
		//$this->db->order_by('po.updated_at', 'desc');
		         ->limit(1);

		$data = $builder->get()->getRow();	

		return $data;
	}


	// Get Pizza order Products list
	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();
		$date = date("Y-m-d");
		$this->db->select('po.order_number, po.order_id');
		$this->db->from('pizza_orders as po');
		//$this->db->join('pizza_order_items as poi','poi.order_id=po.order_id');
		$this->db->like('po.order_number', $search);
		$this->db->like('po.read_status', 0);
		//$this->db->order_by('name', 'asc');
		$this->db->where('DATE(po.deliver_at)', $date);
		
		foreach($this->db->get()->result() as $row)
		{

			$suggestions[] = array('value' => $row->order_number, 'label' => $row->order_number);
		}
		

		//only return $limit suggestions
		if(count($suggestions > $limit))
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

	public function get_pizza_item_info($item_kit_id)
	{
		$this->db->select('items.item_id, poi.quantity, poi.price');
		$this->db->from('pizza_order_items as poi');
		$this->db->join('items','poi.item_number=items.item_number');
		$this->db->where('poi.order_id', $item_kit_id);
		
		//return an array of item kit items for an item
		return $this->db->get()->result_array();
	}

	public function updateOrder($order_id,$order_quantity, $order_price)
	{
		$this->db->where('order_id', $order_id);
		return $this->db->update('pizza_orders', ['order_quantity' => $order_quantity, 'order_price' => $order_price]);
	}

	public function get_pizza_order_info($id){

		$pizza_order = $this->db->table('pizza_orders')->where('order_id',$id)->get()->getRow();
		$pizza_order_items = $this->db->table('pizza_order_items')->where('order_id',$id)->get()->getResultArray();

		return [
			'pizza_order' => $pizza_order,
			'pizza_order_items' => $pizza_order_items,
		];

	}

	public function pizza_item_info($item_number,$item_size){

		$pizza_item_info = $this->db->table('pizza_item_list')
		                   ->where(['item_number'=>$item_number,'size'=>$item_size])
						   ->get()->getRow();
        return $pizza_item_info;
	}

	public function pizza_order_completed($pizza_order_data,$pizza_order_items){

		$order_data = [

			'sale_time' => $pizza_order_data->updated_at,
			'employee_id' => $pizza_order_data->person_id,
			'pizza_invoice' => $pizza_order_data->order_number,
			'order_id' => $pizza_order_data->order_id,
		];
		
		if($this->db->table('order_completed')->insert($order_data)){

           $sale_id = $this->db->insertID();


		   foreach($pizza_order_items as $key=>$item){
		    
			   $order_items = [];

			   $pizza_item_info = $this->pizza_item_info($item['item_number'],$item['size']);

			   $order_items=[
				 'sale_id' => $sale_id,
				 'item_id' => $item['item_id'],
				 'item_number'=>$item['item_number'],
				 'item_name'=>$pizza_item_info->name,
				 'description' => '',
				 'serialnumber' => '',
				 'line' => $key,
				 'quantity_purchased' => $item['quantity'],
				 'item_cost_price' => $item['sub_total'],
				 'item_unit_price' => $item['sub_total'],
				 'discount_percent' => 0,
				 'item_location' => 1
			   ];

			   $this->db->table('order_completed_items')->insert($order_items);
			   
		   }
		}

	}


	public function get_pizza_completed_order(){

	 $completed_order = $this->db->table('order_completed')->get()->getResultArray();
	 $completed_order_items = $this->db->table('order_completed_items')->get()->getResultArray();
	 return [
		'completed_order' => $completed_order,
		'completed_order_items' => $completed_order_items,
	 ];

	}

	public function delete_pizza_completed_order($id){

		$completed_order = $this->db->table('order_completed')->where('sale_id',$id)->get();

		if($completed_order->getNumRows() == 1){

		$delete_order_items = $this->db->table('order_completed_items')->where('sale_id',$id)->delete();
        if($delete_order_items){

			$delete_order = $this->db->table('order_completed')->where('sale_id',$id)->delete();

			if($delete_order){ 
				return ['message'=>'Successfully deleted'];
			}else{
				 return ['error'=>'Error! deleting the completed pizza order.(pizza_order)'];
			}

		}

		return ['messgae' => 'No order found in database with given id.(pizza_order)'];

		}
	
	
   
	   }

}
?>