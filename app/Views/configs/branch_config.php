<?php echo form_open('config/save_branch', array('id' => 'branch_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>
<div id="config_wrapper">
    <fieldset id="config_info">
        <div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>
        <ul id="info_error_message_box" class="error_message_box"></ul>

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('config_lang.config_branch_name'), 'branch_name', array('class' => 'control-label col-xs-2 required')); ?>
         
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'branch_name',
                    'id' => 'branch_name',
                    'class' => 'form-control input-sm required',
                    'value' => isset($appData['branch_name'])?$appData['branch_name']:'')); ?>
            </div>
        </div>
       
       

        <div class="form-group form-group-sm">
            <?php echo form_label(lang('config_lang.config_branch_address'), 'address', array('class' => 'control-label col-xs-2 required')); ?>
            <div class='col-xs-6'>
                <?php echo form_textarea(array(
                    'name' => 'branch_address',
                    'id' => 'branch_address',
                    'class' => 'form-control input-sm required',
                    'value' => isset($appData['branch_address'])?$appData['branch_address']:'')); ?>
            </div>
        </div>


        <div class="form-group form-group-sm">
            <?php echo form_label(lang('config_lang.config_branch_phone'), 'phone', array('class' => 'control-label col-xs-2 required')); ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-phone-alt"></span></span>
                    <?php echo form_input(array(
                        'name' => 'branch_phone',
                        'id' => 'branch_phone',
                        'class' => 'form-control input-sm required',
                        'value' =>isset($appData['branch_phone'])?$appData['branch_name']:'')); ?>
                </div>
            </div>
        </div>

         <!--	branch code		gu-->
         <div class="form-group form-group-sm">
            <?php echo form_label(lang('config_lang.config_branch_code'), 'branch_code', array('class' => 'control-label col-xs-2 required')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'branch_code',
                    'id' => 'branch_code',
                    'class' => 'form-control input-sm required',
                    'value' => isset($appData['branch_code'])?$appData['branch_code']:'')); ?>
            </div>
        </div>
        <!--	system code		gu-->
        <div class="form-group form-group-sm">
            <?php echo form_label("System ID", 'system_code', array('class' => 'control-label col-xs-2 required')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'system_code',
                    'id' => 'system_code',
                    'class' => 'form-control input-sm required',
                    'value' => isset($appData['system_code'])?$appData['system_code']:'')); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label("STRN", 'strn', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'strn',
                    'id' => 'strn',
                    'class' => 'form-control input-sm',
                    'value' =>isset($appData['strn'])?$appData['strn']:'')); ?>
            </div>
        </div>
        <div class="form-group form-group-sm">
            <?php echo form_label("NTN", 'ntn', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'ntn',
                    'id' => 'ntn',
                    'class' => 'form-control input-sm',
                    'value' => isset($appData['ntn'])?$appData['ntn']:'')); ?>
            </div>
        </div>

                
      

        <div class="form-group form-group-sm">
            <?php echo form_label("FBR Post Url", 'fbr_post_url', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'fbr_post_url',
                    'id' => 'fbr_post_url',
                    'class' => 'form-control input-sm',
                    'value' => isset($appData['fbr_post_url'])?$appData['fbr_post_url']:'')); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label("FBR Bearer Token", 'fbr_bearer_token', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'fbr_bearer_token',
                    'id' => 'fbr_bearer_token',
                    'class' => 'form-control input-sm',
                    'value' => isset($appData['fbr_bearer_token'])?$appData['fbr_bearer_token']:'')); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label("FBR PCT/Access Code", 'fbr_pct_code', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'fbr_pct_code',
                    'id' => 'fbr_pct_code',
                    'class' => 'form-control input-sm',
                    'value' => isset($appData['fbr_pct_code'])?$appData['fbr_pct_code']:'')); ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?php echo form_label("FBR POS Id", 'fbr_pos_id', array('class' => 'control-label col-xs-2')); ?>
            <div class='col-xs-6'>
                <?php echo form_input(array(
                    'name' => 'fbr_pos_id',
                    'id' => 'fbr_pos_id',
                    'class' => 'form-control input-sm',
                    'value' => isset($appData['fbr_pos_id'])?$appData['fbr_pos_id']:'')); ?>
            </div>
        </div>        

        <?php echo form_submit(array(
				'name' => 'submit_form',
				'id' => 'submit_form',
				'value'=>lang('common_lang.common_submit'),
				'class' => 'btn btn-primary btn-sm pull-right')); ?>
		</fieldset>
      


            
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    
    //validation and submit handling
    $(document).ready(function () {
      

        $('#branch_config_form').validate($.extend(form_support.handler, {

            errorLabelContainer: "#info_error_message_box",

            rules: {
                branch_name: "required",
                branch_code: "required",
                branch_address: "required",
                system_code: "required",
                
            },

            messages: {
                branch_name: "<?php echo lang('config_lang.config_branch_name_required'); ?>",
                branch_code: "<?php echo lang('common_lang.config_branch_code_required'); ?>",
                branch_address: "<?php echo lang('config_lang.config_address_required'); ?>",
                branch_phone: "<?php echo lang('config_lang.config_phone_required'); ?>",
            }
        }));
    });
</script>