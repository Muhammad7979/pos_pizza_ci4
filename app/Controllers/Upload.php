<?php 

namespace App\Controllers;


class Upload extends SecureController
{
	/**
     * Upload to db
     */
    public function index()
    {
        $data = array();

        //$response = "<pre>";
        $data['success'] = exec('php index.php cli upload', $output, $error);


        // $response .= " " . print_r($output) . "</pre>";

        // $data['success'] = $response;

        //$this->_reload($data);
        // $data['success'];

        session()->set(['upload_message' => $data['success']]);
        redirect('home');

    }
}