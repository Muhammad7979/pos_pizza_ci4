<?php
namespace App\Models\apis;

use CodeIgniter\Model;

class User extends Model 
{
    
    protected $store_orders_table;
    protected $store_items_table;
    protected $raw_items_table;
    function __construct()
    {

        parent::__construct();

        $this->store_orders_table = 'raw_orders';
        $this->store_items_table = 'raw_order_items';
        $this->raw_items_table = 'raw_items';

    }

    /* GET Store Products List */

    public function store_products_list($id='')
    {
        $query = $this->db->select('raw_items.item_id,raw_items.name, raw_items.item_type as type, raw_items.item_attributes')
                ->join('raw_order_item_quantities as roiq','roiq.item_id=raw_items.item_id')
                ->group_by('raw_items.item_id')
                ->get_where('raw_items', array('roiq.store_id' => $id, 'raw_items.deleted' => 0));


        $data = $query->result();
        foreach ($data as $key=>$value) {
            $value->has_attributes = false;
            
            if($value->item_attributes==1)
                $value->has_attributes = true;
        }
        return $data;

    }

    /* GET Counter Products List */

    public function counter_products_list($id='')
    {
        // return $id;
        $query = $this->db->select('raw_items.item_id,raw_items.name, roi.type, raw_items.item_attributes')
                ->join('counter_order_items as roi','roi.order_id=counter_orders.order_id')
                ->join('counter_order_item_quantities as roiq','roiq.item_id=roi.item_id')
                ->join('raw_items','raw_items.item_id=roi.item_id')
                ->group_by('raw_items.item_id')
                ->get_where('counter_orders', array('counter_orders.person_id' => $id, 'counter_orders.deleted' => 0));

        $data = $query->result();

        foreach ($data as $key=>$value) {
            $value->has_attributes = false;
            if($value->item_attributes == 1)
                $value->has_attributes = true;
        }

        return $data;
    }

    /* GET Production Products List */

    public function production_products_list($id='')
    {
        $this->db->from('counter_items');
        $this->db->where('counter_items.counter_id',$id);
        
        $data = $this->db->get()->result_array();
        $nArray = [];
        foreach ($data as $value) {
            $name = '';
            if($value['category']==2){
                $query = $this->db->select('raw_items.name')
                ->get_where('raw_items', array('raw_items.item_id' => $value['item_id'], 'raw_items.deleted' => 0));
                if($query->row())
                $name = $query->row()->name;
                $has_attributes = true;
                $type = 4;
            }else{
                $query = $this->db->select('items.name')
                ->get_where('items', array('items.item_id' => $value['item_id'], 'items.deleted' => 0));
                if($query->row())
                $name = $query->row()->name;
                $has_attributes = false;
                $type = 5;
            }
            if($name){
                $nArray[] = [
                    'item_id' => $value['item_id'],
                    'name' => $name,
                    'type' => $type,
                    'has_attributes' => $has_attributes,
                ];
            }
        }

        return $nArray;
    }

    /* GET Production Products Attributes List */

    public function production_products_attributes_list($id='')
    {
        $this->db->select('attribute_id as value, attribute_title as label, attribute_price as price');
        $this->db->from('raw_item_attributes');
        $this->db->where('raw_item_attributes.item_id',$id);
        $this->db->where('raw_item_attributes.deleted',0);
        $this->db->where('raw_item_attributes.attribute_category',0);
        $data['size'] = $this->db->get()->result_array();

        $this->db->select('attribute_id as value, attribute_title as label, attribute_price as price');
        $this->db->from('raw_item_attributes');
        $this->db->where('raw_item_attributes.item_id',$id);
        $this->db->where('raw_item_attributes.deleted',0);
        $this->db->where('raw_item_attributes.attribute_category',1);
        $data['extras'] = $this->db->get()->result_array();

        return $data;

    }

    /* GET Store Counters List */

