<?php

namespace ICCM\BOF;

use RuntimeException;

class Results
{
    private $dbo;
    private $logger;

    function __construct($dbo, $logger) {
        $this->dbo = $dbo;
        $this->logger = $logger;
    }

    /**
     * Books the Prep BoF, according to the values returned by
     * DBO::getPrepBoF.  If the round or location returned by getPrepBoF is -1,
     * then the last round or location will be used.  If getPrepBoF returns
     * false, then the Prep BoF will not be booked.
     *
     * @param int $rounds The total number of rounds
     * @param int $locations The total number of locations
     *
     * @return boolean true if the Prep BoF was booked, otherwise false
     */
    private function bookPrepBoF($rounds, $locations) {
        if ($row = $this->dbo->getPrepBoF()) {
            $round = $row->round;
            $location = $row->location;
            if ($round == -1) {
                $round = $rounds - 1;
            }
            if ($location == -1) {
                $location = $locations - 1;
            }
            $this->dbo->bookWorkshop($row->id, $row->name, $round, $location, $row->available, "Prep BoF", $this->logger);
            return true;
        }
        return false;
    }

    /**
     * Books the top vote-getting workshops into location 0 of each round.
     *
     * @param int $rounds The total number of rounds
     */
    private function bookTopVotes($rounds) {
        $topWorkshops = $this->dbo->getTopWorkshops($rounds);
        $count = 0;
        foreach ($topWorkshops as $workshop) {
            $this->dbo->bookWorkshop($workshop->id, $workshop->name, $count, 0, $workshop->available, "{$workshop->votes} votes", $this->logger);
            $count += 1;
        }
    }

    /**
     * Books workshops, and resolves facilitator conflicts.
     *
     * @param int $rounds The total number of rounds.
     * @param int $locations The total number locations.
     */
    private function bookWorkshops($rounds, $locations) {
        $this->logger->clearLog();

        $this->dbo->beginTransaction();
        $this->dbo->calculateVotes();
        $this->bookTopVotes($rounds);
        $booked = $this->bookPrepBoF($rounds, $locations);
        $this->fillBooking($rounds, $locations, $booked ? 1 : 0);
        $this->dbo->commit();

        $this->resolveConflicts($locations);
    }

    /**
     * Helper for bookWorkshops() to book the rest of the workshops based on
     * votes and availability.
     *
     * @param int $rounds The total number of rounds.
     * @param int $locations The total number locations.
     * @param int $booked 1 if Prep BoF was booked, otherwise 0
     */
    private function fillBooking($rounds, $locations, $booked) {
        //loop through remaining possible slots
        for ($i=$rounds + $booked ; $i < $rounds * $locations ; $i++) {
            //get highest # votes for unscheduled bof
            if (!($maxvote = $this->dbo->getMaxVote())) {
                // there are none left, we are done
                break;
            }

            if ($row = $this->dbo->getWorkshopToBook($locations - 1, $maxvote)) {
                $this->dbo->bookWorkshop($row->id, $row->name, $row->round, $row->last_location + 1, $row->available, "{$maxvote} votes, {$row->available} available, {$row->facilitators} facilitators", $this->logger);
            }
        }
    }

