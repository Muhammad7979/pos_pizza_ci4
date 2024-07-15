<?php
namespace App\Libraries;

/**
 * Created by @UmarCloud.
 * User: gamingumar
 * Date: 21 Oct 2016
 * Time: 10:52 AM
 */
use DateTime;
use DateInterval;
use Config\Services;
use CodeIgniter\Database\Config;
use CodeIgniter\Files\File;
use Config\Database;
use Predis\Client;
use CodeIgniter\Log\Handlers\FileHandler;

class Gu
{
    protected $CI;
    protected $appConfigModel;
    public function __construct()
    {
        // Assign the CodeIgniter instance
        $this->CI = Services::codeigniter();


        // $this->db = \Config\Database::connect();
        $this->appConfigModel = new \App\Models\Appconfig();
    }

    /**
     * Check if this is LIVE Server ****************************** CONFIG
     * @return bool
     */
    // public function isServer()
    // {
    //     $online = \Config\Database::connect('online');

    //     if(false === $online->getConnection())
    //     {
    //         return false;
    //     }
    //     else

    //     {
    //         return false;
    //     }




    // }

    // public function isServer()
    // {
    //     try {
    //         $onlineDB =\Config\Database::connect('online');
    //         // dd($onlineDB);
    //         if ($onlineDB->simpleQuery('SELECT 1')) {
    //             return true; // The connection is to the online database
    //         } else {
    //             return false; // The connection is not to the online database
    //         }    
    //     } catch (\Throwable $th) {
    //         return false;   
    //     }

    // }

    public function isServer()
    {
        // try {
            // $onlineDB = \Config\Database::connect('online');
            // $databaseName = $onlineDB->getDatabase(); // Get the name of the connected database

            // if ($databaseName === 'pos_pizza') {
                return true; // The connected database is 'tehzeeb_pos'
        //     } else {
        //         return false; // The connected database is not 'tehzeeb_pos'
        //     }
        // } catch (\Throwable $th) {
        //     return false;
        // }
    }





    public function getStoreDate()
    {
        $date = new \DateTime();

        $hour = $date->format('H');

        if ($hour >= 0 && $hour < 5) {
            $toSub = new \DateInterval('PT5H0M');
            $date->sub($toSub);
        }

        return $date->format('Y-m-d');
    }


    /**
     * Check if Start Date allowed for Reports
     * @param $start_date
     * @return bool
     */

    public function checkStartDate($start_date)
    {
        if ($this->isServer()) {
            return true;
        }

        $date1 = new \DateTime();
        $date2 = new \DateTime($start_date);

        $diff = $date1->diff($date2)->days;

        if ($diff > 1) {
            return false;
        } else {
            return true;
        }
    }




    public function getStoreBranchCode()
    {
        return $this->appConfigModel->get('branch_code');
    }
    public function getBranches()
    {
        return [
            'all' => "All Branches",
            'th001' => "th001 F11",
            'th002' => "th002 G9",
            'th003' => "th003 Blue Area",
            'th004' => "th004 Commercial",
            'th005' => "th005 Scheme III",
            'th006' => "th006 Saddar",
            'th007' => "th007 PWD",
            'th008' => "th008 G11",
            'th009' => "th009 T Chowk",
            'th012' => "th012 Digital",
            'th014' => "th014 Lahore",
            'gu-test-branch' => "Test Branch",
            //        'b1' => "b1",
            //        'b2' => "b2",
        ];
    }


    /**
     * Get System ID / Code of Computer in branch
     *
     * @return mixed
     */
    public function getStoreSystemCode()
    {
        return $this->appConfigModel->get('system_code');
    }


    public function generateBillNumber($code = null)
    {
        if (is_null($code)) {
            $code = date('YmdHis');
        }

        return $this->getStoreBranchCode() . "-" . $this->getStoreSystemCode() . $code . mt_rand(100, 999);
    }

    public function getStoreInfoByBranchCode($branch_code)
    {
        if ($this->isServer()) {
            $online = Database::connect('online');

            $branch = $online->table('branches')->where('branch_code', $branch_code)->get()->getResult();

            if (count($branch) > 0) {
                $name = $branch[0]->name;
                $city = $branch[0]->city;
                $address = $branch[0]->address;
                $phone = $branch[0]->phone;
            } else {
                $name = "Tehzeeb";
                $city = "Branch City not found";
                $address = "Branch Address not found";
                $phone = "Branch Phone not found";
            }

            return [
                'name' => $name,
                'city' => $city,
                'address' => $address,
                'phone' => $phone
            ];
        } else {
            return [
                'name' => $this->appConfigModel->get('company'),
                'address' => nl2br($this->appConfigModel->get('address')),
                'phone' => $this->appConfigModel->get('phone'),
                'city' => '',
            ];
        }
    }

