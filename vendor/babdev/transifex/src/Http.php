<?php
/**
 * BabDev Transifex Package
 *
 * @copyright  Copyright (C) 2012-2015 Michael Babker. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace BabDev\Transifex;

use Joomla\Http\Http as BaseHttp;
use Joomla\Http\TransportInterface;

/**
 * HTTP client class for connecting to the Transifex API.
 *
 * @since  1.0
 * @deprecated  2.0  The joomla/http package will no longer be used.
 */
class Http extends BaseHttp
{
	/**
	 * Constructor.
	 *
	 * @param   array               $options    Client options array.
	 * @param   TransportInterface  $transport  The HTTP transport object.
	 *
	 * @since   1.0
	 */
	public function __construct($options = array(), TransportInterface $transport = null)
	{
		// Call the BaseHttp constructor to setup the object.
		parent::__construct($options, $transport);

		// Make sure the user agent string is defined.
		if (!$this->getOption('userAgent'))
		{
			$this->setOption('userAgent', 'BDTransifex/2.0');
		}

		// Set the default timeout to 120 seconds.
		if (!$this->getOption('timeout'))
		{
			$this->setOption('timeout', 120);
		}
	}
}
