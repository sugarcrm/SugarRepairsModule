<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_EmailAddressRepairs extends supp_Repairs
{
    protected $loggerTitle = "EmailAddress";
    protected $foundIssues = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieves an id to set as the primary email address
     * @param string $bean_module Module name
     * @param string $bean_id Id of the module record
     * @return $id|string Id of the email_addr_bean_rel
     */
    public function getNewPrimaryAddress($bean_module, $bean_id)
    {
        $sql = "
            SELECT id
            FROM email_addr_bean_rel
            WHERE bean_module = '$bean_module' AND bean_id = '$bean_id' AND deleted = '0'
            ORDER BY date_created
        ";
        $id = $GLOBALS['db']->getOne($sql);
        return $id;
    }

    /**
     * Sets a email address as the primary
     *
     * @param $id
     * @return bool
     */
    public function setPrimaryAddress($id)
    {
        $results = false;
        $sql = "
            UPDATE email_addr_bean_rel
            SET primary_address = '1'
            WHERE id = '$id'
        ";
        if (!$this->isTesting) {
            $this->logChange("-> Updating email address relationship as primary :: id->{$id}");
            $results = $this->updateQuery($sql);
        } else {
            $this->logChange("-> Will update email address relationship as primary :: id->{$id}");
        }

        return $results;
    }

    /**
     * Repairs beans missing a primary email address
     */
    public function repairPrimaryEmailAddresses()
    {

        $this->logAll("Repairing records with no primary email address.");

        // select any email addresses tied to a bean
        // that do not have a primary email address specified
        $sql = "
            SELECT eabr.bean_module,eabr.bean_id,count(*) num_email_addrs
            FROM email_addr_bean_rel eabr
                LEFT JOIN email_addr_bean_rel eabr_primary
                    ON
                        eabr_primary.bean_module = eabr.bean_module AND
                        eabr_primary.bean_id = eabr.bean_id AND
                        eabr_primary.deleted= '0' AND
                        eabr_primary.primary_address = '1'
            WHERE eabr.deleted = '0' AND eabr_primary.id IS NULL
            GROUP BY eabr.bean_module,eabr.bean_id
        ";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {

            $bean_module = $row['bean_module'];
            $bean_id = $row['bean_id'];

            $this->log("-> Processing record :: {$bean_module}->{$bean_id}");
            $this->foundIssues[] = array(
                "bean_module" => $bean_module,
                "bean_id" => $bean_id,
            );
            $id = $this->getNewPrimaryAddress($bean_module, $bean_id);
            if ($id === false) {
                $this->logAction("-> Unable to find a primary email address for {$bean_module}->{$bean_id}. This will have to be fixed manually.");
            }

            $results = $this->setPrimaryAddress($id);
            if (!$results == true && !$this->isTesting) {
                $this->logAction("-> Failed to update primary email address for {$bean_module}->{$bean_id}. This will have to be fixed manually.");
            }
        }

        $foundIssuesCount = count($this->foundIssues);
        $this->log("Found {$foundIssuesCount} beans missing a primary email address.");
    }

    /**
     * Repairs any emails with multiple primary addresses
     */
    public function repairMultiplePrimaryAddresses()
    {
        $this->logAll("Repairing records with multiple primary email addresses.");
        //This query returns all records that are part of a multiple primary address group and
        // the most recent email address which will be used as the primary.
        $sql = "
          SELECT count(id) AS howMany, bean_id, bean_module, email_address_id
          FROM email_addr_bean_rel
          WHERE primary_address=1 AND deleted=0
          GROUP BY bean_id
          HAVING howMany>1
          ORDER BY date_modified";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            //Get all the email address relationship records EXCEPT the one we are
            //  keeping for the primary
            $findSQL = "SELECT id FROM email_addr_bean_rel
                        WHERE bean_id = '{$row['bean_id']}' AND
                              email_address_id != '{$row['email_address_id']}' AND
                              deleted = 0
                      ";
            $findResult = $GLOBALS['db']->query($findSQL);
            while ($findRow = $GLOBALS['db']->fetchByAssoc($findResult)) {
                $repairSQL = "UPDATE email_addr_bean_rel
                              SET primary_address=0
                              WHERE id = '{$findRow['id']}'
                              ";
                if (!$this->isTesting) {
                    $this->logChange("-> Updating the primary email address for {$row['bean_module']}->{$row['bean_id']}");
                    $this->updateQuery($repairSQL);
                } else {
                    $this->logChange("-> Will update the primary email address for {$row['bean_module']}->{$row['bean_id']}");
                }
            }
        }
    }

    /**
     * Identifies customers who have opted out of campaigns and opts out their email addresses
     */
    public function repairOptedOutAddresses()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.7.2.0', '<') || version_compare($GLOBALS['sugar_version'], '7.8.0.0', '>')) {
            $this->log('Repair does not apply to this version.');
            return false;
        }

        $findOptedOut = "SELECT distinct target_id, target_type
                         FROM campaign_log
                         WHERE activity_type = 'removed'
                         AND deleted = 0
                         AND target_type not in ('Users')
                         AND date_modified > '2016-10-19 22:48:00'
                         ORDER BY target_type, target_id
                         ";

        $findResult = $GLOBALS['db']->query($findOptedOut);

        while ($row = $GLOBALS['db']->fetchByAssoc($findResult)) {

            $sql = "SELECT ea.id FROM email_addresses ea
                    INNER JOIN email_addr_bean_rel eabr ON eabr.email_address_id = ea.id
                    WHERE eabr.bean_id = %s
                    AND eabr.bean_module = %s
                    AND eabr.deleted = 0
                    AND ea.opt_out = 0
                    ";

            $sql = sprintf($sql, $GLOBALS['db']->quoted($row['target_id']), $GLOBALS['db']->quoted($row['target_type']));
            $related = $GLOBALS['db']->query($sql);

            while ($emailaddress = $GLOBALS['db']->fetchByAssoc($related)) {
                if (!$this->isTesting) {
                    $this->logChange("-> Opting out EmailAddress->{$emailaddress['id']} for {$row['target_type']}->{$row['target_id']}");
                    $repairSQL = "UPDATE email_addresses SET opt_out = 1 where id ='{$emailaddress['id']}'";
                    $this->updateQuery($repairSQL);
                } else {
                    $this->logChange("-> Will opt out EmailAddress->{$emailaddress['id']} for {$row['target_type']}->{$row['target_id']}");
                }
            }
        }
    }


    /**
     * Executes the EmailAddress repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if ($this->backupTable(array('email_addr_bean_rel', 'email_addresses'), $stamp)) {
            $this->repairPrimaryEmailAddresses();
            $this->repairMultiplePrimaryAddresses();
            $this->repairOptedOutAddresses();
        }
    }
}
