<?php

namespace ICCM\BOF;

use ErrorException;
use \PDO;
use RuntimeException;
use Twig\Error\RuntimeError;

class DBO
{
    private $db;
    private $PrepBofId = 1;

    function __construct($db) {
        $this->db = $db;

        $settings = require __DIR__.'/../../cfg/settings.php';
        $this->PrepBofId = $settings['settings']['PrepBofId'];
    }

    /**
     * Begin a transaction on the database
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    /**
     * Books the given BoF in the round and location given.
     *
     * @param int $workshop The ID of the BoF to book
     * @param string $name  The name associated with the BoF
     * @param int $round The round in which to book (0-based)
     * @param int $location The location in which to book (0-based)
     * @param int $available How many people are available to attend
     * @param string $reason The reason for booking this BoF (e.g. it got many
     * votes, there were many people available, it's the Prep BoF, etc.)
     * @param ICCM\BOF\Logger $logger A logger object to log which BoF was
     * booked, where, and when.
     */
    public function bookWorkshop($workshop, $name, $round, $location, $available, $reason, $logger) {
        static $queryUpdate = null;
        if ($queryUpdate == null) {
            $queryUpdate = $this->db->prepare(
                "UPDATE workshop
                    SET round_id = :round,
                        location_id = :location,
                        available = :available
                  WHERE id = :workshop"
            );
        }
        $logger->logBookWorkshop($this, $name, $round, $location, $round, $reason);
        $queryUpdate->bindValue(':round', (int) $round, PDO::PARAM_INT);
        $queryUpdate->bindValue(':location', (int) $location, PDO::PARAM_INT);
        $queryUpdate->bindValue(':available', (int) $available, PDO::PARAM_INT);
        $queryUpdate->bindValue(':workshop', (int) $workshop, PDO::PARAM_INT);
        $queryUpdate->execute();
    }

    /**
     * Calculates the number of votes for each workshop, and sets the votes
     * column for each.
     */
    public function calculateVotes() {
        $sql="UPDATE workshop
                 SET votes = (SELECT SUM(participant)
                                FROM workshop_participant
                               WHERE workshop.id=workshop_participant.workshop_id),
                     round_id = NULL,
                     location_id = NULL,
                     available = NULL";
        $this->db->query($sql);
    }

    /**
     * Commits a transaction to the database.
     */
    public function commit() {
        $this->db->commit();
    }

    /**
     * Exports data from the booked workshops in CSV format.
     *
     * @return string
     */
    public function exportWorkshops() {
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
                    // TOM: Shouldn't this be $facilitator instead of $frow->name ?
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

        return $csvout;
    }

    /**
     * Finds workshops with conflicts in the leader. If a workshop has
     * multiple leaders, it is assumed not to conflict. This is less than
     * perfect, since it's possible to have three workshops in the same round,
     * with the first having leader A, the second leader B, and the third both
     * leader A and leader B. This is a conflict, but one that's difficult to
     * detect. If an algorithm is found to detect this case, we should apply
     * it.
     *
     * @return array with two members, "count" and "conflicts". The count
     * member is the number of conflicting workshops. The conflicts member is
     * an array containing one of the conflicting workshops for each unique
     * leader/round pair. This means that "count" may be more than the items
     * in the "conflicts" array.
     */
    public function findConflicts($logger) {
        // Declare queryConflicts as static, so it doesn't have to be prepared
        // every time this function is invoked.
        static $queryConflicts = null;
        static $queryCountWorkshops = null;
        if ($queryConflicts == null) {
            // sqlConflicts is a query string that returns a row for every
            // conflict I.e. there's a participant for a scheduled workshop who
            // is marked as the only leader for more than one in a single
            // round.
            $sqlConflicts =
                "SELECT c.participant_id, c.round_id
                   FROM
                       -- SELECT all workshops/leaders assigned to a round
                       (SELECT wp.participant_id, w.round_id
                          FROM workshop AS w
                          JOIN workshop_participant AS wp
                            ON w.id=wp.workshop_id
                         WHERE w.round_id IS NOT NULL
                           AND wp.leader=1
                           AND w.id NOT IN
                           -- Except workshops with more than one leader
                           (SELECT w.id
                              FROM workshop AS w
                              JOIN workshop_participant AS wp
                                ON w.id=wp.workshop_id
                             WHERE wp.leader=1
                          GROUP BY w.id
                            HAVING COUNT(*) > 1)
                      ORDER BY w.available asc, wp.participant_id) AS c
               GROUP BY c.participant_id, c.round_id
                 HAVING COUNT(*) > 1";
            $queryConflicts = $this->db->prepare($sqlConflicts);
        }
        if ($queryCountWorkshops == null) {
            // SQL to find the workshop and location IDs of the conflicting
            // workshops
            $sqlCountWorkshops = "SELECT COUNT(w.id)
                                    FROM workshop AS w
                                    JOIN workshop_participant AS wp
                                      ON w.id=wp.workshop_id
                                   WHERE wp.leader=1
                                     AND wp.participant_id=:participant_id
                                     AND w.round_id=:round_id";
            $queryCountWorkshops = $this->db->prepare($sqlCountWorkshops);
        }
        $queryConflicts->execute();
        $logger->log("Querying for conflicts");
        $resultsArray = array(
            'conflicts' => $queryConflicts->fetchAll(PDO::FETCH_OBJ),
            'count' => 0
        );
        //$tmp = count($resultsArray['conflicts']);
        foreach ($resultsArray['conflicts'] as $conflict) {
            $queryCountWorkshops->bindValue('participant_id', (int) $conflict->participant_id, PDO::PARAM_INT);
            $queryCountWorkshops->bindValue('round_id', (int) $conflict->round_id, PDO::PARAM_INT);
            $queryCountWorkshops->execute();
            $count = (int) $queryCountWorkshops->fetchColumn();
            if ($count > 0) {
                $resultsArray['count'] += $count - 1;
            }
        }
        $logger->log("Found {$resultsArray['count']} conflicts");

        return $resultsArray;
    }

