<?php

namespace ByJG\ApiTools\OpenApi31;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\OpenApi\OpenApiBase;

/**
 * OpenAPI 3.1.x Schema implementation
 *
 * Handles OpenAPI 3.1.0 and above with full JSON Schema 2020-12 compatibility.
 * Adds support for webhooks, type arrays for nullable, const, if/then/else, and prefixItems.
 */
class OpenApi31Schema extends OpenApiBase
{
    /**
     * @inheritDoc
     */
    #[\Override]
    protected function getDefaultVersion(array $data): string
    {
        return $data['openapi'] ?? '3.1';
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function getServerVariableDefault(array $variableDefinition): string
    {
        // OpenAPI 3.1: default is optional
        return $variableDefinition['default'] ?? '';
    }

    /**
     * @inheritDoc
     * @throws InvalidRequestException
     */
    #[\Override]
    public function getRequestParameters(string $path, string $method): Body
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApi31RequestBody($this, "$method $path", []);
        }
        return new OpenApi31RequestBody($this, "$method $path", $structure['requestBody']);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body
    {
        return new OpenApi31ResponseBody($schema, $name, $structure, $allowNullValues);
    }

    // ==================== OpenAPI 3.1 Specific Methods ====================

    /**
     * Check if schema has webhooks
     *
     * @return bool
     */
    public function hasWebhooks(): bool
    {
        return isset($this->jsonFile['webhooks']) && !empty($this->jsonFile['webhooks']);
    }

    /**
     * Get all webhook names
     *
     * @return array<string>
     */
    public function getWebhookNames(): array
    {
        if (!$this->hasWebhooks()) {
            return [];
        }
        return array_keys($this->jsonFile['webhooks']);
    }

    /**
     * Get webhook definition for OpenAPI 3.1
     *
     * @param string $webhookName
     * @param string $method
     * @return mixed
     * @throws DefinitionNotFoundException
     */
    public function getWebhookDefinition(string $webhookName, string $method): mixed
    {
        if (!isset($this->jsonFile['webhooks'][$webhookName][$method])) {
            throw new DefinitionNotFoundException("Webhook '$webhookName' with method '$method' not found");
        }
        return $this->jsonFile['webhooks'][$webhookName][$method];
    }

    /**
     * Get webhook request parameters
     *
     * @param string $webhookName
     * @param string $method
     * @return Body
     * @throws DefinitionNotFoundException
     */
    public function getWebhookRequestParameters(string $webhookName, string $method): Body
    {
        $structure = $this->getWebhookDefinition($webhookName, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApi31RequestBody($this, "webhook $webhookName $method", []);
        }
        return new OpenApi31RequestBody($this, "webhook $webhookName $method", $structure['requestBody']);
    }

    /**
     * Get webhook response parameters
     *
     * @param string $webhookName
     * @param string $method
     * @param int $status
     * @return Body
     * @throws DefinitionNotFoundException
     */
    public function getWebhookResponseParameters(string $webhookName, string $method, int $status): Body
    {
        $structure = $this->getWebhookDefinition($webhookName, $method);

        if (!isset($structure['responses'][$status])) {
            throw new DefinitionNotFoundException(
                "Response status '$status' not found for webhook '$webhookName' method '$method'"
            );
        }

        return $this->getResponseBody(
            $this,
            "webhook $webhookName $method $status",
            $structure['responses'][$status]
        );
    }

    /**
     * Get the JSON Schema dialect declared by the root `jsonSchemaDialect` field
     *
     * @return string|null
     */
    public function getSchemaDialect(): ?string
    {
        return $this->jsonFile['jsonSchemaDialect'] ?? null;
    }

    /**
     * Check if schema uses JSON Schema 2020-12
     *
     * The OAS 3.1 dialect (the default when none is declared) is based on 2020-12.
     *
     * @return bool
     */
    public function isJsonSchema202012(): bool
    {
        $dialect = $this->getSchemaDialect();
        return $dialect === null
            || str_contains($dialect, '2020-12')
            || str_starts_with($dialect, 'https://spec.openapis.org/oas/3.1/dialect/');
    }
}
