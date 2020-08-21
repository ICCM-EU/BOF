<?php

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Projector;
use ICCM\BOF\DBO;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \ICCM\BOF\Projector
 */
class TestProjector extends TestCase
{
    /**
     * @test
     */
    public function showProjectorViewReturnsEmptyDataForBadStage() {
        $config = [
            'bofs' => [],
            'stage' => 'blah',
            'locked' => false,
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStage'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getStage')
            ->willReturn('blah');

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
            ->with($response, 'proj_layout.html', $config)
            ->willReturn(4);

        $projector = new Projector($view, null, $dbo);
        $this->assertEquals(4, $projector->showProjectorView(null, $response, null));
    }

    /**
     * @test
     */
    public function showProjectorViewReturnsNoVoteAndLeaderForNominatingStage() {
        $bofs = [
            (object) [
                'name' => 'Topic5',
                'id' => 105,
            ],
            (object) [
                'name' => 'Topic4',
                'id' => 104,
            ],
            (object) [
                'name' => 'Topic3',
                'id' => 103,
            ],
            (object) [
                'name' => 'Topic2',
                'id' => 102,
            ],
            (object) [
                'name' => 'Topic2',
                'id' => 101,
            ],
            (object) [
                'name' => 'Prep Team',
                'id' => 1,
            ]
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStage', 'getWorkshops'])
            ->getMock();

        $config = [
            'bofs' => $bofs,
            'stage' => 'nominating',
            'locked' => false,
        ];

        $dbo->expects($this->once())
            ->method('getStage')
            ->willReturn('nominating');

        $dbo->expects($this->once())
            ->method('getWorkshops')
            ->willReturn($bofs);

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
            ->with($response, 'proj_layout.html', $config)
            ->willReturn(4);

        $projector = new Projector($view, null, $dbo);
        $this->assertEquals(4, $projector->showProjectorView(null, $response, null));
    }

    /**
     * @test
     */
    public function showProjectorViewShowsFinishedStageForFinished() {
        $locations = [
            0 => 'Room A',
            1 => 'Room B'
        ];

        $rounds = [
            0 => 'Round 1',
            1 => 'Round 2'
        ];

        $bofs = [
            0 => [
                'name' => 'Round 1',
                'rooms' => [
                    0 => [
                        'name' => 'Room A',
                        'topic' => 'Topic1',
                        'description' => 'Topic1',
                        'votes' => 19.5,
                        'facilitators' => 'user1'
                    ],
                    1 => [
                        'name' => 'Room B',
                        'topic' => 'Topic5',
                        'description' => 'Topic5',
                        'votes' => 15.25,
                        'facilitators' => 'user5'
                    ]
                ],
            ],
            1 => [
                'name' => 'Round 2',
                'rooms' => [
                    0 => [
                        'name' => 'Room A',
                        'topic' => 'Topic4',
                        'description' => 'Topic4',
                        'votes' => 16.75,
                        'facilitators' => 'user4'
                    ],
                    1 => [
                        'name' => 'Room B',
                        'topic' => 'Prep Team',
                        'description' => 'Prep Team',
                        'votes' => 2.5,
                        'facilitators' => 'user1, user2'
                    ]
                ],
            ]
        ];

        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookedWorkshop', 'getCurrentVotes', 'getFacilitators', 'getLocationNames', 'getRoundNames', 'getStage'])
            ->getMock();

        $dbo->expects($this->exactly(4))
            ->method('getBookedWorkshop')
            ->withConsecutive([0, 0], [0, 1], [1, 0], [1, 1])
            ->will($this->onConsecutiveCalls(
                (object) [
                    'name' => 'Topic1',
                    'description' => 'Topic1',
                    'votes' => 19.5,
                    'id' => 101
                ],
                (object) [
                    'name' => 'Topic5',
                    'description' => 'Topic5',
                    'votes' => 15.25,
                    'id' => 105
                ],
                (object) [
                    'name' => 'Topic4',
                    'description' => 'Topic4',
                    'votes' => 16.75,
                    'id' => 104
                ],
                (object) [
                    'name' => 'Prep Team',
                    'description' => 'Prep Team',
                    'votes' => 2.5,
                    'id' => 1
                ]));

        $dbo->expects($this->exactly(4))
            ->method('getFacilitators')
            ->withConsecutive([101], [105], [104], [1])
            ->will($this->onConsecutiveCalls('user1', 'user5', 'user4', 'user1, user2'));

        $dbo->expects($this->once())
            ->method('getLocationNames')
            ->willReturn($locations);

        $dbo->expects($this->once())
            ->method('getRoundNames')
            ->willReturn($rounds);

        $dbo->expects($this->once())
            ->method('getStage')
            ->willReturn('finished');

        $config = [
            'bofs' => $bofs,
            'stage' => 'finished',
            'locked' => false
        ];

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
            ->with($response, 'proj_layout.html', $config)
            ->willReturn(4);

        $projector = new Projector($view, null, $dbo);
        $this->assertEquals(4, $projector->showProjectorView(null, $response, null));
    }

    private function _showProjectorViewShowsVotingStageForStage($stage) {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentVotes', 'getStage'])
            ->getMock();

        $bofs = [
            (object) [
                'name' => 'Topic1',
                'id' => 101,
                'votes' => 19.5,
                'leader' => 'user1'
            ],
            (object) [
                'name' => 'Topic4',
                'id' => 104,
                'votes' => 16.75,
                'leader' => 'user4'
            ],
            (object) [
                'name' => 'Topic5',
                'id' => 105,
                'votes' => 15.25,
                'leader' => 'user5'
            ],
            (object) [
                'name' => 'Topic3',
                'id' => 103,
                'votes' => 15.25,
                'leader' => 'user3'
            ],
            (object) [
                'name' => 'Topic2',
                'id' => 102,
                'votes' => 10.5,
                'leader' => 'user2'
            ],
            (object) [
                'name' => 'Prep Team',
                'id' => 1,
                'votes' => 2.5,
                'leader' => 'user1, user2'
            ]
        ];
        $currentVotes = [
            (object) [
                'name' => 'Topic1',
                'id' => 101,
                'votes' => 19.5,
                'leader' => 'user1'
            ],
            (object) [
                'name' => 'Topic4',
                'id' => 104,
                'votes' => 16.75,
                'leader' => 'user4'
            ],
            (object) [
                'name' => 'Topic5',
                'id' => 105,
                'votes' => 15.25,
                'leader' => 'user5'
            ],
            (object) [
                'name' => 'Topic3',
                'id' => 103,
                'votes' => 15.25,
                'leader' => 'user3'
            ],
            (object) [
                'name' => 'Topic2',
                'id' => 102,
                'votes' => 10.5,
                'leader' => 'user2'
            ],
            (object) [
                'name' => 'Prep Team',
                'id' => 1,
                'votes' => 2.5,
                'leader' => 'user1, user2'
            ]
        ];

        $config = [
            'bofs' => $bofs,
            'stage' => $stage,
            'locked' => $stage=='locked'
        ];

        $dbo->expects($this->once())
            ->method('getCurrentVotes')
            ->willReturn($currentVotes);

        $dbo->expects($this->once())
            ->method('getStage')
            ->willReturn($stage);

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
            ->with($response, 'proj_layout.html', $config)
            ->willReturn(4);

        $projector = new Projector($view, null, $dbo);
        $this->assertEquals(4, $projector->showProjectorView(null, $response, null));
    }

    /**
     * @test
     */
    public function showProjectorViewShowsVotingStageForLocked() {
        $this->_showProjectorViewShowsVotingStageForStage('locked');
    }

    /**
     * @test
     */
    public function showProjectorViewShowsVotingStageForVoting() {
        $this->_showProjectorViewShowsVotingStageForStage('voting');
    }


}
