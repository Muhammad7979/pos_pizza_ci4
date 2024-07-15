<?php 
namespace App\Controllers;

use App\Libraries\Gu;
use App\Libraries\ItemLib;
use App\Libraries\BarcodeLib;
use App\Libraries\Biometric;
use App\Libraries\SaleLib;
use App\Libraries\ReceivingLib;
use App\Models\Appconfig;
use App\Models\Inventory;
use App\Models\reports\Inventory_pizza;
use App\Models\Stock_location;
use App\Models\reports\Summary_categories;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Person;
use App\Models\reports\Detailed_sales;
use App\Models\reports\Inventory_consumed_item;
use App\Models\reports\Inventory_counter;
use App\Models\reports\Specific_customer;
use App\Models\reports\Specific_discount;
use App\Models\reports\Specific_employee;
use App\Models\reports\Summary_customers;
use App\Models\reports\Summary_discounts;
use App\Models\reports\Summary_employees;
use App\Models\reports\Summary_items;
use App\Models\reports\Summary_payments;
use App\Models\reports\Summary_sales;
use App\Models\reports\Summary_suppliers;
use App\Models\reports\Summary_taxes;
 use App\Models\reports\Inventory_deliver_item;
use App\Models\reports\Inventory_discard_item;
use App\Models\reports\Inventory_received_item;
use App\Models\reports\Inventory_store;
use App\Models\reports\Inventory_warehouse;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\reports\Inventory_order;



class Reports extends SecureController
{

	protected $db;
    protected $appData;
	protected $gu;
    protected $employeeModel;
    protected $stockLocation;
	protected $branchModel;
    protected $item;
    protected $itemLib;
	protected $barcode_lib;
	protected $sale_lib;
	protected $receiving_lib;
	protected $module;
	protected $Warehouse;
	protected $exploder;
	protected $Store;
	protected $Counter;
	protected $Vendor;
    protected $Inventory_received_item;
	protected $Inventory_order;
	public function __construct()
	{
		helper('report');
		parent::__construct('reports');

		$this->db = \Config\Database::connect();
        $this->employeeModel=new Employee();
		$this->module=new Module();
        $this->stockLocation=new Stock_location();
		$this->branchModel=new Branch();
        $this->item=new Item();
        $this->itemLib=new ItemLib();
		$this->barcode_lib=new BarcodeLib();
		$this->sale_lib=new SaleLib();
		$this->receiving_lib=new ReceivingLib();
		$this->gu=new Gu();
		$this->Warehouse = new Warehouse();
		$this->Store = new Store();
		$this->Counter = new Counter();
		$this->Vendor = new Vendor();
		$this->Inventory_order = new Inventory_order();

		$method_name = service('uri')->getSegment(2);
		$this->exploder = $exploder = explode('_', $method_name);

		if(sizeof($exploder) > 1)
		{
			preg_match('/(?:inventory)|([^_.]*)(?:_graph|_row)?$/', $method_name, $matches);
			preg_match('/^(.*?)([sy])?$/', array_pop($matches), $matches);
			$submodule_id = $matches[1] . ((count($matches) > 2) ? $matches[2] : 's');

			// new reports for Store, store, counter, vendor inventory
			if($exploder[1]=='order' || $exploder[1]=='warehouse' || $exploder[1]=='store' || $exploder[1]=='counter' || $exploder[1]=='vendor' || $exploder[1]=='processing' || $exploder[1]=='pizza'){
				$submodule_id = $exploder[1].'_'.$exploder[2];
			}

			// check access to report submodule
			if(!$this->employeeModel->has_grant('reports_' . $submodule_id, $this->employeeModel->get_logged_in_employee_info()->person_id))
			{
				redirect('no_access/reports/reports_' . $submodule_id);
			}
		}


	}


	//Initial report listing screen
	public function index($module_id=NULL)
	{
		$data = $this->data;
		$logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		$person_id = $logged_in_employee_info->person_id;
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data['barcode_lib']=$this->barcode_lib;
		$data['person_id']=$person_id;
		$data['grants'] = $this->xss_clean($this->employeeModel->get_employee_grants(session()->get('person_id')));
		$data = $this->xss_clean($data);
		if($this->employeeModel->has_grant('config',$person_id)){
			return view('reports/listing',$data);
		}else{
			return view('stock_reports/listing',$data);
		}
	}
	
