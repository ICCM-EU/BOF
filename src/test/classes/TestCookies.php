<?php

require_once __DIR__ . '/../../classes/Cookies.php';

use PHPUnit\Framework\TestCase;
use phpmock\spy\Spy;
use ICCM\BOF\Cookies;

class TestCookies extends TestCase
{
    /**
     * @covers \ICCM\BOF\Cookies::set
     * @test
     */
    public function setInvokesSetCookie() {
        $cookies = new Cookies();
        $spy = new Spy('ICCM\BOF', 'setcookie', function() { return 1; });
        $spy->enable();

        $args = [ 0 => 'name', 1 => 'value', 2 => 45, 3 => 'path', 4 => 'domain', 5 => false, 6 => false ];

        $this->assertEquals(1, $cookies->set($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]));

        $invocations = $spy->getInvocations();
        $this->assertEquals(1, count($invocations));
        $this->assertEquals($args, $invocations[0]->getArguments());
        $spy->disable();

    }

}
