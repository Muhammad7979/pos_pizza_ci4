<?php echo form_open('config/save_message', array('id' => 'message_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal')); ?>
	<div id="config_wrapper">
		<fieldset id="config_message">
			<div id="required_fields_message"><?php echo lang('common_lang.common_fields_required_message'); ?></div>
			<ul id="message_error_message_box" class="error_message_box"></ul>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_msg_uid'), $appData['msg_uid'], array('class' => 'control-label col-xs-2 required')); ?>
				<div class="col-xs-4">
					<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-user"></span></span>
						<?php echo form_input(array(
							'name' => 'msg_uid',
							'id' => 'msg_uid',
							'class' => 'form-control input-sm required',
							'value'=>$appData['msg_uid'])); ?>
					</div>
				</div>
			</div>

			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_msg_pwd'), 'msg_pwd', array('class' => 'control-label col-xs-2 required')); ?>
				<div class="col-xs-4">
					<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-lock"></span></span>
						<?php echo form_password(array(
							'name' => 'msg_pwd',
							'id' => 'msg_pwd',
							'class' => 'form-control input-sm required',
							'value'=>$appData['msg_pwd'])); ?>
					</div>
				</div>
			</div>
			
			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_msg_src'), 'msg_src', array('class' => 'control-label col-xs-2 required')); ?>
				<div class="col-xs-4">
				<div class="input-group">
						<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-bullhorn"></span></span>
						<?php echo form_input(array(
							'name' => 'msg_src',
							'id' => 'msg_src',
							'class' => 'form-control input-sm required',
							'value'=>$appData['msg_src'] == null ? $appData['company'] : $appData['msg_src']));?>
					</div>
				</div>
			</div>
			
			<div class="form-group form-group-sm">	
				<?php echo form_label(lang('config_lang.config_msg_msg'), 'msg_msg', array('class' => 'control-label col-xs-2')); ?>
				<div class='col-xs-4'>
					<?php echo form_textarea(array(
						'name' => 'msg_msg',
						'id' => 'msg_msg',
						'class' => 'form-control input-sm',
						'value'=>$appData['msg_msg'],
						'placeholder'=>lang('config_lang.config_msg_msg_placeholder')));?>
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
$(document).ready(function()
{
	$('#message_config_form').validate($.extend(form_support.handler, {

		errorLabelContainer: "#message_error_message_box",

		rules: 
		{
			msg_uid: "required",
			msg_pwd: "required",
			msg_src: "required"
   		},

		messages: 
		{
			msg_uid: "<?php echo lang('config_lang.config_msg_uid_required'); ?>",
			msg_pwd: "<?php echo lang('config_lang.config_msg_pwd_required'); ?>",
			msg_src: "<?php echo lang('config_lang.config_msg_src_required'); ?>"
		}
	}));
});
</script>