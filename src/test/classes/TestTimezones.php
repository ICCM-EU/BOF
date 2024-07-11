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

  /**
   * @covers \ICCM\BOF\Timezones::DateTimeSetTimezone
   * @test
   */
  public function testParseAndUtc()
  {
    $sut = ICCM\BOF\Timezones::ParseAndUtc("2000-01-01", "08:00:00", "America/New_York");
    $passes = $sut->format("Y-m-d H:i:s") == "2000-01-01 13:00:00"
        || $sut->format("Y-m-d H:i:s") == "2000-01-01 12:00:00";
    $this->assertTrue($passes);
  }

  /**
   * @covers \ICCM\BOF\Timezones::DateTimeSetTimezone
   * @test
   */
  public function testTimezonesDateTimeSetTimezone()
  {
    $date = new DateTime("2000-01-01 08:00:00", new DateTimeZone("America/New_York"));
    $sut = ICCM\BOF\Timezones::DateTimeSetTimezone($date, "UTC");
    $passes = $sut->format(DateTimeInterface::ISO8601) == "2000-01-01T13:00:00+0000"
        || $sut->format(DateTimeInterface::ISO8601) == "2000-01-01T12:00:00+0000";
    $this->assertTrue($passes);
  }

  /**
   * @covers \ICCM\BOF\Timezones::ParseDateTimeWithTimezone
   * @test
   */
  public function testTimezonesParseDateTimeWithTimezone()
  {
    $sut = ICCM\BOF\Timezones::ParseDateTimeWithTimezone(
        "2000-01-01"." "."08:00:00",
        "America/New_York"
    );
    $this->assertTrue($sut instanceof DateTime);
  }

}
