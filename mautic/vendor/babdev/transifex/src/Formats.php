<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Formats class.
 *
 * @link   http://docs.transifex.com/developer/api/formats
 * @since  1.0
 */
class Formats extends TransifexObject
{
	/**
	 * Method to get the supported formats.
	 *
	 * @return  \stdClass  The supported formats from the API.
	 *
	 * @since   1.0
	 */
	public function getFormats()
	{
		// Build the request path.
		$path = '/formats';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}
}
