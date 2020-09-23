<?php

defined('ABSPATH') or die('Naw ya dinnie!');

function yk_mt_admin_page_meals_dashboard() {

    yk_mt_admin_permission_check();

    ?>
    <div class="wrap ws-ls-user-meals ws-ls-admin-page">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <?php
                        if ( false === YK_MT_IS_PREMIUM ) {
                            yk_mt_display_pro_upgrade_notice();
                        }
                    ?>
                   <div class="postbox">
                        <h2 class="hndle"><span><?php echo __( 'Recent Meals', YK_MT_SLUG ); ?></span></h2>
                        <div class="inside">

                        </div>
                    </div>
                </div>
            </div>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <?php yk_mt_dashboard_meals_side_bar(); ?>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
    <?php
}
