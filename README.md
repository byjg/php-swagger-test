# PHP Swagger Test
[![Build Status](https://travis-ci.org/byjg/php-swagger-test.svg?branch=master)](https://travis-ci.org/byjg/php-swagger-test)
[![Maintainable Rate](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Reliability Rate](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Security Rate](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=security_rating)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=alert_status)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Code Coverage](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=coverage)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=bugs)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=code_smells)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Techinical Debt](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=sqale_index)](https://sonarcloud.io/dashboard?id=php-swagger-test)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=php-swagger-test&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=php-swagger-test)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/php-swagger-test/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/php-swagger-test/?branch=master)



# MIGRATION to OAS 3.0

## https://blog.readme.io/an-example-filled-guide-to-swagger-3-2/
## https://blog.runscope.com/posts/tutorial-upgrading-swagger-2-api-definition-to-openapi-3



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

# Using it as Functional Test cases

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
        $request = new \ByJG\Swagger\SwaggerRequester();
        $request
            ->withMethod('GET')
            ->withPath("/path/for/get/1");

        $this->assertRequest($request);
    }

    /**
     * Test if the REST address /path/for/get/NOTFOUND returns a status code 404.
     */
    public function testGetNotFound()
    {
        $request = new \ByJG\Swagger\SwaggerRequester();
        $request
            ->withMethod('GET')
            ->withPath("/path/for/get/NOTFOUND")
            ->assertResponseCode(404);

        $this->assertRequest($request);
    }

    /**
     * Test if the REST address /path/for/post/ID with the method POST  
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost()
    {
        $request = new \ByJG\Swagger\SwaggerRequester();
        $request
            ->withMethod('POST')
            ->withPath("/path/for/post/2")
            ->withRequestBody(['name'=>'new name', 'field' => 'value']);

        $this->assertRequest($request);
    }

    /**
     * Test if the REST address /another/path/for/post/{id} with the method POST  
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost2()
    {
        $request = new \ByJG\Swagger\SwaggerRequester();
        $request
            ->withMethod('POST')
            ->withPath("/path/for/post/3")
            ->withQuery(['id'=>10])
            ->withRequestBody(['name'=>'new name', 'field' => 'value']);

        $this->assertRequest($request);
    }

}
```

# Using it as Unit Test cases

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

# Install

```
composer require "byjg/swagger-test=1.2.*"
```

# Questions?

Use the Github issue.

