<?php
namespace App\Controllers;

use App\Libraries\Gu;
use App\Models\Sale;
use Config\Database;
use Predis\Client;
use App\Models\Appconfig;

/**
 * Created by @UmarCloud.
 * User: gamingumar
 * Date: 04 Oct 2016
 * Time: 11:25 AM
 */
class Cli extends BaseController
{
    protected $gu;
    protected $Sale;

    protected $appConfig;
    public function __construct()
    {
        $this->gu = new Gu();
        $this->Sale = new Sale();
        $this->appConfig = new Appconfig();
        // this controller can only be called from the command line
        //if (!$this->input->is_cli_request()) show_error('Direct access is not allowed');
    }

 
    public function message($to = 'Cron schedule')
    {
        //run this using php index.php cli message
        $data = "GU {$to}! " . date('l jS \\of F Y h:i:s A', time()) . PHP_EOL;

        file_put_contents(APPPATH . "logs/cron.log", $data, FILE_APPEND);

        //ENABLE SYNC AUTOMATICALLY IF CRASHED AFTER 20 MINUTES

        if ($this->gu->lastSaleTimeDiff() > 20) {
            if ($this->gu->canSync() == false) {
                echo "Auto Sync enabled";
                $this->gu->enableSync();
                $this->gu->log('AUTO SYNC ENABLED');

                $data = "AUTO SYNC ENABLED " . date('l jS \\of F Y h:i:s A', time()) . PHP_EOL;

                file_put_contents(APPPATH . "logs/cron.log", $data, FILE_APPEND);
            }

        }

    }

    public function clearLog($to = 'LOG Clear')
    {
        //run this using php index.php cli message
        $data = "GU {$to}! " . date('l jS \\of F Y h:i:s A', time()) . PHP_EOL;

        file_put_contents(APPPATH . "logs/cron.log", $data);
    }


    public function bgtest()
    {
        sleep(120);

        $data = "GU 2 minute delay bg task! " . date('l jS \\of F Y h:i:s A', time()) . PHP_EOL;

        file_put_contents(APPPATH . "logs/cron.log", $data, FILE_APPEND);
    }

    public function onlyLocalReport()
    {
        echo $this->gu->onlyLocalReport();
    }

    public function getLastSaleSyncTime()
    {
        echo $this->gu->getLastSaleSyncTime();
    }

    public function getLastSaleTimeDiff()
    {
        echo $this->gu->lastSaleTimeDiff();
    }

