<?php

/**
 * RssAtomBundle.
 *
 *
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @copyright (c) 2013, Alexandre Debril
 *
 * creation date : 31 mars 2013
 */
namespace Debril\RssAtomBundle\Provider;

use Debril\RssAtomBundle\Protocol\Parser\FeedContent;
use Debril\RssAtomBundle\Protocol\Parser\Item;
use Debril\RssAtomBundle\Exception\FeedException\FeedNotFoundException;

/**
 * Class MockProvider.
 */
class MockProvider implements FeedContentProviderInterface
{
    /**
     * @param array $options
     *
     * @return FeedContent
     *
     * @throws FeedNotFoundException
     */
    public function getFeedContent(array $options)
    {
        $content = new FeedContent();

        $id = array_key_exists('id', $options) ? $options['id'] : null;

        if ($id === 'not-found') {
            throw new FeedNotFoundException();
        }

        $content->setPublicId($id);

        $content->setTitle('thank you for using RssAtomBundle');
        $content->setDescription('this is the mock FeedContent');
        $content->setLink('https://raw.github.com/alexdebril/rss-atom-bundle/');
        $content->setLastModified(new \DateTime());

        $item = new Item();

        $item->setPublicId('1');
        $item->setLink('https://raw.github.com/alexdebril/rss-atom-bundle/somelink');
        $item->setTitle('This is an item');
        $item->setSummary('this stream was generated using the MockProvider class');
        $item->setDescription('lorem ipsum ....');
        $item->setUpdated(new \DateTime());
        $item->setComment('http://example.com/comments');

        $item->setAuthor('Contributor');

        $content->addItem($item);

        return $content;
    }
}
