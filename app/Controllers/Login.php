<?php
namespace App\Controllers;

use App\Models\Employee;
use Config\Services;
class Login extends BaseController
{
    protected $Employee;
    protected $validation;

    public function __construct()
    {    
        $this->Employee = new Employee();
        $this->validation = Services::validation();
    }

    public function index()
    {
     if($this->Employee->is_logged_in())
     {
        return redirect()->to('/home');
     }
    if ($this->request->is('post')) 
    {
        $this->validation->setRules([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!$this->validation->withRequest($this->request)->run()) {
            $errors = $this->validation->getErrors();
            $data['errors']=$errors;
            return view('login',$data);
        } else {
            $result = $this->login_check();
            if ($result) {
                return redirect()->to('/home');
            } else {
                $errors = $this->validation->getErrors();
                $data['errors']=$errors;
                return view('login', $data);

            }
        }
    } else {
        if ($this->Employee->is_logged_in()) {
            return redirect()->to('/home'); 
        }
        else
        {
            $data['errors']=[];
            return view('login',$data);
        }
       
    }
    }


    public function login_check()
    {
        $password = $this->request->getPost('password');
        $username = $this->request->getPost('username');

        if (!$this->Employee->login($username, $password)) {
            $this->validation->setError('login_check', lang('login_lang.login_invalid_username_and_password'));
            return false;
        }

        return true;
    }


}