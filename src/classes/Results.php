<?php

namespace ICCM\BOF;
use \PDO;

class Results
{
    private $db;
    private $view;
    private $router;
    private $logbuffer;
    private $debug=0;
    private $PrepBofId = 1;

    function __construct($view, $db, $router) {
        $this->view = $view;
        $this->db = $db;
        $this->router = $router;

        $settings = require __DIR__.'/../../cfg/settings.php';
        $this->PrepBofId = $settings['settings']['PrepBofId'];
    }

    function _switchSessions($id1, $round_id1, $location_id1,
                             $id2, $round_id2, $location_id2) {
        static $queryUpdateRound = null;
        if ($queryUpdateRound == null) {
            $sqlUpdateRound = "UPDATE workshop
                                SET round_id=:round_id, location_id=:location_id
                                WHERE id=:id";
            $queryUpdateRound = $this->db->prepare($sqlUpdateRound);
        }
        $this->log("Swapping workshop {$id1} in round {$round_id1}, location {$location_id1} with workshop {$id2} in round {$round_id2}, location {$location_id2}");
        // Put $id2 into $round_id1 and $location_id1
        $queryUpdateRound->bindValue(':id', (int) $id2, PDO::PARAM_INT);
        $queryUpdateRound->bindValue(':round_id', (int) $round_id1, PDO::PARAM_INT);
        $queryUpdateRound->bindValue(':location_id', (int) $location_id1, PDO::PARAM_INT);
        $success = $queryUpdateRound->execute();

        // Put $id1 into $round_id2 and $location_id2
        $queryUpdateRound->bindValue(':id', (int) $id1, PDO::PARAM_INT);
        $queryUpdateRound->bindValue(':round_id', (int) $round_id2, PDO::PARAM_INT);
        $queryUpdateRound->bindValue(':location_id', (int) $location_id2, PDO::PARAM_INT);
        $sucess = $queryUpdateRound->execute();
    }

    function _findConflicts() {
        // Declare queryConflicts as static, so it doesn't have to be prepared
        // every time this function is invoked.
        static $queryConflicts = null;
        if ($queryConflicts == null) {
            // sqlConflicts is a query string that returns a row for every
            // conflict I.e. there's a participant for a scheduled workshop who
            // is marked as the only leader for more than one in a single
            // round.
            $sqlConflicts = "SELECT c.participant_id, c.round_id
                            FROM
                                (SELECT wp.participant_id, w.round_id
                                FROM workshop_participant AS wp
                                JOIN workshop AS w
                                    ON w.id=wp.workshop_id
                                WHERE leader=1
                                    AND w.id NOT IN
                                    (SELECT w.id
                                        FROM workshop AS w
                                        JOIN workshop_participant AS wp
                                        ON w.id=wp.workshop_id
                                    WHERE leader=1
                                    GROUP BY w.id
                                    HAVING COUNT(*) > 1)
                            ORDER BY wp.participant_id) AS c
                GROUP BY c.participant_id, c.round_id HAVING COUNT(1) > 1";
            $queryConflicts = $this->db->prepare($sqlConflicts);
        }
        $queryConflicts->execute();
        return $queryConflicts->fetchAll(PDO::FETCH_OBJ);
    }

