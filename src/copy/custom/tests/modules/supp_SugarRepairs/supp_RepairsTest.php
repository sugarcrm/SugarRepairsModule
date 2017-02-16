<?php
require_once 'custom/tests/modules/supp_SugarRepairs/Stubs/RepairStub.php';

/**
 * @group support
 * @group default
 */
class supp_RepairsTest extends Sugar_PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $GLOBALS['current_user']->getSystemUser();
        $GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
        $GLOBALS['log'] = LoggerManager::getLogger('SugarCRM');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Testss the getCustomModules Method
     */
    public function testGetCustomModules()
    {
        $Repair = new RepairStub();
        $modules = $Repair->getCustomModules();
        $this->assertArrayHasKey('supp_SugarRepairs', $modules);
        $this->assertEquals($modules['supp_SugarRepairs'], 'modules/supp_SugarRepairs');
    }

    /**
     * Tests the getModuleTemplateFile method
     */
    public function testGetModuleTemplateFile()
    {
        $Repair = new RepairStub();

        $this->assertEquals($Repair->getModuleTemplateFile('supp_SugarRepairs', 'vardefs.php'), 'include/SugarObjects/templates/issue/vardefs.php');

        if (version_compare($GLOBALS['sugar_version'], '7.0', '<')) {
            $this->assertEquals($Repair->getModuleTemplateFile('supp_SugarRepairs', 'clients/base/views/record/record.php'), NULL);
        } else {
            $this->assertEquals($Repair->getModuleTemplateFile('supp_SugarRepairs', 'clients/base/views/record/record.php'), 'include/SugarObjects/templates/issue/clients/base/views/record/record.php');
        }
        $this->assertEquals($Repair->getModuleTemplateFile('supp_SugarRepairs', 'test.php'), NULL);
    }

}