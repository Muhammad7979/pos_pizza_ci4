<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Libraries\ModuleLib;
use App\Controllers\SecureController;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Appconfig;
use App\Models\Notification;

/**
 * @filter auth
 */
class Home extends SecureController
{
    protected $db;
    protected $appData;
    protected $module;
    protected $Employee;
    protected $Appconfig;

    public function __construct()
    {
        parent::__construct();
        $this->Employee = new Employee();
        $this->Appconfig = new Appconfig();
    }

    public function index($module_id = null)
    {
        $data = $this->data;
        return view('home', $data);
    }


    public function logout()
    {
        if ($this->Employee->logout()) {
            return redirect()->to('/');
        }

    }
}