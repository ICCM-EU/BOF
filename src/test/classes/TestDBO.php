<?php

require_once __DIR__ . '/../../classes/DBO.php';
require_once __DIR__ . '/../../classes/Logger.php';

use PHPUnit\Framework\TestCase;
use ICCM\BOF\DBO;

/**
 * @covers ICCM\BOF\DBO
 */
class TestDBO extends TestCase
{
    static private $pdo = null;

    # Helpers for setup
    private function _resetWorkshops() {
        // Note that calling _setupRounds and _setupLocations will clear out
        // their tables because they may be called multiple times during the
        // same test.
        self::$pdo->query("DELETE FROM participant");
        self::$pdo->query("DELETE FROM workshop");
        self::$pdo->query("DELETE FROM workshop_participant");
    }

    private function _setAvailable($available) {
        $id = 0;
        $query = self::$pdo->prepare("UPDATE workshop SET available=:available WHERE id=:id");
        foreach ($available as $avail) {
            if ($id == 0) {
                $query->bindValue(':id', (int) 1, PDO::PARAM_INT);
                $id = 101;
            }
            else {
                $query->bindValue(':id', (int) $id, PDO::PARAM_INT);
                $id++;
            }
            $query->bindValue(':available', $avail, PDO::PARAM_STR);
            $query->execute();
        }
    }

    private function _setBooking($bookings) {
        $query = self::$pdo->prepare("UPDATE workshop SET round_id=:round, location_id=:location WHERE id=:id");
        foreach ($bookings as $booking) {
            $query->bindValue(':id', (int) $booking[0], PDO::PARAM_INT);
            $query->bindValue(':round', (int) $booking[1], PDO::PARAM_INT);
            $query->bindValue(':location', (int) $booking[2], PDO::PARAM_INT);
            $query->execute();
        }
    }

    private function _setupRounds($rounds, $valid) {
        self::$pdo->query("DELETE FROM round");
        $sql = "INSERT INTO round (id, time_period) VALUES";
        for ($count = 0, $id = 0; $count < $rounds; $count++, $id++) {
            if ($count != 0) {
                $sql .= ",({$id}, 'Round {$count}')";
            }
            else {
                $sql .= "({$id}, 'Round {$count}')";
            }
            // Put some holes in the IDs if $valid is false
            if (! $valid) {
                $id++;
            }
        }
        self::$pdo->query($sql);
    }

    private function _setupLocations($locations, $valid) {
        self::$pdo->query("DELETE FROM location");
        $sql = "INSERT INTO location (id, name) VALUES";
        for ($count = 0, $id = 0, $letter = 'A'; $count < $locations; $count++, $id++, $letter++) {
            if ($count != 0) {
                $sql .= ",({$id}, 'Room {$letter}')";
            }
            else {
                $sql .= "({$id}, 'Room {$letter}')";
            }
            // Put some holes in the IDs if $valid is false
            if (! $valid) {
                $id++;
            }
        }
        self::$pdo->query($sql);
    }

