<?php

namespace App\Libraries;

class ItemLib
{
    protected $session;
    protected $stockLocationModel;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->stockLocationModel = new \App\Models\Stock_location();
    }

    public function get_item_location()
    {
        $session = session();
    
        if (!$session->has('item_location')) {
            $locationId = $this->stockLocationModel->get_default_location_id();
            $this->set_item_location($locationId);
        }
    
        return $session->get('item_location');
    }
    

    public function set_item_location($location)
    {
        $session = session();
        $session->set('item_location', $location);
    }
    

    public function clearItemLocation()
    {
        $this->session->remove('item_location');
    }
}
