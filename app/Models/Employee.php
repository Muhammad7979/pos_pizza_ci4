<?php
namespace App\Models;

use App\Models\Person;
use App\Libraries\Gu;
use CodeIgniter\Database\Database as DatabaseDatabase;
use Config\Database;
use Config\Services;

class Employee extends Person
{
    protected $table = 'ospos_employees';
    protected $gu;
    public function __construct()
    {
        parent::__construct();

        // helper('session');
        // $this->db = \Config\Database::connect();// Load the database
        $this->gu = new Gu();

    }

    /*
    Determines if a given person_id is an employee
    */
    public function exists($person_id)
    {
       $builder = $this->db->table('employees')
                 ->join('people', 'people.person_id = employees.person_id')
                 ->where('employees.person_id', $person_id);

        return ($builder->get()->getNumRows() == 1);
    }

    public function onlineExists($person_id)
    {
        // $online = $this->load->database('online', TRUE);
        $online = Database::connect('online');

        $builder =  $online->table('employees')
                          ->join('people', 'people.person_id = employees.person_id')
                          ->where('employees.person_id', $person_id);

        return ($builder->get()->getNumRows() == 1);
    }

    /*
    Gets total of rows
    */
    public function get_total_rows()
    {
        $builder = $this->db->table($this->table)
            ->where('deleted', 0);

        return $builder->countAllResults();
    }

    /*
    Returns all the employees
    */
    public function get_all($limit = 10000, $offset = 0)
    {
        $builder = $this->db->table($this->table)
            ->where('deleted', 0)
            ->join('people', 'employees.person_id = people.person_id')
            ->orderBy('last_name', 'asc')
            ->limit($limit)
            ->offset($offset);

        return $builder->get();
    }

    /*
    Gets information about a particular employee
    */
    public function get_info($employee_id)
    {
        $builder = $this->db->table($this->table)
            ->join('people', 'people.person_id = employees.person_id')
            ->where('employees.person_id', $employee_id)
            ->get();

        if ($builder->getNumRows() == 1) {
            return $builder->getRow();
        } else {
            //Get empty base parent object, as $employee_id is NOT an employee
            $person_obj = parent::get_info(-1);

            //Get all the fields from employee table
            //append those fields to base parent object, we we have a complete empty object
            foreach ($this->db->getFieldNames($this->table) as $field) {
                $person_obj->$field = '';
            }

            return $person_obj;
        }
    }

    /*
    Gets information about multiple employees
    */
    public function get_multiple_info($employee_ids)
    {
        $builder = $this->db->table($this->table)
            ->join('people', 'people.person_id = employees.person_id')
            ->whereIn('employees.person_id', $employee_ids)
            ->orderBy('last_name', 'asc')
            ->get();
        return $builder;
    }

    /*
    Inserts or updates an employee
    */

