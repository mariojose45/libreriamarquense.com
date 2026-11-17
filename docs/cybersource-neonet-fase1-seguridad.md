# CyberSource / NeoNet: seguridad Fase 1 (TEST)

## Alcance y limites

Se modifico la copia local, sin migrar ambiente, usar credenciales productivas,
modificar MarquenseLIVE ni realizar pagos o altas reales de pedidos. No se
modifico `config/cybersource.private.php`, ni se eliminaron registros de clientes.
Las pruebas usan almacenamiento temporal propio y no cargan configuracion privada.

No desplegar aisladamente un solo controlador: las clases y controladores de esta
fase comparten esquema de sesiones y deben revisarse como un conjunto.

## Archivos y responsabilidades

| Archivo | Correccion |
| --- | --- |
| `integrations/cybersource/src/SecureAcceptanceService.php` | HMAC SHA-256 Base64 con comparacion constante; esquema de respuesta; campos criticos obligatoriamente firmados; correlacion con sesion; UUID de 32 caracteres. `validateResponse` exige la sesion confiable. `hasValidResponseSignature` solo autentica el sobre, no autoriza un pedido. |
| `integrations/cybersource/src/Money.php` | Importes exactos en centavos; rechaza formatos ambiguos, exponentes, negativos y decimales adicionales. |
| `integrations/cybersource/src/CatalogPriceValidator.php` | Consulta el mismo catalogo por ID que utiliza la tienda; valida producto/presentacion/precio y reconstruye las representaciones del detalle. No confia en autorizaciones ni metadatos de precios del navegador. TLS obligatorio. |
| `integrations/cybersource/src/OrderPayloadValidator.php` | Aplica catalogo al checkout de tarjeta; conserva el validador existente para otros medios de pago. |
| `integrations/cybersource/src/CheckoutCoordinator.php` | Deduplica solicitudes de la misma compra/navegador; reserva un solo intento por referencia; no reabre sesiones pagadas, enviadas, canceladas o ya iniciadas. |
| `integrations/cybersource/src/PaymentSessionStore.php` | Bloqueos por operacion/referencia; revisiones contra escrituras obsoletas; JSON temporal con escritura comprobada, flush, fsync cuando existe, permisos restrictivos y rename atomico. Indices persistentes de compra y transaccion. |
| `integrations/cybersource/src/PaymentStorageException.php` | Distingue fallos de almacenamiento/bloqueo para responder 503, no confirmar eventos no guardados. |
| `integrations/cybersource/src/SecureAcceptanceNotification.php` | Parseo estricto de formulario, rechazo de parametros duplicados/arrays y limite de cuerpo. Guarda solo datos autenticados necesarios para conciliar. Un ID de proveedor no puede asociarse a dos referencias. |
| `integrations/cybersource/src/OrderFulfillment.php` | Unico coordinador de registro y correo. Guarda la intencion de envio antes de HTTP. Ante resultado ambiguo no reenvia automaticamente. Guarda la confirmacion funcional antes del correo. |
| `integrations/cybersource/src/OrderForwarder.php` | Para pagos, exige `success === true` e `idcotizacion` positivo; HTTP 2xx no basta. TLS verificado, sin redirecciones ni reintentos automaticos. Conserva el contrato anterior para llamadas no pagadas. |
| `integrations/cybersource/src/SensitiveData.php`, `SecureLogger.php` | Redacta claves/campos de tarjeta con prefijos `req_`, datos personales y cuerpos completos en logs. Conserva los datos del pedido en almacenamiento protegido para poder procesarlo. |
| `api/cybersource/create_checkout.php` | Verificacion de catalogo, GTQ y deduplicacion por compra canonica; mantiene CSRF y limites existentes. Solo inicia Secure Acceptance, sin fallback generico. |
| `api/cybersource/secure_acceptance_redirect.php` | Reserva atomica del intento antes de servir el formulario. Nunca genera un nuevo UUID para una referencia ya utilizada. |
| `api/cybersource/payment_return.php` | Acepta unicamente POST firmado de formulario; mismo verificador que Merchant POST. Informa por separado pago aprobado y pedido pendiente, sin afirmar un registro inexistente. |
| `api/cybersource/payment_webhook.php` | Acepta `application/x-www-form-urlencoded`; elimina el bypass por token administrativo/JSON. Persiste y responde sin esperar a la API de pedidos o al correo. |
| `api/cybersource/_bootstrap.php` | Elimina validaciones monetarias permisivas y autorizaciones extraidas de datos no confiables; delega finalizacion al coordinador unico. |
| `integrations/cybersource/process_pending.php` | Procesador durable CLI, inaccesible via HTTP y con guarda de ambiente TEST. Recupera confirmaciones que no tuvieron retorno del navegador. |
| `config/cybersource.php`, `config/cybersource.private.example.php` | Fuente publica del catalogo; cancelacion dirigida al receptor firmado. La plantilla sigue TEST; no se alteraron credenciales privadas. |
| `.gitignore` | Excluye bloqueos y temporales de almacenamiento, ademas de las sesiones existentes. |
| `integrations/cybersource/tests/fixtures.php`, `concurrent_worker.php`, `run.php` | Fixtures sinteticos, pruebas funcionales y carreras con procesos reales. Sin configuracion privada ni servicios reales. |
| `docs/cybersource-neonet-integracion.md` | Actualiza el flujo y separa la futura migracion productiva. |