    /**
     * Gets the workshop details (id, name, description and number of votes)
     * for the workshop booked for a specific location and round.
     * 
     * @param int $roundId The id of the round to retrieve workshop info
     * @param int $locationId The id of the location to retrieve workshop info
     *
     * @return stdClass containing the workshop details.
     */
    public function getBookedWorkshop($roundId, $locationId) {
        static $queryWorkshop = null;
        if ($queryWorkshop == null) {
            $sqlWorkshop = "SELECT id, name, description, votes
                            FROM workshop
                            WHERE location_id=:room
                            AND round_id=:round";
            $queryWorkshop = $this->db->prepare($sqlWorkshop);
        }
        $queryWorkshop->bindValue(':room', $locationId);
        $queryWorkshop->bindValue(':round', $roundId);
        $queryWorkshop->execute();
        
        return $queryWorkshop->fetch(PDO::FETCH_OBJ);
    }

    // Note that return value is an array with the first conflict found, and
    // the total number of conflicts represented here.  That number is the
    // number of rows returned by the query - 1.  At least two rows will be
    // returned for every conflict.
    /**
     * Gets the workshop id and location_id for a conflicting workshop, given
     * a leader/round pair (e.g. from an item in the "conflicts" member
     * returned by findConflicts()).
     *
     * @param stdClass $conflict An object containing the participant_id of a
     * leader and a round_id for a conflict.
     *
     * @return stdClass containing the workshop id and location_id of a
     * conflicting workshop.
     */
    public function getConflict($conflict) {
        static $queryFindWorkshop = null;
        if ($queryFindWorkshop == null) {
            // SQL to find the workshop and location IDs of the conflicting
            // workshops
            $sqlFindWorkshop = "SELECT w.id id, w.location_id location_id
                                FROM workshop AS w
                                JOIN workshop_participant AS wp
                                  ON w.id=wp.workshop_id
                                WHERE wp.leader=1
                                  AND w.id!=:prep_bof_id
                                  AND wp.participant_id=:participant_id
                                  AND w.round_id=:round_id
                                  AND w.location_id!=0";
            $queryFindWorkshop = $this->db->prepare($sqlFindWorkshop);
            $queryFindWorkshop->bindValue(':prep_bof_id', (int) $this->PrepBofId, PDO::PARAM_INT);
        }
        $queryFindWorkshop->bindValue(':round_id', (int) $conflict->round_id, PDO::PARAM_INT);
        $queryFindWorkshop->bindValue(':participant_id', (int) $conflict->participant_id);
        $queryFindWorkshop->execute();
        return $queryFindWorkshop->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Gets an array of workshop id, round_id, location_id, and new
     * availability for workshops with which to switch the given leader/round
     * pair (e.g. from an item in the "conflicts" member returned by
     * findConflicts()). Note that while there are many potential candidates,
     * this method always returns an array sorted by the highest new
     * availability.
     *
     * @param stdClass $conflict An object containing the participant_id of a
     * leader and a round_id for a conflict.
     *
     * @return Array An array of objects representing the workshops to try
     * switching with to reduce conflicts.
     */
    public function getConflictSwitchTargets($conflict) {
        static $queryFindSwitchTarget = null;
        if ($queryFindSwitchTarget == null) {
            // SQL to find something to switch our conflict with.  Note that
            // we NEVER switch with something in location 0, because that
            // location has all our top vote getters, and we never switch with
            // the Prep BOF.
            $sqlFindSwitchTarget = "SELECT DISTINCT w.id, w.round_id, w.location_id,
                                        (SELECT count(*)
                                        FROM workshop_participant
                                        WHERE workshop_id = w.id
                                            AND participant=1
                                            AND participant_id NOT IN
                                            (SELECT participant_id
                                            FROM workshop_participant
                                            JOIN workshop ON workshop.id = workshop_participant.workshop_id
                                            WHERE workshop.round_id=:round_id
                                                AND (workshop_participant.participant=1 or
                                                    (workshop_participant.workshop_id = :prep_bof_id and workshop_participant.participant > 0))
                                        )) AS available
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
                                        WHERE (wp.leader=1
                                            AND wp.participant_id=:participant_id)
                                            OR w.id=:prep_bof_id)
                               ORDER BY available desc, w.id asc";
            $queryFindSwitchTarget = $this->db->prepare($sqlFindSwitchTarget);
            $queryFindSwitchTarget->bindValue(':prep_bof_id', (int) $this->PrepBofId, PDO::PARAM_INT);
        }
        $queryFindSwitchTarget->bindValue(':round_id', (int) $conflict->round_id, PDO::PARAM_INT);
        $queryFindSwitchTarget->bindValue(':participant_id', (int) $conflict->participant_id);
        $queryFindSwitchTarget->execute();
        return $queryFindSwitchTarget->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Returns the current number of votes for each workshop, as well as some
     * detailed information about the workshop.
     * 
     * @return array An array whose elements are an object representing the
     * name, id, number of votes, and leader of each workshop. The leader
     * member is a comma-delimited list of users' names as a string.
     */
    public function getCurrentVotes() {
        $groupConcat = "GROUP_CONCAT(leader ORDER BY leader ASC SEPARATOR ', ')";
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            // Note that we can't make ORDER BY work inside the GROUP_CONCAT,
            // and the trick used in getFacilitiators doesn't work, either. To
            // get around these problems, we use a subquery and sort the
            // subquery by p.name. But we still have to specify ORDER BY in the
            // GROUP_CONCAT for mysql!
            $groupConcat = "GROUP_CONCAT(leader, ', ')";
        }
        $sql = "SELECT name, id, SUM(vote) as votes, "
                       . $groupConcat . " AS leader
                  FROM (SELECT w.name AS name, w.id AS id,
                               wp.participant AS vote, p.name AS leader
                          FROM workshop w
                     LEFT JOIN workshop_participant wp
                            ON wp.workshop_id = w.id
                     LEFT JOIN participant p
                            ON wp.participant_id = p.id
                           AND wp.leader = 1
                      ORDER BY p.name) AS t
              GROUP BY id
              ORDER BY votes DESC, id DESC";
        $query = $this->db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    // Returns a comma-delimited string of facilitators
    /**
     * Gets the names of the facilitators for the specified workshop.
     * 
     * @param int $workshopId The ID of the workshop for which to get the
     * facilitators.
     *
     * @return string The list of facilitators, as a comma-delimited string,
     * or an empty string if there are no leaders.
     */
    public function getFacilitators($workshopId) {
        static $queryFacilitators = null;
        if ($queryFacilitators == null) {
            $groupConcat = "GROUP_CONCAT(p.name ORDER BY p.name ASC SEPARATOR ', ')";
            // Ugly, but it works for SQLITE to force ordering in the
            // GROUP_CONCAT to be what we want.
            if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $groupConcat = "GROUP_CONCAT(p.name, ', ') OVER (ORDER BY p.name ASC ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)";
            }
            $sqlFacilitators =
                "SELECT DISTINCT " . $groupConcat . " AS leaders
                   FROM participant p
                   JOIN workshop_participant wp
                     ON p.id = wp.participant_id
                  where wp.leader = 1
                    AND wp.workshop_id = :id";
            $queryFacilitators = $this->db->prepare($sqlFacilitators);
        }

        $queryFacilitators->bindValue(':id', $workshopId);
        $queryFacilitators->execute();
        if ($row = $queryFacilitators->fetch(PDO::FETCH_OBJ)) {
            return $row->leaders;
        }
        return "";
    }