    public function counters_list($id = null, $store = null)
    {
        //return $id;

        $query = $this->db->select('counters.person_id as counter_id, counters.category as type, counters.company_name as counter_name')
                ->get_where('counters', array('counters.person_id !=' => $store, 'counters.store_id' => $id, 'counters.deleted' => 0, 'counters.category' => 1));

        return $query->result();
    }

    /* GET Store Production List */

    public function production_list($id = null, $store = null, $type = 0)
    {
        //return $id;

        $query = $this->db->select('counters.person_id as counter_id, counters.category as type, counters.company_name as counter_name')
                ->get_where('counters', array('counters.person_id !=' => $store, 'counters.store_id' => $id, 'counters.deleted' => 0, 'counters.category' => 2, 'counters.special_counter' => $type));

        return $query->result();
    }

    public function order_items($data = null, $items = null)
    {
        if($this->db->insert('counter_orders', $data))
        {
            $last_id = $this->db->insert_id();

            // $new_items = [];
            // foreach ($items as $value) {
            //     $new_items[] = [
            //         'order_id' => $last_id,
            //         'item_id' => $value['item_id'],
            //         'quantity' => $value['quantity'],
            //         'type' => $value['type'],
            //         'attribute' => $value['attribute'],
            //     ];
            // }
            foreach ($items as $key=>$value) {
                    $items[$key]['order_id'] = $last_id;
            }
            
            $this->db->insert_batch('counter_order_items', $items);

            return $last_id;
            //return True;
        }
        return False;
    }

    public function pizza_order_items($data = null, $items = null)
    {
        if($this->db->insert('pizza_orders', $data))
        {
            $last_id = $this->db->insert_id();

            $items['order_id'] = $last_id;
            //return $items;
            // foreach ($items as $key=>$value) {
            //         $items[$key]['order_id'] = $last_id;
            // }
            $this->db->insert('pizza_order_items', $items);

            return $last_id;
            //return True;
        }
        return False;
    }

    /*
    Perform a search on items
    */
    public function search($store_id = -1, $person_id = -1, $order_id = -1, $is_deliverable = -1)
    {
        $this->db->from('counter_orders');
        
        if($person_id > 0)
        {
            $this->db->where('person_id', $person_id);
        }

        if($store_id > 0)
        {
            $this->db->where('store_id', $store_id);
        }

        if($order_id > 0)
        {
            $this->db->where('order_id', $order_id);
        }

        if($is_deliverable > 0)
        {
            $this->db->where('is_deliverable', $is_deliverable);
        }

        $this->db->order_by('order_id', 'desc');

        $query = $this->db->get();
        $data = $query->result();

        foreach ($data as $order) {

            if ($order->is_deliverable==0) {
                $order->store_name = $this->get_company_name($order->store_id,'stores')->company_name;
            }else{
                $order->store_name = $this->get_company_name($order->person_id,'counters')->company_name;
            }

            $this->db->select('counter_order_items.*');
            $this->db->from('counter_order_items');

            $this->db->where('order_id', $order->order_id);
            $query = $this->db->get();
            $order->items = $query->result();
            foreach ($order->items as $value) {
                if ($value->type==5) {
                    //get pos item name
                    $value->name = $this->get_product_name($value->item_id,'items')->name;
                }else{
                    //get raw item name
                    $value->name = $this->get_product_name($value->item_id,'raw_items')->name;
                }
            }
        }
        
        return $data;    
    }

    /*
    Inserts or Updates Counter Order
    */
    public function save_user(&$counter_order_data, $order_id = FALSE)
    {
        if(!$order_id || !$this->exists($order_id))
        {
            if($this->db->insert('counter_orders', $counter_order_data))
            {
                $counter_order_data['order_id'] = $this->db->insert_id();

                return TRUE;
            }

            return FALSE;
        }

        $this->db->where('order_id', $order_id);

        return $this->db->update('counter_orders', $counter_order_data);
    }

