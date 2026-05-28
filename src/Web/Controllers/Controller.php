<?php

declare(strict_types=1);

namespace PhpMvc\Controllers;

use PhpMvc\Actions\Responses\ActionResponse;
use PhpMvc\Actions\Responses\LocalRedirectTo;
use PhpMvc\Actions\Responses\RedirectTo;
use PhpMvc\Actions\Responses\View;
use PhpMvc\Responses\Headers\Header;
use PhpMvc\Responses\StatusCode;

abstract class Controller
{
    /**
     * @param array<Header> $headers
     */
    public function __construct(private array $headers = []) {}

    protected function addHeader(Header $header): void
    {
        $this->headers[] = $header;
    }

    protected function view(
        ?string $name = null,
        ?object $model = null,
        StatusCode $statusCode = StatusCode::Ok,
    ): ActionResponse {
        $controllerPath = str_replace('Controller', '', basename(str_replace('\\', '/', static::class)));
        $viewName = $name ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        return new View("{$controllerPath}/{$viewName}", $model, array_merge($this->headers, []), $statusCode);
    }

    /**
     * @param null|array<string, mixed> $args
     */
    protected function redirectTo(string $url, ?array $args = []): ActionResponse
    {
        return RedirectTo::create(url: $url, args: $args, headers: array_merge($this->headers, []));
    }

    /**
     * @param class-string $controller
     */
    protected function redirectToAction(
        string $action,
        ?string $controller = null,
        ?object $args = null,
    ): ActionResponse {
        return LocalRedirectTo::create($action, $controller ?? static::class, $args, array_merge($this->headers, []));
    }
}
