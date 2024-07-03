<?php
namespace App\Controllers;
use App\Controllers\SecureController;
use App\Libraries\BarcodeLib;
use App\Libraries\CartLib;
use App\Libraries\Gu;
use App\Models\Apis\User;
use App\Models\Appconfig;
use App\Models\Employee;
use App\Models\Pizza_order;
use App\Models\Pizza_order_item;
use Config\Database;
use Pusher\Pusher;

class Pizza_orders extends SecureController
{

	protected $Employee;
	protected $Pizza_order;
    protected $Pizza_order_item;
	protected $appData;
	protected $cart;
	protected $barcode_lib;

	protected $User;
	public function __construct()
	{
       
		parent::__construct('pizza_orders');
		$this->appData = new Appconfig();
		$this->Employee = new Employee();
		$this->Pizza_order = new Pizza_order();
		$this->Pizza_order_item = new Pizza_order_item();
		$this->cart = new CartLib();
		$this->User = new User();
		$this->barcode_lib = new BarcodeLib();
	}
	
	public function index()
	{
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;

		// $store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
		// $data['table_headers'] = $this->xss_clean(get_counter_orders_manage_table_headers());
		$data['pizza_items'] = $this->Pizza_order->get_pizza_menu();

		//$data['get_html'] = $this->getHTMLofView(14,167,226);

		// echo "<pre>";
		// print_r($data['pizza_items']->result());
		// echo "</pre>";
		return view('pizza_orders/manage', $data);
	}

	public function getHTMLofView($order_id='', $store_id='', $counter_id='')
    {

    	// $this->load->library('barcode_lib');
        // $this->load->model('apis/User','User');

        $order_data  = $this->User->order_data_slip($order_id);
        $items = $this->User->order_data_items($order_id);

        $counter_name = $this->User->get_company_name($counter_id,'counters')->company_name; 
        $store_name = $this->User->get_company_name($store_id,'stores')->company_name; 
        //print_r($data);exit();
        $data = [
            'branch' => $store_name,
            'counter' => $counter_name,
            'order_id' => $order_data->order_id,
            'count' => $order_data->order_number,
            'reordered' => $order_data->reordered,
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
				'order_id' => $order_data->order_id,
            ],
            'items' => $items
        ];

        $config = $this->barcode_lib->get_barcode_config();
        $config['barcode_type'] = 'Code128';
        $data['barcode_lib'] = $this->barcode_lib;
        $data['barcode_config'] = $config;

