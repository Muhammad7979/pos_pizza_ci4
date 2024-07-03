(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['<?php echo $appData['language']; ?>'] = {
		formatLoadingMessage: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_loading');?>";
		},
		formatRecordsPerPage: function (pageNumber) {
			return "<?php echo lang('bootstrap_tables_lang.tables_rows_per_page'); ?>".replace('{0}', pageNumber);
		},
		formatShowingRows: function (pageFrom, pageTo, totalRows) {
			return "<?php echo lang('bootstrap_tables_lang.tables_page_from_to'); ?>".replace('{0}', pageFrom).replace('{1}', pageTo).replace('{2}', totalRows);
		},
		formatSearch: function () {
			return "<?php echo lang('common_lang.common_search'); ?>";
		},
		formatNoMatches: function () {
			return "<?php echo lang(preg_match('(customers|suppliers|employees)', $controller_name) ?
				'common_lang.common_no_persons_to_display' : $controller_name.'_lang.'.$controller_name . '_no_' . $controller_name .'_to_display'); ?>";
		},	
		formatPaginationSwitch: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_hide_show_pagination'); ?>";
		},
		formatRefresh: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_refresh'); ?>";
		},
		formatToggle: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_toggle'); ?>";
		},
		formatColumns: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_columns'); ?>";
		},
		formatAllRows: function () {
			return "<?php echo lang('bootstrap_tables_lang.tables_all'); ?>";
		},
		formatConfirmDelete : function() {
			return "<?php echo lang($controller_name. '_lang.'. (isset($editable) ? $editable : $controller_name). "_confirm_delete")?>";
		}
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales["<?php echo $appData['language'];?>"]);

})(jQuery);