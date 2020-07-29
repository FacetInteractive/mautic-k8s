<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Language Information class.
 *
 * @link   http://docs.transifex.com/developer/api/language_info
 * @since  1.0
 */
class Languageinfo extends TransifexObject
{
	/**
	 * Method to get data on the specified language.
	 *
	 * @param   string  $lang  The language code to retrieve
	 *
	 * @return  \stdClass  The language data.
	 *
	 * @since   1.0
	 */
	public function getLanguage($lang)
	{
		// Build the request path.
		$path = '/language/' . $lang . '/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get data on all supported API languages.
	 *
	 * @return  \stdClass  The language data.
	 *
	 * @since   1.0
	 */
	public function getLanguages()
	{
		// Build the request path.
		$path = '/languages/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}
}
