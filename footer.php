<?php
$footer_social_links = array_filter($site_social_links ?? []);
?>
<section class="footer-area pt-100 pb-70">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-sm-6">
                <div class="single-footer-widget">
                    <h2>Contactanos</h2>

                    <ul class="footer-contact-info">
                        <li>
                            <span>Ubicaci&oacute;n:</span>
                            <a href="contact.php">8A Avenida 19-55, Cdad. de Guatemala 01001.</a>
                        </li>
                        <li>
                            <span>Telefonos:</span>
                            <a href="tel:+50255910533">Llamadas: <?php echo htmlspecialchars($site_phone_number); ?> | WhatsApp: <?php echo htmlspecialchars($site_whatsapp_number); ?></a>
                        </li>
                        <?php if (!empty($site_email)): ?>
                            <li>
                                <span>Email:</span>
                                <a href="mailto:<?php echo htmlspecialchars($site_email); ?>"><?php echo htmlspecialchars($site_email); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <?php if (!empty($footer_social_links)): ?>
                        <ul class="footer-social">
                            <?php if (!empty($footer_social_links['facebook'])): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($footer_social_links['facebook']); ?>" target="_blank" rel="noopener">
                                        <i class='bx bxl-facebook'></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($footer_social_links['instagram'])): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($footer_social_links['instagram']); ?>" target="_blank" rel="noopener">
                                        <i class='bx bxl-instagram'></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($footer_social_links['tiktok'])): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($footer_social_links['tiktok']); ?>" target="_blank" rel="noopener">
                                        <i class='bx bxl-tiktok'></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="single-footer-widget">
                    <h2>Servicios</h2>

                    <ul class="quick-links">
                        <li><a href="servicios.php">Servicios</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="single-footer-widget">
                    <h2>Informacion</h2>

                    <ul class="quick-links">
                        <li><a href="nosotros.php">Quienes Somos</a></li>
                        <li><a href="terminos.php">Terminos y Condiciones</a></li>
                        <li><a href="politicas_de_garantia.php">Politicas de Garantia</a></li>
                        <li><a href="metodos_de_pago.php">Metodos de Pago</a></li>
                        <li><a href="preguntas_frecuentes.php">Preguntas Frecuentes</a></li>
                        <li><a href="contact.php">Contacto</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="single-footer-widget">
                    <h2>Soporte y Atencion</h2>

                    <div class="newsletter-item">
                        <div class="newsletter-content">
                            <p>Necesitas ayuda con utiles escolares, papeleria o una lista escolar? Escribenos por WhatsApp.</p>
                        </div>

                        <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener" class="default-btn"
                            style="padding:12px 25px; display:inline-block;">
                            <i class='bx bxl-whatsapp'></i> Chatear Ahora
                            <span></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Footer Area -->

<!-- Start Copy Right Area -->
<div class="copyright-area">
    <div class="container">
        <div class="copyright-area-content">
            <p>
                Copyright &copy; <?php echo date('Y'); ?> COMPUSISGT. Todos los derechos reservados.
                <a href="derechos.php">libreriamarquense.com</a>
            </p>
        </div>
    </div>
</div>
<!-- End Copy Right Area -->

<!-- Start Go Top Area -->
<div class="go-top">
    <i class='bx bx-up-arrow-alt'></i>
</div>
<!-- End Go Top Area -->

<!-- Start WhatsApp Float Button -->
<a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener" class="whatsapp-float" title="Chatear por WhatsApp">
    <i class='bx bxl-whatsapp'></i>
</a>
<!-- End WhatsApp Float Button -->

<style>
    /* Boton flotante de WhatsApp */
    .whatsapp-float {
        position: fixed;
        width: 60px;
        height: 60px;
        bottom: 120px;
        right: 20px;
        background-color: #466934;
        color: #FFF;
        border-radius: 50px;
        text-align: center;
        font-size: 50px;
        box-shadow: 2px 2px 3px #999;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .whatsapp-float i {
        margin: 0;
        line-height: 1;
    }

    .whatsapp-float:hover {
        background-color: #355329;
        transform: scale(1.1);
        box-shadow: 3px 3px 5px #666;
        color: #FFF;
    }

    .whatsapp-float:active {
        transform: scale(0.95);
    }

    /* Ajustar posicion cuando hay boton go-top activo */
    @media (max-width: 768px) {
        .whatsapp-float {
            width: 55px;
            height: 55px;
            font-size: 28px;
            bottom: 110px;
            right: 15px;
        }
    }
</style>

<!-- Jquery Slim JS -->
<script src="assets/js/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<!-- Meanmenu JS -->
<script src="assets/js/jquery.meanmenu.js"></script>
<!-- Owl Carousel JS -->
<script src="assets/js/owl.carousel.min.js"></script>
<!-- Magnific Popup JS -->
<script src="assets/js/jquery.magnific-popup.min.js"></script>
<!-- Nice Select JS -->
<script src="assets/js/jquery.nice-select.min.js"></script>
<!-- Slick JS -->
<script src="assets/js/slick.min.js"></script>

<!-- Odometer JS -->
<script src="assets/js/odometer.min.js"></script>
<!-- Appear JS -->
<script src="assets/js/jquery.appear.js"></script>
<!-- Jquery Ui JS -->
<script src="assets/js/jquery-ui.min.js"></script>
<!-- Ajaxchimp JS -->
<script src="assets/js/jquery.ajaxchimp.min.js"></script>
<!-- Form Validator JS -->
<script src="assets/js/form-validator.min.js"></script>
<!-- Contact JS -->
<script src="assets/js/contact-form-script.js"></script>
<!-- Wow JS -->
<script src="assets/js/wow.min.js"></script>
<!-- Carrito JS -->
<script src="assets/js/carrito.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href*="servicios.php"]').forEach(function (link) {
        if (link.textContent.trim().toLowerCase() === 'servicios') {
            var item = link.closest('li') || link;
            item.remove();
        }
    });
});
</script>
</body>

</html>