    /**
     * Gets the names of the locations in the DB as an array.
     *
     * @return array An array of the strings, whose keys are the IDs of the
     * locations.
     */
    public function getLocationNames() {
        $sql="SELECT id, name FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();
        $location_names = [];

        $count = 0;
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            $location_names[$row->id]=$row->name;
            $count++;
        }
        return $location_names;
    }

    /**
     * Gets the maximum vote total for workshops not yet booked.
     *
     * @return float  The maximum vote total
     */
    public function getMaxVote() {
        static $queryGetMaxVote = null;
        if ($queryGetMaxVote == null) {
            $queryGetMaxVote = $this->db->prepare(
                "SELECT max(votes) as maxvote
                   FROM workshop
                  WHERE round_id IS NULL
                 -- AND published=1"
            );
        }

        $queryGetMaxVote->execute();
        return (float) $queryGetMaxVote->fetchColumn();
    }

    /**
     * Gets the number of locations in the database.
     *
     * @return int The number of locations
     */
    public function getNumLocations() {
        $sql="SELECT COUNT(*) FROM location";
        $query=$this->db->prepare($sql);
        $query->execute();
        return (int) $query->fetchColumn();
    }

    /**
     * Gets the number of rounds in the database.
     *
     * @return int The number of rounds
     */
    public function getNumRounds() {
        $sql="SELECT COUNT(*) FROM round";
        $query=$this->db->prepare($sql);
        $query->execute();
        return (int) $query->fetchColumn();
    }

    /**
     * Gets information about the PrepBoF for booking
     *
     * @return stdClass An object with the id, name, and number of available
     * participants for the Prep BoF.
     */
    public function getPrepBoF() {
        $sql="SELECT id, name,
                     (SELECT COUNT(ID)
                        FROM workshop_participant
                       WHERE workshop_participant.workshop_id = ".$this->PrepBofId.
                       " AND participant > 0) AS available
                FROM workshop
                WHERE id = ".$this->PrepBofId;

        return $this->db->query($sql)->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Gets the time_periods of the rounds in the DB as an array.
     *
     * @return array An array of the strings, whose keys are the IDs of the
     * rounds.
     */
    public function getRoundNames() {
        $sql="SELECT id, time_period FROM round";
        $query=$this->db->prepare($sql);
        $query->execute();
        $round_names = [];

        $count = 0;
        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
            $round_names[$row->id] = $row->time_period;
            $count++;
        }
        return $round_names;
    }

    /**
     * Returns the current stage based on the current time and the data in the
     * config table.
     * 
     * @returns a string representing the current stage.
     */
    public function getStage() {
        $now = 'now()';
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $now = "DateTime('Now')";
        }
        $sql = "SELECT CASE
            WHEN ".$now." > (SELECT value FROM config WHERE item = 'nomination_begins')
             AND ".$now." < (SELECT value FROM config WHERE item = 'nomination_ends') THEN 'nominating'
            WHEN ".$now." > (SELECT value FROM config WHERE item = 'voting_begins')
             AND ".$now." < (SELECT value FROM config WHERE item = 'voting_ends') THEN 'voting'
            WHEN ".$now." > (SELECT value FROM config WHERE item = 'voting_ends') THEN 'finished'
            ELSE 'locked'
            END AS stage";
        $query = $this->db->prepare($sql);
        $query->execute();
        return $query->fetchColumn(0);
    }

    /**
     * Returns information for the top vote-getting workshops.
     *
     * @param int $workshops The total number of workshops to return
     *
     * @return array An array of objects with the id, name, number of votes,
     * and number of available participants for the top $rounds vote getting
     * workshops.
     */
    public function getTopWorkshops($workshops) {
        $sql="SELECT id, name, votes,
                     (SELECT COUNT(wp.id)
                        FROM workshop_participant wp
                       WHERE wp.workshop_id = workshop.id
                         AND wp.participant = 1) AS available
                FROM workshop
               -- WHERE published=1
               ORDER BY votes desc
               LIMIT :workshops";
        $qry_top3 = $this->db->prepare($sql);
        $qry_top3->bindValue(':workshops', (int) $workshops, PDO::PARAM_INT);
        $qry_top3->execute();

        return $qry_top3->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Gets the workshop names and ids for all workshops, in descending order
     * according to id.
     */
    public function getWorkshops() {
        $sql = "SELECT workshop.name, workshop.id FROM workshop ORDER BY id DESC";
        $query=$this->db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Gets booking information for a workshop with a particular vote total to
     * book. Note that this method accounts for availability of the
     * participants in order to determine into which round to place the
     * workshop.
     *
     * @param int $location The maximum location ID. This is usually
     * as getNumLocations() - 1.
     * @param float $maxvote The highest vote total of workshops not yet
     * booked (@see getMaxVote()).
     *
     * @return stdClass An object representing the workshop id to book,
     * along with which round and location to book it into, and the number of
     * available participants.
     */
    public function getWorkshopToBook($location, $maxvote) {
        static $queryGetWorkshop = null;
        if ($queryGetWorkshop == null) {
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
        }
        $queryGetWorkshop->bindValue(':maxlocation', (int) $location, PDO::PARAM_INT);
        //find next bof to book
        $queryGetWorkshop->bindValue(':maxvote', $maxvote, PDO::PARAM_STR);
        $queryGetWorkshop->execute();
        return $queryGetWorkshop->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Inserts the given title as a new workshop, using the given creator.
     * 
     * @param string $name The name for the new workshop.
     * @param string $description The description for the new workshop.
     * @param int $creator_id The ID of the user submitting this workshop. 
     */
    public function nominate($name, $description, $creator_id) {
        $sql = 'INSERT
                  INTO workshop (`name`,`description`,`creator_id`, `published`)
                VALUES (:name, :description, :creator_id, 0)';

        $query=$this->db->prepare($sql);
        $query->bindValue('name', $name, PDO::PARAM_STR);
        $query->bindValue('description', $description, PDO::PARAM_STR);
        $query->bindValue('creator_id', (int) $creator_id, PDO::PARAM_INT);
        $query->execute();
    }

    /**
     * Rolls back (undoes) a transaction that hasn't been committed yet.
     */
    public function rollBack() {
        $this->db->rollBack();
    }

    /**
     * Switches two workshop bookings.
     *
     * @param int $id1 The workshop id of the first workshop to switch
     * @param int $round_id1 The round_id of the first workshop to switch
     * @param int $location_id1 The location_id of the first workshop to switch
     * @param int $id2 The workshop id of the second workshop to switch
     * @param int $round_id2 The round_id of the second workshop to switch
     * @param int $location_id2 The location_id of the second workshop to
     * switch
     */
    public function switchBookings($id1, $round_id1, $location_id1,
                                   $id2, $round_id2, $location_id2) {
        static $queryUpdateRound = null;
        if ($queryUpdateRound == null) {
            $sqlUpdateRound = "UPDATE workshop
                                SET round_id=:round_id, location_id=:location_id
                                WHERE id=:id";
            $queryUpdateRound = $this->db->prepare($sqlUpdateRound);
        }
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

    /**
     * Checks if location IDs are consecutive, starting with id 0. TODO: Note
     * this is naieve, if location IDs starts with < 0, it won't be caught
     * here.
     *
     * @return true if location IDs are consecutive, starting with 0,
     * otherwise false.
     */
    public function validateLocations($locations) {
        // validate that we have consecutive location ids starting with 0
        $sql="SELECT id FROM location WHERE id >= ".$locations;
        $query=$this->db->prepare($sql);
        $query->execute();
        return ($query->fetch(PDO::FETCH_OBJ)) ? false : true;
    }

    /**
     * Checks if round IDs are consecutive, starting with id 0. TODO: Note
     * this is naieve, if round IDs starts with < 0, it won't be caught here.
     *
     * @return true if round IDs are consecutive, starting with 0, otherwise
     * false.
     */
    public function validateRounds($rounds) {
        // validate that we have consecutive round ids starting with 0
        $sql="SELECT id FROM round WHERE id >= ".$rounds;
        $query=$this->db->prepare($sql);
        $query->execute();
        return ($query->fetch(PDO::FETCH_OBJ)) ? false : true;
    }
}

?>
