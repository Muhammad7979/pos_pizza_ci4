<?php 
namespace App\Controllers;
use App\Controllers\SecureController;
use App\Models\Employee;
use App\Models\Pizza_order;
use Config\Database;

class Pizza_orders_list extends SecureController
{

	public $status;
	protected $Employee;

	protected $Pizza_order;
	public function __construct()
	{
		$this->Employee = new Employee();
		$this->Pizza_order = new Pizza_order();
		parent::__construct('pizza_orders_list');
		// $this->load->helper('date');
	}
	
	public function index()
	{
		$date = date('Y-m-d');
        $date = date("Y-m-d", strtotime($date));
		$microtime = round(microtime($date) * 1000);
       $currentDateTime = date('Y-m-d H:i:s');
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
		
		$search = $this->Pizza_order->search($store_id);
		$data['pizza_items'] = $search->getResult();
		$data['store_id'] = $store_id;

        foreach($data['pizza_items'] as $item)
        {

    	$deliver_at=$item->deliver_at;  

    	 if($currentDateTime > $deliver_at)
        {
        	$this->status='expired';

        	$item->order_status="Expired";
        // array_push($data['pizza_items'], ['status'=>$this->status]);

        }
        else
        {
        	$this->status='active';
        	$item->order_status="Active";

        // array_push($data['pizza_items'], ['status'=>$this->status]);

        }  	

        }
     
       
        // exit();
	
		
		// $data['get_size'] = ['','Mini','Small','Medium','Large','Xlarge'];
        // $data['get_layer'] = ['Thick','Thin'];
        // $data['get_dough'] = ['Plain','W WH'];
        // $data['get_isHalf'] = ['No','Yes'];
 
		// echo "<pre>";
		// print_r($data['pizza_items']);
		// echo "</pre>";
		// // $data = [];
		// exit();

		
		 // echo $currentDateTime;
     
		return view('pizza_orders/list', $data);
	}

	public function getLatestOrders()
	{
		   $currentDateTime = date('Y-m-d H:i:s');
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
		
		$search = $this->Pizza_order->searchLatest($store_id);
		$data['pizza_items'] = $search->getResult();
		$data['store_id'] = $store_id;

		 foreach($data['pizza_items'] as $item)
        {

    	$deliver_at=$item->deliver_at;  

    	 if($currentDateTime > $deliver_at)
        {
        	$this->status='expired';

        	$item->order_status="Expired";
        // array_push($data['pizza_items'], ['status'=>$this->status]);

        }
        else
        {
        	$this->status='active';
        	$item->order_status="Active";

      
        }  	

        }


    // echo "<pre>";
	// 	print_r($data['pizza_items']);
	// 	echo "</pre>";
	// 	// $data = [];
	// 	exit();

      
		echo $data = json_encode($data);
	}

	public function view($status='')
	{
		$person_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$store_id = $this->Employee->get_company_name($person_id,'counters')->store_id;
		
		$search = $this->Pizza_order->search($store_id, $status);
		$data['pizza_items'] = $search->result();
		$data['store_id'] = $store_id;

		// $data['get_size'] = ['','Mini','Small','Medium','Large','Xlarge'];
        // $data['get_layer'] = ['Thick','Thin'];
        // $data['get_dough'] = ['Plain','W WH'];
        // $data['get_isHalf'] = ['No','Yes'];
		
		return view('pizza_orders/list', $data);
	}

	public function update_status($order_id='', $microtime='', $status='')
	{
		if($status=='Inprocess'){
			$order = $this->updatingOrder($order_id);
		}elseif($status=='Rejected'){
			$order = $this->deletingOrder($order_id);
		}elseif($status=='Pending'){
			$order = $this->holdingOrder($order_id);
		}

		$noti_to = $this->Pizza_order->get_info($order_id)->person_id;
		$this->SendingExpoNotification('Order Status Updated', $noti_to, $order_id);

		redirect()->to('pizza_orders_list');
	}

	private function updatingOrder($order_id)
	{
		$status = 'Inprocess';
		$this->Pizza_order->update_status($order_id, $status);
	}
	
	private function holdingOrder($order_id)
	{
		$status = 'Pending';
		$this->Pizza_order->skipping_order($order_id, $status);
	}

	private function deletingOrder($order_id)
	{
		$status = 'Rejected';
		$this->Pizza_order->delete_order($order_id, $status);
	}


	public function SendingExpoNotification($message, $noti_to, $order_id)
	{
		$expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            $expoToken = $tkn->token;
        }

		// Expo Push Notification
            $data = [
                'order_id' => $order_id,
                'title' => 'Order Status Updated',
                'taskCreated' => 'AllTabsScreen',
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