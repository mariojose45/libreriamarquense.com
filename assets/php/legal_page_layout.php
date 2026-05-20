<?php

if (!function_exists('lm_render_legal_styles')) {
    function lm_render_legal_styles()
    {
        ?>
        <style>
            .lm-legal-area {
                background: #F7F8FC;
                padding: 50px 0;
            }

            .lm-legal-header {
                margin: 0 auto 28px;
                max-width: 900px;
                text-align: center;
            }

            .lm-legal-header h2 {
                color: #16224a;
                font-size: 48px;
                font-weight: 800;
                line-height: 1.1;
                margin-bottom: 0;
            }

            .lm-legal-card {
                background: #ffffff;
                border: 1px solid rgba(36, 71, 155, 0.12);
                border-radius: 28px;
                box-shadow: 0 22px 55px rgba(19, 33, 76, 0.10);
                overflow: hidden;
                position: relative;
            }

            .lm-legal-card::before {
                background: #24479b;
                content: "";
                display: block;
                height: 8px;
                width: 100%;
            }

            .lm-legal-card-body {
                padding: 42px 46px;
            }

            .lm-legal-card-top {
                align-items: center;
                display: flex;
                gap: 18px;
                margin-bottom: 18px;
            }

            .lm-legal-icon {
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

            .lm-legal-heading {
                flex: 1 1 auto;
            }

            .lm-legal-heading h3 {
                color: #16224a;
                font-size: 30px;
                font-weight: 800;
                line-height: 1.2;
                margin-bottom: 8px;
            }

            .lm-legal-updated {
                color: #617089;
                font-size: 17px;
                line-height: 1.6;
                margin-bottom: 0;
            }

            .lm-legal-intro {
                color: #2d3850;
                font-size: 18px;
                line-height: 1.8;
                margin-bottom: 28px;
            }

            .lm-legal-block + .lm-legal-block {
                border-top: 1px solid rgba(36, 71, 155, 0.10);
                margin-top: 24px;
                padding-top: 24px;
            }

            .lm-legal-block h4 {
                color: #16224a;
                font-size: 28px;
                font-weight: 800;
                line-height: 1.25;
                margin-bottom: 14px;
            }

            .lm-legal-block p {
                color: #2e384f;
                font-size: 18px;
                line-height: 1.85;
                margin-bottom: 14px;
            }

            .lm-legal-block p:last-child {
                margin-bottom: 0;
            }

            .lm-legal-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .lm-legal-list li {
                color: #2e384f;
                font-size: 18px;
                line-height: 1.8;
                padding-left: 24px;
                position: relative;
            }

            .lm-legal-list li + li {
                margin-top: 8px;
            }

            .lm-legal-list li::before {
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
                .lm-legal-header h2 {
                    font-size: 40px;
                }

                .lm-legal-card-body {
                    padding: 34px 28px;
                }

                .lm-legal-card-top {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }

            @media only screen and (max-width: 767px) {
                .lm-legal-area {
                    padding: 40px 0;
                }

                .lm-legal-header h2 {
                    font-size: 32px;
                }

                .lm-legal-heading h3 {
                    font-size: 24px;
                }

                .lm-legal-updated,
                .lm-legal-intro,
                .lm-legal-block p,
                .lm-legal-list li {
                    font-size: 16px;
                }

                .lm-legal-block h4 {
                    font-size: 22px;
                }
            }
        </style>
        <?php
    }

    function lm_open_legal_section($pageTitle, $cardTitle, $updatedText, $introText = '', $iconClass = 'bx-file')
    {
        lm_render_legal_styles();
        ?>
        <section class="lm-legal-area">
            <div class="container">
                <div class="lm-legal-header">
                    <h2><?php echo $pageTitle; ?></h2>
                </div>

                <div class="lm-legal-card">
                    <div class="lm-legal-card-body">
                        <div class="lm-legal-card-top">
                            <div class="lm-legal-icon">
                                <i class='bx <?php echo htmlspecialchars($iconClass); ?>'></i>
                            </div>

                            <div class="lm-legal-heading">
                                <h3><?php echo $cardTitle; ?></h3>
                                <?php if (!empty($updatedText)): ?>
                                    <p class="lm-legal-updated"><?php echo $updatedText; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($introText)): ?>
                            <p class="lm-legal-intro"><?php echo $introText; ?></p>
                        <?php endif; ?>
        <?php
    }

    function lm_render_legal_block($title, array $paragraphs = [], array $bullets = [])
    {
        ?>
        <div class="lm-legal-block">
            <h4><?php echo $title; ?></h4>

            <?php foreach ($paragraphs as $paragraph): ?>
                <p><?php echo $paragraph; ?></p>
            <?php endforeach; ?>

            <?php if (!empty($bullets)): ?>
                <ul class="lm-legal-list">
                    <?php foreach ($bullets as $bullet): ?>
                        <li><?php echo $bullet; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    function lm_close_legal_section()
    {
        ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}
