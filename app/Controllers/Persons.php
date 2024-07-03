<?php
namespace App\Controllers;
use App\Controllers\Secure_Controller;
use App\Models\Person;
use App\Models\Module;

abstract class Persons extends SecureController
{ 
	private $Person;
	public function __construct($module_id = NULL)
	{

		parent::__construct($module_id);
		$this->Person = new Person();
		
	}
	
	/*
	 Gives search suggestions based on what is being searched for
	*/
	// public function suggest()
	// {
	// 	$suggestions = $this->xss_clean($this->Person->get_search_suggestions($this->input->post('term')));

	// 	echo json_encode($suggestions);
	// }
	public function suggest()
    {
        $request = $this->request;
        $term = $request->getPost('term');

        $suggestions = $this->Person->getSearchSuggestions($term);

        return $this->response->setJSON($suggestions);
    }
	/*
	Gets one row for a person manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		
		$person = new Person();
		$row_data = $person->get_info($row_id);
		$data_row = $this->xss_clean(get_person_data_row($row_data, $this));

		return json_encode($data_row);
	}
	
}
?>