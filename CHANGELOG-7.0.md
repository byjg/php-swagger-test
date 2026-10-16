# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Breaking Changes

- `Schema::getInstance()`, deprecated since 6.0, is removed. Use `Schema::fromFile()` for a file,
  `Schema::fromJson()` for a JSON string, or `Schema::fromArray()` for an array; they take the same
  second argument (`$allowNullValues`). See [Migrating from Schema::getInstance()](docs/migration-guide.md).
- `OpenApiValidation::assertRequest()`, deprecated since 6.0, is removed. It was the former name of
  `sendRequest()`, which takes the same requester.
- `ApiTestCase::makeRequest()`, deprecated since 6.0, is removed. Build the request with
  `ApiRequester` (`withMethod()`, `withPath()`, `withQuery()`, `withRequestBody()`,
  `withRequestHeader()`, `expectStatus()`) and pass it to `sendRequest()`. See
  [Migrating from makeRequest()](docs/migration-guide.md).
- `Body::match()` takes an optional second argument, `?string $contentType = null`. A class that
  extends `Body` (or one of its subclasses) and overrides `match()` has to add it to its signature.
- A response is validated against the media type of its `Content-Type` header (see Validation).
  A response whose header names a type the operation does not document now fails, also when its
  body would have matched the type guessed before.
- A request body is validated against the media type of its `Content-Type` header (see Validation).
  A request sent with a type the operation does not document now fails. On OpenAPI 3.0 a request
  with no `Content-Type` is validated against `application/json` when the operation documents it,
  like on 3.1, instead of against the first documented type.
- `InvalidRequestException: Cannot handle Content Type '…'` is no longer thrown: every request body
  is parsed now (see Validation). A request whose type the operation does not document throws
  `NotMatchedException: Content type '…' not found for <METHOD> <path>` instead. Code that caught
  `InvalidRequestException` for this case has to catch `NotMatchedException`.

## Validation

- `minLength` and `maxLength` are now enforced on string values, and `minimum` and
  `maximum` on numeric values, for both request and response bodies. Before, these
  keywords were read from the specification but never checked, so a value outside the
  declared limits was accepted.

  Length is counted in characters, not bytes. A violation throws
  `NotMatchedException`, for example
  `Value 'abcdef' in 'name' is longer than maxLength 5`.

  A request or response that was passing only because the limit was ignored now fails;
  the fix is in the data or in the specification.

  `exclusiveMinimum`, `exclusiveMaximum` and `multipleOf` are still not checked.

- The response body is matched against the media type of the response's `Content-Type`
  header. The requester read the header but did not pass it on, so the type was guessed from
  the body (a string became `text/plain`), and a response documented as `text/html` (or any
  non-JSON, non-XML type) could never be validated: `Content type not found`.

  The documented type is looked up as OpenAPI defines it: the type itself, then its range
  (`text/*`), then `*/*`. A media type documented without a schema accepts any body. Without a
  `Content-Type` header the type is still guessed from the body.

- The request body is matched the same way, against the media type of the request's
  `Content-Type`. OpenAPI 3.0 used the first documented media type and 3.1 preferred JSON, whatever
  the request was sent as, so an operation documenting `application/json` and
  `multipart/form-data` validated a multipart upload against the JSON schema (or the reverse), and a
  request with an undocumented type was checked against another type's schema. Without a
  `Content-Type` (`withRequestBody()` sends arrays as JSON and sets none), `application/json` is
  used, then `application/xml`, `text/xml`, then the first documented type, on both versions.

- The `multipart/form-data` parser only read a text field written with a `;` after its name, as
  byjg/webrequest writes it. A field as a browser or curl sends it
  (`Content-Disposition: form-data; name="title"`) was not read: it raised "Undefined array key"
  warnings and was stored under a `null` key, so a required field was reported missing. A file part
  of any type other than `application/octet-stream` kept its own `Content-Type` header lines in its
  value. The boundary was also used as a regular expression and a quoted boundary
  (`boundary="..."`) was not recognised. The parser now splits the body on the boundary and reads
  each part's name and value, with CRLF or LF line endings.

- Request and response bodies are parsed by one rule, by their `Content-Type`: any `*/json` or
  `*+json` type (RFC 6839, e.g. `application/merge-patch+json`) as JSON, any `*/xml` or `*+xml`
  type (e.g. `application/problem+xml`) as XML, multipart and urlencoded forms as fields, and
  anything else as the raw string. Without a `Content-Type`, a body that is not JSON is kept as a
  string. Before, a request of any other type raised
  `InvalidRequestException: Cannot handle Content Type`, an XML request could not be validated,
  only `text/xml` and `application/xml` responses were read as XML, and a non-JSON response
  without a `Content-Type` became `null` ("Body is expected"); it is now matched as `text/plain`.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/restserver` is now `^7.0`.
- `byjg/webrequest` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
