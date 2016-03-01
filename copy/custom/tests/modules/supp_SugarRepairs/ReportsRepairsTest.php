<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ReportRepairs.php');

/**
 * @group support
 * @group team
 */
class suppSugarRepairsReportsRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{
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
     * Test for removing duplicates from team sets
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

        $reportTest = new supp_ReportRepairs();
        $reportTest->repairReports();

        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $beforeJson = html_entity_decode($savedReport->content);
        $content = $jsonObj->decode($beforeJson, false);
        $newFilter = $content['filters_def']['Filter_1'];

        $this->assertTrue($newFilter[0][1][0]['input_name0'][0]=='one&one');
        $this->assertTrue($newFilter[0][1][1]['input_name0'][0]=='six_six');
        $this->assertTrue($newFilter[1][0]['input_name0'][0]=='two_two');
        $this->assertTrue($newFilter[1][1]['input_name0'][0]=='one_one');
        $this->assertTrue($newFilter[1][1]['input_name0'][1]=='six_six');
    }


    private function multiLevelKeyReplacementFilter()
    {
        return array(
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
        );


    }
}