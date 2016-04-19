<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');
require_once('modules/supp_SugarRepairs/Classes/supp_TeamSets.php');

class supp_TeamSetRepairs extends supp_Repairs
{
    protected $loggerTitle = "Team Sets";

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Removes duplicate teams in a team set
     */
    public function removeDuplicateTeams()
    {
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

        $this->log("Checking for duplicates in team sets...");

        //Fix team set duplicate teams --------------------------------------
        $sql = "SELECT team_set_id, team_id, count(*) FROM team_sets_teams GROUP BY team_set_id, team_id having count(*) > 1";

        $result = $GLOBALS['db']->query($sql);
        $teamSetBean = new supp_TeamSets();

        $foundIssues = 0;
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $foundIssues++;
            if (!$this->isTesting) {
                $this->logChange("Updating team set teams for {$row['team_set_id']}");
                $teamSetBean->id = $row['team_set_id'];
                $teamSetBean->removeTeamFromSet($row['team_id']);
                $teamSetBean->addTeamToSet($row['team_id']);
            } else {
                $this->logChange("-> Will update duplicate team set: " . $row['id']);
            }
        }

        $this->log("Found {$foundIssues} duplicates");
    }

    /**
     * Fixes team sets with bad team counts
     */
    public function repairTeamCounts()
    {
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

        $this->log("Checking for bad team counts in team sets...");

        //Fix teams with bad team counts --------------------------------------
        $sql = "SELECT team_sets.id, team_sets.team_count, count(*) as actual_count FROM team_sets INNER JOIN team_sets_teams on team_sets.id = team_sets_teams.team_set_id and team_sets_teams.deleted = 0 INNER JOIN teams on teams.id = team_sets_teams.team_id and teams.deleted = 0 WHERE team_sets.deleted = 0 GROUP BY team_sets.id HAVING count(*) <> team_sets.team_count";

        $result = $GLOBALS['db']->query($sql);
        $teamSetBean = new supp_TeamSets();

        $foundIssues = 0;
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $foundIssues++;

            if (!$this->isTesting) {
                $this->logChange("Updating team set " . $row['id']);
                $teamSetBean->id = $row['id'];
                $teams = $teamSetBean->fixTeamCount($row['id']);
            } else {
                $this->logChange("-> Will update invalid team set: " . $row['id']);
            }
        }

        $this->log("Found {$foundIssues} bad team counts");
    }

    /**
     * Executes the TeamSet repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

        $this->logAll('Begin TeamSet repairs');

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if (
            $this->backupTable('team_sets', $stamp)
            && $this->backupTable('team_sets_teams', $stamp)
        ) {
            $this->log('Begin team set repairs');
            $this->removeDuplicateTeams();
            $this->repairTeamCounts();
            $this->log('End team set repairs');
        } else {
            $this->log('Could not backup table');
        }
    }

}
