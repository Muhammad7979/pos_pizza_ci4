<?php include(APPPATH . 'Views/partial/header.php'); ?>

<div class="inner_block">

    <ul class="nav nav-tabs" data-tabs="tabs">
        <li class="active" role="presentation">
            <a data-toggle="tab" href="#info_tab"
               title="<?php echo lang('config_lang.config_info_configuration'); ?>"><?php echo lang('config_lang.config_info'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#general_tab"
               title="<?php echo lang('config_lang.config_general_configuration'); ?>"><?php echo lang('config_lang.config_general'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#locale_tab"
               title="<?php echo lang('config_lang.config_locale_configuration'); ?>"><?php echo lang('config_lang.config_locale'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#barcode_tab"
               title="<?php echo lang('config_lang.config_barcode_configuration'); ?>"><?php echo lang('config_lang.config_barcode'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#stock_tab"
               title="<?php echo lang('config_lang.config_location_configuration'); ?>"><?php echo lang('config_lang.config_location'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#receipt_tab"
               title="<?php echo lang('config_lang.config_receipt_configuration'); ?>"><?php echo lang('config_lang.config_receipt'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#invoice_tab"
               title="<?php echo lang('config_lang.config_invoice_configuration'); ?>"><?php echo lang('config_lang.config_invoice'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#email_tab"
               title="<?php echo lang('config_lang.config_email_configuration'); ?>"><?php echo lang('config_lang.config_email'); ?></a>
        </li>
        <li role="presentation">
            <a data-toggle="tab" href="#message_tab"
               title="<?php echo lang('config_lang.config_message_configuration'); ?>"><?php echo lang('config_lang.config_message'); ?></a>
        </li>
<!--        <li role="presentation">-->
<!--            <a data-toggle="tab" href="#license_tab"-->
<!--               title="--><?php //echo lang('config_lang.config_license_configuration'); ?><!--">--><?php //echo lang('config_lang.config_license'); ?><!--</a>-->
<!--        </li>-->
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade in active" id="info_tab">
            <?php include(APPPATH . 'Views/configs/info_config.php'); ?>
        </div>
        <div class="tab-pane" id="general_tab">
           <?php include(APPPATH . 'Views/configs/general_config.php'); ?> 
        </div>
        <div class="tab-pane" id="locale_tab">
           <?php include(APPPATH . 'Views/configs/locale_config.php'); ?> 
        </div>
        <div class="tab-pane" id="barcode_tab">
          <?php include(APPPATH . 'Views/configs/barcode_config.php'); ?>  
        </div>
        <div class="tab-pane" id="stock_tab">
          <?php include(APPPATH . 'Views/configs/stock_config.php'); ?>  
        </div>
        <div class="tab-pane" id="receipt_tab">
           <?php include(APPPATH . 'Views/configs/receipt_config.php'); ?> 
        </div>
        <div class="tab-pane" id="invoice_tab">
          <?php include(APPPATH . 'Views/configs/invoice_config.php'); ?>  
        </div>
        <div class="tab-pane" id="email_tab">
          <?php include(APPPATH . 'Views/configs/email_config.php'); ?>  
        </div>
        <div class="tab-pane" id="message_tab">
          <?php include(APPPATH . 'Views/configs/message_config.php'); ?>  
        </div>
        <div class="tab-pane" id="license_tab">
          <?php include(APPPATH . 'Views/configs/license_config.php'); ?> 
        </div>
    </div>
</div>

<?php include(APPPATH . 'Views/partial/footer.php'); ?>