<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

const PRODUCTOS_CACHE_TTL = 120;
const PRODUCTOS_LARGE_RESPONSE_CACHE_TTL = 900;
const PRODUCTOS_SEARCH_CATALOG_TTL = 21600;
const PRODUCTOS_DEFAULT_PER_PAGE = 30;
const PRODUCTOS_MAX_PER_PAGE = 60;
const PRODUCTOS_ERROR_MEMORY_RESERVE = 524288;

$GLOBALS['productos_respuesta_enviada'] = false;
$GLOBALS['productos_memoria_reservada'] = str_repeat('x', PRODUCTOS_ERROR_MEMORY_RESERVE);

function responderJson(array $payload, int $statusCode = 200): void
{
    $GLOBALS['productos_respuesta_enviada'] = true;
    http_response_code($statusCode);
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
    );

    if ($json === false) {
        $json = '{"success":false,"message":"No fue posible generar la respuesta JSON.","data":[]}';
    }

    echo $json;
    exit;
}

function registrarErrorProductos(string $message): void
{
    error_log('[productos_paginados] ' . $message);
}

set_exception_handler(static function ($exception): void {
    registrarErrorProductos(
        get_class($exception) . ': ' . $exception->getMessage()
        . ' en ' . $exception->getFile() . ':' . $exception->getLine()
    );

    responderJson([
        'success' => false,
        'message' => 'Ocurrio un error interno al procesar los productos.',
        'data' => [],
    ], 500);
});

register_shutdown_function(static function (): void {
    $GLOBALS['productos_memoria_reservada'] = null;
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (
        !is_array($error)
        || !in_array($error['type'], $fatalTypes, true)
        || !empty($GLOBALS['productos_respuesta_enviada'])
    ) {
        return;
    }

    registrarErrorProductos(
        'Error fatal: ' . $error['message']
        . ' en ' . $error['file'] . ':' . $error['line']
    );

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code(500);
    }

    echo '{"success":false,"message":"Ocurrio un error interno al procesar los productos.","data":[]}';
});

$rutasFile = __DIR__ . '/rutas.php';
if (!is_file($rutasFile) || !is_readable($rutasFile)) {
    registrarErrorProductos('No se encontro un archivo de rutas legible.');
    responderJson([
        'success' => false,
        'message' => 'La configuracion del servicio no esta disponible.',
        'data' => [],
    ], 500);
}

require_once $rutasFile;

if (!isset($BASE_API) || !is_string($BASE_API) || trim($BASE_API) === '') {
    registrarErrorProductos('La variable BASE_API no esta definida correctamente.');
    responderJson([
        'success' => false,
        'message' => 'La configuracion del servicio no es valida.',
        'data' => [],
    ], 500);
}

$BASE_API = rtrim(trim($BASE_API), '/') . '/';

if (!function_exists('curl_init')) {
    responderJson([
        'success' => false,
        'message' => 'El servidor no tiene habilitada la extension cURL.',
        'data' => [],
    ], 503);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderJson([
        'success' => false,
        'message' => 'Metodo no permitido.',
        'data' => [],
    ], 405);
}

function leerEntradaJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        responderJson([
            'success' => false,
            'message' => 'El cuerpo de la solicitud no contiene JSON valido.',
            'data' => [],
        ], 400);
    }

    return $decoded;
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