    /**
     * Get from redis and process
     *
     * TODO - ADD THIS METHOD IN TRY CATCH LIKE dbUp
     *
     */
    public function upload()
    {
        //$isOnlineServer = $this->gu->isServer();


        if ($this->gu->canSync() == false) {
            echo "Sync Disabled";
            exit;
        }

        try {

            $this->gu->disableSync();

            $online = Database::connect('online', true);
            $online->initialize();
            $local = Database::connect('default', true);

            /**
             * Redis Config
             */
            $redis = new \Predis\Client();

            $sales_count = count($redis->keys('sale:*'));
            if ($sales_count > 0) {

                //get all sales record from redis and get old and new sales

                $new_sales = array();
                $old_sales = array();

                //TODO - FOR FULL OFFLINE ONLY WORK WITH NEW SALES AND ADD ALL TO LOCAL

                for ($i = 1; $i <= $sales_count; $i++) {
                    $sale = $redis->keys('sale:*')[$i - 1];

                    $sale = $redis->hgetall($sale);


                    $status = $sale['status'];


                    if ($status == 1) {
                        if (count($old_sales) < 20) {
                            array_push($old_sales, $sale);
                        }
                    } else {
                        array_push($new_sales, $sale);
                    }


                    //for online version
                    // if($i >= 20 ){
                    //     break;
                    // }

                    //for offline version //works with both actually
                    if (count($new_sales) > 20) {
                        break;
                    }
                }


                // echo "<pre>";
                // print_r($sale_data->invoice_number);
                // print_r($sale);
                // echo "</pre>";
                // exit;



                /**
                 * now we have 2 array with old and new sales
                 *
                 * save all old records to online db directly
                 *
                 * get percent from new record
                 *
                 * save in local and online db
                 *
                 * if no connection upload record to local db and mark status = 1
                 */


                // echo "<pre>";

                $oldCount = count($old_sales);
                $newCount = count($new_sales);

                //check if online db is not connected or it is online
                if (false == $online->connID) {//} || $isOnlineServer == true) {
                    /**
                     * WHEN ONLINE DB IS NOT CONNECTED, ONLY PROCESS RECORDS WITH (STATUS == 0)
                     */

                    $this->gu->log('cant connect');
                    echo "can't connect";
                    //$hold_percent = 1;
                    //save all to local db and mark status = 1
                    foreach ($new_sales as $sale) {
                        if ($this->Sale->dbUp($local, $sale)) {
                            $redis->hset($sale['id'], 'status', 1);
                        } else {
                            $this->gu->log($sale['id'] . ' unable to save to local db after online server failed.');
                        }
                    }

                } else {
                    /**
                     * WHEN ONLINE DB IS CONNECTED, PROCESS ALL REDIS RECORDS
                     */

                    //get config variable $hold_percent

                    //$hold_percent = $this->config->item('percent');

                    $branch_code = $this->gu->getStoreBranchCode();

                    $onlineBranch = $online->table('branches')->where('branch_code', $branch_code)->get()->getResult();
                    // $onlineBranch = $online->get_where('branches',['branch_code'=>$branch_code])->result();


                    if (count($onlineBranch) > 0) {
                        $hold_percent = $onlineBranch[0]->percent;
                    } else {
                        $hold_percent = 1;
                    }

                    /**
                     * UPLOAD ALL RECORDS WITH STATUS == 1 TO ONLINE DB
                     */
                    foreach ($old_sales as $sale) {
                        if ($this->Sale->dbUp($online, $sale)) {
                            $redis->del($sale['id']); //TODO - UPDATE LAST TIMESTAMP
                            echo "<br/> uploaded and deleted " . $sale['id'];
                            $this->gu->updateLastSaleSyncTime();
                        } else {
                            $this->gu->log($sale['id'] . ' uploading all records: dbUp failed');
                        }

                    }


                    $hold = round($newCount * $hold_percent);

                    //at least save 1 record to local on sync
                    // if($newCount > 0){
                    //     $hold = ($hold == 0) ? 1 : $hold;
                    // }

                    // echo "<h2>OLD COUNT: $oldCount</h2>";
                    // echo "<h2>NEW COUNT: $newCount</h2>";
                    // echo "<p>HOLD: $hold</p>";

                    shuffle($new_sales);

                    /**
                     * PROCESS NEW RECORDS WITH %
                     */
                    for ($i = 0; $i < $newCount; $i++) {

                        $sale = $new_sales[$i];

                        // echo "<pre>";
                        // print_r($sale);
                        // echo "<br/>";

                        if ($i + 1 <= $hold) {

                            //save to local db
                            if ($this->Sale->dbUp($local, $sale)) {
                                $redis->hset($sale['id'], 'status', 1);
                            } else {
                                echo " local db up failed ";
                                $this->gu->log($sale['id'] . ' process new records: local db up failed');
                            }
                        }
                        //save to online db
                        /**
                         * Save to online db
                         */
                        if ($this->Sale->dbUp($online, $sale)) {
                            //delete record from redis TODO - UPDATE LAST TIMESTAMP
                            $redis->del($sale['id']);
                            $this->gu->updateLastSaleSyncTime();
                        } else {
                            echo " online db up failed ";
                            $this->gu->log($sale['id'] . ' process new records: online db up failed');
                        }

                    }

                }
                // return true;
                // session()->set('upload_message','Sales database updated.');
                echo "Sales database updated.";
            } else {
                // session()->set('upload_message','No cached data');
                echo "No cached data.";

            }

            $this->gu->enableSync();


        } catch (\Exception $e) {
            $this->gu->log('Something went wrong while trying to sync sales.');
            $this->gu->enableSync();
        }

    }



