<?php

require_once ('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');

/**
 * @group support
 */

class suppSugarRepairsTeamSetsBeanTest extends Sugar_PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $this->mockupWorkFlowRecord();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->teardownWorkflowRecord();
    }


    /**
     * In this test we are checking that the T_VARIABLE, T_ARRAY_NAME & T_ARRAY_KEY tags
     * have been placed the white space removed and that the $GLOBALS has been corrected properly
     */
    public function testgetAnnotatedTokenList() {
        $testFile = "<?php\n\$GLOBALS['app_list_strings']['test_list']=array (\n\n\n\n\n'one&one' => 'One');\n";
        $newRepairTest = new supp_LanguageRepairs();
        $tokenList = $newRepairTest->getAnnotatedTokenList(null, $testFile);

        $this->assertTrue($tokenList[1][1] == "\$app_list_strings");
        $this->assertTrue($tokenList[1]['TOKEN_NAME'] == 'T_VARIABLE');

        $this->assertTrue($tokenList[3][1] == 'test_list');
        $this->assertTrue($tokenList[3]['TOKEN_NAME'] == 'T_ARRAY_NAME');

        $this->assertTrue($tokenList[8][1] == 'one&one');
        $this->assertTrue($tokenList[8]['TOKEN_NAME'] == 'T_ARRAY_KEY');

        $this->assertTrue($tokenList[12] == ';');
    }

    /**
     * In this test we are checking to make sure and the characters &,/,-,( and ) are replaced with underscores or nothing
     */
    public function testprocessTokenList() {
        $testFile = "<?php\n\$GLOBALS['app_list_strings']['test_list']=array (\n\n\n\n\n'one&one' => 'One',\n'one-one' => 'Two',\n'one/one' => 'Three',\n'one(one)' => 'Four',\n);\n";
        $newRepairTest = new supp_LanguageRepairs();
        $tokenList = $newRepairTest->processTokenList(null, $testFile);
        $this->assertTrue($tokenList[7][0][1] == "one_one");
        $this->assertTrue($tokenList[8][0][1] == "one_one");
        $this->assertTrue($tokenList[9][0][1] == "one_one");
        $this->assertTrue($tokenList[10][0][1] == "oneone");
    }

    public function testupdateReportFilters() {
        $this->markTestSkipped('Skipping for now as I am not sure how to test this function');

    }

    /**
     * This test checks to make sure the eval and value fields in the DB are updated, it looks for the correct one_one and makes sure
     * that the old one&one has been removed
     */
    public function testupdateWorkFlow() {
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->tableBackupFlag=array('workflow_triggershells'=>'workflow_triggershells','workflow_actions'=>'workflow_actions');
        $newRepairTest->updateWorkflow("one&one", "one_one", true);

        $sql = "SELECT eval FROM workflow_triggershells WHERE id='TEST1'";
        $hash=$GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['eval'],'one_one')!==false);
        $this->assertTrue(strstr($hash['eval'],'one&one')===false);

        $sql = "SELECT value FROM workflow_actions WHERE id='TEST1'";
        $hash=$GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['value'],'one_one')!==false);
        $this->assertTrue(strstr($hash['value'],'one&one')===false);
    }

    private function mockupWorkFlowRecord() {
        $sql = "INSERT INTO workflow_triggershells (id, deleted, date_entered, date_modified, modified_user_id, created_by, field, type, frame_type, eval, parent_id, show_past, rel_module, rel_module_type, parameters)
                     VALUES ('TEST1', '0', '2016-02-09 18:27:01', '2016-02-09 18:27:01', '1', '1', 'single_c', 'compare_specific', 'Primary', ' (\$focus->fetched_row[''single_c''] == ''one&one'')&& (isset(\$focus->single_c) && \$focus->single_c == ''two-two'')', '1', '1', NULL, 'any', NULL)";
        $GLOBALS['db']->query($sql);
        $sql= "INSERT INTO workflow_actions (id, deleted, date_entered, date_modified, modified_user_id, created_by, field, value, set_type, adv_type, parent_id, ext1, ext2, ext3)
                     VALUES ('TEST2', '0', '2016-02-09 19:58:01', '2016-02-09 19:58:01', '1', '1', 'multiple_c', 'one&one^,^four(four^,^nine_nine^,^seven / seven^,^one&one^,^ten(ten)', 'Basic', '', '1', '', '', '')";
        $GLOBALS['db']->query($sql);
    }

    private function teardownWorkflowRecord() {
        $sql = "DELETE FROM workflow_triggershells WHERE id='TEST1'";
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM workflow_actions WHERE id='TEST2'";
        $GLOBALS['db']->query($sql);
    }
}