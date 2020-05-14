<?php

defined('ABSPATH') or die("Jog on!");

/**
 * REST Handler for adding a meal to an entry
 *
 * @return WP_REST_Response
 */
function yk_mt_ajax_add_meal_to_entry() {

    check_ajax_referer( 'yk-mt-nonce', 'security' );

    // Validate we have all the expected fields
    $post_data = yk_mt_ajax_extract_and_validate_post_data( [ 'entry-id', 'meal-id', 'meal-type', 'quantity' ] );

    $post_data[ 'user-id' ]     = get_current_user_id();
    $post_data[ 'entry-id' ]    = ( true === empty( $post_data[ 'entry-id' ] ) ) ? yk_mt_entry_get_id_or_create( (int) $post_data[ 'user-id' ]  ) : (int) $post_data[ 'entry-id' ];

	$quantity = (int) $post_data[ 'quantity' ];

    if ( $quantity > 50 ) {
        $quantity = 50;
    }

	// Ensure the user is the owner of the entry.
	if ( false === yk_mt_security_entry_owned_by_user( $post_data[ 'entry-id' ], $post_data[ 'user-id' ] ) ) {
		return wp_send_json( [ 'error' => 'security' ] );
	}

    for ( $i = 0; $i < $quantity; $i++ ) {
        if ( false === yk_mt_entry_meal_add( (int) $post_data[ 'entry-id' ], (int) $post_data[ 'meal-id' ], (int) $post_data[ 'meal-type' ] ) ) {
            return wp_send_json( [ 'error' => 'updating-db' ] );
        }
    }

    wp_send_json( [ 'error' => false, 'entry' => yk_mt_entry( $post_data[ 'entry-id' ] ) ] );
}
add_action( 'wp_ajax_add_meal_to_entry', 'yk_mt_ajax_add_meal_to_entry' );

/**
 * Delete a meal from an entry
 */
function yk_mt_ajax_delete_meal_from_entry() {

    check_ajax_referer( 'yk-mt-nonce', 'security' );

    $post_data = yk_mt_ajax_extract_and_validate_post_data( [ 'entry-id', 'meal-entry-id' ] );

	// Ensure the user is the owner of the entry.
	if ( false === yk_mt_security_entry_owned_by_user( $post_data[ 'entry-id' ], get_current_user_id() ) ) {
		return wp_send_json( [ 'error' => 'security' ] );
	}

    if ( true !== yk_mt_entry_meal_delete( (int) $post_data[ 'meal-entry-id' ] ) ) {
        return wp_send_json( [ 'error' => 'updating-db' ] );
    }

    wp_send_json( [ 'error' => false, 'entry' => yk_mt_entry( $post_data[ 'entry-id' ] ) ] );
}
add_action( 'wp_ajax_delete_meal_from_entry', 'yk_mt_ajax_delete_meal_from_entry' );

/**
 * Add a new meal
 *
 * @return mixed
 */
function yk_mt_ajax_meal_add() {

    check_ajax_referer( 'yk-mt-nonce', 'security' );

	$post_data = yk_mt_ajax_extract_and_validate_post_data( [ 'name', 'unit' ] );

    $post_data[ 'added_by' ] = get_current_user_id();

    // Ensure we have a calorie value (can be 0)
	$post_data[ 'calories' ] = yk_mt_ajax_extract_and_validate_post_data_single( 'calories', false );

	$meta_field_keys = yk_mt_meta_fields_visible_user_keys();

	// If meta fields are enabled then look for an array of values. Validate add add to DB call.
    if ( false === empty( $meta_field_keys ) ) {

        if ( true === empty( $_POST[ 'meta-fields' ] ) || false === is_array( $_POST[ 'meta-fields' ] ) ) {
            return wp_send_json( [ 'error' => 'missing-meta-fields-array' ] );
        }

        $meta_fields = $_POST[ 'meta-fields' ];

        foreach ( yk_mt_meta_fields_visible_user_keys() as $key ) {
            if ( false === isset( $meta_fields[ $key ] ) ) {
                return wp_send_json( [ 'error' => 'missing-meta-field-' . $key ] );
            }

            $post_data[ $key ] = (int) $meta_fields[ $key ];
        }

    }

    // If a unit that doesn't expect a quantity, then clear quantity
	if ( true === in_array( $post_data[ 'unit' ], yk_mt_units_where( 'drop-quantity' ) ) ) {
		$post_data[ 'quantity' ] = '';
	} else {
		// Now check we have it if expected!
		$post_data[ 'quantity' ] = yk_mt_ajax_extract_and_validate_post_data_single( 'quantity' );
	}

    // Are we updating a meal?
    $post_data[ 'id' ]  = yk_mt_ajax_get_post_value_int( 'id', false );
	$entry_id           = yk_mt_ajax_get_post_value_int( 'entry-id' );

    if ( false === empty( $post_data[ 'id' ] ) ) {

        if ( false === yk_mt_db_meal_update( $post_data ) ) {
            return wp_send_json( [ 'error' => 'updating-db' ] );
        }

        yk_mt_entry_calories_calculate_update_used( $entry_id );

    } else {

        $meal_id = yk_mt_db_meal_add( $post_data );

        // If we have an entry / meal type ID, then add the meal to the entry automatically
        if ( false === empty( $entry_id ) &&
            false === empty( $_POST[ 'meal-type' ] ) ) {

            yk_mt_entry_meal_add( $entry_id, $meal_id, (int) $_POST[ 'meal-type' ] );
        }

        if ( false === $meal_id ) {
            return wp_send_json( [ 'error' => 'updating-db' ] );
        }
    }

	$post_data['id'] = $meal_id;

    wp_send_json( [ 'error' => false, 'new-meal' => $post_data ] );
}
add_action( 'wp_ajax_add_meal', 'yk_mt_ajax_meal_add' );

