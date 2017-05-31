# PHP Swagger Test
[![Build Status](https://travis-ci.org/byjg/php-swagger-test.svg?branch=master)](https://travis-ci.org/byjg/php-swagger-test)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/php-swagger-test/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/php-swagger-test/?branch=master)

A set of tools for test your REST calls based on the swagger documentation using PHPUnit

## Unit and Functional Tests

PHP Swagger Test can help you to test your REST Api. You can use this tool both for Unit Tests or Functional Tests.

This tool reads a previously Swagger JSON file (not YAML) and enable you to test the request and response. 
You can use the tool "https://github.com/zircote/swagger-php" for create the JSON file when you are developing your
rest API. 

The SwaggerTest's assertion process is based on throwing exceptions if some validation or test failed.

### Testing - The easy way

Swagger Test provide the class `SwaggerTestCase` for you extend and create a PHPUnit test case. The code will try to 
make a request to your API Method and check if the request parameters, status and object returned are OK. 

```php
<?php
/**
 * Create a TestCase inherited from SwaggerTestCase
 */
class MyTestCase extends \ByJG\Swagger\SwaggerTestCase
{
    protected $filePath = '/path/to/json/definition';
    
    /**
     * Test if the REST address /path/for/get/ID with the method GET returns what is
     * documented on the "swagger.json"
     */
    public function testGet()
    {
        $this->makeRequest('GET', "/path/for/get/ID");
    }

    /**
     * Test if the REST address /path/for/get/NOTFOUND returns a status code 404.
     */
    public function testGetNotFound()
    {
        $this->makeRequest('GET', "/path/for/get/NOTFOUND", 404);
    }

    /**
     * Test if the REST address /path/for/get/ID with the method POST returns status code 200 
     * and returns the object ['name'=>'new name', 'field' => 'value'] as is documented in the 
     * "swagger.json" file
     */
    public function testPost()
    {
        $this->makeRequest('GET', "/path/for/get/ID", 200, null, ['name'=>'new name', 'field' => 'value']);
    }
}
```

### Testing - TL;DR

Basically for use Swagger Test as functional test you have to:

**1. Make a request to your API**

You can use any PSR-7 compliant or class for make the API Request.

**2. Check the Status Code**

Just get the status code and check if is the expected.

**3. Check the Body Parameters**

The Swagger Test does not check the Query Parameters, only the json payload in the request. You can get the
request definition by using the `SwaggerSchema::getRequestParameter($path, $method)`. This will return a instance
of `SwaggerRequestBody` and you can call the `match($body)` method.

**4. Check the Body Response** 

After you receiving the body response you can get the response definition 
by using the `SwaggerSchema::getResponseParameter($path, $method, $status)`. This will return a instance
of `SwaggerResponseBody` and you can call the `match($body)` method.


See an example below:

```php
<?php
$this->swaggerSchema = new \ByJG\Swagger\SwaggerSchema($contentsOfSwaggerJson);

$this->assertEquals($status, $statusReturned);

$bodyRequestDef = $this->swaggerSchema->getRequestParameters($path, $method);
$bodyResponseDef = $this->swaggerSchema->getResponseParameters($path, $method, $statusExpected);

if (!empty($requestBody)) {
    $bodyRequestDef->match($requestBody);
}
$bodyResponseDef->match($responseBody);
```

## Validate the data your REST is receiving

This tool was not developed only for unit and functional tests. You can use to validate if the required body
parameters is the expected. 

So, before your API Code you can validate the request body using:

```php
<?php
$bodyRequestDef = $this->swaggerSchema->getRequestParameters($path, $method);
$bodyRequestDef->match($requestBody);
```

## Install

```
composer require "byjg/swagger-test=1.0.*"
```

## Questions?

Use the Github issue.
