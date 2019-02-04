<?php

namespace ICCM\BOF;
use \PDO;

class Results
{
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    public function calculateResults($request, $response, $args) {

        $log = '';
        //$this->db->beginTransaction();

        // translate location id to a real name (for logging purposes)
        $sql="SELECT id, name FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();
       
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            $location_names[$row->id]=$row->name;
        }

	$sql="SELECT COUNT(*) FROM round";
        $query=$this->db->prepare($sql);
        $query->execute();
        $rounds=$query->fetchColumn();

        $sql="SELECT COUNT(*)  FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();
        $locations=$query->fetchColumn();

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
               WHERE published=1
               ORDER BY votes desc
               LIMIT :rounds";
        $qry_top3 = $this->db->prepare($sql);
        $qry_top3->bindValue(':rounds', (int) $rounds, PDO::PARAM_INT);
        $qry_top3->execute();

        $count=1;
        while ($row=$qry_top3->fetch(PDO::FETCH_OBJ))
        {
            $sql2="UPDATE workshop
                      SET round_id = :count,
                          location_id=1,
                          available=(SELECT COUNT(ID)
                                       FROM workshop_participant 
                                      WHERE workshop.id=workshop_participant.workshop_id
                                        AND participant=1)
                    WHERE id= :id";

            $log.="Putting workshop '{$row->name}' in round '$count' at location '{$location_names[1]}'. Reason: {$row->votes} votes\n";

            $updatequery = $this->db->prepare($sql2);
            $updatequery->execute($sql2, ['count'=>$count, 'id'=> $row->id]);

            $count += 1;
        }

        //loop through remaining possible slots
        for ($i=$rounds+1 ; $i <= $rounds * $locations ; $i++) {

            //get highest # votes for unscheduled bof
            $sql="SELECT max(votes) as maxvote
                    FROM workshop 
                   WHERE round_id IS NULL 
                     AND published=1";
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
                         ON roundsTable.last_location < :locations
                    WHERE s.round_id is null
                      AND published=1
                      AND s.votes = :maxVote
                    ORDER BY available DESC, facilitators DESC, round ASC
                    LIMIT 0,1";
            $query = $this->db->prepare($sql);
            $query->execute(['maxVote'=>$maxvote, 'locations'=>$locations]);
echo "before whil";
            while ($row=$query->fetch(PDO::FETCH_OBJ))
            {
print_r($row);
                $sql2="UPDATE workshop
                              SET round_id = :round,
                                  location_id = :location,
                                  available = :avail
                            WHERE id=:id";

                $updatequery = $this->db->prepare($sql2);
                $updatequery->execute([
                    'round'=>$row->round,
                    'location'=>$row->last_location + 1,
                    'avail'=>$row->available,
                    'id'=>$row->id]);

                echo "Putting workshop '{$row->name}' in round '{$row->round}' at location '" . $location_names[$row->last_location+1] . "'. Reason: Vote count = {$row->available}, {$row->facilitators} facilitators\n";
                $log.= "Putting workshop '{$row->name}' in round '{$row->round}' at location '" . $location_names[$row->last_location+1] . "'. Reason: Vote count = {$row->available}, {$row->facilitators} facilitators\n";

            }
        }


        $log.="Processing non-scheduled workshops...done";
echo $log;
    //    $this->db->commit();

    }
    public function exportResult($request, $response, $args) {

    }


}

?>
