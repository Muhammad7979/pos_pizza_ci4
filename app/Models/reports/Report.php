<?php
namespace App\Models\reports;

use App\Libraries\Gu;
use App\Models\Item;
use App\Models\Sale;
use CodeIgniter\Model;
abstract class Report extends Model 
{
	function __construct()
	{
		// parent::__construct();
		

//        $online = $this->load->database('online', TRUE);
//        echo $online->hostname;

		// Assuming you are inside a controller method

// Prevent caching in the browser
response()->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
response()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
response()->setHeader('Cache-Control', 'post-check=0, pre-check=0', false);
response()->setHeader('Pragma', 'no-cache');

        $gu = new Gu();
        $this->db = $gu->getReportingDb();

		//Create our temp tables to work with the data in our report
		//$this->Sale->create_temp_table();
		$Sale = new Sale();
		$Item = new Item();
		// dd($this->db);
        $Sale->create_temp_table($this->db);
        //$this->Receiving->create_temp_table();
		$Item->create_temp_table();

	}
	
	//Returns the column names used for the report
	public abstract function getDataColumns();
	
	//Returns all the data to be populated into the report
	public abstract function getData(array $inputs);
	
	//Returns key=>value pairing of summary data for the report
	public abstract function getSummaryData(array $inputs);
}
?>