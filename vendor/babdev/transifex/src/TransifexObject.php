<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

use Joomla\Http\Response;

/**
 * Transifex API object class.
 *
 * @since  1.0
 */
abstract class TransifexObject
{
	/**
	 * Options for the Transifex object.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $options;

	/**
	 * The HTTP client object to use in sending HTTP requests.
	 *
	 * @var    Http
	 * @since  1.0
	 */
	protected $client;

	/**
	 * Constructor.
	 *
	 * @param   array  $options  Transifex options array.
	 * @param   Http   $client   The HTTP client object.
	 *
	 * @since   1.0
	 */
	public function __construct($options = array(), Http $client = null)
	{
		$this->options = $options;
		$this->client  = isset($client) ? $client : new Http($this->options);
	}

	/**
	 * Method to build and return a full request URL for the request.  This method will
	 * add appropriate pagination details if necessary and also prepend the API url
	 * to have a complete URL for the request.
	 *
	 * @param   string  $path  URL to inflect
	 *
	 * @return  string  The request URL.
	 *
	 * @since   1.0
	 * @deprecated  2.0  Deprecated without replacement
	 */
	protected function fetchUrl($path)
	{
		// Ensure the API URL is set before moving on
		$base = isset($this->options['api.url']) ? $this->options['api.url'] : '';

		return $base . $path;
	}

	/**
	 * Method to update an API endpoint with resource content
	 *
	 * @param   string  $path     API path
	 * @param   string  $content  The content of the resource.  This can either be a string of data or a file path.
	 * @param   string  $type     The type of content in the $content variable.  This should be either string or file.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 * @throws  \InvalidArgumentException
	 */
	protected function updateResource($path, $content, $type)
	{
		// Verify the content type is allowed
		if (!in_array($type, array('string', 'file')))
		{
			throw new \InvalidArgumentException('The content type must be specified as file or string.');
		}

		$data = array(
			'content' => ($type == 'string') ? $content : file_get_contents($content)
		);

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
	 * Process the response and decode it.
	 *
	 * @param   Response  $response      The response.
	 * @param   integer   $expectedCode  The expected "good" code.
	 *
	 * @return  \stdClass
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 * @deprecated  2.0  Deprecated without replacement
	 */
	protected function processResponse(Response $response, $expectedCode = 200)
	{
		// Validate the response code.
		if ($response->code != $expectedCode)
		{
			// Decode the error response and throw an exception.
			$error = json_decode($response->body);

			// Check if the error message is set; send a generic one if not
			$message = isset($error->message) ? $error->message : $response->body;

			throw new \DomainException($message, $response->code);
		}

		return json_decode($response->body);
	}
}
