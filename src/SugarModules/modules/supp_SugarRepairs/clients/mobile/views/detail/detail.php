<?php
$module_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$viewdefs[$module_name] =
    array(
        'mobile' =>
            array(
                'view' =>
                    array(
                        'detail' =>
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
                                                    ),
                                            ),
                                        'maxColumns' => '1',
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
                                    ),
                                'panels' =>
                                    array(
                                        0 =>
                                            array(
                                                'label' => 'LBL_PANEL_DEFAULT',
                                                'name' => 'LBL_PANEL_DEFAULT',
                                                'columns' => '1',
                                                'labelsOnTop' => 1,
                                                'placeholders' => 1,
                                                'fields' =>
                                                    array(
                                                        0 => 'supp_sugarrepairs_number',
                                                        1 => 'priority',
                                                        2 => 'status',
                                                        3 =>
                                                            array(
                                                                'name' => 'name',
                                                                'label' => 'LBL_SUBJECT',
                                                            ),
                                                        4 => 'assigned_user_name',
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            ),
    );