    /*
    updates an order's items received quantity
    */
    public function update_user(&$raw_order_items_data, $order_id, $column)
    {
        $success = TRUE;
        
        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->trans_start();
        
        foreach($raw_order_items_data as $row)
        {
            $row['order_id'] = $order_id;    
            $data = array(
                $column => $row[$column],
            );
            $this->db->where('order_id', $order_id);
            $this->db->where('item_id', $row['item_id']);
            $this->db->where('type', $row['type']);
            $this->db->update('counter_order_items', $data);
        }
        
        $this->db->trans_complete();

        $success &= $this->db->trans_status();

        return $success;
    }

    /*
    Determines if a given order_id is an order
    */
    public function exists($order_id)
    {
        $this->db->from('counter_orders');
        $this->db->where('order_id', $order_id);

        return ($this->db->get()->num_rows() == 1);
    }

    /*
    Determines if a given order_id is already received
    */
    public function checkOrderReceiving($order_id)
    {
        $this->db->from('counter_orders');
        $this->db->where('order_id', $order_id);
        $this->db->where('is_received', 1);

        return ($this->db->get()->num_rows() == 1);
    }

    /*
    Determines if a given order_id is delivered
    */
    public function checkOrderDelivery($order_id)
    {
        $this->db->from('counter_orders');
        $this->db->where('order_id', $order_id);
        $this->db->where('is_delivered', 1);

        return ($this->db->get()->num_rows() == 1);
    }

    /*
    Perform a search on store items
    */
    public function counter_items($person_id = -1)
    {
        $this->db->select('raw_items.item_id,roiq.available_quantity,raw_items.name,counter_orders.store_id,counter_order_items.type');

        $this->db->from('counter_order_item_quantities as roiq');
        $this->db->join('counter_order_items', 'counter_order_items.item_id = roiq.item_id', 'full');
        $this->db->join('counter_orders', 'counter_orders.order_id = counter_order_items.order_id', 'full');

        $this->db->join('raw_items', 'raw_items.item_id = roiq.item_id', 'full');

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('roiq.item_id');
        
        // order by name of item
        $this->db->order_by('raw_items.name', 'asc');

        $this->db->where('roiq.store_id', $person_id);
        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(roiq.created_at)', $date);
        $this->db->where_in('counter_order_items.type', [1,2,3,4]);

        $data = $this->db->get();

        $this->db->select('items.item_id,roiq.available_quantity,items.name,counter_orders.store_id,counter_order_items.type');
        $this->db->from('counter_order_item_quantities as roiq');
        $this->db->join('counter_order_items', 'counter_order_items.item_id = roiq.item_id', 'full');
        $this->db->join('counter_orders', 'counter_orders.order_id = counter_order_items.order_id', 'full');

        // pos items
        $this->db->join('items', 'items.item_id = roiq.item_id', 'full');

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('roiq.item_id');
        
        // order by id of item
        $this->db->order_by('items.item_id', 'asc');

        $this->db->where('roiq.store_id', $person_id);
        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(roiq.created_at)', $date);
        $this->db->where('counter_order_items.type', 5);

        $data2 = $this->db->get();

        $data3 =  array_merge($data->result(),$data2->result());

        foreach ($data3 as $value) {
            if($value->type==4 || $value->type==5){
                $value->store_name = $this->get_company_name($value->store_id,'counters')->company_name;
            }else{
                $value->store_name = $this->get_company_name($value->store_id,'stores')->company_name;
            }
        }

        return $data3;
    }

