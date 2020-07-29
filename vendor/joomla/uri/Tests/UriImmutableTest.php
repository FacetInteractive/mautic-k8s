<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Uri\Tests;

use Joomla\Uri\UriImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Joomla\Uri\UriImmutable class.
 *
 * @since  1.0
 */
class UriImmuteableTest extends TestCase
{
	/**
	 * Object under test
	 *
	 * @var    UriImmutable
	 * @since  1.0
	 */
	protected $object;

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
		$this->object = new UriImmutable('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');
	}

	/**
	 * Tests the __set method. Immutable objects will throw
	 * an exception when you try to change a property.
	 *
	 * @return  void
	 *
	 * @since   1.2.0
	 * @covers  Joomla\Uri\UriImmutable::__set
	 * @expectedException \BadMethodCallException
	 */
	public function test__set()
	{
		$this->object->uri = 'http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment';
	}

	/**
	 * Test the __toString method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::__toString
	 */
	public function test__toString()
	{
		$this->assertThat(
			$this->object->__toString(),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);
	}

	/**
	 * Test the toString method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::toString
	 */
	public function testToString()
	{
		$classname = \get_class($this->object);

		// The next 2 tested functions should generate equivalent results
		$this->assertThat(
			$this->object->toString(),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);

		$this->assertThat(
			$this->object->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment')),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);

		$this->assertThat(
			$this->object->toString(array('scheme')),
			$this->equalTo('http://')
		);

		$this->assertThat(
			$this->object->toString(array('host', 'port')),
			$this->equalTo('www.example.com:80')
		);

		$this->assertThat(
			$this->object->toString(array('path', 'query', 'fragment')),
			$this->equalTo('/path/file.html?var=value#fragment')
		);

		$this->assertThat(
			$this->object->toString(array('user', 'pass', 'host', 'port', 'path', 'query', 'fragment')),
			$this->equalTo('someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);
	}

	/**
	 * Test the render method.
	 *
	 * @return  void
	 *
	 * @since   1.2.0
	 * @covers  Joomla\Uri\UriImmutable::render
	 */
	public function testRender()
	{
		$classname = \get_class($this->object);

		$this->assertThat(
			$this->object->render($classname::ALL),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);

		$this->assertThat(
			$this->object->render($classname::SCHEME),
			$this->equalTo('http://')
		);

		$this->assertThat(
			$this->object->render($classname::HOST | $classname::PORT),
			$this->equalTo('www.example.com:80')
		);

		$this->assertThat(
			$this->object->render($classname::PATH | $classname::QUERY | $classname::FRAGMENT),
			$this->equalTo('/path/file.html?var=value#fragment')
		);

		$this->assertThat(
			$this->object->render($classname::ALL & ~$classname::SCHEME),
			$this->equalTo('someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);
	}

	/**
	 * Test the hasVar method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::hasVar
	 */
	public function testHasVar()
	{
		$this->assertThat(
			$this->object->hasVar('somevar'),
			$this->equalTo(false)
		);

		$this->assertThat(
			$this->object->hasVar('var'),
			$this->equalTo(true)
		);
	}

	/**
	 * Test the getVar method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getVar
	 */
	public function testGetVar()
	{
		$this->assertThat(
			$this->object->getVar('var'),
			$this->equalTo('value')
		);

		$this->assertThat(
			$this->object->getVar('var2'),
			$this->equalTo('')
		);

		$this->assertThat(
			$this->object->getVar('var2', 'default'),
			$this->equalTo('default')
		);
	}

	/**
	 * Test the getQuery method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getQuery
	 */
	public function testGetQuery()
	{
		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('var=value')
		);

		$this->assertThat(
			$this->object->getQuery(true),
			$this->equalTo(array('var' => 'value'))
		);
	}

	/**
	 * Test the getScheme method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getScheme
	 */
	public function testGetScheme()
	{
		$this->assertThat(
			$this->object->getScheme(),
			$this->equalTo('http')
		);
	}

	/**
	 * Test the getUser method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getUser
	 */
	public function testGetUser()
	{
		$this->assertThat(
			$this->object->getUser(),
			$this->equalTo('someuser')
		);
	}

	/**
	 * Test the getPass method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getPass
	 */
	public function testGetPass()
	{
		$this->assertThat(
			$this->object->getPass(),
			$this->equalTo('somepass')
		);
	}

	/**
	 * Test the getHost method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getHost
	 */
	public function testGetHost()
	{
		$this->assertThat(
			$this->object->getHost(),
			$this->equalTo('www.example.com')
		);
	}

	/**
	 * Test the getPort method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getPort
	 */
	public function testGetPort()
	{
		$this->assertThat(
			$this->object->getPort(),
			$this->equalTo('80')
		);
	}

	/**
	 * Test the getPath method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getPath
	 */
	public function testGetPath()
	{
		$this->assertThat(
			$this->object->getPath(),
			$this->equalTo('/path/file.html')
		);
	}

	/**
	 * Test the getFragment method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::getFragment
	 */
	public function testGetFragment()
	{
		$this->assertThat(
			$this->object->getFragment(),
			$this->equalTo('fragment')
		);
	}

	/**
	 * Test the isSsl method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @covers  Joomla\Uri\UriImmutable::isSsl
	 */
	public function testisSsl()
	{
		$this->object = new UriImmutable('https://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');

		$this->assertThat(
			$this->object->isSsl(),
			$this->equalTo(true)
		);

		$this->object = new UriImmutable('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');

		$this->assertThat(
			$this->object->isSsl(),
			$this->equalTo(false)
		);
	}
}
