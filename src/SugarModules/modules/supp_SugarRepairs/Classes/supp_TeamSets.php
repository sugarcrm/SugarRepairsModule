<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

require_once('modules/Teams/TeamSet.php');

class supp_TeamSets Extends TeamSet
{
    /**
     * Updates the team count
     */
    public function fixTeamCount()
    {
        $teams = $this->getTeamIds($this->id);
        $stats = $this->_getStatistics($teams);
        $this->team_md5 = $stats['team_md5'];
        $this->team_count = $stats['team_count'];
        $this->save();
    }

    /**
     * Adds a team to a team set
     * @param $team_id
     */
    public function addTeamToSet($team_id)
    {
        if ($this->load_relationship('teams')) {
            $this->teams->add($team_id);
        } else {
            $guid = create_guid();
            $insertQuery = "INSERT INTO team_sets_teams (id, team_set_id, team_id) VALUES ('{$guid}', '{$this->id}', '{$team_id}')";
            $GLOBALS['db']->query($insertQuery);
        }
    }

    /**
     * removes a team from a team set
     * @param string $team_id
     */
    public function removeTeamFromSet($team_id)
    {
        $sqlDelete = "DELETE FROM team_sets_teams WHERE team_id = '$team_id' AND team_set_id = '$this->id'";
        $GLOBALS['db']->query($sqlDelete, true, "Error deleting team id from team_sets_teams: ");
    }

    /**
     * Updates the stats of a team set
     */
    public function updateStats()
    {
        //have to recalc the md5 hash and the count
        $stats = $this->_getStatistics($this->getTeamIds($this->id));
        $this->team_md5 = $stats['team_md5'];
        $this->team_count = $stats['team_count'];
        $this->save();
    }
}
