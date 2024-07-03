<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
         <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <base href="<?php echo base_url(); ?>"/>
        <title><?= $appData['company'] . ' | Point of Sale' ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('images/favicon.ico') ?>">
        <link rel="stylesheet" type="text/css" href="<?= base_url('dist/bootswatch/' . (empty($appData['theme']) ? 'flatly' : $appData['theme']) . '/bootstrap.min.css') ?>"/>
        <link rel="stylesheet" href="<?= base_url('dist/bootswatch/'.$appData['theme'].'/bootstrap.min.css') ?>">
        <link href="<?= base_url('fonts/fonts.css') ?>" rel="stylesheet" type="text/css">
    
        <?php if (cookie('debug') == "true" || request()->getGet("debug") == "true") : ?>
    
        <!-- Include css files -->
        <link rel="stylesheet" type="text/css" href="<?= base_url('dist/daterangepicker.css') ?>">
        <link rel="stylesheet" type="text/css" href="<?= base_url('dist/jquery-ui.min.css') ?>">
        <script type="text/javascript" src="<?= base_url('bootstrap-dialog/dist/js/bootstrap-dialog.min.css') ?>"></script>



       
   <!-- start css template tags -->
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/bootstrap.autocomplete.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/invoice.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/ospos.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/ospos_print.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/popupbox.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/receipt.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/register.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/reports.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('css/style.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('bootstrap-table/dist/bootstrap-table.min.css') ?>">


     <!-- Include JS files from npm packages -->
     <script type="text/javascript" src="<?= base_url('dist/jquery.min.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('dist/jquery.form.min.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('dist/jquery.validate.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('jquery-ui/dist/jquery-ui.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('bootstrap-dialog/dist/js/bootstrap-dialog.min.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('jasny-bootstrap/dist/js/jasny-bootstrap.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('bootstrap-select/dist/js/bootstrap-select.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('bootstrap-table/src/bootstrap-table.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('bootstrap-table/dist/extensions/export/bootstrap-table-export.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('dist/moment.min.js') ?>"></script>
     <script type="text/javascript" src="<?= base_url('html2canvas/dist/html2canvas.js') ?>"></script>

     <script type="text/javascript" src="<?= base_url('bootstrap-table/dist/bootstrap-table-locale-all.min.js') ?>"></script>

     

     <!-- Include CSS files from npm packages -->
     <!-- <script src="https://cdnjs.cloudflasre.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> -->
     <!-- start js template tags -->
        <script type="text/javascript" src="<?= base_url('js/manage_tables.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('js/imgpreview.full.jquery.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('js/nominatim.autocomplete.js') ?>"></script>
        
        
        <!-- end js template tags -->
        
        <?php else : ?>
            
            <!-- start mincss template tags -->
            
            
            <link rel="stylesheet" type="text/css" href="<?= base_url('dist/jquery-ui.css') ?>">
            <link rel="stylesheet" type="text/css" href="<?= base_url('dist/opensourcepos.min.css') ?>">
            <link rel="stylesheet" type="text/css" href="<?= base_url('dist/style.css') ?>">
            <!-- end mincss template tags -->
            
            <!-- start minjs template tags -->
            <script type="text/javascript"  src="<?= base_url('dist/opensourcepos.min.js') ?>"></script>
            <script type="text/javascript" src="<?= base_url('dist/bootstrap.min.js') ?>"></script>
            <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> -->
            <script type="text/javascript"  src="<?= base_url('bootstrap/dist/js/bootstrap.min.js') ?>"></script>

         <!-- end minjs template tags -->
         <?php endif; ?>    
         <!-- End CSS files from npm packages -->
         
         <!-- Include custom CSS files -->
         
         
         <!-- Include other custom CSS files as needed -->
         
         
         
         
         <!-- End JS files from npm packages -->
         
         <!-- Include custom JS files -->
         <!-- Include other custom JS files as needed -->
         
         <?php include(APPPATH . 'Views/partial/header_js.php'); ?>
         <?php include(APPPATH . 'Views/partial/lang_lines.php'); ?>
         <style type="text/css">
             html {
                 overflow: auto;
        }
        </style>

        <script>
            $(document).ready(function(){
                $.notifyDefaults({
	placement: {
		from: '<?php echo $appData['notify_vertical_position'] ?>',
        align: '<?php echo $appData['notify_horizontal_position'] ?>'

	}});
            });
        </script>
       
    <!-- <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script> -->
<!-- <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script> -->
<!-- <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css"> -->
<!-- <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script> -->
    </head>
    <body>
<?php  $request = \Config\Services::request();  ?>

    <div class="wrapper">
    <header id="header" class="topbar">
        <div class="inner_block">
        <div class="logo"><a href="<?= base_url() ?>"><img src="<?= base_url('uploads/'.$appData['company_logo']) ?>" width="317" height="75" alt=""></a></div>
            <div class="date_time_block">
                <div id="liveclock"
                     class="time_col">

                    </div>


                <div class="login_col">
                <div class="login_name">
    &nbsp;<?= $user_info->first_name . " " . $user_info->last_name .
    ($request->getGet("debug") == "true" ? $session->get('session_sha1') : "") ?>
    <?= "@ " . $gu->getStoreBranchCode() . "-" . $gu->getStoreSystemCode() ?>&nbsp;
    
                            <!-- Notification bell icons and dropdown list -->
                            <div class="dropdown" style="float: right; padding: 0px 13px 0px 0px">
                            <a href="#" onclick="return false;" role="button" data-toggle="dropdown" id="dropdownMenu1" data-target="#" style="float: left" aria-expanded="true">
                                <i class="glyphicon glyphicon-bell" style="font-size: 20px; float: left; color: black">
                                </i>
                            </a>
                            <span class="badge badge-danger" id="notificationsCount<?php echo $user_info->person_id ?>"><?php echo count($user_notifications); ?></span>
                            <ul class="dropdown-menu dropdown-menu-left pull-right" role="menu" aria-labelledby="dropdownMenu1">
                                <li role="presentation">
                                    <a style="cursor: default;" class="dropdown-menu-header">Notifications</a>
                                </li>
                                <ul class="timeline timeline-icons timeline-sm" id="newNotifications<?php echo $user_info->person_id ?>" style="padding:10px;width:300px; max-height: 350px;overflow-y: auto;">
                                    <?php 
                                        if (empty($user_notifications)) {
                                            echo '<p id="para'.$user_info->person_id.'">No New Notifications</p>';
                                        }else{
                                            foreach ($user_notifications as $notification) {
                                    ?>
                                        <li>
                                            <?php if ($notification['category']==0) { ?>
                                                <?php
                                                    if($counter_notifications_url==1){
                                                ?>
                                                <a href="javascript:" class="text-primary">
                                                <?php }else{ ?>
                                                <a href="<?php echo base_url().'counter_orders/updatenotification/'.$notification['order_id'].'/'.$notification['id']; ?>" class="text-primary">
                                                <?php } ?>
                                            <?php }elseif ($notification['category']==2 || $notification['category']==4) { ?>
                                                <a href="<?php echo base_url().'raw_orders/updatenotification/'.$notification['order_id'].'/'.$notification['id']; ?>" class="text-primary">
                                            <?php }if ($notification['category']==3) { ?>
                                                <a href="<?php echo base_url().'store_orders/updatenotification/'.$notification['order_id'].'/'.$notification['id']; ?>" class="text-primary">
                                            <?php } ?>
                                                <p>
                                                    <?php echo $notification['details'] ?>
                                                    
                                                    <span class="timeline-date"><?php echo $notification['created_at'] ?></span>
                                                </p>
                                            </a>
                                        </li>
                                    <?php
                                            }
                                        }
                                    ?>
                                </ul>
                                <li role="presentation">
                                    <a href="<?php echo base_url().'notifications' ?>" class="dropdown-menu-footer">View All Notifications</a>
                                </li>
                            </ul>
                        </div>
</div>

<a href="<?php echo base_url('home/logout'); ?>" class="login_button">Logout</a>
                    <a href="<?= current_url() ?>" id="reload_btn" class="login_button">Reload</a>


                    <?php
                    $sales_count = $gu->getCacheSalesCount();
                    if($gu->isServer() && $appData['test_mode']):
                        
                    ?>
                        <a  class="login_button">Online Server  </a>

                 
                    <a class="login_button" style="color:blue; font-weight: bold;">Test Mode</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
     

    </header>


<section id="body">