$.fn.datetimepicker.dates['<?php echo $appData["language"]; ?>'] = {
    days: [
		"<?php echo lang("calendar_lang.cal_sunday"); ?>",
        "<?php echo lang("calendar_lang.cal_monday"); ?>",
        "<?php echo lang("calendar_lang.cal_tuesday"); ?>",
        "<?php echo lang("calendar_lang.cal_wednesday"); ?>",
        "<?php echo lang("calendar_lang.cal_thursday"); ?>",
        "<?php echo lang("calendar_lang.cal_friday"); ?>",
        "<?php echo lang("calendar_lang.cal_saturday"); ?>",
        "<?php echo lang("calendar_lang.cal_sunday"); ?>"
		],
        daysShort: [
		"<?php echo lang("calendar_lang.cal_sun"); ?>",
        "<?php echo lang("calendar_lang.cal_mon"); ?>",
        "<?php echo lang("calendar_lang.cal_tue"); ?>",
        "<?php echo lang("calendar_lang.cal_wed"); ?>",
        "<?php echo lang("calendar_lang.cal_thu"); ?>",
        "<?php echo lang("calendar_lang.cal_fri"); ?>",
        "<?php echo lang("calendar_lang.cal_sat"); ?>"
		],
        daysMin: [
		"<?php echo lang("calendar_lang.cal_su"); ?>",
        "<?php echo lang("calendar_lang.cal_mo"); ?>",
        "<?php echo lang("calendar_lang.cal_tu"); ?>",
        "<?php echo lang("calendar_lang.cal_we"); ?>",
        "<?php echo lang("calendar_lang.cal_th"); ?>",
        "<?php echo lang("calendar_lang.cal_fr"); ?>",
        "<?php echo lang("calendar_lang.cal_sa"); ?>"
		],
        months: [
		"<?php echo lang("calendar_lang.cal_january"); ?>",
        "<?php echo lang("calendar_lang.cal_february"); ?>",
        "<?php echo lang("calendar_lang.cal_march"); ?>",
        "<?php echo lang("calendar_lang.cal_april"); ?>",
        "<?php echo lang("calendar_lang.cal_may"); ?>",
        "<?php echo lang("calendar_lang.cal_june"); ?>",
        "<?php echo lang("calendar_lang.cal_july"); ?>",
        "<?php echo lang("calendar_lang.cal_august"); ?>",
        "<?php echo lang("calendar_lang.cal_september"); ?>",
        "<?php echo lang("calendar_lang.cal_october"); ?>",
        "<?php echo lang("calendar_lang.cal_november"); ?>",
        "<?php echo lang("calendar_lang.cal_december"); ?>"
		],
        monthsShort: [
		"<?php echo lang("calendar_lang.cal_jan"); ?>",
        "<?php echo lang("calendar_lang.cal_feb"); ?>",
        "<?php echo lang("calendar_lang.cal_mar"); ?>",
        "<?php echo lang("calendar_lang.cal_apr"); ?>",
        "<?php echo lang("calendar_lang.cal_may"); ?>",
        "<?php echo lang("calendar_lang.cal_jun"); ?>",
        "<?php echo lang("calendar_lang.cal_jul"); ?>",
        "<?php echo lang("calendar_lang.cal_aug"); ?>",
        "<?php echo lang("calendar_lang.cal_sep"); ?>",
        "<?php echo lang("calendar_lang.cal_oct"); ?>",
        "<?php echo lang("calendar_lang.cal_nov"); ?>",
        "<?php echo lang("calendar_lang.cal_dec"); ?>"
		],
    today: "<?php echo lang("datepicker_lang.datepicker_today"); ?>",
    <?php
        if( strpos($appData['timeformat'], 'a') !== false )
        {
    ?>
    meridiem: ["am", "pm"],
    <?php
        }
        elseif( strpos($appData['timeformat'], 'A') !== false )
        {
    ?>
    meridiem: ["AM", "PM"],
    <?php
        }
        else
        {
    ?>
    meridiem: [],
    <?php
        }
    ?>
    weekStart: <?php echo lang("datepicker_lang.datepicker_weekstart"); ?>
};