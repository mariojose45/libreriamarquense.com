<?php include 'head.php';

$current_page = basename($_SERVER['PHP_SELF']);

$paginas_servicios = [
    'derechos.php'
];
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Derechos de Autor</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Derechos de Autor</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .copyright-showcase-area {
        background: #F7F8FC;
        padding: 50px 0;
    }

    .copyright-showcase-header {
        margin: 0 auto 28px;
        max-width: 820px;
        text-align: center;
    }

    .copyright-showcase-header h2 {
        color: #17214F;
        font-size: 56px;
        font-weight: 800;
        line-height: 1.05;
        margin-bottom: 14px;
    }

    .copyright-showcase-header p {
        color: #5F6675;
        font-size: 18px;
        line-height: 1.7;
        margin-bottom: 0;
    }

    .copyright-legal-card {
        background: #ffffff;
        border: 1px solid rgba(26, 38, 151, 0.12);
        border-radius: 30px;
        box-shadow: 0 24px 60px rgba(19, 33, 76, 0.10);
        overflow: hidden;
        position: relative;
    }

    .copyright-legal-card::before {
        background: #1A2697;
        content: "";
        display: block;
        height: 8px;
        width: 100%;
    }

    .copyright-legal-card-body {
        padding: 48px 56px 42px;
    }

    .copyright-legal-top {
        margin-bottom: 20px;
    }

    .copyright-legal-heading h3 {
        color: #17214F;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 0;
    }

    .copyright-legal-updated {
        color: #5F6675;
        font-size: 20px;
        line-height: 1.7;
        margin-bottom: 24px;
    }

    .copyright-legal-copy {
        color: #2D3448;
        font-size: 21px;
        font-weight: 500;
        line-height: 1.8;
        margin-bottom: 24px;
    }

    .copyright-legal-note {
        align-items: center;
        background: #EEF2FF;
        border-left: 5px solid #1A2697;
        border-radius: 18px;
        color: #1A2697;
        display: flex;
        font-size: 17px;
        font-weight: 600;
        gap: 12px;
        line-height: 1.7;
        padding: 18px 22px;
    }

    .copyright-legal-note i {
        color: #166B38;
        font-size: 24px;
    }

    @media only screen and (max-width: 991px) {
        .copyright-showcase-header h2 {
            font-size: 42px;
        }

        .copyright-legal-card-body {
            padding: 36px 28px 30px;
        }

    }

    @media only screen and (max-width: 767px) {
        .copyright-showcase-area {
            padding: 40px 0;
        }

        .copyright-showcase-header h2 {
            font-size: 34px;
        }

        .copyright-showcase-header p {
            font-size: 16px;
        }

        .copyright-legal-heading h3 {
            font-size: 24px;
        }

        .copyright-legal-updated {
            font-size: 17px;
            margin-bottom: 18px;
        }

        .copyright-legal-copy {
            font-size: 18px;
        }

        .copyright-legal-note {
            align-items: flex-start;
            font-size: 15px;
        }
    }
</style>

<section class="copyright-showcase-area">
    <div class="container">
        <div class="copyright-showcase-header">
            <h2>Derechos de Autor</h2>
        </div>

        <div class="copyright-legal-card">
            <div class="copyright-legal-card-body">
                <div class="copyright-legal-top">
                    <div class="copyright-legal-heading">
                        <h3>❓📝 Texto recomendado de Derechos Reservados</h3>
                    </div>
                </div>

                <p class="copyright-legal-updated">Última actualización: 26 de diciembre de 2025</p>

                <p class="copyright-legal-copy">© 2025 COMPUSISGT – Computadoras y Sistemas de Guatemala. Todos los derechos reservados. El contenido de este sitio web, incluyendo textos, imágenes, logotipos y diseños, es propiedad de COMPUSISGT y está protegido por las leyes de derechos de autor. Queda prohibida su reproducción total o parcial sin autorización previa.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
