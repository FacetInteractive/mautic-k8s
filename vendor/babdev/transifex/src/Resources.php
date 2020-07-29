<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

/**
 * Transifex API Resources class.
 *
 * @link   http://docs.transifex.com/developer/api/resources
 * @since  1.0
 */
class Resources extends TransifexObject
{
	/**
	 * Method to create a resource.
	 *
	 * @param   string  $project   The slug for the project
	 * @param   string  $name      The name of the resource
	 * @param   string  $slug      The slug for the resource
	 * @param   string  $fileType  The file type of the resource
	 * @param   array   $options   Optional additional params to send with the request
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 */
	public function createResource($project, $name, $slug, $fileType, array $options = array())
	{
		// Build the request path.
		$path = '/project/' . $project . '/resources/';

		// Build the required request data.
		$data = array(
			'name'      => $name,
			'slug'      => $slug,
			'i18n_type' => $fileType
		);

		// Set the accept translations flag if provided
		if (isset($options['accept_translations']))
		{
			$data['accept_translations'] = $options['accept_translations'];
		}

		// Set the resource category if provided
		if (isset($options['category']))
		{
			$data['category'] = $options['category'];
		}

		// Set a resource priority if provided
		if (isset($options['priority']))
		{
			$data['priority'] = $options['priority'];
		}

		// Attach the resource data if provided as a string
		if (isset($options['content']))
		{
			$data['content'] = $options['content'];
		}
		elseif (isset($options['file']))
		{
			$data['content'] = file_get_contents($options['file']);
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
	 * Method to delete a resource within a project.
	 *
	 * @param   string  $project   The project the resource is part of
	 * @param   string  $resource  The resource slug within the project
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function deleteResource($project, $resource)
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource;

		// Send the request.
		return $this->processResponse($this->client->delete($this->fetchUrl($path)), 204);
	}

	/**
	 * Method to get information about a resource within a project.
	 *
	 * @param   string   $project   The project the resource is part of
	 * @param   string   $resource  The resource slug within the project
	 * @param   boolean  $details   True to retrieve additional project details
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function getResource($project, $resource, $details = false)
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/';

		if ($details)
		{
			$path .= '?details';
		}

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get the content of a resource within a project.
	 *
	 * @param   string  $project   The project the resource is part of
	 * @param   string  $resource  The resource slug within the project
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function getResourceContent($project, $resource)
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/content/';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to get information about a project's resources.
	 *
	 * @param   string  $project  The project to retrieve details for
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function getResources($project)
	{
		// Build the request path.
		$path = '/project/' . $project . '/resources';

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * Method to update the content of a resource within a project.
	 *
	 * @param   string  $project   The project the resource is part of
	 * @param   string  $resource  The resource slug within the project
	 * @param   string  $content   The content of the resource.  This can either be a string of data or a file path.
	 * @param   string  $type      The type of content in the $content variable.  This should be either string or file.
	 *
	 * @return  \stdClass  The project details from the API.
	 *
	 * @since   1.0
	 */
	public function updateResourceContent($project, $resource, $content, $type = 'string')
	{
		// Build the request path.
		$path = '/project/' . $project . '/resource/' . $resource . '/content/';

		return $this->updateResource($path, $content, $type);
	}
}
