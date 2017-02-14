<?php
$module_name = 'supp_SugarRepairs';
$_object_name = 'supp_sugarrepairs';
$viewdefs [$module_name] =
    array(
        'DetailView' =>
            array(
                'templateMeta' =>
                    array(
                        'form' =>
                            array(
                                'buttons' =>
                                    array(
                                        0 => 'EDIT',
                                        1 => 'DUPLICATE',
                                        2 => 'DELETE',
                                        3 => 'FIND_DUPLICATES',
                                    ),
                            ),
                        'maxColumns' => '2',
                        'widths' =>
                            array(
                                0 =>
                                    array(
                                        'label' => '10',
                                        'field' => '30',
                                    ),
                                1 =>
                                    array(
                                        'label' => '10',
                                        'field' => '30',
                                    ),
                            ),
                        'useTabs' => false,
                        'tabDefs' =>
                            array(
                                'DEFAULT' =>
                                    array(
                                        'newTab' => false,
                                        'panelDefault' => 'expanded',
                                    ),
                            ),
                        'syncDetailEditViews' => true,
                    ),
                'panels' =>
                    array(
                        'default' =>
                            array(
                                0 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'name',
                                                'label' => 'LBL_SUBJECT',
                                            ),
                                    ),
                                1 =>
                                    array(
                                        0 => 'status',
                                        1 => 'priority',
                                    ),
                                2 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'type',
                                                'comment' => 'The type of issue (ex: issue, feature)',
                                                'label' => 'LBL_TYPE',
                                            ),
                                        1 =>
                                            array(
                                                'name' => 'target_type',
                                                'label' => 'LBL_TARGET_TYPE',
                                            ),
                                    ),
                                3 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'cycle_id',
                                                'label' => 'LBL_CYCLE_ID',
                                            ),
                                        1 =>
                                            array(
                                                'name' => 'target',
                                                'label' => 'LBL_TARGET',
                                            ),
                                    ),
                                4 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'value_before',
                                                'studio' => 'visible',
                                                'label' => 'LBL_VALUE_BEFORE',
                                            ),
                                    ),
                                5 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'value_after',
                                                'studio' => 'visible',
                                                'label' => 'LBL_VALUE_AFTER',
                                            ),
                                    ),
                                6 =>
                                    array(
                                        0 =>
                                            array(
                                                'name' => 'date_entered',
                                                'customCode' => '{$fields.date_entered.value} {$APP.LBL_BY} {$fields.created_by_name.value}',
                                                'label' => 'LBL_DATE_ENTERED',
                                            ),
                                        1 =>
                                            array(
                                                'name' => 'date_modified',
                                                'customCode' => '{$fields.date_modified.value} {$APP.LBL_BY} {$fields.modified_by_name.value}',
                                                'label' => 'LBL_DATE_MODIFIED',
                                            ),
                                    ),
                            ),
                    ),
            ),
    );
?>
