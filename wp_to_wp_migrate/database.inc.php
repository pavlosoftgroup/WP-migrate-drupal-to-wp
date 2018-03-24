<?php
/**
 * User: varaksin
 * Time: 18:46
 */

/** @var TYPE_NAME $db_pref */
$db_pref = get_option( 'wp_to_wp_prefix' );

/**
 * @param $user
 * @param $pass
 * @param $dbname
 * @param $host
 *
 * @return bool
 */
function wp_to_wp_db_connect( $user, $pass, $dbname, $host ) {
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
function wp_to_wp_drupal_database_connection() {

	return new wpdb( get_option( 'wp_to_wp_user' ), get_option( 'wp_to_wp_pass' ), get_option( 'wp_to_wp_database' ), get_option( 'wp_to_wp_host' ) );
}


/**
 * @param $num_page
 *
 * @return array
 */
function wp_to_wp_get_posts( $num_page ) {
	global $db_pref;
	$table_post = $db_pref . 'posts';

	$wpdb2 = wp_to_wp_drupal_database_connection();
	$limit = limit_posts_values( $num_page );

	$results = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT ID, post_title FROM $table_post WHERE post_type='post' ORDER BY post_title  ASC $limit", '' )
	);
	$res     = [];
	$i       = 1;
	foreach ( $results as $key => $result ) {
		$res[ $key ] = [ 'id' => $result->ID, 'title' => $result->post_title, 'no' => $i ++ ];
	}
	return $res;
}

/**
 * @param $post
 *
 * @return array
 */
function wp_to_wp_get_posts_list( $post ) {
    var_dump($post);

    global $db_pref;
	$table_post               = $db_pref . 'posts';
	$table_term_relationships = $db_pref . 'term_relationships';
	$table_term_taxonomy      = $db_pref . 'term_taxonomy';
	$post_srtng               = implode( ",", $post );

	$wpdb2 = wp_to_wp_drupal_database_connection();

	$result = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  ps.post_title, ps.post_date, ps.post_content, ps.post_name, ps.ID FROM $table_post as ps
                           WHERE ps.ID IN ($post_srtng)", '' )
	);

	$post_cat = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  tr.term_taxonomy_id, tt.taxonomy, tr.object_id FROM $table_term_relationships  as tr 
                           LEFT JOIN $table_term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                           WHERE tr.object_id IN ($post_srtng)", '' )
	);

	$cat_array = [];
	foreach ( $post_cat as $value ) {

		switch ( $value->taxonomy ) {
			case 'category' :
				$cat_array[ $value->object_id ]['category'][] = $value->term_taxonomy_id;

				break;
			case 'post_tag' :
				$cat_array[ $value->object_id ]['post_tag'][] = $value->term_taxonomy_id;
				break;
		}
	}

	return [ 'posts' => $result, 'cat' => $cat_array ];
}

/**
 * @param $page
 *
 * @return string
 */
function limit_posts_values( $page ) {
	$count = 20;
	$page  = (int) $page - 1;
	$pages = (int) $page * $count;

//	return $page != 0 ? 'LIMIT 25 OFFSET ' . $pages : 'LIMIT  25';
	return $page != 0 ? 'LIMIT ' . $count . ' OFFSET ' . $pages : 'LIMIT ' . $count;

}

/**
 * @param $id
 *
 * @return mixed
 */
function wp_to_wp_get_post( $id ) {
	global $db_pref;
	$table_post               = $db_pref . 'posts';
	$table_term_relationships = $db_pref . 'term_relationships';
	$table_term_taxonomy      = $db_pref . 'term_taxonomy';

	$wpdb2 = wp_to_wp_drupal_database_connection();

	$result    = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  ps.post_title, ps.post_date, ps.post_content, ps.post_name FROM $table_post as ps
                           WHERE ID='$id'", '' )
	);
	$post_cat  = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  tr.term_taxonomy_id, tt.taxonomy FROM $table_term_relationships  as tr 
                           LEFT JOIN $table_term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                           WHERE tr.object_id='$id'", '' ) );
	$cat_array = [];
	foreach ( $post_cat as $category ) {
		$cat_array[ $category->taxonomy ][ $category->term_taxonomy_id ] = $category->term_taxonomy_id;
	}
	$result[0]->category = $cat_array;
	var_dump( $post_cat );

	return $result[0];
}

/**
 * @param $id
 * @param $pid
 *
 * @return array
 */
function wp_to_wp_insert_image( $id, $pid ) {
	global $db_pref;
	$table_post = $db_pref . 'posts';
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$wpdb2  = wp_to_wp_drupal_database_connection();
	$result = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT guid FROM $table_post WHERE post_parent='$id' AND post_type='attachment'", '' )
	);
	$output = [];

	$upload_dir = wp_upload_dir();
	if ( wp_mkdir_p( $upload_dir['path'] ) ) {
		$file_path = $upload_dir['path'] . '/';
	} else {
		$file_path = $upload_dir['basedir'] . '/';
	}
	if ( is_object( $result[0] ) ) {
		foreach ( $result as $image_post ) {
			$output[]   = $image_post->guid;
			$image_data = file_get_contents( $image_post->guid );
			$filename   = basename( $image_post->guid );
			$file       = $file_path . $pid . '-' . $filename;
			file_put_contents( $file, $image_data );

			$attachment  = [
				'post_mime_type' => 'image/jpeg',
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			];
			$img_tag     = media_sideload_image( $image_post->guid, $pid );
			$attach_id   = wp_insert_attachment( $attachment, $file, $pid );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			add_post_meta( $pid, '_thumbnail_id', $attach_id );
			if ( is_wp_error( $img_tag ) ) {
				echo $img_tag->get_error_message();
			}
		}
	}

	return $output;
}

/**
 * @return array
 */
function category_import_list() {
	global $db_pref;
	$table_terms         = $db_pref . 'terms';
	$table_term_taxonomy = $db_pref . 'term_taxonomy';
	$wpdb2               = wp_to_wp_drupal_database_connection();
	$result              = $wpdb2->get_results(
		$wpdb2->prepare( /** @lang text */
			"SELECT  cn.term_id,  cn.name, tt.taxonomy  FROM $table_terms as cn
                            LEFT JOIN $table_term_taxonomy as tt ON cn.term_id = tt.term_taxonomy_id
                            ", '' )
	);
	$output              = [];
	foreach ( $result as $category ) {
		switch ( $category->taxonomy ) {
			case 'category':
				$output['category'][ $category->term_id ] = [
					'name'    => $category->name,
					'term_id' => wp_create_category( $category->name ),
				];

				break;
			case 'post_tag':
				$output['post_tag'][ $category->term_id ] = $category->name;
				break;
		}
	}

	return $output;
}