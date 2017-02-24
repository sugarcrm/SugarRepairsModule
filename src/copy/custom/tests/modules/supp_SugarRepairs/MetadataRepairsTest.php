<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_MetadataRepairs.php');

/**
 * Class MetadataRepairsTest
 * @group support
 * @group metadata
 */
class MetadataRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{
    protected $class = 'supp_MetadataRepairs';

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $GLOBALS['current_user']->is_admin = 1;
        $GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    protected function getMethod($name)
    {
        $class = new ReflectionClass($this->class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Tests the hasAuditBtnDef method
     */
    public function testHasAuditBtnDef()
    {
        $method = $this->getMethod('hasAuditBtnDef');
        $Repair = new supp_MetadataRepairs();

        $btnDefinition = array(
            'name' => 'audit_button'
        );
        $definition = array(
            $btnDefinition
        );


        $this->assertEquals($method->invokeArgs($Repair, array($definition)), $btnDefinition);

        $definition = array(
            array(
                'name' => 'close_button'
            ),
            $btnDefinition
        );

        $this->assertEquals($method->invokeArgs($Repair, array($definition)), $btnDefinition);

        $definition = array(
            array(
                'name' => 'save_button'
            ),
            array(
                'type' => 'actiondropdown',
                'buttons' => array(
                    $btnDefinition
                )
            )
        );
        $this->assertEquals($method->invokeArgs($Repair, array($definition)), $btnDefinition);

        $definition = array(
            array(
                'name' => 'save_button'
            ),
            array(
                'name' => 'cancel_button'
            )
        );
        $this->assertEquals($method->invokeArgs($Repair, array($definition)), FALSE);

    }

    /**
     * Tests the repairMissingAuditButtons Repair
     */
    public function testRepairMissingAuditButtons()
    {
        global $sugar_version;
        if (!version_compare($sugar_version, '7.7', '>=')) {
            $this->markTestSkipped('Skipping test');
            return;
        }
        $auditButtonExists = FALSE;
        $Repair = new supp_MetadataRepairs();
        $Repair->setTesting(false);
        $Repair->repairMissingAuditButtons(array('supp_SugarRepairs' => 'modules/supp_SugarRepairs/'));
        include 'modules/supp_SugarRepairs/clients/base/views/record/record.php';
        $this->assertArrayHasKey('buttons', $viewdefs['supp_SugarRepairs']['base']['view']['record']);


    }
}