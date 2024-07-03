<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Libraries\Gu;
use Config\Database;
use stdClass;

class Giftcard extends Model
{
	/*
	Determines if a given giftcard_id is an giftcard
	*/

	protected $table = 'ospos_giftcards';
	protected $primaryKey = 'giftcard_id';
	protected $allowedFields = ['giftcard_id', 'giftcard_number','value','status','deleted','person_id','record_time','expires_at'];
    public function __construct()
    {
		helper('session');
		$this->db = \Config\Database::connect();// Load the database
	$this->gu = new Gu(); 
    }

	public function exists($giftcard_id)
	{
		$builder= $this->db->table($this->table)
	                       ->where('giftcard_id', $giftcard_id) 
		                   ->where('deleted', 0);
						   

		return ($builder->countAllResults() == 1);
	}
	
	/*
	Gets max gift card number
	*/
	// public function get_max_number()
	// {
	// $builder = $this->db->select_max('giftcard_number');

	// 	return $builder->get($this->table)->row();
	// }
// 	public function get_max_number()
// {
//     $builder = $this->db->table($this->table)
// 	                    ->selectMax('giftcard_number')
//                         ->get();

//     return $builder->getRow()->max_number;
// }

public function get_max_number()
{
    // Create a Query Builder instance for the specified table
    $builder = $this->db->table($this->table);

    // Select the maximum value of the 'giftcard_number' column
    $builder->selectMax('giftcard_number');

    // Execute the query and fetch the result
    $result = $builder->get();

    // Get the maximum value from the result and return it as an integer
    $maxNumber = intval($result->getRow()->giftcard_number);

    return $maxNumber;
}


// public function get_max_number()
// {
//     $builder = $this->db->table($this->table)
//                         ->selectMax('giftcard_number')
//                         ->get();

//     // Get the maximum value directly from the result
//     // The value will be in the 'giftcard_number' property of the first element in the result array
//     $result = $builder->getResult();

//     if (!empty($result)) {
//         return $result[0];
//     }

//     // Return a default value (e.g., 0) if no rows are returned from the query
//     return 0;
// }

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
	Gets information about a particular giftcard
	*/
	public function get_info($giftcard_id)
	{
	$builder = $this->db->table($this->table)
		         ->join('people', 'people.person_id = giftcards.person_id', 'left')
		         ->where('giftcard_id', $giftcard_id)
		         ->where('deleted', 0)
				 ->get();


		if($builder->getNumRows() == 1)
		{
			return $builder->getRow();
		}
		else
		{
			//Get empty base parent object, as $giftcard_id is NOT an giftcard
			$giftcard_obj = new stdClass();

			//Get all the fields from giftcards table
			foreach($this->db->getFieldNames($this->table) as $field)
			{
				$giftcard_obj->$field = '';
			}

			return $giftcard_obj;
		}
	}

	/*
	Gets an giftcard id given an giftcard number
	*/
	public function get_giftcard_id($giftcard_number)
	{
		$now = date("Y-m-d");
        
		$builder = $this->db->table($this->table)
		                    ->where('giftcard_number', $giftcard_number)
		                    ->where('status', 0)
		                    ->where('DATE(expires_at) >=', $now)
							->get();


		if($builder->getNumRows() == 1)
		{
			return $builder->getRow()->giftcard_id;
		}

		return FALSE;
	}

	/*
	Gets information about multiple giftcards
	*/
	public function get_multiple_info($giftcard_ids)
	{
		$builder = $this->db->table($this->table)
		                     ->whereIn('giftcard_id', $giftcard_ids)
		                     ->where('deleted', 0)
		                     ->orderBy('giftcard_number', 'asc');

		return $builder->get();
	}

	/*
	Inserts or updates a giftcard
	*/
	// public function saveGiftcard(&$giftcard_data, $giftcard_id = -1)
	// {
	// 	$builder = $this->db->table($this->table);
	// 	if($giftcard_id == -1 || !$this->exists($giftcard_id))
	// 	{
	// 		if(!$this->exists_giftcard($giftcard_data['giftcard_number'])){
	// 			if ($builder->insert($this->table, $giftcard_data)) {
	// 				$giftcard_data['giftcard_id'] = $this->db->insertID();
				
	// 				return true;
	// 			}
	// 		}

	// 		return FALSE;
	// 	}
	// 	else
	// 	{
	// 		$builder->where('giftcard_id', $giftcard_id);

	// 		return $builder->update($giftcard_data);
	// 	}

	
	// }
	public function saveGiftcard(&$giftcard_data, $giftcard_id = -1)
	{
		$builder = $this->db->table($this->table);
		
		if ($giftcard_id == -1 || !$this->exists($giftcard_id))
		{
			if (!$this->exists_giftcard($giftcard_data['giftcard_number']))
			{
				if ($builder->insert($giftcard_data))
				{
					$giftcard_data['giftcard_id'] = $this->db->insertID();
					return true; // Inserted successfully
				}
			}
			return false; // Insert failed or gift card already exists
		}
		else
		{
			// Remove the giftcard_id from the data as it should not be updated
			unset($giftcard_data['giftcard_id']);
			
			$builder->where('giftcard_id', $giftcard_id);
			
			if ($builder->update($giftcard_data))
			{
				return true; // Updated successfully
			}
			return false; // Update failed or giftcard_id doesn't exist
		}
	}
	
