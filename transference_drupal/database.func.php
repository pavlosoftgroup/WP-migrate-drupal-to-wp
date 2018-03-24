<?php

/**
 * @param $user
 * @param $pass
 * @param $dbname
 * @param $host
 *
 * @return bool
 */
function transference_db_connect( $user, $pass, $dbname, $host ) {
	global $wpdb2;

	$wpdb2 = new wpdb( $user, $pass, $dbname, $host );

	if ( ! empty( $wpdb2->error ) ) {
		//    wp_die($wpdb2->error);
		return false;
	}

	return true;
}

/**
 * @return wpdb
 */
function transferens_drupal_database_connection() {

	return new wpdb( get_option( 'transference_user' ), get_option( 'transference_pass' ), get_option( 'transference_database' ), get_option( 'transference_host' ) );
}

/**
 * @return array
 */
function transference_drupal_node_types() {
	$wpdb2   = transferens_drupal_database_connection();
	$res     = [];
	$results = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT type, name FROM node_type", '' )
	);
	foreach ( $results as $key => $result ) {
		$res[ $key ] = [ 'name' => $result->name, 'type' => $result->type ];
	}

	return $res;

}

/**
 * @param $value_arr
 *
 * @return string
 */
function transferens_node_types_option_render( $value_arr ) {
	$output = '';
	foreach ( $value_arr as $value ) {
		$output .= '<option value="' . $value['type'] . '">' . $value['name'] . '</option>';
	}

	return $output;
}

/**
 * @param $type
 * @param $num_page
 */
function transferens_get_posts( $type, $num_page ) {
	$wpdb2 = transferens_drupal_database_connection();
	$limit = transferens_limit_posts_values( $num_page );
	$i     = 1;

	$nodes = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT n.title, n.nid FROM node as n
                            LEFT JOIN field_data_body as b ON n.nid = b.entity_id
                            WHERE n.type='$type' ORDER BY n.title  ASC  $limit", '' )
	);

	return $nodes;

}

/**
 * @param $page
 *
 * @return string
 */
function transferens_limit_posts_values( $page ) {
	$count = 40;
	$page  = (int) $page - 1;
	$pages = (int) $page * $count;

//	return $page != 0 ? 'LIMIT 25 OFFSET ' . $pages : 'LIMIT 25 ';
	return $page != 0 ? 'LIMIT ' . $count . ' OFFSET ' . $pages : 'LIMIT ' . $count;

}

/**
 * @param $post
 */
function transferens_import_nodes( $post ) {
	global $wpdb;
	$result = '';
	$cat_id = wp_create_category( 'Articles' );
	wp_update_term_count( array( (int) $cat_id ), 'category' );
	$start = microtime( true );

	$user_id = username_exists( 'QArea Team' );
	if ( ! $user_id ) {
		$user_id = wp_create_user( 'QArea Team', '1111111111', 'qarea.team@qarea.com' );
	}
	$nodes = [];
	foreach ( $post as $key => $value ) {
		if ( $value == 'on' ) {
			$nodes[] = $key;
		}
	}
	$node_srtng = implode( ",", $nodes );
	$wpdb2      = transferens_drupal_database_connection();
	$query      = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT n.title, n.created, b.body_value, n.nid FROM node as n
                            LEFT JOIN field_data_body as b ON n.nid = b.entity_id
                           WHERE n.nid IN ($node_srtng)", '' )
	);

	ini_set( "memory_limit", - 1 );
	set_time_limit( 0 );
	ignore_user_abort( true );

	wp_defer_term_counting( true );
	wp_defer_comment_counting( true );
	$wpdb->query( 'SET autocommit = 0;' );
	$i = 1;


	foreach ( $query as $node ) {
		$content = str_replace( "/sites/default/files/", "https://qarea.com/sites/default/files/", $node->body_value );
		$output  = [
			'post_title'   => $node->title,
			'post_type'    => 'post',
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_date'    => date( 'Y-m-d H:i:s', $node->created ),
		];
		$res     = transferens_wp_create_post( $output, $node->nid );
		wp_set_post_categories( $res, $cat_id );
		$result .= '<p>Post id ' . $node->nid . ' is import successful. <b>Created post with id:' . $res . '</b></p>';

	}

	$wpdb->query( 'COMMIT;' );
	wp_defer_term_counting( false );
	wp_defer_comment_counting( false );
	var_dump( 'Время выполнения скрипта: ' . round( microtime( true ) - $start, 4 ) . ' сек.' );


	return $result;
}

/**
 * @param $type
 *
 * @return array|null|object
 */