/**
 * Fetch the data for a meal
 */
function yk_mt_ajax_meal() {

    check_ajax_referer( 'yk-mt-nonce', 'security' );

    // Validate we have all the expected fields
    $post_data = yk_mt_ajax_extract_and_validate_post_data([ 'meal-id' ] );

    $meal = yk_mt_db_meal_get( $post_data[ 'meal-id' ], get_current_user_id() );

    if ( false === $meal ) {
	    return wp_send_json( [ 'error' => 'loading-meal' ] );
    }

    wp_send_json( [ 'error' => false, 'meal' => $meal ] );
}
add_action( 'wp_ajax_meal', 'yk_mt_ajax_meal' );

/**
 * Fetch all meals for a given user
 */
function yk_mt_ajax_meals() {

	check_ajax_referer( 'yk-mt-nonce', 'security' );

	// If performing a search, set options to look for string. Otherwise load load a max of 20 from DB for user.
	if ( false === empty( $_POST[ 'search' ] ) ) {
		$options = [ 'search' => $_POST[ 'search' ] ];
	} else {
		$options = [ 'limit' => 20 ];
	}

	// Are users allowed to search across everyone's meals? Or just their own?
	$user_id = ( true === yk_mt_license_is_premium() && true === yk_mt_site_options_as_bool('search-others-meals', false ) ) ?
                    NULL :
                        get_current_user_id();

	$meals = yk_mt_db_meal_for_user( $user_id, $options );

    // Compress meal objects to reduce data returned via AJAX
    if ( false === empty( $meals ) ) {
        $meals = array_map( 'yk_mt_ajax_prep_meal', $meals );
    }

	wp_send_json( $meals );
}
add_action( 'wp_ajax_meals', 'yk_mt_ajax_meals' );

/**
 * Search external for meals
 */
function yk_mt_ajax_external_search() {

	if ( false === YK_MT_HAS_EXTERNAL_SOURCES ) {
		return false;
	}

	if ( empty( $_POST[ 'search' ] ) ) {
		wp_send_json( [] );
	}

	check_ajax_referer( 'yk-mt-nonce', 'security' );

	$cache_key = 'ext-search-' . md5( $_POST[ 'search' ] );

	if ( $cache = yk_mt_cache_temp_get( $cache_key ) )  {
		wp_send_json( $cache );
	}

	$meals = yk_mt_ext_source_search( $_POST[ 'search' ] );

	// Do we have an error?
	if ( 'ERR' === $meals ) {
		wp_send_json( 'error' );
	}

	// Compress meal objects to reduce data returned via AJAX
	if ( false === empty( $meals ) ) {

		$meals = array_map( 'yk_mt_ajax_external_prep_meal', $meals );
	}

	// Cache data for this search term ( for 5 mins )
	if ( false === empty( $meals ) ) {
		yk_mt_cache_temp_set( $cache_key, $meals );
	}

	wp_send_json( $meals );
}
add_action( 'wp_ajax_external_search', 'yk_mt_ajax_external_search' );

/**
 * Add an external meal to the user's meal collection
 */
function yk_mt_ajax_external_add_to_collection() {

	if ( false === YK_MT_HAS_EXTERNAL_SOURCES ) {
		return false;
	}

	if ( empty( $_POST[ 'meal_id' ] ) ) {
		wp_send_json( false );
	}

	check_ajax_referer( 'yk-mt-nonce', 'security' );

	$meal_id = yk_mt_ext_add_meal_to_user_collection( $_POST[ 'meal_id' ] );

	// No meal found?
	if ( false === $meal_id ) {
		wp_send_json ( 'error' );
	}



	// TODO: Auto add to selected entry?


	// TODO: Return meal id of new user meal OR 'error' if issue

wp_Send_json ( $meal_id );

}
add_action( 'wp_ajax_external_add_to_collection', 'yk_mt_ajax_external_add_to_collection' );

