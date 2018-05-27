<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DateTimeHelper test.
 */
class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox The guessTimezoneFromOffset returns correct values
     *
     * @covers \Mautic\CoreBundle\Helper\DateTimeHelper::guessTimezoneFromOffset
     */
    public function testGuessTimezoneFromOffset()
    {
        $helper   = new DateTimeHelper();
        $timezone = $helper->guessTimezoneFromOffset();
        $this->assertEquals($timezone, 'Europe/London');
        $timezone = $helper->guessTimezoneFromOffset(3600);
        $this->assertEquals($timezone, 'Europe/Paris');
        $timezone = $helper->guessTimezoneFromOffset(-2 * 3600);
        $this->assertEquals($timezone, 'America/Goose_Bay'); // Is it really in timezone -2
        $timezone = $helper->guessTimezoneFromOffset(-5 * 3600);
        $this->assertEquals($timezone, 'America/New_York');
    }

    public function testBuildIntervalWithBadUnit()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $helper = new DateTimeHelper();
        $helper->buildInterval(4, 'j');
    }

    public function testBuildIntervalWithRightUnits()
    {
        $helper   = new DateTimeHelper();
        $interval = $helper->buildInterval(4, 'Y');
        $this->assertEquals(new \DateInterval('P4Y'), $interval);
        $interval = $helper->buildInterval(4, 'M');
        $this->assertEquals(new \DateInterval('P4M'), $interval);
        $interval = $helper->buildInterval(4, 'I');
        $this->assertEquals(new \DateInterval('PT4M'), $interval);
        $interval = $helper->buildInterval(4, 'S');
        $this->assertEquals(new \DateInterval('PT4S'), $interval);
    }
}