function normalizarTipoBusqueda($value): string
{
    $tipo = strtolower(trim((string) $value));
    if ($tipo === '' || in_array($tipo, ['general', 'todos', 'todo', 'automatico'], true)) {
        return 'general';
    }

    $permitidos = ['nombre', 'descripcion', 'autor', 'editorial', 'marca'];

    return in_array($tipo, $permitidos, true) ? $tipo : 'general';
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
            $idCategoria = trim((string) ($input['idcategoria'] ?? ''));
            $idSucursal = trim((string) ($input['idsucursal'] ?? '4'));
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxCategoria.php',
            'payload' => [
                'idcategoria' => preg_match('/^\d{1,10}$/', $idCategoria) ? $idCategoria : '',
                'idsucursal' => preg_match('/^\d{1,10}$/', $idSucursal) ? $idSucursal : '4',
            ],
            ];
        case 'busqueda':
            $search = trim((string) ($input['search'] ?? ''));
            $idSucursal = trim((string) ($input['idsucursal'] ?? '4'));
            $tipoBusqueda = normalizarTipoBusqueda($input['tipoBusqueda'] ?? 'general');
            $search = preg_replace('/[\x00-\x1F\x7F]/u', '', $search);
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxSearch.php',
            'payload' => [
                'search' => function_exists('mb_substr') ? mb_substr($search, 0, 80, 'UTF-8') : substr($search, 0, 80),
                'idsucursal' => preg_match('/^\d{1,10}$/', $idSucursal) ? $idSucursal : '4',
                'tipoBusqueda' => $tipoBusqueda,
            ],
            ];
        case 'marca':
            $idMarca = trim((string) ($input['idmarca'] ?? ''));
            $idSucursal = trim((string) ($input['idsucursal'] ?? '4'));
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxMarca.php',
            'payload' => [
                'idmarca' => preg_match('/^\d{1,10}$/', $idMarca) ? $idMarca : '',
                'idsucursal' => preg_match('/^\d{1,10}$/', $idSucursal) ? $idSucursal : '4',
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
            if (empty($sourceConfig['payload']['search'])) {
                return 'El termino de busqueda es obligatorio.';
            }

            return preg_match('/<\s*(script|iframe|object|embed|style|link|meta)|javascript\s*:|on[a-z]+\s*=/i', $sourceConfig['payload']['search'])
                ? 'El termino de busqueda no es valido.'
                : null;
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
        if (!@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
            registrarErrorProductos('No fue posible crear el directorio de cache.');
            return '';
        }
    }

    if (!is_writable($cacheDir)) {
        registrarErrorProductos('El directorio de cache no tiene permisos de escritura.');
        return '';
    }

    $cacheKey = md5(json_encode([
        'mode' => $mode,
        'payload' => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
}

function obtenerDatosCacheados(string $cacheFile, int $ttl = PRODUCTOS_CACHE_TTL): ?array
{
    if ($cacheFile === '' || !is_file($cacheFile) || !is_readable($cacheFile)) {
        return null;
    }

    $modifiedAt = @filemtime($cacheFile);
    if ($modifiedAt === false) {
        return null;
    }

    $age = time() - (int) $modifiedAt;
    if ($age > $ttl) {
        return null;
    }

    $contents = @file_get_contents($cacheFile);
    if ($contents === false) {
        registrarErrorProductos('No fue posible leer un archivo de cache.');
        return null;
    }

    $cached = json_decode($contents, true);
    if (!is_array($cached) || json_last_error() !== JSON_ERROR_NONE) {
        registrarErrorProductos('Se ignoro un archivo de cache con JSON invalido.');
        return null;
    }

    return $cached;
}

function guardarDatosCacheados(string $cacheFile, array $data): bool
{
    if ($cacheFile === '') {
        return false;
    }

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        registrarErrorProductos('No fue posible serializar los datos para cache.');
        return false;
    }

    $written = @file_put_contents(
        $cacheFile,
        $json,
        LOCK_EX
    );

    if ($written === false) {
        registrarErrorProductos('No fue posible escribir el archivo de cache.');
        return false;
    }

    return true;
}

function consultarApiRemota(string $url, array $payload): array
{
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'El servidor no tiene habilitada la extension cURL.',
            'data' => [],
        ];
    }

    $requestBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($requestBody === false) {
        registrarErrorProductos('No fue posible serializar el payload remoto.');
        return [
            'success' => false,
            'message' => 'No fue posible preparar la consulta de productos.',
            'data' => [],
        ];
    }

    $ch = curl_init();
    if ($ch === false) {
        registrarErrorProductos('curl_init no pudo crear un manejador.');
        return [
            'success' => false,
            'message' => 'No fue posible iniciar la consulta de productos.',
            'data' => [],
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $errorNumber = curl_errno($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $errorNumber !== 0) {
        registrarErrorProductos(
            'Error cURL ' . $errorNumber . ' al consultar la fuente remota: ' . $error
        );
        return [
            'success' => false,
            'message' => 'No fue posible consultar la fuente remota.',
            'data' => [],
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        registrarErrorProductos('La fuente remota respondio HTTP ' . $httpCode . '.');
        return [
            'success' => false,
            'message' => 'La fuente remota no pudo procesar la solicitud.',
            'data' => [],
        ];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        registrarErrorProductos('La fuente remota devolvio JSON invalido.');
        return [
            'success' => false,
            'message' => 'La fuente remota devolvio una respuesta no valida.',
            'data' => [],
        ];
    }

    if (!array_key_exists('success', $decoded) || !array_key_exists('data', $decoded) || !is_array($decoded['data'])) {
        registrarErrorProductos('La fuente remota devolvio una estructura inesperada.');
        return [
            'success' => false,
            'message' => 'La fuente remota devolvio una estructura no valida.',
            'data' => [],
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

function filtrarProductosPorIdArticulo(array $productos, string $idArticulo): array
{
    if ($idArticulo === '' || !preg_match('/^[0-9]+$/', $idArticulo)) {
        return $productos;
    }

    return array_values(array_filter($productos, static function (array $producto) use ($idArticulo): bool {
        return (string) ($producto['idarticulo'] ?? '') === $idArticulo;
    }));
}

function normalizarNombrePresentacion($value): string
{
    $texto = trim((string) $value);
    if ($texto === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $sinAcentos = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($sinAcentos !== false) {
            $texto = $sinAcentos;
        }
    }

    $texto = preg_replace('/\s+/', ' ', $texto) ?? '';
    return strtoupper(trim($texto));
}

function presentacionEsVisible($nombre): bool
{
    $normalizado = normalizarNombrePresentacion($nombre);
    if ($normalizado === '' || $normalizado === '0') {
        return false;
    }

    return !in_array($normalizado, ['MAYORISTA', 'MINOREO', 'MINORISTA', 'MENUDEO'], true);
}

function primerValorProducto(array $producto, array $keys)
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $producto) && trim((string) $producto[$key]) !== '') {
            return $producto[$key];
        }
    }

    return null;
}

function stockRealProducto(array $producto): ?float
{
    $valor = primerValorProducto($producto, ['stock', 'stocksucursal', 'existencia', 'stock_unidad']);
    if ($valor === null) {
        return null;
    }

    $stock = (float) $valor;
    return $stock >= 0 ? $stock : null;
}

function crearPresentacionProducto($nombre, $tipo, $stock, $precio): ?array
{
    if (!presentacionEsVisible($nombre)) {
        return null;
    }

    $precio = (float) ($precio ?? 0);
    $stock = (float) ($stock ?? 0);

    if ($precio <= 0 || $stock <= 0) {
        return null;
    }

    return [
        'nombre' => trim((string) $nombre),
        'tipo' => $tipo,
        'stock' => $stock,
        'precio' => $precio,
    ];
}

function obtenerPresentacionesProducto(array $producto): array
{
    $presentaciones = [];
    $stockReal = stockRealProducto($producto);

    if (isset($producto['presentaciones']) && is_array($producto['presentaciones'])) {
        foreach ($producto['presentaciones'] as $presentacion) {
            if (!is_array($presentacion)) {
                continue;
            }

            $normalizada = crearPresentacionProducto(
                $presentacion['nombre'] ?? $presentacion['presentacion'] ?? '',
                (string) ($presentacion['tipo'] ?? ''),
                $stockReal ?? ($presentacion['stock'] ?? $presentacion['existencia'] ?? 0),
                $presentacion['precio'] ?? $presentacion['precio_venta'] ?? 0
            );

            if ($normalizada !== null) {
                $presentaciones[] = $normalizada;
            }
        }
    }

    $definiciones = [
        ['nombre_01', 'unidad', ['stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_unidad', 'precio_01', 'precio_venta']],
        ['nombre_02', 'blister', ['stock_blister', 'stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_blister', 'precio_02']],
        ['nombre_03', 'caja', ['stock_caja', 'stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_caja', 'precio_03']],
        ['nombre_04', 'fardo', ['stock_fardo', 'stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_fardo', 'precio_04']],
        ['nombre_05', 'sacos', ['stock_sacos', 'stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_sacos', 'precio_05']],
        ['nombre_06', 'paquete', ['stock_paquete', 'stock', 'stocksucursal', 'existencia', 'stock_unidad'], ['precio_paquete', 'precio_06']],
    ];

    for ($i = 7; $i <= 20; $i++) {
        $idx = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $definiciones[] = ["nombre_$idx", "presentacion_$idx", ['stock', 'stocksucursal', 'existencia', 'stock_unidad'], ["precio_$idx"]];
    }

    foreach ($definiciones as [$nombreKey, $tipo, $stockKeys, $precioKeys]) {
        $normalizada = crearPresentacionProducto(
            $producto[$nombreKey] ?? '',
            $tipo,
            primerValorProducto($producto, $stockKeys),
            primerValorProducto($producto, $precioKeys)
        );

        if ($normalizada !== null) {
            $presentaciones[] = $normalizada;
        }
    }

    $unicas = [];
    foreach ($presentaciones as $presentacion) {
        $clave = normalizarNombrePresentacion($presentacion['tipo'] . ':' . $presentacion['nombre']);
        $unicas[$clave] = $presentacion;
    }

    return array_values($unicas);
}

function agregarPresentacionesNormalizadas(array $producto): array
{
    $producto['presentaciones'] = obtenerPresentacionesProducto($producto);
    return $producto;
}

function abrirRespuestaRemotaComoStream(string $url, array $payload, string $cacheFile): array
{
    if ($cacheFile !== '' && is_file($cacheFile) && is_readable($cacheFile)) {
        $modifiedAt = @filemtime($cacheFile);
        if (
            $modifiedAt !== false
            && (time() - (int) $modifiedAt) <= PRODUCTOS_LARGE_RESPONSE_CACHE_TTL
        ) {
            $cachedStream = @fopen($cacheFile, 'rb');
            if (is_resource($cachedStream)) {
                return [
                    'success' => true,
                    'stream' => $cachedStream,
                    'cache_target' => '',
                    'temporary_path' => '',
                    'cache_file' => $cacheFile,
                ];
            }
        }
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'El servidor no tiene habilitada la extension cURL.',
        ];
    }

    $requestBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($requestBody === false) {
        registrarErrorProductos('No fue posible serializar el payload de menos de 100.');
        return [
            'success' => false,
            'message' => 'No fue posible preparar la consulta de productos.',
        ];
    }

    $temporaryPath = '';
    $stream = false;

    if ($cacheFile !== '') {
        $temporaryPath = $cacheFile . '.' . getmypid() . '.' . uniqid('', true) . '.tmp';
        $stream = @fopen($temporaryPath, 'w+b');
    }

    if (!is_resource($stream)) {
        $temporaryPath = '';
        $stream = @tmpfile();
    }

    if (!is_resource($stream)) {
        registrarErrorProductos('No fue posible crear un archivo temporal para la respuesta remota.');
        return [
            'success' => false,
            'message' => 'No fue posible preparar el almacenamiento temporal de productos.',
        ];
    }

    $ch = curl_init();
    if ($ch === false) {
        fclose($stream);
        if ($temporaryPath !== '') {
            @unlink($temporaryPath);
        }

        registrarErrorProductos('curl_init no pudo crear el manejador de menos de 100.');
        return [
            'success' => false,
            'message' => 'No fue posible iniciar la consulta de productos.',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_FILE => $stream,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
    ]);

    $executed = curl_exec($ch);
    $error = curl_error($ch);
    $errorNumber = curl_errno($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($executed === false || $errorNumber !== 0) {
        fclose($stream);
        if ($temporaryPath !== '') {
            @unlink($temporaryPath);
        }

        registrarErrorProductos(
            'Error cURL ' . $errorNumber . ' al consultar menos de 100: ' . $error
        );
        return [
            'success' => false,
            'message' => 'No fue posible consultar la fuente remota.',
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        fclose($stream);
        if ($temporaryPath !== '') {
            @unlink($temporaryPath);
        }

        registrarErrorProductos('La fuente de menos de 100 respondio HTTP ' . $httpCode . '.');
        return [
            'success' => false,
            'message' => 'La fuente remota no pudo procesar la solicitud.',
        ];
    }

    rewind($stream);

    return [
        'success' => true,
        'stream' => $stream,
        'cache_target' => $temporaryPath !== '' ? $cacheFile : '',
        'temporary_path' => $temporaryPath,
        'cache_file' => $cacheFile,
    ];
}

function cerrarRespuestaRemotaStream(array $context, bool $guardarCache): void
{
    if (isset($context['stream']) && is_resource($context['stream'])) {
        fclose($context['stream']);
    }

    $temporaryPath = (string) ($context['temporary_path'] ?? '');
    $cacheTarget = (string) ($context['cache_target'] ?? '');

    if ($temporaryPath === '') {
        return;
    }

    if ($guardarCache && $cacheTarget !== '') {
        if (!@copy($temporaryPath, $cacheTarget)) {
            registrarErrorProductos('No fue posible publicar la cache de menos de 100.');
        }
    }

    @unlink($temporaryPath);
}

function procesarProductosDesdeJsonStream($stream, callable $onProduct): int
{
    if (!is_resource($stream)) {
        throw new RuntimeException('La respuesta remota no esta disponible.');
    }

    rewind($stream);
    $json = stream_get_contents($stream);
    if ($json === false || $json === '') {
        throw new UnexpectedValueException('La fuente remota devolvio una respuesta vacia.');
    }

    if (!preg_match('/"success"\s*:\s*(true|false)/i', $json, $successMatch)) {
        throw new UnexpectedValueException('La fuente remota no informo el estado de la solicitud.');
    }

    if (strtolower($successMatch[1]) !== 'true') {
        throw new UnexpectedValueException('La fuente remota rechazo la solicitud de productos.');
    }

    if (!preg_match('/"data"\s*:\s*\[/', $json, $dataMatch, PREG_OFFSET_CAPTURE)) {
        throw new UnexpectedValueException('La fuente remota no devolvio una lista de productos.');
    }

    $position = $dataMatch[0][1] + strlen($dataMatch[0][0]);
    $jsonLength = strlen($json);
    $count = 0;
    $arrayClosed = false;

    while ($position < $jsonLength) {
        while ($position < $jsonLength && ord($json[$position]) <= 32) {
            $position++;
        }

        if ($position >= $jsonLength) {
            break;
        }

        $char = $json[$position];
        if ($char === ']') {
            $arrayClosed = true;
            $position++;
            break;
        }

        if ($char !== '{') {
            throw new UnexpectedValueException('La lista remota contiene un producto no valido.');
        }

        $itemStart = $position;
        $depth = 0;
        $inString = false;
        $escaped = false;
        $itemEnd = null;

        for (; $position < $jsonLength; $position++) {
            $char = $json[$position];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === '}' || $char === ']') {
                $depth--;
                if ($depth === 0) {
                    $itemEnd = $position;
                    $position++;
                    break;
                }
            }
        }

        if ($itemEnd === null) {
            throw new UnexpectedValueException('La respuesta remota termino antes de completar un producto.');
        }

        $itemJson = substr($json, $itemStart, $itemEnd - $itemStart + 1);
        $product = json_decode($itemJson, true);
        if (!is_array($product) || json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException('La fuente remota devolvio un producto con JSON no valido.');
        }

        $onProduct($product, $count, $itemStart, $itemEnd - $itemStart + 1);
        $count++;

        while ($position < $jsonLength && ord($json[$position]) <= 32) {
            $position++;
        }

        if ($position >= $jsonLength) {
            break;
        }

        $char = $json[$position];
        if ($char === ']') {
            $arrayClosed = true;
            $position++;
            break;
        }

        if ($char !== ',') {
            throw new UnexpectedValueException('La lista remota tiene una separacion de productos no valida.');
        }

        $position++;
    }

    if (!$arrayClosed) {
        throw new UnexpectedValueException('La lista remota de productos esta incompleta.');
    }

    $tail = substr($json, $position);
    if (trim($tail) !== '}') {
        throw new UnexpectedValueException('La fuente remota devolvio una estructura JSON inesperada.');
    }

    return $count;
}

function obtenerTamanoStream($stream): int
{
    if (!is_resource($stream)) {
        return 0;
    }

    $stats = fstat($stream);
    return is_array($stats) ? (int) ($stats['size'] ?? 0) : 0;
}

function cargarIndiceProductosStream(string $cacheFile, int $sourceSize): ?array
{
    if ($cacheFile === '' || $sourceSize <= 0) {
        return null;
    }

    $indexFile = $cacheFile . '.index.json';
    if (!is_file($indexFile) || !is_readable($indexFile) || !is_file($cacheFile)) {
        return null;
    }

    $indexModifiedAt = @filemtime($indexFile);
    $sourceModifiedAt = @filemtime($cacheFile);
    if (
        $indexModifiedAt === false
        || $sourceModifiedAt === false
        || $indexModifiedAt < $sourceModifiedAt
    ) {
        return null;
    }

    $index = obtenerDatosCacheados($indexFile, PHP_INT_MAX);
    if (
        !is_array($index)
        || empty($index['success'])
        || (int) ($index['source_size'] ?? 0) !== $sourceSize
        || !isset($index['items'])
        || !is_array($index['items'])
    ) {
        return null;
    }

    return $index;
}

function construirIndiceProductosStream($stream): array
{
    $items = [];
    $minimumPrice = null;
    $maximumPrice = null;

    $total = procesarProductosDesdeJsonStream(
        $stream,
        static function (
            array $producto,
            int $index,
            int $offset,
            int $length
        ) use (&$items, &$minimumPrice, &$maximumPrice): void {
            $price = (float) ($producto['precio_venta'] ?? 0);
            $minimumPrice = $minimumPrice === null ? $price : min($minimumPrice, $price);
            $maximumPrice = $maximumPrice === null ? $price : max($maximumPrice, $price);

            $items[] = [
                'offset' => $offset,
                'length' => $length,
                'idarticulo' => (string) ($producto['idarticulo'] ?? ''),
                'price' => $price,
            ];
        }
    );

    return [
        'success' => true,
        'source_size' => obtenerTamanoStream($stream),
        'total' => $total,
        'minimum_price' => $minimumPrice,
        'maximum_price' => $maximumPrice,
        'items' => $items,
    ];
}

function guardarIndiceProductosStream(string $cacheFile, array $index): void
{
    if ($cacheFile === '') {
        return;
    }

    guardarDatosCacheados($cacheFile . '.index.json', $index);
}

function filtrarIndiceProductosStream(
    array $items,
    string $selectedIdArticulo,
    ?float $selectedPriceMin,
    ?float $selectedPriceMax
): array {
    return array_values(array_filter(
        $items,
        static function (array $item) use (
            $selectedIdArticulo,
            $selectedPriceMin,
            $selectedPriceMax
        ): bool {
            if (
                $selectedIdArticulo !== ''
                && (string) ($item['idarticulo'] ?? '') !== $selectedIdArticulo
            ) {
                return false;
            }

            $price = (float) ($item['price'] ?? 0);
            if ($selectedPriceMin !== null && $price < $selectedPriceMin) {
                return false;
            }

            if ($selectedPriceMax !== null && $price > $selectedPriceMax) {
                return false;
            }

            return true;
        }
    ));
}

function leerProductosDesdeIndice($stream, array $items): array
{
    $products = [];

    foreach ($items as $item) {
        $offset = (int) ($item['offset'] ?? -1);
        $length = (int) ($item['length'] ?? 0);
        if ($offset < 0 || $length <= 0 || fseek($stream, $offset) !== 0) {
            throw new UnexpectedValueException('No fue posible ubicar un producto en la respuesta remota.');
        }

        $itemJson = fread($stream, $length);
        if ($itemJson === false || strlen($itemJson) !== $length) {
            throw new UnexpectedValueException('No fue posible leer un producto completo de la respuesta remota.');
        }

        $product = json_decode($itemJson, true);
        if (!is_array($product) || json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException('Un producto indexado contiene JSON no valido.');
        }

        $products[] = agregarPresentacionesNormalizadas($product);
    }

    return $products;
}

function responderMenosDe100Paginado(
    array $sourceConfig,
    int $requestedPage,
    int $perPage,
    ?float $selectedPriceMin,
    ?float $selectedPriceMax,
    string $selectedIdArticulo
): void {
    $cacheFile = obtenerRutaCache('menosde100_remoto', (array) $sourceConfig['payload']);
    $context = abrirRespuestaRemotaComoStream(
        (string) $sourceConfig['url'],
        (array) $sourceConfig['payload'],
        $cacheFile
    );

    if (empty($context['success']) || !isset($context['stream'])) {
        responderJson([
            'success' => false,
            'message' => (string) ($context['message'] ?? 'No fue posible cargar los productos.'),
            'data' => [],
        ], 502);
    }

    $stream = $context['stream'];
    $cacheFile = (string) ($context['cache_file'] ?? '');
    $sourceSize = obtenerTamanoStream($stream);
    $index = cargarIndiceProductosStream($cacheFile, $sourceSize);
    $indexWasBuilt = false;
    $cacheIsValid = false;

    try {
        if ($index === null) {
            $index = construirIndiceProductosStream($stream);
            $indexWasBuilt = true;
        }

        $filteredItems = filtrarIndiceProductosStream(
            (array) $index['items'],
            $selectedIdArticulo,
            $selectedPriceMin,
            $selectedPriceMax
        );
        $totalProducts = count($filteredItems);
        $totalPages = $totalProducts > 0 ? (int) ceil($totalProducts / $perPage) : 1;
        $currentPage = min($requestedPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $pageProducts = leerProductosDesdeIndice(
            $stream,
            array_slice($filteredItems, $offset, $perPage)
        );

        $cacheIsValid = true;
    } catch (Throwable $exception) {
        registrarErrorProductos(
            get_class($exception) . ' al procesar menos de 100: ' . $exception->getMessage()
        );
        cerrarRespuestaRemotaStream($context, false);

        responderJson([
            'success' => false,
            'message' => 'La fuente remota devolvio una respuesta de productos no valida.',
            'data' => [],
        ], 502);
    }

    cerrarRespuestaRemotaStream($context, $cacheIsValid);

    if ($indexWasBuilt && $cacheIsValid) {
        guardarIndiceProductosStream($cacheFile, $index);
    }

    $minimumPrice = $index['minimum_price'] ?? null;
    $maximumPrice = $index['maximum_price'] ?? null;
    if ($minimumPrice === null || $maximumPrice === null) {
        $priceRange = ['min' => 0, 'max' => 100];
    } else {
        $min = (int) floor($minimumPrice / 10) * 10;
        $max = (int) ceil($maximumPrice / 10) * 10;
        $priceRange = [
            'min' => $min,
            'max' => $min === $max ? $max + 100 : $max,
        ];
    }

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
            'mode' => 'menosde100',
            'tipoBusqueda' => null,
        ],
        'filters' => [
            'price_range' => $priceRange,
            'selected_price_min' => $selectedPriceMin,
            'selected_price_max' => $selectedPriceMax,
        ],
    ]);
}

