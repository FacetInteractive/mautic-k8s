<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\EventCollector\Accessor\Event;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;

class ConditionAccessorTest extends \PHPUnit_Framework_TestCase
{
    public function testEventNameIsReturned()
    {
        $accessor = new ConditionAccessor(['eventName' => 'test']);

        $this->assertEquals('test', $accessor->getEventName());
    }

    public function testExtraParamIsReturned()
    {
        $accessor = new ConditionAccessor(['eventName' => 'test', 'foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $accessor->getExtraProperties());
    }
}
