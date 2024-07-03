    <link rel='stylesheet' href='<?php echo base_url('dist/bootstrap-tagsinput.css'); ?>'>
    <script src='<?php echo base_url('dist/bootstrap3-typeahead.min.js'); ?>'></script>
    <script src='<?php echo base_url('dist/bootstrap-tagsinput.js'); ?>'></script>
  
    <link rel='stylesheet' href='<?php echo base_url('dist/select2.min.css'); ?>'>
    <script src='<?php echo base_url('dist/select2.min.js'); ?>'></script>

<div id="required_fields_message"><?php echo  lang ('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('vendors/save/'.$person_info->person_id, array('id'=>'vendor_form', 'class'=>'form-horizontal')); ?>
<ul class="nav nav-tabs nav-justified" data-tabs="tabs">
    <li class="active" role="presentation">
        <a data-toggle="tab"
           href="#vendor_basic_info"><?php echo lang("vendors_lang.vendors_basic_information"); ?></a>
    </li>
    <li role="presentation">
        <a data-toggle="tab" href="#vendor_stores_info"><?php echo lang("vendors_lang.vendors_store_info"); ?></a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade in active" id="vendor_basic_info">

        <fieldset>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('vendors_lang.vendors_company_name'), 'company_name', array('class'=>'required control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                        'name'=>'company_name',
                        'id'=>'company_name_input',
                        'class'=>'form-control input-sm',
                        'value'=>$person_info->company_name)
                        );?>
                </div>
            </div>

            <?php echo view("people/form_basic_info"); ?>

            <div class="form-group form-group-sm">  
                <?php echo form_label(lang('vendors_lang.vendors_account_number'), 'account_number', array('class'=>'control-label col-xs-3')); ?>
                <div class='col-xs-8'>
                    <?php echo form_input(array(
                        'name'=>'account_number',
                        'id'=>'account_number',
                        'class'=>'form-control input-sm',
                        'value'=>$person_info->account_number)
                        );?>
                </div>
            </div>
        </fieldset>

    </div>
    <?php $selected = empty($selected_stores) ? []:$selected_stores; ?>
    <div class="tab-pane" id="vendor_stores_info">
        <fieldset style="min-height: 300px;">
            <div id="required_fields_message"><?php echo lang('vendors_lang.required_stores_message'); ?></div>
            <div class="form-group form-group-sm">
                <?php echo form_label(lang('vendors_lang.vendors_store_name'), 'store_ids', array('class'=>'required control-label col-xs-3')); ?>
                <div class="col-xs-8">
                    <?php echo form_multiselect('store_ids[]', $stores, $selected, array('id' => 'store_ids', 'class' => 'form-control js-example-basic-multiple', 'multiple'=>'multiple')); ?>
                </div>
            </div>
        </fieldset>
    </div>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{    
    $('.js-example-basic-multiple').select2({
        placeholder: 'Select Multipe Stores',
        allowClear: true,
        closeOnSelect: false,
        enable: false
    });

    $('input[name=items_list]').tagsinput({
        typeahead: {
            // source: ['Green Pepper / Shimla Mirch', 'Cabage / BandGobhi', 'Chilli / Mirch', 'Carrot/ Gajar', 'Coriander / Dhania','Cucumber / Kheera', 'Garlic / Lehsan', 'Mustard Leaves / Salaad pata', 'Onion / Pyaz', 'Potato / Aalu', 'Tomatoes / Tamatar', 'Lemon / leemu', 'Spinach / Palak', 'Peas / Matar', 'Mango / Aam', 'Mint / Podina', 'GrapeFruit / Chakotra', 'Banana / Kayla', 'Guave / Amrood' ,'Peach / Aaru', 'Stawberry / Stawberry', 'Plum / Aalu bukhara', 'Pineapple / An-anaas', 'Grapes / Angoor', 'Papaya / Papeeta', 'Pomegranate / Anaar', 'Apple / Sayeb' ,'Sapodilla / Cheeku', 'Orange / Kinnow'],
            source: <?php echo $vendor_items ?>,
            afterSelect: function() {
                this.$element[0].value = '';
            }
         }
    });
    
    $('.bootstrap-tagsinput').addClass('form-control');

    $(".bootstrap-tagsinput input").focus(function(){
       $(this).parent().addClass("blackbg");
    }).blur(function(){
        $(this).parent().removeClass("blackbg");
    });

    $('#vendor_form').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) { 
            e.preventDefault();
            return false;
        }
    });
    $.validator.setDefaults({ignore: []});

    $('#vendor_form').validate($.extend({
        submitHandler:function(form)
        {   
            $('.bootstrap-dialog-footer').prepend('<div class="guLoader30px"></div>');
            $('#submit').hide();
            $(form).ajaxSubmit({
            success:function(response)
            {
                dialog_support.hide();
                table_support.handle_submit('<?php echo site_url('vendors'); ?>', response);
            },
            dataType:'json'
        });

        },
        rules:
        {
            company_name: "required",
            first_name: "required",
            last_name: "required",
            items_list: "required",
            'store_ids[]': "required",
            email: "email"
        },
        messages: 
        {
            company_name: "<?php echo lang('vendors_lang.vendors_company_name_required'); ?>",
            first_name: "<?php echo lang('common_lang.common_first_name_required'); ?>",
            last_name: "<?php echo lang('common_lang.common_last_name_required'); ?>",
            items_list: "Atleast 1 Item is required.",
            'store_ids[]': "Atleast 1 Store is required.",
            email: "<?php echo lang('common_lang.common_email_invalid_format'); ?>"
        }
    }, form_support.error));
});

</script>