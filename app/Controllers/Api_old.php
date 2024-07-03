<?php
namespace App\Controllers;

use App\Libraries\BarcodeLib;
use App\Models\apis\Auth;
use App\Models\apis\Login;
use App\Models\apis\User;
use App\Models\Appconfig;
use App\Models\Counter;
use App\Models\Counter_order;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Pizza_order;
use App\Models\Pizza_order_item;
use App\Models\Production_order;
use App\Models\reports\Inventory_consumed_item;
use App\Models\reports\Inventory_deliver_item;
use App\Models\reports\Inventory_discard_item;
use App\Models\reports\Inventory_pizza;
use App\Models\reports\Inventory_received_item;
use Config\Database;
use Pusher\Pusher;
use Restserver\Libraries\REST_Controller;
use App\Models\Store_item_quantity;

class Api extends REST_Controller {

    protected $post;
    protected $token;
    protected $appKey;
    protected $appSecret;
    protected $Login;
    protected $Auth;
    protected $User;
    protected $Employee;
    public function __construct()
    {
        parent::__construct();

        $this->post = $_REQUEST;

        //Get Headers
        $headerData = getallheaders();

        $this->token = !empty($headerData['Authorization']) ? $headerData['Authorization'] : '' ;

        // log_message('info', 'The purpose of some variable is to provide some value.');
        // log_message('info',print_r($this->post(),TRUE));
        // log_message('info', 'The purpose of some variable is to provide some value.');

        $this->appKey = !empty($this->post('client_id')) ? $this->post('client_id') : '' ;
        $this->appSecret = !empty($this->post('client_secret')) ? $this->post('client_secret') : '' ;

        $this->Login = new Login();
        $this->Auth = new Auth();
        $this->User = new User();
        $this->Employee = new Employee();
    }

