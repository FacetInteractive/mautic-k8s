<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Languages class.
 *
 * @link   http://docs.transifex.com/developer/api/languages
 * @since  1.0
 */
class Languages extends TransifexObject
{
	/**
	 * Method to create a language for a project.
	 *
	 * @param   string   $slug                 The slug for the project
	 * @param   string   $langCode             The language code for the new language
	 * @param   array    $coordinators         An array of coordinators for the language
	 * @param   array    $options              Optional additional params to send with the request
	 * @param   boolean  $skipInvalidUsername  If true, the API call does not fail and instead will return a list of invalid usernames
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function createLanguage($slug, $langCode, array $coordinators, array $options = array(), $skipInvalidUsername = false)
	{
		// Make sure the $coordinators array is not empty
		if (count($coordinators) < 1)
		{
			throw new \InvalidArgumentException('The coordinators array must contain at least one username.');
		}

		// Build the request path.
		$path = '/project/' . $slug . '/languages/';

		// Check if invalid usernames should be skipped
		if ($skipInvalidUsername)
		{
			$path .= '?skip_invalid_username';
		}

		// Build the required request data.
		$data = array(
			'language_code' => $langCode,
			'coordinators' => $coordinators
		);

		// Valid options to check
		$validOptions = array('translators', 'reviewers', 'list');

		// Loop through the valid options and if we have them, add them to the request data
		foreach ($validOptions as $option)
		{
			if (isset($options[$option]))
			{
				$data[$option] = $options[$option];
			}
		}

		// Send the request.
		return $this->processResponse(
			$this->client->post(
				$this->fetchUrl($path),
				json_encode($data),
				array('Content-Type' => 'application/json')
			),
			201
		);
	}

	/**
	 * Method to delete a language within a project.
	 *
	 * @param   string  $project   The project to retrieve details for
	 * @param   string  $langCode  The language code to retrieve details for
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function deleteLanguage($project, $langCode)
	{
		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/';

		// Send the request.
		return $this->processResponse($this->client->delete($this->fetchUrl($path)), 204);
	}

	/**
	 * Method to get the coordinators for a language team in a project
	 *
	 * @param   string  $project   The project to retrieve details for
	 * @param   string  $langCode  The language code to retrieve details for
	 *
	 * @return  \stdClass  The coordinator information from the API.
	 *
	 * @since   1.0
	 */
	public function getCoordinators($project, $langCode)
	{
		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/coordinators/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get information about a given language in a project.
	 *
	 * @param   string  $project   The project to retrieve details for
	 * @param   string  $langCode  The language code to retrieve details for
	 *
	 * @return  \stdClass  The language details for the specified project from the API.
	 *
	 * @since   1.0
	 */
	public function getLanguage($project, $langCode)
	{
		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get a list of languages for a specified project.
	 *
	 * @param   string  $project  The project to retrieve details for
	 *
	 * @return  \stdClass  The language data for the project.
	 *
	 * @since   1.0
	 */
	public function getLanguages($project)
	{
		// Build the request path.
		$path = '/project/' . $project . '/languages/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get the reviewers for a language team in a project
	 *
	 * @param   string  $project   The project to retrieve details for
	 * @param   string  $langCode  The language code to retrieve details for
	 *
	 * @return  \stdClass  The reviewer information from the API.
	 *
	 * @since   1.0
	 */
	public function getReviewers($project, $langCode)
	{
		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/reviewers/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get the translators for a language team in a project
	 *
	 * @param   string  $project   The project to retrieve details for
	 * @param   string  $langCode  The language code to retrieve details for
	 *
	 * @return  \stdClass  The translators information from the API.
	 *
	 * @since   1.0
	 */
	public function getTranslators($project, $langCode)
	{
		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/translators/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to update the coordinators for a language team in a project
	 *
	 * @param   string   $project              The project to retrieve details for
	 * @param   string   $langCode             The language code to retrieve details for
	 * @param   array    $coordinators         An array of coordinators for the language
	 * @param   boolean  $skipInvalidUsername  If true, the API call does not fail and instead will return a list of invalid usernames
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function updateCoordinators($project, $langCode, array $coordinators, $skipInvalidUsername = false)
	{
		return $this->updateTeam($project, $langCode, $coordinators, $skipInvalidUsername, 'coordinators');
	}

	/**
	 * Method to update a language within a project.
	 *
	 * @param   string  $slug          The slug for the project
	 * @param   string  $langCode      The language code for the new language
	 * @param   array   $coordinators  An array of coordinators for the language
	 * @param   array   $options       Optional additional params to send with the request
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function updateLanguage($slug, $langCode, array $coordinators, array $options = array())
	{
		// Make sure the $coordinators array is not empty
		if (count($coordinators) < 1)
		{
			throw new \InvalidArgumentException('The coordinators array must contain at least one username.');
		}

		// Build the request path.
		$path = '/project/' . $slug . '/language/' . $langCode . '/';

		// Build the required request data.
		$data = array('coordinators' => $coordinators);

		// Set the translators if present
		if (isset($options['translators']))
		{
			$data['translators'] = $options['translators'];
		}

		// Set the reviewers if present
		if (isset($options['reviewers']))
		{
			$data['reviewers'] = $options['reviewers'];
		}

		// Send the request.
		return $this->processResponse(
			$this->client->put(
				$this->fetchUrl($path),
				json_encode($data),
				array('Content-Type' => 'application/json')
			),
			200
		);
	}

	/**
	 * Method to update the reviewers for a language team in a project
	 *
	 * @param   string   $project              The project to retrieve details for
	 * @param   string   $langCode             The language code to retrieve details for
	 * @param   array    $reviewers            An array of reviewers for the language
	 * @param   boolean  $skipInvalidUsername  If true, the API call does not fail and instead will return a list of invalid usernames
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function updateReviewers($project, $langCode, array $reviewers, $skipInvalidUsername = false)
	{
		return $this->updateTeam($project, $langCode, $reviewers, $skipInvalidUsername, 'reviewers');
	}

	/**
	 * Base method to update a given language team in a project
	 *
	 * @param   string   $project              The project to retrieve details for
	 * @param   string   $langCode             The language code to retrieve details for
	 * @param   array    $members              An array of the team members for the language
	 * @param   boolean  $skipInvalidUsername  If true, the API call does not fail and instead will return a list of invalid usernames
	 * @param   string   $team                 The team to update
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	protected function updateTeam($project, $langCode, array $members, $skipInvalidUsername, $team)
	{
		// Make sure the $members array is not empty
		if (count($members) < 1)
		{
			throw new \InvalidArgumentException('The ' . $team . ' array must contain at least one username.');
		}

		// Build the request path.
		$path = '/project/' . $project . '/language/' . $langCode . '/' . $team . '/';

		// Check if invalid usernames should be skipped
		if ($skipInvalidUsername)
		{
			$path .= '?skip_invalid_username';
		}

		// Send the request.
		return $this->processResponse(
			$this->client->put(
				$this->fetchUrl($path),
				json_encode($members),
				array('Content-Type' => 'application/json')
			),
			200
		);
	}

	/**
	 * Method to update the translators for a language team in a project
	 *
	 * @param   string   $project              The project to retrieve details for
	 * @param   string   $langCode             The language code to retrieve details for
	 * @param   array    $translators          An array of translators for the language
	 * @param   boolean  $skipInvalidUsername  If true, the API call does not fail and instead will return a list of invalid usernames
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function updateTranslators($project, $langCode, array $translators, $skipInvalidUsername = false)
	{
		return $this->updateTeam($project, $langCode, $translators, $skipInvalidUsername, 'translators');
	}
}
