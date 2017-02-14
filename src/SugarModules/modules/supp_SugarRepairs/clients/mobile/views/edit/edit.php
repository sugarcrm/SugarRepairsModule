<?php
$module_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$viewdefs[$module_name] =
    array(
        'mobile' =>
            array(
                'view' =>
                    array(
                        'edit' =>
                            array(
                                'templateMeta' =>
                                    array(
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
                                                        0 =>
                                                            array(
                                                                'name' => 'supp_sugarrepairs_number',
                                                                'displayParams' =>
                                                                    array(
                                                                        'required' => false,
                                                                        'wireless_detail_only' => true,
                                                                    ),
                                                            ),
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
