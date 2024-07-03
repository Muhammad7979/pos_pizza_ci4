<div class="wrapper">

    <div class="equipment_maintenance_log_sheet_container" style="margin: 0">

        <div class="equipment_maintenance_log_sheet_header" style="width: 100%;text-align: center; margin-top: 35px">
           
            <img src="<?php echo base_url().'images/tehzeeb/logo.png' ?>" style="width:70%; padding: 0; margin: 0;border-radius: 10px"/>

            <h1 style="margin-top: 0px;padding-bottom: 5px;font-size:38px">
                <?php echo $branch ?> 
            </h1>
            <div>
                <table style="width: 100%;">
                    <tr style="padding: 10px;font-size: 28px;">
                        <td style="font-size: 28px;font-weight: 300;text-align: left; margin-top: 0px;">
                            Dated
                        </td>
                        <td style="font-size: 28px;font-weight: 300;text-align: right; margin-top: 0px;">
                           Deliver At
                        </td>
                    </tr>
                </table>
            </div>
            <hr>
            <div>
                <table style="width: 100%;">
                    <tr style="padding: 10px;font-size: 28px;">
                        <td style="font-size: 28px;font-weight: 300;text-align: left; margin-top: 0px;">
                            <?php echo $date. ' ' .$time  ?> 
                        </td>
                        <td style="font-size: 28px;font-weight: 300;text-align: right; margin-top: 0px;">
                           <?php echo $deliver_date. ' ' .$deliver_time  ?> 
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <br>
        <?php  echo $barcode_lib->display_order_barcode($item, $barcode_config); ?>
        <br>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%">

            <table style="width: 100%;border-collapse: collapse;border: 1px solid#eee">
                <tr style="padding: 10px;text-align: center;font-size: 38px;background-color: #000;color: white;">
                    <th style="padding: 10px;">Token #</th>
                </tr>
                <tr style="padding: 10px;font-size: 100px;border-bottom: 1px solid#eee">
                    <td style="padding: 10px;text-align: center; color: #000"><?php echo $count ?></td>
                </tr>
            <?php if($reordered==1){ ?>
                <tr><td style="text-align: center;">
                <span style="font-size: 30px; text-align: center;">Already Processed Item</span>
                </td></tr>
            <?php } ?>
            </table>
        </div>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%">

            <table style="width: 100%;text-align: center;border-collapse: collapse;border-bottom: 1px solid #000">
                <tr
                    style="padding: 10px;text-align: center;height: 60px; font-size: 30px;background-color: #000;color: white;">
                    <th style="padding: 10px 5px;">Item</th>
                    <th style="padding: 10px 5px;">Qty</th>
                    <th style="padding: 10px 5px;">Price</th>
                    <th style="padding: 10px 5px;">Sub Total</th>
                </tr>
                <?php foreach ($items as $key => $itemk) { ?>
                <tr style="padding: 10px;height: 40px; font-size: 30px;text-align: center;border-bottom: 1px dashed black">
                    <td style="padding: 10px 5px; width: 30%"> <?php echo $itemk->name ?>
                    </td>
                    <td style="padding: 10px 5px; width: 10%"><?php echo parse_decimals($itemk->quantity); ?></td>
                    <td style="padding: 10px 5px; width: 20%"><?php echo parse_decimals($itemk->price); ?></td>
                    <td style="padding: 10px 5px; width: 20%"><?php echo parse_decimals($itemk->sub_total); ?></td>
                </tr>
                <?php } ?>
            </table>
        
            <table style="width: 100%;text-align: center;border-collapse: collapse;border: 1px solid #eee">
                <tr style="padding: 10px;font-size: 48px;">
                    <td style="padding: 10px; width: 50%">Total Price:</td>
                    <td style="padding: 10px; width: 50%">
                        <?php echo parse_decimals($price).'/-' ?>
                    </td>
                </tr>
            </table>
        </div>
        <hr>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%;">
            <table style="text-align: right; font-size: 38px;width: 100%;">
                <tr>
                    <th style="height: 40px">Counter:</th>
                    <td style="text-align: center;">
                        <u
                            style="border-bottom: 2px dotted #000; text-decoration: none;">
                           <?php echo $counter ?> 
                        </u>
                    </td>
                </tr>
            </table> 
        </div> 
        <br>

    </div>
