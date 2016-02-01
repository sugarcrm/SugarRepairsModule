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
        $this->log("Checking for duplicates in team sets...");

        //Fix team set duplicate teams --------------------------------------
        $sql = "SELECT team_set_id, team_id, count(*) FROM team_sets_teams GROUP BY team_set_id, team_id having count(*) > 1";

        $result = $GLOBALS['db']->query($sql);
        $teamSetBean = new supp_TeamSets();

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Updating team set teams for {$row['team_set_id']}");
            $teamSetBean->id = $row['team_set_id'];
            $teamSetBean->removeTeamFromSet($row['team_id']);
            $teamSetBean->addTeamToSet($row['team_id']);
        }
    }

    /**
     * Fixes team sets with bad team counts
     */
    public function repairTeamCounts()
    {
        $this->log("Checking for bad team counts in team sets...");

        //Fix teams with bad team counts --------------------------------------
        $sql = "SELECT team_sets.id, team_sets.team_count, count(*) as actual_count FROM team_sets INNER JOIN team_sets_teams on team_sets.id = team_sets_teams.team_set_id and team_sets_teams.deleted = 0 INNER JOIN teams on teams.id = team_sets_teams.team_id and teams.deleted = 0 WHERE team_sets.deleted = 0 GROUP BY team_sets.id HAVING count(*) <> team_sets.team_count";

        $result = $GLOBALS['db']->query($sql);
        $teamSetBean = new supp_TeamSets();

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Updating team set " . $row['id']);
            $teamSetBean->id = $row['id'];
            $teams = $teamSetBean->fixTeamCount($row['id']);
        }
    }

    /**
     * Executes the TeamSet repairs
     * @param bool $isTesting
     */
    public function execute($isTesting = false)
    {
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
