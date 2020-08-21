## The HTTP Package [![Build Status](https://travis-ci.org/joomla-framework/http.png?branch=master)](https://travis-ci.org/joomla-framework/http)

[![Latest Stable Version](https://poser.pugx.org/joomla/http/v/stable)](https://packagist.org/packages/joomla/http)
[![Total Downloads](https://poser.pugx.org/joomla/http/downloads)](https://packagist.org/packages/joomla/http)
[![Latest Unstable Version](https://poser.pugx.org/joomla/http/v/unstable)](https://packagist.org/packages/joomla/http)
[![License](https://poser.pugx.org/joomla/http/license)](https://packagist.org/packages/joomla/http)

The HTTP package includes a suite of classes to facilitate RESTful HTTP requests over a variety of transport protocols.

The `Http\HttpFactory` class is used to build the classes required for HTTP requests.

### Interfaces

#### `Http\TransportInterface`

> Can you help improve this section of the manual?

### Classes

#### `Http\Http`

The `Http\Http` class provides methods for making RESTful requests.

##### Construction

Construction of `Http\Http` object is generally done using the `Http\HttpFactory` class. However, `Http\Http` is not abstract and can be instantiated directly passing an optional array of options and an optional `Http\TransportInterface` object. If the transport is omitted, the default transport will be used. The default is determined by looking up the transports folder and selecting the first transport that is supported (this will usually be the "curl" transport).

```php
use Joomla\Http\Http;
use Joomla\Http\Transport\Stream as StreamTransport;

$options = array();

$transport = new StreamTransport($options);

// Create a 'stream' transport.
$http = new Http($options, $transport);
```

##### Making a HEAD request

An HTTP HEAD request can be made using the head method passing a URL and an optional key-value array of header variables. The method will return a `Http\Response` object.

```php
use Joomla\Http\HttpFactory;

// Create an instance of a default Http object.
$http = `Http\HttpFactory`::getHttp();

// Invoke the HEAD request.
$response = $http->head('http://example.com');

// The response code is included in the "code" property.
// See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
var_dump($response->code);

// The response headers are included as an associative array in the "headers" property.
var_dump($response->headers);

// The body of the response (not applicable for the HEAD method) is included in the "body" property.
var_dump($response->body);
```

##### Making a GET request

An HTTP GET request can be made using the get method passing a URL, an optional key-value array of header variables and an optional timeout value. In RESTful terms, a GET request is sent to read data from the server.

```php
// Invoke the GET request.
$response = $http->get('http://api.example.com/cars');
```

##### Making a POST request

An HTTP POST request can be made using the post method passing a URL, a data variable, an optional key-value array of header variables and an optional timeout value. The data can be either an associative array of POST variables, or a string to be sent with the request. In RESTful terms, a POST request is sent to create new data on the server.

```php
// Prepare the update data.
$data = array('make' => 'Holden', model => 'EJ-Special');

// Invoke the GET request.
$response = $http->post('http://api.example.com/cars/1', $data);
```

##### Making a PUT request

An HTTP POST request can be made using the post method passing a URL, a data variable, an optional key-value array of header variables and an optional timeout value. The data can be either an associative array of POST variables, or a string to be sent with the request. In RESTful terms, a PUT request is typically sent to update existing data on the server.

```php
// Prepare the update data.
$data = array('description' => 'My first car.', 'color' => 'gray');

// Invoke the GET request.
$response = $http->put('http://api.example.com/cars/1', $data);
```

##### Making a DELETE request

An HTTP DELETE request can be made using the delete method passing a URL, an optional key-value array of header variables and an optional timeout value. In RESTful terms, a DELETE request is typically sent to delete existing data on the server.

```php
// Invoke the DELETE request.
$response = $http->delete('http://api.example.com/cars/1');
```

##### Making a TRACE request

An HTTP TRACE request can be made using the trace method passing a URL and an optional key-value array of header variables. In RESTful terms, a TRACE request is to echo data back to the client for debugging or testing purposes.

##### Working with options

Customs headers can be pased into each REST request, but they can also be set globally in the constructor options where the option path starts with "headers.". In the case where a request method passes additional headers, those will override the headers set in the options.

```php

// Configure a custom Accept header for all requests.
$options = array(
    'headers.Accept' => 'application/vnd.github.html+json'
);

// Make the request, knowing the custom Accept header will be used.
$pull = $http->get('https://api.github.com/repos/joomla/joomla-platform/pulls/1');

// Set up custom headers for a single request.
$headers = array('Accept' => 'application/foo');

// In this case, the Accept header in $headers will override the options header.
$pull = $http->get('https://api.github.com/repos/joomla/joomla-platform/pulls/1', $headers);
```

#### `Http\HttpFactory`

`Http\Http` objects are created by using the `Http\HttpFactory::getHttp` method.

```php
// The default transport will be 'curl' because this is the first transport.
$http = Http\HttpFactory::getHttp();

// Create a 'stream' transport.
$http = Http\HttpFactory::getHttp(null, 'stream');
```

#### Joomla\Http\Response

> Can you help improve this section of the manual?

#### Joomla\Http\Transport\Curl

> Can you help improve this section of the manual?

#### Joomla\Http\Transport\Socket

> Can you help improve this section of the manual?

#### Joomla\Http\Transport\Stream

> Can you help improve this section of the manual?


## Installation via Composer

Add `"joomla/http": "~1.0"` to the require block in your composer.json and then run `composer install`.

```json
{
	"require": {
		"joomla/http": "~1.0"
	}
}
```

Alternatively, you can simply run the following from the command line:

```sh
composer require joomla/http "~1.0"
```
