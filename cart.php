<?php
require_once __DIR__ . '/assets/php/security.php';
$checkout_csrf_token = lm_csrf_token('checkout');
include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);

?>
        <!-- Start Page Banner -->
        <div class="page-title-area">
            <div class="container">
                <div class="page-title-content">
                    <h2>Carrito de compras</h2>

                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li>Carrito de compras</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Page Banner -->

        <!-- Start Cart Area -->
		<section class="cart-area ptb-50">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 col-md-12">
                        <form>
                            <div class="cart-table table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Opciones</th>
                                            <th scope="col">Articulo</th>
                                            <th scope="col">Precio unitario</th>
                                            <th scope="col">Cantidad</th>
                                            <th scope="col">Total</th>
                                        </tr>
                                    </thead>
        
                                    <tbody>
                                        <!-- Los productos se cargarán dinámicamente desde localStorage -->
                                    </tbody>
                                </table>
                            </div>
        

                        </form>
                    </div>

                    <div class="col-lg-4 col-md-12">
                        <div class="cart-totals">
                            <h3>Cart Totals</h3>
    
                            <ul>
                                    <li>Subtotal <span>Q0.00</span></li>
                                <li>Shipping <span>Q00.00</span></li>
                                <li>Total <span>Q0.00</span></li>
                                <li>Total <span>Q0.00</span></li>
                            </ul>
    
                            <a href="javascript:void(0)" class="default-btn" id="btn-continuar-pedido" onclick="abrirModalPedido()">
                                Continuar con el pedido
                                <span></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Cart Area -->

        <!-- ===========================
            MODAL FORMULARIO DE PEDIDO
        =========================== -->
        <div id="modalPedido" class="mp-overlay">
            <div class="mp-modal mp-modal-form">
                <button class="mp-close" onclick="cerrarModalPedido()">✕</button>
                
                <div class="mp-form-content">
                    <h2>Datos del Pedido</h2>
                    <p class="mp-form-subtitle">Por favor complete la siguiente información para continuar</p>
                    
                    <form id="formPedido" onsubmit="procesarPedido(event)">
                        <input type="hidden" id="checkoutCsrfToken" name="csrf_token" value="<?php echo lm_html_escape($checkout_csrf_token); ?>">
                        <input type="text" id="checkoutWebsite" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;opacity:0;pointer-events:none;">
                        <div class="mp-form-group">
                            <label for="nombreCompleto">Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="nombreCompleto" name="nombreCompleto" class="form-control"
                                autocomplete="name" minlength="3" maxlength="80"
                                pattern="[\p{L}\p{M} ]{3,80}"
                                title="Ingrese solo letras y espacios."
                                required>
                        </div>

                        <div class="mp-form-group">
                            <label for="lugarEnvio">Lugar de entrega <span class="required">*</span></label>
                            <select id="lugarEnvio" name="lugarEnvio" class="form-control" autocomplete="off" required>
                                <option value="" data-shipping="0" selected>Seleccione lugar de entrega</option>
                                <option value="" disabled>Ciudad de Guatemala</option>
                                <option value="Ciudad de Guatemala, Zona 1" data-shipping="25">Ciudad de Guatemala, Zona 1 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 2" data-shipping="25">Ciudad de Guatemala, Zona 2 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 3" data-shipping="25">Ciudad de Guatemala, Zona 3 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 4" data-shipping="25">Ciudad de Guatemala, Zona 4 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 5" data-shipping="25">Ciudad de Guatemala, Zona 5 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 6" data-shipping="25">Ciudad de Guatemala, Zona 6 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 7" data-shipping="25">Ciudad de Guatemala, Zona 7 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 8" data-shipping="25">Ciudad de Guatemala, Zona 8 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 9" data-shipping="25">Ciudad de Guatemala, Zona 9 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 10" data-shipping="25">Ciudad de Guatemala, Zona 10 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 11" data-shipping="25">Ciudad de Guatemala, Zona 11 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 12" data-shipping="25">Ciudad de Guatemala, Zona 12 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 13" data-shipping="25">Ciudad de Guatemala, Zona 13 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 14" data-shipping="25">Ciudad de Guatemala, Zona 14 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 15" data-shipping="25">Ciudad de Guatemala, Zona 15 - Q25.00</option>
                                <option value="Ciudad de Guatemala, Zona 16" data-shipping="30">Ciudad de Guatemala, Zona 16 - Q30.00</option>
                                <option value="Ciudad de Guatemala, Zona 17" data-shipping="30">Ciudad de Guatemala, Zona 17 - Q30.00</option>
                                <option value="Ciudad de Guatemala, Zona 18" data-shipping="30">Ciudad de Guatemala, Zona 18 - Q30.00</option>
                                <option value="Ciudad de Guatemala, Zona 19" data-shipping="30">Ciudad de Guatemala, Zona 19 - Q30.00</option>
                                <option value="Ciudad de Guatemala, Zona 21" data-shipping="30">Ciudad de Guatemala, Zona 21 - Q30.00</option>
                                <option value="Ciudad de Guatemala, Zona 24" data-shipping="35">Ciudad de Guatemala, Zona 24 - Q35.00</option>
                                <option value="Ciudad de Guatemala, Zona 25" data-shipping="35">Ciudad de Guatemala, Zona 25 - Q35.00</option>
                                <option value="" disabled>Mixco</option>
                                <option value="Mixco, Zona 1" data-shipping="35">Mixco, Zona 1 - Q35.00</option>
                                <option value="Mixco, Zona 2" data-shipping="35">Mixco, Zona 2 - Q35.00</option>
                                <option value="Mixco, Zona 3" data-shipping="30">Mixco, Zona 3 - Q30.00</option>
                                <option value="Mixco, Zona 4" data-shipping="30">Mixco, Zona 4 - Q30.00</option>
                                <option value="Mixco, Zona 5" data-shipping="30">Mixco, Zona 5 - Q30.00</option>
                                <option value="Mixco, Zona 6" data-shipping="30">Mixco, Zona 6 - Q30.00</option>
                                <option value="Mixco, Zona 7" data-shipping="35">Mixco, Zona 7 - Q35.00</option>
                                <option value="Mixco, Zona 8" data-shipping="35">Mixco, Zona 8 - Q35.00</option>
                                <option value="Mixco, Zona 9" data-shipping="40">Mixco, Zona 9 - Q40.00</option>
                                <option value="Mixco, Zona 10" data-shipping="30">Mixco, Zona 10 - Q30.00</option>
                                <option value="Mixco, Zona 11" data-shipping="35">Mixco, Zona 11 - Q35.00</option>
                                <option value="" disabled>Villa Nueva</option>
                                <option value="Villa Nueva, Zona 1" data-shipping="35">Villa Nueva, Zona 1 - Q35.00</option>
                                <option value="Villa Nueva, Zona 2" data-shipping="35">Villa Nueva, Zona 2 - Q35.00</option>
                                <option value="Villa Nueva, Zona 3" data-shipping="35">Villa Nueva, Zona 3 - Q35.00</option>
                                <option value="Villa Nueva, Zona 4" data-shipping="35">Villa Nueva, Zona 4 - Q35.00</option>
                                <option value="Villa Nueva, Zona 5" data-shipping="35">Villa Nueva, Zona 5 - Q35.00</option>
                                <option value="Villa Nueva, Zona 6" data-shipping="35">Villa Nueva, Zona 6 - Q35.00</option>
                                <option value="Villa Nueva, Zona 8" data-shipping="35">Villa Nueva, Zona 8 - Q35.00</option>
                                <option value="Villa Nueva, Zona 10" data-shipping="35">Villa Nueva, Zona 10 - Q35.00</option>
                                <option value="Villa Nueva, Zona 11" data-shipping="35">Villa Nueva, Zona 11 - Q35.00</option>
                                <option value="Villa Nueva, Zona 12" data-shipping="35">Villa Nueva, Zona 12 - Q35.00</option>
                                <option value="" disabled>Otros lugares</option>
                                <option value="Boca del Monte" data-shipping="40">Boca del Monte - Q40.00</option>
                                <option value="San Miguel Petapa" data-shipping="40">San Miguel Petapa - Q40.00</option>
                                <option value="Ciudad Quetzal" data-shipping="70">Ciudad Quetzal - Q70.00</option>
                                <option value="Departamentos" data-shipping="50">Departamentos - Q50.00</option>
                            </select>
                        </div>

                        <div class="mp-form-group">
                            <label for="direccion">Dirección <span class="required">*</span></label>
                            <input type="text" id="direccion" name="direccion" class="form-control"
                                autocomplete="street-address" minlength="8" maxlength="250"
                                pattern="[\p{L}\p{M}0-9 #.,\/\-]{8,250}"
                                title="Use letras, numeros, espacios y signos de direccion como #, punto, coma, diagonal o guion."
                                required>
                        </div>

                        <div class="mp-form-group">
                            <label for="telefono">Teléfono <span class="required">*</span></label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" 
                                   placeholder="12345678" pattern="[0-9]{8,15}" inputmode="numeric" maxlength="15" minlength="8"
                                   autocomplete="tel-national" title="Ingrese solo numeros, entre 8 y 15 digitos."
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        </div>

                        <div class="mp-form-group">
                            <label for="correo">Correo Electrónico</label>
                            <input type="email" id="correo" name="correo" class="form-control" 
                                   inputmode="email" maxlength="120" autocomplete="email"
                                   placeholder="ejemplo@correo.com">
                        </div>

                        <div class="mp-form-group">
                            <label>
                                <input type="checkbox" id="necesitaFactura" name="necesitaFactura" onchange="toggleNit()">
                                Necesito factura
                            </label>
                        </div>

                        <div class="mp-form-group" id="nit-group" style="display: none;">
                            <label for="tipoDocumento">Tipo de Documento <span class="required">*</span></label>
                            <select id="tipoDocumento" name="tipoDocumento" class="form-control" onchange="cambiarTipoDocumento()">
                                <option value="">Seleccione una opción</option>
                                <option value="nit">NIT</option>
                                <option value="dpi">DPI</option>
                            </select>
                        </div>

                        <div class="mp-form-group" id="numeroDocumento-group" style="display: none;">
                            <label for="numeroDocumento" id="labelNumeroDocumento">Número de Documento <span class="required">*</span></label>
                            <input type="text" id="numeroDocumento" name="numeroDocumento" class="form-control" 
                                   maxlength="13" oninput="validarDocumento()" pattern="[0-9]{1,13}" inputmode="numeric"
                                   autocomplete="off" title="Ingrese solo numeros para NIT o DPI.">
                            <small class="form-text text-muted" id="documentoHelp"></small>
                        </div>

                        <div class="mp-form-group mp-payment-method-group">
                            <label for="formaPago">M&eacute;todos de Pago</label>
                            <select id="formaPago" name="formaPago" class="form-control" autocomplete="off" required>
                                <option value="" selected>Seleccione m&eacute;todo de pago</option>
                                <option value="Pago Contra Entrega">Pago Contra Entrega</option>
                                <option value="Tarjeta">Tarjeta de Cr&eacute;dito/D&eacute;bito</option>
                                <option value="Transferencia">Transferencia Bancaria</option>
                            </select>
                        </div>

                        <div class="mp-card-gateway" id="pasarelaTarjetaInfo" hidden>
                            <div class="mp-card-gateway-header">
                                <strong>Pago seguro con tarjeta</strong>
                                <span>Seras redirigido al portal seguro de Neonet.</span>
                            </div>
                            <p>No ingreses datos de tarjeta en este sitio. La autorizacion se realiza en la pagina segura del proveedor.</p>
                        </div>

                        <div class="mp-order-total">
                            <span>Total a pagar</span>
                            <strong id="totalPagarPedido">Q0.00</strong>
                        </div>

                        <div class="mp-form-buttons">
                            <button type="button" class="mp-btn-cancelar" onclick="cerrarModalPedido()">Cancelar</button>
                            <button type="submit" class="mp-btn-enviar">Enviar Pedido</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            /* ===========================
                ESTILOS BASE DEL MODAL
            =========================== */
            .mp-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.7);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 99999;
            }

            .mp-overlay.show {
                display: flex !important;
            }

            .mp-modal {
                width: 90%;
                max-width: 900px;
                background: #fff;
                border-radius: 10px;
                padding: 20px 22px;
                position: relative;
                box-shadow: 0 10px 35px rgba(0,0,0,0.25);
                animation: fadeIn .25s ease-out;
                max-height: 90vh;
                overflow-y: auto;
            }

            @keyframes fadeIn {
                from { transform: scale(.95); opacity: 0; }
                to   { transform: scale(1); opacity: 1; }
            }

            .mp-close {
                position: absolute;
                top: 10px; 
                right: 12px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #333;
                z-index: 10;
            }

            .mp-close:hover {
                color: #B73639;
            }

            /* ===========================
                MODAL FORMULARIO
            =========================== */
            .mp-modal-form {
                max-width: 560px !important;
            }

            .mp-form-content {
                padding: 4px;
            }

            .mp-form-content h2 {
                margin-bottom: 6px;
                color: #1A2697;
                font-size: 22px;
            }

            .mp-form-subtitle {
                margin-bottom: 18px;
                color: #666;
                font-size: 13px;
            }

            .mp-form-group {
                margin-bottom: 16px;
            }

            .mp-payment-method-group {
                margin-top: 18px;
            }

            .mp-payment-method-group .nice-select.open {
                margin-bottom: 210px;
            }

            .mp-form-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 600;
                color: #333;
                font-size: 13px;
            }

            .mp-form-group label input[type="checkbox"] {
                margin-right: 8px;
                width: auto;
            }

            .required {
                color: #B73639;
            }

            .mp-form-group .form-control {
                width: 100%;
                padding: 9px 13px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                min-height: 42px;
                transition: border-color 0.3s;
            }

            .mp-form-group .form-control:focus {
                outline: none;
                border-color: #1A2697;
                box-shadow: 0 0 0 2px rgba(26, 38, 151, 0.1);
            }

            .mp-form-group select.form-control {
                cursor: pointer;

                text-align: left;
                text-align-last: left;

                padding-top: 8px;
                padding-bottom: 8px;
                padding-left: 14px;

                height: 44px;
            }

            .mp-form-group select.form-control option {
                text-align: left;
                padding: 10px 14px;
            }

            .mp-form-group .nice-select {
                width: 100%;
                float: none;
                height: 44px;
                line-height: 42px;
                padding-left: 14px;
                padding-right: 34px;
                border-radius: 8px;
                border-color: #ddd;
                font-size: 14px;
                color: #222;
                margin-top: 0;
                margin-bottom: 0;
            }

            .mp-form-group .nice-select.open,
            .mp-form-group .nice-select:focus {
                border-color: #1A2697;
                box-shadow: 0 0 0 2px rgba(26, 38, 151, 0.1);
            }

            .mp-form-group .nice-select .list {
                width: 100%;
                max-height: 260px;
                overflow-y: auto;
                margin-top: 6px;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.14);
            }

            .mp-form-group .nice-select .option {
                min-height: 44px;
                line-height: 1.35;
                padding: 11px 16px;
                display: flex;
                align-items: center;
            }

            .mp-payment-method-group .nice-select .list {
                margin-top: 10px;
                padding: 8px 0;
            }

            .mp-payment-method-group .nice-select .option {
                padding-left: 18px;
                padding-right: 18px;
            }

            .mp-card-gateway {
                margin-top: -4px;
                margin-bottom: 16px;
                padding: 12px;
                border: 1px solid rgba(26, 38, 151, 0.18);
                border-radius: 10px;
                background: #F7F8FF;
            }

            .mp-card-gateway[hidden] {
                display: none !important;
            }

            .mp-card-gateway-header {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                margin-bottom: 10px;
            }

            .mp-card-gateway strong {
                display: block;
                color: #1A2697;
                font-size: 15px;
                line-height: 1.3;
            }

            .mp-card-gateway span,
            .mp-card-gateway p {
                color: #555;
                font-size: 13px;
                line-height: 1.45;
            }

            .mp-card-gateway p {
                margin: 0;
            }

            .mp-order-total {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-top: 4px;
                padding: 14px 16px;
                border: 1px solid rgba(183, 54, 57, 0.18);
                border-radius: 10px;
                background: #FFF7F7;
            }

            .mp-order-total span {
                color: #333;
                font-size: 15px;
                font-weight: 700;
                line-height: 1.3;
            }

            .mp-order-total strong {
                color: #B73639;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.1;
                white-space: nowrap;
            }

            .mp-form-buttons {
                display: flex;
                gap: 14px;
                justify-content: flex-end;
                align-items: stretch;
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid #eee;
            }

            .mp-btn-cancelar,
            .mp-btn-enviar {
                min-width: 150px;
                min-height: 50px;
                padding: 11px 20px;
                border: none;
                border-radius: 10px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                line-height: 1.25;
            }

            .mp-btn-cancelar {
                background: #f5f5f5;
                color: #333;
            }

            .mp-btn-cancelar:hover {
                background: #e0e0e0;
            }

            .mp-btn-enviar {
                background: #1A2697;
                color: #fff;
            }

            .mp-btn-enviar:hover {
                background: #101A5C;
            }

            .mp-btn-enviar:disabled {
                background: #ccc;
                cursor: not-allowed;
            }

            @media (max-width: 768px) {
          
                .mp-modal {
                    width: calc(100% - 24px);
                    max-width: 100%;
                    padding: 16px 14px;
                    border-radius: 10px;
                }

                .mp-form-content {
                    padding: 0;
                }


                .mp-form-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    justify-content: space-between;
                    align-items: stretch;
                    margin-top: 24px !important;
                    padding-top: 14px;
                }

                .mp-btn-cancelar,
                .mp-btn-enviar {
                    flex: 1 1 calc(50% - 7px);
                    min-width: 0;
                    min-height: 48px;
                    padding: 10px 12px;
                    font-size: 14px;
                    line-height: 1.2;
                    white-space: normal;
                    word-break: break-word;
                }

                .mp-form-group select.form-control{
                        height: 42px;
                        padding-top: 7px;
                        padding-bottom: 7px;
                        font-size: 14px;
                    }

                    .mp-form-group .nice-select {
                        height: 42px;
                        line-height: 40px;
                        font-size: 14px;
                    }

                .mp-form-group .nice-select .option {
                        min-height: 44px;
                        padding: 11px 16px;
                    }

                    .mp-order-total {
                        align-items: flex-start;
                        flex-direction: column;
                        gap: 6px;
                        padding: 13px 14px;
                    }

                    .mp-order-total strong {
                        font-size: 22px;
                    }

                    .mp-modal{
                        max-height: 85vh;
                    }

                    .mp-btn-cancelar,
                    .mp-btn-enviar{
                        min-height: 48px;
                    }

            }

            .mp-form-buttons {
                width: 100%;
                box-sizing: border-box;
            }

            .mp-btn-cancelar,
            .mp-btn-enviar {
                box-sizing: border-box;
            }

            .mp-btn-cancelar {
                background: #f5f5f5;
                color: #333;
            }

            .mp-btn-cancelar:hover {
                background: #e0e0e0;
            }

            .mp-btn-enviar {
                background: #1A2697;
                color: #fff;
            }

            .mp-btn-enviar:hover {
                background: #101A5C;
            }

            .mp-btn-enviar:disabled {
                background: #ccc;
                cursor: not-allowed;
            }

            .cart-table td:nth-child(2){
                max-width: 220px;
            }

            .cart-table td:nth-child(2) a{
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;

                overflow: hidden;
                text-overflow: ellipsis;
                white-space: normal !important;
            }

            .cart-product-presentation {
                display: inline-flex;
                align-items: center;
                margin-top: 7px;
                padding: 4px 9px;
                border-radius: 5px;
                background: rgba(22, 107, 56, .08);
                color: #166B38;
                font-size: 12px;
                font-weight: 700;
                line-height: 1.2;
            }

            @media (max-width:768px){

                .cart-table td:nth-child(2){
                    max-width: 140px;
                }

            }

            /* ===== ESPACIO ENTRE IMAGEN Y NOMBRE ===== */

            .cart-table table tbody td:nth-child(1){
                padding-right:16px !important;
            }

            .cart-table table tbody td:nth-child(2){
                padding-left:16px !important;
            }

            /* versión celular */

            @media(max-width:768px){

                .cart-table table tbody td:nth-child(1){
                    padding-right:10px !important;
                }

                .cart-table table tbody td:nth-child(2){
                    padding-left:10px !important;
                }

            }

            /* ===== CARRITO RESPONSIVE ===== */
            @media (max-width: 768px) {

                .cart-table table thead th {
                    text-align:center !important;
                    vertical-align:middle !important;

                    padding:16px 10px !important;

                    height:auto !important;
                    line-height:normal !important;

                    white-space:nowrap;
                    font-size:14px;
                }

                .cart-table table tbody tr td.product-thumbnail {
                    padding-right: 12px !important;
                }

                .cart-table table tbody tr td.product-name {
                    padding-left: 12px !important;
                }

                .cart-table table tbody tr td.product-name a {
                    max-width: 140px;
                    line-height: 1.35;
                }
            }

            .mp-form-buttons{
                margin-top: 20px !important;
            }

            /* Ajuste fino en celular */

            @media (max-width:768px){

                .mp-form-buttons{
                    margin-top: 22px !important;
                }

            }

            .cart-area .default-btn,
            .cart-area .default-btn span,
            .cart-area .cart-totals .default-btn,
            .cart-area .cart-totals .default-btn span{
                background: #1A2697 !important;
                border-color: #1A2697 !important;
                color: #ffffff !important;
            }

            .cart-area .default-btn:hover,
            .cart-area .default-btn:focus,
            .cart-area .cart-totals .default-btn:hover,
            .cart-area .cart-totals .default-btn:focus{
                background: #101A5C !important;
                border-color: #101A5C !important;
                color: #ffffff !important;
            }

            .mp-btn-enviar{
                background: #1A2697 !important;
                color: #ffffff !important;
            }

            .mp-btn-enviar:hover{
                background: #101A5C !important;
                color: #ffffff !important;
            }

            .mp-btn-cancelar{
                background: #EEF2FF !important;
                color: #1A2697 !important;
                border: 1px solid #1A2697 !important;
            }

            .mp-btn-cancelar:hover{
                background: #E6E9FA !important;
                color: #101A5C !important;
            }

        </style>

<?php include 'footer.php'; ?>
<script type="text/javascript" src="assets/js/sweatlert.js"></script> 
<script type="text/javascript" src="assets/js/cart.js"></script> 
