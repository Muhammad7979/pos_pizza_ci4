<?php
namespace App\Models;
use CodeIgniter\Model;
use Config\Database;
class Branch extends Model
{
    protected $db;
    protected $table = 'ospos_b';
    protected $allowedFields = ['branch_name', 'branch_address','branch_phone','branch_code','system_code','strn','ntn','fbr_post_url','fbr_bearer_token','fbr_access_code','fbr_pos_id'];
    public function __construct() {
        parent::__construct();

    
        $this->db = \Config\Database::connect();
       }


       public function saveBranchData($dataArray)
       {
           $existingData = $this->db->table($this->table)
                                    ->whereIn('column_name', $dataArray)
                                    ->get()
                                    ->getResult();
       
           $existingValues = array_column($existingData, 'column_name');
           $newData = array_diff($dataArray, $existingValues);
       
           if (!empty($newData)) {
               $insertData = array_map(function($value) {
                   return ['column_name' => $value];
               }, $newData);
               
               $this->db->table($this->table)->insertBatch($insertData);
               return true; // Return a success indicator or custom message if needed
           }
           else
{
    $insertData = array_map(function($value) {
        return ['column_name' => $value];
    }, $newData);
    
    $this->db->table($this->table)->insertBatch($insertData);
    return true; 
}
       
           // Return a failure indicator or custom message if needed
       }
       
}