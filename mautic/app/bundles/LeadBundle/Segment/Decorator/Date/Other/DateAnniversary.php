<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateAnniversary implements FilterDecoratorInterface
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    /**
     * @var DateOptionParameters
     */
    private $dateOptionParameters;

    /**
     * @param DateDecorator        $dateDecorator
     * @param DateOptionParameters $dateOptionParameters
     */
    public function __construct(DateDecorator $dateDecorator, DateOptionParameters $dateOptionParameters)
    {
        $this->dateDecorator        = $dateDecorator;
        $this->dateOptionParameters = $dateOptionParameters;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|string
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return 'like';
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     * @param array|string              $argument
     *
     * @return array|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return array|bool|float|null|string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $dateTimeHelper = $this->dateOptionParameters->getDefaultDate();

        return $dateTimeHelper->toLocalString('%-m-d');
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return bool|string
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|null|string
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