	public function summary_categories($start_date=null, $end_date=null, $sale_type=null, $location_id = 'all',$module_id=null)
	{
		$appData = $this->appconfigModel->get_all();
			$this->employeeModel=new Employee();
			$this->module=new Module();
			$logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
			if(!$this->gu->checkStartDate($start_date)){
				echo "Please select the correct date";
				redirect('reports');
				return false;
			}
			
			$model = new Summary_categories();
			
			$report_data = $model->getData(array('start_date' => $start_date,
			'end_date' => $end_date, 'sale_type' => $sale_type,
			'location_id' => $location_id));
			
			$tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['category'],
				to_quantity_decimals($row['quantity_purchased']),
				to_currency($row['subtotal']),
				to_currency($row['total']),
				to_currency($row['tax']),
				to_currency($row['cost']),
				to_currency($row['profit'])
			));
		}
			
		$data = array(
			'title' => lang('reports_lang.reports_categories_summary_report'),
			'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
		);
			$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
			$data = array_merge($data, $this->data);
			return view('reports/tabular', $data);
		}
		
		// 	//Summary customers report
			public function summary_customers($start_date, $end_date, $sale_type, $location_id = 'all',$module_id=null)
			{
	
			$appData = $this->appconfigModel->get_all();
			$this->employeeModel=new Employee();
			$this->module=new Module();
	
					if(!$this->gu->checkStartDate($start_date)){
				echo "Please select the correct date";
				//$this->load->view('reports/listing');
				redirect('reports');
				return false;
			}
	
			$model = new Summary_customers();
			$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
			$tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['customer'],
					to_quantity_decimals($row['quantity_purchased']),
					to_currency($row['subtotal']),
					to_currency($row['total']),
					to_currency($row['tax']),
					to_currency($row['cost']),
					to_currency($row['profit'])
				));
			}
	
			$data = array(
				'title' => lang('reports_lang.reports_customers_summary_report'),
				'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
			);
				$person = new Person();
				$data['person']= json_encode($person->get_all()->getResult());
				$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
				$data = array_merge($data, $this->data);
			return view('reports/tabular', $data);
		}
	
	//Summary items report
	public function summary_items($start_date, $end_date, $sale_type,
	$location_id = 'all', $payment_type = 'all', $branch_code = 'all')
	{
		$appData = $this->appconfigModel->get_all();
			$this->employeeModel=new Employee();
			$this->module=new Module();
	if(!$this->gu->checkStartDate($start_date)){
	echo "Please select the correct date";
	//$this->load->view('reports/listing');
	redirect('reports');
	return false;
	}
	
	$model = new Summary_items();
	
	if($sale_type == "sales"){
	$sale_type = "sales";
	$sale_mode = "normal";
	}
	elseif ($sale_type == "returns"){
	$sale_type = "returns";
	$sale_mode = "normal";
	}
	elseif ($sale_type == "all"){
	$sale_type = "all";
	$sale_mode = "normal";
	}
	else{
	$sale_mode = $sale_type;
	$sale_type = "returns";
	}
	
	$report_data = $model->getData(array(
	'start_date' => $start_date,
	'end_date' => $end_date,
	'sale_type' => $sale_type,
	'sale_mode' => $sale_mode,
	'payment_type' => $payment_type,
	'location_id' => $location_id,
	'branch_code' => $branch_code,
	));
	$tabular_data = array();
	foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['name'],
				to_quantity_decimals($row['quantity_purchased']),
				//to_currency($row['subtotal']),
				to_currency($row['total']),
//				to_currency($row['tax']),
//				to_currency($row['cost']),
//				to_currency($row['profit'])
			));
		}

	$branch_info = $this->gu->getStoreInfoByBranchCode($branch_code);
	
	$title = lang('reports_lang.reports_items_summary_report')
	." (" .$branch_info['name'] ." / " .$branch_code .")";
	
	
	$subtitle = $title ."<hr/>"
	. date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date))
	. ' <br/> Sale Type: ' . ucwords($sale_type ." " .$sale_mode)
	. ' <br/> Payment Type: ' . ucfirst($payment_type);
	
	$data = array(
	'title' => $title,
	'subtitle' => $subtitle,
	
	'headers' => $this->xss_clean($model->getDataColumns()),
	'data' => $tabular_data,
	'summary_data' => $this->xss_clean($model->getSummaryData(array(
	'start_date' => $start_date,
	'end_date' => $end_date,
	'sale_type' => $sale_type,
	'sale_mode' => $sale_mode,
	'payment_type' => $payment_type,
	'location_id' => $location_id,
	'branch_code' => $branch_code,
	)))
	);
				$person = new Person();
				$data['person']= json_encode($person->get_all()->getResult());
				$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
				$data = array_merge($data, $this->data);
	            return view('reports/tabular', $data);
	}
	
	
	
	//Summary sales report
	public function summary_sales($start_date, $end_date, $sale_type,
	$location_id = 'all', $payment_type = 'all', $branch_code = 'all')
	{
		$appData = $this->appconfigModel->get_all();
		$this->employeeModel=new Employee();
		$this->module=new Module();
	if(!$this->gu->checkStartDate($start_date)){
	echo "Please select the correct date";
	redirect('reports');
	return false;
	}
	
	$model = new Summary_sales();
	if($sale_type == "sales"){
	$sale_type = "sales";
	$sale_mode = "normal";
	}
	elseif ($sale_type == "returns"){
	$sale_type = "returns";
	$sale_mode = "normal";
	}
	elseif ($sale_type == "all"){
	$sale_type = "all";
	$sale_mode = "normal";
	}
	else{
	$sale_mode = $sale_type;
	$sale_type = "returns";
	}
	
	$report_data = $model->getData(array(
	'start_date' => $start_date,
	'end_date' => $end_date,
	'sale_type' => $sale_type,
	'sale_mode' => $sale_mode,
	'payment_type' => $payment_type,
	'location_id' => $location_id,
	'branch_code' => $branch_code
	)
	);
	
	$tabular_data = array();
	foreach($report_data as $row)
	{
	$tabular_data[] = $this->xss_clean(array($row['sale_date'],
	to_quantity_decimals($row['quantity_purchased']),
	// to_currency($row['subtotal']),
	to_currency($row['total']),
	// to_currency($row['tax']),
	// to_currency($row['cost']),
	// to_currency($row['profit'])
	));
	}
	
	$branch_info = $this->gu->getStoreInfoByBranchCode($branch_code);
	$title = lang('reports_lang.reports_sales_summary_report')
	." (" .$branch_info['name'] ." / " .$branch_code .")";
	
	$subtitle = $title ."<hr/>"
	. date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date))
	. ' <br/> Sale Type: ' . ucwords($sale_type ." " .$sale_mode)
	. ' <br/> Payment Type: ' . ucfirst($payment_type);
	
	$data = array(
	'title' => $title,
	'subtitle' => $subtitle,
	'headers' => $this->xss_clean($model->getDataColumns()),
	'data' => $tabular_data,
	'summary_data' => $this->xss_clean($model->getSummaryData(array(
	'start_date' => $start_date,
	'end_date' => $end_date,
	'sale_type' => $sale_type,
	'sale_mode' => $sale_mode,
	'payment_type' => $payment_type,
	'location_id' => $location_id,
	'branch_code' => $branch_code,
	)))
	);
				$person = new Person();
				$data['person']= json_encode($person->get_all()->getResult());
				$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
		    	$data = array_merge($data, $this->data);

	return view('reports/tabular', $data);
	}
	
	
	
	
	// 	//Summary suppliers report
		public function summary_suppliers($start_date, $end_date, $sale_type, $location_id = 'all')
		{
			$appData = $this->appconfigModel->get_all();
			$this->employeeModel=new Employee();
			$this->module=new Module();
			if(!$this->gu->checkStartDate($start_date)){
				echo "Please select the correct date";
				//$this->load->view('reports/listing');
				redirect('reports');
				return false;
			}
			$model = new Summary_suppliers();
			$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
			$tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['supplier'],
					to_quantity_decimals($row['quantity_purchased']),
					to_currency($row['subtotal']),
					to_currency($row['total']),
					to_currency($row['tax']),
					to_currency($row['cost']),
					to_currency($row['profit'])
				));
			}
			$data = array(
				'title' => lang('reports_lang.reports_suppliers_summary_report'),
				'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
			);
			$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
			$data = array_merge($data, $this->data);
			return view('reports/tabular', $data);
		}


		public function summary_discounts($start_date, $end_date, $sale_type, $location_id = 'all',$module_id=null)
		{
			$appData = $this->appconfigModel->get_all();
			$this->employeeModel=new Employee();
			$this->module=new Module();
			if(!$this->gu->checkStartDate($start_date)){
				echo "Please select the correct date";
				//$this->load->view('reports/listing');
				redirect('reports');
				return false;
			}
			$model = new Summary_discounts;
			$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
			$tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['discount_percent'], 
				$row['count']
			));
		}
		
		$data = array(
			'title' => lang('reports_lang.reports_discounts_summary_report'),
			'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
		);
		$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data = array_merge($data, $this->data);
			return view('reports/tabular', $data);
		}
	
	
	
//Summary employees report
public function summary_employees($start_date, $end_date, $sale_type, $location_id = 'all')
{
	$appData = $this->appconfigModel->get_all();
	$this->employeeModel=new Employee();
	$this->module=new Module();
	if(!$this->gu->checkStartDate($start_date)){
		echo "Please select the correct date";
		//$this->load->view('reports/listing');
		redirect('reports');
		return false;
	}
	$model = new  Summary_employees();
	$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
	$tabular_data = array();
	foreach($report_data as $row)
	{
		$tabular_data[] = $this->xss_clean(array($row['employee'],
			to_quantity_decimals($row['quantity_purchased']),
			to_currency($row['subtotal']),
			to_currency($row['total']),
			to_currency($row['tax']),
			to_currency($row['cost']),
			to_currency($row['profit'])
		));
	}

	$data = array(
		'title' => lang('reports_lang.reports_employees_summary_report'),
		'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),
		'headers' => $this->xss_clean($model->getDataColumns()),
		'data' => $tabular_data,
		'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
	);
		$person = new Person();
		$data['person']= json_encode($person->get_all()->getResult());
		$data = array_merge($data, $this->data);
    	return view('reports/tabular', $data);
}