    /**
     * Resolve conflicts with facilitators.  Conflicts might not be resolvable!
     *
     * @param int $locations The total number locations.
     */
    private function resolveConflicts($locations) {
        // Find minimum number of conflicts; limit to $triesLeft
        $conflictArr=$this->dbo->findConflicts($this->logger);
        $conflictIndex = 0;

        // Loop to resolve conflicts.
        // Make sure we don't walk off the end of the
        // $conflictsArr['conflicts'] array, too.
        // Note that we might change $conflictArr INSIDE the
        // loop!
        while (($conflictArr['count'] > 0)
               && ($conflictIndex < count($conflictArr['conflicts']))) {
            $this->logger->log("Resolving {$conflictArr['count']} conflicts!");

            // Find the workshop IDs of the first conflict in our array
            if ($row=$this->dbo->getConflict($conflictArr['conflicts'][$conflictIndex])) {
                // Find something to switch it with...
                $targetRows = $this->dbo->getConflictSwitchTargets($conflictArr['conflicts'][$conflictIndex]);
                if (count($targetRows) > 0) {
                    foreach ($targetRows as $targetRow) {
                        // If $conflictArr2 is not false, then we made good
                        // progress, so reset our control variables, and break
                        // this innermost loop.
                        if (($conflictArr2 = $this->trySwitch($conflictArr['conflicts'][$conflictIndex], $row, $targetRow, $conflictArr['count'])) != false) {
                            $conflictArr = $conflictArr2;
                            // Set the index to -1 here because it gets
                            // incremented below.
                            $conflictIndex = -1;
                            break;
                        }
                        /*else {
                            $this->logger->log('Switching targets failed!');
                        }*/
                    }
                }
                /*else {
                    $this->logger->log("Couldn't find anything to switch with!");
                }*/
            }
            // Advance to the next conflict, and try to resolve it
            $conflictIndex++;
        }
    }

    /**
     * Helper for resolveConflicts. This switches the bookings for two
     * workshops and checks if there are now fewer conflicts. If there are
     * fewer conflicts, the switch is kept by commiting the database
     * transaction, if there are not fewer conflicts, the switch is rejected.
     *
     * @param stdClass $conflict Information about the conflict workshop to
     * switch
     * @param stdClass $row Information about the conflict workshop to switch
     * @param stdClass $targetRow Information about the workshop to switch with
     * @param int $numConflicts The number of conflicts before trying this
     * change.
     */
    private function trySwitch($conflict, $row, $targetRow, $numConflicts) {
        // Note that we start a transaction here, so if this
        // switch is worse, we can roll it back easily!
        // Find something to switch it with...
        $this->dbo->beginTransaction();
        // Switch the workshops!
        $this->dbo->switchBookings($row->id,
            $conflict->round_id,
            $row->location_id,
            $targetRow->id,
            $targetRow->round_id,
            $targetRow->location_id);
        $this->logger->logSwitchedWorkshops($this->dbo, $row->id,
            $targetRow->round_id,
            $targetRow->location_id,
            $targetRow->id,
            $conflict->round_id,
            $row->location_id);
        $conflictArr2=$this->dbo->findConflicts($this->logger);
        // If we now have fewer conflicts, commit the
        // transaction, and let's try to handle the next
        // conflict....
        if ($conflictArr2['count'] < $numConflicts) {
            $this->logger->log('Keeping switch!');
            $this->dbo->commit();
            return $conflictArr2;
        }
        // Otherwise, we need to rollback the transaction, and
        // try again
        $this->logger->log("Switching {$row->id} and {$targetRow->id} resulted in more conflicts, rolling back changes.");
        $this->dbo->rollBack();
        return false;
    }

    /**
     * Entry point for the class -- books workshops and returns data about
     * the booked workshops in CSV format along with some log data about what
     * was done and why.
     */
    public function calculateResults() {
        $rounds = $this->dbo->getNumRounds();
        $locations = $this->dbo->getNumLocations();

        if ($locations < 2) {
            throw new RuntimeException("There must be at least two locations!");
        }

        if (! $this->dbo->validateLocations($locations)) {
            throw new RuntimeException("locations must have consecutive ids starting with 0");
        }

        if (! $this->dbo->validateRounds($rounds)) {
            throw new RuntimeException("rounds must have consecutive ids starting with 0");
        }

        $this->bookWorkshops($rounds, $locations);

        $config['loggedin'] = true;
        $config['stage'] = $this->dbo->getStage();
        $config['csvdata'] = $this->dbo->exportWorkshops();
        $config['log'] = $this->logger->getLog();
        return $config;
    }
}

?>
