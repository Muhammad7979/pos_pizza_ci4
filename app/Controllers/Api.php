<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Pizza_order;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Api extends ResourceController
{
    use ResponseTrait;
    public function pizza_completed_orders(){
        $Pizza_order = new Pizza_order();
        $completed_order_data =  $Pizza_order->get_pizza_completed_order();
        return  $this->respond($completed_order_data);
}

public function delete_pizza_completed_order($id){
    $Pizza_order = new Pizza_order();
    $delete_order =  $Pizza_order->delete_pizza_completed_order($id);
     return $this->respondDeleted($delete_order);
}
}
