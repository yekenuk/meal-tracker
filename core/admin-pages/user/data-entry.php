<?php

defined('ABSPATH') or die('Naw ya dinnie!');

/**
 * Admin page for viewing an entry
 */
function yk_mt_admin_page_entry_view() {

    // TODO: Add role permission check here

    $entry_id = yk_mt_querystring_value( 'entry-id', false );

    $entry = yk_mt_db_entry_get( $entry_id );

    if( true === empty( $entry ) ) {
        return;
    }

    ?>
    <div class="wrap ws-ls-user-data ws-ls-admin-page">
    <div id="poststuff">
        <?php yk_mt_user_header( $entry[ 'user_id' ] ); ?>
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <?php
                    if ( false === YK_MT_IS_PREMIUM ) {
                        yk_mt_display_pro_upgrade_notice();
                    }
                    ?>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php echo __( 'Summary', YK_MT_SLUG ); ?></span></h2>
                        <div class="inside">

                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php echo __('Data', YK_MT_SLUG ); ?></span></h2>
                        <div class="inside">
                            <? print_r( $entry ); ?>

                        </div>
                    </div>
                </div>
            </div>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <?php yk_mt_user_side_bar(  $entry[ 'user_id' ], $entry ); ?>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
    <?php
}