	public function exists_giftcard($giftcard_number)
	{
		$builder = $this->db->table($this->table)
	                        ->where('giftcard_number', $giftcard_number)
                            ->get();
		return ($builder->getRow() == 1);
	}

	/*
	Updates multiple giftcards at once
	*/
	public function update_multiple($giftcard_data, $giftcard_ids)
	{
		$builder = $this->db->table($this->table)
		                     ->whereIn('giftcard_id', $giftcard_ids);

		return $builder->update($this->table, $giftcard_data);
	}

	/*
	Deletes one giftcard
	*/
	public function deleteGiftcard($giftcard_id)
	{
		$builder = $this->db->table($this->table)
		                    ->where('giftcard_id', $giftcard_id);

		return $builder->update($this->table, array('deleted' => 1));
	}

	/*
	Deletes a list of giftcards
	*/
	public function delete_list($giftcard_ids)
	{
		
		$builder = $this->db->table($this->table)
		                    ->whereIn('giftcard_id', $giftcard_ids);

		return $builder->update(array('deleted' => 1));
 	}

 	/*
	Get search suggestions to find giftcards
	*/
	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table($this->table)
		                    ->like('giftcard_number', $search)
		                    ->where('deleted', 0)
		                    ->orderBy('giftcard_number', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[]=array('label' => $row->giftcard_number);
		}

		$customer = $this->db->table('customers')
		->join('people', 'customers.person_id = people.person_id', 'left')
		->groupStart()
			->like('first_name', $search)
			->orLike('last_name', $search)
			->orLike('CONCAT(first_name, " ", last_name)', $search)
		->groupEnd()
		->where('deleted', 0)
		->orderBy('last_name', 'asc')
		->get();
	
	$results = $customer->getResult();
	
	$suggestions = [];
	foreach ($results as $row) {
		$suggestions[] = ['label' => $row->first_name . ' ' . $row->last_name];
	}
			

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}
	
   public function getData(){
	$builder = $this->db->table($this->table)
	->join('people', 'giftcards.person_id = people.person_id', 'left')
	->where('giftcards.deleted', 0);
	return $builder->get();


   }

	/*
	Performs a search on giftcards
	*/
// 	public function search($search, $rows = 0, $limit_from = 0, $sort = 'giftcard_number', $order = 'asc')
//    {
   
//     $builder = $this->db->table($this->table);
// 	$builder->join('people', 'giftcards.person_id = people.person_id', 'left')
// 		->like('giftcard_number', $search)
//         ->where('deleted', 0);
    

//     $builder->orderBy('giftcard_number', $order);

//     if ($rows > 0) {
//         $builder->limit($rows, $limit_from);
//     }

//     return $builder->get();
// }

public function search($search, $rows = 0, $limit_from = 0, $sort = 'giftcard_number', $order = 'asc')
{
   
    $builder = $this->db->table($this->table);
    
    $builder->join('people', 'giftcards.person_id = people.person_id', 'left');
    
    $builder->groupStart();
	$builder->like('first_name', $search);
	$builder->orLike('last_name', $search);
	$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
	$builder->orLike('giftcard_number', $search);
	$builder->orLike('giftcards.person_id', $search);
	$builder->groupEnd();
    
    $builder->where('giftcards.deleted', 0);
    $builder->orderBy($sort, $order);

    if ($rows > 0) {
        $builder->limit($rows, $limit_from);
    }

    return $builder->get();
}

	/*
	Gets gift cards
	*/
	public function get_found_rows($search)
	{
		$builder = $this->db->table($this->table)
		->join('people', 'giftcards.person_id = people.person_id', 'left')
	                    		->like('giftcard_number', $search)
	                    	->where('giftcards.deleted', 0);
		$builder->groupStart();
		$builder->like('first_name', $search);
		$builder->orLike('last_name', $search);
		$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->orLike('giftcard_number', $search);
		$builder->orLike('giftcards.person_id', $search);
		$builder->groupEnd();

		return $builder->get()->getNumRows();
	}
	
	/*
	Gets gift card value
	*/
	public function get_giftcard_value($giftcard_number)
	{
		if( !$this->exists($this->get_giftcard_id($giftcard_number)) )
		{
			return 0;
		}
		
		$builder = $this->db->table($this->table)
		                    ->where('giftcard_number', $giftcard_number);

		return $builder->get()->getRow()->value;
	}
	

	/*
	Updates gift card value
	*/
	public function update_giftcard_status($giftcard_number)
	{
		$giftcard_number = strtoupper($giftcard_number);
		$builder = $this->db->table($this->table)
		                    ->where('giftcard_number', $giftcard_number);
		return $builder->update($this->table, array('status' => 1));
		// $this->db->update($this->table, array('value' => $value, 'deleted' => 1));
	}
	
	/*
	Determines if a given giftcard_number exists
	*/
	public function giftcard_number_exists($giftcard_number, $giftcard_id = '')
	{
		$builder = $this->db->table($this->table)
		                    ->where('giftcard_number', $giftcard_number);
		if(!empty($giftcard_id))
		{
			$builder->where('giftcard_id !=', $giftcard_id);
		}

		return ($builder->countAllResults() == 1);
	}
}
?>