    public function counter_items_for_report($person_id = -1)
    {
        $this->db->select('raw_items.item_id,roiq.available_quantity,raw_items.name,counter_orders.store_id,counter_order_items.type, raw_items.category');

        $this->db->from('counter_order_item_quantities as roiq');
        $this->db->join('counter_order_items', 'counter_order_items.item_id = roiq.item_id', 'full');
        $this->db->join('counter_orders', 'counter_orders.order_id = counter_order_items.order_id', 'full');

        $this->db->join('raw_items', 'raw_items.item_id = roiq.item_id', 'full');

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('roiq.item_id');
        
        // order by name of item
        $this->db->order_by('raw_items.name', 'asc');

        $this->db->where('roiq.store_id', $person_id);
        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(roiq.created_at)', $date);
        $this->db->where_in('counter_order_items.type', [1,2,3,4]);

        $data = $this->db->get();

        $this->db->select('items.item_id,roiq.available_quantity,items.name,counter_orders.store_id,counter_order_items.type, items.category');
        $this->db->from('counter_order_item_quantities as roiq');
        $this->db->join('counter_order_items', 'counter_order_items.item_id = roiq.item_id', 'full');
        $this->db->join('counter_orders', 'counter_orders.order_id = counter_order_items.order_id', 'full');

        // pos items
        $this->db->join('items', 'items.item_id = roiq.item_id', 'full');

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('roiq.item_id');
        
        // order by id of item
        $this->db->order_by('items.item_id', 'asc');

        $this->db->where('roiq.store_id', $person_id);
        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(roiq.created_at)', $date);
        $this->db->where('counter_order_items.type', 5);

        $data2 = $this->db->get();

        $data3 =  array_merge($data->result(),$data2->result());

        foreach ($data3 as $value) {
            if($value->type==4 || $value->type==5){
                $value->store_name = $this->get_company_name($value->store_id,'counters')->company_name;
            }else{
                $value->store_name = $this->get_company_name($value->store_id,'stores')->company_name;
            }
        }

        return $data3;
    }

