<?php

require_once __DIR__ . '/../../classes/Results.php';
require_once __DIR__ . '/../../classes/DBO.php';
require_once __DIR__ . '/../../classes/Logger.php';
require_once __DIR__ . '/../../vendor/slim/twig-view/src/Twig.php';

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Results;
use ICCM\BOF\DBO;
use ICCM\BOF\Logger;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

/**
 * @covers ICCM\BOF\Results
 */
class TestResults extends TestCase
{
    use ProphecyTrait;

    # Helper function for testing Results::calculateResults.  This takes in a
    # number of rounds and locations, sets up mocks which exercise the logic
    # and runs calculateResults, when that method should succeed.
    private function _calculateResults($rounds, $locations, $enough, $conflicts, $havePrep)
    {
        // Expected data values
        $exportedData = <<<EOF
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
        $logData = 'testCalculateResults' . $rounds . ' ' .$locations;

        $config['loggedin'] = true;
        $config['stage'] = 'stage';
        $config['csvdata'] = $exportedData;
        $config['log'] = $logData;

        // Various vars for the DBO mock; some are used in expectations for the
        // mock, while others are return values to fully exercise the logic.
        $row = (object) [
            'id' => 101,
            'name' => 'Workshop 1',
            'round' => 2,
            'location_id' => 1,
            'last_location' => 0,
            'available' => 3,
            'facilitators' => 'user1'
        ];

        $prepBoF = (object) [
            'id' => 1,
            'name' => 'Prep Team',
            'available' => 5
        ];

        $topWorkshops = [];
        for ($i = 0; $i < $rounds; $i++) {
            $topWorkshops[$i] = (object) [
                'id' => $i + 100,
                'name' => 'topic' . $i,
                'votes' => $i,
                'available' => $i
            ];
        }

        $maxVotes = [];
        $workshopsToBook = [];
        $maxWorkshops = $rounds * $locations - $rounds - 1;
        $maxWorkshopsToBook = $rounds * $locations - $rounds - 2;
        if ($enough) {
            $maxWorkshopsToBook++;
        }
        for ($i = 0; $i < $maxWorkshopsToBook; $i++) {
            if ($i == 0) {
                $maxVotes[0] = 23.5;
            }
            else {
                $maxVotes[$i] = $maxVotes[$i-1] - 0.25;
            }
            $workshopsToBook[$i] = $row;
        }
        $maxVotes[$i] = 0.0;
        if ($i > 0) {
            $workshopsToBook[$i-1] = false;
        }
        else {
            $workshopsToBook[0] = false;
        }

        $conflict = (object) [
            'participant_id' => 102,
            'round_id' => 1
        ];

        $conflictArr1 = [
            'count' => 1,
            'conflicts' => [
                $conflict
            ]
        ];

        $conflictArr2 = [
            'count' => 2,
            'conflicts' => [
                $conflict,
                $conflict
            ]
        ];

        $conflictArr3 = [
            'count' => 3,
            'conflicts' => [
                $conflict,
                $conflict,
                $conflict
            ]
        ];

        $conflictArr4 = [
            'count' => 4,
            'conflicts' => [
                $conflict,
                $conflict,
                $conflict,
                $conflict
            ]
        ];

        $conflictArrNone = [
            'count' => 0,
            'conflicts' => []
        ];

        $conflictArrMax = [
            'count' => $rounds * ($locations - 1),
            'conflicts' => []
        ];

        $conflictMaxInArray = $rounds;

        if ($locations == 2) {
            $conflictArrMax['count'] = $rounds - 1;
            $conflictMaxInArray = $rounds - 1;
        }

        for ($i = 0; $i < $conflictMaxInArray; $i++) {
            $conflictArrMax['conflicts'][$i] = $conflict;
        }

        if (! $enough) {
            $conflictArrMax['count'] -= 1;
        }

        $switchTargets = [
            (object) [
                'id' => 101,
                'round_id' => 1,
                'location_id' => 2,
                'available' => 4
            ],
            (object) [
                'id' => 102,
                'round_id' => 1,
                'location_id' => 3,
                'available' => 3
            ]
        ];

        // Logger mock
        $logger = $this->getMockBuilder(Logger::class)
              ->disableOriginalConstructor()
              ->onlyMethods(['clearLog', 'getLog', 'log', 'logBookWorkshop', 'logSwitchedWorkshops'])
              ->getMock();
        $logger->expects($this->once())
            ->method('getLog')
            ->willReturn($logData);
        $logger->expects($this->once())
               ->method('clearLog');

        // DBO mock
        $dbo = $this->prophesize(DBO::class);
        $dbo->getStage()
            ->willReturn('stage')
            ->shouldBeCalledTimes(1);

        $dbo->exportWorkshops()
            ->willReturn($exportedData)
            ->shouldBeCalledTimes(1);
        if ($havePrep) {
            $dbo->bookWorkshop($prepBoF->id, $prepBoF->name, $rounds - 1, 1, $prepBoF->available, 'Prep BoF', $logger)
                ->shouldBeCalledTimes(1);
        }
        else {
            $dbo->bookWorkshop($prepBoF->id, $prepBoF->name, $rounds - 1, 1, $prepBoF->available, 'Prep BoF', $logger)
                ->shouldNotBeCalled();
        }
        for ($i = 0; $i < $rounds; $i++) {
            $dbo->bookWorkshop($topWorkshops[$i]->id, $topWorkshops[$i]->name,
                $i, 0, $topWorkshops[$i]->available, Argument::any(), $logger)
                ->shouldBeCalledTimes(1);
        }
        $dbo->bookWorkshop(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), $logger)
            ->shouldBeCalled();

