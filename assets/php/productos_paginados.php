<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

const PRODUCTOS_CACHE_TTL = 120;
const PRODUCTOS_SEARCH_CATALOG_TTL = 600;
const PRODUCTOS_DEFAULT_PER_PAGE = 30;
const PRODUCTOS_MAX_PER_PAGE = 60;

require_once __DIR__ . '/rutas.php';

function responderJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
            $search = preg_replace('/[\x00-\x1F\x7F]/u', '', $search);
            return [
            'mode' => $mode,
            'url' => $baseApi . 'api_tienda_articulos_listarProductosxSearch.php',
            'payload' => [
                'search' => function_exists('mb_substr') ? mb_substr($search, 0, 80, 'UTF-8') : substr($search, 0, 80),
                'idsucursal' => preg_match('/^\d{1,10}$/', $idSucursal) ? $idSucursal : '4',
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
        mkdir($cacheDir, 0777, true);
    }

    $cacheKey = md5(json_encode([
        'mode' => $mode,
        'payload' => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
}

function obtenerDatosCacheados(string $cacheFile, int $ttl = PRODUCTOS_CACHE_TTL): ?array
{
    if (!is_file($cacheFile)) {
        return null;
    }

    $age = time() - (int) filemtime($cacheFile);
    if ($age > $ttl) {
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

function filtrarProductosPorIdArticulo(array $productos, string $idArticulo): array
{
    if ($idArticulo === '' || !preg_match('/^[0-9]+$/', $idArticulo)) {
        return $productos;
    }

    return array_values(array_filter($productos, static function (array $producto) use ($idArticulo): bool {
        return (string) ($producto['idarticulo'] ?? '') === $idArticulo;
    }));
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
        'marca' => ['marca', 'nombremarca', 'nombre_marca', 'marca_nombre'],
        'editorial' => ['editorial', 'nombreeditorial', 'nombre_editorial', 'editorial_nombre', 'publisher'],
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

function obtenerCoincidenciasExactasBusqueda(array $productos, string $search): array
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

        if (
            $campos['idarticulo'] === $normalizedSearch
            || $campos['codigo'] === $normalizedSearch
            || $campos['nombre'] === $normalizedSearch
            || $campos['autor'] === $normalizedSearch
            || $campos['editorial'] === $normalizedSearch
        ) {
            $exactas[] = $producto;
        }
    }

    return $exactas;
}

function puntuarProductoBusqueda(array $producto, string $normalizedSearch, array $tokens, array $preferredKeys): int
{
    $textoProducto = obtenerTextoProductoBusqueda($producto);
    $campos = $textoProducto['campos'];
    $texto = $textoProducto['texto'];
    $palabras = $textoProducto['palabras'];

    if ($texto === '') {
        return 0;
    }

    $score = isset($preferredKeys[obtenerClaveProductoBusqueda($producto)]) ? 25000 : 0;

    if ($normalizedSearch !== '') {
        if ($campos['idarticulo'] === $normalizedSearch || $campos['codigo'] === $normalizedSearch) {
            return 100000 + $score;
        }

        if ($campos['nombre'] === $normalizedSearch) {
            return 90000 + $score;
        }

        if ($campos['autor'] !== '' && $campos['autor'] === $normalizedSearch) {
            return 88000 + $score;
        }

        if ($campos['editorial'] !== '' && $campos['editorial'] === $normalizedSearch) {
            return 82000 + $score;
        }

        if ($campos['codigo'] !== '' && strpos($campos['codigo'], $normalizedSearch) === 0) {
            $score += 60000;
        }

        if ($campos['idarticulo'] !== '' && strpos($campos['idarticulo'], $normalizedSearch) === 0) {
            $score += 55000;
        }

        if ($campos['nombre'] !== '' && strpos($campos['nombre'], $normalizedSearch) === 0) {
            $score += 50000;
        }

        if ($campos['nombre'] !== '' && strpos($campos['nombre'], $normalizedSearch) !== false) {
            $score += 30000;
        } elseif ($campos['autor'] !== '' && strpos($campos['autor'], $normalizedSearch) !== false) {
            $score += 28000;
        } elseif ($campos['editorial'] !== '' && strpos($campos['editorial'], $normalizedSearch) !== false) {
            $score += 22000;
        } elseif (strpos($texto, $normalizedSearch) !== false) {
            $score += 15000;
        }
    }

    if (empty($tokens)) {
        return $score;
    }

    $matched = 0;
    $nameMatches = 0;
    $codeMatches = 0;
    $categoryMatches = 0;
    $authorMatches = 0;
    $editorialMatches = 0;

    foreach ($tokens as $token) {
        if (!productoContieneToken($texto, $palabras, $token)) {
            continue;
        }

        $matched++;

        if (productoContieneToken($campos['nombre'], $campos['nombre'] === '' ? [] : explode(' ', $campos['nombre']), $token)) {
            $nameMatches++;
        }

        if (productoContieneToken($campos['codigo'], $campos['codigo'] === '' ? [] : explode(' ', $campos['codigo']), $token)) {
            $codeMatches++;
        }

        if (productoContieneToken($campos['categoria'], $campos['categoria'] === '' ? [] : explode(' ', $campos['categoria']), $token)) {
            $categoryMatches++;
        }

        if (productoContieneToken($campos['autor'], $campos['autor'] === '' ? [] : explode(' ', $campos['autor']), $token)) {
            $authorMatches++;
        }

        if (productoContieneToken($campos['editorial'], $campos['editorial'] === '' ? [] : explode(' ', $campos['editorial']), $token)) {
            $editorialMatches++;
        }
    }

    $requiredMatches = count($tokens) >= 6
        ? count($tokens)
        : (count($tokens) <= 2 ? count($tokens) : (int) ceil(count($tokens) * 0.75));
    if ($matched < $requiredMatches && $score < 15000) {
        return 0;
    }

    $score += $matched * 1000;
    $score += $nameMatches * 350;
    $score += $codeMatches * 500;
    $score += $categoryMatches * 150;
    $score += $authorMatches * 650;
    $score += $editorialMatches * 300;

    if ($matched === count($tokens)) {
        $score += 5000;
    }

    if ($nameMatches === count($tokens)) {
        $score += 3000;
    }

    if ($authorMatches === count($tokens)) {
        $score += 4500;
    }

    return $score;
}

function ordenarProductosPorBusqueda(array $productos, string $search, array $preferredKeys = []): array
{
    $normalizedSearch = normalizarTextoBusqueda($search);
    $tokens = obtenerTokensBusqueda($search);
    $scored = [];

    foreach ($productos as $producto) {
        if (!is_array($producto)) {
            continue;
        }

        $score = puntuarProductoBusqueda($producto, $normalizedSearch, $tokens, $preferredKeys);
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

function construirResultadosBusquedaCompleta(string $baseApi, string $search, array $directApiData): array
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

    agregarProductosUnicosBusqueda($productosPorClave, obtenerCatalogoCompletoBusqueda($baseApi));

    $productos = array_values($productosPorClave);
    $exactas = obtenerCoincidenciasExactasBusqueda($productos, $search);

    return !empty($exactas)
        ? $exactas
        : ordenarProductosPorBusqueda($productos, $search, $preferredKeys);
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

if (($sourceConfig['mode'] ?? '') === 'busqueda') {
    $search = (string) ($sourceConfig['payload']['search'] ?? '');
    $directApiData = consultarApiRemota((string) $sourceConfig['url'], (array) $sourceConfig['payload']);
    $resultadosBusqueda = construirResultadosBusquedaCompleta($BASE_API, $search, $directApiData);

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