        return view('slip', $data);
    }

	public function save($order_id = -1)
	{

		$pizza_order_items = [];
		$total_qty = 0;
        $branch_code = $this->appData->get('branch_code');
		$date = !empty(request()->getPost('deliver_at')) ? request()->getPost('deliver_at') : date('Y-m-d');
        $date = date("Y-m-d", strtotime($date));
		$microtime = round(microtime($date) * 1000);

		$count = 1;
		$order_number = 'PN0001';
        if($cData = $this->Pizza_order->exists_order_today($date)){
            $count = $cData->count+1;
            $order_number = 'PN'.str_pad($count, 4, 0, STR_PAD_LEFT);
        }

        $person_id = $this->Employee->get_logged_in_employee_info()->person_id;

		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;

		// get id of counter has permission for pizza orders list eg filling counter 
		$noti_send_to = $this->Pizza_order_item->get_counter_id($store_id, 'counters', 'pizza_orders_list');

		$deliver_at = !empty(request()->getPost('deliver_at')) ? request()->getPost('deliver_at') : date("Y-m-d H:i:s", strtotime('+15 minutes'));

		$pizza_order_data = array(
			'person_id' => $person_id,
			'store_id' => $store_id,
			'order_description' => request()->getPost('order_description'),
			'customer_name' => request()->getPost('customer_name'),
			'customer_phone' => request()->getPost('customer_phone'),
			'order_quantity' => $this->load_cart_count(),
			'order_price' => $this->cart->total(),
			'order_status' => 'Pending',
			'count' => $count,
			'order_number' => $order_number,
			'deliver_at' => $deliver_at,
			'microtime' => $microtime,
			'branch_code' => $branch_code,
		);

		if($this->Pizza_order->save_pizza_order($pizza_order_data, $order_id))
		{
			$success = TRUE;
			//New item ordered
			if ($order_id == -1)
			{
				$order_id = $pizza_order_data['order_id'];
			}

			foreach ($this->cart->contents() as $items) {

				$item_data = $this->Pizza_order_item->get_item_number($items['size'],$items['item_category1'],$items['item_category2']);

				$pizza_order_items[] = array(
					'order_id' => $order_id,
					'item_id' => $items['id'],
					'item_number' => $item_data->item_number,
					'quantity' => $items['qty'],
					'size' => $items['size'],
					'layer' => $items['layer'],
					'type' => $items['type'],
					'dough' => $items['dough'],
					'extras1' => $items['extras1'],
					'extras1_title' => $items['extras1_title'],
					'ingredients1' => $items['ingredients1'],
					'ingredients1_title' => $items['ingredients1_title'],
					'is_half' => $items['is_half'],
					'add_item_id' => $items['add_item_id'],
					'item_description' => $items['item_description'],
					'extras2' => $items['extras2'],
					'extras2_title' => $items['extras2_title'],
					'ingredients2' => $items['ingredients2'],
					'ingredients2_title' => $items['ingredients2_title'],
					'price' => $items['price'],
					'sub_total' => $items['subtotal'],
				);
			}

			$success = $this->Pizza_order_item->save_pizza_order_item($pizza_order_items);
 
			// destroy cart
			$this->cart->destroy();

			// $redis_save = $this->Pizza_order->redis_save($pizza_order_data, $order_id,$pizza_order_items);
			
			$item_data = $this->Pizza_order->get_found_item($order_id);
			
			$item_id = $item_data->item_id;
			$name = $item_data->name;
            $gu = new Gu();
			if(!$gu->isServer() && $noti_send_to>0){
				$this->SendingExpoNotification('New Order Request', $noti_send_to, $order_id, $item_id, $name);
			}
			// $this->sendPusherOrder($store_id, 'Pending',$order_id);

			$html = $this->getHTMLofView($order_id,$store_id,$person_id);

			echo json_encode(array('success' => $success,
								'message' => lang('pizza_order_lang.pizza_orders_successful_adding'), 'id' => $order_id, 'store' => $store_id, 'counter' => $person_id, 'html' => $html));
		}
		else//failure
		{
			//$pizza_order_data = $this->xss_clean($pizza_order_data);

			echo json_encode(array('success' => FALSE, 
								'message' => lang('pizza_order_lang.pizza_orders_error_adding_updating'), 'id' => -1));
		}
	}

	// Count Total Quantities
    function load_cart_count()
    { 
        $output = 0;
        foreach ($this->cart->contents() as $items) {
            $output += $items['qty'];
        }

        return $output;
    }

	public function get_extras($item_id='', $size=-1)
	{
		if($cData = $this->Pizza_order->get_extras($item_id)){
            $data['extras'] = $cData;
            $data['ingredients'] = $this->Pizza_order->get_ingredients($item_id);
            if($size!=-1)
            $data['items'] = $this->Pizza_order->get_items($item_id,$size);
        	echo json_encode($data);
        }else{
        	$data = [];
        	echo json_encode($data);
        } 
	}


	public function sendPusherOrder($store_id, $status, $order_id)
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

		$search = $this->Pizza_order->search($store_id, $status, $order_id);

		$data = $search->getRow();

		if($data->customer_name==''){
			$data->customer_name = 'Walking Customer';
		}

		$pusher->trigger('pusher-channel', 'order-event', $data);
	}


	public function SendingExpoNotification($message, $noti_to, $order_id, $item_id, $name)
	{
		$expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            $expoToken = $tkn->token;
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

	public function getExpoToken($user_id='')
    {
		$db = Database::connect('default');
      $builder = $db->table('expoToken')
            ->where('user_id', $user_id);
        
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

}
?>