    public function exists_item_discard($item_id, $person_id=-1, $item_type = -1)
    {
        $this->db->from('counter_discard_inventory');
        $this->db->where('trans_items', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->where('trans_user', $person_id);
        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(trans_date)', $date);

        return ($this->db->get()->num_rows() == 1);
    }

    public function discard_counter_item($items_detail)
    {

        
        if(!$this->exists_item_discard($items_detail['trans_items'], $items_detail['trans_user'], $items_detail['item_type']))
        {
            return $this->db->insert('counter_discard_inventory', $items_detail);

        }else{

            // get old quantity
            $date = date("Y-m-d");

            $this->db->from('counter_discard_inventory');
            $this->db->where('trans_items', $items_detail['trans_items']);
            $this->db->where('trans_user', $items_detail['trans_user']);
            $this->db->where('item_type', $items_detail['item_type']);
            $this->db->where('DATE(trans_date)', $date);
            $result = $this->db->get()->row();
            $items_detail['trans_inventory'] += $result->trans_inventory;

            // Update Quantity
            $this->db->where('trans_items', $items_detail['trans_items']);
            $this->db->where('trans_user', $items_detail['trans_user']);
            $this->db->where('item_type', $items_detail['item_type']);
            $this->db->where('DATE(trans_date)', $date);

            return $this->db->update('counter_discard_inventory', $items_detail);
        }
    }

    public function get_order_item_quantity($item_id, $store_id, $attribute_id=-1)
    {     
        $this->db->from('counter_order_item_quantities');
        $this->db->where('item_id', $item_id);
        $this->db->where('store_id', $store_id);
        if($attribute_id>0)
            $this->db->where('attribute_id', $attribute_id);
        $result = $this->db->get()->row();
        if(empty($result) == TRUE)
        {
            //Get empty base parent object, as $item_id is NOT an item
            $result = new stdClass();

            //Get all the fields from items table (TODO to be reviewed)
            foreach($this->db->list_fields('counter_order_item_quantities') as $field)
            {
                $result->$field = '';
            }

            $result->quantity = 0;
        }
        
        return $result;   
    }

    public function save_items($items_detail, $item_id, $person_id, $type=-1)
    {
        if(!$this->exists_item($item_id, $person_id, $type))
        {
            return $this->db->insert('counter_order_item_quantities', $items_detail);
        }

        $this->db->where('item_id', $item_id);
        $this->db->where('store_id', $person_id);
        if($type!=-1)
        $this->db->where('type', $type);

        return $this->db->update('counter_order_item_quantities', $items_detail);
    }

    /*
    Gets company name rows for a particular order
    */
    public function get_company_name($person_id,$table)
    {
       $builder = $this->db->table($table)
                 ->where('person_id', $person_id);
        
        //return an array of order items for an item
        return $builder->get()->getRow();
    }

    /*
    Gets product name rows for a particular category
    */
    public function get_product_name($item_id,$table)
    {
        $this->db->from($table);
        $this->db->where('item_id', $item_id);
        
        //return an array of order items for an item
        return $this->db->get()->row();
    }

    public function exists_item($item_id, $person_id=-1, $type=-1)
    {
        $this->db->from('counter_order_item_quantities');
        $this->db->where('item_id', $item_id);
        $this->db->where('store_id', $person_id);
        
        if($type!=-1)
        $this->db->where('type', $type);

        $date = date("Y-m-d");
        //$date = date("2019-12-10");
        $this->db->where('DATE(created_at)', $date);

        return ($this->db->get()->num_rows() == 1);
    }

    public function getExpoToken($person_id='')
    {
        $this->db->from('expoToken');
        $this->db->where('user_id', $person_id);
        
        //return an array of order items for an item
        return $this->db->get()->row();
    }

    /*
    Get Notificatons
    */
    public function notifications($person_id = -1)
    {
        $this->db->select('id as notification_id, details as notification_title, order_id, created_at, status as is_checked, taskCreated');
        $this->db->from('notifications');
        
        if($person_id > 0)
        {
            $this->db->where('noti_to', $person_id);
        }
        
        $this->db->where('category', 0);
        $this->db->where('deleted', 0);
        

        $this->db->order_by('created_at', 'desc');

        $query = $this->db->get();
        $data = $query->result();
        return $data;    
    }

    /*
    Get Unread Notificatons Count
    */
    public function notificationsCount($person_id = -1)
    {
        $this->db->from('notifications');
        
        if($person_id > 0)
        {
            $this->db->where('noti_to', $person_id);
        }
        
        $this->db->where('category', 0);
        $this->db->where('deleted', 0);
        $this->db->where('status', 0);

        $query = $this->db->get();
        $data = $query->num_rows();
        return $data;    
    }

    /*
    Updates Notification Status
    */
    public function notificationsCheck($id)
    {

        $this->db->where('id', $id);

        return $this->db->update('notifications', ['status'=>1]);
    }

    public function items_types()
    {
        $this->db->select('raw_items.category');

        $this->db->from('raw_items');
        $this->db->where_in('raw_items.item_type', [3,4]);
        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('raw_items.category');
        // order by name of item
        $this->db->order_by('raw_items.name', 'asc');

        return $this->db->get()->result();

    }

    public function items_types_items($category = '')
    {
        $this->db->select('raw_items.item_id, raw_items.name, raw_items.description, raw_items.item_attributes');

        $this->db->from('raw_items');

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('raw_items.item_id');
        // order by name of item
        $this->db->order_by('raw_items.name', 'asc');

        $this->db->where('raw_items.category', $category);

        $data = $this->db->get()->result();

        foreach ($data as $key => $value) {
            $data2 = [];
            if($value->item_attributes==1){
                $this->db->select('raw_item_attributes.attribute_id, raw_item_attributes.attribute_title, raw_item_attributes.attribute_price');
                $this->db->from('raw_item_attributes');
                $this->db->where('raw_item_attributes.item_id', $value->item_id);
                $this->db->where('raw_item_attributes.deleted', 0);
                $this->db->where('raw_item_attributes.attribute_category', 0);
                $data2 = $this->db->get()->result();
            }
            $value->attributes = $data2;
        }

        return $data;
    }

    public function orders_counts($person_id = -1)
    {
        //$date = date("Y-m-d");
        $date = date("2019-12-10");
        $this->db->select('order_status, count(order_id) as total');

        $this->db->from('counter_orders');
        $this->db->where('person_id', $person_id);

        $this->db->where('DATE(created_at)', $date);

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('order_status');
        // order by name of item
        $this->db->order_by('order_status', 'asc');

        $data = $this->db->get()->result();

        $array = [
            'Delivered' => '0',
            'Pending' => '0',
            'Received' => '0',
        ];

        foreach ($data as $key => $value) {
            $array[$value->order_status] = $value->total;
        }
        return $array;
    }

    public function randomColor()
    {
        return '#' . substr(md5(mt_rand()), 0, 6);
    }

    public function orders_Items($person_id = -1)
    {
        //$date = date("Y-m-d");
        $date = date("2019-12-10");
        $this->db->select('item_id');

        $this->db->from('counter_orders');
        $this->db->join('counter_order_items as coi', 'coi.order_id=counter_orders.order_id');

        $this->db->where('person_id', $person_id);

        $this->db->where('DATE(created_at)', $date);

        // avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
        $this->db->group_by('item_id');
        // order by name of item
        $this->db->order_by('item_id', 'asc');

        $data = $this->db->get()->result();

        $array = [];

        foreach ($data as $key => $value) {

            $name = $this->get_item_name($value->item_id,'raw_items')->name;
            $value->name = $name;

            $quantity = $this->get_item_available_quantity($value->item_id,'counter_order_item_quantities')->available_quantity;
            $value->quantity = $quantity;

            $color = $this->randomColor();

            $array[] = [
                'name' => $name,
                'population' => intval($quantity),
                'color' => $color,
                'legendFontColor' => '#7F7F7F',
                'legendFontSize' => 15,
            ];
        }

        return $array;

    }

    public function get_item_name($item_id,$table)
    {
        $this->db->from($table);
        $this->db->where('item_id', $item_id);
        
        //return an array of order items for an item
        return $this->db->get()->row();
    }

    public function get_item_available_quantity($item_id,$table)
    {
        $this->db->from($table);
        $this->db->where('item_id', $item_id);
        
        //return an array of order items for an item
        return $this->db->get()->row();
    }

    public function order_data($order_id = -1, $extras = '', $price = '')
    {
        // $date = date("Y-m-d");
        $date = date("Y-m-d");

        $count = 1;
        if($cData = $this->exists_order_today($date)){
            $count = $cData->count+1;
        }

        $counter_order_data = [
            'order_id' => $order_id,
            'extras' => $extras,
            'price' => $price,
            'count' => $count,
        ];

        return $this->db->insert('pizza_orders', $counter_order_data);

        return $counter_order_data;
    }

    public function order_data_slip($order_id = -1)
    {
        
       $builder = $this->db->table('pizza_orders')
                ->where('order_id', $order_id);

        return $builder->get()->getRow();

    }

    public function exists_order_today($date)
    {
        $this->db->select('count');
        $this->db->from('pizza_orders');
        $this->db->where('DATE(created_at)', $date);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        return $this->db->get()->row();
    }

    public function get_pizza_menu()
    {

        $this->db->select('item_id, name, item_number, pic_id, item_type, category');
        $this->db->from('raw_items');
        
        $this->db->where('raw_items.item_type', 4);
        $this->db->where('raw_items.is_pizza', 1);

        $this->db->order_by('item_id', 'asc');

        $data =  $this->db->get();  

        foreach ($data->result() as $key => $value) {

            $image = base_url("uploads/item_pics/dummy.png");
            if (!empty($value->pic_id)) {
                $images = glob("uploads/item_pics/" . $value->pic_id . ".*");
                if (sizeof($images) > 0) {
                    $image = base_url($images[0]);
                }
            }

            $value->image = $image;
            
            $this->db->select('attribute_id as value, attribute_title as label, attribute_price as price');

            $this->db->from('raw_item_attributes');
            $this->db->where('raw_item_attributes.item_id', $value->item_id);
            $this->db->where('raw_item_attributes.attribute_category', 0);
            // $this->db->where('raw_item_attributes.attribute_price >', 0);
            $this->db->where('raw_item_attributes.deleted', 0);
            $sizes =  $this->db->get();
            $value->sizes = $sizes->result();

            $this->db->select('attribute_id as value, attribute_title as label, attribute_price as price');
            $this->db->from('raw_item_attributes');
            $this->db->where('raw_item_attributes.item_id', $value->item_id);
            $this->db->where('raw_item_attributes.attribute_category', 1);
            // $this->db->where('raw_item_attributes.attribute_price >', 0);
            $this->db->where('raw_item_attributes.deleted', 0);
            $extras =  $this->db->get();
            $value->extras = $extras->result();

            foreach ($value->extras as $ke => $valueE) {
                $valueE->checked = FALSE;
            }

            $this->db->select('attribute_id as value, attribute_title as label');
            $this->db->from('raw_item_attributes');
            $this->db->where('raw_item_attributes.item_id', $value->item_id);
            $this->db->where('raw_item_attributes.attribute_category', 2);
            $this->db->where('raw_item_attributes.deleted', 0);
            $ingredients =  $this->db->get();
            $value->ingredients = $ingredients->result();

            $suggestions = [];
            foreach ($value->ingredients as $kv => $valueI) {
                $valueI->checked = FALSE;
                $suggestions[] = $valueI->label;
            }
            $value->description = implode(', ',$suggestions);
        }

        return $data->result();
    }

    public function get_items($item_id='', $size='')
    {
        $this->db->select('item_id, name, category');
        $this->db->from('raw_items');
        $this->db->where('item_id !=', $item_id);
        $this->db->where('item_type', 4);
        $this->db->where('is_pizza', 1);
        $this->db->where('deleted', 0);
        $item_list =  $this->db->get();
        
        //$items = [];
        foreach ($item_list->result() as $key => $value) {
            $this->db->from('raw_item_attributes');
            $this->db->where('item_id', $value->item_id);
            $this->db->where('attribute_id', $size);
            $this->db->where('attribute_category', 0);
            $attr =  $this->db->get()->row();
            $value->price = parse_decimals($attr->attribute_price);
        }
        return $item_list->result();
    }

    public function get_extras($item_id='')
    {
        $this->db->select('attribute_id as value, attribute_title as label, attribute_price as price');
        $this->db->from('raw_item_attributes');
        $this->db->where('raw_item_attributes.item_id', $item_id);
        $this->db->where('raw_item_attributes.attribute_category', 1);
        $this->db->where('raw_item_attributes.deleted', 0);
        $extras =  $this->db->get();
        
        foreach ($extras->result() as $key => $value) {
            $value->price = parse_decimals($value->price);
            $value->checked = FALSE;
        }
        return $extras->result();
    }

    public function get_ingredients($item_id='')
    {
        $this->db->select('attribute_id as value, attribute_title as label');
        $this->db->from('raw_item_attributes');
        $this->db->where('raw_item_attributes.item_id', $item_id);
        $this->db->where('raw_item_attributes.attribute_category', 2);
        $this->db->where('raw_item_attributes.deleted', 0);
        $extras =  $this->db->get();
        
        foreach ($extras->result() as $key => $value) {
            // $value->price = parse_decimals($value->price);
            $value->checked = FALSE;
        }
        return $extras->result();
    }

    public function order_data_items($order_id='')
    {
        //$get_size = ['', 'Mini', 'Small', 'Medium', 'Large', 'XL'];
        // $this->db->select('poi.*, ri.name, ri2.name as flavor2');
       $builder = $this->db->table('pizza_order_items as poi')
                  ->select('poi.*, items.name')
                  ->join('items','items.item_number=poi.item_number')
        //$this->db->join('raw_items as ri','ri.item_id=poi.item_id');
        //$this->db->join('raw_items as ri2','ri2.item_id=poi.add_item_id', 'left');
                  ->where('poi.order_id', $order_id);
        return $builder->get()->getResult();

    }

}
?>