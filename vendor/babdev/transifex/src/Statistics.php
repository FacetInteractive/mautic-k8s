<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Statistics class.
 *
 * @link   http://docs.transifex.com/developer/api/statistics
 * @since  1.0
 */
class Statistics extends TransifexObject
{
	/**
	 * Method to get statistics on a specified resource.
	 *
	 * @param   string  $project   The slug for the project to pull from.
	 * @param   string  $resource  The slug for the resource to pull from.
	 * @param   string  $lang      An optional language code to return data only for a specified language.
	 *
	 * @return  \stdClass  The resource's statistics.
	 *
	 * @since   1.0
	 */
	public function getStatistics($project, $resource, $lang = null)
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/stats/' . $lang;

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}
}
