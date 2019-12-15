<?php

/**
 * Rss/Atom Bundle for Symfony.
 *
 *
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @copyright (c) 2013, Alexandre Debril
 */
namespace Debril\RssAtomBundle\Protocol;

/**
 * Interface FeedInInterface.
 *
 * interface used when reading an external feed.
 */
interface FeedInInterface
{
    /**
     * Atom : feed.entry <feed><entry>
     * Rss  : rss.channel.item <rss><channel><item>.
     *
     * @param ItemInInterface $item
     */
    public function addItem(ItemInInterface $item);

    /**
     * Atom : feed.updated <feed><updated>
     * Rss  : rss.channel.lastBuildDate <rss><channel><lastBuildDate>
     *   or   rss.channel.pubDate <rss><channel><pubDate>.
     *
     * @param \DateTime $lastModified
     */
    public function setLastModified(\DateTime $lastModified);

    /**
     * Atom : feed.title <feed><title>
     * Rss  : rss.channel.title <rss><channel><title>.
     *
     * @param string $title
     */
    public function setTitle($title);

    /**
     * Atom : feed.subtitle <feed><subtitle>
     * Rss  : rss.channel.description <rss><channel><description>.
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Atom : feed.link <feed><link>
     * Rss  : rss.channel.link <rss><channel><link>.
     *
     * @param string $link
     */
    public function setLink($link);

    /**
     * Atom : feed.id <feed><id>
     * Rss  : rss.channel.id <rss><channel><id>.
     *
     * @param string $id
     */
    public function setPublicId($id);
}
