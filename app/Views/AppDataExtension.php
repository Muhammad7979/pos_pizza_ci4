<?php

namespace App\Views;

use App\Services\AppDataService;
use CodeIgniter\View\View;

class AppDataExtension extends \CodeIgniter\View\Extension
{
    protected $appData;

    public function __construct(AppDataService $appData)
    {
        $this->appData = $appData->getAppData();
    }

    public function extend(View $view, &$data = [])
    {
        $data['appData'] = $this->appData;
    }
}
