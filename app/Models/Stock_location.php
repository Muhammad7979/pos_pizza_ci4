<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Models\Employee;
use App\Models\Item;

use Config\Database;


class Stock_location extends Model
{

	protected $table = 'ospos_stock_locations';
	protected $primaryKey = 'id';
    protected $allowedFields = ['location_name','deleted'];
	protected $employeeModel;
	protected $item;
    public function __construct()
    {
		helper('session');
        $this->db = \Config\Database::connect();
		$this->employeeModel=new Employee();
		$this->item=new Item();
    }
    public function exists($location_name = '')
    {
		$builder = $this->db->table($this->table)
                            ->where('location_name', $location_name);
        
        return ($builder->get()->getNumRows() >= 1);
    }
    
    public function get_all($limit = 10000, $offset = 0)
    {
		$builder = $this->db->table($this->table)
                            ->limit($limit)
                            ->offset($offset);
	
        return $builder->get();
    }
    
    public function get_undeleted_all($module_id = 'items')
    {
		$builder = $this->db->table($this->table)
                            ->join('permissions', 'permissions.location_id = stock_locations.location_id')
		                    ->join('grants', 'grants.permission_id = permissions.permission_id')
                            ->where('person_id', session()->get('person_id'))
                            ->like('permissions.permission_id', $module_id, 'after')
                            ->where('deleted', 0);

        return $builder->get();
    }

	public function show_locations($module_id = 'items')
	{
		$stock_locations = $this->get_allowed_locations($module_id);

		return count($stock_locations) > 1;
	}

	public function multiple_locations()
	{
		// return $this->get_all()->num_rows() > 1;
		return $this->countAllResults() > 1;
	}

    public function get_allowed_locations($module_id = 'items')
    {
    	$stock = $this->get_undeleted_all($module_id)->getResultArray();
    	$stock_locations = array();
    	foreach($stock as $location_data)
    	{
    		$stock_locations[$location_data['location_id']] = $location_data['location_name'];
    	}

    	return $stock_locations;
    }

	public function is_allowed_location($location_id, $module_id = 'items')
	{
		$builder = $this->db->table($this->table)
		                    ->join('permissions', 'permissions.location_id = stock_locations.location_id')
		                    ->join('grants', 'grants.permission_id = permissions.permission_id')
		                    ->where('person_id', $this->session->userdata('person_id'))
		                    ->like('permissions.permission_id', $module_id, 'after')
		                    ->where('deleted', 0)
		                    ->where('stock_locations.location_id', $location_id);

		return ($builder->get()->getNumRows() == 1);
	}
    
    public function get_default_location_id()
{
    $builder = $this->db->table($this->table)
                        ->join('permissions', 'permissions.location_id = stock_locations.location_id')
                        ->join('grants', 'grants.permission_id = permissions.permission_id')
                        ->where('person_id', session()->get('person_id'))
                        ->where('deleted', 0)
                        ->limit(1);
    $result = $builder->get()->getRow();
    if ($result !== null) {
        return $result->location_id;
    } else {
        // Handle the case when no location is found, such as returning a default value or throwing an exception.
        return null; // or throw new Exception("No default location found.");
    }
}

    public function get_location_name($location_id) 
    {
		$idExists = $this->db->table($this->table)->where('location_id', (int)$location_id)->get()->getNumRows();
		if($idExists > 0){
		$builder = $this->db->table($this->table)
                            ->where('location_id', (int)$location_id);
    	return $builder->get()->getRow()->location_name;
		}else{
			return '';
		}
    }
    
    public function saveStockLocation(&$location_data, $location_id) 
    {
		$location_name = $location_data['location_name'];

    	if(!$this->exists($location_name))
    	{
    		$this->db->transStart();

    		$location_data = array('location_name'=>$location_name, 'deleted'=>0);
		    $builder = $this->db->table($this->table)
   			                    ->insert($location_data);
   			$location_id = $this->db->insertID();
   			 
   			$this->_insert_new_permission('items', $location_id, $location_name);
   			$this->_insert_new_permission('sales', $location_id, $location_name);
   			$this->_insert_new_permission('receivings', $location_id, $location_name);
    		
   			// insert quantities for existing items
   			$items = $this->item->get_all();
			
   			foreach($items as $item)
   			{
			
   				$quantity_data = array('item_id' => $item['item_id'], 'location_id' => $location_id, 'quantity' => 0);
		        $builder = $this->db->table('item_quantities')
			                     ->insert($quantity_data);
   			}

   			$this->db->transComplete();
			
			return $this->db->transStatus();
   		}
    	else 
    	{
		    $builder = $this->db->table($this->table)

    		                    ->where('location_id', $location_id);

    		return $builder->update($location_data);
    	}
    }
    	
    private function _insert_new_permission($module, $location_id, $location_name)
    {
    	// insert new permission for stock location
		$permissions = $this->db->table('ospos_permissions');

    	$permission_id = $module . '_' . $location_name;
    	$permission_data = array('permission_id' => $permission_id, 'module_id' => $module, 'location_id' => $location_id);
    	$permissions->insert($permission_data);
    	
    	// insert grants for new permission
    	$employees = $this->employeeModel->get_all();
    	foreach($employees->getResultArray() as $employee)
    	{
		    $permissions = $this->db->table('ospos_grants');
    		$grants_data = array('permission_id' => $permission_id, 'person_id' => $employee['person_id']);
    		$permissions->insert($grants_data);
    	}
    }
    
    /*
     Deletes one item
    */
    public function deleteStockLocation($location_id)
    {
    	$this->db->transStart();	
        $builder = $this->db->table($this->table)
    	                    ->where('location_id', $location_id)
    	                    ->update(array('deleted' => 1));
		$permissions = $this->db->table('permissions')
    	                    ->where('location_id', $location_id)
    	                    ->delete();

    	$this->db->transComplete();
		
		return $this->db->transStatus();
    }
}
?>