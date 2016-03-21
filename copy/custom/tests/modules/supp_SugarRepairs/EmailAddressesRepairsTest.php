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
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,email_address_id ,deleted, date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','fakeemailaddrid' ,'$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);

        // older record
        $record_id = "d891c182-e26e-11e5-8409-1e78fe93e4be";
        $deleted = 0;
        $date_created = "2015-12-28";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,email_address_id ,deleted, date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','fakeemailaddrid' ,'$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);

        // oldest record, but deleted...
        $record_id = "5266af86-e26f-11e5-8409-1e78fe93e4be";
        $deleted = 1;
        $date_created = "2014-01-01";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,email_address_id ,deleted, date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','fakeemailaddrid' ,'$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);

        // one more test record for the entire process to test
        $record_id = "7d1da3a8-e4ac-11e5-9c96-fc4c85ba1538";
        $bean_id = "standalone26e-11e5-8409-1e78fe93e4be";
        $deleted = 0;
        $date_created = "2016-02-29";

        $sql = "
            INSERT INTO email_addr_bean_rel(id,bean_module,bean_id,email_address_id ,deleted, date_created, primary_address) 
            VALUES('$record_id','$bean_module','$bean_id','fakeemailaddrid' ,'$deleted','$date_created','0')
        ";
        $res = $GLOBALS['db']->query($sql);
    }

    public function tearDown()
    {
        parent::tearDown();

        $sql = "
            DELETE FROM email_addr_bean_rel
            WHERE id in ('b1c36934-e26e-11e5-8409-1e78fe93e4be','d891c182-e26e-11e5-8409-1e78fe93e4be','5266af86-e26f-11e5-8409-1e78fe93e4be','7d1da3a8-e4ac-11e5-9c96-fc4c85ba1538')
        ";
        $res = $GLOBALS['db']->query($sql);
    }

    /**
     * Test for getting the correct new primary address
     * @covers supp_EmailAddressRepairs::getNewPrimaryAddress
     */
    public function testGetNewPrimaryAddress()
    {

        $bean_module = "supp_unitTest";
        $bean_id = "3272dd2c-e26e-11e5-8409-1e78fe93e4be";

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);

        // should return the oldest, but not deleted
        $id1 = $supp_EmailAddressTest->getNewPrimaryAddress($bean_module, $bean_id);
        $this->assertEquals("d891c182-e26e-11e5-8409-1e78fe93e4be", $id1);

        // should return false
        $id2 = $supp_EmailAddressTest->getNewPrimaryAddress("supp_doesnt_exist", "6a983312-e270-11e5-8409-1e78fe93fake");
        $this->assertFalse($id2);
        
    }

    /**
     * Test for setting the primary address on a record
     * @covers supp_EmailAddressRepairs::setPrimaryAddress
     */
    public function testSetPrimaryAddress()
    {

        $id = "d891c182-e26e-11e5-8409-1e78fe93e4be";

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);
        $results = $supp_EmailAddressTest->setPrimaryAddress($id);

        // should return true
        $this->assertTrue($results);

        $sql = "
            SELECT primary_address 
            FROM email_addr_bean_rel
            WHERE id = '$id'
        ";
        $returnedPrimary = $GLOBALS['db']->getOne($sql);

        // should return "1"
        $this->assertEquals("1", $returnedPrimary);
    }

    /**
     * Test for running entire repair
     * @covers supp_EmailAddressRepairs::repairPrimaryEmailAddresses
     * @covers supp_EmailAddressRepairs::execute
     */
    public function testRepairPrimaryEmailAddresses()
    {

        $id = "7d1da3a8-e4ac-11e5-9c96-fc4c85ba1538";

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);

        $supp_EmailAddressTest->execute(array('test' => false));

        $sql = "
            SELECT primary_address 
            FROM email_addr_bean_rel
            WHERE id = '$id'
        ";
        $returnedPrimary = $GLOBALS['db']->getOne($sql);

        // should return "1"
        $this->assertEquals("1", $returnedPrimary);
    }
}