        if ($conflicts == 0) {
            $dbo->beginTransaction()
                ->shouldBeCalledTimes(1);
            $dbo->commit()
                ->shouldBeCalledTimes(1);
            $dbo->findConflicts($logger)
                ->willReturn($conflictArrNone)
                ->shouldBeCalledTimes(1);
            $dbo->getConflict(Argument::any())->shouldNotBeCalled();
            $dbo->getConflictSwitchTargets(Argument::any())->shouldNotBeCalled();
            $dbo->rollBack()->shouldNotBeCalled();
            $dbo->switchBookings(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }
        else if ($conflicts == 1) {
            $dbo->beginTransaction()
                ->shouldBeCalledTimes(4);
            $dbo->commit()
                ->shouldBeCalledTimes(3);
            $dbo->findConflicts($logger)
                ->willReturn($conflictArr3, $conflictArr4, $conflictArr2, $conflictArr1, $conflictArrNone)
                ->shouldBeCalledTimes(4);
            $dbo->getConflict(Argument::any())
                ->willReturn($row)
                ->shouldBeCalledTimes(3);
            $dbo->getConflictSwitchTargets(Argument::any())
                ->willReturn($switchTargets, $switchTargets, [], $switchTargets)
                ->shouldBeCalledTimes(3);
            $dbo->rollBack()->shouldBeCalledTimes(1);
            $dbo->switchBookings(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldBeCalledTimes(3);
        }
        else if ($conflicts == 2) {
            $dbo->beginTransaction()->shouldBeCalledTimes(1);
            $dbo->commit()->shouldBeCalledTimes(1);
            $dbo->findConflicts($logger)
                ->willReturn($conflictArrMax)
                ->shouldBeCalledTimes(1);
            $dbo->getConflict(Argument::any())
                ->willReturn($row)
                ->shouldBeCalledTimes(count($conflictArrMax['conflicts']));
            $dbo->getConflictSwitchTargets(Argument::any())
                ->willReturn([])
                ->shouldBeCalledTimes(count($conflictArrMax['conflicts']));
            $dbo->rollBack()->shouldNotBeCalled();
            $dbo->switchBookings(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        }
        $dbo->calculateVotes()->shouldBeCalledTimes(1);
        $dbo->getMaxVote()
            ->willReturn(...$maxVotes)
            ->shouldBeCalledTimes($maxWorkshops);
        $dbo->getNumLocations()
            ->willReturn($locations)
            ->shouldBeCalledTimes(1);
        $dbo->getNumRounds()
            ->willReturn($rounds)
            ->shouldBeCalledTimes(1);
        if ($havePrep) {
            $dbo->getPrepBoF(Argument::any())
                ->willReturn($prepBoF)
                ->shouldBeCalledTimes(1);
        }
        else {
            $dbo->getPrepBoF(Argument::any())
                ->willReturn(false)
                ->shouldBeCalledTimes(1);
        }
        $dbo->getTopWorkshops(Argument::any())
            ->willReturn($topWorkshops)
            ->shouldBeCalledTimes(1);
        $dbo->getWorkshopToBook(Argument::any(), Argument::any())
            ->willReturn(...$workshopsToBook)
            ->shouldBeCalledTimes($maxWorkshopsToBook);
        $dbo->validateLocations(Argument::any())
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $dbo->validateRounds(Argument::any())
            ->willReturn(true)
            ->shouldBeCalledTimes(1);

        // ResponseInterface mock
        $response = $this->getMockBuilder(ResponseInterface::class)
              ->disableOriginalConstructor()
              ->getMock();

        // Twig view mock
        $view = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
        $view->expects($this->once())
            ->method('render')
            ->with($response, 'results.html', $config)
            ->willReturn(4);

        $results = new Results($view, null, $dbo->reveal(), $logger);
        // This ensures the ultimate return value is correct (i.e. whatever
        // $view->render returns), and calls the function under test.
        $this->assertEquals(4, $results->calculateResults(null, $response, null));
    }

    /**
     * @test
     */
    public function calculateResultsWithNoPrepBoF() {
        $this->_calculateResults(3, 4, true, 0, false);
    }

    /**
     * @test
     */
    public function calculateResultsWhenNoConflictsEnoughWorkshops34() {
        $this->_calculateResults(3, 4, true, 0, true);
    }
/*
    public function calculateResultsWhenNoConflictsEnoughWorkshops25() {
        $this->_calculateResults(2, 5, true, 0, true);
    }

    public function calculateResultsWhenNoConflictsEnoughWorkshops52() {
        $this->_calculateResults(5, 2, true, 0, true);
    }

    public function calculateResultsWhenNoConflictsNotEnoughWorkshops34() {
        $this->_calculateResults(3, 4, false, 0, true);
    }

    public function calculateResultsWhenNoConflictsNotEnoughWorkshops25() {
        $this->_calculateResults(2, 5, false, 0, true);
    }

    public function calculateResultsWhenNoConflictsNotEnoughWorkshops52() {
        $this->_calculateResults(5, 2, false, 0, true);
    }
*/

    /**
     * @test
     */
    public function calculateResultsConflictsWhenConflictsEnoughWorkshops34() {
        $this->_calculateResults(3, 4, true, 1, true);
    }

    /**
     * @test
     */
    public function calculateResultsConflictsWhenConflictsEnoughWorkshops25() {
        $this->_calculateResults(2, 5, true, 1, true);
    }

    /**
     * @test
     */
    public function calculateResultsConflictsWhenConflictsEnoughWorkshops52() {
        $this->_calculateResults(5, 2, true, 1, true);
    }

    /**
     * @test
     */
    public function calculateResultsConflictsWhenConflictsNotEnoughWorkshops34() {
        $this->_calculateResults(3, 4, false, 1, true);
    }
/*
    public function calculateResultsConflictsWhenConflictsNotEnoughWorkshops25() {
        $this->_calculateResults(2, 5, false, 1, true);
    }

    public function calculateResultsConflictsWhenConflictsNotEnoughWorkshops52() {
        $this->_calculateResults(5, 2, false, 1, true);
    }
 */

    /**
     * @test
     */
    public function calculateResultsWithMaxConflicts() {
        $this->_calculateResults(3, 4, true, 2, true);
        //$this->_calculateResults(2, 5, true, 2, true);
        //$this->_calculateResults(5, 2, true, 2, true);

        //$this->_calculateResults(3, 4, false, 2, true);
        //$this->_calculateResults(2, 5, false, 2, true);
        //$this->_calculateResults(5, 2, false, 2, true);
    }

    /**
     * @test
     */
    public function calculateResultsThrowsExceptionIfInvalidLocations() {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNumLocations',
                          'getNumRounds',
                          'validateLocations',
                          'validateRounds'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getNumLocations')
            ->willReturn(4);

        $dbo->expects($this->once())
            ->method('getNumRounds')
            ->willReturn(4);

        $dbo->expects($this->once())
            ->method('validateLocations')
            ->willReturn(false);

        $dbo->expects($this->any())
            ->method('validateRounds')
            ->willReturn(true);

        $results = new Results(null, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $results->calculateResults(null, null, null, null);
    }

    /**
     * @test
     */
    public function calculateResultsThrowsExceptionIfInvalidRounds() {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNumLocations',
                          'getNumRounds',
                          'validateLocations',
                          'validateRounds'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getNumLocations')
            ->willReturn(4);

        $dbo->expects($this->once())
            ->method('getNumRounds')
            ->willReturn(4);

        $dbo->expects($this->any())
            ->method('validateLocations')
            ->willReturn(true);

        $dbo->expects($this->once())
            ->method('validateRounds')
            ->willReturn(false);

        $results = new Results(null, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $results->calculateResults(null, null, null, null);
    }

    /**
     * @test
     */
    public function calculateResultsThrowsExceptionIfTooFewLocations() {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNumLocations',
                          'getNumRounds',
                          'validateLocations',
                          'validateRounds'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getNumLocations')
            ->willReturn(1);

        $dbo->expects($this->once())
            ->method('getNumRounds')
            ->willReturn(4);

        $dbo->expects($this->any())
            ->method('validateLocations')
            ->willReturn(true);

        $dbo->expects($this->any())
            ->method('validateRounds')
            ->willReturn(true);

        $results = new Results(null, null, $dbo, null);
        $this->expectException(RuntimeException::class);
        $results->calculateResults(null, null, null, null);
    }
}
