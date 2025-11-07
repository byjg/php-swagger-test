---
sidebar_position: 3
---

# Runtime parameters validator

This tool was not developed only for unit and functional tests. You can use to validate if the required body
parameters is the expected.

So, before your API Code you can validate the request body using:

```php
<?php
$schema = \ByJG\ApiTools\Base\Schema::fromJson($contentsOfSchemaJson);
$bodyRequestDef = $schema->getRequestParameters($path, $method);
$bodyRequestDef->match($requestBody);
```
