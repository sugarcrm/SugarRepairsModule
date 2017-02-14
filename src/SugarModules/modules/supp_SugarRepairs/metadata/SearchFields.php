<?php
// created: 2016-03-01 11:40:30
$searchFields['supp_SugarRepairs'] = array(
    'name' =>
        array(
            'query_type' => 'default',
        ),
    'status' =>
        array(
            'query_type' => 'default',
            'options' => 'supp_sugarrepairs_status_dom',
            'template_var' => 'STATUS_OPTIONS',
        ),
    'priority' =>
        array(
            'query_type' => 'default',
            'options' => 'supp_sugarrepairs_priority_dom',
            'template_var' => 'PRIORITY_OPTIONS',
        ),
    'resolution' =>
        array(
            'query_type' => 'default',
            'options' => 'supp_sugarrepairs_resolution_dom',
            'template_var' => 'RESOLUTION_OPTIONS',
        ),
    'supp_sugarrepairs_number' =>
        array(
            'query_type' => 'default',
            'operator' => 'in',
        ),
    'current_user_only' =>
        array(
            'query_type' => 'default',
            'db_field' =>
                array(
                    0 => 'assigned_user_id',
                ),
            'my_items' => true,
            'vname' => 'LBL_CURRENT_USER_FILTER',
            'type' => 'bool',
        ),
    'assigned_user_id' =>
        array(
            'query_type' => 'default',
        ),
    'favorites_only' =>
        array(
            'query_type' => 'format',
            'operator' => 'subquery',
            'subquery' => 'SELECT sugarfavorites.record_id FROM sugarfavorites
			                    WHERE sugarfavorites.deleted=0 
			                        and sugarfavorites.module = \'supp_SugarRepairs\'
			                        and sugarfavorites.assigned_user_id = \'{0}\'',
            'db_field' =>
                array(
                    0 => 'id',
                ),
        ),
    'open_only' =>
        array(
            'query_type' => 'default',
            'db_field' =>
                array(
                    0 => 'status',
                ),
            'operator' => 'not in',
            'closed_values' =>
                array(
                    0 => 'Closed',
                    1 => 'Rejected',
                    2 => 'Duplicate',
                ),
            'type' => 'bool',
        ),
    'range_date_entered' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
    'start_range_date_entered' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
    'end_range_date_entered' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
    'range_date_modified' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
    'start_range_date_modified' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
    'end_range_date_modified' =>
        array(
            'query_type' => 'default',
            'enable_range_search' => true,
            'is_date_field' => true,
        ),
);