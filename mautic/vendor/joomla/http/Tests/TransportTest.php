<?php
/**
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Http\Tests;

use Joomla\Uri\Uri;

/**
 * Test class for Joomla\Http\TransportInterface instances.
 *
 * @since  1.0
 */
class TransportTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var    array  Options for the Transport object.
	 * @since  1.0
	 */
	protected $options = array(
		'transport.curl'   => array(CURLOPT_SSL_VERIFYPEER => false),
		'transport.socket' => array('X-Joomla-Test: true'),
		'transport.stream' => array('ignore_errors' => true)
	);

	/**
	 * @var    string  The URL string for the HTTP stub.
	 * @since  1.0
	 */
	protected $stubUrl;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setUp()
	{
		parent::setUp();

		if (!defined('JTEST_HTTP_STUB') && getenv('JTEST_HTTP_STUB') == '')
		{
			$this->markTestSkipped('The Transport test stub has not been configured');
		}
		else
		{
			$this->stubUrl = defined('JTEST_HTTP_STUB') ? JTEST_HTTP_STUB : getenv('JTEST_HTTP_STUB');
		}
	}

	/**
	 * Data provider for the request test methods.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function transportProvider()
	{
		return array(
			'stream' => array('Joomla\\Http\\Transport\\Stream'),
			'curl' => array('Joomla\\Http\\Transport\\Curl'),
			'socket' => array('Joomla\\Http\\Transport\\Socket')
		);
	}

	/**
	 * Tests the transport constructor to ensure only arrays and ArrayAccess objects are allowed
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @dataProvider       transportProvider
	 * @expectedException  \InvalidArgumentException
	 */
	public function testConstructorWithBadDataObject($transportClass)
	{
		new $transportClass(new \stdClass);
	}

	/**
	 * Tests the request method with a get request
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestGet($transportClass)
	{
		$transport = new $transportClass($this->options);

		$response = $transport->request('get', new Uri($this->stubUrl));

		$body = json_decode($response->body);

		$this->assertThat(
			$response->code,
			$this->equalTo(200)
		);

		$this->assertThat(
			$body->method,
			$this->equalTo('GET')
		);
	}

	/**
	 * Tests the request method with a get request with a bad domain
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since           1.0
	 * @dataProvider       transportProvider
	 * @expectedException  RuntimeException
	 */
	public function testBadDomainRequestGet($transportClass)
	{
		$transport = new $transportClass($this->options);
		$response = $transport->request('get', new Uri('http://xommunity.joomla.org'));
	}

	/**
	 * Tests the request method with a get request for non existant url
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestGet404($transportClass)
	{
		$transport = new $transportClass($this->options);
		$response = $transport->request('get', new Uri($this->stubUrl . ':80'));
	}

	/**
	 * Tests the request method with a put request
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestPut($transportClass)
	{
		$transport = new $transportClass($this->options);

		$response = $transport->request('put', new Uri($this->stubUrl));

		$body = json_decode($response->body);

		$this->assertThat(
			$response->code,
			$this->equalTo(200)
		);

		$this->assertThat(
			$body->method,
			$this->equalTo('PUT')
		);
	}

	/**
	 * Tests the request method with credentials supplied
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestCredentials($transportClass)
	{
		$transport = new $transportClass($this->options);

		$uri = new Uri($this->stubUrl);
		$credentialedUri = new Uri(
			$uri->toString(array('scheme')) . 'username:password@' . $uri->toString(array('host', 'port', 'path', 'query', 'fragment'))
		);

		$response = $transport->request('get', $credentialedUri);

		$body = json_decode($response->body);

		$this->assertThat(
			$response->code,
			$this->equalTo(200)
		);

		$this->assertThat(
			$body->username,
			$this->equalTo('username')
		);

		$this->assertThat(
			$body->password,
			$this->equalTo('password')
		);
	}

	/**
	 * Tests the request method with a post request and array data
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestPost($transportClass)
	{
		$transport = new $transportClass($this->options);

		$response = $transport->request('post', new Uri($this->stubUrl . '?test=okay'), array('key' => 'value'));

		$body = json_decode($response->body);

		$this->assertThat(
			$response->code,
			$this->equalTo(200)
		);

		$this->assertThat(
			$body->method,
			$this->equalTo('POST')
		);

		$this->assertThat(
			$body->post->key,
			$this->equalTo('value')
		);
	}

	/**
	 * Tests the request method with a post request and scalar data
	 *
	 * @param   string  $transportClass  The transport class to test
	 *
	 * @return  void
	 *
	 * @since      1.0
	 * @dataProvider  transportProvider
	 */
	public function testRequestPostScalar($transportClass)
	{
		$transport = new $transportClass($this->options);

		$response = $transport->request('post', new Uri($this->stubUrl . '?test=okay'), 'key=value');

		$body = json_decode($response->body);

		$this->assertThat(
			$response->code,
			$this->equalTo(200)
		);

		$this->assertThat(
			$body->method,
			$this->equalTo('POST')
		);

		$this->assertThat(
			$body->post->key,
			$this->equalTo('value')
		);
	}
}