// 	//Summary payments report
public function summary_payments($start_date, $end_date, $sale_type, $location_id = 'all',  $payment_type, $branch_code, $item_type, $item_id)
{
	$appData = $this->appconfigModel->get_all();
	$this->employeeModel = new Employee();
	$this->module = new Module();
	if (!$this->gu->checkStartDate($start_date)) {
		echo "Please select the correct date";
		//$this->load->view('reports/listing');
		redirect('reports');
		return false;
	}
	$model = new Summary_payments();
	$report_data = $model->getData(array('start_date' => $start_date,
	'end_date' => $end_date, 'sale_type' => $sale_type,
	'location_id' => $location_id,
	'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'item_id'=> $item_id));
	$tabular_data = array();
	foreach ($report_data as $row) {
		$tabular_data[] = $this->xss_clean(array(
			$row['payment_type'],
			$row['count'],
			to_currency($row['payment_amount'])
		));
	}
	$data = array(
		'title' => lang('reports_lang.reports_payments_summary_report'),
		'headers' => $this->xss_clean($model->getDataColumns()),
		'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),			'headers' => $this->xss_clean($model->getDataColumns()),
		'data' => $tabular_data,
		'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date,
		'end_date' => $end_date, 'sale_type' => $sale_type,
		'location_id' => $location_id,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'item_id'=> $item_id)))
	);
	$person = new Person();
	$data['person'] = json_encode($person->get_all()->getResult());
	$data = array_merge($data, $this->data);
	return view('reports/tabular', $data);
}

	public function date_input()
	{
		$Stock_location = new Stock_location();
		$stock_locations = $data = $this->xss_clean($Stock_location->get_allowed_locations('sales'));
		$stock_locations['all'] = lang('reports_lang.reports_all');
		$data['stock_locations'] = array_reverse($stock_locations, TRUE);
		$data['mode'] = 'sale';

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());

		$data['stock_locations'] = $this->xss_clean($this->stockLocation->get_allowed_locations());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/date_input', $data);
	}


	public function summary_taxes($start_date, $end_date, $sale_type, $location_id = 'all',$module_id=null)
	{
		$appData = $this->appconfigModel->get_all();
        $this->employeeModel=new Employee();
        $this->module=new Module();
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            //$this->load->view('reports/listing');
            redirect('reports');
            return false;
        }
		$model = new Summary_taxes();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['percent'], 
				$row['count'], 
				to_currency($row['subtotal']), 
				to_currency($row['total']), 
				to_currency($row['tax'])
			));
		}
		$data = array(
			'title' => lang('reports_lang.reports_taxes_summary_report'),
			'subtitle' => date($appData['dateformat'], strtotime($start_date)) . '-' . date($appData['dateformat'], strtotime($end_date)),
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)))
		);
			$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data = array_merge($data, $this->data);
		return view('reports/tabular', $data);
	}


// 	//Input for reports that require only a date range. (see routes.php to see that all graphical summary reports route here)
	public function date_input_sales()
	{
		
		$Stock_location = new Stock_location();
		$stock_locations = $data = $this->xss_clean($Stock_location->get_allowed_locations('sales'));
		$stock_locations['all'] =  lang('reports_lang.reports_all');
        $data['mode'] = 'sale';
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/date_input', $data);
	}

    public function date_input_recv()
    {
		$Stock_location = new Stock_location();
		$stock_locations = $data = $this->xss_clean($Stock_location->get_allowed_locations('receivings'));
		$stock_locations['all'] =  lang('report_lang.reports_all');
		$data['stock_locations'] = array_reverse($stock_locations, TRUE);
 		$data['mode'] = 'receiving';
		$data = array_merge($data, $this->data);

        return view('reports/date_input', $data);
    }

// 	//Graphical summary sales report
public function graphical_summary_sales($start_date, $end_date, $sale_type, $location_id = 'all', $payment_type, $branch_code, $item_type, $item_id)
{
	$model = new Summary_sales();
	if($sale_type == "sales"){
		$sale_type = "sales";
		$sale_mode = "normal";
		}
		elseif ($sale_type == "returns"){
		$sale_type = "returns";
		$sale_mode = "normal";
		}
		elseif ($sale_type == "all"){
		$sale_type = "all";
		$sale_mode = "normal";
		}
		else{
		$sale_mode = $sale_type;
		$sale_type = "returns";
		}
	$report_data = $model->getData(array('start_date' => $start_date,
		'end_date' => $end_date, 'sale_type' => $sale_type,
		'location_id' => $location_id,'sale_mode' => $sale_mode,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'item_id'=> $item_id));

	$labels = array();
	$series = array();
	$appData = new Appconfig();
	foreach($report_data as $row)
	{
		$row = $this->xss_clean($row);

		$date = date($appData->get('dateformat'), strtotime($row['sale_date']));
		$labels[] = $date;
		$series[] = array('meta' => $date, 'value' => $row['total']);
	}

	$data = array(
		'title' => lang('reports_lang.reports_sales_summary_report'),
		'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
		'chart_type' => 'reports/graphs/line.php',
		'labels_1' => $labels,
		'series_data_1' => $series,
		'summary_data_1' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type,'sale_mode' => $sale_mode, 'item_id'=> $item_id ))),
		'yaxis_title' => lang('reports_lang.reports_revenue'),
		'xaxis_title' => lang('reports_lang.reports_date'),
		'show_currency' => TRUE
	);
	$data['empModel']=$this->employeeModel;
	$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
	$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
	$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
	$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
	$data = array_merge($data, $this->data);

	return view('reports/graphical', $data);
}



