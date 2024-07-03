<?php

namespace App\Models;

use CodeIgniter\Model;

class Appconfig extends Model
{
	protected $table = 'ospos_app_config';
	protected $primaryKey = 'id';
	protected $allowedFields = ['key', 'value'];
	public function __construct()
    {
        parent::__construct();

        // helper('session');
        //   $this->db = \Config\Database::connect();// Load the database
       
    }


	public function exists($key)
	{
		return $this->where('key', $key)->countAllResults() === 1;
	}

	// public function get_all()
	// {
	// 	return $this->orderBy('key', 'asc')->findAll();
	// }
	public function get_all()
    {
        $results = $this->orderBy('key', 'asc')->findAll();

        $data = [];
        foreach ($results as $row) {
            $data[$row['key']] = $row['value'];
        }

        return $data;
    }

	public function get($key)
	{
		$result = $this->where('key', $key)->first();

		if ($result) {
			return $result['value'];
		}

		return false;
	}
	public function save_appconfig($key, $value)
	{
        $config_data = array(
			'key'   => $key,
			'value' => $value
		);

		if(!$this->exists($key))
		{
			$builder = $this->db->table($this->table);
			       return $builder->insert($config_data);
			// return $this->db->insert('app_config', $config_data);
		}

		$builder = $this->db->table($this->table)
							->where('key',$key);
		return $builder->update($config_data);
	}

public function batch_save($data)
	{
		$this->db->transStart();

		foreach ($data as $key => $value) {
			$this->save_appconfig($key, $value);
		}

		$this->db->transComplete();

		return $this->db->transStatus();
	}



	public function delete($id = null, bool $purge = false)
{
    if (is_numeric($id)) {
        return $this->where('id', $id)->delete();
    }

    if (is_string($id)) {
        return $this->where('key', $id)->delete();
    }

    return false;
}


	public function delete_all()
	{
		return $this->emptyTable($this->table);
	}
}
