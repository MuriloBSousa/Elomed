<?php
// Front Controller - single entry point of the application.
// All requests pass through here via .htaccess.
// E.g.: http://localhost/EloMed/public/login/index

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/config/Config.php';
require BASE_PATH . '/app/config/autoload.php';
require BASE_PATH . '/app/helpers/helpers.php';

// Safety net: no exception may surface as a blank screen.
// Registered BEFORE Session::start() so session problems are caught too.
set_exception_handler(function (Throwable $e) {
    error_log('Exceção não tratada: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (headers_sent()) {
        return;
    }
    $message = 'Ocorreu um erro ao processar sua solicitação. Volte e tente novamente.';
    // Only DEBUG mode may expose the raw exception/SQL message; with
    // APP_DEBUG=false even DatabaseException stays generic (no SQL leak).
    $debug = filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    if ($debug) {
        $message = $e->getMessage();
    }
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>EloMed - Erro</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="' . BASE_URL . '/Assets/css/app.css" rel="stylesheet">';
    echo '</head><body class="auth-page"><div class="auth-shell" style="height:auto;min-height:340px;max-width:520px;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem;">';
    echo '<div><span class="brand-icon mx-auto"><i class="bi bi-exclamation-triangle"></i></span>';
    echo '<h1 class="h4 fw-bold mt-3 mb-2">Erro inesperado</h1>';
    echo '<p class="text-muted small mb-3">' . e($message) . '</p>';
    echo '<a class="btn btn-primary rounded-pill px-4" href="' . BASE_URL . '/login/index"><i class="bi bi-arrow-left me-1"></i> Voltar ao início</a>';
    echo '</div></div></body></html>';
    exit;
});

// Session started by the middleware (applies httponly, samesite, lifetime, etc.)
Session::start();

// Captures the friendly URL. E.g. usuario/index/5 -> "usuario/index/5"
$url = trim($_GET['url'] ?? '', '/');

// If nothing comes, assume the default home page
if (empty($url)) {
    $url = 'home/index';
}

$segments = explode('/', $url);

// Security: only letters, numbers, underscore and hyphen in each segment
foreach ($segments as $segment) {
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $segment)) {
        ErroController::erro404();
        exit;
    }
}

// Keeps the old format ($_GET['controller'] and $_GET['action'])
// so the views (e.g. header.php) keep working.
$_GET['controller'] = strtolower($segments[0]);
$_GET['action']     = strtolower($segments[1] ?? 'index');

$method = $_GET['action'];

// Finds the controller for the URL (accepts compound classes,
// e.g. tecnicoenfermagem -> TecnicoEnfermagemController).
$controllerFile = null;
$controllerName = null;

foreach (glob(BASE_PATH . '/app/controllers/*Controller.php') as $file) {
    $candidate = basename($file, '.php');
    if (strtolower($candidate) === $_GET['controller'] . 'controller') {
        $controllerFile = $file;
        $controllerName = $candidate;
        break;
    }
}

if (!$controllerFile) {
    ErroController::erro404();
    exit;
}

if (!class_exists($controllerName)) {
    require_once $controllerFile;
}

$instance = new $controllerName();

// Forced password change (first access): the user may only reach the
// password-change screen until they define a new password.
$forcaTroca = Session::has('usuario_id') && Session::get('forcar_troca_senha');
if ($forcaTroca) {
    $rotaPermitida = ($_GET['controller'] === 'perfil' && in_array($_GET['action'], ['trocar_senha', 'salvar'], true));
    $rotaPublica   = in_array($_GET['controller'], ['login', 'cadastro', 'home'], true);
    if (!$rotaPermitida && !$rotaPublica) {
        header('Location: ' . BASE_URL . '/perfil/trocar_senha');
        exit;
    }
}

// Only public actions are reachable by URL. method_exists() also returns true
// for private/protected methods, which would make them dispatchable and end in
// a 500 ("Call to private method") instead of a 404.
if (method_exists($instance, $method)) {
    $reflexao = new ReflectionMethod($instance, $method);
    if ($reflexao->isPublic() && !$reflexao->isStatic() && $reflexao->getName() !== '__construct') {
        call_user_func_array([$instance, $method], array_slice($segments, 2));
        return;
    }
}

ErroController::erro404();