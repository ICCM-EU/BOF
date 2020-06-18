<?php

namespace ICCM\BOF;

use ErrorException;
use \PDO;
use RuntimeException;
use Twig\Error\RuntimeError;

class Logger
{
    private $logbuffer;
    private $debug=0;
    private $location_names = [];
    private $round_names = [];

    function __construct() {
        $this->logbuffer = '';
    }

    /**
     * Clears all data from the log.
     */
    public function clearLog() {
        $this->logbuffer = '';
    }

    /**
     * Gets the contents of the log, and then clears it.
     * 
     * @return string The log data as a string
     */
    public function getLog() {
        $logbuffer = $this->logbuffer;
        $this->clearLog();
        return $logbuffer;
    }

    /**
     * Adds the given message to the log. The string is postfixed with "\n".
     * 
     * @param string $msg The message to add to the log.
     */
    public function log($msg) {
        if ($this->debug) echo $msg."<br/>\n";
        $this->logbuffer .= $msg."\n";
    }

    /**
     * Logs a message about booking a workshop.
     * 
     * @param DBO $dbo A DBO object to retrieve information about round time
     * periods and location names.
     * @param string $workshop The name of the workshop being booked
     * @param int $round The ID of the round for booking
     * @param int $location The ID of the location for booking
     * @param string $reason The reason this workshop was booked
     */
    public function logBookWorkshop($dbo, $workshop, $round, $location, $reason) {
        if (empty($this->location_names)) {
            $this->location_names = $dbo->getLocationNames();
        }

        if (empty($this->round_names)) {
            $this->round_names = $dbo->getRoundNames();
        }

        $this->log("Putting workshop '{$workshop}' in round '{$this->round_names[$round]}' at location '{$this->location_names[$location]}'. Reason: {$reason}");
    }

    /**
     * Logs a message about workshops that have been switched
     * 
     * @param DBO $dbo A DBO object to retrieve information about round time
     * periods and location names.
     * @param string $workshop1 The name of the first workshop switched
     * @param int $round1 The round id of the first workshop switched
     * @param int $location1 The location id of the first workshop switched
     * @param string $workshop2 The name of the second workshop switched
     * @param int $round2 The round id of the second workshop switched
     * @param int $location2 The location id of the second workshop switched
     */
    public function logSwitchedWorkshops($dbo, $workshop1, $round1, $location1, $workshop2, $round2, $location2) {
        if (empty($this->location_names)) {
            $this->location_names = $dbo->getLocationNames();
        }

        if (empty($this->round_names)) {
            $this->round_names = $dbo->getRoundNames();
        }
        $this->log("Switched workshops!  '{$workshop1}' is now in round '{$this->round_names[$round1]}' at location '{$this->location_names[$location1]}'. '{$workshop2}' is now in round '{$this->round_names[$round2]}' at location '{$this->location_names[$location2]}'.");
    }
}

?>