function normalizarTextoBusqueda($value): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strtr($text, [
        'Á' => 'A',
        'À' => 'A',
        'Â' => 'A',
        'Ä' => 'A',
        'Ã' => 'A',
        'á' => 'a',
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'ã' => 'a',
        'É' => 'E',
        'È' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'Í' => 'I',
        'Ì' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'Ó' => 'O',
        'Ò' => 'O',
        'Ô' => 'O',
        'Ö' => 'O',
        'Õ' => 'O',
        'ó' => 'o',
        'ò' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'õ' => 'o',
        'Ú' => 'U',
        'Ù' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'Ñ' => 'N',
        'ñ' => 'n',
    ]);
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

    if (function_exists('iconv')) {
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($transliterated !== false) {
            $text = $transliterated;
        }
    }

    $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? '';
    $text = preg_replace('/\s+/', ' ', $text) ?? '';

    return trim(strtolower($text));
}

function obtenerTokensBusqueda(string $search): array
{
    $normalized = normalizarTextoBusqueda($search);
    if ($normalized === '') {
        return [];
    }

    $stopWords = array_flip([
        'a', 'al', 'con', 'de', 'del', 'el', 'en', 'la', 'las', 'los', 'para', 'por', 'un', 'una', 'y',
    ]);

    $tokens = [];
    foreach (explode(' ', $normalized) as $token) {
        if ($token === '' || isset($stopWords[$token])) {
            continue;
        }

        if (strlen($token) < 2) {
            continue;
        }

        $tokens[$token] = true;
    }

    if (empty($tokens)) {
        foreach (explode(' ', $normalized) as $token) {
            if ($token !== '') {
                $tokens[$token] = true;
            }
        }
    }

    return array_keys($tokens);
}

