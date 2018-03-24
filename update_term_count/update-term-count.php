<?php

/*
Plugin Name: Update Term Count
Description: Update Terms Count of the Plugin.
Version: 1.0
Author: Pavel Varaksin
License: A "Slug" license name e.g. GPL2
*/

//include 'database.func.php';

add_action( 'admin_menu', 'update_term_count_admin_function' );

/**
 *
 */
function update_term_count_admin_function() {
	add_options_page( 'Update Term', 'Update Term', 8, 'update-termoptions', 'update_term_count_options_page' );
	add_options_page( 'Migrate images path', 'Migrate images path', 8, 'migrate-images', 'migrate_img' );
	add_options_page( 'Multiadd category in posts', 'Multiadd category in post', 8, 'multiadd-catefory', 'multiadd_category' );
//	add_menu_page( 'Transference Manage', 'Transference Manage', 8, 'transferencemanage', 'transference_manage_page' );

//  add_menu_page('Transference Toplevel', 'Transference', 8, __FILE__, 'transference_toplevel_page');
}

/**
 *
 */function multiadd_category() {
    if (!empty($_POST) && $_POST['hidden'] == 1){

	$itblog_cat_id = wp_create_category( 'IT Blog' );
	$qarea_cat_id  = wp_create_category( 'Qarea Post' );
//	$article_cat_id  = wp_create_category( 'Articles' );

	if(!empty($_POST['expert'])){
	$arg = [
		'posts_per_page' => - 1,
        'author_name'=> 'qarea-expert'
	];
	
	$query = new WP_Query( $arg );
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<li>' . get_the_ID() . '</li>';

			$result = wp_set_object_terms( get_the_ID(), [ (int) $itblog_cat_id ], 'category', true );
			if ( is_wp_error( $result ) ) {
		        echo $result->get_error_message();
			}


		}
	}
	wp_reset_query();
	}
	if(!empty($_POST['team'])){
	$arg2 = [
		'posts_per_page' => - 1,
		'author_name'    => 'qarea-team'
	];
	$query_2 = new WP_Query( $arg2 );
	if ( $query_2->have_posts() ) {
		while ( $query_2->have_posts() ) {
			$query_2->the_post();
			echo '<li>' . get_the_ID() . '</li>';
			$result = wp_set_object_terms( get_the_ID(), [ (int) $qarea_cat_id ], 'category', true );
			if ( is_wp_error( $result ) ) {
		        echo $result->get_error_message();
			    }
		    }
	    }
	    wp_reset_query();
	}
	if (!empty($_POST['addtag'])){
	    $arg3 = [
		'posts_per_page' => - 1,
		'author_name'    => 'qarea-team'
	];
	$query_3 = new WP_Query( $arg3 );
//	var_dump($query_3);
	if ( $query_3->have_posts() ) {
		while ( $query_3->have_posts() ) {
			$query_3->the_post();
			echo '<li>' . get_the_ID() . '</li>';
//			$result = wp_set_post_tags( get_the_ID(), 'article', false );
            $post = array(
                    "ID" => (int)get_the_ID(),
			         "tags_input" => "article");
            $result = wp_update_post($post);

			var_dump($result);

			if ( is_wp_error( $result ) ) {
		        echo $result->get_error_message();
			    }
		    }
	    }



	wp_reset_query();
	}
    }
	?>
	<form name="update" method="post"
          action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
		<?php wp_nonce_field( 'update-options' ); ?>

        <input type="hidden" name="hidden"
               value="1">

        <p><?php _e( "Select term type:", 'mt_trans_domain' );
			?>
            <br/>
        <p>
            <label>IT Blog</label>
            <input type="checkbox" name="expert" value="qarea-expert">
        </p>
        <p>
            <label>Qarea</label>
            <input type="checkbox" name="team" value="qarea-team">

        </p>
        <hr/>
          <p>
            <label>it blog (tag)</label>
            <input type="checkbox" name="addtag" value="addtag">

        </p>
        <hr/>
        <p class="submit">
            <input type="submit" name="Submit"
                   value="<?php _e( 'Update Posts', 'mt_trans_domain' ) ?>"/>
        </p>
    <?php

}

/**
 *
 */
function migrate_img() {

	global $wpdb;
	$q = $wpdb->get_results(
		$wpdb->prepare( /** @lang text */
			"SELECT ID, post_content FROM wp_posts WHERE post_type='post' ORDER BY post_title  ASC ", '' )
	);

	foreach ( $q as $post ) {
		$res       = str_replace( 'https://qarea.com/sites/default/files/', 'https://update.qarea.com/blog/wp-content/uploads/files/', $post->post_content );
		$post_data = [
			'ID'           => $post->ID,
			'post_content' => $res
		];
		$result    = wp_update_post( wp_slash( $post_data ) );

	}
	var_dump( $post_data );

}

/**
 *
 */
function update_term_count_options_page() {

	echo "<h2>Update Term count.</h2>";


	if ( $_POST['hidden'] == '1' ) {
		if ( ! empty( $_POST['category'] ) ) {
			$massage = update_term_count_proccess( $_POST['category'] ) ? 'Category Update Successful' : 'Error Update Category!!!';
			echo '<h2>' . $massage . '</h2>';


		}

		if ( ! empty( $_POST['post_tag'] ) ) {
			$massage = update_term_count_proccess( $_POST['post_tag'] ) ? 'Tags Update Successful' : 'Error Update Tags!';
			echo '<h2>' . $massage . '</h2>';
		}
//
	}
	?>
    <form name="update" method="post"
          action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
		<?php wp_nonce_field( 'update-options' ); ?>

        <input type="hidden" name="hidden"
               value="1">

        <p><?php _e( "Select term type:", 'mt_trans_domain' );
			?>
            <br/>
        <p>
            <label>Category</label>
            <input type="checkbox" name="category" value="category">
        </p>
        <p>
            <label>Tag</label>
            <input type="checkbox" name="post_tag" value="post_tag">

        </p>
        <hr/>
        <p class="submit">
            <input type="submit" name="Submit"
                   value="<?php _e( 'Update Term Count', 'mt_trans_domain' ) ?>"/>
        </p>
    </form>
	<?php

}

/**
 * @param $term_type
 *
 * @return bool
 */
function update_term_count_proccess( $term_type ) {
	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare( /** @lang text */
			"SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = '$term_type'", '' )
	);
//	var_dump( $results );
	$output = [];
	foreach ( $results as $term ) {
		$output[] = $term->term_id;
	}
	$res = wp_update_term_count( $output, $term_type );

	return $res;


}