    function _resolveConflicts($rounds, $locations) {
        // Find minimum number of conflicts; limit to $triesLeft
        $triesLeft = $rounds * $locations; // Total number of sessions...
        $numNewConflicts = 0;
        $numConflicts = 0;
        // SQL to find the workshop and location IDs of the conflicting
        // workshops
        $sqlFindWorkshop = "SELECT w.id id, w.location_id location_id
                              FROM workshop AS w
                              JOIN workshop_participant AS wp
                                ON w.id=wp.workshop_id
                             WHERE wp.leader=1
                               AND wp.participant_id=:participant_id
                               AND w.round_id=:round_id
                               AND w.location_id!=0";
        $queryFindWorkshop = $this->db->prepare($sqlFindWorkshop);
        // SQL to find something to switch our conflict with.  Note that
        // we NEVER switch with something in location 0, because that
        // location has all our top vote getters, and we never switch with
        // the Prep BOF.
        $sqlFindSwitchTarget = "SELECT w.id, w.round_id, w.location_id
                                  FROM workshop AS w
                                  JOIN workshop_participant AS wp
                                    ON w.id=wp.workshop_id
                                 WHERE wp.leader=1
                                   AND w.location_id!=0
                                   AND w.round_id!=:round_id
                                   AND w.id NOT IN
                                    (SELECT w.id
                                       FROM workshop AS w
                                       JOIN workshop_participant AS wp
                                         ON w.id=wp.workshop_id
                                      WHERE wp.leader=1
                                        AND w.round_id=:round_id
                                        AND wp.participant_id!=:participant_id
                                        AND w.id!=:prep_bof_id)
                              ORDER BY w.id";
        $queryFindSwitchTarget = $this->db->prepare($sqlFindSwitchTarget);

        $conflicts=$this->_findConflicts();
        // TODO: This isn't quite right -- we need to know how many conflicts
        // there are for a single leader.  If a leader has all the locations
        // in a single round, it counts as one conflict, but it should count
        // as more.  Since it's only counting as one, we'll never resolve it,
        // because the conflict count won't be reduced; we'll resolve one
        // conflict, but still see one conflict.
        if ($conflicts) {
            $numNewConflicts = count($conflicts);
            $numConflicts = $numNewConflicts;
        }
        $conflictIndex = 0;

        // Loop up to $triesLeft times; no infinite loops here!
        while (($numNewConflicts > 0) && ($triesLeft > 0)) {
            $this->log("Found {$numNewConflicts} conflicts, checking conflict: {$conflictIndex}");
            // Make sure we don't go past the number of conflicts!
            if ($conflictIndex >= $numNewConflicts) {
                break;
            }

            // Find the workshop IDs of the first conflict in our array
            $this->log("Looking for workshop with leader: '{$conflicts[$conflictIndex]->participant_id}' and round: '{$conflicts[$conflictIndex]->round_id}'");
            $queryFindWorkshop->bindValue(':round_id', (int) $conflicts[$conflictIndex]->round_id, PDO::PARAM_INT);
            $queryFindWorkshop->bindValue(':participant_id', (int) $conflicts[$conflictIndex]->participant_id);
            $queryFindWorkshop->execute();
            // Move the first result...
            if ($row=$queryFindWorkshop->fetch(PDO::FETCH_OBJ)) {
                // Note that we start a transaction here, so if this switch is
                // worse, we can roll it back easily!
                $this->db->beginTransaction();
                $this->log("Found conflict in round: '{$conflicts[$conflictIndex]->round_id}', id: '{$row->id}', participant_id: '{$conflicts[$conflictIndex]->participant_id}'");
                // Find something to switch it with...
                $queryFindSwitchTarget->bindValue(':round_id', (int) $conflicts[$conflictIndex]->round_id, PDO::PARAM_INT);
                $queryFindSwitchTarget->bindValue(':participant_id', (int) $conflicts[$conflictIndex]->participant_id);
                $queryFindSwitchTarget->bindValue(':prep_bof_id', (int) $this->PrepBofId);
                $queryFindSwitchTarget->execute();
                if ($targetRow=$queryFindSwitchTarget->fetch(PDO::FETCH_OBJ)) {
                    // Switch the workshops!
                    $this->_switchSessions($row->id,
                        $conflicts[$conflictIndex]->round_id,
                        $row->location_id,
                        $targetRow->id,
                        $targetRow->round_id,
                        $targetRow->location_id);
                }
                else {
                    $this->log("Couldn't find something to switch '{$row->id}' in round '{$conflicts[$conflictIndex]->round_id}' with; checking next conflict.");
                    // We couldn't find something to switch it with!
                    // Advance to the next conflict, and try to resolve it
                    $conflictIndex++;
                    $this->db->rollBack();
                    continue;
                }
            }
            else {
                $this->log("Failed to find a conflicting workshop?");
                $conflictIndex++;
                continue;
            }

            // Now, determine how many conflicts exist -- this resets
            // $conflicts, so be sure to set $conflictIndex back to 0!
            $conflicts=$this->_findConflicts();
            $conflictIndex = 0;
            if ($conflicts)
                $numNewConflicts = count($conflicts);
            else
                $numNewConflicts = 0;
            $this->log("numNewConflicts: {$numNewConflicts}");
            // If we now have fewer conflicts, commit the transaction,
            // and let's try to handle the next conflict....
            if ($numNewConflicts < $numConflicts) {
                $numConflicts = $numNewConflicts;
                $this->db->commit();
                // Note that we don't decrement $triesLeft here, because we
                // made progres -- we might as well keep going as long as we
                // keep making progress.
                continue;
            }
            // Otherwise, we need to rollback the transaction, and try again;
            // be sure to count this as a try, by decrementing $triesLeft.
            $this->db->rollBack();
            $triesLeft--;
        }
    }

