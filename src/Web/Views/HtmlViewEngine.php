<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Actions\Responses\View;
use AlfonsoSG\Mvc\HtmlViewEngineSettings;
use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\UiAssetsSettings;

final class HtmlViewEngine implements ViewEngine
{
    /** @var array<string, string> */
    private array $templateCache = [];

    public function __construct(
        private readonly HtmlViewEngineSettings $settings,
        private readonly ContentReplacer $contentReplacer,
        private readonly ?UiAssetsSettings $uiAssetsSettings = null,
    ) {}

    public function render(View $view, RequestContext $context): string
    {
        $viewPath = "{$this->settings->path}/{$view->viewPath}.html";
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Template not found: {$viewPath}");
        }
        if (!array_key_exists($viewPath, $this->templateCache)) {
            $templateFile = file_get_contents($viewPath);
            if (false === $templateFile) {
                throw new \RuntimeException("Could not read template: {$viewPath}");
            }
            $this->templateCache[$viewPath] = $templateFile;
        }
        $templateFile = $this->templateCache[$viewPath];

        // add current identity to the model
        $currentIdentity = $context->getIdentity();
        $contextModel = [
            'user' => (object) [
                'username' => $currentIdentity->username(),
                'isAuthenticated' => $currentIdentity->isAuthenticated(),
            ],
        ];

        if (null !== $this->uiAssetsSettings) {
            $contextModel = array_merge($contextModel, [
                'jsAssetsPathUrl' => $this->uiAssetsSettings->jsAssetsPathUrl,
                'mainJsBundler' => $this->uiAssetsSettings->mainJsBundler,
                'cssAssetsPathUrl' => $this->uiAssetsSettings->cssAssetsPathUrl,
                'mainCssBundler' => $this->uiAssetsSettings->mainCssBundler,
            ]);
        }
        $viewData = $view->data;

        /** @var array<string, mixed> $model */
        $model = array_merge(is_array($viewData) ? $viewData : (array) $viewData, $contextModel);

        $template = $this->applyLayout($templateFile);

        $body = $this->contentReplacer->replace($model, $template, $context);

        // clean empty lines
        return preg_replace("/^\\s*\n/m", '', $body) ?? '';
    }

    private function applyLayout(string $template): string
    {
        preg_match('/\{\{#layout (.*?):\}\}/', $template, $matches);
        if ($matches) {
            $layoutFilename = $matches[1];
            $layoutPath = "{$this->settings->path}/{$layoutFilename}.html";
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout not found: {$layoutFilename}");
            }

            /** @var string */
            $layout = file_get_contents($layoutPath);
            $indentation = '';
            if (preg_match('/^(\s*)\{\{content\}\}/m', $layout, $indentMatches)) {
                $indentation = $indentMatches[1];
            }
            $content = str_replace("{{#layout {$layoutFilename}:}}", '', $template);

            return str_replace('{{content}}', (string) preg_replace('/^/m', $indentation, $content), $layout);
        }

        return $template;
    }
}