## Regla de aprobacion

Se autentican `signed_field_names`, `signed_date_time`, `decision`, `reason_code`,
`req_reference_number`, `req_amount`, `req_currency`, `req_profile_id`,
`req_access_key`, `req_transaction_uuid` y `req_transaction_type`.
Para ACCEPT tambien se exigen `transaction_id`, `auth_amount` y `auth_code` firmados.
Los campos adicionales firmados se admiten sin depender de su orden; valores
opcionales ausentes en la lista firmada se representan vacios como en el protocolo.

Solo `decision=ACCEPT` junto con `reason_code=100` puede aprobar. Deben coincidir
importe solicitado e importe autorizado completos con la sesion, moneda GTQ,
referencia, perfil y UUID/tipo del intento. El codigo 100 nunca sustituye una
decision diferente. Autorizaciones parciales (110) no generan pedido automatico.
REVIEW y errores/duplicados del proveedor quedan para revision, no se convierten
en rechazos que autoricen reintentos a ciegas.

Una confirmacion firmada valida puede llegar despues de `expires_at`; el
vencimiento impide iniciar el intento, pero no borra un cobro ya realizado.
Un rechazo/cancelacion posterior no degrada PAID/ORDER_SENT. Una aprobacion tardia
del mismo intento puede conciliar DECLINED/CANCELLED/EXPIRED. Otro ID de cobro
para la misma referencia se registra en `payment_conflicts` para revision, sin
crear otro pedido. Firmas invalidas no modifican sesiones.

## API externa: verificado y no verificado

El archivo de respuesta de Marquense proporcionado para la auditoria confirma el
contrato observado: `success: true` e `idcotizacion` positivo (por ejemplo 120).
No se encontro el codigo fuente de la API exacta `libremarquenseDos` en esta
copia. Implementaciones de otras tiendas no prueban su comportamiento.
No se encontro documentacion que confirme claves de idempotencia, consulta por
referencia, transacciones atomicas del backend o recuperacion tras respuesta perdida.
Se solicito esa informacion; no se invento un encabezado `Idempotency-Key`.

Por eso el control local es de envio unico conservador, NO una garantia distribuida
de "exactamente una vez":

- Antes de llamar la API se persiste `order_dispatch.state=SENDING`.
- Una respuesta funcional positiva permite `CONFIRMED` y `status=ORDER_SENT`.
- Error, timeout, JSON invalido, HTTP fallido o rechazo funcional dejan el pago
  confirmado y el envio en UNKNOWN para conciliacion, sin correo de exito.
- Un proceso interrumpido que dejo SENDING tampoco se reenvia automaticamente.
- Otra notificacion del proveedor no reinicia ese marcador.
- El correo se reclama/persiste por separado. Fallos o SENDING/UNKNOWN requieren
  revision; no vuelven a enviar el pedido ni duplican el correo automaticamente.
- `mail()` confirma aceptacion local, no entrega final al buzon del cliente.

Para conciliar, consultar primero el backend con SistemasOL usando ID de
transaccion/autorizacion, fecha, importe y cliente. Si existe, obtener el
`idcotizacion` y vincularlo bajo bloqueo con evidencia. Si no existe, exigir
confirmacion del backend antes de habilitar un nuevo envio controlado. No borrar
JSON ni marcadores como mecanismo de reintento. No hay un boton ni endpoint
publico nuevo para saltarse este control.

## Configuracion manual previa a probar el despliegue TEST

1. Mantener `environment=test`, perfil y claves TEST. No tocar MarquenseLIVE.
2. Revisar solo los campos privados: `enabled`, `currency=GTQ`,
   `secure_acceptance.enabled/profile_id/access_key/secret_key/test_endpoint`,
   `transaction_type`, `site.base_url/return_path`, `paths`, `external_order_api`,
   `catalog_api` y `order_email`. No compartir sus valores secretos.
3. En el perfil TEST, Customer Response > Hosted By You debe recibir POST en
   `https://libreriamarquense.com/api/cybersource/payment_return.php` cuando ese
   sea el host de despliegue. Usar el mismo receptor para Custom Cancel Response.
   El formulario firma ambos override hacia `site.base_url + site.return_path`.
4. Merchant POST URL del perfil TEST:
   `https://libreriamarquense.com/api/cybersource/payment_webhook.php`.
   No requiere sesion del navegador, CSRF ni token administrativo; requiere HMAC.
5. Comprobar HTTPS, certificados CA para cURL/OpenSSL, reloj UTC sincronizado y
   que el servidor/WAF permita el POST del proveedor sin redireccion ni login.
   La existencia local de la ruta NO demuestra que el servidor publicado la acepte.
