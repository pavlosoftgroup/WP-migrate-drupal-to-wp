<?php

/*
Plugin Name: Wp To Wp Migrate
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: varaksin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

//include 'options.php';
include 'database.inc.php';

// Hook for adding admin menus
add_action( 'admin_menu', 'wp_to_wp_migrate_admin_function' );

/**
 *
 */
function wp_to_wp_migrate_admin_function() {
	add_options_page( 'WP to WP migrate Setting', 'WP Migrate Setting DB', 7, 'wp-to-wp-settings', 'wp_to_wp_migrate_settings_page' );

//	add_management_page( 'WP to WP migrate Manage', 'WP Migrate Manage', 7, 'wp-to-wp-process', 'wp_to_wp_migrate_page' );

	add_menu_page( 'WP to WP Manage', 'WP to WP Manage', 7, __FILE__, 'wp_to_wp_migrate_toplevel_page' );
}

/**
 *
 */
function wp_to_wp_migrate_settings_page() {

	add_option( 'num_page' );
	update_option( 'num_page', 1 );


	$database_name   = 'wp_to_wp_database';
	$user_name       = 'wp_to_wp_user';
	$database_pass   = 'wp_to_wp_pass';
	$database_host   = 'wp_to_wp_host';
	$database_prefix = 'wp_to_wp_prefix';

	add_option( 'checked_wp_input' );

	add_option( $database_name );
	add_option( $user_name );
	add_option( $database_pass );
	add_option( $database_host, 'localhost' );
	add_option( $database_prefix, 'wp_' );
	add_option( 'database_status', false );
	$hidden_field_name = 'transference_submit_hidden';


	$database_val        = get_option( $database_name );
	$user_val            = get_option( $user_name );
	$pass_val            = get_option( $database_pass );
	$host_val            = get_option( $database_host );
	$database_prefix_val = get_option( $database_prefix );

	echo "<h2>WP to WP Database Options</h2>";

	if ( !empty($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		$checked = $_POST['checked_wp_input'] == 'on' ? 'checked' : '';
		update_option( 'checked_wp_input', $checked );


		$database_val        = $_POST[ $database_name ];
		$user_val            = $_POST[ $user_name ];
		$pass_val            = $_POST[ $database_pass ];
		$host_val            = $_POST[ $database_host ];
		$database_prefix_val = $_POST[ $database_prefix ];

		update_option( $database_name, $database_val );
		update_option( $user_name, $user_val );
		update_option( $database_pass, $pass_val );
		update_option( $database_host, $host_val );
		update_option( $database_prefix, $database_prefix_val );
	}
	$test_connect = false;
	if ( $database_val & $user_val & $pass_val & $host_val ) {
		if ( wp_to_wp_db_connect( $user_val, $pass_val, $database_val, $host_val ) ) {
			add_option( 'wp_to_wp_dbconnect', true );
			$test_connect = true;
		}
	}

	?>
    <form name="form1" method="post"
          action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
		<?php wp_nonce_field( 'update-options' ); ?>

        <input type="hidden" name="<?php echo $hidden_field_name; ?>"
               value="Y">

        <p><?php _e( "Database:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="text" name="<?php echo $database_name; ?>"
                   value="<?php echo $database_val; ?>" size="20">
        </p>
        <hr/>
        <p><?php _e( "Database Host:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="text" name="<?php echo $database_host; ?>"
                   value="<?php echo $host_val; ?>" size="20">
        </p>
        <hr/>
        <p><?php _e( "Database User:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="text" name="<?php echo $user_name; ?>"
                   value="<?php echo $user_val; ?>" size="20">
        </p>
        <hr/>
        <p><?php _e( "Database User Password:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="password" name="<?php echo $database_pass; ?>"
                   value="<?php echo $pass_val; ?>" size="20">
        </p>
        <hr/>
        <p><?php _e( "Database Table prefix:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="text" name="<?php echo $database_prefix; ?>"
                   value="<?php echo $database_prefix_val; ?>" size="20">
        </p>
        <hr/>
        <p><?php _e( "Checked all post:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="checkbox" <?php print get_option( 'checked_wp_input' ); ?> name="checked_wp_input"
                   id="checked_wp_input">
        </p>
        <hr/>
        <p class="submit">
            <input type="submit" name="Submit"
                   value="<?php _e( 'Update Options', 'mt_trans_domain' ) ?>"/>
        </p>
		<?php
		if ( $test_connect ) {
			?>
            <p class="link">
                <a href="admin.php?page=wp_to_wp_migrate%2Fwp-to-wp-migrate.php">Select
                    post to import</a>
            </p>
			<?php
		}
		?>
    </form>
	<?php
}


/**
 *
 */
function wp_to_wp_migrate_toplevel_page() {
//	$num_page = get_option( 'num_page' );
	$num_page = empty($_POST['num_page']) ? 1 : $_POST['num_page'];

//	var_dump($num_page);

	if ( $_POST['num_page'] != NULL) {
		$import_post = clear_post( $_POST );
		$result      = wp_to_wp_migrate_page( $import_post );
        $num_page = (int)$num_page+1;
        var_dump($num_page);
        update_option( 'num_page', $num_page);
	}

	$post_list = wp_to_wp_get_posts( $num_page );
	if ( empty( $post_list ) && $num_page != 1 ) {
		echo "<h2>Import is finished! </h2>";

		return false;
	}
	echo '<h3>Import Result</h3>';
	print $result;
	echo "<h2>Choice Post </h2> Page:" . $num_page;

	if ( get_option( 'wp_to_wp_dbconnect' ) ) {
		?>
        <form name="formpost" method="post" action=" <?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
			<?php wp_nonce_field( 'update-options' ); ?>
            <input type="hidden" name="num_page" value="<?php print $num_page; ?>">


			<?php foreach ( $post_list as $value ) {
				?>
                <p>
                    <input type="checkbox" <?php print get_option( 'checked_wp_input' ); ?>
                           name="<?php print  $value['id']; ?>"
                           id="<?php print  $value['id']; ?>">
                    <label for="<?php print  $value['id']; ?>"><?php print  'No:' . $value['no'] . ' ' . $value['title']; ?></label>
                </p>
				<?php
			}
			?>
            <p class="submit">
                <input type="submit" name="Submit"
                       value="<?php _e( 'Migrate', 'mt_trans_domain' ) ?>"/>
            </p>
        </form>
	<?php } else {
		$text = 'DB not connect. Please, connect to DB.';
		echo '<a href="options-general.php?page=wp-to-wp-settings">' . $text . '</a>';
	}
}

/**
 * @param $post
 *
 * @return array
 */
function clear_post( $post ) {
	$output = [];

	foreach ( $post as $key => $value ) {
		if ( $value == 'on' ) {
			$output[] = $key;
		}
	}

	return $output;
}

/**
 * @param $posts
 *
 * @return string
 */
function wp_to_wp_migrate_page( $posts ) {
	global $wpdb;
	$result  = '';
	$user_id = username_exists( 'QArea Expert' );
	$info    = '';
	$start   = microtime( true );

	if ( ! $user_id ) {
		$user_id = wp_create_user( 'QArea Expert', '1111111111', 'qarea.expert@qarea.com' );
	}
	$cat_list = category_import_list();
	$post_list = wp_to_wp_get_posts_list( $posts );
//	remove_action( 'do_pings', 'do_all_pings', 10, 1 );


	ini_set( "memory_limit", - 1 );
	set_time_limit( 0 );
	ignore_user_abort( true );

	wp_defer_term_counting( true );
	wp_defer_comment_counting( true );
	$wpdb->query( 'SET autocommit = 0;' );
	$i = 1;

	foreach ( $post_list['posts'] as $value ) {

		$tags = [];
		foreach ( $post_list['cat'][ $value->ID ]['post_tag'] as $tag ) {
			$tags[] = $cat_list['post_tag'][ $tag ];
		}


		$post_cat = $cat_list['category'][$post_list['cat'][ $value->ID ]['category'][0]]['term_id'];
		$post_cat_name = $cat_list['category'][$post_list['cat'][ $value->ID ]['category'][0]]['name'];
		$output = [
			'post_title'    => $value->post_title,
			'post_content'  => $value->post_content,
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_author'   => $user_id,
			'post_date'     => $value->post_date,
			'post_name'     => $value->post_name,
//			'post_category' => (int)$post_cat,
		];
		$post_id  = wp_insert_post( $output );
		$category = wp_set_object_terms( $post_id, [(int) $post_cat], 'category' );
		wp_set_post_tags( $post_id, $tags );

		$update_taxonomy = 'category';
		$get_terms_args = array(
			'taxonomy' => $update_taxonomy,
			'name' => $post_cat_name,
			'hide_empty' => false,
		);
		$update_terms = get_terms($get_terms_args);

		$sdf= wp_update_term_count_now([$update_terms], $update_taxonomy);

		$images = wp_to_wp_insert_image( $value->ID, $post_id );
		$i ++;
		if ( is_wp_error( $images ) ) {
			echo $images->get_error_message();
		}
		if ( is_wp_error( $category ) ) {
			echo $category->get_error_message();
		}

		$info   = $post_id != 0 ? 'The post <b>' . $output['post_title'] . '</b> migrate successful!' : 'The post <b>' . $output['post_title'] . '</b> migrate ERROR!';
		$result .= 'Item:' . $i . ' ' . $info . '<br />';

	}
	$wpdb->query( 'COMMIT;' );
	wp_defer_term_counting( false );
	wp_defer_comment_counting( false );
	var_dump( 'Время выполнения скрипта: ' . round( microtime( true ) - $start, 4 ) . ' сек.' );

	return $result;
}


?>



