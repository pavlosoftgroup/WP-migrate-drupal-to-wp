<?php

/*
Plugin Name: Transference Drupal
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: varaksin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

include 'database.func.php';

add_action( 'admin_menu', 'transference_admin_function' );


/**
 *
 */
function transference_admin_function() {
	add_options_page( 'Transference Database Options', 'Transference Database Options', 8, 'transferenceoptions', 'transference_options_page' );
	add_menu_page( 'Transference Manage', 'Transference Manage', 8, 'transferencemanage', 'transference_manage_page' );

//  add_menu_page('Transference Toplevel', 'Transference', 8, __FILE__, 'transference_toplevel_page');
}

/**
 *
 */
function transference_options_page() {
	add_option( 'num_page' );
	add_option( 'node_type' );
	update_option( 'num_page', 1 );
	add_option( 'checked_drupal_input' );


	$database_name = 'transference_database';
	$user_name     = 'transference_user';
	$database_pass = 'transference_pass';
	$database_host = 'transference_host';

	add_option( $database_name );
	add_option( $user_name );
	add_option( $database_pass );
	add_option( $database_host, 'localhost' );
	add_option( 'database_status', false );
	$hidden_field_name = 'transference_submit_hidden';

	$database_val = get_option( $database_name );
	$user_val     = get_option( $user_name );
	$pass_val     = get_option( $database_pass );
	$host_val     = get_option( $database_host );

	echo "<h2>Transference Database Options</h2>";

	if ( $_POST[ $hidden_field_name ] == 'Y' ) {
		$database_val = $_POST[ $database_name ];
		$user_val     = $_POST[ $user_name ];
		$pass_val     = $_POST[ $database_pass ];
		$host_val     = $_POST[ $database_host ];

		update_option( $database_name, $database_val );
		update_option( $user_name, $user_val );
		update_option( $database_pass, $pass_val );
		update_option( $database_host, $host_val );
	}

	$test_connect = false;
	if ( $database_val & $user_val & $pass_val & $host_val ) {
		if ( transference_db_connect( $user_val, $pass_val, $database_val, $host_val ) ) {
			update_option( 'database_status', true );
			$test_connect = true;
		}
	}
	$checked = $_POST['checked_drupal_input'] == 'on' ? 'checked' : '';
	update_option( 'checked_drupal_input', $checked );

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
        <p><?php _e( "Checked all post:", 'mt_trans_domain' ); ?>
            <br/>
            <input type="checkbox" <?php print get_option( 'checked_drupal_input' ); ?> name="checked_drupal_input"
                   id="checked_drupal_input">
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
                <a href="tools.php?page=transferencemanage">Select node type.</a>
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
function transference_manage_page() {
//	var_dump(  get_option( 'num_page' ) );
//	$num_page = get_option( 'num_page' );
	$num_page = isset( $_POST['num_page'] ) ? $_POST['num_page'] : 1;

	echo "<h1>Transference Manage</h1>";


	$node_types  = transference_drupal_node_types();
	$submit_text = 'Export nodes select type';
	$page_title  = '<h3>Select type nodes for import</h3>';

	if ( ! empty( $_POST['trns'] ) ) {
		if ( isset( $_POST['node_type'] ) ) {
			$node_type = $_POST['node_type'];
			update_option( 'node_type', $node_type );
		}
		$node_type = isset( $node_type ) ? $node_type : get_option( 'node_type' );
//			var_dump( $_POST['num_page']  );

		if ( isset( $_POST['num_page'] ) ) {
			$import_result = transferens_import_nodes( $_POST );
			$num_page      = (int) $num_page + 1;
			update_option( 'num_page', $num_page );

		}
//		$num_page = get_option( 'num_page' );

		$nodes       = transferens_get_posts( $node_type, $num_page );
		$submit_text = '' . 'Import select post';
		$page_title  = '<h2>Page <b>' . $num_page . '</b></h2><p>Select nodes for import:</p>';


	}

	if ( empty( $nodes ) && $num_page != 1 ) {
		echo '<h2>Import finished!</h2>';

		return true;
	}
	echo isset( $import_result ) ? '<h2>Import reporting:</h2>' . $import_result : '';
	echo $page_title;
	?>

    <form name="form1" method="post"
          action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
		<?php wp_nonce_field( 'update-options' );

		if ( empty( $nodes ) && $num_page == 1 ) {

			?>

            <input type="hidden" name="trns" value="Y">


            <p><?php _e( "Select node type:", 'mt_trans_domain' ); ?>
                <br/>
                <select name="node_type">
					<?php print transferens_node_types_option_render( $node_types ); ?>
                </select>
            </p>

		<?php } else {
			?>
            <input type="hidden" name="trns" value="N">

            <input type="hidden" name="num_page" value=<?php echo $num_page ?>>

			<?php
			$i = 1;
			foreach ( $nodes as $node ) {
				?>
                <p>
                    <input type="checkbox" <?php print get_option( 'checked_drupal_input' ); ?>
                           name="<?php print  $node->nid; ?>"
                           id="<?php print $node->nid; ?>">
                    <label for="<?php print  $node->nid; ?>"><?php print  'No:' . $i . ' ' . $node->title; ?></label>
                </p>
				<?php
				$i ++;
			}
		}

		?>
        <hr/>
        <p class="submit">
            <input type="submit" name="Submit"
                   value="<?php print $submit_text; ?>"/>
        </p>

    </form>
	<?php
}

/**
 *
 */
function transference_toplevel_page() {
	echo "<h2>Transference Toplevel</h2>";
}



