<?php
$module_name = 'supp_SugarRepairs';
$_object_name = 'supp_sugarrepairs';
$viewdefs [$module_name] =
    array(
        'EditView' =>
            array(
                'templateMeta' =>
                    array(
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
                                                'displayParams' =>
                                                    array(
                                                        'size' => 60,
                                                    ),
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
                                                'comment' => 'Date record created',
                                                'label' => 'LBL_DATE_ENTERED',
                                            ),
                                        1 =>
                                            array(
                                                'name' => 'date_modified',
                                                'comment' => 'Date record last modified',
                                                'label' => 'LBL_DATE_MODIFIED',
                                            ),
                                    ),
                            ),
                    ),
            ),
    );
?>