function transferens_get_nodes( $type ) {
	$res = '';
	$i   = 0;

	$cat_id = wp_create_category( 'Article' );


	$user_id = username_exists( 'QArea Team' );
	if ( ! $user_id ) {
		$user_id = wp_create_user( 'QArea Team', '1111111111', 'qarea.team@qarea.com' );
	}

	$wpdb2   = transferens_drupal_database_connection();
	$results = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT n.title, n.created, b.body_value, n.nid FROM node as n
                            LEFT JOIN field_data_body as b ON n.nid = b.entity_id
                            WHERE n.type='$type'
                            ", '' )
	);
	foreach ( $results as $result ) {
		if ( $i > 2 ) {
			return $res;
		}
//		$content = replace_image_path_in_drupal( $result->body_value );
		$content = str_replace( "/sites/default/files/", "https://qarea.com/sites/default/files/", $result->body_value );


		$output = [
			'post_title'   => $result->title,
			'post_type'    => 'post',
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_date'    => date( 'Y-m-d H:i:s', $result->created ),
		];
		$res    = transferens_wp_create_post( $output, $result->nid );
		wp_set_post_categories( $res, $cat_id );
		$i ++;
	}

	return $res;
}

/**
 * @param $array_value
 *
 * @return int|WP_Error
 */
function transferens_wp_create_post( $array_value, $nid ) {
	$post_id = wp_insert_post( $array_value, true );
	if ( is_wp_error( $post_id ) ) {
		return $post_id->get_error_message();
	}
	$img_tag = transferens_image_migration( $nid, $post_id );
	if ( is_wp_error( $img_tag ) ) {
		echo $img_tag->get_error_message();
	}

	return $post_id;
}

/**
 * @param $nid
 * @param $post_id
 *
 * @return string|WP_Error
 */
function transferens_image_migration( $nid, $post_id ) {
	require_once ABSPATH . WPINC . '/cache.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$temp_path = 'https://dev.qarea.org/downloads/wriper.jpg';

	$wpdb2   = transferens_drupal_database_connection();
	$results = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  f.filename,  f.uri  FROM file_managed as f
                            LEFT JOIN field_data_field_image as m ON f.fid = m.field_image_fid
                            WHERE m.entity_id ='$nid'
                            ", '' )
	);
	$img_tag = '';

	$upload_dir = wp_upload_dir();
//	var_dump($upload_dir);
	if ( wp_mkdir_p( $upload_dir['path'] ) ) {
		$file_path = $upload_dir['path'] . '/';
	} else {
		$file_path = $upload_dir['basedir'] . '/';
	}

	if ( ! empty( $results ) ) {
		$upload_path = str_replace( "public://", "https://qarea.com/sites/default/files/", $results[0]->uri );
		$image_data  = file_get_contents( $upload_path );
		$filename    = basename( $upload_path );
		$file        = $file_path . $post_id . '-' . $filename;

	} else {
//		$upload_path = get_template_directory_uri() . '/images/wriper.jpg';
//		$upload_path = $temp_path;
		$image_data = wp_cache_get( 'wriper_image' );
//		var_dump($image_data);
		if ( false === $image_data ) {
			$image_data = file_get_contents( $temp_path );
			wp_cache_set( 'wriper_image', $image_data );
		}
		$filename = basename( $temp_path );
		$file     = $file_path . $filename;

	}
//	var_dump( strlen( $image_data ) );
	if ( strlen( $image_data ) < 100000 ) {
		unset( $image_data );
		$image_data = wp_cache_get( 'wriper_image' );
		if ( false === $image_data ) {
			$image_data = file_get_contents( $temp_path );
			wp_cache_set( 'wriper_image', $image_data );
		}
	}

	if ( ! file_exists( $file ) ) {
		$fike_res = file_put_contents( $file, $image_data );
	}
//	$file     = $file_path . $post_id . '-' . $filename;
//	$fike_res =file_put_contents( $file, $image_data );
//	var_dump($fike_res);
	$attachment  = [
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'wriper.jpg',
		'post_content'   => '',
		'post_status'    => 'inherit',
	];
	$img_tag     = media_sideload_image( $upload_path, $post_id );
	$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	set_post_thumbnail( $post_id, $attach_id );
//	var_dump($img_tag);
	if ( is_wp_error( $img_tag ) ) {
		echo '<p>Image ' . $img_tag->get_error_message() . '</p>';
	}
	if ( is_wp_error( $attach_data ) ) {
		echo 'Attachment ' . $attach_data->get_error_message() . '</br>';
	}

	return $img_tag;
}

function replace_image_path_in_drupal( $content ) {
	$upload_path = str_replace( "/sites/default/files/", "https://qarea.com/sites/default/files/", $content );

	return $upload_path;
}

?>