<?php

/**
 * Rss/Atom Bundle for Symfony.
 *
 *
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @copyright (c) 2013, Alexandre Debril
 */
namespace Debril\RssAtomBundle\Protocol\Parser;

use Debril\RssAtomBundle\Exception\ParserException;
use Debril\RssAtomBundle\Protocol\FeedInterface;
use Debril\RssAtomBundle\Protocol\ItemInInterface;
use Debril\RssAtomBundle\Protocol\Parser;
use SimpleXMLElement;

/**
 * Class AtomParser.
 */
class AtomParser extends Parser
{
    protected $mandatoryFields = array(
        'id',
        'updated',
        'title',
        'link',
        'entry',
    );

    /**
     *
     */
    public function __construct()
    {
        $this->setdateFormats(array(\DateTime::RFC3339));
    }

    /**
     * @param SimpleXMLElement $xmlBody
     *
     * @return bool
     */
    public function canHandle(SimpleXMLElement $xmlBody)
    {
        return 'feed' === $xmlBody->getName();
    }

    /**
     * @param SimpleXMLElement $xmlBody
     * @param FeedInterface    $feed
     * @param array            $filters
     *
     * @return FeedInterface
     *
     * @throws ParserException
     */
    protected function parseBody(SimpleXMLElement $xmlBody, FeedInterface $feed, array $filters)
    {
        $this->parseHeaders($xmlBody, $feed);

        $namespaces = $xmlBody->getNamespaces(true);

        foreach ($xmlBody->entry as $xmlElement) {
            $itemFormat = isset($itemFormat) ? $itemFormat : $this->guessDateFormat($xmlElement->updated);

            $item = $this->newItem();
            $item->setTitle($xmlElement->title)
                    ->setPublicId($xmlElement->id)
                    ->setSummary($this->parseContent($xmlElement->summary))
                    ->setDescription($this->parseContent($xmlElement->content))
                    ->setUpdated(static::convertToDateTime($xmlElement->updated, $itemFormat));

            $item->setLink($this->detectLink($xmlElement, 'alternate'));

            if ($xmlElement->author) {
                $item->setAuthor($xmlElement->author->name);
            }

            $item->setAdditional($this->getAdditionalNamespacesElements($xmlElement, $namespaces));
            $this->handleEnclosure($xmlElement, $item);

            $this->parseCategories($xmlElement, $item);

            $this->addValidItem($feed, $item, $filters);
        }

        return $feed;
    }

    /**
     * @param SimpleXMLElement $xmlBody
     * @param FeedInterface    $feed
     *
     * @throws ParserException
     */
    protected function parseHeaders(SimpleXMLElement $xmlBody, FeedInterface $feed)
    {
        $feed->setPublicId($xmlBody->id);

        $feed->setLink(current($this->detectLink($xmlBody, 'self')));
        $feed->setTitle($xmlBody->title);
        $feed->setDescription($xmlBody->subtitle);

        $format = $this->guessDateFormat($xmlBody->updated);
        $updated = static::convertToDateTime($xmlBody->updated, $format);
        $feed->setLastModified($updated);
    }

    /**
     * @param SimpleXMLElement $xmlElement
     * @param string           $type
     */
    protected function detectLink(SimpleXMLElement $xmlElement, $type)
    {
        foreach ($xmlElement->link as $xmlLink) {
            if ((string) $xmlLink['rel'] === $type) {
                return $xmlLink['href'];
            }
        }

        // return the first if the desired link does not exist
        return $xmlElement->link[0]['href'];
    }

    protected function parseContent(SimpleXMLElement $content)
    {
        if ($content && 0 < $content->children()->count()) {
            $out = '';
            foreach ($content->children() as $child) {
                $out .= $child->asXML();
            }

            return $out;
        }

        return $content;
    }

    /**
     * Handles enclosures if any.
     *
     * @param SimpleXMLElement $element
     * @param ItemInInterface  $item
     *
     * @return $this
     */
    protected function handleEnclosure(SimpleXMLElement $element, ItemInInterface $item)
    {
        foreach ($element->link as $link) {
            if (strcasecmp($this->getAttributeValue($link, 'rel'), 'enclosure') === 0) {
                $media = $this->createMedia($link);
                $item->addMedia($media);
            }
        }

        return $this;
    }

    /**
     * Parse category elements.
     * We may have more than one.
     *
     * @param SimpleXMLElement $element
     * @param ItemInInterface $item
     */
    protected function parseCategories(SimpleXMLElement $element, ItemInInterface $item)
    {
        foreach ($element->category as $xmlCategory) {
            $category = new Category();
            $category->setName($this->getAttributeValue($xmlCategory, 'term'));

            $item->addCategory($category);
        }
    }
}
