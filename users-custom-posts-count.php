<?php
/**
 * Plugin Name: Users Custom Posts Counts
 * Plugin URI:  http://www.bruno-carreco.com/plugins/wordpress/users-custom-posts-counts
 * Description: Simple plugin that adds a new column showing custom type posts counts on the users list. Users have the option to choose the post type to use. It works just like the Posts column.
 * Version:     1.1
 * Author:      Bruno Carre&ccedil;o
 * Author URI:  http://www.bruno-carreco.com
 * License:     GPL v2
 * Icon by:     Freepik (http://www.freepik.com)
 */

define( 'UCPC_VERSION', '1.1' );

register_activation_hook(__FILE__, 'ucpc_activation');

add_filter( 'manage_users_custom_column', 'ucpc_manage_users_custom_column', 10, 3 );
add_filter( 'manage_users_columns', 'ucpc_manage_users_columns' );
add_action( 'admin_menu', 'ucpc_admin' );
add_action( 'admin_init', '_init_ucpc' );
add_action( 'after_setup_theme', '_init_about' );

/**
 * Set defaults on plugin activation.
 */
function ucpc_activation() {

	$args = array(
	  'public'   => true,
	  '_builtin' => false
	);
	$post_types = get_post_types( $args, 'objects');

	$def_ptype = 'page';

	if ( ! empty( $post_types ) ) {
		$def_ptype = $post_types[0]->name;
	}

	$options = array();

	$options['post_type'] = $def_ptype;

	// Set new option.
	add_option('ucpc_options', $options, '', 'yes');
}

/**
 * Registers the options.
 */
function _init_ucpc() {
	register_setting( 'ucpc_options', 'ucpc_options' );
}

/**
 * Initialize the about page.
 */
function _init_about() {

	if ( ! is_admin() ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/admin/about/class-bc-about.php';

	_ucpc_init_about_page();
}

/**
 * Initializes the About page.
 *
 * @since 1.1
 */
function _ucpc_init_about_page() {
	$description =
	__( 'A basic plugin that simply adds a new column showing the counts for a specific custom type posts on the users table list.' ) .
	__( 'Mirrors the Posts count column on the users table. The new column is sortable and filterable for easier convenience.' );

	$args =  array(
		'name'       => sprintf( 'Users Custom Posts Counts <span class="bc-about-product-version">v.%1$s</span>', UCPC_VERSION ),
		'plugin_id'  => 'ucpc',
		'domain'     => 'ucpc',
		'version'    => UCPC_VERSION,
		'parent'     => 'users-cpc-counts',
		'description'=> $description,
	);
	new BC_About_Page( __FILE__, null, $args );
}

/**
 * Based on the function get_posts_by_author_sql() that retrieve the post SQL based on capability, author, and type.
 * Changed it to have the same behaviour with custom posts as with a normal 'post'
 *
 * @param string $post_type 	Supports 'post', 'custom_post_types' or 'page'.
 * @param bool $full 			Optional.  Returns a full WHERE statement instead of just an 'andalso' term.
 * @param int $post_author 		Optional.  Query posts having a single author ID.
 * @return string 				SQL	WHERE code that can be added to a query.
 */
function ucpc_get_posts_by_author_sql($post_type, $full = TRUE, $post_author = NULL) {
	global $user_ID, $wpdb;

	// Private posts
	if ($post_type == 'post') {
		$cap = 'read_private_posts';
	// Private pages
	} elseif ($post_type == 'page') {
		$cap = 'read_private_pages';
	// Private custom posts
	} else {
		$cap = 'read_private_pages';
	}

	if ($full) {
		if (is_null($post_author)) {
			$sql = $wpdb->prepare('WHERE post_type = %s AND ', $post_type);
		} else {
			$sql = $wpdb->prepare('WHERE post_author = %d AND post_type = %s AND ', $post_author, $post_type);
		}
	} else {
		$sql = '';
	}

	$sql .= "(post_status = 'publish'";

	if (current_user_can($cap)) {
		// Does the user have the capability to view private posts? Guess so.
		$sql .= " OR post_status = 'private'";
	} elseif (is_user_logged_in()) {
		// Users can view their own private posts.
		$id = (int) $user_ID;
		if (is_null($post_author) || !$full) {
			$sql .= " OR post_status = 'private' AND post_author = $id";
		} elseif ($id == (int)$post_author) {
			$sql .= " OR post_status = 'private'";
		} // else none
	} // else none

	$sql .= ')';

	return $sql;
}

/**
 * Adds a new column to the user listing.
 *
 * @param string $output 		Not used.
 * @param bool $column_name 	New column name.
 * @param int $user_id 			User ID.
 * @return string 				The html column.
 */
function ucpc_manage_users_custom_column($output = '', $column_name, $user_id) {
    global $wpdb;

    if( $column_name !== 'post_type_count' )
        return;

	//get the post type selected by the user
	$options = get_option('ucpc_options');
	$options['post_type_label'] = get_post_type_object($options['post_type'])->label;

    $where = ucpc_get_posts_by_author_sql( $options['post_type'], true, $user_id );
    $result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

    return '<a href="' . admin_url("edit.php?post_type=".$options['post_type']."&author=$user_id") . '" title="'.__($options['post_type_label']).'">' . $result . '</a>';
}

/**
 * Renames the new user column.
 *
 * @param string $columns 	Columns array.
 * @return string 			Modified columns array.
 */
function ucpc_manage_users_columns($columns) {

	//get the post type selected by the user
	$options = get_option('ucpc_options');
	$options['post_type_label'] = get_post_type_object($options['post_type'])->label;

    $columns['post_type_count'] = ($options['post_type_label']?__($options['post_type_label']):__('New Custom Type? Please update UCPC options'));

    return $columns;
}


/**
 * Add a new submenu under the settings menu.
 */
function ucpc_admin() {
	add_menu_page( 'User Custom Posts Count', 'Users CPC Count', 'manage_options', 'users-cpc-counts', 'ucpc_options', 'dashicons-groups' );
}


/**
 * Displays the options page content.
 */
function ucpc_options() {

	$args = array(
		'public'   => true,
	);

	$post_types = get_post_types( $args, 'objects' );
?>
	<div class="wrap settings-wrap">
		<form method="post" id="ucpc_options" action="options.php">
			<?php
				// Get defaults.
				settings_fields('ucpc_options');
				$options = get_option('ucpc_options');
			?>

			<h2><?php _e('Settings'); ?> </h2>
			<p> <?php _e('Please select the Post Type you want to use for the User Post Counts'); ?>:</p>

			<?php _e('Post Type: '); ?>

			<select name="ucpc_options[post_type]" id="post_type">
				<?php foreach( $post_types as $post_type ): ?>

						<?php  if ( 'post' === $post_type->name ) continue; ?>

						<option value="<?php echo $post_type->name; ?>" <?php echo ($options['post_type']==$post_type->name?'selected':'') ?> ><?php echo $post_type->label . ' ('. $post_type->name .')'; ?></option>
				<?php endforeach; ?>
			</select>

			<p class="submit">
			<input type="submit" name="submit" class="button-primary" value="Update Options" />
			</p>
		</form>
	</div>
<?php
}
