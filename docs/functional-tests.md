---
sidebar_position: 1
---

# Functional Test cases

Swagger Test provides the class `ApiTestCase` for you extend and create a PHPUnit test case. The code will try to
make a request to your API Method and check if the request parameters, status and object returned are conform to
the OpenAPI Specification Provided.

```php
<?php
/**
 * Create a TestCase inherited from SwaggerTestCase
 */
class MyTestCase extends \ByJG\ApiTools\ApiTestCase
{
    public function setUp(): void
    {
        $schema = \ByJG\ApiTools\Base\Schema::getInstance(file_get_contents('/path/to/json/definition'));
        $this->setSchema($schema);
    }

    /**
     * Test if the REST address /path/for/get/ID with the method GET returns what is
     * documented on the "swagger.json"
     */
    public function testGet()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
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
        $request = new \ByJG\ApiTools\ApiRequester();
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
        $request = new \ByJG\ApiTools\ApiRequester();
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
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/path/for/post/3")
            ->withQuery(['id'=>10])
            ->withRequestBody(['name'=>'new name', 'field' => 'value']);

        $this->assertRequest($request);
    }

}
```
