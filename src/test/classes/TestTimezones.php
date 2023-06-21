<?php

use PHPUnit\Framework\TestCase;
use ICCM\BOF\Timezones;

class TestTimezones extends TestCase
{
  /**
   * @covers \ICCM\BOF\Timezones::List
   * @test
   */
  public function testTimezonesList()
  {
    $sut = ICCM\BOF\Timezones::List();
    $this->assertTrue(!empty($sut));
    $this->assertEquals('(GMT) UTC', $sut['UTC']);
    $this->assertEquals('(GMT-04:00) America, New York', $sut['America/New_York']);
  }
}
