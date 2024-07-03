<?php   include(APPPATH . 'Views/partial/header.php'); ?>

    <div class="pos_home">
        <div class="inner_block">
            <div class="pos_block">
                <h1><?php echo lang('common_lang.common_welcome_message'); ?></h1>

                <?php if (session()->get('upload_message')) { ?>
                <br>
                <div class="alert alert-dismissible alert-success print_hide"><a href="#" class="close" data-dismiss="alert" aria-label="close">×</a><?php echo session()->get('upload_message'); ?></div>
                <?php session()->remove('upload_message'); } ?>

                <ul>
                <?php
                    $i = 0;
                    foreach ($allowed_modules as $module) {
                        if($module->module_id != 'receivings' && $module->module_id != 'customers') {
                            ?>
                            <li <?php echo (++$i % 4 == 0) ? "class='last'" : '' ?>>
                                <a href='<?php echo base_url("$module->module_id"); ?>'
                                title="<?php echo lang('module_lang.module_' . $module->module_id . '_desc'); ?>">
                                <div class="icon">
                                    <img
                                        src="<?php echo base_url() . 'images/menubar/' . $module->module_id . '.png'; ?>"
                                        alt="Menubar Image">
                                </div>
                                <div
                                    class="heading"><?php echo lang("module_lang.module_" . $module->module_id) ?></div>
                                </a></li>
                            <?php
                        }
                    }
                    ?>
                                   <!-- <li class="last"><a href="#">
                                           <div class="icon"><img src="<?php //echo base_url() . 'images/menubar/upload.png'; ?>" alt=""></div>
                                           <div class="heading">Upload</div>
                                       </a></li> -->
                        </ul>
                <div class="clear"></div>
            </div>
        </div>
        <!--inner_block-->
    </div><!--pos_home-->


    <?php include(APPPATH . 'Views/partial/footer.php'); ?>