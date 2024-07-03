<?php  $request = \Config\Services::request();  ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/> 
        <title><?= $appData['company'] . ' | Point of Sale' ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('images/favicon.ico') ?>">
        <link rel="stylesheet" type="text/css" href="<?= base_url('dist/bootswatch/' . (empty($appData['theme']) ? 'flatly' : $appData['theme']) . '/bootstrap.min.css') ?>"/>
        <link href="<?= base_url('fonts/fonts.css') ?>" rel="stylesheet" type="text/css">
        
        <!-- Include CSS files from npm packages -->
        <link rel="stylesheet" href="<?= base_url('node_modules/jquery-ui-dist/jquery-ui.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/bootstrap3-dialog/dist/css/bootstrap-dialog.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/jasny-bootstrap/dist/css/jasny-bootstrap.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/bootstrap-select/dist/css/bootstrap-select.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/bootstrap-table/dist/bootstrap-table.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/bootstrap-daterangepicker/daterangepicker.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/chartist/dist/chartist.min.css') ?>">
        <link rel="stylesheet" href="<?= base_url('node_modules/chartist-plugin-tooltip/dist/chartist-plugin-tooltip.css') ?>">
        <!-- End CSS files from npm packages -->
        
        <link rel="stylesheet" type="text/css" href="<?= base_url('dist/style.css') ?>">
        <!-- Include custom CSS files -->
        <link rel="stylesheet" type="text/css" href="<?= base_url('css/bootstrap.autocomplete.css') ?>">
        <link rel="stylesheet" type="text/css" href="<?= base_url('css/invoice.css') ?>">
        <link rel="stylesheet" type="text/css" href="<?= base_url('css/ospos.css') ?>">

        <!-- Include other custom CSS files as needed -->
        
        <!-- Include JS files from npm packages -->
        <script src="<?= base_url('node_modules/jquery/dist/jquery.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/jquery-form/dist/jquery.form.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/jquery-validation/dist/jquery.validate.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/jquery-ui-dist/jquery-ui.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap/dist/js/bootstrap.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap3-dialog/dist/js/bootstrap-dialog.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/jasny-bootstrap/dist/js/jasny-bootstrap.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap-select/dist/js/bootstrap-select.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap-table/dist/bootstrap-table.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/bootstrap-daterangepicker/daterangepicker.js') ?>"></script>
        <script src="<?= base_url('node_modules/chartist/dist/chartist.min.js') ?>"></script>
        <script src="<?= base_url('node_modules/chartist-plugin-tooltip/dist/chartist-plugin-tooltip.min.js') ?>"></script>
        <!-- End JS files from npm packages -->
        
        <!-- Include custom JS files -->
        <script src="<?= base_url('js/some-custom-script.js') ?>"></script>
        <!-- Include other custom JS files as needed -->
        <style type="text/css">
        html {
            overflow: auto;
        }
    </style>
    </head>
<body>
<div class="wrapper">
    <header id="header" class="topbar">
        <div class="inner_block">
        <div class="logo"><a href="<?= base_url() ?>"><img src="<?= base_url('images/tehzeeb/logo.png') ?>" alt=""></a></div>
            <div class="date_time_block">
                <div id="liveclock"
                     class="time_col">

                     <?php echo date($appData['dateformat'] . ' ' . $appData['timeformat']); ?>
                    </div>


                <div class="login_col">
                <div class="login_name">
    &nbsp;<?= $user_info->first_name . " " . $user_info->last_name .
    ($request->getGet("debug") == "true" ? $session->get('session_sha1') : "") ?>
    <?= "@ " . $gu->getStoreBranchCode() . "-" . $gu->getStoreSystemCode() ?>&nbsp;
</div>

                    <a href="<?php echo base_url(); ?>home/logout" class="login_button">Logout</a>
                    <

                    <?php
                    $sales_count = $gu->getCacheSalesCount();
                    if($gu->isServer()):
                    ?>
                        <a class="login_button">Online Server</a>
<!--                        <a class="login_button" href="--><?php //echo base_url(); ?><!--sales/upload">Upload (--><?php //echo $sales_count; ?><!--)</a>-->
<!--                        <a class="login_button" href="--><?php //echo base_url(); ?><!--items/sync">Sync</a>-->

                    <?php
                    endif;
                    if(config('test_mode')):
                    ?>
                    <a class="login_button" style="color:blue; font-weight: bold;">Test Mode</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
     

    </header>

<section id="body">
