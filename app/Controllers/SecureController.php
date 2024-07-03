<?php 
namespace App\Controllers;

use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Appconfig;
use CodeIgniter\Controller;
use Config\Database;

class SecureController extends BaseController 
{
	/*
	* Controllers that are considered secure extend Secure_Controller, optionally a $module_id can
	* be set to also check if a user can access a particular module in the system.
	*/
    protected $employeeModel;
    protected $module;
	protected $data;
	public function __construct($module_id = NULL, $submodule_id = NULL)
	{
        $this->employeeModel=new Employee();
        $this->module=new Module();

//		$this->track_page($module_id, $module_id);
		$logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
	   
		// load up global data visible to all the loaded views
		$this->data['allowed_modules'] = $this->module->get_allowed_modules($logged_in_employee_info->person_id);
		$this->data['user_info'] = $logged_in_employee_info;
		$this->data['controller_name'] = $module_id;

		$Notification = new Notification();
		$notifications = $Notification->getAllNotifications($logged_in_employee_info->person_id);
		$this->data['user_notifications'] = $notifications;

        $this->data['gu'] = new Gu();

		$appData = new AppConfig();
        $this->data['appData'] = $appData->get_all();



		// display notification list only to counters disable url redirection
		$this->data['counter_notifications_url']= 0;
		if($this->employeeModel->has_module_grant('reports_counter_item', $logged_in_employee_info->person_id))
        {
        	$this->data['counter_notifications_url']= 1;
        }

//        if(ENVIRONMENT != 'production')
//        {
//            $this->load->add_package_path(APPPATH.'third_party/debugbar');
//            $this->load->library('console');
//            $this->output->enable_profiler(TRUE);
//            $this->console->debug('hi, GU DEBUG!');
//        }
	}
	

	/*
	* Internal method to do XSS clean in the derived classes
	*/
	protected function xss_clean($str, $is_image = FALSE)
	{
		// This setting is configurable in application/config/config.php.
		// Users can disable the XSS clean for performance reasons
		// (cases like intranet installation with no Internet access)
		// if($this->appconfigModel->get('ospos_xss_clean') == FALSE)
		// {
			return $str;
		// }
		// else
		// {
		// 	return $this->security->xss_clean($str, $is_image);
		// }
	}

//	protected function track_page($path, $page)
//	{
//		if($this->config->item('statistics') == TRUE)
//		{
//			$this->load->library('tracking_lib');
//
//			if(empty($path))
//			{
//				$path = 'home';
//				$page = 'home';
//			}
//
//			$this->tracking_lib->track_page('controller/' . $path, $page);
//		}
//	}
//
//	protected function track_event($category, $action, $label, $value = NULL)
//	{
//		if($this->config->item('statistics') == TRUE)
//		{
//			$this->load->library('tracking_lib');
//
//			$this->tracking_lib->track_event($category, $action, $label, $value);
//		}
//	}

	public function numeric($str)
	{
		return parse_decimals($str);
	}

	public function check_numeric()
	{
		$appData=$this->appconfigModel->get_all();
		$result = TRUE;

		foreach($this->request->getGet() as $str)
		{
			$result = parse_decimals($str,$appData);
		}

		echo $result !== FALSE ? 'true' : 'false';
	}

	public function saveAndSendExpoNotification($message, $noti_from, $noti_to, $order_id)
	{
		$expoToken = "";
        if($tkn = $this->getExpoToken($noti_to)){
            $expoToken = $tkn->token;
        }
		// Save Notification
		$nData = [
			'order_id'=> $order_id,
			'noti_from'=> $noti_from,
			'noti_to'=> $noti_to,
			'details'=> $message,
			'taskCreated' => 'OrderListDetailScreen',
			'category'=> 0
		];
		$Notification = new Notification();
		$nId = $Notification->saveNotification($nData);
		// Save Notification

		// Expo Push Notification
			
            $data = [
                'order_id' => $order_id,
                'notification_id' => $nId,
                'taskCreated' => 'OrderListDetailScreen'
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
      $db = Database::connect();
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

    public function saveAndSendPusherNotification($order_id, $noti_to, $noti_from, $message, $category)
	{
		require APPPATH . '../vendor/autoload.php';
		$options = array(
		    'cluster' => 'ap1',
		    'useTLS' => true
		);
		$pusher = new \Pusher\Pusher(
		    'd74cdee3f051d856e9f7',
		    'bd5ee912b11458842929',
		    '884876',
		    $options
		);

		// Save Notification
		    $Notification = new Notification();
			$nData = [
				'order_id'=> $order_id,
				'noti_from'=> $noti_from,
				'noti_to'=> $noti_to,
				'details'=> $message,
				'category'=> $category,
			];
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

	// convert special character to text and text to special characters
	// sort = 0 to convert from special char to text
	// sort = 1 to convert text to special char 
	public function specialCharacterReplace($string, $sort=0) {

		if($sort==0){
			//convert to character
			$garbagearray = array('@','#','$','%','^','&','*');
			$convertarray = array('at','hash','dollar','percentage','caret','and','star');
		}else{
			//convert to special
			$convertarray = array('@','#','$','%','^','&','*');
			$garbagearray = array('at','hash','dollar','percentage','caret','and','star');
		}
		$garbagecount = count($garbagearray);
		for ($i=0; $i<$garbagecount; $i++) {
			$string = str_replace($garbagearray[$i], $convertarray[$i], $string);
		}

		return $string;
	}


	// this is the basic set of methods most OSPOS Controllers will implement
	public function index() { return FALSE; }
	public function search() { return FALSE; }
	public function suggest_search() { return FALSE; }
	public function view($data_item_id = -1) { return FALSE; }
	public function save($data_item_id = -1) { return FALSE; }
	public function delete() { return FALSE; }

}
?>