    public function save_employee(&$person_data, &$employee_data, &$grants_data, $employee_id = FALSE)
    {
        $online = Database::connect('online');
        $online->initialize();

        $isOnlineServer = $this->gu->isServer();

        if (FALSE === $online->connID) {
            return FALSE;
        }

        $success = FALSE;

        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();
        if (!$isOnlineServer) {
            $online->transStart();
        }

        if (parent::savePerson($person_data, $employee_id)) {
            if ($employee_id) {
                //check if username already taken by another employee

                if (!$isOnlineServer) {
                    $builder = $online->table('employees')
                                          ->where('employees.username', $employee_data['username'])
                                          ->where('employees.person_id !=', $employee_id);
                    $online_rows = $builder->get()->getNumRows();
                } else {
                    $online_rows = 0;
                }


               $builder = $this->db->table('employees')
                                   ->where('employees.username', $employee_data['username'])
                                   ->where('employees.person_id !=', $employee_id);

                $local_rows = $builder->get()->getNumRows();

                //if username already taken by someone else
                if ($online_rows || $local_rows) {
                    return false;
                }
            }


            //create new
            if (!$employee_id || !$this->onlineExists($employee_id)) {
                if (!$employee_id || !$this->exists($employee_id)) {

                    $employee_data['person_id'] = $employee_id = $person_data['person_id'];

                    if (!$isOnlineServer) {
                        $success = $online->table('employees')->insert($employee_data);
                    } else {
                        $success = true;
                    }
                    if ($success) {
                        $success = $this->db->table('employees')->insert($employee_data);
                    } else {
                        $success = false;
                    }


                } else {
                    $builder = $this->db->table('employees')->where('person_id', $employee_id);
                    $success = $$builder->update( $employee_data);
                }
            } else {
                //update existing
                //update local
                $builder = $this->db->table('employees')->where('person_id', $employee_id);
                $success = $builder->update($employee_data);

                if (!$isOnlineServer) {
                    //update online
                   $builder =  $online->table('employees')->where('person_id', $employee_id);
                    $success = $builder->update($employee_data);
                }


            }


            //We have either inserted or updated a new employee, now lets set permissions.
            if ($success) {
                //First lets clear out any grants the employee currently has.

                if (!$isOnlineServer) {
                    $success = $online->table('grants')->delete(array('person_id' => $employee_id));
                } else {
                    $success = true;
                }

                if ($success) {
                    $success = $this->db->table('grants')->delete(array('person_id' => $employee_id));
                } else {
                    $success = false;
                }

                //Now insert the new grants
                if ($success) {
                    foreach ($grants_data as $permission_id) {
                        //save new permissions to online server
                        if (!$isOnlineServer) {
                            $success = $online->table('grants')->insert(array(
                                'permission_id' => $permission_id,
                                'person_id' => $employee_id
                            ));
                        } else {
                            $success = true;
                        }

                        //save new permissions to local
                        if ($success) {
                            $success = $this->db->table('grants')->insert(array(
                                'permission_id' => $permission_id,
                                'person_id' => $employee_id
                            ));
                        } else {
                            $success = false;
                        }

                    }
                }
            }
        }

        $this->db->transComplete();
        if (!$isOnlineServer) {
            $online->transComplete();
        }

        $success &= $this->db->transStatus();
        if (!$isOnlineServer) {
            $success &= $online->transStatus();
        }

        return $success;
    }
    /*
    Deletes one employee
    */
    // public function delete($employee_id)
    // {
    //     $success = FALSE;

    //     //Don't let employees delete theirself
    //     if ($employee_id == $this->get_logged_in_employee_info()->person_id) {
    //         return FALSE;
    //     }

    //     //Run these queries as a transaction, we want to make sure we do all or nothing
    //     $this->db->trans_start();

    //     //Delete permissions
    //     if ($this->db->delete('grants', array('person_id' => $employee_id))) {
    //         $this->db->where('person_id', $employee_id);
    //         $success = $this->db->update('employees', array('deleted' => 1));
    //     }

    //     $this->db->trans_complete();

    //     return $success;
    // }

    /*
    Deletes a list of employees
    */
    public function delete_list($employee_ids)
    {
        $success = FALSE;
        //Don't let employees delete theirself
        if (in_array($this->get_logged_in_employee_info()->person_id, $employee_ids)) {
            return FALSE;
        }

        //Run these queries as a transaction, we want to make sure we do all or nothing
        $this->db->transStart();
        $builder = $this->db->table('ospos_grants')
            ->whereIn('person_id', $employee_ids);
        //Delete permissions
        // $grants = $this->db->table('ospos_grants');
        if ($builder->delete()) {
            //delete from employee table
            $employees = $this->db->table($this->table)
                ->whereIn('person_id', $employee_ids);
            $success = $employees->update(array('deleted' => 1));
        }

        $this->db->transComplete();

        return $success;
    }

    /*
    Get search suggestions to find employees
    */
    public function get_search_suggestions($search, $limit = 5)
    {
        $suggestions = array();

        $builder = $this->db->table($this->table)
            ->join('people', 'employees.person_id = people.person_id')
            ->groupStart()
            ->like('first_name', $search)
            ->orLike('last_name', $search)
            ->orLike('CONCAT(first_name, " ", last_name)', $search)
            ->groupEnd()
            ->where('deleted', 0)
            ->orderBy('last_name', 'asc');
        foreach ($builder->get()->getResultArray() as $row) {
            $suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name);
        }

