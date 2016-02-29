<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');

/**
 * @group support
 * @group language
 */
class suppSugarRepairsLanguageRepairs extends Sugar_PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $this->mockupLanguageTestRecords();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->teardownLanguageTestRecords();
    }

    /**
     * In this test we are checking that the T_VARIABLE, T_ARRAY_NAME & T_ARRAY_KEY tags
     * have been placed the white space removed and that the $GLOBALS has been corrected properly
     */
    public function testgetAnnotatedTokenList()
    {
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
    public function testprocessTokenList()
    {
        $testFile = "<?php\n\$GLOBALS['app_list_strings']['test_list']=array (\n\n\n\n\n'one&one' => 'One',\n'one-one' => 'Two',\n'one/one' => 'Three',\n'one(one)' => 'Four',\n);\n";
        $newRepairTest = new supp_LanguageRepairs();
        $tokenList = $newRepairTest->processTokenList(null, $testFile);
        $this->assertTrue($tokenList[7][0][1] == "one_one");
        $this->assertTrue($tokenList[8][0][1] == "one_one");
        $this->assertTrue($tokenList[9][0][1] == "one_one");
        $this->assertTrue($tokenList[10][0][1] == "oneone");
    }

    public function testupdateReportFilters()
    {
        $this->markTestSkipped('Skipping for now as I am not sure how to test this function');
    }

    /**
     * This test checks to make sure the eval and value fields in the DB are updated, it looks for the correct one_one and makes sure
     * that the old one&one has been removed
     */
    public function testupdateWorkFlow()
    {
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->updateWorkflow("one&one", "one_one", true);

        $sql = "SELECT eval FROM workflow_triggershells WHERE id='TEST1'";
        $hash = $GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['eval'], 'one_one') !== false);
        $this->assertTrue(strstr($hash['eval'], 'one&one') === false);

        $sql = "SELECT value FROM workflow_actions WHERE id='TEST1'";
        $hash = $GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['value'], 'one_one') !== false);
        $this->assertTrue(strstr($hash['value'], 'one&one') === false);
    }

    /**
     * This test see if the verdefs is updated to one_one and that one&one has been removed
     */
    public function testupdateFiles()
    {
        $newRepairTest = new supp_LanguageRepairs();
        $testFile = "<?php\n\$dictionary['Account']['fields']['account_type']['default']='one&one';";
        $testData = $newRepairTest->updateFiles('one&one', 'one_one', $testFile);
        $this->assertTrue(strstr($testData, 'one_one') !== false);
        $this->assertTrue(strstr($testData, 'one&one') === false);
    }

    /**
     * This test makes sure that both the default_value and the dependencies of a custom field are updated.
     */
    public function testupdateFieldsMetaDataTable()
    {
        $newRepairTest = new supp_LanguageRepairs();
        $fieldData=array('Accounts'=>'test_list');
        $newRepairTest->updateFieldsMetaDataTable($fieldData, "one&one", "one_one", true);
        $sql = "SELECT default_value,ext4 FROM fields_meta_data WHERE id='TEST3'";
        $hash = $GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['default_value'], 'one_one') !== false);
        $this->assertTrue(strstr($hash['default_value'], 'one&one') === false);
        $this->assertTrue(strstr($hash['ext4'], 'one_one') !== false);
        $this->assertTrue(strstr($hash['ext4'], 'one&one') === false);
    }

    /**
     * This test see if the correct value is updated in the correct table for the supplied metadata
     */
    public function testupdateDatabase() {
        $newRepairTest = new supp_LanguageRepairs();
        $fieldData=array('Accounts'=>'industry');
        $newRepairTest->updateDatabase($fieldData, "one&one", "one_one", true);
        $sql = "SELECT industry FROM accounts WHERE id='TEST4'";
        $hash = $GLOBALS['db']->fetchOne($sql);
        $this->assertTrue(strstr($hash['industry'], 'one_one') !== false);
        $this->assertTrue(strstr($hash['industry'], 'one&one') === false);
    }

    /**
     * This test makes sure the code can find the field name and module if given just the list name.
     */
    public function testfindListField() {
        $newRepairTest = new supp_LanguageRepairs();
        $testFieldDefLookup=$newRepairTest->findListField('industry_dom');
        $this->assertTrue($testFieldDefLookup['Accounts']=='industry');
    }

    /**
     * This test makes sure the index names are all fixed as intended
     */
    public function testfixIndexNames() {
        $newRepairTest = new supp_LanguageRepairs();
        $this->assertTrue($newRepairTest->fixIndexNames('one&one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one-one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one/one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one & one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one - one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one / one')=='one_one');
        $this->assertTrue($newRepairTest->fixIndexNames('one{one}')=='oneone');
    }

    private function mockupLanguageTestRecords()
    {
        $sql = "INSERT INTO workflow_triggershells (id, deleted, date_entered, date_modified, modified_user_id, created_by, field, type, frame_type, eval, parent_id, show_past, rel_module, rel_module_type, parameters)
                     VALUES ('TEST1', '0', '2016-02-09 18:27:01', '2016-02-09 18:27:01', '1', '1', 'single_c', 'compare_specific', 'Primary', ' (\$focus->fetched_row[''single_c''] == ''one&one'')&& (isset(\$focus->single_c) && \$focus->single_c == ''two-two'')', '1', '1', NULL, 'any', NULL)";
        $GLOBALS['db']->query($sql);
        $sql = "INSERT INTO workflow_actions (id, deleted, date_entered, date_modified, modified_user_id, created_by, field, value, set_type, adv_type, parent_id, ext1, ext2, ext3)
                     VALUES ('TEST2', '0', '2016-02-09 19:58:01', '2016-02-09 19:58:01', '1', '1', 'multiple_c', 'one&one^,^four(four^,^nine_nine^,^seven / seven^,^one&one^,^ten(ten)', 'Basic', '', '1', '', '', '')";
        $GLOBALS['db']->query($sql);
        $sql = "INSERT INTO fields_meta_data (id, name, vname, comments, help, custom_module, type, len, required, default_value, date_modified, deleted, audited, massupdate, duplicate_merge, reportable, importable, ext1, ext2, ext3, ext4)
                     VALUES ('TEST3', 'multiple_c', 'LBL_MULTIPLE', '', '', 'Accounts', 'multienum', '100', '0', '^one&one^,^two-two^', '2016-02-09 16:15:02', '0', '0', '0', '0', '1', 'true', 'test_list', '', '', 'a:2:{s:7:\"default\";s:23:\"^two-two^,^one&one^\";s:10:\"dependency\";s:0:\"\";}')";
        $GLOBALS['db']->query($sql);
        $sql ="INSERT INTO accounts (id, name, date_entered, date_modified, modified_user_id, created_by, description, deleted, assigned_user_id, team_id, team_set_id, account_type, industry, annual_revenue, phone_fax, billing_address_street, billing_address_city, billing_address_state, billing_address_postalcode, billing_address_country, rating, phone_office, phone_alternate, website, ownership, employees, ticker_symbol, shipping_address_street, shipping_address_city, shipping_address_state, shipping_address_postalcode, shipping_address_country, parent_id, sic_code, campaign_id)
                     VALUES ('TEST4', 'Lang File tester', '2016-02-08 14:54:38', '2016-02-09 23:57:15', '1', '1', NULL, '0', '1', '1', '1', 'one_one', 'one&one', NULL, NULL, '', '', '', '', '', NULL, '', NULL, '', NULL, NULL, NULL, '', '', '', '', '', '', NULL, '')";
        $GLOBALS['db']->query($sql);
    }

    private function teardownLanguageTestRecords()
    {
        $sql = "DELETE FROM workflow_triggershells WHERE id='TEST1'";
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM workflow_actions WHERE id='TEST2'";
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM fields_meta_data WHERE id='TEST3'";
        $GLOBALS['db']->query($sql);
        $sql = "DELETE FROM accounts WHERE id='TEST4'";
        $GLOBALS['db']->query($sql);
    }
}