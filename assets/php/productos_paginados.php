<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

const PRODUCTOS_CACHE_TTL = 120;
const PRODUCTOS_DEFAULT_PER_PAGE = 30;
const PRODUCTOS_MAX_PER_PAGE = 60;

require_once __DIR__ . '/rutas.php';

function responderJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function leerEntradaJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function normalizarEntero($value, int $default, int $min, int $max): int
{
    $normalized = filter_var($value, FILTER_VALIDATE_INT);
    if ($normalized === false) {
        return $default;
    }

    return max($min, min($max, $normalized));
}

function normalizarFloatOpcional($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

function obtenerConfiguracionFuente(string $baseApi, array $input): array
{
    $mode = (string) ($input['mode'] ?? '');

    switch ($mode) {
        case 'nuevos':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosnuevos.php',
            'payload' => [],
            ];
        case 'categoria':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxCategoria.php',
            'payload' => [
                'idcategoria' => (string) ($input['idcategoria'] ?? ''),
            ],
            ];
        case 'busqueda':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxSearch.php',
            'payload' => [
                'search' => trim((string) ($input['search'] ?? '')),
            ],
            ];
        case 'marca':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxMarca.php',
            'payload' => [
                'idmarca' => (string) ($input['idmarca'] ?? ''),
                'idsucursal' => (string) ($input['idsucursal'] ?? '4'),
            ],
            ];
        case 'ofertas':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_ofertas.php',
            'payload' => [],
            ];
        case 'menosde100':
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_menosde100.php',
            'payload' => [],
            ];
        default:
            return [];
    }
}

function validarConfiguracionFuente(array $sourceConfig): ?string
{
    if (empty($sourceConfig['mode']) || empty($sourceConfig['url'])) {
        return 'Modo de listado no valido.';
    }

    switch ($sourceConfig['mode']) {
        case 'categoria':
            return empty($sourceConfig['payload']['idcategoria']) ? 'La categoria es obligatoria.' : null;
        case 'busqueda':
            return empty($sourceConfig['payload']['search']) ? 'El termino de busqueda es obligatorio.' : null;
        case 'marca':
            return empty($sourceConfig['payload']['idmarca']) ? 'La marca es obligatoria.' : null;
        default:
            return null;
    }
}

function obtenerRutaCache(string $mode, array $payload): string
{
    $cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'productos_paginados';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $cacheKey = md5(json_encode([
        'mode' => $mode,
        'payload' => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
}

function obtenerDatosCacheados(string $cacheFile): ?array
{
    if (!is_file($cacheFile)) {
        return null;
    }

    $age = time() - (int) filemtime($cacheFile);
    if ($age > PRODUCTOS_CACHE_TTL) {
        return null;
    }

    $cached = json_decode((string) file_get_contents($cacheFile), true);
    return is_array($cached) ? $cached : null;
}

function guardarDatosCacheados(string $cacheFile, array $data): void
{
    file_put_contents(
        $cacheFile,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function consultarApiRemota(string $url, array $payload): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => 'No fue posible consultar la fuente remota.',
            'error' => $error,
        ];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'La fuente remota devolvio una respuesta no valida.',
            'http_code' => $httpCode,
        ];
    }

    return $decoded;
}

function calcularRangoPrecios(array $productos): array
{
    if (empty($productos)) {
        return [
            'min' => 0,
            'max' => 100,
        ];
    }

    $prices = array_map(
        static function (array $producto): float {
            return (float) ($producto['precio_venta'] ?? 0);
        },
        $productos
    );

    $min = floor(min($prices));
    $max = ceil(max($prices));

    if ($min === $max) {
        $max = $min + 100;
    }

    return [
        'min' => (int) floor($min / 10) * 10,
        'max' => (int) ceil($max / 10) * 10,
    ];
}

function filtrarProductosPorPrecio(array $productos, ?float $priceMin, ?float $priceMax): array
{
    if ($priceMin === null && $priceMax === null) {
        return $productos;
    }

    return array_values(array_filter($productos, static function (array $producto) use ($priceMin, $priceMax): bool {
        $price = (float) ($producto['precio_venta'] ?? 0);

        if ($priceMin !== null && $price < $priceMin) {
            return false;
        }

        if ($priceMax !== null && $price > $priceMax) {
            return false;
        }

        return true;
    }));
}

$input = leerEntradaJson();
$sourceConfig = obtenerConfiguracionFuente($BASE_API, $input);
$validationError = validarConfiguracionFuente($sourceConfig);

if ($validationError !== null) {
    responderJson([
        'success' => false,
        'message' => $validationError,
        'data' => [],
    ], 422);
}

$requestedPage = normalizarEntero($input['page'] ?? 1, 1, 1, 5000);
$perPage = normalizarEntero($input['per_page'] ?? PRODUCTOS_DEFAULT_PER_PAGE, PRODUCTOS_DEFAULT_PER_PAGE, 1, PRODUCTOS_MAX_PER_PAGE);
$selectedPriceMin = normalizarFloatOpcional($input['price_min'] ?? null);
$selectedPriceMax = normalizarFloatOpcional($input['price_max'] ?? null);

$cacheFile = obtenerRutaCache((string) $sourceConfig['mode'], (array) $sourceConfig['payload']);
$apiData = obtenerDatosCacheados($cacheFile);

if ($apiData === null) {
    $apiData = consultarApiRemota((string) $sourceConfig['url'], (array) $sourceConfig['payload']);
    if (!empty($apiData['success'])) {
        guardarDatosCacheados($cacheFile, $apiData);
    }
}

if (empty($apiData['success']) || !isset($apiData['data']) || !is_array($apiData['data'])) {
    responderJson([
        'success' => false,
        'message' => (string) ($apiData['message'] ?? 'No fue posible cargar los productos.'),
        'data' => [],
    ], 502);
}

$productos = array_values(array_filter($apiData['data'], 'is_array'));
$priceRange = calcularRangoPrecios($productos);

if ($selectedPriceMin !== null) {
    $selectedPriceMin = max((float) $priceRange['min'], $selectedPriceMin);
}

if ($selectedPriceMax !== null) {
    $selectedPriceMax = min((float) $priceRange['max'], $selectedPriceMax);
}

if ($selectedPriceMin !== null && $selectedPriceMax !== null && $selectedPriceMin > $selectedPriceMax) {
    [$selectedPriceMin, $selectedPriceMax] = [$selectedPriceMax, $selectedPriceMin];
}

$filteredProducts = filtrarProductosPorPrecio($productos, $selectedPriceMin, $selectedPriceMax);
$totalProducts = count($filteredProducts);
$totalPages = $totalProducts > 0 ? (int) ceil($totalProducts / $perPage) : 1;
$currentPage = min($requestedPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$pageProducts = array_slice($filteredProducts, $offset, $perPage);
$from = $totalProducts > 0 ? $offset + 1 : 0;
$to = $totalProducts > 0 ? $offset + count($pageProducts) : 0;

responderJson([
    'success' => true,
    'data' => $pageProducts,
    'meta' => [
        'page' => $currentPage,
        'per_page' => $perPage,
        'total' => $totalProducts,
        'total_pages' => $totalPages,
        'from' => $from,
        'to' => $to,
        'mode' => $sourceConfig['mode'],
    ],
    'filters' => [
        'price_range' => $priceRange,
        'selected_price_min' => $selectedPriceMin,
        'selected_price_max' => $selectedPriceMax,
    ],
]);
