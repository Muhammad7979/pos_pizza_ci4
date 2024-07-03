<?php
namespace App\Libraries;

use App\Models\Appconfig;

class AppData
{
    protected $appconfigModel;
    protected $appData;
  
    public function __construct()
    {
        $this->appconfigModel = new Appconfig();
        $this->appData = $this->appconfigModel->get_all();
    }

    public function getAppData()
    {
        return $this->appData;
    }
}
