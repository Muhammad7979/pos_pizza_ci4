<?php
$suspendedIndex = 900;
?>
<form style="display: flex;
             justify-content: end;
             width: 97%;">
    <input id="search" type="text" name="search" placeholder="Search by cake invoice" style="border-width: 2px; padding: 0px 7px;">
    <button id="syn_cake_item" class="btn btn-primary btn-xs pull-right" style="margin-left:7px">Sync</button>
</form>
<script type="text/javascript">
    
    $(document).ready(function(){
        $('#syn_cake_item').on('click',function(e){
            e.preventDefault();
             var resultList = $('#searchResult').empty();
             resultList.html('<tr><td class="" colspan="5" style="text-align:center">Loading...</td></tr>')
             $('#syn_cake_item').prop('disabled',true);
            $.ajax({

                 url: '<?=  base_url('cli/sync_cake_suspended_sales'); ?>',
                 type: 'GET',
                 success:function(response){
                    $('#syn_cake_item').prop('disabled', false);
                    let searchTerm = '';
                    loadData(searchTerm);
                    alert(response);
                 }

            })
        });

        $('#search').on('input',function(){
            let searchTerm = $(this).val();
            loadData(searchTerm);
        })
         


         function loadData(searchTerm){
          $.ajax({
                url: '<?=  base_url('sales/cakeSuspendedSearch'); ?>',
                type: 'GET',
                data:{ search:searchTerm},
                success:function(response){

                    var resultList = $('#searchResult');
                    resultList.empty();
                    var res = JSON.parse(response);
                     if(res.suspended_sales.length == 0){
                 resultList.append(`<tr><td class="" colspan="5" style="text-align:center">No items.</td></tr>`);
                     }
                    $.each(res.suspended_sales, function(index,sale){
                        var sus_grand_total = 0;
                        resultList.append(`
                            <tr id="sus_sale${sale.sale_id}">
                                <td>
                                    <?php
                                    echo form_open('sales/unsuspend');
                                    ?>
                                    <input class="form form-control suspended_sale_id"
                                           style="max-width: 60px;" data-type="suspended_sale"
                                           name="suspended_sale_id" onfocus="this.select();"
                                           title="Sale ID" tabindex="<?php echo ++$suspendedIndex; ?>"
                                           type="text" value="${sale.sale_id}" />
                                    <?php echo form_close(); ?>
                                </td>
                                <td style="font-weight: bold;">
                                    <?php echo date("M-d h:i A", strtotime('{sale.sale_time}'));?>
                                </td>
                                <td style="font-weight: bold;">
                                    ${sale.cake_invoice}
                                </td>
                                <td>
                                    <table class="table table-striped table-hover">
                                        <tr bgcolor="#CCC">
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                        ${$.map(sale.items, function(item, index){
                                            var sus_sale_total = item.item_unit_price * item.quantity_purchased;
                                            sus_grand_total += sus_sale_total;
                                            return `<tr>
                                                        <td>${item.name}</td>
                                                        <td>${item.quantity_purchased}</td>
                                                        <td>${item.item_unit_price}</td>
                                                        <td>${sus_sale_total}</td>
                                                    </tr>`;
                                        }).join('')}
                                    </table>
                                </td>
                                <td>
                                    <?php echo form_open('sales/cakeUnsuspend');
                                    echo form_hidden('suspended_sale_id', '${sale.sale_id}');
                                    ?>
                                    <input type="submit" name="submit" value="<?php echo lang('sales_lang.sales_unsuspend'); ?>" id="submit" class="btn btn-primary btn-xs pull-right">
                                    <?php echo form_close(); ?>
                                    <br/>
                                    <span style="float: right; margin-top: 20px; font-weight: bold;">
                                        Rs. ${sus_grand_total}
                                    </span>
                                </td>
                            </tr>
                        `);
                    });
                }
            })


         }


    });

</script>

<table id="suspended_sales_table" style="width: 95%;"
       class="table table-striped table-hover table-bordered">
    <thead>
    <tr bgcolor="#CCC">
        <th>Sale <?php echo lang('sales_lang.sales_suspended_sale_id'); ?></th>
        <th><?php echo lang('sales_lang.sales_date'); ?></th>
        <th>Cake Inv.</th>
        <th>Items</th>
        <th><?php echo lang('sales_lang.sales_unsuspend_and_delete'); ?></th>
    </tr>
    </thead>
    <tbody id="searchResult">
    <?php
    if($suspended_sales == null){
     ?>
      <tr>
          <td class="" colspan="5" style="text-align:center">No items.</td>
      </tr>
    <?php
    }else{

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
           <td style="font-weight: bold;">
                <?php echo $suspended_sale->cake_invoice;?>
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
                <?php echo form_open('sales/cakeUnsuspend');
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
}
    ?>
    </tbody>
</table>


