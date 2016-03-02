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
    }

    public function tearDown()
    {
        parent::tearDown();
        SugarTestAccountUtilities::removeAllCreatedAccounts();
    }

    /**
     * In this test we are checking that the T_VARIABLE, T_ARRAY_NAME & T_ARRAY_KEY tags
     * have been placed the white space removed and that the $GLOBALS has been corrected properly
     */
    public function testGetAnnotatedTokenList()
    {
        $testFile = "<?php\n\$GLOBALS['app_list_strings']['test_list']=array (\n\n\n\n\n'one&one' => 'One');\n";
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $tokenList = $newRepairTest->getAnnotatedTokenList($testFile);

        $this->assertTrue(substr($tokenList[0][1], 0, 5) == "<?php");
        $this->assertTrue($tokenList[0]['TOKEN_NAME'] == 'T_OPEN_TAG');

        $this->assertTrue($tokenList[1][1] == "\$app_list_strings");
        $this->assertTrue($tokenList[1]['TOKEN_NAME'] == 'T_VARIABLE');

        $this->assertTrue($tokenList[3][1] == "'test_list'");
        $this->assertTrue($tokenList[3]['TOKEN_NAME'] == 'T_ARRAY_NAME');

        $this->assertTrue($tokenList[8][1] == "'one&one'");
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
        $newRepairTest->setTesting(false);
        $tokenList = $newRepairTest->processTokenList($testFile);

        $this->assertTrue($tokenList[7][0][1] == "'one_one'");
        $this->assertTrue($tokenList[8][0][1] == "'one_one'");
        $this->assertTrue($tokenList[9][0][1] == "'one_one'");
        $this->assertTrue($tokenList[10][0][1] == "'oneone'");
    }

    /**
     * This test see if the correct value is updated in the correct table for the supplied metadata
     */
    public function testupdateDatabase()
    {
        $id = create_guid();
        $account = SugarTestAccountUtilities::createAccount($id);
        $account->industry = "one&one";
        $account->save();


        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $fieldData = array('Accounts' => 'industry');
        $newRepairTest->updateDatabase($fieldData, "one&one", "one_one");

        $account->retrieve();
        $this->assertEquals("one_one", $account->industry);
    }

    /**
     * This test makes sure the code can find the field name and module if given just the list name.
     */
    public function testfindListField()
    {
        $newRepairTest = new supp_LanguageRepairs();
        $testFieldDefLookup = $newRepairTest->findListField('industry_dom');
        $this->assertTrue($testFieldDefLookup['Accounts'] == 'industry');
    }

    /**
     * This test makes sure the index names are all fixed as intended
     */
    public function testGetValidLanguageKeyName()
    {
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one&one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one-one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one/one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one & one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one - one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one / one') == 'one_one');
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one(one)') == 'oneone');
    }
}