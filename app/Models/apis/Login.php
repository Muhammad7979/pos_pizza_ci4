<?php
namespace App\Models\apis;

use CodeIgniter\Model;

class Login extends Model 
{
    function __construct()
    {
        parent::__construct();

    }

    public function logout_user($token)
    {
        $success = 0;
        
        $this->db->from('authentication');
        // $this->db->where('token', $token);
        $this->db->like('token', $token);
        $query = $this->db->get();

        if ($query->num_rows() > 0) 
        {
            $row = $query->row();
            $id =  $row->id;

            $this->db->delete('authentication', array('id' => $id));
            $this->db->delete('expoToken', array('token_id' => $id));
            $success = 1;

        }
        return $success;
    }
    public function login_user($username, $password)
    {
        $query = $this->db->join('people','people.person_id=employees.person_id')->join('counters','counters.person_id=employees.person_id')->get_where('employees', array('employees.username' => $username, 'employees.deleted' => 0), 1);

        if ($query->num_rows() == 1) 
        {
            $row = $query->row();

            $success = false;
            // compare passwords depending on the hash version
            if ($row->hash_version == 1 && $row->password == md5($password)) {
                $this->db->where('person_id', $row->person_id);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->update('employees', array('hash_version' => 2, 'password' => $password_hash));
                $success = TRUE;    
            } else if ($row->hash_version == 2 && password_verify($password, $row->password)) {
                $success = TRUE;
            }

            if($success)
            {
                //$modules = $this->Module->get_allowed_modules($row->person_id);
                $token = $this->_generate_key();
                $expired_at = date("Y-m-d H:i:s", strtotime('+30 days'));

                $token_id = $this->_createToken($row->person_id,$token,$expired_at);


                //Get Store Name
                $store_name = $this->get_store_name($row->store_id,'stores');

                $data['status'] = 'Success'; 
                $data['statusCode'] = '1'; 
                $data['message'] = 'Login Successfull.'; 
                $data['count'] = $query->num_rows(); 
                $data['user'] = (object) [
                    'id' => $row->person_id,
                    'username' => $row->username,
                    'firstname' => $row->first_name,
                    'lastname' => $row->last_name,
                    'store_id' => $row->store_id,
                    'store_name' => $store_name->company_name,
                    'category' => $row->category,
                    'category_name' => ($row->category==1) ? 'Counter' : 'Production',
                    'token_id' => $token_id,
                    'accessToken' => $token,
                ];

            }
            else
            {
                $data['status'] = 'Success'; 
                $data['statusCode'] = '4'; 
                $data['message'] = 'Invalid Password.'; 
                $data['count'] = '0'; 
                $data['user'] = (object) [];
            }
        }
        else
        {
            $data['status'] = 'Success'; 
            $data['statusCode'] = '4'; 
            $data['message'] = 'Invalid Username.'; 
            $data['count'] = '0'; 
            $data['user'] = (object) [];
        }

        return $data;
    }

    /* Check Authenticated User Token */

    public function check_auth_user($token){

        $data = $this->db->select('expired_at')->from('authentication')->where('token',$token)->get()->row();
        $now = date('Y-m-d H:i:s');
        if($data && $now<=$data->expired_at)
        {
            return true;
        } else {
            return false;
        }
    }

    /* Create/Update Token in Authentication Table */

    private function _createToken($id,$token,$expired_at)
    {
        $this->db->insert('authentication',array('user_id' => $id,'token' => $token,'expired_at' => $expired_at));

        return $this->db->insert_id();

    }

    /* Create Token */

    private function _generate_key()
    {
        do
        {
            if (function_exists('mcrypt_create_iv')) {
                $randomData = mcrypt_create_iv(512, MCRYPT_DEV_URANDOM);
                if ($randomData !== false && strlen($randomData) === 512) {
                    $new_key = bin2hex($randomData);
                }
            }elseif (function_exists('openssl_random_pseudo_bytes')) {
                $randomData = openssl_random_pseudo_bytes(512);
                if ($randomData !== false && strlen($randomData) === 512) {
                    $new_key = bin2hex($randomData);
                }
            }else{
                $randomData = random_bytes(512);
                if ($randomData !== false && strlen($randomData) === 512) {
                    $new_key = bin2hex($randomData);
                }
            }

        }
        while ($this->_key_exists($new_key));

        return $new_key;
    }

    /* Match for duplicate key */

    private function _key_exists($key)
    {
        return $this->db
            ->where('token', $key)
            ->count_all_results('authentication') > 0;
    }


    /*
    Gets company name rows for a particular order
    */
    private function get_store_name($person_id,$table)
    {
        $this->db->from($table);
        $this->db->where('person_id', $person_id);
        
        //return an array of order items for an item
        return $this->db->get()->row();
    }
    

    /* Create/Update Expo Token in ExpoToken Table */

    public function NewDevice($id,$token,$token_id)
    {
        return $this->db->insert('expoToken',array('user_id' => $id,'token_id' => $token_id,'token' => $token));
    }

}
?>