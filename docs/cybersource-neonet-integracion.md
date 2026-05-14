# Integracion CyberSource / Neonet

Esta integracion esta preparada para PHP puro y mantiene el flujo actual de pedidos.

## Archivos principales

- `config/cybersource.php`: configuracion base, sin secretos.
- `config/cybersource.private.example.php`: plantilla para credenciales.
- `config/cybersource.private.php`: archivo privado que debe crear el administrador.
- `integrations/cybersource/`: clases de la pasarela.
- `api/cybersource/create_checkout.php`: crea la sesion de pago y devuelve la URL segura.
- `api/cybersource/secure_acceptance_redirect.php`: firma y envia el formulario POST hacia la pagina segura de CyberSource/Neonet.
- `api/cybersource/payment_return.php`: retorno del navegador desde Neonet.
- `api/cybersource/payment_webhook.php`: confirmacion servidor a servidor.
- `api/cybersource/capture.php`: captura autorizaciones.
- `api/cybersource/refund.php`: reembolsa pagos.
- `api/cybersource/void.php`: anula capturas pendientes.
- `api/cybersource/reversal.php`: libera autorizaciones.

## Activacion

1. Copiar:

```text
config/cybersource.private.example.php
```

como:

```text
config/cybersource.private.php
```

2. Colocar ahi:

```text
Merchant ID
Key ID
Shared Secret
Profile ID de Secure Acceptance
Access Key de Secure Acceptance
Secret Key de Secure Acceptance
Token interno administrativo
```

3. Mantener ambiente `test` mientras se usa:

```text
https://apitest.cybersource.com
```

4. Cambiar a `production` cuando CyberSource/Neonet confirme salida a produccion:

```text
https://api.cybersource.com
```

## Flujo actual

- Pago Contra Entrega y Transferencia siguen enviando el pedido directamente a la API externa actual.
- Tarjeta prepara una referencia interna y redirige a CyberSource/Neonet Secure Acceptance.
- La tarjeta y el CVV no se capturan ni guardan en este sitio.
- Cuando CyberSource/Neonet devuelve una respuesta firmada aprobada, el backend envia el pedido a la API externa.
- No se usan logos locales de tarjetas porque la seleccion/ingreso de tarjeta ocurre dentro del portal seguro.

## Seguridad

- `config/cybersource.private.php` esta en `.gitignore`.
- `logs/*.log` esta en `.gitignore`.
- `storage/cybersource/*.json` esta en `.gitignore`.
- Las firmas, tokens, claves y otros datos sensibles se redactan antes de escribir logs o sesiones JSON.
- Los endpoints administrativos requieren `X-Internal-Token`.

## Configuracion Secure Acceptance

En el Business Center de CyberSource/Neonet:

```text
Payment Configuration > Secure Acceptance Settings
```

Use el perfil activo de pruebas y coloque:

```text
Profile ID -> secure_acceptance.profile_id
Access Key -> secure_acceptance.access_key
Secret Key -> secure_acceptance.secret_key
```

El servidor genera la firma HMAC-SHA256. No coloque estas claves en JavaScript.

## Revision contra el manual REST

Puntos cubiertos por la implementacion:

- Ambientes separados: `https://apitest.cybersource.com` y `https://api.cybersource.com`.
- Firma REST HTTP Signature con `Merchant ID`, `Key ID`, `Shared Secret`, `Digest`, `Date`, `Host` y `v-c-merchant-id`.
- Operaciones REST preparadas:
  - `POST /pts/v2/payments`
  - `POST /pts/v2/payments/{id}/captures`
  - `POST /pts/v2/payments/{id}/refunds`
  - `POST /pts/v2/captures/{id}/voids`
  - `POST /pts/v2/payments/{id}/reversals`
- Soporte para modo `authorization` o `sale` mediante configuracion.
- Flujo de pedido existente conservado para la API externa.
- Secretos fuera del frontend y fuera del repositorio.

Puntos que dependen de informacion del proveedor:

- URL real o endpoint real del checkout hospedado de Neonet.
- Parametros exactos que Neonet exige al iniciar pago hospedado.
- Parametros exactos que Neonet devuelve al retorno o webhook.
- Metodo de firma/validacion del retorno de Neonet.
- Confirmacion de moneda autorizada: `GTQ` o `USD`.
- Confirmacion operativa: `authorization` + `capture` o `sale`.
- Confirmacion si Neonet requiere Visa Secure / 3D Secure y bajo que modalidad.
