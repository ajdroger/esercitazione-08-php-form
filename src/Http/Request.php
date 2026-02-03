<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed>  $post
     * @param array<string, mixed>  $server
     */
    public function __construct(
        private readonly array $query,
        private readonly array $post,
        private readonly array $server
    ) {}

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string)($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = (string)($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $this->post;
    }
}