function obtenerClaveProductoBusqueda(array $producto): string
{
    $id = trim((string) ($producto['idarticulo'] ?? ''));
    if ($id !== '') {
        return 'id:' . $id;
    }

    $codigo = normalizarTextoBusqueda($producto['codigo'] ?? '');
    if ($codigo !== '') {
        return 'codigo:' . $codigo;
    }

    return 'producto:' . md5(json_encode([
        $producto['nombre'] ?? '',
        $producto['precio_venta'] ?? '',
        $producto['imagen'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function agregarProductosUnicosBusqueda(array &$productosPorClave, array $productos): void
{
    foreach ($productos as $producto) {
        if (!is_array($producto)) {
            continue;
        }

        $clave = obtenerClaveProductoBusqueda($producto);
        if (!isset($productosPorClave[$clave])) {
            $productosPorClave[$clave] = $producto;
        }
    }
}

function consultarBusquedaRemotaPorCampos(string $url, array $payload, array $tiposBusqueda): array
{
    $tiposBusqueda = array_values(array_unique(array_filter(array_map('normalizarTipoBusqueda', $tiposBusqueda))));
    $tiposBusqueda = array_values(array_diff($tiposBusqueda, ['general']));

    if (empty($tiposBusqueda)) {
        return consultarApiRemota($url, $payload);
    }

    if (count($tiposBusqueda) === 1) {
        $payload['tipoBusqueda'] = $tiposBusqueda[0];
        return consultarApiRemota($url, $payload);
    }

    $multiHandle = curl_multi_init();
    $handles = [];

    foreach ($tiposBusqueda as $tipoBusqueda) {
        $payloadCampo = $payload;
        $payloadCampo['tipoBusqueda'] = $tipoBusqueda;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payloadCampo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10,
        ]);

        $handles[] = $ch;
        curl_multi_add_handle($multiHandle, $ch);
    }

    do {
        $status = curl_multi_exec($multiHandle, $active);
        if ($active) {
            curl_multi_select($multiHandle, 1.0);
        }
    } while ($active && $status === CURLM_OK);

    $productosPorClave = [];
    $consultasExitosas = 0;

    foreach ($handles as $ch) {
        $decoded = json_decode((string) curl_multi_getcontent($ch), true);
        if (!empty($decoded['success']) && isset($decoded['data']) && is_array($decoded['data'])) {
            $consultasExitosas++;
            agregarProductosUnicosBusqueda($productosPorClave, $decoded['data']);
        }

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);

    if ($consultasExitosas === 0) {
        return [
            'success' => false,
            'message' => 'No fue posible consultar la búsqueda remota.',
            'data' => [],
        ];
    }

    return [
        'success' => true,
        'data' => array_values($productosPorClave),
    ];
}

function obtenerValorProductoBusqueda(array $producto, array $keys): string
{
    $valores = [];

    foreach ($keys as $key) {
        if (!array_key_exists($key, $producto) || !is_scalar($producto[$key])) {
            continue;
        }

        $normalizado = normalizarTextoBusqueda($producto[$key]);
        if ($normalizado !== '' && $normalizado !== '0') {
            $valores[$normalizado] = true;
        }
    }

    return trim(implode(' ', array_keys($valores)));
}

function obtenerTextoDinamicoProductoBusqueda(array $producto): string
{
    $valores = [];

    foreach ($producto as $key => $value) {
        if (!is_scalar($value)) {
            continue;
        }

        if (!preg_match('/(autor|author|editorial|isbn|barra|proveedor|marca|categoria|descripcion|nombre|titulo|sku|codigo|cod)/i', (string) $key)) {
            continue;
        }

        $normalizado = normalizarTextoBusqueda($value);
        if ($normalizado !== '' && $normalizado !== '0') {
            $valores[$normalizado] = true;
        }
    }

    return trim(implode(' ', array_keys($valores)));
}

function obtenerTextoProductoBusqueda(array $producto): array
{
    $fieldGroups = [
        'idarticulo' => ['idarticulo', 'id_articulo', 'articulo_id', 'id'],
        'codigo' => ['codigo', 'codarticulo', 'cod_articulo', 'codigo_articulo', 'codigobarra', 'codigo_barra', 'codigobarras', 'isbn', 'isbn13'],
        'nombre' => ['nombre', 'titulo', 'title', 'nombre_articulo', 'nombrearticulo', 'producto'],
        'descripcion' => ['descripcion', 'description', 'detalle', 'sinopsis', 'resumen'],
        'categoria' => ['categoria', 'nombrecategoria', 'nom_categoria', 'categoria_nombre', 'nombre_categoria'],
        'marca' => ['marca', 'editorial_marca', 'nombremarca', 'nombre_marca', 'marca_nombre'],
        'editorial' => ['editorial', 'editorial_marca', 'nombreeditorial', 'nombre_editorial', 'editorial_nombre', 'publisher'],
        'autor' => ['autor', 'author', 'autores', 'authors', 'nombreautor', 'nombre_autor', 'autor_nombre', 'autorlibro', 'autor_libro', 'escritor'],
        'sku' => ['sku', 'referencia', 'ref'],
        'extra' => [],
    ];

    $normalizados = [];
    foreach ($fieldGroups as $campo => $keys) {
        $normalizados[$campo] = $campo === 'extra'
            ? obtenerTextoDinamicoProductoBusqueda($producto)
            : obtenerValorProductoBusqueda($producto, $keys);
    }

    $texto = trim(implode(' ', array_filter($normalizados)));
    $palabras = $texto === '' ? [] : array_values(array_unique(explode(' ', $texto)));

    return [
        'campos' => $normalizados,
        'texto' => $texto,
        'palabras' => $palabras,
    ];
}

function palabraCoincideConToken(string $palabra, string $token): bool
{
    if ($palabra === '' || $token === '') {
        return false;
    }

    if (strpos($palabra, $token) !== false) {
        return true;
    }

    if (strlen($token) > 3 && substr($token, -1) === 's' && strpos($palabra, substr($token, 0, -1)) !== false) {
        return true;
    }

    if (strlen($palabra) > 3 && substr($palabra, -1) === 's' && strpos(substr($palabra, 0, -1), $token) !== false) {
        return true;
    }

    $tokenLength = strlen($token);
    if ($tokenLength >= 4) {
        $maxDistance = $tokenLength >= 7 ? 2 : 1;
        return levenshtein($token, $palabra) <= $maxDistance;
    }

    return false;
}

function productoContieneToken(string $texto, array $palabras, string $token): bool
{
    if ($token === '') {
        return false;
    }

    if (strpos($texto, $token) !== false) {
        return true;
    }

    foreach ($palabras as $palabra) {
        if (palabraCoincideConToken($palabra, $token)) {
            return true;
        }
    }

    return false;
}

function obtenerCampoBusquedaSeleccionado(array $campos, string $tipoBusqueda): string
{
    $tipoBusqueda = normalizarTipoBusqueda($tipoBusqueda);
    if ($tipoBusqueda === 'general') {
        $valores = [];
        foreach (['nombre', 'descripcion', 'autor', 'editorial', 'marca', 'codigo', 'categoria', 'extra'] as $campo) {
            $valor = trim((string) ($campos[$campo] ?? ''));
            if ($valor !== '') {
                $valores[$valor] = true;
            }
        }

        return trim(implode(' ', array_keys($valores)));
    }

    return (string) ($campos[$tipoBusqueda] ?? $campos['nombre'] ?? '');
}

function campoSeleccionadoCoincide(string $campo, string $normalizedSearch, array $tokens): bool
{
    if ($campo === '' || $normalizedSearch === '') {
        return false;
    }

    $palabras = explode(' ', $campo);
    if (productoContieneToken($campo, $palabras, $normalizedSearch)) {
        return true;
    }

    foreach ($tokens as $token) {
        if (!productoContieneToken($campo, $palabras, $token)) {
            return false;
        }
    }

    return !empty($tokens);
}

function obtenerCoincidenciasExactasBusqueda(array $productos, string $search, string $tipoBusqueda = 'general'): array
{
    $normalizedSearch = normalizarTextoBusqueda($search);
    if ($normalizedSearch === '') {
        return [];
    }

    $exactas = [];
    foreach ($productos as $producto) {
        if (!is_array($producto)) {
            continue;
        }

        $textoProducto = obtenerTextoProductoBusqueda($producto);
        $campos = $textoProducto['campos'];
        $tipoNormalizado = normalizarTipoBusqueda($tipoBusqueda);
        if ($tipoNormalizado === 'general') {
            foreach (['nombre', 'descripcion', 'autor', 'editorial', 'marca', 'codigo', 'categoria'] as $campo) {
                if ((string) ($campos[$campo] ?? '') === $normalizedSearch) {
                    $exactas[] = $producto;
                    break;
                }
            }
        } else {
            $campoSeleccionado = obtenerCampoBusquedaSeleccionado($campos, $tipoNormalizado);
            if ($campoSeleccionado === $normalizedSearch) {
                $exactas[] = $producto;
            }
        }
    }

    return $exactas;
}

function puntuarProductoBusqueda(array $producto, string $normalizedSearch, array $tokens, array $preferredKeys, string $tipoBusqueda = 'general'): int
{
    $textoProducto = obtenerTextoProductoBusqueda($producto);
    $campos = $textoProducto['campos'];
    $tipoBusqueda = normalizarTipoBusqueda($tipoBusqueda);
    $texto = obtenerCampoBusquedaSeleccionado($campos, $tipoBusqueda);
    $palabras = $texto === '' ? [] : array_values(array_unique(explode(' ', $texto)));

    $score = isset($preferredKeys[obtenerClaveProductoBusqueda($producto)]) ? 25000 : 0;

    if ($texto === '') {
        return $score;
    }

    if (!campoSeleccionadoCoincide($texto, $normalizedSearch, $tokens)) {
        return isset($preferredKeys[obtenerClaveProductoBusqueda($producto)]) ? $score : 0;
    }

    if ($normalizedSearch !== '') {
        if ($texto === $normalizedSearch) {
            return 90000 + $score;
        }

        if (strpos($texto, $normalizedSearch) === 0) {
            $score += 50000;
        }

        if (strpos($texto, $normalizedSearch) !== false) {
            $score += 30000;
        }
    }

    if (empty($tokens)) {
        return $score;
    }

    $matched = 0;
    $fieldMatches = 0;

    foreach ($tokens as $token) {
        if (!productoContieneToken($texto, $palabras, $token)) {
            continue;
        }

        $matched++;
        $fieldMatches++;
    }

    $requiredMatches = count($tokens) >= 6
        ? count($tokens)
        : (count($tokens) <= 2 ? count($tokens) : (int) ceil(count($tokens) * 0.75));
    if ($matched < $requiredMatches && $score < 15000) {
        return 0;
    }

    $score += $matched * 1000;
    $score += $fieldMatches * 500;

    if ($matched === count($tokens)) {
        $score += 5000;
    }

    if ($fieldMatches === count($tokens)) {
        $score += 3000;
    }

    return $score;
}

function ordenarProductosPorBusqueda(array $productos, string $search, array $preferredKeys = [], string $tipoBusqueda = 'general'): array
{
    $normalizedSearch = normalizarTextoBusqueda($search);
    $tokens = obtenerTokensBusqueda($search);
    $scored = [];

    foreach ($productos as $producto) {
        if (!is_array($producto)) {
            continue;
        }

        $score = puntuarProductoBusqueda($producto, $normalizedSearch, $tokens, $preferredKeys, $tipoBusqueda);
        if ($score <= 0) {
            continue;
        }

        $scored[] = [
            'score' => $score,
            'nombre' => normalizarTextoBusqueda($producto['nombre'] ?? ''),
            'idarticulo' => (int) ($producto['idarticulo'] ?? 0),
            'producto' => $producto,
        ];
    }

    usort($scored, static function (array $a, array $b): int {
        if ($a['score'] !== $b['score']) {
            return $b['score'] <=> $a['score'];
        }

        if ($a['nombre'] !== $b['nombre']) {
            return $a['nombre'] <=> $b['nombre'];
        }

        return $b['idarticulo'] <=> $a['idarticulo'];
    });

    return array_column($scored, 'producto');
}

function consultarProductosPorCategorias(string $baseApi, array $idsCategorias): array
{
    $productos = [];
    $idsCategorias = array_values(array_unique(array_filter($idsCategorias)));

    foreach (array_chunk($idsCategorias, 8) as $chunk) {
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($chunk as $idCategoria) {
            $payload = [
                'idcategoria' => (string) $idCategoria,
                'idsucursal' => '4',
            ];
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseApi . 'api_tienda_articulos_listarProductosxCategoria.php',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 12,
            ]);

            $handles[] = $ch;
            curl_multi_add_handle($multiHandle, $ch);
        }

        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                curl_multi_select($multiHandle, 1.0);
            }
        } while ($active && $status === CURLM_OK);

        foreach ($handles as $ch) {
            $decoded = json_decode((string) curl_multi_getcontent($ch), true);
            if (!empty($decoded['success']) && isset($decoded['data']) && is_array($decoded['data'])) {
                foreach ($decoded['data'] as $producto) {
                    if (is_array($producto)) {
                        $productos[] = $producto;
                    }
                }
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }

    return $productos;
}

function obtenerCatalogoCompletoBusqueda(string $baseApi): array
{
    $cacheFile = obtenerRutaCache('catalogo_busqueda_completo', []);
    $cached = obtenerDatosCacheados($cacheFile, PRODUCTOS_SEARCH_CATALOG_TTL);

    if (!empty($cached['success']) && isset($cached['data']) && is_array($cached['data'])) {
        return array_values(array_filter($cached['data'], 'is_array'));
    }

    $productosPorClave = [];

    $productosNuevos = consultarApiRemota($baseApi . 'api_tienda_articulos_listarProductosnuevos.php', []);
    if (!empty($productosNuevos['success']) && isset($productosNuevos['data']) && is_array($productosNuevos['data'])) {
        agregarProductosUnicosBusqueda($productosPorClave, $productosNuevos['data']);
    }

    if (count($productosPorClave) < 200) {
        $categorias = consultarApiRemota($baseApi . 'api_tienda_categorias_listar.php', []);
        $idsCategorias = [];

        if (!empty($categorias['success']) && isset($categorias['data']) && is_array($categorias['data'])) {
            foreach ($categorias['data'] as $categoria) {
                if (!is_array($categoria)) {
                    continue;
                }

                $idCategoria = trim((string) ($categoria['idcategoria'] ?? ''));
                $activa = !isset($categoria['condicion']) || (int) $categoria['condicion'] === 1;
                if ($activa && preg_match('/^\d{1,10}$/', $idCategoria)) {
                    $idsCategorias[] = $idCategoria;
                }
            }
        }

        agregarProductosUnicosBusqueda($productosPorClave, consultarProductosPorCategorias($baseApi, $idsCategorias));
    }

    $catalogo = array_values($productosPorClave);
    guardarDatosCacheados($cacheFile, [
        'success' => true,
        'data' => $catalogo,
    ]);

    return $catalogo;
}

function construirResultadosBusquedaCompleta(string $baseApi, string $search, array $directApiData, string $tipoBusqueda = 'general'): array
{
    $productosPorClave = [];
    $preferredKeys = [];

    if (!empty($directApiData['success']) && isset($directApiData['data']) && is_array($directApiData['data'])) {
        agregarProductosUnicosBusqueda($productosPorClave, $directApiData['data']);

        foreach ($directApiData['data'] as $producto) {
            if (is_array($producto)) {
                $preferredKeys[obtenerClaveProductoBusqueda($producto)] = true;
            }
        }
    }

    $productosDirectos = array_values($productosPorClave);
    if (!empty($productosDirectos)) {
        $exactasDirectas = obtenerCoincidenciasExactasBusqueda($productosDirectos, $search, $tipoBusqueda);
        $ordenadosDirectos = !empty($exactasDirectas)
            ? $exactasDirectas
            : ordenarProductosPorBusqueda($productosDirectos, $search, $preferredKeys, $tipoBusqueda);

        return !empty($ordenadosDirectos) ? $ordenadosDirectos : $productosDirectos;
    }

    agregarProductosUnicosBusqueda($productosPorClave, obtenerCatalogoCompletoBusqueda($baseApi));

    $productos = array_values($productosPorClave);
    $exactas = obtenerCoincidenciasExactasBusqueda($productos, $search, $tipoBusqueda);

    return !empty($exactas)
        ? $exactas
        : ordenarProductosPorBusqueda($productos, $search, $preferredKeys, $tipoBusqueda);
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
$selectedIdArticulo = trim((string) ($input['idarticulo'] ?? ''));

if (($sourceConfig['mode'] ?? '') === 'menosde100') {
    responderMenosDe100Paginado(
        $sourceConfig,
        $requestedPage,
        $perPage,
        $selectedPriceMin,
        $selectedPriceMax,
        $selectedIdArticulo
    );
}

if (($sourceConfig['mode'] ?? '') === 'busqueda') {
    $search = (string) ($sourceConfig['payload']['search'] ?? '');
    $tipoBusqueda = (string) ($sourceConfig['payload']['tipoBusqueda'] ?? 'general');
    $remotePayload = (array) $sourceConfig['payload'];
    $directApiData = normalizarTipoBusqueda($tipoBusqueda) === 'general'
        ? consultarBusquedaRemotaPorCampos(
            (string) $sourceConfig['url'],
            $remotePayload,
            ['nombre', 'descripcion', 'autor', 'editorial', 'marca']
        )
        : consultarApiRemota((string) $sourceConfig['url'], $remotePayload);
    $resultadosBusqueda = construirResultadosBusquedaCompleta($BASE_API, $search, $directApiData, $tipoBusqueda);

    $apiData = [
        'success' => true,
        'data' => $resultadosBusqueda,
    ];
} else {
    $cacheFile = obtenerRutaCache((string) $sourceConfig['mode'], (array) $sourceConfig['payload']);
    $apiData = obtenerDatosCacheados($cacheFile);

    if ($apiData === null) {
        $apiData = consultarApiRemota((string) $sourceConfig['url'], (array) $sourceConfig['payload']);
        if (!empty($apiData['success'])) {
            guardarDatosCacheados($cacheFile, $apiData);
        }
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
$productos = filtrarProductosPorIdArticulo($productos, $selectedIdArticulo);

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
$pageProducts = array_map('agregarPresentacionesNormalizadas', $pageProducts);
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
        'tipoBusqueda' => $sourceConfig['payload']['tipoBusqueda'] ?? null,
    ],
    'filters' => [
        'price_range' => $priceRange,
        'selected_price_min' => $selectedPriceMin,
        'selected_price_max' => $selectedPriceMax,
    ],
]);
