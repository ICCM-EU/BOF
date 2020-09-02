<?php

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
	$realArgs = [ 0 => 'name', 1 => 'value', 2 => [
           'expires' => 45,
           'path' => 'path',
	   'domain' => 'domain',
           'secure' => false,
	   'httponly' => false,
	   'samesite' => 'Strict'
	]];
        if (PHP_VERSION_ID < 70300) {
	    $realArgs = [ 0 => 'name', 1 => 'value', 2 => 45, 3 => 'path; SameSite=Strict', 4 => 'domain', 5 => false, 6 => false ];
	}

        $this->assertEquals(1, $cookies->set($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]));

        $invocations = $spy->getInvocations();
        $this->assertEquals(1, count($invocations));
        $this->assertEquals($realArgs, $invocations[0]->getArguments());
        $spy->disable();

    }

}
