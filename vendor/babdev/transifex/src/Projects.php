<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Projects class.
 *
 * @link   http://docs.transifex.com/developer/api/projects
 * @since  1.0
 */
class Projects extends TransifexObject
{
	/**
	 * Checks that a license is an accepted value
	 *
	 * @param   string  $license  The license to check
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	private function checkLicense($license)
	{
		$accepted = array('proprietary', 'permissive_open_source', 'other_open_source');

		// Ensure the license option is an allowed value
		if (!in_array($license, $accepted))
		{
			throw new \InvalidArgumentException(
				sprintf(
					'The license %s is not valid, accepted license values are %s',
					$license,
					implode(', ', $accepted)
				)
			);
		}
	}

	/**
	 * Method to create a project.
	 *
	 * @param   string  $name            The name of the project
	 * @param   string  $slug            The slug for the project
	 * @param   string  $description     A description of the project
	 * @param   string  $sourceLanguage  The source language code for the project
	 * @param   array   $options         Optional additional params to send with the request
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function createProject($name, $slug, $description, $sourceLanguage, array $options = array())
	{
		// Build the request path.
		$path = '/projects/';

		// Build the request data.
		$data = array(
			'name'                 => $name,
			'slug'                 => $slug,
			'description'          => $description,
			'source_language_code' => $sourceLanguage
		);

		$customOptions = array(
			'long_description', 'private', 'homepage', 'trans_instructions', 'tags', 'maintainers', 'team', 'auto_join',
			'license', 'fill_up_resources', 'repository_url', 'organization', 'archived'
		);

		foreach ($customOptions as $option)
		{
			if (isset($options[$option]))
			{
				$data[$option] = $options[$option];
			}
		}

		// Check if the license is acceptable.
		if (isset($options['license']))
		{
			$this->checkLicense($options['license']);
			$data['license'] = $options['license'];
		}

		// Check mandatory fields.
		if (!isset($data['license']) || in_array($data['license'], array('permissive_open_source', 'other_open_source')))
		{
			if (!isset($data['repository_url']))
			{
				throw new \InvalidArgumentException(
					'If a project is denoted either as permissive_open_source or other_open_source, '
					. 'the field repository_url is mandatory and should contain a link to the public repository '
					. 'of the project to be created.'
				);
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
	 * Method to delete a project.
	 *
	 * @param   string  $slug  The slug for the resource.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function deleteProject($slug)
	{
		// Build the request path.
		$path = '/project/' . $slug;

		// Send the request.
		return $this->processResponse($this->client->delete($this->fetchUrl($path)), 204);
	}

	/**
	 * Method to get information about a project.
	 *
	 * @param   string   $project  The project to retrieve details for
	 * @param   boolean  $details  True to retrieve additional project details
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function getProject($project, $details = false)
	{
		// Build the request path.
		$path = '/project/' . $project . '/';

		if ($details)
		{
			$path .= '?details';
		}

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get a list of projects the user is part of.
	 *
	 * @return  \stdClass  The list of projects from the API.
	 *
	 * @since   1.0
	 */
	public function getProjects()
	{
		// Build the request path.
		$path = '/projects/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to update a project.
	 *
	 * @param   string  $slug     The slug for the project
	 * @param   array   $options  Optional additional params to send with the request
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function updateProject($slug, array $options = array())
	{
		// Build the request path.
		$path = '/project/' . $slug . '/';

		// Build the request data.
		$data = array();

		// Valid options to check
		$validOptions = array(
			'long_description', 'private', 'homepage', 'trans_instructions', 'tags', 'maintainers', 'team', 'auto_join',
			'license', 'fill_up_resources', 'repository_url', 'organization', 'archived'
		);

		// Loop through the valid options and if we have them, add them to the request data
		foreach ($validOptions as $option)
		{
			if (isset($options[$option]))
			{
				$data[$option] = $options[$option];
			}
		}

		// Set the license if present
		if (isset($options['license']))
		{
			$this->checkLicense($options['license']);
			$data['license'] = $options['license'];
		}

		// Make sure we actually have data to send
		if (empty($data))
		{
			throw new \RuntimeException('There is no data to send to Transifex.');
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
}
