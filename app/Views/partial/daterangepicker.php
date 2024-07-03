<?php
if($gu->isServer()){
    $minDate = date($appData['dateformat'], mktime(0,0,0,01,01,2010));
}
else{
    $minDate = date($appData['dateformat'], mktime(0,0,0,date("m"),date("d"),date("Y"))-1);
}
lang("calendar"); lang("date"); ?>

var start_date = "<?php echo date('Y-m-d') ?>";
var end_date   = "<?php echo date('Y-m-d') ?>";

$('#daterangepicker').daterangepicker({
	"ranges": {
		"<?php echo lang("datepicker_lang.datepicker_today"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d"),date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_today_last_year"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d"),date("Y")-1));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y")-1)-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_yesterday"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")-1,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d"),date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_last_7"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")-6,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_last_30"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")-29,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_this_month"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),1,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m")+1,1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_same_month_to_same_day_last_year"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),1,date("Y")-1));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y")-1)-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_this_month_last_year"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),1,date("Y")-1));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m")+1,1,date("Y")-1)-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_last_month"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m")-1,1,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_this_year"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,1,1,date("Y")));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),1,date("Y")+1)-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_last_year"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,1,1,date("Y")-1));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,1,1,date("Y"))-1);?>"
		],
		"<?php echo lang("datepicker_lang.datepicker_all_time"); ?>": [
			"<?php echo date($appData['dateformat'], mktime(0,0,0,01,01,2010));?>",
			"<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>"
		],
	},
	"locale": {
		"format": '<?php echo dateformat_momentjs($appData["dateformat"])?>',
		"separator": " - ",
		"applyLabel": "<?php echo lang("datepicker_lang.datepicker_apply"); ?>",
		"cancelLabel": "<?php echo lang("datepicker_lang.datepicker_cancel"); ?>",
		"fromLabel": "<?php echo lang("datepicker_lang.datepicker_from"); ?>",
		"toLabel": "<?php echo lang("datepicker_lang.datepicker_to"); ?>",
		"customRangeLabel": "<?php echo lang("datepicker_lang.datepicker_custom"); ?>",
		"daysOfWeek": [
			"<?php echo lang("calendar_lang.cal_su"); ?>",
			"<?php echo lang("calendar_lang.cal_mo"); ?>",
			"<?php echo lang("calendar_lang.cal_tu"); ?>",
			"<?php echo lang("calendar_lang.cal_we"); ?>",
			"<?php echo lang("calendar_lang.cal_th"); ?>",
			"<?php echo lang("calendar_lang.cal_fr"); ?>",
			"<?php echo lang("calendar_lang.cal_sa"); ?>",
			"<?php echo lang("calendar_lang.cal_su"); ?>"
		],
		"monthNames": [
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
		"firstDay": <?php echo lang("datepicker_lang.datepicker_weekstart"); ?>
	},
	"alwaysShowCalendars": true,
	"startDate": "<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>",
	"endDate": "<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>",
	"minDate": "<?php echo $minDate; ?>",
	"maxDate": "<?php echo date($appData['dateformat'], mktime(0,0,0,date("m"),date("d")+1,date("Y"))-1);?>"
}, function(start, end, label) {
	start_date = start.format('YYYY-MM-DD');
	end_date = end.format('YYYY-MM-DD');
});
