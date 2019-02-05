<?php

namespace ICCM\BOF;
use \PDO;

class Results
{
    private $db;
    private $logbuffer;
    private $debug=1;

    function log($msg) {
        if ($this->debug) echo $msg."<br/>\n";
        $this->logbuffer .= $msg;
    }

    function __construct($db) {
        $this->db = $db;
    }

    public function calculateResults($request, $response, $args) {
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

        //loop through remaining possible slots
        for ($i=$rounds+1 ; $i <= $rounds * $locations ; $i++) {

            //get highest # votes for unscheduled bof
            $sql="SELECT max(votes) as maxvote
                    FROM workshop 
                   WHERE round_id IS NULL 
                     -- AND published=1";
            $query = $this->db->prepare($sql);
            $query->execute();
            if (!($maxvote = $query->fetchColumn())) {
                // there are none left, we are done
                break;
            }

            //find next bof to book
            $sql = "SELECT s.id id,s.name,
                           roundsTable.round_id round,
                           roundsTable.last_location last_location,
                           available,  
                           (SELECT count(*) 
                              FROM workshop_participant
                             WHERE workshop_id = s.id
                               AND participant=1
                               AND participant_id NOT IN
                                           (SELECT participant_id
                                              FROM workshop_participant
                                              JOIN workshop ON workshop.id=workshop_participant.workshop_id
                                             WHERE workshop.round_id=roundsTable.round_id
                                               AND workshop_participant.participant=1)
                           ) AS available,
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
                         ON roundsTable.last_location < ?
                    WHERE s.round_id is null
                      -- AND published=1
                      AND s.votes = ?
                    ORDER BY available DESC, facilitators DESC, round ASC
                    LIMIT 0,1";
            $query = $this->db->prepare($sql);
            $params = array($locations-1, $maxvote);
            $query->execute($params);

            while ($row=$query->fetch(PDO::FETCH_OBJ))
            {
                $sql2="UPDATE workshop
                              SET round_id = ?,
                                  location_id = ?,
                                  available = ?
                            WHERE id=?";

                $updatequery = $this->db->prepare($sql2);
                $params = array($row->round, $row->last_location + 1, $row->available, $row->id);
                $updatequery->execute($params);

                $this->log("Putting workshop '{$row->name}' in round '{$round_names[$row->round]}' at location '" . $location_names[$row->last_location+1] . "'. Reason: Vote count = {$row->available}, {$row->facilitators} facilitators");

            }
        }

        $this->log("Processing non-scheduled workshops...done");
        $this->db->commit();

    }
    public function exportResult($request, $response, $args) {

    }


}

?>
