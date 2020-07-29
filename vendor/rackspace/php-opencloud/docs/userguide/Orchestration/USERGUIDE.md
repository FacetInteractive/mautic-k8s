# Complete User Guide for the Orchestration Service

Orchestration is a service that you can use to create and manage cloud resources
such as databases, load balancers, and servers, and the software installed on
servers.

## Table of Contents

* [Concepts](#concepts)
* [Prerequisites](#prerequisites)
  * [Client](#client)
  * [Orchestration service](#orchestration-service)
* [Templates](#templates)
  * [Validate template](#validate-template)
    * [Validate a template from a file](#validate-a-template-from-a-file)
    * [Validate Template from URL](#validate-template-from-url)
* [Stacks](#stacks)
  * [Preview stack](#preview-stack)
    * [Preview a stack from a template file](#preview-a-stack-from-a-template-file)
    * [Preview a stack from a template URL](#preview-a-stack-from-a-template-url)
  * [Create stack](#create-stack)
    * [Create a stack from a template file](#create-a-stack-from-a-template-file)
    * [Create a stack from a template URL](#create-a-stack-from-a-template-url)
  * [List stacks](#list-stacks)
  * [Get stack](#get-stack)
  * [Get stack template](#get-stack-template)
  * [Update stack](#update-stack)
    * [Update a stack from a template file](#update-a-stack-from-a-template-file)
    * [Update Stack from Template URL](#update-stack-from-template-url)
  * [Delete stack](#delete-stack)
  * [Abandon Stack](#abandon-stack)
  * [Adopt stack](#adopt-stack)
* [Stack resources](#stack-resources)
  * [List stack resources](#list-stack-resources)
  * [Get stack resource](#get-stack-resource)
  * [Get stack resource metadata](#get-stack-resource-metadata)
* [Stack resource events](#stack-resource-events)
  * [List stack events](#list-stack-events)
  * [List stack resource events](#list-stack-resource-events)
  * [Get stack resource event](#get-stack-resource-event)
* [Resource types](#resource-types)
  * [List resource types](#list-resource-types)
  * [Get resource type](#get-resource-type)
  * [Get resource type template](#get-resource-type-template)
* [Build info](#build-info)
  * [Get build info](#get-build-info)

## Concepts

To use the Orchestration service effectively, you should understand the following 
key concepts:

* **Template**: A JSON or YAML document that describes how a set of resources should
be assembled to produce a working deployment. The template specifies the resources
to use, the attributes of these resources that are parameterized and the information
that is sent to the user when a template is instantiated.

* **Resource**: Some component of your architecture (a cloud server, a group of scaled
cloud servers, a load balancer, some configuration management system, and so on) that
is defined in a template.

* **Stack**: A running instance of a template. When a stack is created, the resources
specified in the template are created.

## Prerequisites

### Client
To use the Orchestration service, you must first instantiate a `OpenStack` or `Rackspace` client object.

* If you are working with an OpenStack cloud, instantiate an `OpenCloud\OpenStack` client as follows:

    ```php
    use OpenCloud\OpenStack;

    $client = new OpenStack('<OPENSTACK CLOUD IDENTITY ENDPOINT URL>', array(
        'username' => '<YOUR OPENSTACK CLOUD ACCOUNT USERNAME>',
        'password' => '<YOUR OPENSTACK CLOUD ACCOUNT PASSWORD>'
    ));
    ```

* If you are working with the Rackspace cloud, instantiate a `OpenCloud\Rackspace` client as follows:

    ```php
    use OpenCloud\Rackspace;

    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => '<YOUR RACKSPACE CLOUD ACCOUNT USERNAME>',
        'apiKey'   => '<YOUR RACKSPACE CLOUD ACCOUNT API KEY>'
    ));
    ```

### Orchestration service
All Orchestration operations are done via an _orchestration service object_. To 
instantiate this object, call the `orchestrationService` method on the `$client`
object. This method takes two arguments:

| Position | Description | Data type | Required? | Default value | Example value |
| -------- | ----------- | ----------| --------- | ------------- | ------------- |
|  1       | Name of the service, as it appears in the service catalog | String | No | `null`; automatically determined when possible | `cloudOrchestration` |
|  2       | Cloud region | String | Yes | - | `DFW` |


```php
$region = '<CLOUD REGION NAME>';
$orchestrationService = $client->orchestrationService(null, $region);
```

Any stacks and resources created with this `$orchestrationService` instance will
be stored in the cloud region specified by `$region`.

## Templates
An Orchestration template is a JSON or YAML document that
describes how a set of resources should be assembled to produce a working
deployment (known as a [stack](#stacks)). The template specifies the resources
to use, the attributes of these resources that are parameterized and the
information that is sent to the user when a template is instantiated.

### Validate template
Before you use a template to create a stack, you might want to validate it.

#### Validate a template from a file
If your template is stored on your local computer as a JSON or YAML file, you
can validate it as shown in the following example:

```php
use OpenCloud\Common\Exceptions\InvalidTemplateError;

try {
    $orchestrationService->validateTemplate(array(
        'template' => file_get_contents(__DIR__ . '/lamp.yaml')
    ));
} catch (InvalidTemplateError $e) {
    // Use $e->getMessage() for explanation of why template is invalid
}
```

[ [Get the executable PHP script for this example](/samples/Orchestration/validate-template-from-template-url.php) ]

#### Validate Template from URL
If your template is stored as a JSON or YAML file in a remote location accessible
via HTTP or HTTPS, you can validate it as shown in the following example:

```php
use OpenCloud\Common\Exceptions\InvalidTemplateError;

try {
    $orchestrationService->validateTemplate(array(
        'templateUrl' => 'https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml'
    ));
} catch (InvalidTemplateError $e) {
    // Use $e->getMessage() for explanation of why template is invalid
}
```

[ [Get the executable PHP script for this example](/samples/Orchestration/validate-template-from-template-url.php) ]

## Stacks
A stack is a running instance of a template. When a stack is created, the
[resources](#stack-resources) specified in the template are created.

### Preview stack
Before you create a stack from a template, you might want to see what that stack
will look like. This is called _previewing the stack_.

This operation takes one parameter, an associative array, with the following keys:

| Name | Description | Data type | Required? | Default value | Example value |
| ---- | ----------- | --------- | --------- | ------------- | ------------- |
| `name` | Name of the stack | String. Must start with an alphabetic character, and must contain only alphanumeric, `_`, `-` or `.` characters | Yes | - | `simple-lamp-setup` |
| `template` | Template contents | String. JSON or YAML | No, if `templateUrl` is specified | `null` | `heat_template_version: 2013-05-23\ndescription: LAMP server\n` |
| `templateUrl` | URL of the template file | String. HTTP or HTTPS URL | No, if `template` is specified | `null` | `https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml` |
| `parameters` | Arguments to the template, based on the template's parameters. For example, see the parameters in [this template section](https://github.com/rackspace-orchestration-templates/lamp/blob/master/lamp.yaml#L22) | Associative array | No | `null` | `array('flavor_id' => 'general1-1')` |

#### Preview a stack from a template file

If your template is stored on your local computer as a JSON or YAML file, you
can use it to preview a stack as shown in the following example:

```php
$stack = $orchestrationService->previewStack(array(
    'name'         => 'simple-lamp-setup',
    'template'     => file_get_contents(__DIR__ . '/lamp.yml'),
    'parameters'   => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    )
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```

[ [Get the executable PHP script for this example](/samples/Orchestration/preview-stack-from-template-file.php) ]

#### Preview a stack from a template URL

If your template is stored as a JSON or YAML file in a remote location accessible
via HTTP or HTTPS, you can use it to preview a stack as shown in the following
example:

```php
$stack = $orchestrationService->previewStack(array(
    'name'         => 'simple-lamp-setup',
    'templateUrl'  => 'https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml',
    'parameters'   => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    )
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```

[ [Get the executable PHP script for this example](/samples/Orchestration/preview-stack-from-template-url.php) ]

### Create stack
You can create a stack from a template.

This operation takes one parameter, an associative array, with the following keys:

| Name | Description | Data type | Required? | Default value | Example value |
| ---- | ----------- | --------- | --------- | ------------- | ------------- |
| `name` | Name of the stack | String. Must start with an alphabetic character, and must contain only alphanumeric, `_`, `-` or `.` characters. | Yes | - | `simple-lamp-setup` |
| `template` | Template contents | String. JSON or YAML | No, if `templateUrl` is specified | `null` | `heat_template_version: 2013-05-23\ndescription: LAMP server\n` |
| `templateUrl` | URL of template file | String. HTTP or HTTPS URL | No, if `template` is specified | `null` | `https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml` |
| `parameters` | Arguments to the template, based on the template's parameters | Associative array | No | `null` | `array('server_hostname' => 'web01')` |
| `timeoutMins` | Duration, in minutes, after which stack creation should time out | Integer | Yes | - | 5 |

#### Create a stack from a template file

If your template is stored on your local computer as a JSON or YAML file, you
can use it to create a stack as shown in the following example:

```php
$stack = $orchestrationService->createStack(array(
    'name'         => 'simple-lamp-setup',
    'templateUrl'  => 'https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml',
    'parameters'   => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    ),
    'timeoutMins'  => 5
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/create-stack-from-template-file.php) ]

#### Create a stack from a template URL
If your template is stored as a JSON or YAML file in a remote location accessible
via HTTP or HTTPS, you can use it to create a stack as shown in the following
example:

```php
$stack = $orchestrationService->stack();
$stack->create(array(
    'name'          => 'simple-lamp-setup',
    'templateUrl'   => 'https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp.yaml',
    'parameters'    => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    ),
    'timeoutMins'   => 5
));
```
[ [Get the executable PHP script for this example](/samples/Orchestration/create-stack-from-template-url.php) ]

### List stacks
You can list all the stacks that you have created as shown in the following example:

```php
$stacks = $orchestrationService->listStacks();
foreach ($stacks as $stack) {
    /** @var $stack OpenCloud\Orchestration\Resource\Stack **/
}
```
[ [Get the executable PHP script for this example](/samples/Orchestration/list-stacks.php) ]

### Get stack
You can retrieve a specific stack using its name, as shown in the following example:

```php
$stack = $orchestrationService->getStack('simple-lamp-setup');
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-stack.php) ]

### Get stack template
You can retrieve the template used to create a stack. Note that a JSON string is
returned, regardless of whether a JSON or YAML template was used to create the stack.

```php
$stackTemplate = $stack->getTemplate();
/** @var $stackTemplate string **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-stack-template.php) ]

### Update stack
You can update a running stack.

This operation takes one parameter, an associative array, with the following keys:

| Name | Description | Data type | Required? | Default value | Example value |
| ---- | ----------- | --------- | --------- | ------------- | ------------- |
| `template` | Template contents | String. JSON or YAML | No, if `templateUrl` is specified | `null` | `heat_template_version: 2013-05-23\ndescription: LAMP server\n` |
| `templateUrl` | URL of template file | String. HTTP or HTTPS URL | No, if `template` is specified | `null` | `https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp-updated.yaml` |
| `parameters` | Arguments to the template, based on the template's parameters | Associative array | No | `null`| `array('flavor_id' => 'general1-1')` |
| `timeoutMins` | Duration, in minutes, after which stack update should time out | Integer | Yes | - | 5 |

#### Update a stack from a template file

If your template is stored on your local computer as a JSON or YAML file, you
can use it to update a stack as shown in the following example:

```php
$stack->update(array(
    'template'      => file_get_contents(__DIR__ . '/lamp-updated.yml'),
    'parameters'    => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    ),
    'timeoutMins'   => 5
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/update-stack-from-template-file.php) ]

#### Update Stack from Template URL

If your template is stored as a JSON or YAML file in a remote location accessible
via HTTP or HTTPS, you can use it to update a stack as shown in the following
example:

```php
$stack->update(array(
    'templateUrl'   => 'https://raw.githubusercontent.com/rackspace-orchestration-templates/lamp/master/lamp-updated.yaml',
    'parameters'    => array(
        'server_hostname' => 'web01',
        'image'           => 'Ubuntu 14.04 LTS (Trusty Tahr) (PVHVM)'
    ),
    'timeoutMins'   => 5
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/update-stack-from-template-url.php) ]

### Delete stack

If you no longer need a stack and all its resources, you can delete the stack
_and_ the resources as shown in the following example:

```php
$stack->delete();
```
[ [Get the executable PHP script for this example](/samples/Orchestration/delete-stack.php) ]

### Abandon Stack

If you want to delete a stack but preserve all its resources, you can abandon
the stack as shown in the following example:

```php
$abandonStackData = $stack->abandon();
/** @var $abandonStackData string **/

file_put_contents(__DIR__ . '/sample_adopt_stack_data.json', $abandonStackData);
```
[ [Get the executable PHP script for this example](/samples/Orchestration/abandon-stack.php) ]

Note that this operation returns data about the abandoned stack as a string. You
can use this data to recreate the stack by using the [adopt stack](#adopt-stack)
operation.

### Adopt stack

If you have data from an abandoned stack, you can re-create the stack as shown
in the following example:

```php
$stack = $orchestrationService->adoptStack(array(
    'name'           => 'simple-lamp-setup',
    'template'       => file_get_contents(__DIR__ . '/lamp.yml'),
    'adoptStackData' => $abandonStackData,
    'timeoutMins'    => 5
));
/** @var $stack OpenCloud\Orchestration\Resource\Stack **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/adopt-stack.php) ]

## Stack resources

A stack is made up of zero or more resources such as databases, load balancers, 
and servers, and the software installed on servers.

### List stack resources

You can list all the resources for a stack as shown in the following example:

```php
$resources = $stack->listResources();
foreach ($resources as $resource) {
    /** @var $resource OpenCloud\Orchestration\Resource\Resource **/
}
```
[ [Get the executable PHP script for this example](/samples/Orchestration/list-stack-resources.php) ]

### Get stack resource

You can retrieve a specific resource in a stack bt using that resource's name,
as shown in the following example:

```php
$resource = $stack->getResource('load-balancer');
/** @var $resource OpenCloud\Orchestration\Resource\Resource **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-stack-resource.php) ]

### Get stack resource metadata

You can retrieve the metadata for a specific resource in a stack as shown in the 
following example:

```php
$resourceMetadata = $resource->getMetadata();
/** @var $resourceMetadata \stdClass **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-stack-resource-metadata.php) ]

## Stack resource events
Operations on resources within a stack (such as the creation of a resource) produce
events.

### List stack events
You can list all of the events for all of the resources in a stack as shown in
the following example:

```php
$stackEvents = $stack->listEvents();
foreach ($stackEvents as $stackEvent) {
    /** @var $stackEvent OpenCloud\Orchestration\Resource\Event **/
}
```
[ [Get the executable PHP script for this example](/samples/Orchestration/list-stack-events.php) ]

### List stack resource events
You can list all of the events for a specific resource in a stack as shown in the
following example:

```php
$resourceEvents = $resource->listEvents();
foreach ($resourceEvents as $resourceEvent) {
    /** @var $resourceEvent OpenCloud\Orchestration\Resource\Event **/
}
```
[ [Get the executable PHP script for this example](/samples/Orchestration/list-stack-resource-events.php) ]

### Get stack resource event
You can retrieve a specific event for a specific resource in a stack, by using
the resource event's ID, as shown in the following example:

```php
$resourceEvent = $resource->getEvent('c1342a0a-59e6-4413-9af5-07c9cae7d729');
/** @var $resourceEvent OpenCloud\Orchestration\Resource\Event **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-stack-resource-event.php) ]

## Resource types
When you define a template, you must use resource types supported by your cloud.

### List resource types
You can list all supported resource types as shown in the following example:

```php
$resourceTypes = $orchestrationService->listResourceTypes();
foreach ($resourceTypes as $resourceType) {
    /** @var $resourceType OpenCloud\Orchestration\Resource\ResourceType **/
}
```
[ [Get the executable PHP script for this example](/samples/Orchestration/list-resource-types.php) ]

### Get resource type
You can retrieve a specific resource type's schema as shown in the following example:

```php
$resourceType = $orchestrationService->getResourceType('OS::Nova::Server');
/** @var $resourceType OpenCloud\Orchestration\Resource\ResourceType **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-resource-type.php) ]

### Get resource type template
You can retrieve a specific resource type's representation as it would appear
in a template, as shown in the following example:

```php
$resourceTypeTemplate = $resourceType->getTemplate();
/** @var $resourceTypeTemplate string **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-resource-type-template.php) ]

## Build info

### Get build info
You can retrieve information about the current Orchestration service build as
shown in the following example:

```php
$buildInfo = $orchestrationService->getBuildInfo();
/** @var $resourceType OpenCloud\Orchestration\Resource\BuildInfo **/
```
[ [Get the executable PHP script for this example](/samples/Orchestration/get-build-info.php) ]
