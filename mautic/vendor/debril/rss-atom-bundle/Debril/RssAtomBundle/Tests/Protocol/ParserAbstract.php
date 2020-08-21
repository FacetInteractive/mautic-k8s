<?php

namespace Debril\RssAtomBundle\Tests\Protocol;

use Debril\RssAtomBundle\Protocol\Parser;

/**
 * Class ParserAbstract.
 */
class ParserAbstract extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    protected $object;

    /**
     * @dataProvider getDefaultFormats
     * @covers Debril\RssAtomBundle\Protocol\Parser::guessDateFormat
     */
    public function testGuessDateFormat($default)
    {
        $this->object->setdateFormats($default);

        $date = 'Mon, 06 Sep 2009 16:45:00 GMT';
        $format = $this->object->guessDateFormat($date);

        $this->assertEquals(\DateTime::RSS, $format);
    }

    /**
     * @return array
     */
    public function getDefaultFormats()
    {
        return
            array(
                array(
                    array(
                        \DateTime::RFC3339,
                        \DateTime::RSS,
                    ),
                ),
            );
    }
}
