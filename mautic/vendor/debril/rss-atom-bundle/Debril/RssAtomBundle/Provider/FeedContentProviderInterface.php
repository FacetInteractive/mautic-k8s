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

use Debril\RssAtomBundle\Exception\FeedException\FeedNotFoundException;
use Debril\RssAtomBundle\Protocol\FeedOutInterface;

/**
 * Interface FeedContentProviderInterface.
 */
interface FeedContentProviderInterface
{
    /**
     * @param array $options
     *
     * @throws FeedNotFoundException
     *
     * @return FeedOutInterface
     */
    public function getFeedContent(array $options);
}
