<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Translation Strings class.
 *
 * @link   http://docs.transifex.com/developer/api/translation_strings
 * @since  1.0
 */
class Translationstrings extends TransifexObject
{
	/**
	 * Method to get pseudolocalization strings on a specified resource.
	 *
	 * @param   string  $project   The slug for the project to pull from.
	 * @param   string  $resource  The slug for the resource to pull from.
	 *
	 * @return  \stdClass  The resource's pseudolocalization.
	 *
	 * @since   1.0
	 */
	public function getPseudolocalizationStrings($project, $resource)
	{
		// Build the request path
		$path = '/project/' . $project . '/resource/' . $resource . '/pseudo/?pseudo_type=MIXED';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get the translation strings on a specified resource.
	 *
	 * @param   string   $project   The slug for the project to pull from.
	 * @param   string   $resource  The slug for the resource to pull from.
	 * @param   string   $lang      The language to return the translation for.
	 * @param   boolean  $details   Flag to retrieve additional details on the strings
	 * @param   array    $options   An array of additional options for the request
	 *
	 * @return  \stdClass  The resource's translation in the specified language.
	 *
	 * @since   1.0
	 */
	public function getStrings($project, $resource, $lang, $details = false, $options = array())
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/translation/' . $lang . '/strings/';

		// Flag for when the query string starts
		$firstQuerySet = false;

		if ($details)
		{
			$path         .= '?details';
			$firstQuerySet = true;
		}

		if (isset($options['key']))
		{
			if ($firstQuerySet)
			{
				$path .= '\&key=' . $options['key'];
			}
			else
			{
				$path         .= '?key=' . $options['key'];
				$firstQuerySet = true;
			}
		}

		if (isset($options['context']))
		{
			if ($firstQuerySet)
			{
				$path .= '\&context=' . $options['context'];
			}
			else
			{
				$path .= '?context=' . $options['context'];
			}
		}

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}
}
