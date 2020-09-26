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
                        <h2 class="hndle"><span><?php echo __( 'Meals added by Admin', YK_MT_SLUG ); ?></span></h2>
                        <div class="inside">
							<p><?php echo __( 'The following meals can be searched by your users and added to their daily entries.', YK_MT_SLUG ); ?></p>
							<?php

							if ( false === yk_mt_site_options_as_bool('search-admin-meals', false ) ) {
								printf( '<p class="yk-mt-error-red"><strong>%s</strong>. %s.</p>',
											__( 'Admin Collection not searchable', YK_MT_SLUG ),
											__( 'As an administrator, you can add, edit and delete meals. However, the setting "Admin\'s meal collection" under "Searching meals" has been disabled which means your users can not search this collection', YK_MT_SLUG )
								);
							}

							$meals = yk_mt_db_meal_for_user( NULL, [ 'admin-meals-only' => true, 'sort-order' => 'asc', 'use-cache' => false ] );

							yk_mt_table_meals( [ 'meals'   => $meals, 'show-username' => true ] );

							?>
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