</div>

<p style="font-size: 30px; text-align: center; padding-bottom: 20px;"><strong>Thank you for purchasing!</strong></p>

<hr style="border: 1px dashed black;" />
<!-- <div style="page-break-after: always;"></div> -->

<!-- <div class="wrapper">

    <div class="equipment_maintenance_log_sheet_container" style="margin: 0">

        <div class="equipment_maintenance_log_sheet_header" style="width: 100%;text-align: center; margin-top: 35px">
           
            <img src="<?php echo base_url().'images/tehzeeb/logo.png' ?>" style="width:80%; padding: 0; margin: 0;border-radius: 10px"/>

            <h1 style="margin-top: 0px;padding-bottom: 5px;font-size:48px">
                <?php echo $branch ?> 
            </h1>
            <p style="font-size:38px;font-weight: 300;">Duplicate</p>
            <hr>
            <div>
                <table style="width: 100%;">
                    <tr style="padding: 10px;font-size: 38px;">
                        <td style="font-size: 38px;font-weight: 300;text-align: left; margin-top: 0px;">
                            <?php echo $date ?> 
                        </td>
                        <td style="font-size: 38px;font-weight: 300;text-align: right; margin-top: 0px;">
                           <?php echo $time ?> 
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <br>
        <?php echo $barcode_lib->display_order_barcode($item, $barcode_config); ?>
        <br>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%">

            <table style="width: 100%;border-collapse: collapse;border: 1px solid#eee">
                <tr style="padding: 10px;text-align: center;font-size: 38px;background-color: #000;color: white;">
                    <th style="padding: 10px;">Token #</th>
                </tr>
                <tr style="padding: 10px;font-size: 160px;border-bottom: 1px solid#eee">
                    <td style="padding: 10px;text-align: center; color: #000;"><?php echo $count ?></td>
                </tr>
            </table>
        </div>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%">

            <table style="width: 100%;text-align: center;border-collapse: collapse;border: 1px solid #eee">
                <tr
                    style="padding: 10px;text-align: center;height: 60px; font-size: 30px;background-color: #000;color: white;">
                    <th style="padding: 10px 5px;">Item</th>
                    <th style="padding: 10px 5px;">Qty</th>
                    <th style="padding: 10px 5px;">Price</th>
                    <th style="padding: 10px 5px;">Sub Total</th>
                </tr>
                <?php foreach ($items as $key => $itemk) { ?>
                <tr style="padding: 10px;height: 40px; font-size: 30px;text-align: center;border-bottom: 1px solid #eee">
                    <td style="padding: 10px 5px; width: 30%"> <?php echo $itemk->name ?>
                    </td>
                    <td style="padding: 10px 5px; width: 10%"><?php echo parse_decimals($itemk->quantity); ?></td>
                    <td style="padding: 10px 5px; width: 20%"><?php echo parse_decimals($itemk->price); ?></td>
                    <td style="padding: 10px 5px; width: 20%"><?php echo parse_decimals($itemk->sub_total); ?></td>
                </tr>
                <?php } ?>
            </table>

            <table style="width: 100%;text-align: center;border-collapse: collapse;border: 1px solid#eee">
                <tr style="padding: 10px;font-size: 48px;">
                    <th style="padding: 10px;">Total Price:</th>
                    <td style="padding: 10px;">
                        <?php echo parse_decimals($price).'/-' ?>
                    </td>
                </tr>
            </table>
        </div>
        <hr>
        <div class="equipment_maintenance_log_sheet_table" style="width: 100%;">
            <table style="text-align: right; font-size: 38px;width: 100%;">
                <tr>
                    <th style="height: 40px">Counter:</th>
                    <td style="text-align: center;">
                        <u
                            style="border-bottom: 2px dotted #000; text-decoration: none;">
                           <?php echo $counter ?> 
                        </u>
                    </td>
                </tr>
            </table> 
        </div> 
        <br>

    </div>
</div>

<p style="font-size: 30px; text-align: center; padding-bottom: 20px;"><strong>Thank you for purchasing!</strong>
        </p> -->

<!-- <div style="page-break-after: always;"></div> -->