<?php
$suspendedIndex = 900;
?>
<table id="suspended_sales_table" style="width: 95%;"
       class="table table-striped table-hover table-bordered">
    <thead>
    <tr bgcolor="#CCC">
        <th>Sale <?php echo lang('sales_lang.sales_suspended_sale_id'); ?></th>
        <th><?php echo lang('sales_lang.sales_date'); ?></th>
        <th>Items</th>
        <th><?php echo lang('sales_lang.sales_unsuspend_and_delete'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($suspended_sales as $suspended_sale)
    {
        ?>
        <tr id="sus_sale<?php echo $suspended_sale->sale_id;?>">
            <td>
                <?php
                echo form_open('sales/unsuspend');
                ?>
                <input class="form form-control suspended_sale_id"
                       style="max-width: 60px;" data-type="suspended_sale"
                       name="suspended_sale_id" onfocus="this.select();"
                       title="Sale ID" tabindex="<?php echo ++$suspendedIndex; ?>"
                       type="text" value="<?php echo $suspended_sale->sale_id;?>" />
                <?php echo form_close(); ?>
            </td>
            <td style="font-weight: bold;">
                <?php echo date("M-d h:i A", strtotime($suspended_sale->sale_time));?>
            </td>
            <td>
                <table class="table table-striped table-hover">
                    <tr bgcolor="#CCC">
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                    <?php
                    $sus_grand_total = 0;
                    foreach ($suspended_sale->items as $sale_item)
                    {
                        $sus_sale_total = $sale_item->item_unit_price*$sale_item->quantity_purchased;
                        $sus_grand_total += $sus_sale_total;
                    ?>
                        <tr>
<!--                            <td>--><?php //echo $sale_item->item_number; ?><!--</td>-->
                            <td><?php echo $sale_item->name; ?></td>
                            <td><?php echo $sale_item->quantity_purchased; ?></td>
                            <td><?php echo $sale_item->item_unit_price; ?></td>
                            <td><?php echo $sus_sale_total; ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </td>
            <td>
                <?php echo form_open('sales/unsuspend');
                echo form_hidden('suspended_sale_id', $suspended_sale->sale_id);
                ?>
                <input type="submit" name="submit" value="<?php echo lang('sales_lang.sales_unsuspend'); ?>" id="submit" class="btn btn-primary btn-xs pull-right">
                <?php echo form_close(); ?>
                <br/>

                <span style="float: right; margin-top: 20px; font-weight: bold;">
                    Rs. <?php echo $sus_grand_total; ?>
                </span>

            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>


