<?php

namespace App\Controllers;

use App\Controllers\Persons;
use App\Libraries\AppData;
use App\Libraries\Biometric;
use App\Libraries\Gu;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Person;
use CodeIgniter\Database\Database as DatabaseDatabase;
use Config\Database;

class Employees extends Persons
{
    protected $Employee;
    protected $CloudABIS_API_URL = 'https://bioplugin.cloudabis.com/v12/';
    protected $CloudABISAppKey = '58a9fa2fa73c43219fa5fba624fe02c4';
    protected $CloudABISSecretKey = '640611549E9D4D34B2E068DA29C4208F';
    protected $CloudABISCustomerKey = '640611549E9D4D34B2E068DA29C4208F';
    protected $ENGINE_NAME = 'FingerPrint';
    protected $appData;
    public function __construct()
    {
        parent::__construct('employees');
        $this->Employee = new Employee();
		$this->appData = new AppData();

    }

    public function index($module_id = null)
    {
        $this->employeeModel=new Employee();
        $this->module=new Module();
        $Person = new Person();
        $data = $this->data;
        $data['person']= json_encode($Person->get_all()->getResult());

         $data['table_headers'] = $this->xss_clean(get_people_manage_table_headers());
        return view('people/manage', $data);
    }


    function get_persons(){

          $Person = new Person();
          return $Person->get_all()->getResult();
}
    public function search()
    {

        $search = request()->getVar('search');
        $limit = request()->getVar('limit');
        $offset = request()->getVar('offset');
        $sort = request()->getVar('sort');
        $order = request()->getVar('order');
        $total_rows = 0;
        if($search=='')
        {
               $employees = $this->get_persons();
               $data_rows = [];
               foreach ($employees as $person) 
               {
                  $data_rows[] = get_person_data_row($person, $this);
                  $total_rows++;
                 }
        $data_rows = $this->xss_clean($data_rows);
        return json_encode(['total' => $total_rows, 'rows' => $data_rows]);

         }else{

        $employees = $this->Employee->search($search, $limit, $offset, $sort, $order);
        $total_rows = $this->Employee->get_found_rows($search);
        $data_rows = [];
        foreach ($employees->getResult() as $person) {
            $data_rows[] = get_person_data_row($person, $this);
        }
        $data_rows = $this->xss_clean($data_rows);
        return json_encode(['total' => $total_rows, 'rows' => $data_rows]);
    }
    }

    public function suggest_search()
    {
        $suggestions = $this->xss_clean($this->Employee->get_search_suggestions(request()->getGet('term')));

        return json_encode($suggestions);
    }

    public function view($employee_id = -1)
    {   $Module = new Module();
        $Employee = new Employee();
        $person_info = $Employee->get_info($employee_id);
        foreach (get_object_vars($person_info) as $property => $value) {
            $person_info->$property = $this->xss_clean($value);
        }
        $data['person_info'] = $person_info;

        $modules = array();
        foreach ($Module->get_all_modules()->getResult() as $module) {
            $module->module_id = $this->xss_clean($module->module_id);
            $module->grant = $this->xss_clean($Employee->has_grant($module->module_id, $person_info->person_id));

            $modules[] = $module;
        }

        $data['all_modules'] = $modules;


        $permissions = array();
        foreach ($Module->get_all_subpermissions()->getResult() as $permission) {
            $permission->module_id = $this->xss_clean($permission->module_id);
            $permission->permission_id = $this->xss_clean($permission->permission_id);
            $permission->grant = $this->xss_clean($Employee->has_grant($permission->permission_id, $person_info->person_id));

            $permissions[] = $permission;
        }
        $data['all_subpermissions'] = $permissions;

        $appData = $this->appData->getAppData();
        $data['appData'] = $appData;
        return view('employees/form', $data);
    }

    public function biometric($employee_id = -1)
    {
        $person_info = $this->Employee->get_info($employee_id);
        foreach (get_object_vars($person_info) as $property => $value) {
            $person_info->$property = $this->xss_clean($value);
        }
        $data['person_info'] = $person_info;

        return view('employees/biometric', $data);
    }

    public function save_biometric()
    {   $biometric = new \App\Libraries\Biometric();
        $token = session()->get('CloudABIS_accessToken');

        $_isRegistered = $biometric->isRegistered((object)request()->getPost(), $token);

        if ($_isRegistered[1]->operationResult == 'NO') {
            $result = $biometric->register((object)request()->getPost(), $token);
        } else {
            $result = $biometric->update((object)request()->getPost(), $token);
        }

        $employee_id = request()->getPost('id');

        return json_encode(['success' => true, 'message' => $result[1]->message, 'id' => $employee_id]);
    }

    public function save($employee_id = -1)
    {
        $person_data = [
            'first_name' =>request()->getPost('first_name'),
            'last_name' =>request()->getPost('last_name'),
            'gender' =>request()->getPost('gender'),
            'email' =>request()->getPost('email'),
            'phone_number' =>request()->getPost('phone_number'),
            'address_1' =>request()->getPost('address_1'),
            'address_2' =>request()->getPost('address_2'),
            'city' =>request()->getPost('city'),
            'state' =>request()->getPost('state'),
            'zip' =>request()->getPost('zip'),
            'country' =>request()->getPost('country'),
            'comments' =>request()->getPost('comments'),
        ];

        $grants_data = request()->getPost('grants') ? request()->getPost('grants') : [];
        if (request()->getPost('password') !== '') {
            $employee_data = [
                'username' => request()->getPost('username'),
                'password' => password_hash(request()->getVar('password'), PASSWORD_DEFAULT),
                'hash_version' => 2
            ];
        } else {
            $employee_data = ['username' => request()->getPost('username')];
        }

        if ($this->Employee->save_employee($person_data, $employee_data, $grants_data, $employee_id)) {
            $person_data = $this->xss_clean($person_data);
            $employee_data = $this->xss_clean($employee_data);

            if ($employee_id == -1) {
                return json_encode(['success' => true, 'message' => lang('employees_lang.employees_successful_adding') . ' ' . $person_data['first_name'] . ' ' . $person_data['last_name'], 'id' => $employee_data['person_id']]);
            } else {
                return json_encode(['success' => true, 'message' => lang('employees_lang.employees_successful_updating') . ' ' . $person_data['first_name'] . ' ' . $person_data['last_name'], 'id' => $employee_id]);
            }
        } else {
            $person_data = $this->xss_clean($person_data);

            return json_encode(['success' => false, 'message' => 'Username not available or ' . lang('employees_lang.employees_error_adding_updating') . ' ' . $person_data['first_name'] . ' ' . $person_data['last_name'], 'id' => -1]);
        }
    }

    public function delete()
    {
        $employees_to_delete = $this->xss_clean(request()->getPost('ids'));

        if ($this->Employee->delete_list($employees_to_delete)) {
            return json_encode(['success' => true, 'message' => lang('employees_lang.employees_successful_deleted') . ' ' . count($employees_to_delete) . ' ' . lang('employees_lang.employees_one_or_multiple')]);
        } else {
            return json_encode(['success' => false, 'message' => lang('employees_lang.employees_cannot_be_deleted')]);
        }
    }
}
