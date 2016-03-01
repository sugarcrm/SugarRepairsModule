<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ReportRepairs.php');

/**
 * @group support
 * @group team
 */
class suppSugarRepairsReportsRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $reportIDs = array();

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test for replacing keys at multiple levels in a report filter
     */
    public function testMultiLevelKeyReplacement()
    {
        $jsonObj = getJSONobj();
        $reportBean = BeanFactory::newBean('Reports');
        $reportBean->name = 'MultiLevel key replacement test';
        $reportBean->report_type = 'tabular';
        $reportBean->content = $jsonObj->encode($this->multiLevelKeyReplacementFilter(), false);
        $reportBean->module = 'Accounts';
        $reportBean->chart_type = 'none';
        $reportBean->schedule_type = 'pro';
        $reportBean->assigned_user_id = $GLOBALS['current_user']->id;
        $reportBean->team_id = '1';
        $reportBean->team_set_id = '1';
        $reportID = $reportBean->save();
        $this->reportIDs[]=$reportID;

        $reportTest = new supp_ReportRepairs();
        $reportTest->repairReports();

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $beforeJson = html_entity_decode($savedReport->content);
        $content = $jsonObj->decode($beforeJson, false);
        $newFilter = $content['filters_def']['Filter_1'];

        $this->assertTrue($newFilter[0][1][0]['input_name0'][0] == 'one&one');
        $this->assertTrue($newFilter[0][1][1]['input_name0'][0] == 'six_six');
        $this->assertTrue($newFilter[1][0]['input_name0'][0] == 'two_two');
        $this->assertTrue($newFilter[1][1]['input_name0'][0] == 'one_one');
        $this->assertTrue($newFilter[1][1]['input_name0'][1] == 'six_six');
    }

    /**
     * Test for removing duplicates from team sets
     */
    public function testReportWithDeletedField()
    {
        $jsonObj = getJSONobj();
        $reportBean = BeanFactory::newBean('Reports');
        $reportBean->name = 'Report with deleted field test';
        $reportBean->report_type = 'tabular';
        $reportBean->content = $jsonObj->encode($this->deletedFieldFilter(), false);
        $reportBean->module = 'Accounts';
        $reportBean->chart_type = 'none';
        $reportBean->schedule_type = 'pro';
        $reportBean->assigned_user_id = $GLOBALS['current_user']->id;
        $reportBean->team_id = '1';
        $reportBean->team_set_id = '1';
        $reportID = $reportBean->save();
        $this->reportIDs[] = $reportID;

        $reportTest = new supp_ReportRepairs();
        $reportTest->repairReports();

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $message = "Broken: ";
        $this->assertTrue(substr($savedReport->name, 0, strlen($message)) == $message);
    }


    private function multiLevelKeyReplacementFilter()
    {
        return array(
            'display_columns' =>
                array(
                    0 =>
                        array(
                            'name' => 'name',
                            'label' => 'Name',
                            'table_key' => 'self',
                        ),
                    1 =>
                        array(
                            'name' => 'billing_address_city',
                            'label' => 'Billing City',
                            'table_key' => 'self',
                        ),
                    2 =>
                        array(
                            'name' => 'billing_address_country',
                            'label' => 'Billing Country',
                            'table_key' => 'self',
                        ),
                    3 =>
                        array(
                            'name' => 'billing_address_postalcode',
                            'label' => 'Billing Postal Code',
                            'table_key' => 'self',
                        ),
                    4 =>
                        array(
                            'name' => 'billing_address_state',
                            'label' => 'Billing State',
                            'table_key' => 'self',
                        ),
                    5 =>
                        array(
                            'name' => 'billing_address_street',
                            'label' => 'Billing Street',
                            'table_key' => 'self',
                        ),
                    6 =>
                        array(
                            'name' => 'date_entered',
                            'label' => 'Date Created',
                            'table_key' => 'self',
                        ),
                    7 =>
                        array(
                            'name' => 'date_modified',
                            'label' => 'Date Modified',
                            'table_key' => 'self',
                        ),
                    8 =>
                        array(
                            'name' => 'description',
                            'label' => 'Description',
                            'table_key' => 'self',
                        ),
                    9 =>
                        array(
                            'name' => 'employees',
                            'label' => 'Employees',
                            'table_key' => 'self',
                        ),
                ),
            'module' => 'Accounts',
            'group_defs' =>
                array(),
            'summary_columns' =>
                array(),
            'report_name' => 'Test 1',
            'chart_type' => 'none',
            'do_round' => 1,
            'numerical_chart_column' => '',
            'numerical_chart_column_type' => '',
            'assigned_user_id' => '1',
            'report_type' => 'tabular',
            'full_table_list' =>
                array(
                    'self' =>
                        array(
                            'value' => 'Accounts',
                            'module' => 'Accounts',
                            'label' => 'Accounts',
                            'dependents' =>
                                array(),
                        ),
                ),
            'filters_def' =>
                array(
                    'Filter_1' =>
                        array(
                            'operator' => 'OR',
                            0 =>
                                array(
                                    'operator' => 'AND',
                                    0 =>
                                        array(
                                            'name' => 'date_entered',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'tp_this_year',
                                            'input_name0' => 'tp_this_year',
                                            'input_name1' => 'on',
                                        ),
                                    1 =>
                                        array(
                                            'operator' => 'AND',
                                            0 =>
                                                array(
                                                    'name' => 'testdropdown_c',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => 'one&one',
                                                        ),
                                                ),
                                            1 =>
                                                array(
                                                    'name' => 'testmultiselect_c',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => 'six & six',
                                                        ),
                                                ),
                                        ),
                                ),
                            1 =>
                                array(
                                    'operator' => 'OR',
                                    0 =>
                                        array(
                                            'name' => 'testmultiselect_c',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'is',
                                            'input_name0' =>
                                                array(
                                                    0 => 'two-two',
                                                ),
                                        ),
                                    1 =>
                                        array(
                                            'name' => 'testdropdown_c',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'one_of',
                                            'input_name0' =>
                                                array(
                                                    0 => 'one&one',
                                                    1 => 'six & six',
                                                ),
                                        ),
                                ),
                        ),
                ),
        );


    }

    private function deletedFieldFilter()
    {
        return array(
            'display_columns' =>
                array(
                    0 =>
                        array(
                            'name' => 'name',
                            'label' => 'Name',
                            'table_key' => 'self',
                        ),
                    1 =>
                        array(
                            'name' => 'deleted_field',
                            'label' => 'Billing City',
                            'table_key' => 'self',
                        ),
                    2 =>
                        array(
                            'name' => 'billing_address_country',
                            'label' => 'Billing Country',
                            'table_key' => 'self',
                        ),
                    3 =>
                        array(
                            'name' => 'billing_address_postalcode',
                            'label' => 'Billing Postal Code',
                            'table_key' => 'self',
                        ),
                    4 =>
                        array(
                            'name' => 'billing_address_state',
                            'label' => 'Billing State',
                            'table_key' => 'self',
                        ),
                    5 =>
                        array(
                            'name' => 'billing_address_street',
                            'label' => 'Billing Street',
                            'table_key' => 'self',
                        ),
                    6 =>
                        array(
                            'name' => 'date_entered',
                            'label' => 'Date Created',
                            'table_key' => 'self',
                        ),
                    7 =>
                        array(
                            'name' => 'date_modified',
                            'label' => 'Date Modified',
                            'table_key' => 'self',
                        ),
                    8 =>
                        array(
                            'name' => 'description',
                            'label' => 'Description',
                            'table_key' => 'self',
                        ),
                    9 =>
                        array(
                            'name' => 'employees',
                            'label' => 'Employees',
                            'table_key' => 'self',
                        ),
                ),
            'module' => 'Accounts',
            'group_defs' =>
                array(),
            'summary_columns' =>
                array(),
            'report_name' => 'Test 1',
            'chart_type' => 'none',
            'do_round' => 1,
            'numerical_chart_column' => '',
            'numerical_chart_column_type' => '',
            'assigned_user_id' => '1',
            'report_type' => 'tabular',
            'full_table_list' =>
                array(
                    'self' =>
                        array(
                            'value' => 'Accounts',
                            'module' => 'Accounts',
                            'label' => 'Accounts',
                            'dependents' =>
                                array(),
                        ),
                ),
        );


    }
}