    function log($msg) {
        if ($this->debug) echo $msg."<br/>\n";
        $this->logbuffer .= $msg."\n";
    }

    public function _calculateResults() {
        $this->logbuffer = '';
        $this->db->beginTransaction();

        $sql="SELECT COUNT(*) FROM round";
        $query=$this->db->prepare($sql);
        $query->execute();
        $rounds=$query->fetchColumn();

        $sql="SELECT COUNT(*) FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();
        $locations=$query->fetchColumn();

        // validate that we have consecutive location ids starting with 0
        $sql="SELECT id FROM location WHERE id >= ".$locations;
        $query=$this->db->prepare($sql);
        $query->execute();
        if ($row=$query->fetch(PDO::FETCH_OBJ)) {
            die("locations must have consecutive ids starting with 0");
        }

        // validate that we have consecutive round ids starting with 0
        $sql="SELECT id FROM round WHERE id >= ".$rounds;
        $query=$this->db->prepare($sql);
        $query->execute();
        if ($row=$query->fetch(PDO::FETCH_OBJ)) {
            die("rounds must have consecutive ids starting with 0");
        }

        // translate location id to a real name (for logging purposes)
        $sql="SELECT id, name FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();

        $count = 0;
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            $location_names[$row->id]=$row->name;
            $count++;
        }

        // translate round id to a real name (for logging purposes)
        $sql="SELECT id, time_period FROM round";
        $query=$this->db->prepare($sql);
        $query->execute();