        $builder = $this->db->table($this->table)
            ->join('people', 'employees.person_id = people.person_id')
            ->where('deleted', 0)
            ->like('email', $search)
            ->orderBy('email', 'asc');
        foreach ($builder->get()->getResultArray() as $row) {
            $suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
        }

        $builder = $this->db->table($this->table)
            ->join('people', 'employees.person_id = people.person_id')
            ->where('deleted', 0)
            ->like('username', $search)
            ->orderBy('username', 'asc');
        foreach ($builder->get()->getResultArray() as $row) {
            $suggestions[] = array('value' => $row->person_id, 'label' => $row->username);
        }

        $builder = $this->db->table($this->table)
            ->join('people', 'employees.person_id = people.person_id')
            ->where('deleted', 0)
            ->like('phone_number', $search)
            ->orderBy('phone_number', 'asc');
        foreach ($builder->get()->getResultArray() as $row) {
            $suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
        }

        //only return $limit suggestions
        if (count($suggestions) > $limit) {
            $suggestions = array_slice($suggestions, 0, $limit);
        }

        return $suggestions;
    }

    /*
   Gets rows
   */
    public function get_found_rows($search)
    {
        $builder = $this->db->table($this->table)
            ->join('people', 'ospos_employees.person_id = people.person_id')
            ->groupStart()
            ->like('first_name', $search)
            ->orLike('last_name', $search)
            ->orLike('email', $search)
            ->orLike('phone_number', $search)
            ->orLike('username', $search)
            ->orLike('CONCAT(first_name, " ", last_name)', $search)
            ->groupEnd()
            ->where('ospos_employees.deleted', 0);

        return $builder->get()->getNumRows();
    }

    /*
    Performs a search on employees
    */
    // public function search($search, $rows = 0, $limit_from = 0, $sort='last_name', $order = 'asc')
    // {
    //   $builder = $this->db->table($this->table)
    //              ->join('people', 'employees.person_id = people.person_id')
    //              ->groupStart()
    //              ->like('first_name', $search)
    //              ->orLike('last_name', $search)
    //              ->orLike('email', $search)
    //              ->orLike('phone_number', $search)
    //              ->orLike('username', $search)
    //              ->orLike('CONCAT(first_name, " ", last_name)', $search)
    //              ->groupEnd()
    //              ->orderBy($sort, $order)
    //              ->where('deleted', 0);

    //     if ($rows > 0) {
    //         $builder->limit($rows, $limit_from);
    //     }

    //     return $builder->get();
    // }
    public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc')
    {

        $builder = $this->db->table($this->table);
        $builder->join('people', 'employees.person_id = people.person_id');
        $builder->groupStart();
        $builder->like('first_name', $search);
        $builder->orLike('last_name', $search);
        $builder->orLike('email', $search);
        $builder->orLike('phone_number', $search);
        $builder->orLike('username', $search);
        $builder->orLike("CONCAT(first_name, ' ', last_name)", $search);
        $builder->groupEnd();
        $builder->where('deleted', 0);
        $builder->orderBy('last_name', $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();

    }


    //     public function search($search, $rows, $limit_from = 1, $sort = 'last_name', $order = 'asc')
// {
//     $builder = $this->db->table($this->table)
//         ->join('people', 'employees.person_id = people.person_id')
//         ->where(function ($builder) use ($search) {
//             $builder->like('first_name', $search)
//                 ->orLike('last_name', $search)
//                 ->orLike('email', $search)
//                 ->orLike('phone_number', $search)
//                 ->orLike('username', $search)
//                 ->orLike("CONCAT(first_name, ' ', last_name)", $search);
//         })
//         ->where('deleted', 0)
//         ->orderBy($sort, $order);

    //     if ($rows > 0) {
//         $builder->limit($rows, $limit_from);
//     }

    //     return $builder->get();
// }


    /*
    Attempts to login employee and set session. Returns boolean based on outcome.
    */
    public function login($username, $password)
    {

        // $builder = $this->db->get_where('employees', array('username' => $username, 'deleted' => 0), 1);
        $builder = $this->db->table($this->table)
            ->where('username', $username)
            ->where('deleted', 0)
            ->limit(1)
            ->get();

        if ($builder->getNumRows() == 1) {
            $row = $builder->getRow();

            // compare passwords depending on the hash version
            if ($row->hash_version == 1 && $row->password == md5($password)) {
                $builder = $this->db->table($this->table);
                $builder->where('person_id', $row->person_id);
                session()->set('person_id', $row->person_id);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                return $builder->update(array('hash_version' => 2, 'password' => $password_hash));
            } else if ($row->hash_version == 2 && password_verify($password, $row->password)) {
                session()->set('person_id', $row->person_id);
                return TRUE;
            }

        }

        return FALSE;
    }

    /*
    Attempts to login employee and set session. Returns boolean based on outcome.
    */
    public function biometric_login($username, $password)
    {
        // $query = $this->db->get_where('employees', array('username' => $username, 'deleted' => 0), 1);
        $builder = $this->db->table('employees')
            ->where('username', $username)
            ->where('deleted', 0)
            ->limit(1)
            ->get();

        if ($builder->getNumRows() == 1) {
            $row = $builder->getRow();

            // compare passwords depending on the hash version
            if ($row->hash_version == 1) {
                $builder = $this->db->table('employees')
                    ->where('person_id', $row->person_id);
                $session = session();
                $session->setTempdata('person_id', $row->person_id, 900);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                return $builder->update(array('hash_version' => 2, 'password' => $password_hash));
            } else if ($row->hash_version == 2) {
                $session = session();
                $session->setTempdata('person_id', $row->person_id, 900);

                return TRUE;
            }

        }

        return FALSE;
    }

    /*
    Logs out a user by destorying all session data and redirect to login
    */
    public function logout()
    {
        $session = session();
        $session->destroy();
        return True;
    }


    /*
    Determins if a employee is logged in
    */
    public function is_logged_in()
    {
        return session()->has('person_id');
    }

    /*
    Gets information about the currently logged in employee.
    */
    public function get_logged_in_employee_info()
    {
        if ($this->is_logged_in()) {

            return $this->get_info(session()->get('person_id'));
        }

        return FALSE;
    }

    /*
    Determines whether the employee has access to at least one submodule
     */
    public function has_module_grant($permission_id, $person_id)
    {
        $grants = $this->db->table('ospos_grants')
            ->like('permission_id', $permission_id . '%', 'after')
            ->where('person_id', $person_id);
        $result_count = $grants->countAllResults();
        if ($result_count != 1) {
            return ($result_count != 0);
        }

        return $this->has_subpermissions($permission_id);
    }


    /*
   Checks permissions
   */
    public function has_subpermissions($permission_id)
    {
        $permission = $this->db->table('ospos_permissions')
            ->like('permission_id', $permission_id . '%', 'after');
        $result_count = $permission->countAllResults();

        return ($result_count != 0);
    }


    /*
    Determines whether the employee specified employee has access the specific module.
    */
    public function has_grant($permission_id, $person_id)
    {
        //if no module_id is null, allow access
        if ($permission_id == null) {
            return TRUE;
        }
        $builder = $this->db->table('grants')
            ->getWhere(
                ['person_id' => $person_id, 'permission_id' => $permission_id],
                1
            );
        return ($builder->getNumRows() == 1);
    }

    /*
   Gets employee permission grants
   */
    public function get_employee_grants($person_id)
    {
        $grant = $this->db->table('ospos_grants')

            ->where('person_id', $person_id);

        return $grant->get()->getResultArray();
    }


    public function get_company_name($person_id, $table)
    {
        $builder = $this->db->table($table)
            ->where('person_id', $person_id);

        //return an array of order items for an item
        return $builder->get()->getRow();
    }
}






?>