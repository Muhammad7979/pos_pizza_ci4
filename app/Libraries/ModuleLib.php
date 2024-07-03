<?php
namespace App\Libraries;
use App\Models\Employee;
use App\Models\Module;

class ModuleLib{
    public $employeeModel;
    public $module;
    public $allowed_modules;
    public function __construct()
	{
        

        $this->employeeModel=new Employee();
        $this->module=new Module();
		
	}

    public function checkmodule($module_id = NULL, $submodule_id = NULL){
        if(!$this->employeeModel->is_logged_in())
		{
			return redirect()->route('/');
		}

//		$this->track_page($module_id, $module_id);
		
		$logged_in_employee_info = $this->employeeModel->get_logged_in_employee_info();
		if(!$this->employeeModel->has_module_grant($module_id, $logged_in_employee_info->person_id) || 
			(isset($submodule_id) && !$this->employeeModel->has_module_grant($submodule_id, $logged_in_employee_info->person_id)))
		{
			redirect('no_access/' . $module_id . '/' . $submodule_id);
		}
        
		//  up global data visible to all the loaded views
		$this->allowed_modules = $this->module->get_allowed_modules($logged_in_employee_info->person_id);
	

        return $this->allowed_modules; 

    }

}