<?php
namespace App\Models\apis;
use CodeIgniter\Model;

class Auth extends Model 
{
    function __construct()
    {
        parent::__construct();

    }

    /* Check Authenticated User Token */

    public function check_auth_user($token){
        
        $token = substr($token, 7);

        $data = $this->db->table('authentication')->select('expired_at')->like('token',$token)->get()->getRow();
        $now = date('Y-m-d H:i:s');
        if($data && $now<=$data->expired_at)
        {
            return true;
        } else {
            return false;
        }
    }

}
?>