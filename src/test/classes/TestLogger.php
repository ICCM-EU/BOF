<?php

require_once __DIR__ . '/../../classes/DBO.php';
require_once __DIR__ . '/../../classes/Logger.php';

use PHPUnit\Framework\TestCase;
use ICCM\BOF\DBO;
use ICCM\BOF\Logger;

/**
 * @covers ICCM\BOF\Logger
 */
class TestLogger extends TestCase
{
    /**
     * @covers ICCM\BOF\Logger::clearLog
     * @test
     */
    public function clearLogReturnsEmpty() {
        $logger = new Logger();
        $logger->log('Test message');
        $logger->clearLog();
        $this->assertEmpty($logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::getLog
     * @test
     */
    public function getLogReturnsEmptyIfNothingLogged() {
        $logger = new Logger();
        $this->assertEmpty($logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::getLog
     * @test
     */
    public function getLogReturnsLoggedMessage() {
        $message = 'Test message';
        $expected = $message."\n";
        $logger = new Logger();
        $logger->log($message);
        $this->assertEquals($expected, $logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::getLog
     * @test
     */
    public function getLogReturnsEmptyAfterGetLog() {
        $message = 'Test message';
        $expected = $message."\n";
        $logger = new Logger();
        $logger->log($message);
        $logger->getLog();
        $this->assertEmpty($logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::log
     * @test
     */
    public function logAppendsWithNewlines() {
        $message1 = 'Test message';
        $message2 = 'Test message';
        $expected = $message1."\n".$message2."\n";
        $logger = new Logger();
        $logger->log($message1);
        $logger->log($message2);
        $this->assertEquals($expected, $logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::logBookWorkshop
     * @test
     */
    public function logBookWorkshop() {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLocationNames', 'getRoundNames'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getLocationNames')
            ->willReturn(['one', 'two', 'three']);

        $dbo->expects($this->once())
            ->method('getRoundNames')
            ->willReturn(['one', 'two', 'three']);

        $reason = "My reason";

        $logger = new Logger();

        $expected = "Putting workshop '1' in round 'one' at location 'three'. Reason: {$reason}\n";
        $logger->logBookWorkshop($dbo, 1, 0, 2, $reason);
        $this->assertEquals($expected, $logger->getLog());

        // Calling logBookWorkshop() a second time should not cause
        // $dbo->getLocationNames() or $dbo->getRoundsNames() to be called more
        // than once.

        $expected = "Putting workshop '2' in round 'two' at location 'one'. Reason: {$reason}\n";
        $logger->logBookWorkshop($dbo, 2, 1, 0, $reason);
        $this->assertEquals($expected, $logger->getLog());
    }

    /**
     * @covers ICCM\BOF\Logger::logSwitchedWorkshops
     * @test
     */
    public function logSwitchedWorkshops() {
        $dbo = $this->getMockBuilder(DBO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLocationNames', 'getRoundNames'])
            ->getMock();

        $dbo->expects($this->once())
            ->method('getLocationNames')
            ->willReturn(['one', 'two', 'three']);

        $dbo->expects($this->once())
            ->method('getRoundNames')
            ->willReturn(['one', 'two', 'three']);

        $workshop1 = 1;
        $round1 = 0;
        $location1 = 2;

        $workshop2 = 2;
        $round2 = 1;
        $location2 = 0;

        $logger = new Logger();

        $expected = "Switched workshops!  '{$workshop1}' is now in round 'one' at location 'three'. '{$workshop2}' is now in round 'two' at location 'one'.\n";
        $logger->logSwitchedWorkshops($dbo, $workshop1, $round1, $location1, $workshop2, $round2, $location2);

        $this->assertEquals($expected, $logger->getLog());

        // Calling logSwitchedWorkshops() a second time should not cause
        // $dbo->getLocationNames() or $dbo->getRoundsNames() to be called more
        // than once.
        $expected = "Switched workshops!  '{$workshop1}' is now in round 'two' at location 'one'. '{$workshop2}' is now in round 'one' at location 'three'.\n";
        $logger->logSwitchedWorkshops($dbo, $workshop1, $round2, $location2, $workshop2, $round1, $location1);
        $this->assertEquals($expected, $logger->getLog());
    }
}