    public function enableSync()
    {
        $this->gu->enableSync();
        echo "Sync Enabled";
    }

    public function disableSync()
    {
        $this->gu->disableSync();
        echo "Sync Disabled";
    }



    /**
     * Sync Online & local database
     */
    public function sync()
    {
        $isOnlineServer = $this->gu->isServer();

        if ($isOnlineServer) {
            echo "Sync not needed here. ";
            exit;
        }

        $local = Database::connect('default', true);
        $online = Database::connect('online', true);

        $online->initialize();


        if (FALSE === $online->conn_id) {
            echo $log = date('d M Y h:i A') . " can't connect to online server. ";

            file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
            exit;
        }


        //get items table data of both tables
        $online_items = $online->table('items')->get()->getResult();
        $local_items = $local->table('items')->get()->getResult();

        $onlineJson = json_encode($online_items);
        $localJson = json_encode($local_items);

        $diff = strcmp($localJson, $onlineJson);

        if ($diff) {

            echo "Items update available. Updating items record... ";

            //getting all items record from online db
            //$online_items = $online->get('items')->result();
            $online_item_quantities = $online->table('item_quantities')->get()->getResult();
            $online_item_taxes = $online->table('items_taxes')->get()->getResult();
            $online_inventory = $online->table('inventory')->get()->getResult();

            //TODO - BACKUP OLD ITEM QUANTITIES
            $local_item_quantities = $local->table('item_quantities')->get()->getResult();
            $local_item_taxes = $local->table('items_taxes')->get()->getResult();

            try {
                //truncate local items table
                $local->transStart();

                $local->query("SET FOREIGN_KEY_CHECKS = 0");
                $local->table('ospos_item_quantities')->truncate();
                $local->table('ospos_items_taxes')->truncate();
                $local->table('ospos_inventory')->truncate();
                $local->table('ospos_items')->truncate();

                /**
                 * adding data in items table
                 */
                foreach ($online_items as $item) {
                    $builder = $local->table('items');
                    if (!$builder->insert($item)) {
                        echo " item error. sync failed.";
                        return false;
                    }
                }


                /**
                 * adding data in item taxes table
                 *
                 * TODO - RESTORE ITEM TAXES FROM LOCAL DB
                 *
                 */
                echo " Updating local item taxes from online . ";
                foreach ($online_item_taxes as $taxes) {
                    $builder = $local->table('items_taxes');
                    if (!$builder->insert($taxes)) {
                        echo " item tax error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in item quantity table
                 *
                 * TODO - RESTORE ITEM QUANTITY FROM LOCAL DB
                 *
                 */
                echo " Updating local item quantities from online . ";
                foreach ($online_item_quantities as $quantity) {
                    $builder = $local->table('item_quantities');
                    if (!$builder->insert($quantity)) {
                        echo " item quantity error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in inventory table
                 *
                 * TODO - RESTORE LOCAL ITEM INVENTORY
                 */
                foreach ($online_inventory as $inventory) {
                    $builder = $local->table('inventory');
                    if (!$builder->insert($inventory)) {
                        echo " item quantity error. sync failed.";
                        $local->transRollback();
                        return false;
                    }
                }


                /**
                 * restoring old item taxes
                 */
                echo " Restoring old item taxes. ";
                foreach ($local_item_taxes as $taxes) {
                    $itemTaxes['item_id'] = $taxes->item_id;
                    $itemTaxes['name'] = $taxes->name;
                    $itemTaxes['percent'] = $taxes->percent;

                    $builder = $local->table('items_taxes')
                        ->where($itemTaxes)
                        ->update($taxes);

                }
                echo " local Item Taxes Updated. ";

                /**
                 * restoring old item quantities
                 */
                echo " Restoring old item quantities. ";
                foreach ($local_item_quantities as $quantity) {
                    $itemQuantity['item_id'] = $quantity->item_id;
                    $itemQuantity['location_id'] = $quantity->location_id;

                    $builder = $local->table('item_quantities')
                        ->where($itemQuantity)
                        ->update($quantity);

                }
                echo " local Item Quantities Updated. ";



                /**
                 * Restore OLD local non protected Items
                 *
                 * only custom 10 should update from online
                 *
                 */
                foreach ($online_items as $item) {
                    if ($item->custom10 == "no") {
                        //find old local item
                        //set custom 10 to no
                        //update the item

                        foreach ($local_items as $oldItem) {
                            if ($item->item_id == $oldItem->item_id) {
                                $item_array['item_id'] = $oldItem->item_id;
                                $builder = $local->table('items');
                                $builder->where($item_array);

                                $oldItem->custom10 = "no";
                                $builder->update($oldItem);
                                break;
                            }
                        }
                    }
                }

                $local->query("SET FOREIGN_KEY_CHECKS = 1");

                //close transaction
                $local->transComplete();
                echo " Items Updated successfully.";
            } catch (\Exception $e) {
                $local->transRollback();
                $local->query("SET FOREIGN_KEY_CHECKS = 1");
                echo " items sync process failed. <br/>";
                return false;
            }





        } else {
            echo "Items database already up to date.";
            return true;
        }
    }

    public function sync_with_custom_price()
    {
        $isOnlineServer = $this->gu->isServer();

        if ($isOnlineServer) {
            echo "Sync not needed here. ";
            exit;
        }

        $local = Database::connect('default', TRUE);
        $online = Database::connect('online', TRUE);

        $online->initialize();

        if (FALSE === $online->conn_id) {
            echo $log = date('d M Y h:i A') . " can't connect to online server. ";

            file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
            exit;
        }


        //get items table data of both tables
        $online_items = $online->table('items')
            ->join('item_custom_price icp', 'icp.item_id = items.item_id AND icp.branch_code="' . $this->gu->getStoreBranchCode() . '"', 'left')
            ->select('icp.custom_id, icp.custom_cost_price, icp.custom_unit_price, items.*')
            ->orderBy('items.item_id')
            ->get()
            ->getResult();

        $local_items = $local->table('items')->get()->getResult();

        $onlineJson = json_encode($online_items);
        $localJson = json_encode($local_items);

        $diff = strcmp($localJson, $onlineJson);

        if ($diff) {

            echo "Items update available. Updating items record... ";

            //getting all items record from online db
            //$online_items = $online->get('items')->result();
            $online_item_quantities = $online->table('item_quantities')->get()->getResult();
            $online_item_taxes = $online->table('items_taxes')
                ->join('item_custom_price icp', 'icp.item_id = items_taxes.item_id AND icp.branch_code="' . $this->gu->getStoreBranchCode() . '"', 'left')
                ->select('icp.custom_id, icp.custom_tax_percent, items_taxes.*')
                ->orderBy('items_taxes.item_id')
                ->get()
                ->getResult();
            $online_inventory = $online->table('inventory')->get()->getResult();

            //TODO - BACKUP OLD ITEM QUANTITIES
            $local_item_quantities = $local->table('item_quantities')->get()->getResult();
            $local_item_taxes = $local->table('items_taxes')->get()->getResult();

            try {
                //truncate local items table
                $local->transStart();

                $local->query("SET FOREIGN_KEY_CHECKS = 0");
                $local->table('ospos_item_quantities')->truncate();
                $local->table('ospos_items_taxes')->truncate();
                $local->table('ospos_inventory')->truncate();
                $local->table('ospos_items')->truncate();

                /**
                 * adding data in items table
                 */
                foreach ($online_items as $item) {

                    if ($item->custom_id && $item->custom_cost_price != null && $item->custom_unit_price != null) {
                        $item->cost_price = $item->custom_cost_price;
                        $item->unit_price = $item->custom_unit_price;
                    }
                    unset($item->custom_id, $item->custom_cost_price, $item->custom_unit_price);
                    $builder = $local->table('items');
                    if (!$builder->insert($item)) {
                        echo " item error. sync failed.";
                        return false;
                    }
                }


                /**
                 * adding data in item taxes table
                 *
                 * TODO - RESTORE ITEM TAXES FROM LOCAL DB
                 *
                 */
                echo " Updating local item taxes from online . ";
                foreach ($online_item_taxes as $taxes) {

                    if ($taxes->custom_id && $taxes->custom_tax_percent != null) {
                        $taxes->percent = $taxes->custom_tax_percent;
                    }
                    unset($taxes->custom_id, $taxes->custom_tax_percent);
                    $builder = $local->table('items_taxes');
                    if (!$builder->insert($taxes)) {
                        echo " item tax error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in item quantity table
                 *
                 * TODO - RESTORE ITEM QUANTITY FROM LOCAL DB
                 *
                 */
                echo " Updating local item quantities from online . ";
                foreach ($online_item_quantities as $quantity) {
                    $builder = $local->table('item_quantities');
                    if (!$builder->insert($quantity)) {
                        echo " item quantity error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in inventory table
                 *
                 * TODO - RESTORE LOCAL ITEM INVENTORY
                 */
                foreach ($online_inventory as $inventory) {
                    $builder = $local->table('inventory');
                    if (!$builder->insert($inventory)) {
                        echo " item quantity error. sync failed.";
                        $local->transRollback();
                        return false;
                    }
                }


                /**
                 * restoring old item taxes
                 */
                echo " Restoring old item taxes. ";
                foreach ($local_item_taxes as $taxes) {
                    $itemTaxes['item_id'] = $taxes->item_id;
                    $itemTaxes['name'] = $taxes->name;
                    $itemTaxes['percent'] = $taxes->percent;

                    $builder = $local->table('items_taxes')
                        ->where($itemTaxes)
                        ->update($taxes);

                }
                echo " local Item Taxes Updated. ";

                /**
                 * restoring old item quantities
                 */
                echo " Restoring old item quantities. ";
                foreach ($local_item_quantities as $quantity) {
                    $itemQuantity['item_id'] = $quantity->item_id;
                    $itemQuantity['location_id'] = $quantity->location_id;

                    $builder = $local->table('item_quantities')
                        ->where($itemQuantity)
                        ->update($quantity);

                }
                echo " local Item Quantities Updated. ";



                /**
                 * Restore OLD local non protected Items
                 *
                 * only custom 10 should update from online
                 *
                 */
                foreach ($online_items as $item) {
                    if ($item->custom10 == "no") {
                        //find old local item
                        //set custom 10 to no
                        //update the item

                        foreach ($local_items as $oldItem) {
                            if ($item->item_id == $oldItem->item_id) {
                                $item_array['item_id'] = $oldItem->item_id;
                                $builder = $local->table('items');
                                $builder->where($item_array);

                                $oldItem->custom10 = "no";
                                $builder->update($oldItem);
                                break;
                            }
                        }
                    }
                }

                $local->query("SET FOREIGN_KEY_CHECKS = 1");

                //close transaction
                $local->transComplete();
                echo " Items Updated successfully.";
            } catch (\Exception $e) {
                $local->transRollback();
                $local->query("SET FOREIGN_KEY_CHECKS = 1");
                echo " items sync process failed. <br/>";
                return false;
            }





        } else {
            echo "Items database already up to date.";
            return true;
        }
    }


    public function sync_cake_suspended_sales()
    {


        if ($this->gu->canCakeSync() == false) {
            echo "Sync Disabled";
            exit;
        }

        $this->gu->disableCakeSync();
        $cake_app_ip = $this->appConfig->get('cake_app_ip');
        $apiUrl = 'http://' . $cake_app_ip . '/api/suspendedOrder';

        $response = file_get_contents($apiUrl);

        // Check for errors
        if ($response === false) {

            // Handle error
            $this->gu->enableCakeSync();
            echo "Connection failed.";
            return false;

        } else {

            $local = Database::connect('default', true);
            $responseData = json_decode($response, true);     // Process the response data
            $onlineJson = json_encode($responseData['suspended_sale']);
            $local_items = $local->table('cake_suspended')->get()->getResult();
            // $local_items = $local->get('cake_suspended')->result();
            $localJson = json_encode($local_items);
            $diff = strcmp($localJson, $onlineJson);

            if ($diff) {

                try {

                    $local->transStart();

                    $local->query("SET FOREIGN_KEY_CHECKS = 0");
                    $local->table('cake_suspended')->truncate();
                    $local->table('cake_suspended_items')->truncate();
                    $local->table('cake_suspended_items_taxes')->truncate();
                    $local->table('cake_suspended_payments')->truncate();

                    $online_items = $responseData['suspended_sale'];

                    foreach ($online_items as $item) {

                        if (!$local->table('cake_suspended')->insert($item)) {

                            echo " Cake suspended table error. sync failed.";
                            $this->gu->enableCakeSync();
                            return false;

                        }
                    }


                    /**
                     * adding cake suspended data in cake_suspended table
                     */
                    $online_suspended_items = $responseData['suspended_sale_items'];

                    foreach ($online_suspended_items as $item) {

                        if (!$local->table('cake_suspended_items')->insert($item)) {
                            echo "Cake suspended items table error. sync failed.";
                            $this->gu->enableCakeSync();
                            return false;
                        }
                    }

                    /**
                     * adding cake suspended taxes data in cake_suspended table
                     */
                    $online_suspended_items_taxes = $responseData['suspended_items_taxes'];

                    foreach ($online_suspended_items_taxes as $item) {

                        if (!$local->table('cake_suspended_items_taxes')->insert($item)) {

                            echo "Cake suspended items taxes table error. sync failed.";
                            $this->gu->enableCakeSync();
                            return false;
                        }
                    }


                    /**
                     * adding cake suspended payments data in cake_suspended table
                     */
                    //  $online_suspended_payments = $responseData['suspended_payments'];

                    //  foreach ($online_suspended_payments as $item) {

                    //     if (!$local->insert('cake_suspended_payments', $item)) {

                    //         echo " Cake suspended payments table error. sync failed.";
                    //         $this->gu->enableCakeSync();
                    //         return false;
                    //       }
                    //  }

                    $local->query("SET FOREIGN_KEY_CHECKS = 1");

                    //close transaction
                    $local->transComplete();
                    $this->gu->enableCakeSync();
                    echo " Sales Updated successfully.";


                } catch (\Exception $e) {
                    $this->gu->enableCakeSync();
                    $local->transRollback();
                    $local->query("SET FOREIGN_KEY_CHECKS = 1");
                    echo " Sales sync process failed. <br/>";
                    return false;
                }

            } else {
                $this->gu->enableCakeSync();
                echo "Already up to date.";
                return true;
            }


        }

    }


    public function sync_pizza_completed_orders()
    {

        // $this->gu->enablePizzaSync();

        if ($this->gu->canPizzaSync() == false) {
            echo "Sync Disabled";
            exit;
        }

        $this->gu->disablePizzaSync();
        $pizza_app_ip = $this->appConfig->get('pizza_app_ip');
        $apiUrl = 'http://' . $pizza_app_ip . '/api/get_completed_orders';

        $response = file_get_contents($apiUrl);
        // Check for errors
        if ($response === false) {

            // Handle error
            $this->gu->enablePizzaSync();
            echo "Connection failed.";
            return false;

        } else {

            $local = Database::connect('default', true);
            $responseData = json_decode($response, true);     // Process the response data
            $onlineJson = json_encode($responseData['completed_order']);
            $local_items = $local->table('order_completed')->get()->getResult();
            $localJson = json_encode($local_items);
            $diff = strcmp($localJson, $onlineJson);

            if ($diff) {

                try {

                    $local->transStart();

                    $local->query("SET FOREIGN_KEY_CHECKS = 0");
                    $local->table('order_completed')->truncate();
                    $local->table('order_completed_items')->truncate();


                    $online_completed_orders = $responseData['completed_order'];

                    foreach ($online_completed_orders as $order) {

                        if (!$local->table('order_completed')->insert($order)) {

                            echo "Order completed table error. sync failed.";
                            $this->gu->enablePizzaSync();
                            return false;

                        }
                    }


                    /**
                     * adding cake suspended data in cake_suspended table
                     */
                    $online_completed_orders_items = $responseData['completed_order_items'];

                    foreach ($online_completed_orders_items as $item) {

                        if (!$local->table('order_completed_items')->insert($item)) {
                            echo "Order completed items table error. sync failed.";
                            $this->gu->enablePizzaSync();
                            return false;
                        }
                    }


                    $local->query("SET FOREIGN_KEY_CHECKS = 1");

                    //close transaction
                    $local->transComplete();
                    $this->gu->enablePizzaSync();
                    echo " Sales Updated successfully.";


                } catch (\Exception $e) {
                    $this->gu->enablePizzaSync();
                    $local->transRollback();
                    $local->query("SET FOREIGN_KEY_CHECKS = 1");
                    echo " Sales sync process failed. <br/>";
                    return false;
                }

            } else {
                $this->gu->enablePizzaSync();
                echo "Already up to date.";
                return true;
            }


        }

    }
    /**
     * Sync Online & local database
     */
    public function sync_giftcards()
    {
        $isOnlineServer = $this->gu->isServer();

        if ($isOnlineServer) {
            echo "Sync not needed here. ";
            exit;
        }

        $local = Database::connect('default', TRUE);
        $online = Database::connect('online', TRUE);

        $online->initialize();

        if (FALSE === $online->conn_id) {
            echo $log = date('d M Y h:i A') . " can't connect to online server. ";

            file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
            exit;
        }


        //get giftcards table data of both tables
        $online_giftcards = $online->table('giftcards')->get()->getResult();
        $local_giftcards = $local->table('giftcards')->get()->getResult();

        $onlineJson = json_encode($online_giftcards);
        $localJson = json_encode($local_giftcards);

        $diff = strcmp($localJson, $onlineJson);

        if ($diff) {

            echo "Giftcard update available. Updating giftcards record... ";

            try {
                //truncate local giftcards table
                $local->transStart();

                $local->query("SET FOREIGN_KEY_CHECKS = 0");
                $local->table('ospos_giftcards')->truncate();

                /**
                 * adding data in giftcards table
                 */
                foreach ($online_giftcards as $item) {
                    $builder = $local->table('giftcards');
                    if (!$builder->insert($item)) {
                        echo " item error. sync failed.";
                        return false;
                    }
                }


                $local->query("SET FOREIGN_KEY_CHECKS = 1");

                //close transaction
                $local->transComplete();
                echo " Giftcard Updated successfully.";
            } catch (\Exception $e) {
                $local->transRollback();
                $local->query("SET FOREIGN_KEY_CHECKS = 1");
                echo " Giftcard sync process failed. <br/>";
                return false;
            }

        } else {
            echo "Giftcard database already up to date.";
            return true;
        }
    }


    public function sync_categories()
    {
        $isOnlineServer = $this->gu->isServer();

        if ($isOnlineServer) {
            echo "Sync not needed here. ";
            exit;
        }
        $local = Database::connect('default', TRUE);
        $online = Database::connect('online', TRUE);
        $online->initialize();


        if (FALSE === $online->conn_id) {
            echo $log = date('d M Y h:i A') . " can't connect to online server. ";

            file_put_contents(APPPATH . "logs/cron.log", $log, FILE_APPEND);
            exit;
        }


        //get items table data of both tables
        $online_items = $online->table('raw_items')->get()->getResult();
        $local_items = $local->table('raw_items')->get()->getResult();

        $onlineJson = json_encode($online_items);
        $localJson = json_encode($local_items);

        $diff = strcmp($localJson, $onlineJson);

        if ($diff) {

            echo "Items update available. Updating items record... ";

            //getting all items record from online db
            //$online_items = $online->get('items')->result();
            $online_item_quantities = $online->table('raw_item_quantities')->get()->getResult();
            $online_item_attributes = $online->table('raw_item_attributes')->get()->getResult();
            $online_inventory = $online->table('raw_inventory')->get()->getResult();

            //TODO - BACKUP OLD ITEM QUANTITIES
            $local_item_quantities = $local->table('raw_item_quantities')->get()->getResult();
            $local_item_attributes = $local->table('raw_item_attributes')->get()->getResult();

            try {
                //truncate local items table
                $local->transStart();

                $local->query("SET FOREIGN_KEY_CHECKS = 0");
                $local->table('ospos_raw_item_quantities')->truncate();
                $local->table('ospos_raw_item_attributes')->truncate();
                $local->table('ospos_raw_inventory')->truncate();
                $local->table('ospos_raw_items')->truncate();

                /**
                 * adding data in items table
                 */
                foreach ($online_items as $item) {
                    if (!$local->table('raw_items')->insert($item)) {
                        echo " item error. sync failed.";
                        return false;
                    }
                }

                /**
                 * adding data in item quantity table
                 *
                 * TODO - RESTORE ITEM QUANTITY FROM LOCAL DB
                 *
                 */
                echo " Updating local item quantities from online . ";
                foreach ($online_item_quantities as $quantity) {
                    if (!$local->table('raw_item_quantities')->insert($quantity)) {
                        echo " item quantity error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in item attributes table
                 *
                 * TODO - RESTORE ITEM ATTRIBUTE FROM LOCAL DB
                 *
                 */
                echo " Updating local item attributes from online . ";
                foreach ($online_item_attributes as $attribute) {
                    if (!$local->table('raw_item_attributes')->insert($attribute)) {
                        echo " item attribute error. sync failed. ";
                        $local->transRollback();
                        return false;
                    }
                }

                /**
                 * adding data in inventory table
                 *
                 * TODO - RESTORE LOCAL ITEM INVENTORY
                 */
                foreach ($online_inventory as $inventory) {
                    if (!$local->table('raw_inventory')->insert($inventory)) {
                        echo " item quantity error. sync failed.";
                        $local->transRollback();
                        return false;
                    }
                }


                /**
                 * restoring old item quantities
                 */
                echo " Restoring old item quantities. ";
                foreach ($local_item_quantities as $quantity) {
                    $itemQuantity['item_id'] = $quantity->item_id;
                    $itemQuantity['location_id'] = $quantity->location_id;


                    $local->table('raw_item_quantities')->where($itemQuantity)
                        ->update($quantity);

                }
                echo " local Item Quantities Updated. ";


                /**
                 * restoring old item attributes
                 */
                echo " Restoring old item attributes. ";
                foreach ($local_item_attributes as $attribute) {
                    $itemAttribute['attribute_id'] = $attribute->item_id;
                    $itemAttribute['item_id'] = $attribute->item_id;


                    $local->table('raw_item_attributes')->where($itemAttribute)
                        ->update($attribute);

                }
                echo " local Item Attributes Updated. ";



                /**
                 * Restore OLD local non protected Items
                 *
                 * only custom 10 should update from online
                 *
                 */
                foreach ($online_items as $item) {
                    if ($item->custom10 == "no") {
                        //find old local item
                        //set custom 10 to no
                        //update the item

                        foreach ($local_items as $oldItem) {
                            if ($item->item_id == $oldItem->item_id) {
                                $item_array['item_id'] = $oldItem->item_id;

                                $oldItem->custom10 = "no";
                                $local->table('raw_items')
                                    ->where($item_array)
                                    ->update($oldItem);
                                break;
                            }
                        }
                    }
                }

                $local->query("SET FOREIGN_KEY_CHECKS = 1");

                //close transaction
                $local->transComplete();
                echo " Items Updated successfully.";
            } catch (\Exception $e) {
                $local->transRollback();
                $local->query("SET FOREIGN_KEY_CHECKS = 1");
                echo " items sync process failed. <br/>";
                return false;
            }

        } else {
            echo "Items database already up to date.";
            return true;
        }
    }


}