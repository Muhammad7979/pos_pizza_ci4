<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;
use stdClass;
use CodeIgniter\Database\BaseBuilder;

class Item_kit extends Model
{
	/*
	Determines if a given item_id is an item kit
	*/
	protected $table = 'ospos_item_kits';
	protected $allowed_fields = ['name', 'description'];

    public function __construct()
    {
        $this->db = Database::connect(); // Load the database
    }
	public function exists($item_kit_id)
	{
        $builder = $this->db->table($this->table)
		                    ->where('item_kit_id', $item_kit_id)
                            ->get();
		return ($builder->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table($this->table);

		return $builder->countAllResults();
	}
	
	/*
	Gets information about a particular item kit
	*/
	public function get_info($item_kit_id)
	{
		$builder = $this->db->table($this->table)
		                   ->where('item_kit_id', $item_kit_id)
		                   ->get();

		if ($builder->getNumRows() == 1)
		 {
			return $builder->getRow();
		 }else{
			//Get empty base parent object, as $item_kit_id is NOT an item kit
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->getFieldNames($this->table) as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Gets information about multiple item kits
	*/
	public function get_multiple_info($item_kit_ids)
	{
		$builder = $this->db->table($this->table)
		                    ->whereIn('item_kit_id', $item_kit_ids)
		                    ->orderBy('name', 'asc');

		return $builder->get();
	}

	/*
	Inserts or updates an item kit
	*/
	public function saveItemKits(&$item_kit_data, $item_kit_id = FALSE)
	{
		$builder = $this->db->table($this->table);
		if(!$item_kit_id || !$this->exists($item_kit_id))
		{
			if($builder->insert($item_kit_data))
			{
				$insertId =  $this->db->insertID();
				$item_kit_data['item_kit_id'] = $insertId;

				return TRUE;
			}

			return FALSE;
		}

		$builder->where('item_kit_id', $item_kit_id);

		return $builder->update($item_kit_data);
	}

	/*
	Deletes one item kit
	*/
	public function deleteItemKits($item_kit_id)
	{  
		$builder = $this->db->table($this->table)
		                   ->where('item_kit_id', $item_kit_id);
		return $builder->delete(); 	
	}

	/*
	Deletes a list of item kits
	*/
	public function delete_list($item_kit_ids)
	{
		$builder = $this->db->table($this->table)
		                    ->whereIn('item_kit_id', $item_kit_ids);

		return $builder->delete();		
 	}

	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table($this->table);

		//KIT #
		if(stripos($search, 'KIT ') !== FALSE)
		{
			$builder->like('item_kit_id', str_ireplace('KIT ', '', $search))
			        ->orderBy('item_kit_id', 'asc');
					

			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => 'KIT '. $row->item_kit_id, 'label' => 'KIT ' . $row->item_kit_id);
			}
		}
		else
		{
			$builder->like('name', $search)
			        ->orderBy('name', 'asc');

			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => 'KIT ' . $row->item_kit_id, 'label' => $row->name);
			}
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

	/*
	Perform a search on items
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'name', $order = 'asc')
	{
		$builder = $this->db->table($this->table);
		if (!empty($search)) {
			$builder->like('name', $search);
			$builder->orLike('description', $search);
		}


		//KIT #
		if (stripos($search, 'KIT ') !== FALSE) {
			$builder->orLike('item_kit_id', str_ireplace('KIT ', '', $search));
		}

		$builder->orderBy('name', 'ASC');

		if ($rows > 0) {
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}
	
	public function get_found_rows($search)
	{
		$builder = $this->db->table($this->table);

		if (!empty($search)) {
			$builder->like('name', $search)
				->orLike('description', $search);
		}

		//KIT #
		if (stripos($search, 'KIT ') !== FALSE) {
			$builder = $this->db->table($this->table)
				->orLike('item_kit_id', str_ireplace('KIT ', '', $search));
		}

		return $builder->get()->getNumRows();
	}

	public function kits_upload($db){
          
		$branch = $this->db->table('app_config')->where('key','branch_code')->get()->getRow();
		$item_kits_data = $this->db->table('item_kits')->get()->getResult();
		$item_kits_data_items = $this->db->table('item_kit_items')->get()->getResult();
		$kits = $db->table('item_kits')->where('branch_code',$branch->value)->get();

   try{
			
		$db->transStart();

		if($kits->getNumRows() > 0){

		$db->table('item_kits')->where('branch_code',$branch->value)->delete();

	  }
		
		foreach($item_kits_data as $key => $data){

			$items = $this->db->table('item_kit_items')
							  ->where('item_kit_id',$data->item_kit_id)
							  ->get()->getResult();

		   $data->branch_code = $branch->value;
		   unset($data->item_kit_id);

			$db->table('item_kits')->insert($data);
			$item_kit_id = $db->insertID();
		   
		   foreach($items as $item){

			   $item->item_kit_id = $item_kit_id;
			   $db->table('item_kit_items')->insert($item);

		   }
	   
		}

		$db->transComplete();

	   return true;

		  }
		   catch (\Exception $e){
			   $db->transRollback();
			   return false;
		   }

   }
}
?>