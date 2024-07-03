<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;

class Module extends Model 
{
    function __construct()
    {
        parent::__construct();
    }
	
	public function get_module_name($module_id)
	{
		$query = $this->db->table('modules')->where('module_id', $module_id)->get();
		
		if($query->getNumRows() == 1)
		{
			$row = $query->getRow();

			return lang('module_lang.'.$row->name_lang_key);
		}
		
		return lang('error_lang.error_unknown');
	}
	
	public function get_module_desc($module_id)
	{
		$query = $this->db->table('modules')->where('module_id' , $module_id)->get();

		if($query->getNumRows() == 1)
		{
			$row = $query->getRow();

			return lang('module_lang'.$row->desc_lang_key);
		}
	
		return lang('error_lang.error_unknown');	
	}
	
	public function get_all_permissions()
	{
		return $this->db->table('permissions')->get();
	}
	
	public function get_all_subpermissions()
	{
	$builder = $this->db->table('permissions')
		         ->join('modules', 'modules.module_id = permissions.module_id')
		// can't quote the parameters correctly when using different operators..
		         ->where($this->db->prefixTable('modules') . '.module_id!=', 'permission_id', FALSE);

		return $builder->get();
	}
	
	public function get_all_modules()
	{
		$builder = $this->db->table('modules')
		->orderBy('sort', 'asc');

		return $builder->get();		
	}
	
	public function get_allowed_modules($person_id)
	{
		$builder = $this->db->table('modules')
		         ->join('permissions', 'permissions.permission_id = modules.module_id')
		         ->join('grants', 'permissions.permission_id = grants.permission_id')
		         ->where('person_id', $person_id)
		         ->orderBy('sort', 'asc');

		return $builder->get()->getResult();		
	}
}
?>
