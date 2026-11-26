<?php

if (PHP_SAPI === 'cli') {
    http_response_code(404);
    exit;
}
ini_set('display_errors', '0');
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Content-Type: text/html; charset=utf-8');

use LM\CyberSource\Config;
use LM\CyberSource\ManualReconciliation;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\ReconciliationAdminAuth;
use LM\CyberSource\ReconciliationAdminService;
use LM\CyberSource\ReconciliationAdminStore;
use LM\CyberSource\ReconciliationAdminMetrics;

try {
    $root = dirname(__DIR__, 2);
    $settings = require $root . '/config/reconciliation_admin.php';
    if (!is_array($settings) || !isset($settings['enabled']) || $settings['enabled'] !== true) {
        http_response_code(404);
        exit('No disponible.');
    }
    require $root . '/integrations/cybersource/Autoload.php';
    ReconciliationAdminAuth::requireHttps($_SERVER, $settings['host']);
    if (!isset($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST'), true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        exit('Metodo no permitido.');
    }
    if ($settings['url_path'] !== '/admin/conciliacion/' || !empty($_SERVER['PATH_INFO'])
        || !isset($_SERVER['DOCUMENT_ROOT']) || !is_dir($_SERVER['DOCUMENT_ROOT'])) {
        throw new RuntimeException('Configuracion administrativa no disponible.');
    }
    if (isset($_SERVER['CONTENT_LENGTH']) && (!preg_match('/^[0-9]+$/D', (string) $_SERVER['CONTENT_LENGTH'])
        || (int) $_SERVER['CONTENT_LENGTH'] > 16384)) {
        http_response_code(413);
        exit('Solicitud demasiado grande.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_SERVER['CONTENT_TYPE'])
        || strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'])[0])) !== 'application/x-www-form-urlencoded')) {
        http_response_code(415);
        exit('Se requiere un formulario administrativo.');
    }
    $nonce = bin2hex(random_bytes(16));
    header("Content-Security-Policy: default-src 'none'; style-src 'nonce-" . $nonce
        . "'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    $metrics = new ReconciliationAdminMetrics(isset($settings['performance_logging']) ? $settings['performance_logging'] : false);
    $private = null;
    $metricsAuthorized = false;
    $requestKind = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'post' : 'dashboard';
    register_shutdown_function(function () use ($metrics, &$private, &$metricsAuthorized, &$requestKind) {
        if (!$metricsAuthorized || !($private instanceof ReconciliationAdminStore)) { return; }
        try {
            $record = $metrics->snapshot($requestKind);
            if ($record !== null) { $private->writePerformance($record); }
        } catch (Throwable $e) { /* Optional timings never interrupt authentication or reconciliation. */ }
    });
    $private = $metrics->measure('private_init_ms', function () use ($settings, $root, $metrics) {
        return new ReconciliationAdminStore($settings['private_directory'], array($root, $_SERVER['DOCUMENT_ROOT']), $metrics);
    });
    $auth = new ReconciliationAdminAuth($private);
    $metrics->measure('session_open_ms', function () use ($auth) { $auth->start(); });
    $input = function ($name, $limit = 160) {
        if (!isset($_POST[$name]) || !is_string($_POST[$name]) || strlen($_POST[$name]) > $limit) {
            throw new RuntimeException('Campo no valido.');
        }
        return $_POST[$name];
    };
    $loadService = function () use ($root, $settings, $private, $auth, $metrics) {
        return $metrics->measure('service_init_ms', function () use ($root, $settings, $private, $auth) {
            $config = Config::forProcess($root);
            if (!is_dir((string) $config->get('paths.session_dir'))) {
                throw new RuntimeException('Almacenamiento de pagos no disponible.');
            }
            $store = new PaymentSessionStore($config->get('paths.session_dir'));
            return new ReconciliationAdminService($config, $settings, $store,
                new ManualReconciliation($store, $config), $private, $auth);
        });
    };
    $actor = $metrics->measure('auth_current_ms', function () use ($auth) { return $auth->current(); });
    $metricsAuthorized = $actor !== null;
    if (!isset($_SESSION['panel_section'])
        || !in_array($_SESSION['panel_section'], array('home', 'search', 'pending', 'incidents', 'cron_check'), true)) {
        $_SESSION['panel_section'] = 'home';
        unset($_SESSION['reference'], $_SESSION['review'], $_SESSION['cursor'], $_SESSION['next_cursor']);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $auth->requireCsrf($input('csrf', 64));
        } catch (Throwable $e) {
            http_response_code(403);
            exit('Formulario vencido o no autorizado. Vuelva a abrir el panel.');
        }
        $action = $input('action', 20);
        try {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
            if ($action === 'login' && !$actor) {
                $metrics->measure('login_ms', function () use ($auth, $input, $ip) {
                    $auth->login(strtolower(trim($input('username', 64))), $input('password', 72), $ip);
                });
                $metricsAuthorized = true;
                $requestKind = 'login';
            } else {
                $auth->requireUser();
                if ($action === 'logout') {
                    $auth->logout();
                    $requestKind = 'logout';
                } elseif ($action === 'show') {
                    unset($_SESSION['reference'], $_SESSION['review']);
                    if ($_SESSION['panel_section'] === 'home') { $_SESSION['panel_section'] = 'search'; }
                    $reference = trim($input('reference', 40));
                    if (!preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}$/D', $reference)) {
                        throw new RuntimeException('Referencia no valida.');
                    }
                    $_SESSION['reference'] = $reference;
                } elseif (in_array($action, array('home', 'search', 'list', 'incidents'), true)) {
                    $_SESSION['panel_section'] = $action === 'list' ? 'pending' : $action;
                    unset($_SESSION['cursor'], $_SESSION['next_cursor'], $_SESSION['reference'], $_SESSION['review']);
                } elseif ($action === 'back') {
                    if (!isset($_SESSION['reference'])) {
                        if ($_SESSION['panel_section'] === 'cron_check') {
                            $_SESSION['panel_section'] = 'pending';
                        } else {
                            $_SESSION['panel_section'] = 'home';
                            unset($_SESSION['cursor'], $_SESSION['next_cursor']);
                        }
                    }
                    unset($_SESSION['reference'], $_SESSION['review']);
                } elseif ($action === 'cron_check') {
                    $_SESSION['panel_section'] = 'cron_check';
                    unset($_SESSION['reference'], $_SESSION['review']);
                } elseif ($action === 'next') {
                    if (!in_array($_SESSION['panel_section'], array('pending', 'incidents'), true)) {
                        throw new RuntimeException('Navegacion no permitida.');
                    }
                    $_SESSION['cursor'] = isset($_SESSION['next_cursor']) ? $_SESSION['next_cursor'] : '';
                    unset($_SESSION['reference'], $_SESSION['review']);
                } elseif ($action === 'link') {
                    $service = $loadService();
                    if (!$service->canLink()) {
                        $private->audit('link_blocked', $auth->requireUser());
                        throw new RuntimeException('Vinculaciones bloqueadas.');
                    }
                    $matches = array();
                    foreach (array('customer_matches', 'amount_matches', 'items_match', 'status_matches', 'authorize_link') as $field) {
                        $matches[$field] = isset($_POST[$field]) && $_POST[$field] === '1';
                    }
                    $auth->confirmPassword($input('password', 72), $ip);
                    $_SESSION['reference'] = $metrics->measure('link_ms', function () use ($service, $input, $matches) {
                        return $service->link($input('csrf', 64), $input('review_token', 64),
                            trim($input('quotation', 20)), trim($input('evidence', 161)), $matches);
                    });
                    $_SESSION['flash'] = 'Vinculacion local confirmada. No se crearon pedidos, cobros ni correos desde este panel.';
                } else {
                    throw new RuntimeException('Operacion no permitida.');
                }
            }
        } catch (Throwable $e) {
            $requestKind = 'error';
            $_SESSION['flash'] = $action === 'link'
                ? 'Vinculacion no confirmada. Consulte nuevamente la referencia; no cree ni reenvie pedidos. Revise autorizaciones, revision, evidencia y correspondencias.'
                : ($action === 'show' ? 'Referencia no valida. Indique una referencia LMQ completa desde el listado o el formulario.'
                : 'Solicitud no confirmada. Verifique credenciales, sesion y permisos. Tras varios intentos, espere 15 minutos.');
        }
        $auth->close();
        header('Location: ' . $settings['url_path']
            . ($action === 'show' && isset($_SESSION['reference']) ? '#detalle-operacion' : ''), true, 303);
        exit;
    }
    $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : '';
    unset($_SESSION['flash']);
    $page = array('items' => array(), 'next' => '', 'unreadable' => array());
    $detail = null;
    $detailError = '';
    $selectedReference = '';
    $cronPreview = null;
    $reviewToken = '';
    $canLink = false;
    $section = $_SESSION['panel_section'];
    $listMode = $section === 'incidents' ? 'incidents' : 'pending';
    if ($actor) {
        $service = $loadService();
        $canLink = $service->canLink() && !in_array($section, array('incidents', 'cron_check'), true);
        if (!isset($_SESSION['reference']) && in_array($section, array('pending', 'incidents'), true)) {
            $page = $metrics->measure('pending_ms', function () use ($service, $listMode) {
                return $service->pending(isset($_SESSION['cursor']) ? $_SESSION['cursor'] : '', $listMode);
            });
            $_SESSION['next_cursor'] = $page['next'];
        } elseif (!isset($_SESSION['reference']) && $section === 'cron_check') {
            $cronPreview = $service->cronPreview();
        } elseif (!isset($_SESSION['reference'])) {
            $private->audit($section === 'search' ? 'view_search' : 'view_home', $actor);
        }
        if (isset($_SESSION['reference'])) {
            $selectedReference = $_SESSION['reference'];
            $requestKind = 'detail';
            try {
                $detail = $metrics->measure('detail_ms', function () use ($service) { return $service->detail($_SESSION['reference']); });
                if ($canLink && $detail['payment_verified'] && $detail['status'] === 'PAID'
                    && !$detail['financial_review'] && !$detail['charge_group_blocked'] && !$detail['pending_notifications']
                    && $detail['quotation_id'] === null && in_array($detail['dispatch'], array('SENDING', 'UNKNOWN'), true)) {
                    $reviewToken = $auth->issueReview($detail['reference'], $detail['revision']);
                }
            } catch (Throwable $e) {
                $detail = null;
                $reviewToken = '';
                $detailError = 'No fue posible consultar esta referencia. Puede no existir o tener datos no disponibles o ilegibles. Solicite una revision privada del almacenamiento. No se modifico el pago.';
                unset($_SESSION['review']);
                $private->audit('view_reference_failed', $actor, array('reference' => $selectedReference));
            }
        }
    }
    $escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    // All session changes, including review tokens, are complete before rendering.
    $auth->close();
    $metrics->measure('render_ms', function () use ($auth, $escape, $nonce, $flash, $actor, $settings, $canLink, $page, $detail, $detailError, $selectedReference, $reviewToken, $listMode, $section, $cronPreview) {
        require __DIR__ . '/view.php';
    });
} catch (Throwable $e) {
    $requestKind = 'error';
    error_log('Reconciliation admin: acceso o inicializacion no disponible.');
    http_response_code(503);
    echo 'Panel no disponible. Revise HTTPS, habilitacion, almacenamiento privado y permisos con el responsable del hosting.';
}
