<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
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
    }

    public function tearDown()
    {
        parent::tearDown();

        SugarTestContactUtilities::removeAllCreatedContacts();

        $sql = "
            DELETE FROM campaign_log
            WHERE id in ('08815978-9c6b-11e6-b2b0-029d6f706c63')
        ";

        $GLOBALS['db']->query($sql);
    }

    /**
     * Test for running entire repair
     * @covers supp_EmailAddressRepairs::repairPrimaryEmailAddresses
     */
    public function testRepairPrimaryEmailAddresses()
    {
        $contact = SugarTestContactUtilities::createContact();
        $contact->email1 = 'test@test.com';
        $contact->save();
        $contact->retrieve();

        $sea = new SugarEmailAddress;
        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 1);

        //unset primary
        $GLOBALS['db']->query("UPDATE email_addr_bean_rel SET primary_address = 0 WHERE id ='{$email_addresses[0]['id']}'");

        //verify
        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 0);

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);
        $supp_EmailAddressTest->repairPrimaryEmailAddresses();

        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 1);
    }

    /**
     * Test for running entire repair
     * @covers supp_EmailAddressRepairs::repairMultiplePrimaryAddresses
     */
    public function testRepairMultiplePrimaryEmailAddresses()
    {
        $contact = SugarTestContactUtilities::createContact();
        $contact->email1 = 'test1@test.com';
        $contact->email2 = 'test2@test.com';
        $contact->save();
        $contact->retrieve();

        $sea = new SugarEmailAddress;
        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 1);
        $this->assertEquals($email_addresses[1]['primary_address'], 0);

        //set both as primary and modify date to be later
        $time = $GLOBALS['timedate']->getNow()->modify('+1 minutes')->asDb();
        $GLOBALS['db']->query("UPDATE email_addr_bean_rel SET date_modified = '{$time}' WHERE id ='{$email_addresses[0]['id']}'");
        $GLOBALS['db']->query("UPDATE email_addr_bean_rel SET primary_address = 1 WHERE id ='{$email_addresses[1]['id']}'");

        //verify
        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 1);
        $this->assertEquals($email_addresses[1]['primary_address'], 1);

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);
        $supp_EmailAddressTest->repairMultiplePrimaryAddresses();

        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        $this->assertEquals($email_addresses[0]['primary_address'], 1);
        $this->assertEquals($email_addresses[1]['primary_address'], 0);
    }

    /**
     * Test for ensuring opt outs
     * @covers supp_EmailAddressRepairs::repairOptedOutAddresses
     */
    public function testAssertOptOut()
    {
        $contact = SugarTestContactUtilities::createContact();
        $contact->email1 = 'test@test.com';
        $contact->save();
        $contact->retrieve();

        $sea = new SugarEmailAddress;
        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);

        $this->assertEquals($email_addresses[0]['opt_out'], 0);

        $sql = "
        INSERT INTO `campaign_log`
        (`id`, `campaign_id`, `target_tracker_key`, `target_id`, `target_type`, `activity_type`, `activity_date`, `related_id`, `related_type`, `archived`, `hits`, `list_id`, `deleted`, `date_modified`, `more_information`, `marketing_id`)
        VALUES
        ('08815978-9c6b-11e6-b2b0-029d6f706c63', '36a7321c-cff8-6479-75b0-57ed64d0f7a1', '08813fc4-9c6b-11e6-9754-029d6f706c63', '{$contact->id}', '{$contact->module_name}', 'removed', '2016-10-27 17:30:00', NULL, NULL, 0, 0, '0c6b0988-9ac5-11e6-ba4f-02134eb59a31', 0, '2017-10-27 17:30:01', '', '94265e49-a896-ffac-db0b-58052b878d4f');
        ";

        $GLOBALS['db']->query($sql);

        $supp_EmailAddressTest = new supp_EmailAddressRepairs();
        $supp_EmailAddressTest->setTesting(false);

        $supp_EmailAddressTest->repairOptedOutAddresses();

        $email_addresses = $sea->getAddressesByGUID($contact->id, $contact->module_name);
        print_r($email_addresses);
        $this->assertEquals($email_addresses[0]['opt_out'], 1);
    }
}
