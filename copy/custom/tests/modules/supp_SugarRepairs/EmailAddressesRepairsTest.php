<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_EmailAddressRepairs.php');

/**
 * @group support
 * @group emailAddresses
 */
class suppSugarRepairsEmailAddressesRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $reportIDs = array();

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");

        $bean_module = "supp_unitTest";
        $bean_id = "3272dd2c-e26e-11e5-8409-1e78fe93e4be";

        // newer record
        $record_id = "b1c36934-e26e-11e5-8409-1e78fe93e4be";
        $deleted = 0;
        $date_created = "2016-02-29";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,deleted,date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);

        // older record
        $record_id = "d891c182-e26e-11e5-8409-1e78fe93e4be";
        $deleted = 0;
        $date_created = "2015-12-28";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,deleted,date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);

        // oldest record, but deleted...
        $record_id = "5266af86-e26f-11e5-8409-1e78fe93e4be";
        $deleted = 1;
        $date_created = "2014-01-01";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,deleted,date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);
    }

    public function tearDown()
    {
        parent::tearDown();

        $sql = "
            DELETE FROM email_addr_bean_rel
            WHERE id in ('b1c36934-e26e-11e5-8409-1e78fe93e4be','d891c182-e26e-11e5-8409-1e78fe93e4be','5266af86-e26f-11e5-8409-1e78fe93e4be')
        ";
        $res = $GLOBALS['db']->query($sql);
    }

    /**
     * Test for 
     * @covers supp_EmailAddressRepairs::getNewPrimaryAddress
     */
    public function testGetNewPrimaryAddress()
    {

        $bean_module = "supp_unitTest";
        $bean_id = "3272dd2c-e26e-11e5-8409-1e78fe93e4be";

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();

        // should return the oldest, but not deleted
        $id = $supp_EmailAddressTest->getNewPrimaryAddress($bean_module, $bean_id);
        $this->assertEquals("d891c182-e26e-11e5-8409-1e78fe93e4be",$id);

        // should return false
        $id = $supp_EmailAddressTest->getNewPrimaryAddress("supp_doesnt_exist", "6a983312-e270-11e5-8409-1e78fe93fake");
        $this->assertFalse($id);
        
    }

    /**
     * Test for 
     * @covers supp_EmailAddressRepairs::setPrimaryAddress
     */
    public function testSetPrimaryAddress()
    {

        $id = "d891c182-e26e-11e5-8409-1e78fe93e4be";

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $results = $supp_EmailAddressTest->setPrimaryAddress($id);

        // should return true
        $this->assertsTrue($results);

        $sql = "
            SELECT primary_address 
            FROM email_addr_bean_rel
            WHERE id = '$id'
        ";
        $returnedPrimary = $GLOBALS['db']->getOne($sql);

        // should return "1"
        $this->assertEquals("1",$returnedPrimary);
    }

    /**
     * Test for 
     * @covers supp_EmailAddressRepairs::repairPrimaryEmailAddresses
     */
    public function testRepairPrimaryEmailAddresses()
    {
        //not sure on how to test this one yet...
    }


}