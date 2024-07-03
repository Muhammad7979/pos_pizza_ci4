<?php  
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;


class Person extends Model
{
    // protected $db;
    protected $table = 'ospos_people';

    public function __construct() {
        parent::__construct();

    
        // $this->db = \Config\Database::connect();
       }



    public function dbexists($person_id)
{
    // $prefix = $this->db->prefixTable();
    // $tableName = $prefix . 'people';
    // $builder = $this->db->table($tableName);
    $builder = $this->db->table('people');
    // $builder->select('*');
    $builder->where('person_id', $person_id);
    $result = $builder->get()->getResult('array');
    return (count($result) == 1);
}
public function online_Exists($person_id)
	{
        
        $onlineConn = Database::connect('online');
		// $online = $this->load->database('online', TRUE);
        $online = $onlineConn->table('people');
		$online->where('person_id', $person_id);
		return ($online->get()->getNumRows() == 1);
	}

public function get_all($limit = 10000, $offset = 0)
{
    
    // $builder = $this->db->table($this->table);
    // $builder->orderBy('last_name', 'asc');
    // $builder->limit($limit, $offset);
    $builder = $this->db->table($this->table)
	                    ->join('employees', 'people.person_id = employees.person_id')
	                    ->where('deleted', 0)
		                ->orderBy('last_name', 'asc')
		                ->limit($limit)
		                ->offset($offset);


    return $builder->get();
}

public function get_info($person_id)
{
    //  $prefix = $this->db->prefi();
    // $tableName = $prefix . 'people';
    // $builder = $this->db->table($tableName);

    $builder = $this->db->table($this->table);
    $builder->where('person_id', $person_id);
    $builder->limit(1);

    $query = $builder->get();

    if ($query->getNumRows() == 1) {
        return $query->getRow();
    } else {
        // Create object with empty properties.
        $person_obj = new Person();

        foreach ($this->db->getFieldNames($this->table) as $field) {
            $person_obj->$field = '';
        }
        return $person_obj;
    }
}


	public function savePerson(&$person_data, $person_id = FALSE)
	{
		$online = Database::connect('online');
		$online->initialize();
		//$local = $this->load->database('default', TRUE);

		if (FALSE === $online->connID) {
			return FALSE;
		}

		if(!$person_id || !$this->onlineExists($person_id))
		{
			//delete local person if already exists
			if($this->exists($person_id))
			{
			    $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
				$this->db->table('people')->where('person_id',$person_id)->delete();
				$this->db->table('employees')->where('person_id',$person_id)->delete();
				$this->db->query("SET FOREIGN_KEY_CHECKS = 0");
			}


			if(!$person_id || !$this->exists($person_id))
			{
				if($online->table('people')->insert($person_data))
				{
					$person_data['person_id'] = $online->insertID();

					if($this->db->table('people')->insert($person_data))
					{
						return TRUE;
					}
					return FALSE;
				}

			}


		}

		//for update
		$updated = false;

		$builder_online_db = $online->table('people')->where('person_id', $person_id);

		if($builder_online_db->update($person_data))
		{
			$builder_local_db = $this->db->table('people')->where('person_id', $person_id);
			$updated = $builder_local_db->update($person_data);
		}

		return $updated;
	}
	
    public function onlineExists($person_id)
    {
        $online = Database::connect('online');
        $builder = $online->table('people')
		                  ->where('people.person_id', $person_id);

		return ($builder->get()->getNumRows() == 1);
    }
	public function exists($person_id)
	{
		$builder = $this->db->table('people')	
		                    ->where('people.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}
    
}




