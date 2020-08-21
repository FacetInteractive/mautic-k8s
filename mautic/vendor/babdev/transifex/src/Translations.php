<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Translations class.
 *
 * @link   http://docs.transifex.com/developer/api/translations
 * @since  1.0
 */
class Translations extends TransifexObject
{
	/**
	 * Method to get statistics on a specified resource.
	 *
	 * @param   string  $project   The slug for the project to pull from.
	 * @param   string  $resource  The slug for the resource to pull from.
	 * @param   string  $lang      The language to return the translation for.
	 * @param   string  $mode      The mode of the downloaded file.
	 *
	 * @return  \stdClass  The resource's translation in the specified language.
	 *
	 * @since   1.0
	 */
	public function getTranslation($project, $resource, $lang, $mode = '')
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/translation/' . $lang;

		if (!empty($mode))
		{
			$path .= '?mode=' . $mode . '&file';
		}

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to update the content of a resource within a project.
	 *
	 * @param   string  $project   The project the resource is part of
	 * @param   string  $resource  The resource slug within the project
	 * @param   string  $lang      The language to return the translation for.
	 * @param   string  $content   The content of the resource.  This can either be a string of data or a file path.
	 * @param   string  $type      The type of content in the $content variable.  This should be either string or file.
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function updateTranslation($project, $resource, $lang, $content, $type = 'string')
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/translation/' . $lang;

		return $this->updateResource($path, $content, $type);
	}
}