    /**
     * Sets up the workshop, participant, and workshop_participant tables according to the given parameters.
     * 
     * @param int $rounds The number of rounds in the database
     * @param int $locations The number of locations in the database     
     * @param bool $enough True if there should be enough workshops to fill up
     * all the rounds and locations; if false there will be fewer.
     * @param int $conflict Should/how conflicts with facilitators be
     * generated. 0 indicates that all workshops will have one unique
     * facilitator. 1 indicates that workshops will have a limited number of
     * facilitators, guaranteeing there will be some conflicts. 2 indicates
     * that all workshops should have the same facilitator, guaranteeing the
     * maximum number of conflicts. 3 indicates that all workshops will have a
     * unique facilitator, and one facilitator that is common to all. Please
     * note that "all workshops" in the above statements exclude the prep BoF
     * which always has at least two facilitators, user ids 101 and 102. For a
     * value of 3, the Prep BoF additionally gets the one facilitator that all
     * other workshops also have.
     */
    private function _setupWorkshops($rounds, $locations, $enough, $conflict) {
        if (($rounds == 0) || ($locations == 0)) {
            print("Error using setupWorkshops: {$rounds} {$locations}!\n");
            return;
        }

        // Set the number of workshops to create
        $workshops = ($rounds * $locations) + 5;

        // Set the number of users to create
        $users = $workshops * 5;
        // The first 1/8 of users are in the Prep BoF
        $max_prep_user_id = (int) ($users / 8) + 101;

        // If $enough is false, reduce the number of workshops by 6.
        // We do this AFTER setting the number of users to keep votes and
        // availability closer to identical.
        if (! $enough) {
            $workshops -= 6;
        }

        if ($workshops < 4) {
            print("Error using setupWorkshops!\n");
            return;
        }

        // Insert rounds
        $this->_setupRounds($rounds, true);

        // Insert locations
        $this->_setupLocations($locations, true);

        // Create the users, but don't forget the special admin user
        $sql = "INSERT INTO participant (id, name, password) VALUES";
        for ($count = 0; $count <= $users; $count++) {
            if ($count != 0) {
                $id= $count + 100;
                $sql .= ",({$id}, 'user{$count}', '*14E65567ABDB5135D0CFD9A70B3032C179A49EE7')";
            }
            else {
                $sql .= "(1, 'admin', '*14E65567ABDB5135D0CFD9A70B3032C179A49EE7')";
            }
        }
        self::$pdo->query($sql);

        // Create workshops, and don't for the prep team BoF!
        $sql = "INSERT INTO workshop (id, creator_id, name, description, published) VALUES";
        for ($count = 0; $count < $workshops; $count++) {
            if ($count != 0) {
                $id= $count + 100;
                $sql .= ",({$id}, {$id}, 'topic{$count}', 'Description for topic{$count}', 0)";
            }
            else {
                $sql .= "(1, 1, 'Prep Team', 'Prep Team BoF', 0)";
            }
        }
        self::$pdo->query($sql);

        // Set up votes -- For now, we'll set the user as the leader if the
        // user_id matches the workshop_id

        // Each user will vote 1 for 1-2 workshops, and .25 for 1-4 workshops
        // Seed the RNG; note that we use a predictable RNG so the results
        // will be consistent for each run!
        mt_srand(($rounds << 8) + $locations);
        $sql = "INSERT INTO workshop_participant(id, workshop_id, participant_id, leader, participant) VALUES (:id, :workshop_id, :participant_id, :leader, :participant)";
        $query = self::$pdo->prepare($sql);
        $workshop_id = (int) 1;
        $wp_id = (int) 0;
        $user_id = (int) 101;
        $query->bindParam(':id', $wp_id, PDO::PARAM_INT);
        $query->bindParam(':workshop_id', $workshop_id, PDO::PARAM_INT);
        $query->bindParam(':participant_id', $user_id, PDO::PARAM_INT);
        for (; $user_id <= $users + 100; $user_id++) {
            // vote of 1 for own workshop (or % $workshops)
            $workshop_id = (($user_id - 100) % $workshops) + 100;
            $leader = 0;
            if ($workshop_id == $user_id) {
                $leader = 1;
            }

            //print("1. id: {$wp_id}, workshop_id: {$workshop_id}, participant_id: {$user_id}, leader: {$leader}, participant: 1\n");
            $query->bindValue(':leader', (int) $leader, PDO::PARAM_INT);
            $query->bindValue(':participant', (int) 1, PDO::PARAM_INT);
            $query->execute();
            $wp_id++;

            // vote of 1 for random workshop
            for ($count = 1; $count < 2; $count++) {
                $workshop_id = mt_rand(101, $workshops + 99);
                if ($workshop_id != $user_id) {
                    //print("{$count}. id: {$wp_id}, workshop_id: {$workshop_id}, participant_id: {$user_id}, leader: 1, participant: 1\n");
                    $query->bindValue(':leader', (int) 0, PDO::PARAM_INT);
                    $query->bindValue(':participant', (int) 1, PDO::PARAM_INT);
                    try {
                        $query->execute();
                    }
                    catch (Exception $e) {
                        //print("(1.0) Ignoring duplicate user_id/workshop_id: {$user_id}/{$workshop_id}\n");
                    }

                    $wp_id++;
                }
            }

            // vote of 0.25
            $votes_left = (3 - $count) * 4;
            $max = mt_rand($count, $votes_left);
            for (; $count < $max; $count++) {
                $workshop_id = mt_rand(101, $workshops + 99);
                //print("id: {$wp_id}, workshop_id: {$workshop_id}, participant_id: {$user_id}, leader: 0, participant: 0.25\n");
                $query->bindValue(':leader', 0, PDO::PARAM_STR);
                $query->bindValue(':participant', '0.25', PDO::PARAM_STR);
                try {
                    $query->execute();
                }
                catch (Exception $e) {
                    //print("(0.25) Ignoring duplicate user_id/workshop_id: {$user_id}/{$workshop_id}\n");
                }
                $wp_id++;
            }

            // Now the prep bof users vote for it
            if ($user_id < $max_prep_user_id) {
                $workshop_id = 1;
                //print("id: {$wp_id}, workshop_id: {$workshop_id}, participant_id: {$user_id}, participant: 0.25\n");
                if ($user_id < 103) {
                    $query->bindValue(':leader', 1, PDO::PARAM_STR);
                }
                else {
                    $query->bindValue(':leader', 0, PDO::PARAM_STR);
                }
                $query->bindValue(':participant', '0.25', PDO::PARAM_STR);
                $query->execute();
                $wp_id++;
            }
        }

        // Now reset the leaders if $conflict is true
        if (($conflict != 0) && ($conflict != 3)) {
            if ($conflict == 1) {
                //$max_leader_id = ($rounds * ($locations - 1)) + 100;
                //$max_leader_id = $rounds + 100;
                $max_leader_id = $rounds + 100 + $locations;
            }
            else {
                $max_leader_id = 101;
            }
            $leader_id = 101;
            $sql = "UPDATE workshop_participant SET participant_id=:user_id WHERE participant_id=:workshop_id AND workshop_id=:workshop_id AND leader=1";
            $query = self::$pdo->prepare($sql);
            //print ($sql."\n");
            for ($workshop_id = 101; $workshop_id < $workshops + 100; $workshop_id++) {

                //print("Changing user_id {$workshop_id} to {$leader_id} for topic: {$workshop_id}\n");
                $query->bindValue(':workshop_id', (int) $workshop_id, PDO::PARAM_INT);
                $query->bindValue(':user_id', (int) $leader_id, PDO::PARAM_INT);
                try {
                    $query->execute();
                }
                catch (Exception $e) {
                    //print("user_id {$leader_id} already voted for topic: {$workshop_id}\n");
                    //print("\tChanging leader to 0 for user_id: {$workshop_id}:{$workshop_id}\n");
                    //print("\tChanging leader to 1 for user_id: {$leader_id}:{$workshop_id}\n");
                    $query2 = self::$pdo->prepare("UPDATE workshop_participant SET leader=0 WHERE participant_id=:workshop_id AND workshop_id=:workshop_id");
                    $query2->bindValue(':workshop_id', (int) $workshop_id, PDO::PARAM_INT);
                    $query2->execute();
                    $query3 = self::$pdo->prepare("UPDATE workshop_participant SET leader=1 WHERE participant_id=:user_id AND workshop_id=:workshop_id");
                    $query3->bindValue(':workshop_id', (int) $workshop_id, PDO::PARAM_INT);
                    $query3->bindValue(':user_id', (int) $leader_id, PDO::PARAM_INT);
                    $query3->execute();
                }
                //print("workshop_id: {$workshop_id}, participant_id: {$leader_id}\n");
                $leader_id++;
                if ($leader_id > $max_leader_id) {
                    $leader_id = 101;
                }
            }
        }

        // Add an additional leader, of the last user id for every workshop
        if ($conflict == 3) {
            $user_id = $users + 100;
            $sql1 = "UPDATE workshop_participant SET leader=1 WHERE participant_id=".$user_id." AND workshop_id=:workshop_id";
            $sql2 = "INSERT INTO workshop_participant(id, workshop_id, participant_id, leader, participant) VALUES (:id, :workshop_id, ".$user_id.", 1, 1)";
            $queryUpdate = self::$pdo->prepare($sql1);
            $queryInsert = self::$pdo->prepare($sql2);
            for ($workshop_id = 101; $workshop_id < $workshops + 100; $workshop_id++) {
                $queryUpdate->bindValue(':workshop_id', (int) $workshop_id, PDO::PARAM_INT);
                try{
                    $queryUpdate->execute();
                }
                catch (Exception $e) {
                    $queryInsert->bindValue(':workshop_id', (int) $workshop_id, PDO::PARAM_INT);
                    $queryUpdate->execute();
                }
            }
            // Make sure to get the prep bof!
            try{
                $queryUpdate->bindValue(':workshop_id', 1, PDO::PARAM_INT);
                $queryUpdate->execute();
            }
            catch (Exception $e) {
                $queryInsert->bindValue(':workshop_id', (int) 1, PDO::PARAM_INT);
                $queryUpdate->execute();
            }
        }
    }