/**
 * Strip back a meal object ready for transmission via AJAX
 * @param $meal
 * @return mixed
 */
function yk_mt_ajax_external_prep_meal( $meal ) {

	if ( true === is_array( $meal ) ) {

		/**
		 * Since we have the full meal object here (i.e. we're about to display it in search results), let's cache it for 5 minutes. That way,
		 * if a user selects it, we have the relevant data for the meal cached (if not, we will need to call out to external API again).
		 */
		yk_mt_cache_temp_set( 'ext-meal-' . $meal[ 'ext_id' ], $meal );

		$meal[ 'id' ] = $meal[ 'ext_id' ];

		$meal = yk_mt_array_strip_keys( $meal, [ 'ext_id', 'calories', 'unit', 'meta_proteins', 'meta_fats', 'meta_carbs', 'source' ] );
	}

	return $meal;
}

/**
 * Save Settings for user
 */
function yk_mt_ajax_save_settings() {

	check_ajax_referer( 'yk-mt-nonce', 'security' );

	$updated = false;

	foreach ( $_POST as $key => $value ) {

		$key = str_replace( 'yk-mt-', '', $key );

		if ( false === in_array( $key, yk_mt_settings_allowed_keys() ) ) {
			continue;
		}

		yk_mt_settings_set( $key, $value );

        do_action( 'yk_mt_settings_saved_' . $key, $value );

		$updated = true;
	}

    do_action( 'yk_mt_settings_saved' );

	wp_send_json( [ 'error' => ! $updated ] );
}
add_action( 'wp_ajax_save_settings', 'yk_mt_ajax_save_settings' );

/**
 * REST Handler for fetching an entry
 *
 * @return WP_REST_Response
 */
function yk_mt_ajax_get_entry() {

	check_ajax_referer( 'yk-mt-nonce', 'security' );

	$entry_id = ( false === empty( $_POST[ 'entry-id' ] ) ) ? (int) $_POST[ 'entry-id' ] : false;

    $entry = yk_mt_entry( $entry_id );

    // Ensure the User is requesting their own entry!
	if ( get_current_user_id() !== (int) $entry[ 'user_id' ] ) {
		return wp_send_json( [ 'error' => 'security' ] );
	}

    wp_send_json( $entry );
}
add_action( 'wp_ajax_get_entry', 'yk_mt_ajax_get_entry' );

/**
 * Strip back a meal object ready for transmission via AJAX
 * @param $meal
 * @return mixed
 */
function yk_mt_ajax_prep_meal( $meal ) {

    if ( true === is_array( $meal ) ) {

    	$meal[ 'name' ] = sprintf( '%1$s ( %2$s / %3$d%4$s )',
                    $meal[ 'name' ],
		            yk_mt_get_unit_string( $meal ),
                    $meal[ 'calories' ],
                    __( 'kcal', YK_MT_SLUG )
        );

        $meal = yk_mt_array_strip_keys( $meal, [ 'added_by', 'calories', 'unit', 'quantity', 'description', 'deleted', 'favourite' ] );
    }

    return $meal;
}

/**
 * For the given array of keys, ensure they are found within $_POST
 *
 * @param array $post_data
 * @param array $keys
 */
function yk_mt_ajax_extract_and_validate_post_data( $keys = [] ) {

	$post_data = [];

    foreach ( $keys as $key ) {
  	    $post_data[ $key ] = yk_mt_ajax_extract_and_validate_post_data_single( $key );
    }

    return $post_data;
}

/**
 * For a given key, ensure they are found within $_POST
 *
 * @param $key
 *
 * @param bool $is_empty_check
 *
 * @return mixed
 */
function yk_mt_ajax_extract_and_validate_post_data_single( $key, $is_empty_check = true ) {

	if ( true === $is_empty_check &&
	        true === empty( $_POST[ $key ] ) ) {
		wp_send_json( [ 'error' => 'missing-' . $key ] );
	} elseif ( false === isset( $_POST[ $key ] ) ) {
		wp_send_json( [ 'error' => 'missing-' . $key ] );
	}

	return $_POST[ $key ];
}

/**
 * Fetch data from $_POST and force to INT
 * @param $key
 * @param null $default
 * @return bool|int|mixed
 */
function yk_mt_ajax_get_post_value_int( $key, $default = NULL ) {
    return yk_mt_ajax_get_post_value( $key, $default, true );
}

/**
 * Fetch data from $_POST
 * @param $key
 * @param bool $default
 * @param bool $force_to_int
 * @return bool|int|mixed
 */
function yk_mt_ajax_get_post_value( $key, $default = NULL, $force_to_int = false ) {

    $value = NULL;

    if ( false === empty( $_POST[ $key ] ) ) {
        return ( true === $force_to_int ) ? (int) $_POST[ $key ] : $_POST[ $key ];
    }

    return $default ?: NULL;
}
