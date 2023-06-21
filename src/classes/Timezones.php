<?php

namespace ICCM\BOF;

use DateTime;
use DateTimeZone;

class Timezones
{
  public static function List()
  {
    static $timezones = null;

    if ($timezones === null) {
      $timezones = [];
      $offsets = [];
      $now = new DateTime('now', new DateTimeZone('UTC'));

      foreach (DateTimeZone::listIdentifiers() as $timezone) {
        $now->setTimezone(new DateTimeZone($timezone));
        $offsets[] = $offset = $now->getOffset();
        $timezones[$timezone] = Timezones::_formatTimezoneLabel($timezone, $offset);
      }

      array_multisort($offsets, $timezones);
    }

    return $timezones;
  }

  static function _formatTimezoneLabel($timezone, $offset)
  {
    return '(' . Timezones::_formatGMTOffset($offset) . ') ' . Timezones::_formatTimezoneName($timezone);
  }

  static function _formatGMTOffset($offset)
  {
    $hours = intval($offset / 3600);
    $minutes = abs(intval($offset % 3600 / 60));
    return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
  }

  static function _formatTimezoneName($name)
  {
    $name = str_replace('/', ', ', $name);
    $name = str_replace('_', ' ', $name);
    $name = str_replace('St ', 'St. ', $name);
    return $name;
  }
}