    private function _setVotes($votes) {
        $id = 0;
        $query = self::$pdo->prepare("UPDATE workshop SET votes=:votes WHERE id=:id");
        foreach ($votes as $vote) {
            if ($id == 0) {
                $query->bindValue(':id', (int) 1, PDO::PARAM_INT);
                $id = 101;
            }
            else {
                $query->bindValue(':id', (int) $id, PDO::PARAM_INT);
                $id++;
            }
            $query->bindValue(':votes', $vote, PDO::PARAM_STR);
            $query->execute();
        }
    }

    /**
     * Helper function to verify if booked workshops are where we expect them
     * to be, and that nothing else has been booked.
     */
    private function _verifyBooking($expected) {
        $query = self::$pdo->prepare("SELECT id, round_id, location_id FROM workshop WHERE round_id IS NOT NULL ORDER BY round_id, location_id");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_OBJ);
        $this->assertNotFalse($rows);
        $this->assertEquals(count($expected), count($rows));
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i][0], $rows[$i]->id);
            $this->assertEquals($expected[$i][1], $rows[$i]->round_id);
            $this->assertEquals($expected[$i][2], $rows[$i]->location_id);
        }
    }


    /*
    # Helper for making sure we're testing what we expect
    private function _printWorkshopData() {
        print("\nid\tround_id\tlocation_id\tavailable\tvotes\tname\tdescription\n");
        //$query = self::$pdo->prepare("SELECT id,round_id,location_id,available,votes,name,description FROM workshop WHERE round_id IS NOT NULL ORDER BY round_id, location_id");
        $query = self::$pdo->prepare("SELECT id,round_id,location_id,available,votes,name,description FROM workshop ORDER BY id");
        $query->execute();
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            print("{$row->id}\t{$row->round_id}\t\t{$row->location_id}\t\t{$row->available}\t\t{$row->votes}\t{$row->name}\t{$row->description}\n");
        }
    }

    private function _printFacilitators() {
        print("\nid\tround_id\tfacilitator\n");
        $query = self::$pdo->prepare("SELECT workshop.id id, workshop.round_id round_id, p.participant_id facilitator FROM workshop JOIN workshop_participant AS p ON workshop.id=p.workshop_id WHERE workshop.round_id IS NOT NULL AND p.leader=1 ORDER BY workshop.round_id, workshop.id, p.participant_id");
        $query->execute();
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            print("{$row->id}\t{$row->round_id}\t\t{$row->facilitator}\n");
        }
    }
    */

    /**
     * @beforeClass
     */
    public static function setUpBeforeClass() : void {
        self::$pdo = new PDO('sqlite::memory:');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = file_get_contents(dirname(__FILE__).'/../../../sql/createtables.sql');
        $sql = preg_replace(array("/AUTO_INCREMENT=\d+/",
            "/CHARSET=\w+/",
            "/COLLATE.*_ci/",
            "/DEFAULT\s+CURRENT_TIMESTAMP\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/",
            "/DEFAULT NULL/",
            "/DEFAULT '.*'/",
            "/DEFAULT /",
            "/\s\sKEY.*,/"), "", $sql);
        $sql = preg_replace("/UNIQUE KEY `.*?`/", "UNIQUE", $sql);
        $sql = str_replace("unsigned", "", $sql);
        $sql = str_replace("AUTO_INCREMENT", "", $sql);
        $sql = str_replace("DEFAULT", "", $sql);
        $sql = str_replace("CHARACTER SET latin1 ", "", $sql);
        $sql = str_replace("ENGINE=InnoDB", "", $sql);
        $sql = str_replace("ENGINE=MyISAM", "", $sql);
        self::$pdo->exec($sql);
    }

    /**
     * @afterClass
     */
    public static function tearDownAfterClass(): void {
        self::$pdo = null;
    }

    /**
     * @before
     */
    protected function setUp(): void {
        // Clear out everything
        $this->_resetWorkshops();
    }

    /**
     * @covers ICCM\BOF\DBO::beginTransaction
     * @test
     */
    public function beginTransactionOnlyInvokesPDOBeginTransaction() {
        $pdoMock = $this->getMockBuilder(PDO::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['beginTransaction', 'commit', 'prepare', 'query', 'rollBack'])
                 ->getMock();
        $pdoMock->expects($this->once())
            ->method('beginTransaction');
        $pdoMock->expects($this->never())
            ->method('commit');
        $pdoMock->expects($this->never())
            ->method('prepare');
        $pdoMock->expects($this->never())
            ->method('query');
        $pdoMock->expects($this->never())
            ->method('rollBack');
        $dbo = new DBO($pdoMock);
        $dbo->beginTransaction();
    }

    /**
     * @covers ICCM\BOF\DBO::bookWorkshop
     * @test
     */
    public function bookWorkshopSetsRoundLocationAndAvailable() {
        $this->_setupWorkshops(12, 15, true, 0);
        $dbo = new DBO(self::$pdo);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['logBookWorkshop'])
            ->getMock();
        $logger->expects($this->once())
            ->method('logBookWorkshop');

        $dbo->bookWorkshop(105, "topic5", 10, 12, 15, "blue", $logger);
        $expected = [[105, 10, 12]];
        $this->_verifyBooking($expected);

        $query = self::$pdo->prepare("SELECT available FROM workshop WHERE id = 105");
        $query->execute();
        $row = $query->fetch(PDO::FETCH_OBJ);
        $this->assertNotFalse($row);
        $this->assertEquals(15, $row->available);
    }
 
    /**
     * @covers ICCM\BOF\DBO::calculateVotes
     * @test
     */
    public function calculateVotesSetsVotesFromWorkshopParticipant() {
        $this->_setupWorkshops(3, 4, true, 0);
        $dbo = new DBO(self::$pdo);
        $dbo->calculateVotes();
        // Get all workshops into rows
        $query = self::$pdo->prepare("SELECT votes FROM workshop ORDER BY id");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_OBJ);
        $this->assertEquals(17, count($rows));
        $this->assertEquals(2.5, $rows[0]->votes);
        $this->assertEquals(11.25, $rows[1]->votes);
        $this->assertEquals(11.5, $rows[2]->votes);
        $this->assertEquals(10.75, $rows[3]->votes);
        $this->assertEquals(12.5, $rows[4]->votes);
        $this->assertEquals(12.0, $rows[5]->votes);
        $this->assertEquals(15.25, $rows[6]->votes);
        $this->assertEquals(11.75, $rows[7]->votes);
        $this->assertEquals(11.25, $rows[8]->votes);
        $this->assertEquals(9.75, $rows[9]->votes);
        $this->assertEquals(9.0, $rows[10]->votes);
        $this->assertEquals(8.5, $rows[11]->votes);
        $this->assertEquals(12.0, $rows[12]->votes);
        $this->assertEquals(12.75, $rows[13]->votes);
        $this->assertEquals(9.5, $rows[14]->votes);
        $this->assertEquals(7.25, $rows[15]->votes);
        $this->assertEquals(10.75, $rows[16]->votes);
    }

    /**
     * @covers ICCM\BOF\DBO::commit
     * @test
     */
    public function commitOnlyInvokesPDOCommit() {
        $pdoMock = $this->getMockBuilder(PDO::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['beginTransaction', 'commit', 'prepare', 'query', 'rollBack'])
                 ->getMock();
        $pdoMock->expects($this->never())
            ->method('beginTransaction');
        $pdoMock->expects($this->once())
            ->method('commit');
        $pdoMock->expects($this->never())
            ->method('prepare');
        $pdoMock->expects($this->never())
            ->method('query');
        $pdoMock->expects($this->never())
            ->method('rollBack');
        $dbo = new DBO($pdoMock);
        $dbo->commit();
    }

    /**
     * @covers ICCM\BOF\DBO::exportWorkshops
     * @test
     */
    public function exportWorkshops() {
        $this->_setupWorkshops(3, 4, true, 0);
        $this->_setVotes([4, 17, 5, 16.25, 6, 15.75, 7, 18.25, 19.75, 8, 21.75, 9]);
        $this->_setAvailable([4, 17, 5, 16, 6, 15, 7, 18, 19, 8, 21, 9]);
        $this->_setBooking([[101, 0, 0],
            [102, 0, 1],
            [103, 0, 2],
            [104, 0, 3],
            [105, 1, 0],
            [106, 1, 1],
            [107, 1, 2],
            [108, 1, 3],
            [109, 2, 0],
            [1, 2, 1],
            [110, 2, 2],
            [111, 2, 3]
        ]);
        $dbo = new DBO(self::$pdo);
        $csvout = $dbo->exportWorkshops();
        $expected = <<<EOF
Room A,"topic1","user1","Description for topic1",17.0,17
Room B,"topic2","user2","Description for topic2",5.0,5
Room C,"topic3","user3","Description for topic3",16.25,16
Room D,"topic4","user4","Description for topic4",6.0,6
Room A,"topic5","user5","Description for topic5",15.75,15
Room B,"topic6","user6","Description for topic6",7.0,7
Room C,"topic7","user7","Description for topic7",18.25,18
Room D,"topic8","user8","Description for topic8",19.75,19
Room A,"topic9","user9","Description for topic9",8.0,8
Room B,"Prep Team","user1, user2","Prep Team BoF",4.0,4
Room C,"topic10","user10","Description for topic10",21.75,21
Room D,"topic11","user11","Description for topic11",9.0,9

EOF;
        $this->assertEquals($expected, $csvout);
    }

    /**
     * Helper for various findConflictsMax test
     */
    private function _findConflictsMax($rounds, $locations) {
        $this->_resetWorkshops();
        $this->_setupWorkshops($rounds, $locations, true, 2);

        // Book a consecutive set of workshops, so it's easier to verify what
        // findConflicts returns.
        $bookedWorkshops = [];
        $workshop_id = 101;
        $i = 0;
        for ($round = 0; $round < $rounds; $round++) {
            for ($location = 0; $location < $locations; $location++) {
                if (($round == $rounds - 1) && ($location == 1)) {
                    $bookedWorkshops[$i] = [1, $round, $location];
                }
                else {
                    $bookedWorkshops[$i] = [$workshop_id, $round, $location];
                    $workshop_id++;
                }
                $i++;
            }
        }
        $this->_setBooking($bookedWorkshops);

        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();

        $expectedConflicts = $rounds * ($locations - 1);
        $expectedInArray = $rounds;

        if ($locations == 2) {
            $expectedConflicts--;
            $expectedInArray--;
        }

        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals($expectedConflicts, $conflictsArr['count']);

        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals($expectedInArray, count($conflicts));
        for ($i = 0; $i < $expectedInArray; $i++) {
            $this->assertEquals(101, $conflicts[$i]->participant_id);
            $this->assertEquals($i, $conflicts[$i]->round_id);
        }
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWhenEverythingConflicts() {
        $this->_findConflictsMax(2, 5);
        $this->_findConflictsMax(3, 4);
        $this->_findConflictsMax(4, 2);
        $this->_findConflictsMax(5, 2);
        $this->_findConflictsMax(8, 2);
        $this->_findConflictsMax(8, 7);
        $this->_findConflictsMax(3, 9);
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWhenNoConflictsExist() {
        $this->_setupWorkshops(3, 4, true, 0);
        $this->_setBooking([[101, 0, 0],
            [102, 0, 1],
            [103, 0, 2],
            [104, 0, 3],
            [105, 1, 0],
            [106, 1, 1],
            [107, 1, 2],
            [108, 1, 3],
            [109, 2, 0],
            [1, 2, 1],
            [110, 2, 2],
            [111, 2, 3]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(0, $conflictsArr['count']);
        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(0, count($conflicts));
    }

    /**
     * @testdox find conflicts when everything has two faciliators, one in
     * common and one unique.
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWhenNoConflictsExistMax() {
        $this->_setupWorkshops(3, 4, true, 3);
        $this->_setBooking([[101, 0, 0],
            [102, 0, 1],
            [103, 0, 2],
            [104, 0, 3],
            [105, 1, 0],
            [106, 1, 1],
            [107, 1, 2],
            [108, 1, 3],
            [109, 2, 0],
            [1, 2, 1],
            [110, 2, 2],
            [111, 2, 3]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(0, $conflictsArr['count']);
        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(0, count($conflicts));
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWithMultiple34() {
        $this->_setupWorkshops(3, 4, true, 1);
        // This ensure that 102, 107, and 109 all have facilitator 102
        self::$pdo->query("UPDATE workshop_participant SET leader=1 WHERE participant_id=102 and workshop_id=107");
        self::$pdo->query("UPDATE workshop_participant SET leader=0 WHERE participant_id=107 and workshop_id=107");
        // Book 101 & 108 for round 0 (conflict!) and 1012, 107, and 109 for
        // round 1 (conflict!)
        $this->_setBooking([[101, 0, 0],
            [108, 0, 1],
            [104, 0, 2],
            [105, 0, 3],
            [106, 1, 0],
            [102, 1, 1],
            [107, 1, 2],
            [109, 1, 3],
            [103, 2, 0],
            [1, 2, 1],
            [111, 2, 2],
            [112, 2, 3]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(3, $conflictsArr['count']);
        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(2, count($conflicts));
        $this->assertEquals(101, $conflicts[0]->participant_id);
        $this->assertEquals(0, $conflicts[0]->round_id);
        $this->assertEquals(102, $conflicts[1]->participant_id);
        $this->assertEquals(1, $conflicts[1]->round_id);
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWhenNoConflictsExist52() {
        $this->_setupWorkshops(5, 2, true, 1);
        $this->_setBooking([[101, 0, 0],
            [102, 0, 1],
            [103, 1, 0],
            [104, 1, 1],
            [105, 2, 0],
            [106, 2, 1],
            [107, 3, 0],
            [108, 3, 1],
            [109, 4, 0],
            [1, 4, 1]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(0, $conflictsArr['count']);
        //$conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(0, count($conflictsArr['conflicts']));
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWithOnlyOneConflict52() {
        $this->_setupWorkshops(5, 2, true, 1);
        $this->_setBooking([[109, 0, 0],
            [102, 0, 1],
            [103, 1, 0],
            [104, 1, 1],
            [105, 2, 0],
            [106, 2, 1],
            [107, 3, 0],
            [108, 3, 1],
            [101, 4, 0],
            [1, 4, 1]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(1, $conflictsArr['count']);
        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(1, count($conflicts));
        $this->assertEquals(102, $conflicts[0]->participant_id);
        $this->assertEquals(0, $conflicts[0]->round_id);
    }

    /**
     * @covers ICCM\BOF\DBO::findConflicts
     * @test
     */
    public function findConflictsWithOnlyOneConflictMultipleFacilitators() {
        $this->_setupWorkshops(3, 4, true, 1);
        $this->_setBooking([[101, 0, 0],
            [103, 0, 1],
            [104, 0, 2],
            [105, 0, 3],
            [106, 1, 0],
            [102, 1, 1],
            [108, 1, 2],
            [109, 1, 3],
            [107, 2, 0],
            [1, 2, 1],
            [111, 2, 2],
            [112, 2, 3]
        ]);
        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $dbo = new DBO(self::$pdo);
        $conflictsArr = $dbo->findConflicts($logger);
        $this->assertEquals(1, $conflictsArr['count']);
        $conflicts = $conflictsArr['conflicts'];
        $this->assertEquals(1, count($conflicts));
        $this->assertEquals(102, $conflicts[0]->participant_id);
        $this->assertEquals(1, $conflicts[0]->round_id);
    }
 
    /**
     * @covers ICCM\BOF\DBO::getConflict
     * @test
     */
    public function getConflictReturnsExpectedData() {
        $this->_setupWorkshops(3, 4, true, 1);
        $this->_setBooking([
            [102, 1, 1]
        ]);
        $dbo = new DBO(self::$pdo);
        $conflict = (object) [
            'participant_id' => 102,
            'round_id' => 1
        ];
        $row = $dbo->getConflict($conflict);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(102, $row->id);
        $this->assertEquals(1, $row->location_id);
    }

    /**
     * @covers ICCM\BOF\DBO::getConflict
     * @test
     */
    public function getConflictReturnsFalseWhenConflictInLocationZero() {
        $this->_setupWorkshops(3, 4, true, 1);
        $this->_setBooking([
            [102, 1, 0]
        ]);
        $dbo = new DBO(self::$pdo);
        $conflict = (object) [
            'participant_id' => 102,
            'round_id' => 1
        ];
        $row = $dbo->getConflict($conflict);
        $this->assertFalse($row);
    }

    /**
     * @covers ICCM\BOF\DBO::getConflict
     * @test
     */
    public function getConflictForWrongData() {
        $this->_setupWorkshops(3, 4, true, 1);
        $this->_setBooking([[101, 0, 0],
            [102, 0, 1],
            [103, 0, 2],
            [104, 0, 3],
            [105, 1, 0],
            [106, 1, 1],
            [107, 1, 2],
            [108, 1, 3],
            [109, 2, 0],
            [110, 2, 1],
            [111, 2, 2],
            [112, 2, 3]
        ]);
        $dbo = new DBO(self::$pdo);
        $conflict = (object) [
            'participant_id' => 102,
            'round_id' => 2
        ];
        $this->assertFalse($dbo->getConflict($conflict));
    }

    /**
     * @covers ICCM\BOF\DBO::getConflictSwitchTargets
     * @test
     */
    public function getConflictSwitchTargetsForSimpleConflict() {
        $this->_setupWorkshops(3, 4, true, 1);
        $this->_setBooking([[101, 0, 0],
            [103, 0, 1],
            [104, 0, 2],
            [105, 0, 3],
            [106, 1, 0],
            [102, 1, 1],
            [108, 1, 2],
            [109, 1, 3],
            [107, 2, 0],
            [1, 2, 1],
            [111, 2, 2],
            [112, 2, 3]
        ]);
        $dbo = new DBO(self::$pdo);
        $conflict = (object) [
            'participant_id' => 102,
            'round_id' => 1
        ];
        $targetRows = $dbo->getConflictSwitchTargets($conflict);
        $this->assertEquals(5, count($targetRows));
        $this->assertEquals(103, $targetRows[0]->id);
        $this->assertEquals(0, $targetRows[0]->round_id);
        $this->assertEquals(1, $targetRows[0]->location_id);
        $this->assertEquals(9, $targetRows[0]->available);
        $this->assertEquals(104, $targetRows[1]->id);
        $this->assertEquals(0, $targetRows[1]->round_id);
        $this->assertEquals(2, $targetRows[1]->location_id);
        $this->assertEquals(8, $targetRows[1]->available);
        $this->assertEquals(111, $targetRows[2]->id);
        $this->assertEquals(2, $targetRows[2]->round_id);
        $this->assertEquals(2, $targetRows[2]->location_id);
        $this->assertEquals(8, $targetRows[2]->available);
        $this->assertEquals(112, $targetRows[3]->id);
        $this->assertEquals(2, $targetRows[3]->round_id);
        $this->assertEquals(3, $targetRows[3]->location_id);
        $this->assertEquals(8, $targetRows[3]->available);
        $this->assertEquals(105, $targetRows[4]->id);
        $this->assertEquals(0, $targetRows[4]->round_id);
        $this->assertEquals(3, $targetRows[4]->location_id);
        $this->assertEquals(7, $targetRows[4]->available);
    }

    /**
     * @covers ICCM\BOF\DBO::getConflictSwitchTargets
     * @test
     */
    public function getConflictSwitchTargetsWhenAllHaveSameFacilitator() {
        $this->_setupWorkshops(3, 4, true, 2);
        $this->_setBooking([[101, 0, 0],
            [103, 0, 1],
            [104, 0, 2],
            [105, 0, 3],
            [106, 1, 0],
            [102, 1, 1],
            [108, 1, 2],
            [109, 1, 3],
            [107, 2, 0],
            [1, 2, 1],
            [111, 2, 2],
            [112, 2, 3]
        ]);
        $dbo = new DBO(self::$pdo);
        $conflict = (object) [
            'participant_id' => 101,
            'round_id' => 0
        ];
        $targetRows = $dbo->getConflictSwitchTargets($conflict);
        $this->assertEquals(0, count($targetRows));

        $conflict->round_id = 1;
        $targetRows = $dbo->getConflictSwitchTargets($conflict);
        $this->assertEquals(0, count($targetRows));

        $conflict->round_id = 2;
        $targetRows = $dbo->getConflictSwitchTargets($conflict);
        $this->assertEquals(0, count($targetRows));
    }

    /**
     * @covers ICCM\BOF\DBO::getMaxVote
     * @test
     */
    public function getMaxVote() {
        $this->_setupWorkshops(1, 7, false, 0); // 6 workshops!
        $dbo = new DBO(self::$pdo);
        $this->_setVotes([9, 21.75, 15.75, 19.75, 18.75, 15.75]);
        $this->assertEquals(21.75, $dbo->getMaxVote());
    }

    /**
     * @covers ICCM\BOF\DBO::getMaxVote
     * @test
     */
    public function getMaxVoteASecondTime() {
        $this->_setupWorkshops(1, 7, false, 0); // 6 workshops!
        $dbo = new DBO(self::$pdo);
        $this->_setVotes([9, 21.75, 15.75, 19.75, 18.75, 15.75]);

        // Set everything with a vote total > 15.75 to have round and location,
        // which will leave 15.75 as the next highest vote total.
        self::$pdo->query("UPDATE workshop SET round_id=1, location_id=1, published=1 WHERE votes > 15.75");
        $this->assertEquals(15.75, $dbo->getMaxVote());
    }

    /**
     * @covers ICCM\BOF\DBO::getLocationNames
     * @test
     */
    public function getLocationNamesReturnsExpected() {
        $expected = [ 'Room A', 'Room B', 'Room C' ];
        $this->_setupLocations(3, true);

        $dbo = new DBO(self::$pdo);
        $this->assertEquals($expected, $dbo->getLocationNames());
    }

    /**
     * @covers ICCM\BOF\DBO::getMaxVote
     * @test
     */
    public function getMaxVoteForMultipleWorkshopsWithSameVotes() {
        $this->_setupWorkshops(1, 7, false, 0); // 6 workshops!
        $dbo = new DBO(self::$pdo);
        $this->_setVotes([9, 21.75, 15.75, 19.75, 18.75, 15.75]);

        // Set everything with a vote total > 15.75 to have round and location,
        // which will leave 15.75 as the next highest vote total.
        self::$pdo->query("UPDATE workshop SET round_id=1, location_id=1, published=1 WHERE votes > 15.75");
        // Since 102 has a vote total of 15.75, set it to have a round and
        // location. We should still get 15.75 as the max vote.
        self::$pdo->query("UPDATE workshop SET round_id=1, location_id=1,
        published=1 WHERE id=102");
        $this->assertEquals(15.75, $dbo->getMaxVote());
    }

    /**
     * @covers ICCM\BOF\DBO::getNumLocations
     * @test
     */
    public function getNumLocationsReturnsExpected() {
        $expected = 5;
        $this->_setupLocations($expected, true);
        $dbo = new DBO(self::$pdo);
        $this->assertEquals($expected, $dbo->getNumLocations());
    }

    /**
     * @covers ICCM\BOF\DBO::getNumRounds
     * @test
     */
    public function getNumRoundsReturnsExpected() {
        $expected = 7;
        $this->_setupRounds($expected, true);
        $dbo = new DBO(self::$pdo);
        $this->assertEquals($expected, $dbo->getNumRounds());
    }

    /**
     * @covers ICCM\BOF\DBO::getPrepBoF
     * @test
     */
    public function getPrepBoFReturnsExpectedData() {
        $this->_setupWorkshops(3, 3, true, 0);
        $dbo = new DBO(self::$pdo);
        $prepBoF = $dbo->getPrepBoF();
        $this->assertNotFalse($prepBoF);
        $this->assertEquals(1, $prepBoF->id);
        $this->assertEquals("Prep Team", $prepBoF->name);
        $this->assertEquals(8, $prepBoF->available);
    }

    /**
     * @covers ICCM\BOF\DBO::getPrepBoF
     * @test
     */
    public function getPrepBoFReturnsFalseIfNotFound() {
        // Remove all workshops, so getPrepBoF() won't return anything!
        $this->_resetWorkshops();
        $dbo = new DBO(self::$pdo);
        $this->assertFalse($dbo->getPrepBoF());
    }

    /**
     * @covers ICCM\BOF\DBO::getRoundNames
     * @test
     */
    public function getRoundNamesReturnsExpected() {
        $expected = [ 'Round 0', 'Round 1', 'Round 2' ];
        $this->_setupRounds(3, true);

        $dbo = new DBO(self::$pdo);
        $this->assertEquals($expected, $dbo->getRoundNames());
    }

    /**
     * @covers ICCM\BOF\DBO::getTopWorkshops
     * @test
     */
    public function getTopWorkshopsForThreeRounds() {
        $this->_setupWorkshops(3, 3, true, 0);
        $this->_setVotes([4, 17, 5, 16.25, 6, 15.75, 7, 18.25, 19.75, 8, 21.75, 9, 17.5]);
        $dbo = new DBO(self::$pdo);
        $workshops = $dbo->getTopWorkshops(3);
        $this->assertEquals(3, count($workshops));
        $this->assertEquals(110, $workshops[0]->id);
        $this->assertEquals('topic10', $workshops[0]->name);
        $this->assertEquals(21.75, $workshops[0]->votes);
        $this->assertEquals(11, $workshops[0]->available);
        $this->assertEquals(108, $workshops[1]->id);
        $this->assertEquals('topic8', $workshops[1]->name);
        $this->assertEquals(19.75, $workshops[1]->votes);
        $this->assertEquals(12, $workshops[1]->available);
        $this->assertEquals(107, $workshops[2]->id);
        $this->assertEquals('topic7', $workshops[2]->name);
        $this->assertEquals(18.25, $workshops[2]->votes);
        $this->assertEquals(9, $workshops[2]->available);
    }

    /**
     * @covers ICCM\BOF\DBO::getTopWorkshops
     * @test
     */
    public function getTopWorkshopsForEightRounds() {
        $this->_setupWorkshops(8, 3, true, 0);
        $this->_setVotes([4, 17, 5, 16.25, 6, 15.75, 7, 18.25, 19.75, 8, 21.75, 9, 17.5]);
        $dbo = new DBO(self::$pdo);
        $workshops = $dbo->getTopWorkshops(8);
        $this->assertEquals(8, count($workshops));
        $this->assertEquals(110, $workshops[0]->id);
        $this->assertEquals('topic10', $workshops[0]->name);
        $this->assertEquals(21.75, $workshops[0]->votes);
        $this->assertEquals(11, $workshops[0]->available);
        $this->assertEquals(108, $workshops[1]->id);
        $this->assertEquals('topic8', $workshops[1]->name);
        $this->assertEquals(19.75, $workshops[1]->votes);
        $this->assertEquals(11, $workshops[1]->available);
        $this->assertEquals(107, $workshops[2]->id);
        $this->assertEquals('topic7', $workshops[2]->name);
        $this->assertEquals(18.25, $workshops[2]->votes);
        $this->assertEquals(9, $workshops[2]->available);
        $this->assertEquals(112, $workshops[3]->id);
        $this->assertEquals('topic12', $workshops[3]->name);
        $this->assertEquals(17.5, $workshops[3]->votes);
        $this->assertEquals(13, $workshops[3]->available);
        $this->assertEquals(101, $workshops[4]->id);
        $this->assertEquals('topic1', $workshops[4]->name);
        $this->assertEquals(17.0, $workshops[4]->votes);
        $this->assertEquals(11, $workshops[4]->available);
        $this->assertEquals(103, $workshops[5]->id);
        $this->assertEquals('topic3', $workshops[5]->name);
        $this->assertEquals(16.25, $workshops[5]->votes);
        $this->assertEquals(11, $workshops[5]->available);
        $this->assertEquals(105, $workshops[6]->id);
        $this->assertEquals('topic5', $workshops[6]->name);
        $this->assertEquals(15.75, $workshops[6]->votes);
        $this->assertEquals(15, $workshops[6]->available);
        $this->assertEquals(111, $workshops[7]->id);
        $this->assertEquals('topic11', $workshops[7]->name);
        $this->assertEquals(9.0, $workshops[7]->votes);
        $this->assertEquals(12, $workshops[7]->available);
    }

    /**
     * @covers ICCM\BOF\DBO::getWorkshopToBook
     * @test
     */
    public function getWorkshopToBookReturnsFalseIfVoteTooBig() {
        $this->_setupWorkshops(3, 4, true, 0);
        $dbo = new DBO(self::$pdo);
        $this->_setVotes([2.5, 12.5, 9.5, 15.75, 5.75]);
        $row = $dbo->getWorkshopToBook(3, 21.75);
        $this->assertFalse($row);
    }

    /**
     * @covers ICCM\BOF\DBO::getWorkshopToBook
     * @test
     */
    public function getWorkshopToBookReturnsCorrectWorkshops() {
        $this->_setupWorkshops(3, 4, true, 0);
        $dbo = new DBO(self::$pdo);
        $this->_setVotes([2.5, 12.5, 9.5, 15.75, 5.75, 20.0, 19.0, 18.0, 5.5, 5.25, 5.0, 4.75, 4.0]);
        $this->_setBooking([ 
            [105, 0, 0],
            [106, 1, 0],
            [107, 2, 0],
            [1, 2, 1]
        ]);
        $updateQuery = self::$pdo->prepare("UPDATE workshop SET round_id=:round_id, location_id=:location_id, available=:available WHERE id=:id");

        $row = $dbo->getWorkshopToBook(3, 15.75);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(103, $row->id);
        $this->assertEquals("topic3", $row->name);
        $this->assertEquals(1, $row->round);
        $this->assertEquals(0, $row->last_location);
        $this->assertEquals(10, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 12.5);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(101, $row->id);
        $this->assertEquals("topic1", $row->name);
        $this->assertEquals(0, $row->round);
        $this->assertEquals(0, $row->last_location);
        $this->assertEquals(11, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 9.5);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(102, $row->id);
        $this->assertEquals("topic2", $row->name);
        $this->assertEquals(1, $row->round);
        $this->assertEquals(1, $row->last_location);
        $this->assertEquals(10, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 5.75);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(104, $row->id);
        $this->assertEquals("topic4", $row->name);
        $this->assertEquals(1, $row->round);
        $this->assertEquals(2, $row->last_location);
        $this->assertEquals(11, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 5.5);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(108, $row->id);
        $this->assertEquals("topic8", $row->name);
        $this->assertEquals(0, $row->round);
        $this->assertEquals(1, $row->last_location);
        $this->assertEquals(8, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 5.25);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(109, $row->id);
        $this->assertEquals("topic9", $row->name);
        $this->assertEquals(2, $row->round);
        $this->assertEquals(1, $row->last_location);
        $this->assertEquals(8, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 5.0);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(110, $row->id);
        $this->assertEquals("topic10", $row->name);
        $this->assertEquals(0, $row->round);
        $this->assertEquals(2, $row->last_location);
        $this->assertEquals(6, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        $row = $dbo->getWorkshopToBook(3, 4.75);
        $this->assertNotFalse($row);
        $this->assertInstanceOf(stdclass::class, $row);
        $this->assertEquals(111, $row->id);
        $this->assertEquals("topic11", $row->name);
        $this->assertEquals(2, $row->round);
        $this->assertEquals(2, $row->last_location);
        $this->assertEquals(6, $row->available);
        $this->assertEquals(1, $row->facilitators);
        $updateQuery->bindValue(':id', (int) $row->id, PDO::PARAM_INT);
        $updateQuery->bindValue(':round_id', (int) $row->round, PDO::PARAM_INT);
        $updateQuery->bindValue(':location_id', ((int) $row->last_location)+1, PDO::PARAM_INT);
        $updateQuery->bindValue(':available', (int) $row->available, PDO::PARAM_INT);
        $updateQuery->execute();

        // Attempting to get one more than can be scheduled!
        $row = $dbo->getWorkshopToBook(3, 4.0);
        // If this returns true, then round and last_location MUST be NULL
        if ($row) {
            $this->assertInstanceOf(stdclass::class, $row);
            $this->assertEquals(112, $row->id);
            $this->assertNull($row->round);
            $this->assertNull($row->last_location);
        }
    }

    /**
     * @covers ICCM\BOF\DBO::rollBack
     * @test
     */
    public function rollbackOnlyInvokesPDOCommit() {
        $pdoMock = $this->getMockBuilder(PDO::class)
                 ->disableOriginalConstructor()
                 ->setMethods(['beginTransaction', 'commit', 'prepare', 'query', 'rollBack'])
                 ->getMock();
        $pdoMock->expects($this->never())
            ->method('beginTransaction');
        $pdoMock->expects($this->never())
            ->method('commit');
        $pdoMock->expects($this->never())
            ->method('prepare');
        $pdoMock->expects($this->never())
            ->method('query');
        $pdoMock->expects($this->once())
            ->method('rollBack');
        $dbo = new DBO($pdoMock);
        $dbo->rollBack();
    }

    /**
     * @covers ICCM\BOF\DBO::switchBookings
     * @test
     */
    public function switchBookingsSwitchesOnlyWhatItShould() {
        $this->_setupWorkshops(2, 2, true, 0);
        $booking = [[101, 0, 0],
            [103, 0, 1],
            [104, 1, 0],
            [102, 1, 1]
        ];
        $this->_setBooking($booking);
        $booking[0][0] = 102;
        $booking[3][0] = 101;
        $dbo = new DBO(self::$pdo);
        $dbo->switchBookings(101, 0, 0, 102, 1, 1);
        $this->_verifyBooking($booking);
    }

    /**
     * @covers ICCM\BOF\DBO::validateLocations
     * @test
     */
    public function validateLocationsWhenValid() {
        $this->_setupLocations(5, true);
        $dbo = new DBO(self::$pdo);
        $this->assertTrue($dbo->validateLocations(5));
    }

    /**
     * @covers ICCM\BOF\DBO::validateLocations
     * @test
     */
    public function validateLocationsWhenInvalid() {
        $this->_setupLocations(5, false);
        $dbo = new DBO(self::$pdo);
        $this->assertFalse($dbo->validateLocations(5));
    }

    /**
     * @covers ICCM\BOF\DBO::validateRounds
     * @test
     */
    public function validateRoundsWhenValid() {
        $this->_setupRounds(5, true);
        $dbo = new DBO(self::$pdo);
        $this->assertTrue($dbo->validateRounds(5));
    }

    /**
     * @covers ICCM\BOF\DBO::validateRounds
     * @test
     */
    public function validateRoundsWhenInvalid() {
        $this->_setupRounds(5, false);
        $dbo = new DBO(self::$pdo);
        $this->assertFalse($dbo->validateRounds(5));
    }
}