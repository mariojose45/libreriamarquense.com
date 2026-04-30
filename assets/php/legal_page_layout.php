<?php

if (!function_exists('ticell_render_legal_styles')) {
    function ticell_render_legal_styles()
    {
        ?>
        <style>
            .ti-cell-legal-area {
                background: #F7F8FC;
                padding: 50px 0;
            }

            .ti-cell-legal-header {
                margin: 0 auto 28px;
                max-width: 900px;
                text-align: center;
            }

            .ti-cell-legal-header h2 {
                color: #16224a;
                font-size: 48px;
                font-weight: 800;
                line-height: 1.1;
                margin-bottom: 0;
            }

            .ti-cell-legal-card {
                background: #ffffff;
                border: 1px solid rgba(36, 71, 155, 0.12);
                border-radius: 28px;
                box-shadow: 0 22px 55px rgba(19, 33, 76, 0.10);
                overflow: hidden;
                position: relative;
            }

            .ti-cell-legal-card::before {
                background: #24479b;
                content: "";
                display: block;
                height: 8px;
                width: 100%;
            }

            .ti-cell-legal-card-body {
                padding: 42px 46px;
            }

            .ti-cell-legal-card-top {
                align-items: center;
                display: flex;
                gap: 18px;
                margin-bottom: 18px;
            }

            .ti-cell-legal-icon {
                align-items: center;
                background: linear-gradient(145deg, rgba(36, 71, 155, 0.15), rgba(36, 71, 155, 0.04));
                border-radius: 20px;
                color: #24479b;
                display: inline-flex;
                flex: 0 0 72px;
                font-size: 34px;
                height: 72px;
                justify-content: center;
                width: 72px;
            }

            .ti-cell-legal-heading {
                flex: 1 1 auto;
            }

            .ti-cell-legal-heading h3 {
                color: #16224a;
                font-size: 30px;
                font-weight: 800;
                line-height: 1.2;
                margin-bottom: 8px;
            }

            .ti-cell-legal-updated {
                color: #617089;
                font-size: 17px;
                line-height: 1.6;
                margin-bottom: 0;
            }

            .ti-cell-legal-intro {
                color: #2d3850;
                font-size: 18px;
                line-height: 1.8;
                margin-bottom: 28px;
            }

            .ti-cell-legal-block + .ti-cell-legal-block {
                border-top: 1px solid rgba(36, 71, 155, 0.10);
                margin-top: 24px;
                padding-top: 24px;
            }

            .ti-cell-legal-block h4 {
                color: #16224a;
                font-size: 28px;
                font-weight: 800;
                line-height: 1.25;
                margin-bottom: 14px;
            }

            .ti-cell-legal-block p {
                color: #2e384f;
                font-size: 18px;
                line-height: 1.85;
                margin-bottom: 14px;
            }

            .ti-cell-legal-block p:last-child {
                margin-bottom: 0;
            }

            .ti-cell-legal-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .ti-cell-legal-list li {
                color: #2e384f;
                font-size: 18px;
                line-height: 1.8;
                padding-left: 24px;
                position: relative;
            }

            .ti-cell-legal-list li + li {
                margin-top: 8px;
            }

            .ti-cell-legal-list li::before {
                background: #4aa364;
                border-radius: 999px;
                content: "";
                height: 8px;
                left: 3px;
                position: absolute;
                top: 13px;
                width: 8px;
            }

            @media only screen and (max-width: 991px) {
                .ti-cell-legal-header h2 {
                    font-size: 40px;
                }

                .ti-cell-legal-card-body {
                    padding: 34px 28px;
                }

                .ti-cell-legal-card-top {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }

            @media only screen and (max-width: 767px) {
                .ti-cell-legal-area {
                    padding: 40px 0;
                }

                .ti-cell-legal-header h2 {
                    font-size: 32px;
                }

                .ti-cell-legal-heading h3 {
                    font-size: 24px;
                }

                .ti-cell-legal-updated,
                .ti-cell-legal-intro,
                .ti-cell-legal-block p,
                .ti-cell-legal-list li {
                    font-size: 16px;
                }

                .ti-cell-legal-block h4 {
                    font-size: 22px;
                }
            }
        </style>
        <?php
    }

    function ticell_open_legal_section($pageTitle, $cardTitle, $updatedText, $introText = '', $iconClass = 'bx-file')
    {
        ticell_render_legal_styles();
        ?>
        <section class="ti-cell-legal-area">
            <div class="container">
                <div class="ti-cell-legal-header">
                    <h2><?php echo $pageTitle; ?></h2>
                </div>

                <div class="ti-cell-legal-card">
                    <div class="ti-cell-legal-card-body">
                        <div class="ti-cell-legal-card-top">
                            <div class="ti-cell-legal-icon">
                                <i class='bx <?php echo htmlspecialchars($iconClass); ?>'></i>
                            </div>

                            <div class="ti-cell-legal-heading">
                                <h3><?php echo $cardTitle; ?></h3>
                                <?php if (!empty($updatedText)): ?>
                                    <p class="ti-cell-legal-updated"><?php echo $updatedText; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($introText)): ?>
                            <p class="ti-cell-legal-intro"><?php echo $introText; ?></p>
                        <?php endif; ?>
        <?php
    }

    function ticell_render_legal_block($title, array $paragraphs = [], array $bullets = [])
    {
        ?>
        <div class="ti-cell-legal-block">
            <h4><?php echo $title; ?></h4>

            <?php foreach ($paragraphs as $paragraph): ?>
                <p><?php echo $paragraph; ?></p>
            <?php endforeach; ?>

            <?php if (!empty($bullets)): ?>
                <ul class="ti-cell-legal-list">
                    <?php foreach ($bullets as $bullet): ?>
                        <li><?php echo $bullet; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    function ticell_close_legal_section()
    {
        ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
