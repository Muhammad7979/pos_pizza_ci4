<?php
namespace App\Models;

use CodeIgniter\Model;

class Notification extends Model 
{	
	/*
    Save notifications
    */
	public function saveNotification($notification_data)
	{
		$this->db->table('notifications')->insert($notification_data);
		return $this->db->insertID();
	}
	
	/*
    Get Single notifications for a particular id
    */
	public function getSingleNotification($id)
	{
		$builder = $this->db->table('notifications')
		                    ->where('id', $id);
		return $builder->get()->getRow();		
	}

	/*
    Get All notifications for a particular person
    */
    public function getAllNotifications($person_id)
    {
        $builder = $this->db->table('notifications')
                            ->where('noti_to', $person_id)
                            ->where('status', 0)
                            ->orderBy('created_at', 'desc');
        
        //return an array of order items for an item
        return $builder->get()->getResultArray();
    }

    /*
    update notification status to read for a particular id
    */
	public function updateNotification($id)
	{
		$builder = $this->db->table('notifications')->where('id', $id);
		return $builder->update(['status' => 1,]);
	}

	/*
	Gets rows
	*/
	public function get_found_rows($employee, $category, $search)
	{
		$builder = $this->db->table('notifications')
		                    ->groupStart()
			                    ->like('details', $search)
		                    ->groupEnd()
		                    ->where('deleted', 0)
		                    ->where('noti_to', $employee)
		                    ->whereIn('category', $category);

		return $builder->get()->getNumRows();
	}
	
	/*
	Perform a search on warehouses
	*/
	public function search($employee, $category, $search, $rows = 0, $limit_from = 0, $sort = 'created_at', $order = 'desc')
	{
		$builder = $this->db->table('notifications')
		                    ->groupStart()
			                    ->like('details', $search)
		                    ->groupEnd()
		                    ->where('deleted', 0)
		                    ->where('noti_to', $employee)
		                    ->whereIn('category', $category)
		
		                    ->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Deletes one notification
	*/
	public function delete_notification($id)
	{
		$builder = $this->db->table('notifications')->where('id', $id);

		return $builder->update(array('deleted' => 1));
	}

	/*
	Deletes a list of notifications
	*/
	public function delete_list($ids)
	{
		$builder = $this->db->table('notifications')->whereIn('id', $ids);

		return $builder->update(array('deleted' => 1));
 	}

 	public function getEmployeeType($id='', $table='')
 	{
 		$builder = $this->db->table($table)
		                    ->where('person_id', $id);
		return $builder->get()->getNumRows();
 	}
}	
?>