    /** User Logout Api
     * @method : GET
     * @params : []
     */
    public function logout_user_get()
    {
        $token = substr($this->token, 7);
        $data = $this->Login->logout_user($token);

        if($data){
            $response = [
                'status' => 'Success',
                'statusCode' => 1,
                'statusMessage' => 'User Logged Out Successfully.',
                'data' => $data,
            ];
        }else{
            $response = [
                'status' => 'Error',
                'statusCode' => 2,
                'statusMessage' => 'Some Error Has Been Occured.',
                'data' => $data,
            ];
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /** Login User Api
     * @method : Post
     * @params : [client_id, client_secret, username, password]
     */
    public function login_user_post()
    {
        if($this->emptykeyCheck($this->appSecret,$this->appKey))
        {
            $status = $this->keyMatch($this->appSecret,$this->appKey); 
            if($status)
            {  
                $username = $this->post('username');
                $password = $this->post('password');
                $token = $this->post('token');

                $data = $this->Login->login_user($username, $password);

                if($data){
                	if($data['statusCode']==1){
                    	$this->Login->NewDevice($data['user']->id, $token, $data['user']->token_id);
                	}
                    $message = [
                        'status' => $data['status'],
                        'statusCode' => $data['statusCode'],
                        'statusMessage' => $data['message'],
                        'count' => $data['count'],
                        'payLoad' => $data['user']
                    ];
                }else{
                    $message = [
                        'status' => 'Error',
                        'statusCode' => 2,
                        'statusMessage' => 'User Authentication Failed.',
                        'httpcode' => REST_Controller::HTTP_NOT_FOUND,
                    ];
                }
                $this->set_response($message, REST_Controller::HTTP_OK);
            }
            else
            {
                $errorResponse = [
                    'status' => 'Error',
                    'statusCode' => 3,
                    'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                    'statusMessage' => 'Invalid Authentication Keys.',
                ];
                $this->set_response($errorResponse, REST_Controller::HTTP_OK);
            }
        }
        else
        {
            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_NOT_FOUND,
                'statusMessage' => 'Invalid Authentication Keys.',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    private function emptykeyCheck($appSecret = null, $appKey = null)
    {
        if (empty($appSecret) || empty($appKey)) 
        {
            return false;
        }else{
            return true;
        }

        // try{
        //     if (empty($appSecret) || empty($appKey)) 
        //     {
        //         return false;
        //     }else{
        //         return true;
        //     }
        // }catch(EXCEPTION $ex){
        //     echo $ex->getMessage();
        // }
    }

    private function keyMatch($appSecret = null, $appKey = null)
    {
        $config = new Appconfig();
        //set/get appkey and appSecret in config.php
        if($appSecret == $config->get('appSecret') && $appKey == $config->get('appKey')){
            return true;
        }else{
            return false;
        }
            
        // try{
        //     if($appSecret == $this->config->item('appSecret') && $appKey == $this->config->item('appKey')){
        //         return true;
        //     }else{
        //         return false;
        //     }
        // }catch(EXCEPTION $ex){
        //     echo $ex->getMessage();
        // }
    }


    /** Test Api
     * @method : Get
     * @params : []
     */
    public function user_testing_get()
    {
        $status = 1;
        //$status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $content = file_get_contents('php://input');

            $data = json_decode($content, true);

            $response = $this->placePizzaOrder($data);

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Store Products Api
     * @method : Get
     * @params : []
     */
    public function user_store_products_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {   
            $data = $this->User->store_products_list($id);

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Store\'s Product List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Product Found in Store',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Counter Products Api
     * @method : Get
     * @params : []
     */
    public function user_counter_products_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {   
            $data = $this->User->counter_products_list($id);

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Counter\'s Product List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Product Found in Counter',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Production Counter Allowed Items Api
     * @method : Get
     * @params : []
     */
    public function user_production_counter_items_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {   
            $data = $this->User->production_products_list($id);

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Counter\'s Items List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Items Found in Counter',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Production Products Api
     * @method : Get
     * @params : []
     */
    public function user_production_products_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {   
            $data = $this->User->production_products_list($id);

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Production\'s Product List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Product Found at Production',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Production Products Attributes Api
     * @method : Get
     * @params : []
     */
    public function user_production_products_attributes_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {   
            $data = $this->User->production_products_attributes_list($id);

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Production\'s Product Attributes List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Product Attributes Found at Production',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Store Counters Api
     * @method : Get
     * @params : []
     */
    public function user_counters_get($store_id,$person_id)
    {

        // $person_id = $this->post['person_id'];
        // $store_id = $this->post['store_id'];
       
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $data = $this->User->counters_list($store_id, $person_id);
            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Counters List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Counters Found in Store',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** GET Store Production Api
     * @method : Get
     * @params : []
     */
    public function user_production_get($store_id,$person_id, $type)
    {

        // $person_id = $this->post['person_id'];
        // $store_id = $this->post['store_id'];
       
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $data = $this->User->production_list($store_id, $person_id, $type);
            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Production List',
                    'count' => count($data),
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Production Found in Store',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }
    /** Place Order to Store Api
     * @method : POST
     * @params : []
     */
    public function user_order_products_post()
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $data = $this->placeOrder();

            if($data==1){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Processed Successfully.',
                ];
            }elseif($data==-1){
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Quantity For All Items Is Required.',
                    'payLoad' => '',
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Processing Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    private function placeOrder()
    {
        if(!empty($this->post['id']) && !empty($this->post['quantity']) && !empty($this->post['type']) && 
           !empty($this->post['category']) && !empty($this->post['person_id']) && 
           !empty($this->post['store_id'])){

                $item_ids = $this->post['id'];
                $item_quantities = $this->post['quantity'];
                $item_types = $this->post['type'];
                $attribute = $this->post['attribute'];
                //$price = $this->post['price'];
                $order_quantity = array_sum($item_quantities);

            if(count($item_ids)==count($item_quantities)){

                $microtime = round(microtime(true) * 1000);

                $record = [
                    'category' => $this->post['category'],
                    'person_id' => $this->post['person_id'],
                    'is_deliverable' => $this->post['is_deliverable'],
                    'store_id' => $this->post['store_id'],
                    'description' => $this->post['description'],
                    'order_quantity' => $order_quantity,
                    'microtime' => $microtime,
                    'order_status' => 'Pending',
                ];

                $sum = 0;
                $items = [];
                for ($i=0; $i < count($item_ids); $i++) { 
                    $items[] = [
                        'item_id' => $item_ids[$i],
                        'quantity' => $item_quantities[$i],
                        'type' => $item_types[$i],
                        'attribute' => $attribute[$i],
                    ];

                    //$sum += $price[$i];
                }

                $last_id = $this->User->order_items($record, $items);

                // order slip print + extras price process
                // if(!empty($this->post['extra_price']) && !empty($this->post['extra_description'])){

                //     $order_id = $last_id;
                //     $extras = $this->post['extra_description'];
                //     $price = $sum + $this->post['extra_price'];

                //     $this->User->order_data($order_id, $extras, $price);
                // }


                $person_id = $this->post['person_id'];
                $store_id = $this->post['store_id'];
                $company_name = $this->Employee->get_company_name($person_id,'counters')->company_name;
                $message = 'New Order From '.$company_name;

                if ($this->post['is_deliverable']==1) {
                    // Expo Notification / order to other counter
                    $category = "RecievedOrderDetailScreen";
                    $this->saveAndSendExpoNotification($message, $person_id, $store_id, $last_id, $category);

                    $this->sendProductionPusherOrder($store_id, 'Pending',$last_id);
                }else{
                    // Pusher Notification / order to store
                    $this->saveAndSendPusherNotification($last_id, $person_id, $store_id, $message, 0);
                }

                return 1;
            }else{
                return -1;
            }
        }else{
            return 0;
        }
    }

    /** Products Orders List Api
     * @method : Get
     * @params : []
     */
    public function user_order_products_get($person_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            // -1 is for order_id and store_id
            $counter_orders = $this->User->search(-1, $person_id, -1);

            if($counter_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders List',
                    'count' => count($counter_orders),
                    'payLoad' => $counter_orders
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Order List Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Single Product Order Api
     * @method : Get
     * @params : []
     */
    public function user_order_single_product_get($order_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            // -1 is for person_id and store_id
            $counter_orders = $this->User->search(-1, -1,$order_id);

            if($counter_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders List',
                    'count' => count($counter_orders),
                    'payLoad' => (object)$counter_orders[0]
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Order Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Update Product Order Api
     * @method : Post
     * @params : []
     */
    public function user_update_order_product_post()
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $this->updatingOrder();
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }


    private function updatingOrder()
    {
        $Counter_order = new Counter_order();
        $Store_item_quantity = new Store_item_quantity();
        $order_id = $this->post['order_id'];
        $item_ids = $this->post['id'];
        $item_quantities = $this->post['quantity'];
        $item_types = $this->post['type'];
        $attribute = $this->post['attribute'];
        $receiving_quantity = array_sum($item_quantities);

        $raw_order_data = [
            'receiving_description' => request()->getPost('description'),
            'receiving_quantity' => $receiving_quantity,
            'order_status' => 'Received',
            'is_received' => '1',
        ];

        if($this->User->checkOrderDelivery($order_id)){
            if(!$this->User->checkOrderReceiving($order_id)){
                if($this->User->save_user($raw_order_data, $order_id)){

                    $counter_order_items = [];
                    if($item_quantities != NULL)
                    {
                        for ($i=0; $i < count($item_ids); $i++) { 
                            $counter_order_items[$i]['received_quantity'] = $item_quantities[$i];
                            $counter_order_items[$i]['item_id'] = $item_ids[$i];
                            $counter_order_items[$i]['type'] = $item_types[$i];
                            $counter_order_items[$i]['attribute'] = $attribute[$i];
                        }
                    }

                    $store_id = $Counter_order->get_info($order_id)->store_id;
                    $person_id = $Counter_order->get_info($order_id)->person_id;
                    $is_deliverable = $Counter_order->get_info($order_id)->is_deliverable;
                    $company_name = $this->Employee->get_company_name($person_id,'counters')->company_name;
                    $message = 'Order Received By '.$company_name;

                    foreach ($counter_order_items as $order_items) {

                        $item_id = $order_items['item_id'];
                        $updated_quantity = $order_items['received_quantity'];
                        $attribute1 = $order_items['attribute'];
                        $type = $order_items['type'];

                        if ($is_deliverable==0) {
                            $item_quantity = $Store_item_quantity->get_item_quantity($item_id, $store_id);

                            $location_detail = array('item_id' => $item_id,
                                'store_id' => $store_id,
                                'available_quantity' => $item_quantity->available_quantity - $updated_quantity,
                            );

                            $Store_item_quantity->save_store_item_quantity($location_detail, $item_id, $store_id);

                        }else{
                            //for pos-5 and categories-5 items no need to subtract quantity
                            if($type!=4 && $type!=5){
                                $old_quantity = $this->User->get_order_item_quantity($item_id, $store_id, $attribute1);

                                $items_detail = array('item_id' => $item_id,
                                    'store_id' => $store_id,
                                    'available_quantity' => $old_quantity->available_quantity - $updated_quantity,
                                    'attribute_id' => $attribute1
                                );

                                $this->User->save_items($items_detail, $item_id, $store_id); 
                            }
                        }

                        $old_quantity = $this->User->get_order_item_quantity($item_id, $person_id, $attribute1);

                        $items_detail = array('item_id' => $item_id,
                            'store_id' => $person_id,
                            'available_quantity' => $old_quantity->available_quantity + $updated_quantity,
                            'total_quantity' => $old_quantity->total_quantity + $updated_quantity,
                            'attribute_id' => $attribute1,
                            'type' => $type,
                        );

                        $this->User->save_items($items_detail, $item_id, $person_id, $type);
                    }

                    $this->User->update_user($counter_order_items, $order_id, 'received_quantity');

                    if ($is_deliverable==0) {
                        // Pusher Notification / order to store
                        $this->saveAndSendPusherNotification($order_id, $person_id, $store_id, $message, 0);
                    }else{
                        // Expo Notification
                        $category = "RecievedOrderDetailScreen";
                        $this->saveAndSendExpoNotification($message, $person_id, $store_id, $order_id, $category);
                    }

                    $response = [
                        'status' => 'Success',
                        'statusCode' => 1,
                        'statusMessage' => 'Orders Updated Successfully.',
                    ];
                }else{
                    $response = [
                        'status' => 'Error',
                        'statusCode' => 2,
                        'statusMessage' => 'Error adding/updating Order.',
                    ];
                }
            }else{
                $response = [
                        'status' => 'Success',
                        'statusCode' => 1,
                        'statusMessage' => 'Order Already Received.',
                    ];
            }
        }else{
            $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Not Yet Delivered.',
                ];
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /** Counter Items List Api
     * @method : POST
     * @params : []
     */
    public function user_counter_items_get($person_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $counter_orders = $this->User->counter_items($person_id);

            if($counter_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Items List',
                    'count' => count($counter_orders),
                    'payLoad' => $counter_orders
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Item List Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Discard Types Api
     * @method : Get
     * @params : []
     */
    public function user_discard_types_get()
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $data = [
                ['id' => 1, 'name' => 'Discard'],
                ['id' => 2, 'name' => 'Waste'],
                ['id' => 3, 'name' => 'Expired'],
                ['id' => 4, 'name' => 'Damage'],
                ['id' => 5, 'name' => 'Left Over'],
            ];

            if($data){

                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Item(s) Discard Types.',
                    'count' => count($data),
                    'payLoad' => $data,
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'No Discard Type Found.',
                    'payLoad' => '',
                ];
            }
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Discart Counter Items Api
     * @method : Get
     * @params : []
     */
    public function user_discard_counter_items_post()
    {
        // log_message('info', 'The purpose of some variable is to provide some value.');

        // log_message('info',print_r($array_or_object,TRUE));

        // log_message('info', 'The purpose of some variable is to provide some value.');

        $success = TRUE;
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $item_ids = explode(',',$this->post('item_id'));
            $item_types = explode(',',$this->post('item_type'));
            $item_quantities = explode(',',$this->post('quantity'));

            foreach ($item_ids as $key => $value) {
               
                $item_detail = [
                    'trans_user' => $this->post('person_id'),
                    'trans_items' => $item_ids[$key],
                    'item_type' => $item_types[$key],
                    'trans_inventory' => $item_quantities[$key],
                    'discard_type' => $this->post('discard_type'),
                    'trans_comment' => ($this->post('comments')) ? $this->post('comments') : ''
                ];

                $old_quantity = $this->User->get_order_item_quantity($item_detail['trans_items'], $item_detail['trans_user']);

                $old_items_detail = array('item_id' => $item_detail['trans_items'],
                    'store_id' => $item_detail['trans_user'],
                    'available_quantity' => $old_quantity->available_quantity - $item_detail['trans_inventory'],
                );

                $success &= $this->User->save_items($old_items_detail, $item_detail['trans_items'], $item_detail['trans_user']);

                $success &= $this->User->discard_counter_item($item_detail);
            }
         
            if($success){

                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Item(s) Discarded Successfully.',
                    'payLoad' => '',
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Error adding/updating Item(s).',
                    'payLoad' => '',
                ];
            }
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Received Orders List Api
     * @method : Get
     * @params : []
     */
    public function user_received_order_get($person_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            // -1 is for order_id and person id
            // 1 for deliverable orders
            $counter_orders = $this->User->search($person_id, -1, -1, 1);

            if($counter_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders List',
                    'count' => count($counter_orders),
                    'payLoad' => $counter_orders
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Order List Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /** Update Deliver Product Order Api
     * @method : Post
     * @params : []
     */
    public function user_update_deliver_order_product_post()
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $this->updatingReceivedOrder();
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    private function updatingReceivedOrder()
    {
        $Counter_order = new Counter_order();
        $order_id = $this->post['order_id'];
        $item_ids = $this->post['id'];
        $item_quantities = $this->post['quantity'];
        $item_types = $this->post['type'];
        $delivered_quantity = array_sum($item_quantities);

        $raw_order_data = [
            'delivered_description' => request()->getPost('description'),
            'delivered_quantity' => $delivered_quantity,
            'order_status' => 'Delivered',
            'is_delivered' => '1',
        ];

        if(!$this->User->checkOrderReceiving($order_id)){
            if(!$this->User->checkOrderDelivery($order_id)){
                if($this->User->save_user($raw_order_data, $order_id)){

                    $counter_order_items = [];
                    if($item_quantities != NULL)
                    {
                        for ($i=0; $i < count($item_ids); $i++) { 
                            $counter_order_items[$i]['delivered_quantity'] = $item_quantities[$i];
                            $counter_order_items[$i]['item_id'] = $item_ids[$i];
                            $counter_order_items[$i]['type'] = $item_types[$i];
                        }
                    }

                    $this->User->update_user($counter_order_items, $order_id, 'delivered_quantity');
                    
                    $store_id = $Counter_order->get_info($order_id)->store_id;
                    $person_id = $Counter_order->get_info($order_id)->person_id;

                    $company_name = $this->Employee->get_company_name($person_id,'counters')->company_name;
                    $message = 'Order Delivered By '.$company_name;

                    // $category = "RecievedOrderDetailScreen";
                    $category = "OrderListDetailScreen";
                    $this->saveAndSendExpoNotification($message, $store_id, $person_id, $order_id, $category);

                    $response = [
                        'status' => 'Success',
                        'statusCode' => 1,
                        'statusMessage' => 'Orders Updated Successfully.',
                    ];
                }else{
                    $response = [
                        'status' => 'Error',
                        'statusCode' => 2,
                        'statusMessage' => 'Error adding/updating Order.',
                    ];
                }
            }else{
                $response = [
                        'status' => 'Success',
                        'statusCode' => 1,
                        'statusMessage' => 'Order Already Delivered.',
                    ];
            }
        }else{
            $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Already Received.',
                ];
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /** Device/Expo Register Api
     * @method : Post
     * @params : [person_id,Token]
     */
    public function user_create_new_token_post()
    {
        //$status = $this->Auth->check_auth_user($this->token);
        $status = true;
        if($status)
        {
            $person_id = request()->getPost('person_id');
            $token = request()->getPost('token');
            $token_id = request()->getPost('token_id');

            if($this->Login->NewDevice($person_id, $token, $token_id)){

                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Device/Token Saved Successfully.',
                    'payLoad' => '',
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Error adding/updating Device/Token.',
                    'payLoad' => '',
                ];
            }
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function saveAndSendExpoNotification($message, $noti_from, $noti_to, $order_id, $category)
    {   
        $expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            if ($tkn->token != '' && $tkn->token != false && $tkn->token != 'false') {
                $expoToken = $tkn->token;
            }
            
        }
        
        // Save Notification
        $nData = [
            'order_id'=> $order_id,
            'noti_from'=> $noti_from,
            'noti_to'=> $noti_to,
            'details'=> $message,
            'taskCreated' => $category,
            'category'=> 0
        ];
        $Notification = new Notification();
        $nId = $Notification->saveNotification($nData);
        // Save Notification

        // Expo Push Notification
            
            $data = [
                'order_id' => $order_id,
                'notification_id' => $nId,
                'taskCreated' => $category
            ];
            $data = json_encode($data);
            if($expoToken){
                // if token found send notification
                $this->sendExpoNotification($expoToken, $message, $data);
            }else{
                // else return true without sending notification
                return 1;
            }
            
        // Expo Push Notification
    }

    public function getExpoToken($person_id='')
    {
        $db = Database::connect();
        $builder = $db->table('expoToken')
                            ->where('user_id', $person_id);
        
        //return an array of order items for an item
        return $builder->get()->getRow();
    }

    public function sendExpoNotification($expoToken, $message, $data)
    {
        require APPPATH . 'vendor/autoload.php';
  
        $interestDetails = ['hqPqPe5i7iZaAYdeQFpL'.$expoToken, $expoToken];
          
        // You can quickly bootup an expo instance
        $expo = \ExponentPhpSDK\Expo::normalSetup();
          
        // Subscribe the recipient to the server
        $expo->subscribe($interestDetails[0], $interestDetails[1]);
          
        // Build the notification data
        $notification = ['body' => $message, 'data'=> $data];

        //$notification = ['body' => 'Hello World!', 'data'=> json_encode(array('someData' => 'goes new here'))];

        // Notify an interest with a notification
        $expo->notify($interestDetails[0], $notification); 
    }

    public function sendProductionPusherOrder($person_id, $status, $order_id)
    {
        require APPPATH . 'vendor/autoload.php';
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );
        $pusher = new Pusher(
            'd74cdee3f051d856e9f7',
            'bd5ee912b11458842929',
            '884876',
            $options
        );
        $Production_order = new Production_order();
        $search = $Production_order->search($person_id, $status, $order_id);
        $data = $search->getRow();

        return $pusher->trigger('pusher-channel', 'production-order-event', $data);
    }

    public function saveAndSendPusherNotification($order_id, $noti_from, $noti_to, $message, $category)
    {
        require APPPATH . 'vendor/autoload.php';
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );
        $pusher = new Pusher(
            'd74cdee3f051d856e9f7',
            'bd5ee912b11458842929',
            '884876',
            $options
        );

        // Save Notification
            $nData = [
                'order_id'=> $order_id,
                'noti_from'=> $noti_from,
                'noti_to'=> $noti_to,
                'details'=> $message,
                'category'=> $category,
            ];
            $Notification = new Notification();
            $nId = $Notification->saveNotification($nData);

        // Get Notification
            $notification = $Notification->getSingleNotification($nId);

        $data['id'] = $notification->id;
        $data['user'] = $notification->noti_to;
        $data['order'] = $notification->order_id;
        $data['message'] = $notification->details;
        $data['date'] = $notification->created_at;

        if ($notification->category==0) {
            $data['url'] = base_url().'counter_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
        }elseif ($notification->category==2 || $notification->category==4) {
            $data['url'] = base_url().'raw_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
        }elseif ($notification->category==3) {
            $data['url'] = base_url().'store_orders/updatenotification/'.$notification->order_id.'/'.$notification->id;
        }

        $pusher->trigger('pusher-channel', 'notificaton-event', $data);
    }

    /** Get Notifications List
     * @method : Get
     * @params : []
     */
    public function user_notifications_get($person_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $notifications = $this->User->notifications($person_id);

            if($notifications){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Notifications',
                    'count' => count($notifications),
                    'payLoad' => $notifications,
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Notification Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /**Get Unread Notificatons Count
     * @method : Get
     * @params : []
     */
    public function user_notifications_count_get($person_id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $notifications = $this->User->notificationsCount($person_id);

            if($notifications){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Unread Notifications',
                    'count' => $notifications,
                    'payLoad' => '',
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Unread Notification Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    /**Check Unread Notificatons
     * @method : Get
     * @params : []
     */
    public function user_notifications_check_get($id)
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $notifications = $this->User->notificationsCheck($id);

            if($notifications){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Notification Checked Successfully.',
                    'payLoad' => '',
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Error Updating Notification Status.',
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }



    /** Items Type List Api
     * @method : GET
     * @params : []
     */
    public function user_items_type_get()
    {
        $item_types = $this->User->items_types();

        if($item_types){
            $response = [
                'status' => 'Success',
                'statusCode' => 1,
                'statusMessage' => 'Items Type List',
                'count' => count($item_types),
                'payLoad' => $item_types
            ];
        }else{
            $response = [
                'status' => 'Success',
                'statusCode' => 1,
                'statusMessage' => 'No Item Type List Found.',
                'count' => 0,
                'payLoad' => '',
            ];
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /** Items Type Items List Api
     * @method : GET
     * @params : []
     */
    public function user_items_type_items_get($category)
    {
        $item_types = $this->User->items_types_items($category);

        if($item_types){
            $response = [
                'status' => 'Success',
                'statusCode' => 1,
                'statusMessage' => 'Items List',
                'count' => count($item_types),
                'payLoad' => $item_types
            ];
        }else{
            $response = [
                'status' => 'Success',
                'statusCode' => 1,
                'statusMessage' => 'No Item List Found.',
                'count' => 0,
                'payLoad' => '',
            ];
        }

        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    /** Dashboard Orders counts Api
     * @method : GET
     * @params : []
     */
    public function user_orders_counts_get($person_id)
    {   
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $orders_counts = $this->User->orders_counts($person_id);

            if($orders_counts){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders Counts Detail',
                    'count' => count($orders_counts),
                    'payLoad' => $orders_counts
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Orders Detail Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function user_orders_items_get($person_id)
    {   
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $orders_Items = $this->User->orders_Items($person_id);

            if($orders_Items){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders Items List',
                    'count' => count($orders_Items),
                    'payLoad' => $orders_Items
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Orders Items Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function generatePDF_get($order_id, $store_id, $counter_id)
    {   
        $order_data  = $this->User->order_data_slip($order_id);

        $items = $this->User->order_data_items($order_id);

        $counter_name = $this->User->get_company_name($counter_id,'counters')->company_name; 
        $store_name = $this->User->get_company_name($store_id,'stores')->company_name; 
        //print_r($data);exit();
        $data = [
            'branch' => $store_name,
            'counter' => $counter_name,
            'reordered' => $order_data->reordered,
            'order_id' => $order_data->order_id,
            'count' => $order_data->order_number,
            'price' => $order_data->order_price,
            'date' => date("D, M d, Y", strtotime($order_data->created_at)),
            'time' => date("h:i A", strtotime($order_data->created_at)),
            'deliver_date' => date("D, M d, Y", strtotime($order_data->deliver_at)),
            'deliver_time' => date("h:i A", strtotime($order_data->deliver_at)),
            'item' => [
                'item_id' => $order_data->order_number,
                'name' => $order_data->order_number,
                'item_number' => '',
                'unit_price' => $order_data->order_price,
            ],
            'items' => $items
        ];
        $barcode_lib = new BarcodeLib();
        $config = $barcode_lib->get_barcode_config();
        $config['barcode_type'] = 'Code128';
        
        $data['barcode_config'] = $config;
        // echo "<pre>";
        // print_r($config);
        // echo "</pre>";
        echo view('slip',$data);
    }

    public function user_generate_slip_get($order_id, $store_id, $counter_id)
    {  
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $order_data  = $this->User->order_data_slip($order_id);
            if($order_data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Slip Download',
                    'count' => count($order_data),
                    'payLoad' => base_url().'api/v1/user/generatePdf/'.$order_id.'/'.$store_id.'/'.$counter_id
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Order Slip Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function generateReportPDF_get($store_id, $counter_id)
    {   

        $counter_name = $this->User->get_company_name($counter_id,'counters')->company_name; 
        $store_name = $this->User->get_company_name($store_id,'stores')->company_name; 

        $counter_items = $this->User->counter_items_for_report($counter_id);

        $data = [
            'branch' => $store_name,
            'counter' => $counter_name,
            'counter_items' => $counter_items,
            'date' => date("D, M d, Y"),
            'time' => date("h:i A"),
        ];

        $data['waste_types'] = [
            1 => 'Discard',
            2 => 'Waste',
            3 => 'Expired',
            4 => 'Damage',
            5 => 'Left Over',
        ];

        return view('report',$data);
    }

    public function user_generate_report_get($store_id, $counter_id)
    {  

        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $counter_orders = $this->User->counter_items($counter_id);

            if($counter_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Report Download',
                    'count' => count($counter_orders),
                    'payLoad' => base_url().'api/v1/user/generateReportPdf/'.$store_id.'/'.$counter_id
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Report Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_products_get()
    {  

        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $pizza_items = $this->User->get_pizza_menu();

            if($pizza_items){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Pizza Items List',
                    'count' => count($pizza_items),
                    'payLoad' => $pizza_items
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Item Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function getHTMLofView($order_id='', $store_id='', $counter_id='')
    {

        $order_data  = $this->User->order_data_slip($order_id);
        $items = $this->User->order_data_items($order_id);

        $counter_name = $this->User->get_company_name($counter_id,'counters')->company_name; 
        $store_name = $this->User->get_company_name($store_id,'stores')->company_name; 
        //print_r($data);exit();
        $data = [
            'branch' => $store_name,
            'counter' => $counter_name,
            'order_id' => $order_data->order_id,
            'reordered' => $order_data->reordered,
            'count' => $order_data->order_number,
            'price' => $order_data->order_price,
            'date' => date("D, M d, Y", strtotime($order_data->created_at)),
            'time' => date("h:i A", strtotime($order_data->created_at)),
            'deliver_date' => date("D, M d, Y", strtotime($order_data->deliver_at)),
            'deliver_time' => date("h:i A", strtotime($order_data->deliver_at)),
            'item' => [
                'item_id' => $order_data->order_number,
                'name' => $order_data->order_number,
                'item_number' => '',
                'unit_price' => $order_data->order_price,
            ],
            'items' => $items
        ];
        $barcode_lib = new BarcodeLib();
        $config = $barcode_lib->get_barcode_config();
        $config['barcode_type'] = 'Code128';
        
        $data['barcode_config'] = $config;

        return view('slip', $data);
    }

    /** Place Pizza Order to production Api
     * @method : POST
     * @params : []
     */
    public function user_pizza_order_products_post()
    {

        $Pizza_order_item = new Pizza_order_item();
        $Pizza_order = new Pizza_order();
        $Counter = new Counter();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $content = file_get_contents('php://input');

            $data = json_decode($content, true);
            
            $data = $this->placePizzaOrder($data);
       
            if($data['response']==1){

                $noti_send_to = $Pizza_order_item->get_counter_id($data['store_id'], 'counters', 'pizza_orders_list');
             
                $item_data = $Pizza_order->get_found_item($data['order_id']);
                $item_id = $item_data->item_id;
                $name = $item_data->name;

                if($noti_send_to>0){
                // send expo notification
                    $this->SendingExpoNotification('New Order Request', $noti_send_to, $data['order_id'], $item_id, $name);
                
                }

                $counter_ids = $Counter->get_all_pizza_counters_of_store($data['store_id']);
                foreach ($counter_ids as $counter_id) {
                    $this->SendingSelfExpoNotification('AllTabsScreen','Order Status Updated', $counter_id, $data['order_id']);
                }
                //$this->SendingSelfExpoNotification('AllTabsScreen', 'Order Status Updated', $data['counter_id'], $data['order_id']);
                //$this->sendPusherOrder($data['store_id'], 'Pending',$data['order_id']);

                $get_html = $this->getHTMLofView($data['order_id'],$data['store_id'],$data['counter_id']);
                
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Processed Successfully.',
                    'payLoad' => $get_html
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Processing Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function user_pizza_reorder_products_post()
    {
        $Pizza_order = new Pizza_order();
        $Pizza_order_item = new Pizza_order_item();
        $Counter = new Counter();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {

            $content = file_get_contents('php://input');

            $data = json_decode($content, true);

            $date = date('Y-m-d');

            $count = 1;
            $order_number = 'PN0001';
            if($cData = $Pizza_order->exists_order_today($date)){
                $count = $cData->count+1;
                $order_number = 'PN'.str_pad($count, 4, 0, STR_PAD_LEFT);
            }

            $item_data = $Pizza_order_item->get_item_info($data['id']);
            $order_data  = $Pizza_order->get_info($item_data->order_id);
            
            $microtime = round(microtime($date) * 1000);

            unset($order_data->order_id);
            $order_data->order_number = $order_number;
            $order_data->count = $count;
            $order_data->order_status = 'Completed';
            $order_data->person_id = $data['counter_id'];
            $order_data->order_quantity = $data['quantity'];
            $order_data->order_price = $item_data->price*$data['quantity'];
            $order_data->deliver_at = date('Y-m-d H:i:s');
            $order_data->created_at = date('Y-m-d H:i:s');
            $order_data->updated_at = date('Y-m-d H:i:s');
            $order_data->microtime = $microtime;
            $order_data->customer_name = '';
            $order_data->customer_phone = '';
            $order_data->reordered = 1;

            $order_data = (array)$order_data;
            if($Pizza_order->save_pizza_order($order_data)){

                if($item_data->quantity==$data['quantity']){
                    // remove old item
                    $itemRows = $Pizza_order_item->get_total_rows($item_data->order_id);

                    if($itemRows>1){
                        $Pizza_order_item->deleteItem($data['id']);
                    }else{
                        $Pizza_order->delete($item_data->order_id);
                        $Pizza_order_item->delete($item_data->order_id);
                    }

                }else{
                    // minus qty
                    $quantity = $item_data->quantity-$data['quantity'];
                    $price = $item_data->price*$data['quantity'];

                    
                    $Pizza_order_item->updateItem($data['id'],$quantity, $price);
                }
                $orderRow = $Pizza_order_item->get_total_sum($item_data->order_id);
                $order_quantity = $orderRow->order_quantity;
                $order_price = $orderRow->order_price;
                $Pizza_order->updateOrder($item_data->order_id,$order_quantity, $order_price);

                unset($item_data->id);
                $item_data->sub_total = $order_data['order_price'];
                $item_data->order_id = $order_data['order_id'];
                $item_data->created_at = date('Y-m-d H:i:s');
                $item_data->updated_at = date('Y-m-d H:i:s');
                $item_data->quantity = $data['quantity'];

                $item_data = (array)$item_data;
                $Pizza_order_item->saveSingle($item_data);
                
                $get_html = $this->getHTMLofView($order_data['order_id'],$order_data['store_id'],$data['counter_id']);

                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Processed Successfully.',
                    'payLoad' => $get_html
                ];

                $counter_ids = $Counter->get_all_pizza_counters_of_store($order_data['store_id']);
                foreach ($counter_ids as $counter_id) {
                    $this->SendingSelfExpoNotification('AllTabsAndRejectedItemScreen','Order Status Updated', $counter_id, $order_data['order_id']);
                }

            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Processing Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }
    
    private function placePizzaOrder($data)
    {
        $order_id = -1;
        $pizza_order_items = [];
        $total_qty = 0;
        $Pizza_order = new Pizza_order();
        $Pizza_order_item = new Pizza_order_item();
        $date = isset($data['deliver_at']) ? $data['deliver_at'] : date('Y-m-d');
        $date = date("Y-m-d", strtotime($date));
        $microtime = round(microtime($date) * 1000);

        $count = 1;
        $order_number = 'PN0001';
        if($cData = $Pizza_order->exists_order_today($date)){
            $count = $cData->count+1;
            $order_number = 'PN'.str_pad($count, 4, 0, STR_PAD_LEFT);
        }

        $person_id = $data['person_id'];

        $store_id = $data['store_id'];

        // get id of counter has permission for pizza orders list eg filling counter 
        //$noti_send_to = $this->Pizza_order_item->get_counter_id($store_id, 'counters', 'pizza_orders_list');
        $deliver_at = isset($data['deliver_at']) ? $data['deliver_at'] : date("Y-m-d H:i:s", strtotime('+15 minutes'));
        $deliver_at = date("Y-m-d H:i:s", strtotime($deliver_at));
        $pizza_order_data = array(
            'person_id' => $person_id,
            'store_id' => $store_id,
            'order_description' => $data['order_description'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'order_quantity' => $data['order_quantity'],
            'order_price' => $data['order_price'],
            'order_status' => 'Pending',
            'count' => $count,
            'order_number' => $order_number,
            'deliver_at' => $deliver_at,
            'microtime' => $microtime,
        );
        if($Pizza_order->save_pizza_order($pizza_order_data))
        {
            $success = TRUE;
            if ($order_id == -1)
            {
                $order_id = $pizza_order_data['order_id'];
            }

            foreach ($data['cart_items'] as $items) {
                $items['type'] = 4;
                $items['item_category2'] = '';
                $add_item_id = 0;
                if($items['add_item_id']){
                    $add_item_id = $items['add_item_id']['item_id'];
                    $items['item_category2'] = $items['add_item_id']['category'];
                }
                $item_data = $Pizza_order_item->get_item_number($items['size'],$items['item_category1'],$items['item_category2']);

                $extras1_values = $extras1_title = [];
                if($items['extras1']){
                    foreach ($items['extras1'] as $ex1) {
                        if($ex1['checked']==true){
                            array_push($extras1_values, $ex1['value']);
                            array_push($extras1_title, $ex1['label']);
                        }
                       
                    }
                }
                $extras1_values = implode(', ', $extras1_values);
                $extras1_title = implode(', ', $extras1_title);

                $ingredients1_values = $ingredients1_title = [];
                if($items['ingredients1']){
                    foreach ($items['ingredients1'] as $ing1) {
                        if($ing1['checked']==true){
                            array_push($ingredients1_values, $ing1['value']);
                            array_push($ingredients1_title, $ing1['label']);
                        }
                       
                    }
                }
                $ingredients1_values = implode(', ', $ingredients1_values);
                $ingredients1_title = implode(', ', $ingredients1_title);

                $extras2_values = $extras2_title = [];
                if($items['extras2']){
                    foreach ($items['extras2'] as $ex2) {
                        if($ex2['checked']==true){
                            array_push($extras2_values, $ex2['value']);
                            array_push($extras2_title, $ex2['label']);
                        }
                       
                    }
                }
                $extras2_values = implode(', ', $extras2_values);
                $extras2_title = implode(', ', $extras2_title);

                $ingredients2_values = $ingredients2_title = [];
                if($items['ingredients2']){
                    foreach ($items['ingredients2'] as $ing2) {
                        if($ing2['checked']==true){
                            array_push($ingredients2_values, $ing2['value']);
                            array_push($ingredients2_title, $ing2['label']);
                        }
                       
                    }
                }
                $ingredients2_values = implode(', ', $ingredients2_values);
                $ingredients2_title = implode(', ', $ingredients2_title);

                $pizza_order_items[] = array(
                    'order_id' => $order_id,
                    'item_id' => $items['item_id'],
                    'item_number' => $item_data->item_number,
                    'quantity' => $items['qty'],
                    'size' => $items['size'],
                    'layer' => $items['layer'],
                    'type' => $items['type'],
                    'dough' => $items['dough'],
                    'extras1' => $extras1_values,
                    'extras1_title' => $extras1_title,
                    'ingredients1' => $ingredients1_values,
                    'ingredients1_title' => $ingredients1_title,
                    'is_half' => $items['is_half'],
                    'add_item_id' => $add_item_id,
                    'item_description' => $items['item_description'],
                    'extras2' => $extras2_values,
                    'extras2_title' => $extras2_title,
                    'ingredients2' => $ingredients2_values,
                    'ingredients2_title' => $ingredients2_title,
                    'price' => $items['price'],
                    'sub_total' => $items['sub_total'],
                );
            }

            $success = $Pizza_order_item->save_pizza_order_item($pizza_order_items);
        }

        // $item_data = $this->Pizza_order->get_found_item($order_id);
        // $item_id = $item_data->item_id;
        // $name = $item_data->name;

        // // send expo notification
        // if($noti_send_to>0){
        //     $this->SendingExpoNotification('New Order Request', $noti_send_to, $order_id, $item_id, $name);
        // }
        // $this->sendPusherOrder($store_id, 'Pending',$order_id);

        $data = [
            'response' => 1,
            'order_id' => $order_id,
            'store_id' => $store_id,
            'counter_id' => $person_id,
        ];

        return $data;
    }

    // private function placePizzaOrder()
    // {
    //     if(!empty($this->post['item_id']) && !empty($this->post['quantity']) && !empty($this->post['type']) && !empty($this->post['size']) && !empty($this->post['person_id']) && !empty($this->post['store_id'])){

    //             $item_id = $this->post['item_id'];
    //             $item_quantities = $this->post['quantity'];
    //             $item_types = $this->post['type'];
    //             $size = $this->post['size'];
    //             $layer = $this->post['layer'];
    //             $is_half = $this->post['is_half'];
    //             $add_item_id = $this->post['add_item_id'];

    //             if($is_half==1 && ($add_item_id=='' || $add_item_id=='undefined')){
    //                 $data = [
    //                     'response' => -1,
    //                     'order_id' => '',
    //                     'store_id' => '',
    //                     'counter_id' => '',
    //                 ];

    //                 return $data;
    //             }
                
    //             $extras1 = $this->post['extras1'];
    //             $extras2 = $this->post['extras2'];
    //             $price = $this->post['price'];
    //             $order_quantity = ($item_quantities);
    //             $order_price = ($price);

    //             $date = date("Y-m-d");

    //             $count = 1;
    //             $order_number = 'PN0001';
    //             if($cData = $this->Pizza_order->exists_order_today($date)){
    //                 $count = $cData->count+1;
    //                 $order_number = 'TP'.str_pad($count, 4, 0, STR_PAD_LEFT);
    //             }

    //             $record = [
    //                 'person_id' => $this->post['person_id'],
    //                 'store_id' => $this->post['store_id'],
    //                 'description' => $this->post['description'],
    //                 'order_quantity' => $order_quantity,
    //                 'order_price' => $order_price,
    //                 'order_status' => 'Pending',
    //                 'count' => $count,
    //                 'order_number' => $order_number,
    //                 'deliver_at' => date('Y-m-d H:i:s'),
    //             ];

    //             $items = [
    //                 'item_id' => $item_id,
    //                 'quantity' => $item_quantities,
    //                 'type' => $item_types,
    //                 'size' => $size,
    //                 'layer' => $layer,
    //                 'extras1' => $extras1,
    //                 'is_half' => $is_half,
    //                 'add_item_id' => $add_item_id,
    //                 'extras2' => $extras2,
    //                 'price' => $price,
    //             ];

    //             $last_id = $this->User->pizza_order_items($record, $items);


    //             $person_id = $this->post['person_id'];
    //             $store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
    //             $company_name = $this->Employee->get_company_name($person_id,'counters')->company_name;
    //             $message = 'New Order From '.$company_name;

    //             $data = [
    //                 'response' => 1,
    //                 'order_id' => $last_id,
    //                 'store_id' => $store_id,
    //                 'counter_id' => $person_id,
    //             ];
    //             return $data;

    //     }else{
    //         $data = [
    //             'response' => -1,
    //             'order_id' => '',
    //             'store_id' => '',
    //             'counter_id' => '',
    //         ];
    //         return $data;
    //     }
    // }

    public function user_other_pizza_products_get($item_id, $size)
    {  

        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $pizza_items = $this->User->get_items($item_id,$size);

            if($pizza_items){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Pizza Items List',
                    'count' => count($pizza_items),
                    'payLoad' => $pizza_items
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Item Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_other_pizza_extras_get($item_id)
    {  

        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $pizza_items = $this->User->get_extras($item_id);

            if($pizza_items){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Other Items Topping',
                    'count' => count($pizza_items),
                    'payLoad' => $pizza_items
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Item Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_other_pizza_ingredients_get($item_id)
    {  

        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $pizza_items = $this->User->get_ingredients($item_id);

            if($pizza_items){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Other Items Ingredients',
                    'count' => count($pizza_items),
                    'payLoad' => $pizza_items
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Item Ingredients Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_order_list_get($person_id, $oStatus)
    {  
        $Pizza_order = new Pizza_order();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
        
            $search = $Pizza_order->search($store_id, $oStatus);
            $pizza_orders = $search->getResult();

            if($pizza_orders){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Pizza Orders List',
                    'count' => count($pizza_orders),
                    'payLoad' => $pizza_orders
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Order Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_order_update_status_get($order_id, $oStatus)
    {  

        $status = $this->Auth->check_auth_user($this->token);
        $Pizza_order = new Pizza_order();
        if($status)
        {
            $success = $Pizza_order->update_status($order_id, $oStatus);

            if($success){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Orders Updated Successfully',
                    'count' => 1,
                    'payLoad' => ''
                ];
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Updating Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_order_status_get($person_id)
    {  
        $status = $this->Auth->check_auth_user($this->token);
        $Pizza_order = new Pizza_order();
        if($status)
        {
            $store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;

            $data['processed_orders'] =$processed = $Pizza_order->get_orders($store_id, 'Inprocess');
            // $data['processed_orders'] = array_reverse($processed->result());

            $data['completed_orders'] =$completed = $Pizza_order->get_orders($store_id, 'Completed');
            // $data['completed_orders'] = array_reverse($completed->result());

            $data['pending_orders'] = $pending = $Pizza_order->get_orders($store_id, 'Pending');
            // $data['pending_orders'] = array_reverse($pending->result());

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Pizza Orders Status',
                    'count' => 1,
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Data Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_order_canceled_get($store_id)
    {  
        $Pizza_order = new Pizza_order();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            //$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;

            $data = $canceled = $Pizza_order->get_canceled_orders($store_id, 'Rejected');
            // $data = [];
            // foreach ($canceled as $value) {
            //     foreach ($value->items as $value2) {
            //         $data[] = $value2;
            //     }
            // }
            // $data['processed_orders'] = array_reverse($processed->result());

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Pizza Orders Status',
                    'count' => 1,
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Data Found.',
                    'count' => 0,
                    'payLoad' => [],
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }

    }

    public function user_pizza_order_delete_get($order_id, $store_id)
    { 

        $Pizza_order = new Pizza_order();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $status = 'Deleted';
            $success = $Pizza_order->delete_order($order_id, $status);
            if($success){


                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Deleted Successfully',
                    'count' => 1,
                    'payLoad' => ''
                ];
                $this->sendPusherOrderRemove($order_id, $store_id);
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Deleting Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function user_pizza_order_cancel_waste_get($order_id, $store_id)
    { 

        $Pizza_order = new Pizza_order();
        $Counter = new Counter();
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $oStatus = 'Rejected';
            $success = $Pizza_order->update_status($order_id, $oStatus);
            if($success){

                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Order Updated Successfully',
                    'count' => 1,
                    'payLoad' => ''
                ];

                $counter_ids = $Counter->get_all_pizza_counters_of_store($store_id);
                foreach ($counter_ids as $counter_id) {
                    $this->SendingSelfExpoNotification('RejectedItemScreen','Order Status Updated', $counter_id, $order_id);
                }

                $this->sendPusherOrderRemove($order_id, $store_id);
            }else{
                $response = [
                    'status' => 'Error',
                    'statusCode' => 2,
                    'statusMessage' => 'Order Updating Failed.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }


    public function sendPusherOrder($store_id, $status, $order_id)
    {
        require APPPATH . 'vendor/autoload.php';
        $Pizza_order = new Pizza_order();
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );
        $pusher = new Pusher(
            'd74cdee3f051d856e9f7',
            'bd5ee912b11458842929',
            '884876',
            $options
        );

        $search = $Pizza_order->search($store_id, $status, $order_id);

        $data = $search->getRow();

        $pusher->trigger('pusher-channel', 'order-event', $data);
    }

    public function sendPusherOrderRemove($order_id, $store_id)
    {
        require APPPATH . 'vendor/autoload.php';
        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );
        $pusher = new Pusher(
            'd74cdee3f051d856e9f7',
            'bd5ee912b11458842929',
            '884876',
            $options
        );

        $data = ['store_id' => $store_id, 'order_id' => $order_id];

        $pusher->trigger('pusher-channel', 'order-remove-event', $data);
    }

    public function SendingSelfExpoNotification($screen, $message, $noti_to, $order_id)
    {
        $expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            if ($tkn->token != '' && $tkn->token != false && $tkn->token != 'false') {
                $expoToken = $tkn->token;
            }
            
        }
        // Expo Push Notification
            $data = [
                'order_id' => $order_id,
                'title' => 'Order Status Updated',
                'taskCreated' => $screen,
            ];
            $data = json_encode($data);
            if($expoToken){
                // if token found send notification
                $this->sendExpoNotification($expoToken, $message, $data);
            }else{
                // else return true without sending notification
            }
        // Expo Push Notification
    }

    public function SendingExpoNotification($message, $noti_to, $order_id, $item_id, $name)
    {
        $expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            if ($tkn->token != '' && $tkn->token != false && $tkn->token != 'false') {
                $expoToken = $tkn->token;
            }
            
        }

        // Expo Push Notification
            
            $data = [
                'order_id' => $order_id,
                'item_id' => $item_id,
                'name' => $name,
                'title' => 'New Order Request',
            ];
            $data = json_encode($data);
            if($expoToken){
                // if token found send notification
                $this->sendExpoNotification($expoToken, $message, $data);
            }else{
                // else return true without sending notification
            }
        // Expo Push Notification
    }


    public function inventoryi_counter_item_get($start_date, $end_date, $counter_id = 'all', $item_type = -1)
    {
    
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $counter_detail = $this->Employee->get_company_name($counter_id,'counters');

            $counter_name = $counter_detail->company_name;

            $store_name = $this->Employee->get_company_name($counter_detail->store_id,'stores')->company_name;

            $title = '';
            
            $data = [];
            
            if($item_type=='Delivered'){
                $title = lang('reports_lang.reports_inventory_counter_report');

                $model = new Inventory_deliver_item();

                $report_data = $model->getData(array('start_date' => $start_date,
                'end_date' => $end_date, 'counter_id' => $counter_id, 'item_type' => $item_type));

                $tabular_data = array();
                foreach($report_data as $row)
                {

                    $tabular_data[] = $this->xss_clean(array(
                        'item_name' => $row['name'],
                        'item_number' => $row['item_number'],
                        'category' => $row['category'],
                        'store_counter' => $row['company_name'],
                        'quantity' => to_quantity_decimals($row['quantity']), 
                        'price' => trim(to_currency($row['cost_price'])),
                        'sub_total' => trim(to_currency($row['sub_total_value'])),
                    ));

                }

                $data = array(
                    'title' => $title,
                    'start_date' => $start_date, 
                    'end_date' => $end_date, 
                    'counter_name' => $counter_name,
                    'store_name' => $store_name,
                    'item_from_name' => 'Counter',
                    'headers' => $this->xss_clean($model->getDataColumns()),
                    'data' => $tabular_data,
                );

            }elseif($item_type=='Received'){
                $title = lang('reports_lang.reports_inventory_received_report');

                $model = new Inventory_received_item();

                $report_data = $model->getData(array('start_date' => $start_date,
                'end_date' => $end_date, 'counter_id' => $counter_id, 'item_type' => $item_type));

                $tabular_data = array();
                foreach($report_data as $row)
                {

                    if($cName = $this->Employee->get_company_name($row['store_id'],'stores')){
                        $company_name = $cName->company_name;
                    }elseif($cName = $this->Employee->get_company_name($row['store_id'],'counters')){
                        $company_name = $cName->company_name;
                    }

                    $tabular_data[] = $this->xss_clean(array(
                        'item_name' => $row['name'],
                        'item_number' => $row['item_number'],
                        'category' => $row['category'],
                        'store_counter' => $company_name,
                        'quantity' => to_quantity_decimals($row['quantity']), 
                        'price' => trim(to_currency($row['cost_price'])),
                        'sub_total' => trim(to_currency($row['sub_total_value'])),
                    ));
                }

                $data = array(
                    'title' => $title,
                    'start_date' => $start_date, 
                    'end_date' => $end_date, 
                    'counter_name' => $counter_name,
                    'store_name' => $store_name,
                    'item_from_name' => 'Counter',
                    'headers' => $this->xss_clean($model->getDataColumns()),
                    'data' => $tabular_data,
                );
            }elseif($item_type=='Discard'){
                $title = lang('reports_lang.reports_inventory_discard_report');

                $model = new Inventory_discard_item();

                $report_data = $model->getData(array('start_date' => $start_date,
                'end_date' => $end_date, 'counter_id' => $counter_id, 'item_type' => $item_type));

                $tabular_data = array();
                foreach($report_data as $row)
                {
                    $tabular_data[] = $this->xss_clean(array(
                        'item_name' => $row['name'],
                        'item_number' => $row['item_number'],
                        'category' => $row['category'],
                        'discard_type' => $row['discard_type'],
                        'quantity' => to_quantity_decimals($row['trans_inventory']), 
                        'price' => trim(to_currency($row['cost_price'])),
                        'sub_total' => trim(to_currency($row['sub_total_value'])),
                    ));
                }

                $data = array(
                    'title' => $title,
                    'start_date' => $start_date, 
                    'end_date' => $end_date, 
                    'counter_name' => $counter_name,
                    'store_name' => $store_name,
                    'item_from_name' => 'Counter',
                    'headers' => $this->xss_clean($model->getDataColumns()),
                    'data' => $tabular_data,
                );

            }elseif($item_type=='Consumed'){
                $title = lang('reports_lang.reports_inventory_consumed_report');
                $model = new Inventory_consumed_item();

                $report_data = $model->getData(array('start_date' => $start_date,
                'end_date' => $end_date, 'counter_id' => $counter_id, 'item_type' => $item_type));

                $tabular_data = array();
                foreach($report_data as $row)
                {

                    $tabular_data[] = $this->xss_clean(array(
                        'item_name' => $row['name'],
                        'item_number' => $row['item_number'],
                        'category' => $row['category'],
                        'quantity' => to_quantity_decimals($row['quantity']), 
                        'price' => trim(to_currency($row['cost_price'])),
                        'sub_total' => trim(to_currency($row['sub_total_value'])),
                    ));

                }

                $data = array(
                    'title' => $title,
                    'start_date' => $start_date, 
                    'end_date' => $end_date, 
                    'counter_name' => $counter_name,
                    'store_name' => $store_name,
                    'item_from_name' => 'Counter',
                    'headers' => $this->xss_clean($model->getDataColumns()),
                    'data' => $tabular_data,
                );

            }

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Counter Item Data',
                    'count' => 1,
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Data Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }

    public function inventoryi_pizza_stock_get($start_date, $end_date, $counter_id = -1, $item_type = 'All')
    {
        $status = $this->Auth->check_auth_user($this->token);
        if($status)
        {
            $model = new Inventory_pizza();
            $employee_id = $counter_id;

            // check if employee is pizza filling or pizza order 
            $Counter = new Counter();
            $counter_data = $Counter->get_info($employee_id);

            $counter_id = -1;
            $store_id = $this->Employee->get_company_name($employee_id,'counters')->store_id;
            $store_name = $this->Employee->get_company_name($store_id,'stores')->company_name;

            $item_from_name = lang('reports_lang.reports_counter');
            $company_name = 'All';

            if($counter_data->category==3){
                $counter_id = $employee_id;
                $store_id = -1;
                $counter_name = $this->Employee->get_company_name($counter_id,'counters')->company_name;
                $company_name = $counter_name;
            }

            $report_data = $model->getData(array('start_date' => $start_date,
                'end_date' => $end_date, 'store_id' => $store_id, 'counter_id' => $counter_id, 'item_type' => $item_type));

            // echo "<pre>";
            // print_r($report_data);
            // exit();

            $tabular_data = array();
            foreach($report_data as $row)
            {
                $tabular_data[] = $this->xss_clean(array(
                    'item_name' => $row['name'],
                    'item_number' => $row['item_number'],
                    'category' => $row['category'],
                    'quantity' => to_quantity_decimals($row['qty']), 
                    'price' => trim(to_currency($row['cost_price'])),
                    'sub_total' => trim(to_currency($row['sub_total_value'])),
                    'order_status' => $row['order_status'],
                ));
            }


            $data = array(
                'title' => lang('reports_pizza_summary_report'),
                'start_date' => $start_date, 
                'end_date' => $end_date, 
                'store_name' => $store_name,
                'counter_name' => $company_name,
                'headers' => $this->xss_clean($model->getDataColumns()),
                'data' => $tabular_data,
            );

            if($data){
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'Counter Pizza Data',
                    'count' => 1,
                    'payLoad' => $data
                ];
            }else{
                $response = [
                    'status' => 'Success',
                    'statusCode' => 1,
                    'statusMessage' => 'No Data Found.',
                    'count' => 0,
                    'payLoad' => '',
                ];
            }

            $this->set_response($response, REST_Controller::HTTP_OK);
        }
        else
        {
            $token = substr($this->token, 7);
            $data = $this->Login->logout_user($token);

            $errorResponse = [
                'status' => 'Error',
                'statusCode' => 3,
                'httpcode' => REST_Controller::HTTP_UNAUTHORIZED,
                'statusMessage' => 'User Authentication Failed',
            ];
            $this->set_response($errorResponse, REST_Controller::HTTP_OK);
        }
    }


    /*
    * Internal method to do XSS clean in the derived classes
    */
    protected function xss_clean($str, $is_image = FALSE)
    {
        // This setting is configurable in application/config/config.php.
        // Users can disable the XSS clean for performance reasons
        // (cases like intranet installation with no Internet access)
        // if($this->config->item('ospos_xss_clean') == FALSE)
        // {
            return $str;
        // }
        // else
        // {
        //     return $this->security->xss_clean($str, $is_image);
        // }
    }


    public function pizza_completed_orders_get(){
            $Pizza_order = new Pizza_order();
            $completed_order_data =  $Pizza_order->get_pizza_completed_order();
            $this->set_response($completed_order_data, REST_Controller::HTTP_OK);
    }

    public function delete_pizza_completed_order_get($id){
        $Pizza_order = new Pizza_order();
        $delete_order =  $Pizza_order->delete_pizza_completed_order($id);
        $this->set_response($delete_order, REST_Controller::HTTP_OK);
}

}
