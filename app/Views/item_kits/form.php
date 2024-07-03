<div id="required_fields_message"><?= lang('common_lang.common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open('item_kits/save' . $item_kit_info->item_kit_id, ['id' => 'item_kit_form', 'class' => 'form-horizontal']) ?>
<fieldset id="item_kit_basic_info">
    <div class="form-group form-group-sm">
        <?= form_label(lang('item_kits_lang.item_kits_name'), 'name', ['class' => 'required control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_input([
                'name' => 'name',
                'id' => 'name',
                'class' => 'form-control input-sm',
                'value' => $item_kit_info->name
            ])
            ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('item_kits_lang.item_kits_description'), 'description', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_textarea([
                'name' => 'description',
                'id' => 'description',
                'class' => 'form-control input-sm',
                'value' => $item_kit_info->description
            ])
            ?>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <?= form_label(lang('item_kits_lang.item_kits_add_item'), 'item', ['class' => 'control-label col-xs-3']) ?>
        <div class="col-xs-8">
            <?= form_input([
                'name' => 'item',
                'id' => 'item',
                'class' => 'form-control input-sm'
            ])
            ?>
        </div>
    </div>

    <table id="item_kit_items" class="table table-striped table-hover">
        <thead>
            <tr>
                <th width="10%"><?= lang('common_lang.common_delete') ?></th>
                <th width="70%"><?= lang('item_kits_lang.item_kits_item') ?></th>
                <th width="20%"><?= lang('item_kits_lang.item_kits_quantity') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($item_kit_items as $item_kit_item) : ?>
                <tr>
                    <td><a href="#" onclick="return delete_item_kit_row(this);"><span class="glyphicon glyphicon-trash"></span></a></td>
                    <td><?= $item_kit_item['name'] ?></td>
                    <td><input class="quantity form-control input-sm" id="item_kit_item_<?= $item_kit_item['item_id'] ?>" name="item_kit_item[<?= $item_kit_item['item_id'] ?>]" value="<?= to_quantity_decimals($item_kit_item['quantity']) ?>" /></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#item").autocomplete({
            source: '<?= site_url("items/suggest") ?>',
            minLength: 0,
            autoFocus: false,
            delay: 10,
            appendTo: ".modal-content",
            select: function(e, ui) {
                if ($("#item_kit_item_" + ui.item.value).length == 1) {
                    $("#item_kit_item_" + ui.item.value).val(parseFloat($("#item_kit_item_" + ui.item.value).val()) + 1);
                } else {
                    $("#item_kit_items").append("<tr><td><a href='#' onclick='return delete_item_kit_row(this);'><span class='glyphicon glyphicon-trash'></span></a></td><td>" + ui.item.label + "</td><td><input class='quantity form-control input-sm' id='item_kit_item_" + ui.item.value + "' type='text' name='item_kit_item[" + ui.item.value + "]' value='1'/></td></tr>");
                }
                $("#item").val("");
                return false;
            }
        });

        $('#item_kit_form').validate({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit('<?= site_url('item_kits') ?>', response);
                    },
                    dataType: 'json'
                });
            },
            rules: {
                name: "required",
                category: "required"
            },
            messages: {
                name: "<?= lang('items_lang.items_name_required') ?>",
                category: "<?= lang('items_lang.items_category_required') ?>"
            }
        });
    });

    function delete_item_kit_row(link) {
        $(link).parent().parent().remove();
        return false;
    }
</script>