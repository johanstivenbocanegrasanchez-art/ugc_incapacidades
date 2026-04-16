<?php

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . Config::baseUrl() . $path);
        exit;
    }

    protected function user(): array
    {
        return Session::getUser();
    }

    protected function requireLogin(): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    protected function requireRole(array $roles): void
    {
        $this->requireLogin();

        if (!in_array(Session::getRole(), $roles, true)) {
            http_response_code(403);
            $this->renderError(403, 'Sin permiso', '/dashboard');
            exit;
        }
    }

    protected function validateCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? null;
        if (!Security::validateCsrfToken($token)) {
            http_response_code(422);
            Flash::error('Token de seguridad inválido. Inténtalo de nuevo.');
            $this->redirect('/dashboard');
        }
    }

    private function renderError(int $code, string $message, string $link = '/login'): void
    {
        $baseUrl = Config::baseUrl();
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1.0">
            <title>{$code} - Error</title>
            <style>
                body{font-family:'Inter',Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f0;color:#1c2b1e}
                .error-box{text-align:center;padding:60px 24px}
                .error-box h2{font-size:48px;color:#0a5a1f;margin:0 0 8px}
                .error-box p{color:#5a6b5b;font-size:16px;margin:0 0 24px}
                a{color:#128b3b;text-decoration:none;font-weight:600}a:hover{text-decoration:underline}
            </style>
        </head>
        <body>
            <div class="error-box">
                <h2>{$code}</h2>
                <p>{$message}</p>
                <a href="{$baseUrl}{$link}">Volver</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
