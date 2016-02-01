<?php

require_once ('modules/supp_SugarRepairs/Classes/Repairs/supp_TeamSetRepairs.php');

/**
 * @group support
 */

class suppSugarRepairsTeamSetsBeanTest extends Sugar_PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
    }

    public function tearDown()
    {
        SugarTestTeamUtilities::removeAllCreatedAnonymousTeams();
        parent::tearDown();
    }

    /**
     * Test for removing duplicates from team sets
     */
    public function testRepairDuplicateTeamsInTeamSets()
    {
        $team = SugarTestTeamUtilities::createAnonymousTeam();
        $teamSet = new TeamSet();
        $teamSetId = $teamSet->addTeams(array($team->id));

        $guid = create_guid();
        $insertQuery = "INSERT INTO team_sets_teams (id, team_set_id, team_id) VALUES ('{$guid}', '{$teamSetId}', '{$team->id}')";
        $GLOBALS['db']->query($insertQuery);

        $countBefore = $GLOBALS['db']->getOne("SELECT count(*) FROM team_sets_teams  WHERE team_set_id = '{$teamSetId}' AND team_id = '{$team->id}'");

        $teamSetRepairs = new supp_TeamSetRepairs();
        $teamSetRepairs->removeDuplicateTeams();

        $countAfter = $GLOBALS['db']->getOne("SELECT count(*) FROM team_sets_teams WHERE team_set_id = '{$teamSetId}' AND team_id = '{$team->id}'");

        $this->assertTrue($countBefore == 2);
        $this->assertTrue($countAfter == 1);
    }

    /**
     * Test for team sets with bad team counts
     */
    public function testRepairTeamsCounts()
    {
        $team1 = SugarTestTeamUtilities::createAnonymousTeam();
        $team2 = SugarTestTeamUtilities::createAnonymousTeam();

        $teamSet = new TeamSet();
        $teamSetId = $teamSet->addTeams(array($team1->id));

        $guid = create_guid();
        $insertQuery = "INSERT INTO team_sets_teams (id, team_set_id, team_id) VALUES ('{$guid}', '{$teamSetId}', '{$team2->id}')";
        $GLOBALS['db']->query($insertQuery);

        $countBefore = $GLOBALS['db']->getOne("SELECT team_count FROM team_sets  WHERE id = '{$teamSetId}'");
        $teamSetRepairs = new supp_TeamSetRepairs();
        $teamSetRepairs->repairTeamCounts();
        $countAfter = $GLOBALS['db']->getOne("SELECT team_count FROM team_sets  WHERE id = '{$teamSetId}'");

        $this->assertTrue($countBefore == 1);
        $this->assertTrue($countAfter == 2);
    }
}