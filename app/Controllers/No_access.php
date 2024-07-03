<?php 
namespace App\Controllers;

use App\Models\Module;
use CodeIgniter\Controller;
use CodeIgniter\Security\Security;

class No_Access extends Controller 
{
	public function __construct()
	{
		// parent::__construct();
	}

	public function index($module_id = '', $permission_id = '')
	{
		$Module = new Module();
        $data['module_name']   = $Module->get_module_name($module_id);
		$data['permission_id'] = $permission_id;
		
		return view('no_access', $data);
	}


}
?>