<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers  = [];
    private string $body    = '';

    public function setStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function json(array $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->setHeader('Content-Type', 'application/json');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    public function success(mixed $data = null, string $message = 'Operation successful', int $code = 200): self
    {
        return $this->json([
            'success'   => true,
            'code'      => $code,
            'message'   => $message,
            'data'      => $data,
            'errors'    => null,
            'timestamp' => date('c'),
        ], $code);
    }

    public function error(string $message, int $code = 400, array $errors = []): self
    {
        return $this->json([
            'success'   => false,
            'code'      => $code,
            'message'   => $message,
            'data'      => null,
            'errors'    => empty($errors) ? null : $errors,
            'timestamp' => date('c'),
        ], $code);
    }

    public function view(string $template, array $data = [], int $status = 200): self
    {
        $this->statusCode = $status;
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->body = $this->renderView($template, $data);
        return $this;
    }

    private function renderView(string $template, array $data): string
    {
        extract($data);
        $templatePath = VIEW_PATH . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        ob_start();
        require $templatePath;
        $output = ob_get_clean();

        $basePath = function_exists('app_base_path') ? trim(app_base_path(), '/') : '';
        if ($basePath !== '') {
            $pattern = '/\b(href|src|action)=("|\')\/(?!' . preg_quote($basePath, '/') . '\/)/';
            $replacement = '$1=$2/' . $basePath . '/';
            $output = preg_replace($pattern, $replacement, $output) ?? $output;
        }

        return $output;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->statusCode = $status;
        if ($url !== '' && str_starts_with($url, '/')) {
            $url = function_exists('app_url') ? app_url($url) : $url;
        }
        $this->setHeader('Location', $url);
        return $this;
    }

    public function send(): void
    {
        // Discard any stray buffered output (PHP warnings, BOM bytes, partial renders)
        // so headers can always be set. The response body is already in $this->body.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        echo $this->body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }
}
