<?php

if (!function_exists('show_report_if_allowed')) {
    function show_report_if_allowed($report_prefix, $report_name, $person_id, $permission_id = '')
    {   
        $permission_id = empty($permission_id) ? 'reports_' . $report_name : $permission_id;
        if (emp_have_grant($permission_id, $person_id)) {
            show_report($report_prefix, $report_name, $permission_id);
        }
    }
}



if (!function_exists('show_report')) {
    function show_report($report_prefix, $report_name, $lang_key = '')
    {
        
        $CI = \Config\Services::codeigniter();

        // Error handling
        if (!isset($CI)|| !$report_name) {
            echo 'Error: Invalid $grants data or CI instance not available.';
            return;
        }
        $lang_key = empty($lang_key) ? $report_name : $lang_key;
        $report_label = lang('reports_lang.'.$lang_key);
        $report_prefix = empty($report_prefix) ? '' : $report_prefix . '_';

        if (!empty($report_label) && $report_label != $lang_key . ' (TBD)') {
            echo '<a class="list-group-item" href="' . base_url('reports/' . $report_prefix . preg_replace('/reports_(.*)/', '$1', $report_name)) . '">' . $report_label . '</a>';
        }
    }
}


if (!function_exists('emp_have_grant')) {
    function emp_have_grant($grant, $person_id)
    {
        $employeeModel= new \App\Models\Employee();
        $CI = \Config\Services::codeigniter();
        if ($employeeModel->has_grant($grant, $person_id)) {
            return true;
        } else {
            return false;
        }
    }
}