    public function getReportingDb()
    {

        $local = Database::connect('default');

        if ($this->onlyLocalReport()) {
            return $local;
        }

        $online = \Config\Database::connect('online');

        if ($this->isServer()) {
            return $online;
        }

        // TODO - TURN THIS ON FOR OFFLINE BRANCHES
        $allowLocalDbForReporting = true;

        if (!$online->connID) {
            $this->log(" can't connect to online server");

            $db = $local;
        } else {
            if ($allowLocalDbForReporting && !$this->isServer()) {
                // Online database can be accessed
                // If percent > 0, return local db
                if ($this->getStorePercent($online) > 0) {
                    $db = $local;
                } else {
                    $db = $online;
                }
            } else {
                $db = $online;
            }
        }

        return $db;
    }


    public function onlyLocalReport()
    {
        if (empty($this->appConfigModel->get('only_local_report'))) {
            $this->appConfigModel->save('only_local_report', 'off');
        }

        if ($this->appConfigModel->get('only_local_report') == "on") {
            return true;
        } else {
            return false;
        }
    }

    public function getStorePercent($online = null)
    {
        if ($online == null) {
            $online = Database::connect('online');
        }

        if (!$online->connID) {
            $hold_percent = 1;
        } else {
            $branch_code = $this->getStoreBranchCode();
            $onlineBranch = $online->table('branches')->where('branch_code', $branch_code)->get()->getResult();

            if (count($onlineBranch) > 0) {
                $hold_percent = $onlineBranch[0]->percent;
            } else {
                $hold_percent = 1;
            }
        }

        return $hold_percent;
    }


    public function getSaleTypes()
    {
        return [
            'normal' => 'Normal',
            'breakfast' => 'Breakfast',
            'complementary' => 'Complementary',
            'burger' => 'Burger',
            'cash-counter' => 'Cash Counter',
            'employee' => 'Employee',
        ];
    }

    public function getSaleTypesForFilter()
    {
        return [
            'all' => 'All Sale Modes',
            'sales' => 'Sales',
            'returns' => 'Returns',
            //'breakfast' => 'Breakfast',
            //'complementary' => 'Complementary',
            //'burger' => 'Burger',
            //'cash-counter' => 'Cash Counter',
            //'employee' => 'Employee',
        ];
    }

    public function app_down()
    {
        $this->appConfigModel->set('maintenance_mode', 'on');
        return 'app is down';
    }

    public function app_up()
    {
        $this->appConfigModel->set('maintenance_mode', 'off');
        return 'app is up';
    }

    public function app_status()
    {
        return $this->appConfigModel->get('maintenance_mode');
    }


    public function canSync()
    {
        if ($this->appConfigModel->get('sync_allow') == "off") {
            return false;
        } else {
            return true;
        }
    }


    public function enableSync()
    {
        $this->appConfigModel->set('sync_allow', 'on');
        return 'sync enabled';
    }

    public function disableSync()
    {
        $this->appConfigModel->set('sync_allow', 'off');
        return 'sync disabled';
    }

    public function canCakeSync()
    {
        if ($this->appConfigModel->get('cake_sync_allow') == "off") {
            return false;
        } else {
            return true;
        }
    }

    public function enableCakeSync()
    {
        $this->appConfigModel->set('cake_sync_allow', 'on');
        return 'cake sync enabled';
    }


    public function disableCakeSync()
    {
        $this->appConfigModel->set('cake_sync_allow', 'off');
        return 'cake sync disabled';
    }



    public function canPizzaSync()
    {
        if ($this->appConfigModel->get('pizza_sync_allow') == "off") {
            return false;
        } else {
            return true;
        }
    }
    public function enablePizzaSync()
    {
        $this->appConfigModel->set('pizza_sync_allow', 'on');
        return 'pizza sync enabled';
    }
    public function disablePizzaSync()
    {
        $this->appConfigModel->set('pizza_sync_allow', 'off');
        return 'pizza sync disabled';
    }


    public function getLastSaleSyncTime()
    {
        return $this->appConfigModel->get('last_sale_sync');
    }
    public function updateLastSaleSyncTime()
    {
        $time = date('Y-m-d h:i A');
        $this->appConfigModel->set('last_sale_sync', $time);
        return "last sync time updated";
    }

    public function lastSaleTimeDiff()
    {
        $db_date = $this->getLastSaleSyncTime();
        if ($db_date) {
            $db_stamp = strtotime($db_date);
            $diff = round((time() - $db_stamp) / 60);

            return $diff;
        } else {
            return 0;
        }
    }



    /**
     * Check if sale log is enabled ****************************** CONFIG
     * @return bool
     */
    public function saleLog()
    {
        //return false;
        return true;
    }


    public function log($msg)
    {
        $log = date('d M Y h:i A') . " $msg. " . PHP_EOL;

        helper('filesystem');
        write_file(WRITEPATH . 'logs/debug.log', $log, 'a');

        return true;
    }
    public function logInFile($file, $msg)
    {
        $file = "logs/" . $file;

        $fileHandler = new File($file);
        // dd($fileHandler);
        // $fileHandler->move($file,$msg . "\n");
    }


    public function getCacheSalesCount()
    {
        $redis = new Client();

        $keys = $redis->keys('sale:*');
        return count($keys);
    }

    public function getInitials($str)
    {
        $ret = '';
        foreach (explode(' ', $str) as $word) {
            $ret .= ' ' . strtoupper($word[0]);
        }
        return $ret;
    }
}