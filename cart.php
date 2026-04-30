<?php include 'head.php';
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
                        <div class="mp-form-group">
                            <label for="nombreCompleto">Nombre Completo <span class="required">*</span></label>
                            <input type="text" id="nombreCompleto" name="nombreCompleto" class="form-control"
                                   autocomplete="name" minlength="3" maxlength="120"
                                   pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ\s.'-]{3,120}"
                                   title="Ingrese solo letras, espacios y signos permitidos como apostrofe, punto o guion."
                                   required>
                        </div>

                        <div class="mp-form-group">
                            <label for="direccion">Dirección <span class="required">*</span></label>
                            <input type="text" id="direccion" name="direccion" class="form-control"
                                   autocomplete="street-address" minlength="8" maxlength="180"
                                   pattern="[A-Za-z0-9À-ÖØ-öø-ÿÑñ\s#.,/-]{8,180}"
                                   title="Use letras, numeros, espacios y signos de direccion como #, punto, coma, diagonal o guion."
                                   required>
                        </div>

                        <div class="mp-form-group">
                            <label for="telefono">Teléfono <span class="required">*</span></label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" 
                                   placeholder="12345678" pattern="[0-9]{8,15}" inputmode="numeric" maxlength="15" minlength="8"
                                   autocomplete="tel-national" title="Ingrese solo numeros, entre 8 y 15 digitos."
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            <small class="form-text text-muted">Solo números, mínimo 8 dígitos (obligatorio para contactarte)</small>
                        </div>

                        <div class="mp-form-group">
                            <label for="correo">Correo Electrónico</label>
                            <input type="email" id="correo" name="correo" class="form-control" 
                                   inputmode="email" maxlength="120" autocomplete="email"
                                   placeholder="ejemplo@correo.com">
                            <small class="form-text text-muted">Opcional si no necesita factura</small>
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

                        <div class="mp-form-group">
                            <label for="formaPago">Forma de Pago</label>
                            <select id="formaPago" name="formaPago" class="form-control" autocomplete="off">
                                <option value="Efectivo" selected>Efectivo</option>
                                <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                                <option value="Transferencia">Transferencia Bancaria</option>
                            </select>
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
                border-radius: 12px;
                padding: 25px;
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
                top: 12px; 
                right: 15px;
                background: none;
                border: none;
                font-size: 26px;
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
                max-width: 600px !important;
            }

            .mp-form-content {
                padding: 10px;
            }

            .mp-form-content h2 {
                margin-bottom: 10px;
                color: #1A2697;
                font-size: 24px;
            }

            .mp-form-subtitle {
                margin-bottom: 25px;
                color: #666;
                font-size: 14px;
            }

            .mp-form-group {
                margin-bottom: 20px;
            }

            .mp-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
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
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s;
            }

            .mp-form-group .form-control:focus {
                outline: none;
                border-color: #1A2697;
                box-shadow: 0 0 0 2px rgba(26, 38, 151, 0.1);
            }

            .mp-form-group select.form-control {
                cursor: pointer;

                text-align: center;
                text-align-last: center;

                padding-top: 10px;
                padding-bottom: 10px;

                height: 48px;
            }

            .mp-form-group select.form-control option {
                text-align: center;
            }

            .mp-form-buttons {
                display: flex;
                gap: 18px;
                justify-content: flex-end;
                align-items: stretch;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }

            .mp-btn-cancelar,
            .mp-btn-enviar {
                min-width: 190px;
                min-height: 74px;
                padding: 16px 28px;
                border: none;
                border-radius: 10px;
                font-size: 18px;
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
                    padding: 18px 16px;
                    border-radius: 12px;
                }

                .mp-form-content {
                    padding: 0;
                }


                .mp-form-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 14px;
                    justify-content: space-between;
                    align-items: stretch;
                    margin-top: 35px !important;
                    padding-top: 18px;
                }

                .mp-btn-cancelar,
                .mp-btn-enviar {
                    flex: 1 1 calc(50% - 7px);
                    min-width: 0;
                    min-height: 64px;
                    padding: 14px 12px;
                    font-size: 16px;
                    line-height: 1.2;
                    white-space: normal;
                    word-break: break-word;
                }

                .mp-form-group select.form-control{
                        height: 42px;
                        padding-top: 6px;
                        padding-bottom: 6px;
                        font-size: 14px;
                    }

                    .mp-modal{
                        max-height: 85vh;
                    }

                    .mp-btn-cancelar,
                    .mp-btn-enviar{
                        min-height: 52px;
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

            /* ===== ESPACIO ENTRE FORMA DE PAGO Y BOTONES ===== */

            .mp-form-buttons{
                margin-top: 40px !important; /* espacio superior uniforme */
            }

            /* Ajuste fino en celular */

            @media (max-width:768px){

                .mp-form-buttons{
                    margin-top: 35px !important;
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
