<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');
require_once('ModuleInstall/ModuleInstaller.php');
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
        $GLOBALS['current_user']->is_admin=1;
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
        $testFile = "<?php\n\$GLOBALS['app_list_strings']['test_list']=array (\n\n\n\n\n'one&one' => 'One',\n'one-one' => 'Two',\n'one/one' => 'Three',\n'one(one)' => 'Four',\n'one+one' => 'Five',\n);\n";
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $tokenList = $newRepairTest->processTokenList($testFile);

        $this->assertTrue($tokenList[7][0][1] == "'one_one'");
        $this->assertTrue($tokenList[8][0][1] == "'one_one'");
        $this->assertTrue($tokenList[9][0][1] == "'one_one'");
        $this->assertTrue($tokenList[10][0][1] == "'oneone'");
        $this->assertTrue($tokenList[11][0][1] == "'one_one'");
    }

    /**
     * This test see if the correct value is updated in the correct table for the supplied metadata
     */
    public function testupdateDatabase()
    {
        $account = SugarTestAccountUtilities::createAccount();
        $account->industry = "one&one";
        $account->save();


        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $fieldData = array('Accounts' => array(0=>'industry'));
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
        $this->assertTrue($testFieldDefLookup['Accounts'] == array(0=>'industry'));
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
        $this->assertTrue($newRepairTest->getValidLanguageKeyName('one+one') == 'one_one');
    }

    public function testCustomMultiEnums()
    {
        $this->setUpCustomMultiEnums();
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $newRepairTest->runQRAR();
        $newRepairTest->execute(array('t' => false));

        $hash = $GLOBALS['db']->fetchOne("SELECT * FROM accounts_cstm WHERE id_c = 'unittest517'");

        $this->assertEquals("^One^,^_Two_^,^_Three_^,^_Four_^", $hash['unittest517_c']);
    }

    public function testCustomSharingEnums()
    {
        $this->setUpCustomSharingEnums();
        $newRepairTest = new supp_LanguageRepairs();
        $newRepairTest->setTesting(false);
        $newRepairTest->runQRAR();
        $newRepairTest->execute(array('t' => false));

        $hash = $GLOBALS['db']->fetchOne("SELECT * FROM accounts_cstm WHERE id_c = 'unittest715'");

        $this->assertEquals("^One^,^_Two_^,^_Three_^,^_Four_^", $hash['unittest517a_c']);
        $this->assertEquals("One", $hash['unittest517b_c']);
    }

    private function setUpCustomMultiEnums()
    {
        $custom_field=array(
            'name' => 'unittest517',
            'label' => 'LBL_UNITTEST517',
            'type' => 'multienum',
            'module' => 'Accounts',
            'help' => 'Text Field Help Text',
            'comment' => 'A test Field for Language repair Unit tests',
            'default_value' => '',
            'ext1' => 'unittest517_list',
            'max_size' => 100,
            'required' => false, // true or false
            'reportable' => true, // true or false
            'audited' => false, // true or false
            'importable' => 'true', // 'true', 'false', 'required'
            'duplicate_merge' => false, // true or false
        );
        $langFileText = "<?php\n\$app_list_strings['unittest517_list']=array(''=>'',\n'(One)'=>'One',\n'-Two-'=>'Two',\n'&Three&'=>'Three',\n'+Four+'=>'Four'\n);";
        sugar_file_put_contents("custom/Extension/application/Ext/Language/en_us.sugar_unittest517_list.php", $langFileText);

        $mb = new ModuleInstaller();
        $mb->install_custom_fields(array($custom_field));

        $GLOBALS['db']->query("DELETE FROM accounts_cstm WHERE id_c='unittest517' OR id_c='unittest715';");

        $sql = "INSERT INTO accounts_cstm (id_c, unittest517_c)
                VALUES ('unittest517', '^(One)^,^-Two-^,^&Three&^,^+Four+^')";
        $GLOBALS['db']->query($sql);
    }

    private function setUpCustomSharingEnums()
    {
        $custom_field1=array(
            'name' => 'unittest517a',
            'label' => 'LBL_UNITTEST517a',
            'type' => 'multienum',
            'module' => 'Accounts',
            'help' => 'Text Field Help Text',
            'comment' => 'A test Field for Language repair Unit tests',
            'default_value' => '',
            'ext1' => 'unittest517a_list',
            'max_size' => 100,
            'required' => false, // true or false
            'reportable' => true, // true or false
            'audited' => false, // true or false
            'importable' => 'true', // 'true', 'false', 'required'
            'duplicate_merge' => false, // true or false
        );
        $custom_field2=array(
            'name' => 'unittest517b',
            'label' => 'LBL_UNITTEST517b',
            'type' => 'enum',
            'module' => 'Accounts',
            'help' => 'Text Field Help Text',
            'comment' => 'A test Field for Language repair Unit tests',
            'default_value' => '(one)',
            'ext1' => 'unittest517a_list',
            'max_size' => 100,
            'required' => false, // true or false
            'reportable' => true, // true or false
            'audited' => false, // true or false
            'importable' => 'true', // 'true', 'false', 'required'
            'duplicate_merge' => false, // true or false
        );
        $langFileText = "<?php\n\$app_list_strings['unittest517a_list']=array(''=>'',\n'(One)'=>'One',\n'-Two-'=>'Two',\n'&Three&'=>'Three',\n'+Four+'=>'Four'\n);";
        sugar_file_put_contents("custom/Extension/application/Ext/Language/en_us.sugar_unittest517_list.php", $langFileText);

        $mb = new ModuleInstaller();
        $mb->install_custom_fields(array($custom_field1));
        $mb->install_custom_fields(array($custom_field2));

        $GLOBALS['db']->query("DELETE FROM accounts_cstm WHERE id_c='unittest517' OR id_c='unittest715';");

        $sql = "INSERT INTO accounts_cstm (id_c, unittest517_c, unittest517a_c, unittest517b_c) VALUES ('unittest715', '', '^(One)^,^-Two-^,^&Three&^,^+Four+^', '(One)');";
        $GLOBALS['db']->query($sql);
    }

    private function tearDownCustomMultiEnums()
    {
        //the Account and the account_cstm record will be deleted in the tearDown() function
        $custom_field=array(
            'name' => 'unittest517_c',
            'module' => 'Accounts',
        );

        $mb = new ModuleInstaller();
        $mb->uninstall_custom_fields(array($custom_field));
        @unlink("custom/Extension/modules/Accounts/Ext/Vardefs/sugarfield_unittest517_c.php");
        @unlink("custom/Extension/application/Ext/Language/en_us.sugar_unittest517_list.php");
    }

    private function tearDownCustomSharingEnums()
    {
        @unlink("custom/Extension/modules/Accounts/Ext/Vardefs/sugarfield_unittest517a_c.php");
        @unlink("custom/Extension/modules/Accounts/Ext/Vardefs/sugarfield_unittest517b_c.php");
        @unlink("custom/Extension/application/Ext/Language/en_us.sugar_unittest517_list.php");
        $custom_field1=array(
            'name' => 'unittest517a_c',
            'module' => 'Accounts',
        );
        $custom_field2=array(
            'name' => 'unittest517b_c',
            'module' => 'Accounts',
        );
        $mb = new ModuleInstaller();
        $mb->uninstall_custom_fields(array($custom_field1));
        $mb->uninstall_custom_fields(array($custom_field2));
    }

    public static function tearDownAfterClass()
    {
        suppSugarRepairsLanguageRepairs::tearDownCustomMultiEnums();
        suppSugarRepairsLanguageRepairs::tearDownCustomSharingEnums();
        require_once('modules/Administration/QuickRepairAndRebuild.php');
        $RAC = new RepairAndClear();
        $actions = array('clearAll');
        $RAC->repairAndClearAll($actions, array('All Modules'), false, false);
    }
    public static function setUpBeforeClass()
    {
        suppSugarRepairsLanguageRepairs::tearDownCustomMultiEnums();
        suppSugarRepairsLanguageRepairs::tearDownCustomSharingEnums();
        $GLOBALS['db']->query("DELETE FROM accounts_cstm WHERE id_c='unittest517' OR id_c='unittest715';");
    }
}