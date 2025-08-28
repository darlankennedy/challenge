<?php

namespace App\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;

/**
 * Exceção de negócio exibida ao cliente GraphQL com "errors[].extensions".
 */
final class GraphQLClientException extends Exception implements ClientAware, ProvidesExtensions
{
    /** @param array<string,mixed> $extensions */
    public function __construct(
        string $message,
        private array $extensions = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        // Se quiser garantir uma categoria padrão:
        $this->extensions += ['category' => $this->extensions['category'] ?? 'business'];
        parent::__construct($message, $code, $previous);
    }

    /** Mostra a mensagem ao cliente (senão vira "Internal server error" em prod). */
    public function isClientSafe(): bool
    {
        return true;
    }

    /** Conteúdo de errors[].extensions */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
