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
        // Limpiar cualquier output buffering previo para evitar errores de headers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Construir URL completa
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = Config::baseUrl();

        // Si BASE_URL ya incluye el host completo, usarlo directamente
        if (str_starts_with($baseUrl, 'http')) {
            $url = $baseUrl . $path;
        } else {
            $url = $protocol . '://' . $host . $baseUrl . $path;
        }

        header('Location: ' . $url);
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
        $token = $this->getCsrfTokenFromRequest();

        if (!Security::validateCsrfToken($token)) {
            $this->handleCsrfError();
        }
    }

    /**
     * Obtener token CSRF desde POST o headers HTTP
     */
    private function getCsrfTokenFromRequest(): ?string
    {
        // Primero intentar desde POST (formularios tradicionales)
        if (!empty($_POST['_csrf_token'])) {
            return $_POST['_csrf_token'];
        }

        // Luego intentar desde headers (peticiones AJAX/Fetch)
        $headers = getallheaders();
        $csrfHeader = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? null;

        if ($csrfHeader) {
            return $csrfHeader;
        }

        // También intentar leer el body JSON para peticiones API
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (!empty($data['_csrf_token'])) {
                return $data['_csrf_token'];
            }
        }

        return null;
    }

    /**
     * Manejar error CSRF según el tipo de petición
     */
    private function handleCsrfError(): void
    {
        http_response_code(422);

        // Para peticiones AJAX/API, devolver JSON en lugar de redirect
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Token de seguridad inválido']);
            exit;
        }

        Flash::error('Token de seguridad inválido. Inténtalo de nuevo.');
        $this->redirect('/dashboard');
    }

    /**
     * Detectar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return strtolower($requestedWith) === 'xmlhttprequest'
            || strpos($contentType, 'application/json') !== false;
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
