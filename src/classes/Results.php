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

    function log($msg) {
        if ($this->debug) echo $msg."<br/>\n";
        $this->logbuffer .= $msg."\n";
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

        // now reserve the prep team BOF, id 1, room B
        $sql="SELECT name
                FROM workshop
                WHERE id = ".$this->PrepBofId;
        $qry_top3 = $this->db->prepare($sql);
        $qry_top3->execute();

        $count=0;
        if ($row=$qry_top3->fetch(PDO::FETCH_OBJ)) {
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

        //loop through remaining possible slots
        for ($i=$rounds+1 ; $i < $rounds * $locations ; $i++) {

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

        $this->db->commit();

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
