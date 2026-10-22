<?php
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function app_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pos = strpos($script, '/simdosen/');
        $base = ($pos !== false) ? '/simdosen/' : '/';
    }
    return $base . ltrim($path, '/');
}