// 	//Graphical summary items report
public function graphical_summary_items($start_date, $end_date, $sale_type, $location_id = 'all',
$payment_type, $branch_code, $item_type, $item_id)
{
	if(!$this->gu->checkStartDate($start_date)){
		echo "Please select the correct date";
		//$this->load->view('reports/listing');
		redirect('reports');
		return false;
	}

	// $this->load->model('reports/Summary_items');

	$model = new Summary_items();
	if($sale_type == "sales"){
		$sale_type = "sales";
		$sale_mode = "normal";
		}
		elseif ($sale_type == "returns"){
		$sale_type = "returns";
		$sale_mode = "normal";
		}
		elseif ($sale_type == "all"){
		$sale_type = "all";
		$sale_mode = "normal";
		}
		else{
		$sale_mode = $sale_type;
		$sale_type = "returns";
		}
	$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id,
	'payment_type' => $payment_type,'branch_code' => $branch_code,'sale_type' => $sale_type,
	'sale_mode' => $sale_mode, 'item_id'=> $item_id 
));

	$labels = array();
	$series = array();
	foreach($report_data as $row)
	{
		$row = $this->xss_clean($row);

		$labels[] = $row['name'];
		$series[] = $row['total'];
	}
	$appData = new Appconfig();
	$data = array(
		'title' => lang('reports_lang.reports_items_summary_report'),
		'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
		'chart_type' => 'reports/graphs/hbar.php',
		'labels_1' => $labels,
		'series_data_1' => $series,
		'summary_data_1' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'sale_mode' => $sale_mode, 'item_id'=> $item_id ))),
		'yaxis_title' => lang('reports_lang.reports_items'),
		'xaxis_title' => lang('reports_lang.reports_revenue'),
		'show_currency' => TRUE
	);

	$data['empModel']=$this->employeeModel;
	$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
	$data['stock_locations'] = $this->xss_clean($this->stockLocation->get_allowed_locations());
	$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
	$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
	$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
	$data = array_merge($data, $this->data);

	return view('reports/graphical', $data);
}
	//Graphical summary customers report
	public function graphical_summary_categories($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            //$this->load->view('reports/listing');
            redirect('reports');
            return false;
        }
        $model=new Summary_categories();
		$appData = new Appconfig();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		
		$summary = $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)));

		$labels = array();
		$series = array();
        $appdata = $this->appconfigModel->get_all();
		
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);

			$labels[] = $row['category'];
			$series[] = array('meta' => $row['category'] . ' ' . round($row['total'] / $summary['total'] * 100, 2) . '%', 'value' => $row['total']);
		}

		$data = array(
			'title' => lang('reports_lang.reports_categories_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/pie.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $summary,
			'show_currency' => TRUE,
			
		);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);

		return view('reports/graphical', $data);
	}

// 	//Graphical summary suppliers report
	public function graphical_summary_suppliers($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_suppliers();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$summary = $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)));
		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);
			$labels[] = $row['supplier'];
			$series[] = array('meta' => $row['supplier'] . ' ' . round($row['total'] / $summary['total'] * 100, 2) . '%', 'value' => $row['total']);
		}
$appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_suppliers_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/pie.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $summary,
			'show_currency' => TRUE
		);

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/graphical', $data);
	}

// 	//Graphical summary employees report
	public function graphical_summary_employees($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_employees();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$summary = $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)));
		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);
			$labels[] = $row['employee'];
			$series[] = array('meta' => $row['employee'] . ' ' . round($row['total'] / $summary['total'] * 100, 2) . '%', 'value' => $row['total']);
		}
        $appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_employees_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/pie.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $summary,
			'show_currency' => TRUE
		);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/graphical', $data);
	}

// 	//Graphical summary taxes report
	public function graphical_summary_taxes($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_taxes();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$summary = $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id)));
		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);
			$labels[] = $row['percent'];
			$series[] = array('meta' => $row['percent'] . ' ' . round($row['total'] / $summary['total'] * 100, 2) . '%', 'value' => $row['total']);
		}
$appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_taxes_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/pie.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $summary,
			'show_currency' => TRUE
		);

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());

		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);

		return view('reports/graphical', $data);
	}

// 	//Graphical summary customers report
	public function graphical_summary_customers($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_customers();;
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);

			$labels[] = $row['customer'];
			$series[] = $row['total'];
		}
		$appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_customers_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/hbar.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id))),
			'yaxis_title' => lang('reports_lang.reports_customers'),
			'xaxis_title' => lang('reports_lang.reports_revenue'),
			'show_currency' => TRUE
		);

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);

		return view('reports/graphical', $data);
	}

// 	//Graphical summary discounts report
	public function graphical_summary_discounts($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_discounts();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id));
		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);

			$labels[] = $row['discount_percent'];
			$series[] = $row['count'];
		}
        $appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_discounts_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/bar.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id))),
			'yaxis_title' => lang('reports_lang.reports_count'),
			'xaxis_title' => lang('reports_lang.reports_discount_percent'),
			'show_currency' => FALSE
		);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);

		return view('reports/graphical', $data);
	}