6. Revisar mapeo de catalogo con muestras reales: `success/data/idarticulo`,
   `presentaciones` y campos `nombre_01..20/precio_01..20`. Una presentacion/precio
   ambiguo o falta de disponibilidad del catalogo bloquean iniciar el pago.
   Se conserva la marca de reserva por stock; no se implemento reserva atomica
   de inventario entre tiendas/cajas.
7. Crear un cron TEST, tras revisar el destino de la API de pedidos, que invoque
   `<php-cli> <raiz-tienda>/integrations/cybersource/process_pending.php` cada
   minuto. NO fue instalado ni ejecutado contra la configuracion real en esta fase.
   Aunque CyberSource sea TEST, la API externa configurada podria registrar
   cotizaciones reales: el cron no es una prueba inocua. Coordinar datos/destino
   de ensayo con SistemasOL antes de activarlo.
8. Mantener sesiones/indices/bloqueos en el MISMO almacenamiento local persistente
   compartido por todos los procesos PHP de esta tienda. `flock` no es un bloqueo
   distribuido para varias replicas/NFS. Para ese despliegue se requiere una base
   transaccional/cola compartida antes de habilitar pagos.
9. Revisar permisos/ACL, backups y denegacion HTTP de `config`, `storage` y `logs`.
   Apache debe respetar `.htaccess`; Nginx necesita reglas equivalentes. No
   publicar archivos temporales ni fixtures de pruebas. No purgar registros de
   idempotencia mientras puedan existir confirmaciones/reintentos.
10. El runtime local identificado es PHP 7.2.26. La sintaxis se mantuvo compatible,
    pero no es una base apta para aprobar produccion. Usar un PHP con soporte
    vigente y repetir la suite. `fsync` se usa solo cuando el runtime lo ofrece;
    flush/rename no garantizan supervivencia a toda averia de hardware.

El control de la misma compra/navegador es deliberadamente conservador: una
referencia ya iniciada o finalizada no se reutiliza. Una compra identica no abre
otro intento silenciosamente. Hace falta una nueva intencion de compra validada
o conciliacion manual para repetir exactamente esa compra desde esa sesion.
No se deben recomendar borrados de cookies/JSON para saltarse el control.
Los mensajes de cancelacion/rechazo no implican permiso tecnico para reabrir la
referencia anterior.

Sesiones antiguas sin snapshot de perfil/tipo/fecha/UUID o sin verificacion de
catalogo no se "actualizan" confiando en parametros del navegador. Se conservan,
pero exigen conciliacion manual. Un ACCEPT sin auth_code/auth_amount requeridos,
una aprobacion parcial o una decision inconsistente tampoco autorizan fulfillment.

## Pruebas automatizadas

Desde la raiz, con PHP CLI (el comando no carga configuracion privada):

```text
C:\xampp\php\php.exe -n -d allow_url_fopen=0 -d disable_functions=mail,curl_exec integrations/cybersource/tests/run.php
```

La suite crea un directorio temporal con prefijo propio, ejecuta solo fixtures y
elimina exclusivamente esos datos sinteticos al terminar. Trata warnings/notices
como errores. Los procesos hijos tambien tienen HTTP y mail deshabilitados.
Los scripts de pruebas, igual que el procesador, rechazan ejecucion via HTTP.

Resultado local: 169 aserciones aprobadas por ejecucion, tres ejecuciones
consecutivas satisfactorias y validacion de sintaxis de los 35 archivos PHP del
area de integracion/configuracion revisada. Se comprobo sin imprimir secretos
que la configuracion efectiva continua en TEST y apunta al host de checkout TEST.

Cobertura: firmas ausentes/manipuladas, campos criticos sin firmar, replay de
solicitud como respuesta, importes/GTQ/perfil/UUID/referencia/tipo incorrectos,
decisiones contradictorias, campos faltantes, formularios ambiguos, notificaciones
repetidas/tardias, estados finales, cobros en conflicto, fallo funcional/HTTP/JSON
de API, respuesta perdida despues del commit simulado, interrupcion con SENDING,
correo fallido, sesiones antiguas, catalogo/precios alternativos manipulados,
persistencia/revisiones/corrupcion y redaccion. Carreras con seis procesos para
checkout, redireccion, confirmacion y registro/correo.

Las pruebas NO validan disponibilidad del servidor publicado, el perfil NeoNet,
captura/liquidacion real, TLS remoto, entregabilidad de email ni idempotencia
del backend externo. Eso requiere una validacion TEST coordinada posterior.

## Referencias tecnicas

- [Respuesta firmada de Hosted Checkout](https://developer.cybersource.com/docs/cybs/en-us/sa/developer/all/sa-hosted/secure-acceptance/sa-wm-api-fields/sa-response-fields.html).
- [Decisiones y tipos de notificacion](https://developer.cybersource.com/docs/cybs/en-us/sa/developer/all/sa-hosted/secure-acceptance/sa-notification-types.html).
- [Guia oficial Hosted Checkout](https://developer.cybersource.com/docs/cybs/en-us/sa/developer/all/sa-hosted/secure-acceptance.html): verificar HMAC y confirmar Merchant POST rapidamente, sin esperar servicios externos.