        $count = 0;
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            $round_names[$row->id] = $row->time_period;
            $count++;
        }

        //calculate # votes
        $sql="UPDATE workshop
                 SET votes = (SELECT SUM(participant)
                                FROM workshop_participant 
                               WHERE workshop.id=workshop_participant.workshop_id),
                     round_id = NULL,
                     location_id = NULL,
                     available = NULL";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        //place top in each round
        $sql="SELECT id,name, votes
                FROM workshop
               -- WHERE published=1
               ORDER BY votes desc
               LIMIT :rounds";
        $qry_top3 = $this->db->prepare($sql);
        $qry_top3->bindValue(':rounds', (int) $rounds, PDO::PARAM_INT);
        $qry_top3->execute();

        $count=0;
        while ($row=$qry_top3->fetch(PDO::FETCH_OBJ))
        {
            $sql2="UPDATE workshop
                      SET round_id = ?,
                          location_id = 0,
                          available=(SELECT COUNT(ID)
                                       FROM workshop_participant 
                                      WHERE workshop.id=workshop_participant.workshop_id
                                        AND participant=1)
                    WHERE id = ?";

            $this->log("Putting workshop '{$row->name}' in round '{$round_names[$count]}' at location '{$location_names[0]}'. Reason: {$row->votes} votes");
            $updatequery = $this->db->prepare($sql2);
            $params = array($count, $row->id);
            $updatequery->execute($params);

            $count += 1;
        }

        // now reserve the prep team BOF, id 1, room B
        $sql="SELECT name
                FROM workshop
                WHERE id = ".$this->PrepBofId;
        $qry_top3 = $this->db->prepare($sql);
        $qry_top3->execute();

        $count=0;
        if ($row=$qry_top3->fetch(PDO::FETCH_OBJ)) {
            // TODO: Fix this to be in the last round, not the third round!
            $sql2="UPDATE workshop
                      SET round_id = 2,
                          location_id = 1,
                          available=(SELECT COUNT(ID)
                                       FROM workshop_participant 
                                      WHERE workshop.id=workshop_participant.workshop_id
                                        AND participant>0)
                    WHERE id = ".$this->PrepBofId;

            $this->log("Putting workshop '{$row->name}' in round '{$round_names[2]}' at location '{$location_names[1]}'. Reason: Prep Team");
            $updatequery = $this->db->prepare($sql2);
            $updatequery->execute(array());
        }

        $sql = "SELECT s.id id,s.name,
                        roundsTable.round_id round,
                        roundsTable.last_location last_location,
                        --available,  
                        (SELECT count(*) 
                            FROM workshop_participant
                            WHERE workshop_id = s.id
                            AND participant=1
                            AND participant_id NOT IN
                                        (SELECT participant_id
                                            FROM workshop_participant
                                            JOIN workshop ON workshop.id=workshop_participant.workshop_id
                                            WHERE workshop.round_id=roundsTable.round_id
                                            AND (workshop_participant.participant=1 or
                                                -- special treatment of workshop 0. prep team
                                                (workshop_participant.workshop_id = ".$this->PrepBofId." and workshop_participant.participant > 0))
                        )) AS available,
                        (SELECT count(*) 
                            FROM workshop_participant
                            WHERE workshop_id = s.id
                            AND leader=1
                            AND participant_id NOT IN
                                (SELECT participant_id
                                    FROM workshop_participant
                                    JOIN workshop ON workshop.id=workshop_participant.workshop_id
                                    WHERE workshop.round_id=roundsTable.round_id
                                    AND workshop_participant.leader=1)
                        ) AS facilitators
                FROM workshop AS s
                LEFT JOIN (SELECT round_id, max(location_id) last_location
                            FROM workshop
                            WHERE round_id IS NOT NULL
                            GROUP BY round_id) AS roundsTable 
                        ON roundsTable.last_location < :maxlocation
                WHERE s.round_id is null
                    -- AND published=1
                    AND s.votes = :maxvote
                ORDER BY available DESC, facilitators DESC, round ASC
                LIMIT 0,1";
        $queryGetWorkshop= $this->db->prepare($sql);
        $queryGetWorkshop->bindValue(':maxlocation', (int) ($locations - 1), PDO::PARAM_INT);

        //loop through remaining possible slots
        for ($i=$rounds+1 ; $i < $rounds * $locations ; $i++) {

            //get highest # votes for unscheduled bof
            $sql="SELECT max(votes) as maxvote
                    FROM workshop 
                    WHERE round_id IS NULL 
                        -- AND published=1";
            $queryGetMaxVote = $this->db->prepare($sql);

            $queryGetMaxVote->execute();
            if (!($maxvote = $queryGetMaxVote->fetchColumn())) {
                // there are none left, we are done
                break;
            }

            //find next bof to book
            $queryGetWorkshop->bindValue(':maxvote', $maxvote, PDO::PARAM_STR);
            $queryGetWorkshop->execute();

            while ($row=$queryGetWorkshop->fetch(PDO::FETCH_OBJ))
            {
                $sql2="UPDATE workshop
                              SET round_id = ?,
                                  location_id = ?,
                                  available = ?
                            WHERE id=?";

                $updatequery = $this->db->prepare($sql2);
                $params = array($row->round, $row->last_location + 1, $row->available, $row->id);
                $updatequery->execute($params);

                $this->log("Putting workshop '{$row->name}' in round '{$round_names[$row->round]}' at location '"
                 . $location_names[$row->last_location+1]
                 . "'. Reason: Vote count = {$row->available}, {$row->facilitators} facilitators");
            }
        }

        $this->db->commit();

        $this->_resolveConflicts($rounds, $locations);
    }

    public function calculateResults($request, $response, $args) {
        $this->_calculateResults();
        return $this->exportResult($request, $response, $args);
    }

    public function exportResult($request, $response, $args) {
        $csvout = "";

        $sql = "SELECT w.id id, w.name, w.description,
                       round.time_period,
                       location.name as location_name,
                       votes,
                       available
                FROM workshop AS w JOIN round ON round_id = round.id JOIN location ON location_id=location.id
                WHERE w.round_id is NOT null
                ORDER BY round.id, location.id";
        $query = $this->db->prepare($sql);
        $query->execute();

        while ($row=$query->fetch(PDO::FETCH_OBJ))
        {
            $facilitators = "";

            $sql = 'SELECT p.`name`
                FROM `workshop_participant` wp, `participant` p
                WHERE wp.`participant_id` = p.`id`
                AND wp.`leader` = 1
                AND wp.workshop_id = '.$row->id;
            $query2=$this->db->prepare($sql);
            $query2->execute(array());
            while ($frow=$query2->fetch(PDO::FETCH_OBJ)) {
                foreach ($frow as $facilitator) {
                    if ($facilitators != "") {
                           $facilitators .= ', ';
                    }
                    $facilitators .= $frow->name;
                }
            }

            $csvout .= $row->location_name.",".
                        '"'.str_replace('"', "'", strip_tags($row->name)).'",'.
                        '"'.$facilitators.'",'.
                        '"'.str_replace('"', "'", strip_tags($row->description)).'",'.
                        $row->votes.",".
                        $row->available."\n";
        }

        $config['loggedin'] = true;
        $stage =new Stage($this->db);
        $config['stage'] = $stage->getstage();
        $config['csvdata'] = $csvout;
        $config['log'] = $this->logbuffer;
        return $this->view->render($response, 'results.html', $config);
    }
}

?>