// 	//Graphical summary payments report
	public function graphical_summary_payments($start_date, $end_date, $sale_type, $location_id = 'all', $payment_type, $branch_code, $item_type, $item_id)
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Summary_payments();
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'item_id'=> $item_id ));
		$summary = $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'sale_type' => $sale_type, 'location_id' => $location_id,
		'payment_type' => $payment_type,'branch_code' => $branch_code,'item_type' => $item_type, 'item_id'=> $item_id   )));

		$labels = array();
		$series = array();
		foreach($report_data as $row)
		{
			$row = $this->xss_clean($row);

			$labels[] = $row['payment_type'];
			$series[] = array('meta' => $row['payment_type'] . ' ' . round($row['payment_amount'] / $summary['total'] * 100, 2) . '%', 'value' => $row['payment_amount']);
		}
        $appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_payments_summary_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'chart_type' => 'reports/graphs/pie.php',
			'labels_1' => $labels,
			'series_data_1' => $series,
			'summary_data_1' => $summary,
			'show_currency' => TRUE
		);

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/graphical', $data);
	}

	public function specific_customer_input()
	{
		
		$data =$this->data;
		$data['specific_input_name'] = lang('reports_lang.reports_customer');
        $Customer = new Customer();
		$customers = array();
		foreach($Customer->get_all()->getResult() as $customer)
		{		
			$customers[$customer->person_id] = $this->xss_clean($customer->first_name . ' ' . $customer->last_name);
		}
		$data['specific_input_data'] = $customers;

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		return view('reports/specific_input', $data);
	}

	public function specific_customer($start_date, $end_date, $customer_id, $sale_type)
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Specific_customer();
		$headers = $this->xss_clean($model->getDataColumns());
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'customer_id' => $customer_id, 'sale_type' => $sale_type));
		$summary_data = array();
		$details_data = array();
		foreach($report_data['summary'] as $key => $row)
		{
			$summary_data[] = $this->xss_clean(array(anchor('sales/receipt/'.$row['sale_id'], 'POS '.$row['sale_id'], array('target'=>'_blank')), $row['sale_date'], to_quantity_decimals($row['items_purchased']), $row['employee_name'], to_currency($row['subtotal']), to_currency($row['total']), to_currency($row['tax']), to_currency($row['cost']), to_currency($row['profit']), $row['payment_type'], $row['comment']));

			foreach($report_data['details'][$key] as $drow)
			{
				$details_data[$row['sale_id']][] = $this->xss_clean(array($drow['name'], $drow['category'], $drow['serialnumber'], $drow['description'], to_quantity_decimals($drow['quantity_purchased']), to_currency($drow['subtotal']), to_currency($drow['total']), to_currency($drow['tax']), to_currency($drow['cost']), to_currency($drow['profit']), $drow['discount_percent'].'%'));
			}
		}
        $Customer = new Customer();
		$customer_info = $Customer->get_info($customer_id);
		$appData = new Appconfig();
		$data = array(
			'title' => $this->xss_clean($customer_info->first_name . ' ' . $customer_info->last_name . ' ' . lang('reports_lang.reports_report')),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'headers' => $headers,
			'summary_data' => $summary_data,
			'details_data' => $details_data,
			'overall_summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'customer_id' => $customer_id, 'sale_type' => $sale_type)))
		);
		return view('reports/tabular_details', $data);
	}

	public function specific_employee_input()
	{
		
		$data = $this->data;
		$data['specific_input_name'] = lang('reports_lang.reports_employee');
		$employees = array();
		$Employee = new Employee();
		foreach($Employee->get_all()->getResult() as $employee)
		{
			$employees[$employee->person_id] = $this->xss_clean($employee->first_name . ' ' . $employee->last_name);
		}
		$data['specific_input_data'] = $employees;

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		return view('reports/specific_input', $data);
	}

	public function specific_employee($start_date, $end_date, $employee_id, $sale_type)
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Specific_employee();
		$headers = $this->xss_clean($model->getDataColumns());
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'employee_id' => $employee_id, 'sale_type' => $sale_type));
		$summary_data = array();
		$details_data = array();

		foreach($report_data['summary'] as $key => $row)
		{
			$summary_data[] = $this->xss_clean(array(anchor('sales/receipt/'.$row['sale_id'], 'POS '.$row['sale_id'], array('target'=>'_blank')), $row['sale_date'], to_quantity_decimals($row['items_purchased']), $row['customer_name'], to_currency($row['subtotal']), to_currency($row['total']), to_currency($row['tax']), to_currency($row['cost']), to_currency($row['profit']), $row['payment_type'], $row['comment']));

			foreach($report_data['details'][$key] as $drow)
			{
				$details_data[$row['sale_id']][] = $this->xss_clean(array($drow['name'], $drow['category'], $drow['serialnumber'], $drow['description'], to_quantity_decimals($drow['quantity_purchased']), to_currency($drow['subtotal']), to_currency($drow['total']), to_currency($drow['tax']), to_currency($drow['cost']), to_currency($drow['profit']), $drow['discount_percent'].'%'));
			}
		}
        $Employee = new Employee();
		$employee_info = $Employee->get_info($employee_id);
		$appData = new Appconfig();
		$data = array(
			'title' => $this->xss_clean($employee_info->first_name . ' ' . $employee_info->last_name . ' ' . lang('reports_lang.reports_report')),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'headers' => $headers,
			'summary_data' => $summary_data,
			'details_data' => $details_data,
			'overall_summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date,'employee_id' => $employee_id, 'sale_type' => $sale_type)))
		);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/tabular_details', $data);
	}

	public function specific_discount_input()
	{
		$data = $this->data;
		$data['specific_input_name'] = lang('reports_lang.reports_discount');

		$discounts = array();
		for ($i = 0; $i <= 100; $i += 10)
		{
			$discounts[$i] = $i . '%';
		}
		$data['specific_input_data'] = $discounts;
		
		$data = $this->xss_clean($data);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		return view('reports/specific_input', $data);
	}

	public function specific_discount($start_date, $end_date, $discount, $sale_type)
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Specific_discount();
		$headers = $this->xss_clean($model->getDataColumns());
		$report_data = $model->getData(array('start_date' => $start_date, 'end_date' => $end_date, 'discount' => $discount, 'sale_type' => $sale_type));

		$summary_data = array();
		$details_data = array();

		foreach($report_data['summary'] as $key => $row)
		{
			$summary_data[] = $this->xss_clean(array(anchor('sales/receipt/'.$row['sale_id'], 'POS '.$row['sale_id'], array('target'=>'_blank')),$row['name'], $row['sale_date'], to_quantity_decimals($row['items_purchased']), $row['customer_name'], to_currency($row['subtotal']), to_currency($row['total']), to_currency($row['tax']),/*to_currency($row['profit']),*/ $row['payment_type'], $row['comment']));

			foreach($report_data['details'][$key] as $drow)
			{
				$details_data[$row['sale_id']][] = $this->xss_clean(array($drow['name'], $drow['category'], $drow['serialnumber'], $drow['description'], to_quantity_decimals($drow['quantity_purchased']), to_currency($drow['subtotal']), to_currency($drow['total']), to_currency($drow['tax']),/*to_currency($drow['profit']),*/ $drow['discount_percent'].'%'));
			}
		}
        $appData = new Appconfig();
		$data = array(
			'title' => $discount . '% ' . lang('reports_lang.reports_discount') . ' ' . lang('reports_lang.reports_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'headers' => $headers,
			'summary_data' => $summary_data,
			'details_data' => $details_data,
			'overall_summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date,'discount' => $discount, 'sale_type' => $sale_type)))
		);

		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);
		return view('reports/tabular_details', $data);
	}

 	public function get_detailed_sales_row($sale_id)
	{
		// $this->load->model('reports/Detailed_sales');

		$model = new Detailed_sales();

		$report_data = $model->getDataBySaleId($sale_id);

		$summary_data = $this->xss_clean(array(
			'sale_id' => $report_data['sale_id'],
			'sale_date' => $report_data['sale_date'],
			'quantity' => to_quantity_decimals($report_data['items_purchased']),
			'employee' => $report_data['employee_name'],
			'customer' => $report_data['customer_name'],
			'subtotal' => to_currency($report_data['subtotal']),
			'total' => to_currency($report_data['total']),
			'tax' => to_currency($report_data['tax']),
			'cost' => to_currency($report_data['cost']),
			'profit' => to_currency($report_data['profit']),
			'payment_type' => $report_data['payment_type'],
			'comment' => $report_data['comment'],
			'edit' => anchor('sales/edit/'. $report_data['sale_id'], '<span class="glyphicon glyphicon-edit"></span>',
				array('class'=>'modal-dlg print_hide', 'data-btn-delete' => lang('common_lang.common_delete'), 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('sales_lang.sales_update'))
			)
		));

		echo json_encode(array($sale_id => $summary_data));
	}

	public function detailed_sales($start_date, $end_date, $sale_type, $location_id = 'all')
	{
        if(!$this->gu->checkStartDate($start_date)){
            echo "Please select the correct date";
            redirect('reports');
            return false;
        }
		$model = new Detailed_sales();
		$headers = $this->xss_clean($model->getDataColumns());
		$report_data = $model->getData(array(
		    'start_date' => $start_date,
            'end_date' => $end_date,
            'sale_type' => $sale_type,
            'location_id' => $location_id
            )
        );

		$summary_data = array();
		$details_data = array();
        $Stock_location = new Stock_location();
		$show_locations = $this->xss_clean($Stock_location->multiple_locations());

		foreach($report_data['summary'] as $key => $row)
		{
			$summary_data[] = $this->xss_clean(array(
				'id' => $row['sale_id'],
				'sale_type' => $row['sale_type'],
				'branch_code' => $row['branch_code'],
				'sale_date' => $row['sale_date'],
				'quantity' => to_quantity_decimals($row['items_purchased']),
				'employee' => $row['employee_name'],
				'customer' => $row['customer_name'],
				'subtotal' => to_currency($row['subtotal']),
				'total' => to_currency($row['total']),
				'tax' => to_currency($row['tax']),
				'cost' => to_currency($row['cost']),
				'profit' => to_currency($row['profit']),
				'payment_type' => $row['payment_type'],
				'comment' => $row['comment'],
				'edit' => anchor('sales/edit/'.$row['sale_id'], '<span class="glyphicon glyphicon-edit"></span>',
					array('class' => 'modal-dlg print_hide', 'data-btn-delete' => lang('common_lang.common_delete'), 'data-btn-submit' => lang('common_lang.common_submit'), 'title' => lang('sales_lang.sales_update'))
				)
			));

			foreach($report_data['details'][$key] as $drow)
			{
				$quantity_purchased = to_quantity_decimals($drow['quantity_purchased']);
				if($show_locations)
				{
					$quantity_purchased .= ' [' . $Stock_location->get_location_name($drow['item_location']) . ']';
				}
				$details_data[$row['sale_id']][] = $this->xss_clean(array($drow['name'], $drow['category'], $drow['serialnumber'], $drow['description'], $quantity_purchased, to_currency($drow['subtotal']), to_currency($drow['total']), to_currency($drow['tax']), to_currency($drow['cost']), to_currency($drow['profit']), $drow['discount_percent'].'%'));
			}
		}
        $appData = new Appconfig();
		$data = array(
			'title' => lang('reports_lang.reports_detailed_sales_report'),
			'subtitle' => date($appData->get('dateformat'), strtotime($start_date)) . '-' . date($appData->get('dateformat'), strtotime($end_date)),
			'headers' => $headers,
			'editable' => 'sales',
			'summary_data' => $summary_data,
			'details_data' => $details_data,
			'overall_summary_data' => $this->xss_clean($model->getSummaryData(array(
			    'start_date' => $start_date,
                'end_date' => $end_date,
                'sale_type' => $sale_type,
                'location_id' => $location_id)))
		);
		$data['empModel']=$this->employeeModel;
		$data['table_headers'] = $this->xss_clean(get_items_manage_table_headers());
		$data['stock_locations'] = $this->stockLocation->get_all()->getResultArray();
		$data['support_barcode'] = $this->barcode_lib->get_list_barcodes();
		$data['logo_exists'] = $this->appconfigModel->get('company_logo') != '';
		$data = array_merge($data, $this->data);

		return view('reports/tabular_details', $data);
	}
	public function inventoryi_input()
	{
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		$admin = $warehouse = $store = $vendor = false;
		// only admin has grant of config so check if logged in user is admin only if he has grant of config
		if($this->employeeModel->has_grant('config', $employee_id))
		{
			$admin = true;
		}
		// only warehouse has grant of reports_warehouse_stock so check if logged in user is warehouse only if he has grant of reports_warehouse_stock
		if($this->employeeModel->has_grant('reports_warehouse_stock', $employee_id))
		{
			$warehouse = true;
		}
		// only store has grant of reports_counter_stock so check if logged in user is store only if he has grant of reports_counter_stock
		if($this->employeeModel->has_grant('reports_counter_stock', $employee_id))
		{
			$store = true;
		}
		// only store has grant of reports_counter_stock so check if logged in user is store only if he has grant of reports_counter_stock
		if($this->employeeModel->has_grant('reports_vendor_stock', $employee_id))
		{
			$vendor = true;
		}
		// check which reprort to load and get warehouses or stores or counters or vendors
		if($this->exploder[1]=='warehouse'){
			$data = $this->getWarehouseReport($admin);
		}elseif($this->exploder[1]=='store'){
			$data = $this->getStoreReport($warehouse);
			$data['item_types'] = ['all' => 'All', 2=>'Warehouse Items', 3=>'Store Items', 1=>'Vendor Items'];
			$warehouseArray['all'] = 'All';
			$data['warehouses'] = $warehouseArray;
		}elseif($this->exploder[1]=='counter'){
			if($this->exploder[2]=='item'){
				$data['item_type'] = ['Received' => 'Received', 'Discard' => 'Discard', 'Delivered' => 'Delivered', 'Consumed' => 'Consumed'];
			}else{
				$data = $this->getCounterReport($admin, $store, $employee_id);
			}
		}elseif($this->exploder[1]=='vendor'){
			$data = $this->getVendorReport($admin, $vendor, $employee_id);
		}elseif($this->exploder[1]=='order'){
			$data = $this->getStoreReport($warehouse);
			$data['order_types'] = ['all' => 'All', 2=>'Warehouse', 3=>'Store', 1=>'Vendor'];
			$warehouseArray['all'] = 'All';
			$data['warehouses'] = $warehouseArray;
			$data['order_time'] = ['all' => 'All', 'morning' => 'Morning', 'evening' => 'Evening'];
		}else{
			$data['item_type'] = ['All' => 'All',  'Completed'=>'Completed', 'Rejected'=>'Rejected'];
		}
		$data = array_merge($data, $this->data);
		return view('stock_reports/date_input', $data);

	}

	public function inventoryi_counter_item($start_date, $end_date, $counter_id = 'all', $item_type = -1)
	{
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		$counter_detail = $this->employeeModel->get_company_name($employee_id,'counters');
		$counter_name = $counter_detail->company_name;
		$store_name = $this->employeeModel->get_company_name($counter_detail->store_id,'stores')->company_name;
		$title = '';
		if($item_type=='Delivered'){
			$title = lang('reports_lang.reports_inventory_counter_report');
			$model = new Inventory_deliver_item();
			$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'counter_id' => $employee_id, 'item_type' => $item_type));
            $tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['name'],
					$row['item_number'],
					$row['category'],
					$row['company_name'],
					to_quantity_decimals($row['quantity']), 
					to_currency($row['cost_price']),
					to_currency($row['sub_total_value']),
				));
			}

			$data = array(
				'title' => $title,
				'subtitle' => '',
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'start_date' => $start_date, 
				'end_date' => $end_date, 
				'company_name' => $counter_name,
				'store_name' => $store_name,
				'item_from_name' => 'Counter',
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'counter_id' => $counter_id)))
			);

		}elseif($item_type=='Received'){
			$title = lang('stock_reports_lang.reports_inventory_received_report');
			$model = new Inventory_received_item();
			$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'counter_id' => $employee_id, 'item_type' => $item_type));
            $tabular_data = array();
			foreach($report_data as $row)
			{
				if($cName = $this->employeeModel->get_company_name($row['store_id'],'stores')){
					$company_name = $cName->company_name;
				}elseif($cName = $this->employeeModel->get_company_name($row['store_id'],'counters')){
					$company_name = $cName->company_name;
				}

				$tabular_data[] = $this->xss_clean(array($row['name'],
					$row['item_number'],
					$row['category'],
					$company_name,
					to_quantity_decimals($row['quantity']), 
					to_currency($row['cost_price']),
					to_currency($row['sub_total_value']),
				));
			}

			$data = array(
				'title' => $title,
				'subtitle' => '',
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'start_date' => $start_date, 
				'end_date' => $end_date, 
				'company_name' => $counter_name,
				'store_name' => $store_name,
				'item_from_name' => 'Counter',
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'counter_id' => $counter_id)))
			);

		}elseif($item_type=='Discard'){
			$title = lang('reports_inventory_discard_report');
			$model = new Inventory_discard_item();
			$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'counter_id' => $employee_id, 'item_type' => $item_type));
            $tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['name'],
					$row['item_number'],
					$row['category'],
					to_quantity_decimals($row['trans_inventory']), 
					to_currency($row['cost_price']),
					to_currency($row['sub_total_value']),
					$row['discard_type'],
				));
			}

			$data = array(
				'title' => $title,
				'subtitle' => '',
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'start_date' => $start_date, 
				'end_date' => $end_date, 
				'company_name' => $counter_name,
				'store_name' => $store_name,
				'item_from_name' => 'Counter',
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'counter_id' => $counter_id)))
			);

		}elseif($item_type=='Consumed'){
			$title = lang('reports_lang.reports_inventory_consumed_report');
			$model = new Inventory_consumed_item();
			$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'counter_id' => $employee_id, 'item_type' => $item_type));
            $tabular_data = array();
			foreach($report_data as $row)
			{
				$tabular_data[] = $this->xss_clean(array($row['name'],
					$row['item_number'],
					$row['category'],
					to_quantity_decimals($row['quantity']), 
					to_currency($row['cost_price']),
					to_currency($row['sub_total_value']),
				));
			}
			$data = array(
				'title' => $title,
				'subtitle' => '',
				'headers' => $this->xss_clean($model->getDataColumns()),
				'data' => $tabular_data,
				'start_date' => $start_date, 
				'end_date' => $end_date, 
				'company_name' => $counter_name,
				'store_name' => $store_name,
				'item_from_name' => 'Counter',
				'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'counter_id' => $counter_id)))
			);

		}
			$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
			$data = array_merge($data, $this->data);
	    	return view('stock_reports/tabular', $data);
	}

	private function getWarehouseReport($admin=false)
	{
		if($admin){
			// get all warehouses only for admin
			$data = $this->xss_clean($this->Warehouse->get_all()->getResultArray());
			foreach ($data as $key => $value) {
				$stock_locations[$value['person_id']] = $value['company_name'];
			}
			$stock_locations['all'] = lang('reports_lang.reports_all');

			$data['stock_warehouses'] = array_reverse($stock_locations, TRUE);
		}else{
			$data['stock_warehouses'] = array();
		}

		return $data;
	}

	private function getStoreReport($warehouse=false)
	{
		if($warehouse){
			// get all stores only for warehouse
			$data = $this->xss_clean($this->Store->get_all()->getResultArray());
			foreach ($data as $key => $value) {
				$stock_locations[$value['person_id']] = $value['company_name'];
			}
			$stock_locations['all'] = lang('reports_all');

			$data['stock_stores'] = array_reverse($stock_locations, TRUE);
		}else{
			$data['stock_stores'] = array();
		}

		return $data;
	}

	private function getCounterReport($admin=false, $store=false, $store_id=-1)
	{
		if($admin){

			$stores = $this->xss_clean($this->Store->get_all()->getResultArray());
			foreach ($stores as $key => $value) {
				$stock_locations[$value['person_id']] = $value['company_name'];
			}
			$stock_locations['all'] = lang('reports_lang.reports_all');

			$data['stock_stores'] = array_reverse($stock_locations, TRUE);

			$stock_locations2['all'] = lang('reports_lang.reports_all');

			$data['stock_counters'] = array_reverse($stock_locations2, TRUE);
			
			$data['AjaxTrue'] = true;

		}else{
			
			if($store){
				// get all counters of specific store
				$data = $this->xss_clean($this->Counter->get_all_of_store($store_id)->getResultArray());
				foreach ($data as $key => $value) {
					$stock_locations[$value['person_id']] = $value['company_name'];
				}
				$stock_locations['all'] =  lang('reports_lang.reports_all');

				$data['stock_counters'] = array_reverse($stock_locations, TRUE);
			}else{
				$data['stock_counters'] = array();
			}
			$data['AjaxTrue'] = false;
		}
		return $data;
	}

	private function getVendorReport($admin=false, $vendor=false, $store_id=-1)
	{
		if($admin){

			$stores = $this->xss_clean($this->Store->get_all()->getResultArray());
			foreach ($stores as $key => $value) {
				$stock_locations[$value['person_id']] = $value['company_name'];
			}
			$stock_locations['all'] = lang('reports_lang.reports_all');

			$data['stock_stores'] = array_reverse($stock_locations, TRUE);

			$stock_locations2['all'] = lang('reports_lang.reports_all');

			$data['stock_vendors'] = array_reverse($stock_locations2, TRUE);
			
			$data['AjaxTrue2'] = true;

		}else{
			if($vendor){
				// get all vendor of specific store
				$data = $this->xss_clean($this->Vendor->get_all_of_store($store_id)->getResultArray());
				foreach ($data as $key => $value) {
					$stock_locations[$value['person_id']] = $value['company_name'];
				}
				$stock_locations['all'] = lang('reports_lang.reports_all');

				$data['stock_vendors'] = array_reverse($stock_locations, TRUE);
			}else{
				$data['stock_vendors'] = array();
				$data['AjaxTrue2'] = false;
			}
		}
		
		return $data;
	}

	public function inventoryi_pizza_stock($start_date, $end_date, $store_id = 'all', $item_type = 'All' , $item_from , $order_time, $branch_code)
	{
		$model = new Inventory_pizza();
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		// check if employee is pizza filling or pizza order 
		$counter_data = $this->Counter->get_info($employee_id);
		$counter_id = -1;
        if($this->gu->isServer()){
			$store_id = -1;
		}else{
			$store_id = $this->employeeModel->get_company_name($employee_id,'counters')->store_id;
		}
		// $store_name = $this->Employee->get_company_name($store_id,'stores')->company_name;
		$branch_name = $this->db->table('branches')->get()->getResult();
		$branch_code=='all' ? $branch = $branch_code : $branch = $branch_name[0]->name."-".$branch_code;
		// $counter_detail = $this->employeeModel->get_company_name($employee_id,'counters');
		// $store_name = $this->employeeModel->get_company_name($counter_detail->store_id,'stores')->company_name;
		$item_from_name = lang('stock_reports_lang.reports_counter');
		$company_name = 'All';
		if($counter_data->category==3){
			$counter_id = $employee_id;
			$store_id = -1;
			$counter_name = $this->employeeModel->get_company_name($counter_id,'counters')->company_name;
			$company_name = $counter_name;
		}
		$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'store_id' => $store_id, 'counter_id' => $counter_id, 'item_type' => $item_type, 'branch_code'=> $branch_code));
		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['name'],
				$row['item_number'],
				$row['size'],
				// to_quantity_decimals($row['quantity']), 
				to_quantity_decimals($row['qty']), 
				to_currency($row['cost_price']),
				to_currency($row['sub_total_value']),
				$row['order_status'],
			));
		}
		$data = array(
			'title' => lang('stock_reports_lang.reports_pizza_summary_report'),
			'subtitle' => '',
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'start_date' => $start_date, 
			'end_date' => $end_date, 
			'store_name' => $branch,
			'company_name' => $company_name,
			'item_from_name' => $item_from_name,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'store_id' => $store_id)))
		);
			$person = new Person();
			$data['person']= json_encode($person->get_all()->getResult());
			$data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
			$data = array_merge($data, $this->data);
		    return view('stock_reports/tabular', $data);
	}

	public function inventoryi_warehouse_stock($start_date, $end_date, $person_id = 'all')
	{
		$model = new Inventory_warehouse();
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		if ($person_id!=0 || $person_id=='all') {
			$warehouse_name = $person_id;
			if ($person_id!='all') {
				$warehouse_name = $this->employeeModel->get_company_name($person_id,'warehouses')->company_name;
			}
		}else{
			$person_id = $employee_id;
			$warehouse_name = $this->employeeModel->get_company_name($this->employeeModel->get_logged_in_employee_info()->person_id,'warehouses')->company_name;
		}
		$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'person_id' => $person_id));

		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['name'],
				$row['item_number'],
				$row['category'],
				$row['location_name'],
				to_quantity_decimals($row['quantity']), 
				to_currency($row['cost_price']),
				to_currency($row['sub_total_value']),
			));
		}
		$data = array(
			'title' => lang('reports_lang.reports_inventory_summary_report'),
			'subtitle' => '',
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'start_date' => $start_date, 
			'end_date' => $end_date, 
			'company_name' => $warehouse_name,
			'store_name' => $warehouse_name,
			'item_from_name' => 'Warehouse',
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'person_id' => $person_id)))
		);
		$data = array_merge($data, $this->data);
		return view('stock_reports/tabular', $data);
	}

	public function inventoryi_store_stock($start_date, $end_date, $store_id = 'all', $item_type = 2, $item_from = 'all')
	{
		$model = new Inventory_store();
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;

		if ($store_id!=0 || $store_id=='all') {
			$store_name = $store_id;
			if ($store_id!='all') {
				$store_name = $this->employeeModel->get_company_name($store_id,'stores')->company_name;
			}
		}else{
			$store_id = $employee_id;
			$store_name = $this->employeeModel->get_company_name($employee_id,'stores')->company_name;
		}
		$company_name = 'All';
		$item_from_name = lang('reports_lang.reports_items_from');
		
		if($item_type==1){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'vendors')->company_name;
			}
		}elseif($item_type==2){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'warehouses')->company_name;
			}
		}elseif($item_type==3){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'stores')->company_name;
			}
		}
		$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'store_id' => $store_id, 'item_type' => $item_type, 'item_from' => $item_from));
		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['name'],
				$row['item_number'],
				$row['category'],
				to_quantity_decimals($row['quantity']), 
				to_quantity_decimals($row['qty']), 
				to_currency($row['cost_price']),
				to_currency($row['sub_total_value']),
			));
		}
		$data = array(
			'title' => lang('reports_lang.reports_inventory_summary_report'),
			'subtitle' => '',
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'start_date' => $start_date, 
			'end_date' => $end_date, 
			'store_name' => $store_name,
			'company_name' => $company_name,
			'item_from_name' => $item_from_name,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'store_id' => $store_id)))
		);
		$data = array_merge($data, $this->data);
		return view('stock_reports/tabular', $data);
	}

	public function inventoryi_order_stock($start_date, $end_date, $store_id = 'all', $item_type = 2, $item_from = 'all', $order_time = 'all')
	{
		$data = $this->data;
		$model = new Inventory_order();
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;

		if ($store_id!=0 || $store_id=='all') {
			$store_name = $store_id;
			if ($store_id!='all') {
				$store_name = $this->employeeModel->get_company_name($store_id,'stores')->company_name;
			}
		}else{
			$store_id = $employee_id;
			$store_name = $this->employeeModel->get_company_name($employee_id,'stores')->company_name;
		}

		$company_name = 'All';
		$item_from_name = lang('reports_lang.reports_order_from');
		
		if($item_type==1){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'vendors')->company_name;
			}
		}elseif($item_type==2){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'warehouses')->company_name;
			}
		}elseif($item_type==3){
			if ($item_from!='all') {
				$company_name = $this->employeeModel->get_company_name($item_from,'stores')->company_name;
			}
		}

		$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'store_id' => $store_id, 'item_type' => $item_type, 'item_from' => $item_from, 'order_time' => $order_time));

		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['order_id'],
				($row['category']==1 || $row['category']==2) ? ($row['category']==1) ? 'Vendor' : 'Warehouse' : 'Store',
				$row['description'],
				to_quantity_decimals($row['order_quantity']), 
				to_quantity_decimals($row['receiving_quantity']), 
				ucwords($row['order_time']),
				$row['order_status'],
			));
		}


		$data = array(
			'title' => lang('reports_lang.reports_inventory_summary_report'),
			'subtitle' => '',
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'start_date' => $start_date, 
			'end_date' => $end_date, 
			'store_name' => $store_name,
			'company_name' => $company_name,
			'item_from_name' => $item_from_name,
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'store_id' => $store_id)))
		);
		$data = array_merge($data, $this->data);
		return view('stock_reports/tabular', $data);

	}


	public function inventoryi_counter_stock($start_date, $end_date, $store_id = 'all', $counter_id = 'all')
	{
		$model = new Inventory_counter();
		$employee_id = $this->employeeModel->get_logged_in_employee_info()->person_id;
		
		if($store_id==0){
			$store_id = $employee_id;
		}

		if ($counter_id!=0 || $counter_id=='all') {
			$counter_name = $counter_id;
			if ($counter_id!='all') {
				$counter_name = $this->employeeModel->get_company_name($counter_id,'counters')->company_name;
			}
		}else{
			$counter_id = $employee_id;
			$counter_name = isset($this->employeeModel->get_company_name($counter_id,'counters')->company_name)&&$this->employeeModel->get_company_name($counter_id,'counters')->company_name;
		}

		$store_name = isset($this->employeeModel->get_company_name($store_id,'stores')->company_name)&&$this->employeeModel->get_company_name($store_id,'stores')->company_name;

		$report_data = $model->getData(array('start_date' => $start_date,
            'end_date' => $end_date, 'store_id' => $store_id, 'counter_id' => $counter_id));

		$tabular_data = array();
		foreach($report_data as $row)
		{
			$tabular_data[] = $this->xss_clean(array($row['name'],
				$row['item_number'],
				$row['category'],
				$row['location_name'],
				to_quantity_decimals($row['quantity']), 
				to_currency($row['cost_price']),
				to_currency($row['sub_total_value']),
			));
		}

		$data = array(
			'title' => lang('reports_lang.reports_inventory_summary_report'),
			'subtitle' => '',
			'headers' => $this->xss_clean($model->getDataColumns()),
			'data' => $tabular_data,
			'start_date' => $start_date, 
			'end_date' => $end_date, 
			'company_name' => $counter_name,
			'store_name' => $store_name,
			'item_from_name' => 'Counter',
			'summary_data' => $this->xss_clean($model->getSummaryData(array('start_date' => $start_date, 'end_date' => $end_date, 'counter_id' => $counter_id)))
		);
		$data = array_merge($data, $this->data);
		return view('stock_reports/tabular', $data);
	}


}

?>