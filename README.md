# PHP Swagger Test
[![Build Status](https://travis-ci.org/byjg/php-swagger-test.svg?branch=master)](https://travis-ci.org/byjg/php-swagger-test)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/php-swagger-test/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/php-swagger-test/?branch=master)

A set of tools for test your REST calls based on the swagger documentation using PHPUnit.

PHP Swagger Test can help you to test your REST Api. You can use this tool both for Unit Tests or Functional Tests.

This tool reads a previously Swagger JSON file (not YAML) and enable you to test the request and response. 
You can use the tool "https://github.com/zircote/swagger-php" for create the JSON file when you are developing your
rest API. 

The SwaggerTest's assertion process is based on throwing exceptions if some validation or test failed.

You can use the Swagger Test as:

- Functional test sases
- Unit test cases
- Runtime parameters validator

## Using it as Functional Test cases

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
     * Test if the REST address /path/for/post/ID with the method POST  
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost()
    {
        $this->makeRequest(
            'POST',                                      // The method
            "/path/for/post/ID",                         // The path defined in the swagger.json
            200,                                         // The expected status code
            null,                                        // The parameters 'in path'
            ['name'=>'new name', 'field' => 'value']     // The request body
        );
    }

    /**
     * Test if the REST address /another/path/for/post/{id} with the method POST  
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost2()
    {
        $this->makeRequest(
            'POST',                                     // The method
            "/another/path/for/post/{id}",              // The path defined in the swagger.json
            200,                                        // The expected status code
            ['id'=>10],                                 // The parameters 'in path'
            ['name'=>'new name', 'field' => 'value']    // The requested body
        );
    }

}
```

## Using it as Unit Test cases

If you want mock the request API and just test the expected parameters you are sending and 
receiving you have to:

**1. Create the Swagger Test Schema**

```php
<?php
$swaggerSchema = new \ByJG\Swagger\SwaggerSchema($contentsOfSwaggerJson);
```

**2. Get the definitions for your path**

```php
<?php
$path = '/path/to/method';
$statusExpected = 200;
$method = 'POST';

// Returns a SwaggerRequestBody instance
$bodyRequestDef = $swaggerSchema->getRequestParameters($path, $method);

// Returns a SwaggerResponseBody instance
$bodyResponseDef = $swaggerSchema->getResponseParameters($path, $method, $statusExpected);
```

**3. Match the result**

```php
<?php
if (!empty($requestBody)) {
    $bodyRequestDef->match($requestBody);
}
$bodyResponseDef->match($responseBody);
```

If the request or response body does not match with the definition an exception NotMatchException will
be throwed. 

## Using it as Runtime parameters validator

This tool was not developed only for unit and functional tests. You can use to validate if the required body
parameters is the expected. 

So, before your API Code you can validate the request body using:

```php
<?php
$swaggerSchema = new \ByJG\Swagger\SwaggerSchema($contentsOfSwaggerJson);
$bodyRequestDef = $swaggerSchema->getRequestParameters($path, $method);
$bodyRequestDef->match($requestBody);
```

## Install

```
composer require "byjg/swagger-test=1.0.*"
```

## Questions?

Use the Github issue.
