<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ReportRepairs.php');

/**
 * @group support
 * @group report
 */
class suppSugarRepairsReportsRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $reportIDs = array();

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $GLOBALS['current_user']->getSystemUser();
        $GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach ($this->reportIDs as $reportId) {
            $GLOBALS['db']->query("DELETE FROM saved_reports WHERE id = '{$reportId}'");
        }
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
        $this->reportIDs[] = $reportID;

        $reportTest = new supp_ReportRepairs();
        $reportTest->execute(array('test' => false));

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $beforeJson = html_entity_decode($savedReport->content);
        $content = $jsonObj->decode($beforeJson, false);
        $newFilter = $content['filters_def']['Filter_1'];

        //print_r($newFilter);
        $this->assertEquals($newFilter[0][1][0]['input_name0'][0], 'Banking');
        $this->assertEquals($newFilter[0][1][1]['input_name0'][0], 'Banking');
        $this->assertEquals($newFilter[1][0]['input_name0'][0], 'Banking');
        $this->assertEquals($newFilter[1][1]['input_name0'][0], 'Banking');
        $this->assertEquals($newFilter[1][1]['input_name0'][1], 'Education');
    }

    /**
     * Test for detecting a deleted field in a report filter
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
        $reportTest->setTesting(false);
        $reportTest->execute(array('test' => false));

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $message = "Broken: ";
        $this->assertEquals($message, substr($savedReport->name, 0, strlen($message)));
    }

    public function testLegacyTeamSetDefinitionRepair()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.0', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }

        $jsonObj = getJSONobj();
        $reportBean = BeanFactory::newBean('Reports');
        $reportBean->name = 'Legacy TeamSet Report Test';
        $reportBean->report_type = 'tabular';
        $reportBean->content = $jsonObj->encode($this->legacyTeamSetDefinition(), false);
        $reportBean->module = 'Accounts';
        $reportBean->chart_type = 'none';
        $reportBean->schedule_type = 'pro';
        $reportBean->assigned_user_id = $GLOBALS['current_user']->id;
        $reportBean->team_id = '1';
        $reportBean->team_set_id = '1';
        $reportID = $reportBean->save();
        $this->reportIDs[] = $reportID;

        $reportTest = new supp_ReportRepairs();
        $reportTest->setTesting(false);
        $reportTest->repairReports();

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $patterns = array('/\"team_sets\"/', '/\:team_sets\"/', '/Team Set/', '/\"relationship_name\":\"(\w+)_team_sets\"/');
        $replacements = array('/\"team_link\"/', '/\:team_link\"/', '/Teams/', '/\"relationship_name":\"(\w+)_team_link\"/');
        foreach ($patterns as $key => $pattern) {
            $this->assertEquals(0, preg_match($pattern, $savedReport->content));
            $this->assertEquals(1, preg_match($replacements[$key], $savedReport->content));
        }
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
                                                    'name' => 'industry',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => '(Banking)',
                                                        ),
                                                ),
                                            1 =>
                                                array(
                                                    'name' => 'industry',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => '(Banking)',
                                                        ),
                                                ),
                                        ),
                                ),
                            1 =>
                                array(
                                    'operator' => 'OR',
                                    0 =>
                                        array(
                                            'name' => 'industry',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'is',
                                            'input_name0' =>
                                                array(
                                                    0 => '(Banking)',
                                                ),
                                        ),
                                    1 =>
                                        array(
                                            'name' => 'industry',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'one_of',
                                            'input_name0' =>
                                                array(
                                                    0 => '(Banking)',
                                                    1 => '(Education)',
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
                                            'name' => 'deleted_field',
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
                                                    'name' => 'industry',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => '(Banking)',
                                                        ),
                                                ),
                                            1 =>
                                                array(
                                                    'name' => 'industry',
                                                    'table_key' => 'self',
                                                    'qualifier_name' => 'is',
                                                    'input_name0' =>
                                                        array(
                                                            0 => '(Banking)',
                                                        ),
                                                ),
                                        ),
                                ),
                            1 =>
                                array(
                                    'operator' => 'OR',
                                    0 =>
                                        array(
                                            'name' => 'industry',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'is',
                                            'input_name0' =>
                                                array(
                                                    0 => '(Banking)',
                                                ),
                                        ),
                                    1 =>
                                        array(
                                            'name' => 'industry',
                                            'table_key' => 'self',
                                            'qualifier_name' => 'one_of',
                                            'input_name0' =>
                                                array(
                                                    0 => '(Banking)',
                                                    1 => '(Education)',
                                                ),
                                        ),
                                ),
                        ),
                ),
        );
    }

    private function legacyTeamSetDefinition()
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
                            'name' => 'billing_address_state',
                            'label' => 'Billing State',
                            'table_key' => 'self',
                        ),
                    2 =>
                        array(
                            'name' => 'phone_office',
                            'label' => 'Office Phone',
                            'table_key' => 'self',
                        ),
                ),
            'module' => 'Accounts',
            'group_defs' =>
                array(),
            'summary_columns' =>
                array(),
            'report_name' => 'Legacy TeamSet Report Test',
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
                        ),
                    'Accounts:team_sets' =>
                        array(
                            'name' => 'Accounts  >  Team Set',
                            'parent' => 'self',
                            'link_def' =>
                                array(
                                    'name' => 'team_sets',
                                    'relationship_name' => 'accounts_team_sets',
                                    'bean_is_lhs' => false,
                                    'link_type' => 'many',
                                    'label' => 'Team Set',
                                    'module' => 'Teams',
                                    'table_key' => 'Accounts:team_sets',
                                ),
                            'dependents' =>
                                array(
                                    0 => 'Filter.1_table_filter_row_1',
                                ),
                            'module' => 'Teams',
                            'label' => 'Team Set',
                        ),
                ),
            'filters_def' =>
                array(
                    'Filter_1' =>
                        array(
                            'operator' => 'AND',
                            0 =>
                                array(
                                    'name' => 'name',
                                    'table_key' => 'Accounts:team_sets',
                                    'qualifier_name' => 'is',
                                    'input_name0' => 'East',
                                    'input_name1' => 'East',
                                ),
                        ),
                ),
            'chart_type' => 'none',
        );
    }

}
