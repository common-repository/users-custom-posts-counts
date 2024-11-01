<?php
/**
 * Provides an About page for any plugin.
 *
 * @package    WP_Relevant_Ads
 * @subpackage WP_Relevant_Ads/admin
 * @author     Sebet
 */

if ( ! class_exists('scbUtil') ) {
	require_once dirname( __FILE__ ) . '/Util.php';
}

if ( ! class_exists('scbAdminPage') ) {
	require_once dirname( __FILE__ ) . '/AdminPage.php';
}

if ( ! class_exists('scbBoxesPage') ) {
	require_once dirname( __FILE__ ) . '/BoxesPage.php';
}

/**
 * The main About page class.
 */
if ( ! class_exists('BC_About_Page') ) {
class BC_About_Page extends scbBoxesPage {


	/**
	 * The plugin ID.
	 *
	 * @var string $plugin_id
	 */
	private $plugin_id;

	/**
	 * The plugin description.
	 *
	 * @var string $description
	 */
	private $description;

	/**
	 * The plugin name.
	 *
	 * @var string $name
	 */
	private $name;

	/**
	 * The plugin version.
	 *
	 * @var string $version
	 */
	private $version;

	/**
	 * The menu page title.
	 *
	 * @var string $page_title.
	 */
    private $page_title;

	/**
	 * The menu parent name.
	 *
	 * @var string $parent.
	 */
    private $parent;

	/**
	 * The plugin domain.
	 *
	 * @var string $domain
	 */
	private $domain;

	/**
	 * The plugin post type.
	 *
	 * @var string $post_type
	 */
    private $post_type;

	/**
	 * The collection of plugin authors.
	 *
	 * @var array $authors
	 */
	private $authors;

	function __construct( $file = false, $options = null, $args = array() ) {

		$defaults = array(
			'name'        => 'Plugin',
			'domain'      => '',
			'version'     => '1.0',
			'post_type'   => '',
			'page_title'  => 'About',
			'parent'      => 'bc-about',
			'description' => '',
			'authors'     => array(),
			'plugin_id'   => 'bc-about',
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		$this->name        = $name;
		$this->domain      = $domain;
		$this->version     = $version;
		$this->post_type   = $post_type;
		$this->page_title  = $page_title;
		$this->parent      = $parent;

		$this->plugin_id   = $plugin_id;
		$this->description = $description;
		$this->authors     = $authors;

		parent::__construct( $file, $options );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	function setup() {

		$this->args = array(
			'page_title'            => $this->page_title,
			'page_slug'             => $this->get_page_slug(),
			'parent'                => $this->get_parent_page_slug(),
			'columns'               => 2,
			'action_link'           => false,
			'admin_action_priority' => 99999,
		);

		$this->boxes = array(
			array( 'about', __( 'Author', $this->get_domain() ), 'side' ),
			array( 'products', __( 'Other Products from the Author', $this->get_domain() ), 'normal' ),
		);

		if ( $this->description ) {
			$this->boxes[] = array( 'about_product', $this->name, 'side' );
		}

		$url = "http://bruno-carreco.com/wpuno/fetch-products.php?username=sebet&type=recommended";

		$response = wp_remote_get( $url );

		if ( ! is_wp_error($response) && ! empty( $response['body'] ) ) {
			$this->boxes[] = array( 'recommended', __( 'Recommended 3rd Party Products', $this->get_domain() ), 'normal' );
		}

		$this->boxes = apply_filters( 'bc_about_boxes', $this->boxes );
	}

	/**
	 * The box for the plugin authors.
	 */
	function about_box() {
		$authors = 0;
?>
		<table width="100%" class="bc-about-author-products">
			<tr>
				<td class="bc-about-author-authors">

					<?php foreach( $this->get_author() as $author ): ?>

						<?php if ( empty( $author['name'] ) ) continue; ?>

						<?php if ( $authors ) : ?>
							<p>&nbsp;</p>
						<?php endif;?>

						<div class="bc-about-author-author">
							<?php echo ! empty( $author['avatar'] ) ? $author['avatar'] : get_avatar( 0, 55 ); ?>
							<span class="bc-about-author-author-name"><?php echo $author['name']; ?></span>
							<div class="bc-about-author-author-bio">
								<p><?php echo ! empty( $author['bio'] ) ? $author['bio'] : ''; ?></p>
							</div>
							<?php if ( ! empty( $author['social'] ) ): ?>
								<div class="bc-about-social">
									<?php foreach( $author['social'] as $social_id => $url ): ?>

										<a href="<?php echo $url; ?>" title="<?php echo $this->get_social( $social_id, 'description' ); ?>"><div class="dashicons dashicons-<?php echo $social_id; ?>"></div></a>

									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php $authors++; ?>

					<?php endforeach; ?>

				</td>
			</tr>
		</table>
<?php
	}

	/**
	 * The box for the plugin description.
	 */
	function about_product_box() {
?>
		<table width="100%" class="bc-about-author-products">
			<tr>
				<td class="bc-about-author-product">
					<p><?php echo $this->description; ?></p>
				</td>
			</tr>
		</table>
<?php
	}

	/**
	 * The box for the authors products.
	 */
	function products_box() {
		$this->products_output();
	}

	/**
	 * The box for the recommended products.
	 */
	function recommended_box() {
		$this->products_output('recommended');
	}

	function products_output( $type = 'products' ) {

		$url = "http://bruno-carreco.com/wpuno/fetch-products.php?username=sebet&type=$type";

		$response = wp_remote_get( $url );

		$xml = new SimpleXMLElement( $response['body'] );

		$products = $xml->products->product;
?>
		<?php if ( ! empty( $products ) ): ?>

			<?php
				foreach( $products as $product ) {
					$sorted_products[ strtotime( $product->date ) ] = $product;
				}

				krsort( $sorted_products );
			?>

			<table width="100%" class="bc-about-author-products">
				<?php
					foreach( $sorted_products as $key => $product ):

						$pid = $this->get_plugin_id();

						if ( $pid == (string) $product->ID ) {
							continue;
						}

				?>
					<tr>
						<td>
							<?php if ( ! empty( $product->thumbnail ) ): ?>
								<img class="bc-about-product-thumb" src="<?php echo esc_url( $product->thumbnail ); ?>">
							<?php endif; ?>
							<p class="bc-about-product-title"><?php echo html_link( $product->url, $product->title ); ?></p>
						</td>
						<td>
							<?php if ( ! empty( $product->demo ) ): ?>
								<a class="button right" href="<?php echo esc_url( $product->demo ); ?>"><?php echo __( 'Demo', $this->get_domain() ); ?></a>
							<?php endif; ?>

							<?php if ( ! empty( $product->buy ) ): ?>

								<?php if ( 'soon' != $product->buy ): ?>
									<a class="button button-primary right" href="<?php echo esc_url( $product->buy ); ?>"><?php echo __( 'Info', $this->get_domain() ); ?></a>
								<?php else: ?>
									<a class="button button right disabled" ><?php echo __( 'Coming Soon', $this->get_domain() ); ?></a>
								<?php endif; ?>

							<?php else: ?>
								<a class="button button-primary right" href="<?php echo esc_url( $product->url ); ?>"><?php echo __( 'Free Download', $this->get_domain() ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p><?php echo $product->description; ?></p>
						</td>
					</tr>

					<?php if ( ! empty( $product->promotional->$pid->code ) ): ?>

						<tr>
							<td colspan="2">
								<div class="bc-about-author-action-options">
									<p class="bc-about-author-promo"><?php echo sprintf( __( '<span class="bc-about-author-promo-desc">%s</span> when you use the code <span class="bc-about-author-promo-code">\'%s\'</span>', $this->get_domain() ), $product->promotional->$pid->description, $product->promotional->$pid->code ) ?></p>
								</div>
							</td>
						</tr>

					<?php endif; ?>

					<tr>
						<td class="bc-about-author-authors-inline">
							<?php foreach( $product->authors->author as $author ): ?>
								<div class="bc-about-author-author">
									<?php echo get_avatar( (string) $author->avatar, 35 ); ?>
									<span class="bc-about-author-author-name"><?php echo html_link( $author->url, $author->name ); ?></span>
								</div>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<td class="bc-about-author-theme" colspan="2">
								<fieldset>
									<legend><?php echo __( 'Requirements', $this->get_domain() ); ?></legend>
									<?php if ( ! empty( $product->theme) ): ?>
										<?php echo html_link( $product->theme->url, $product->theme->name ); ?>
									<?php else: ?>
										<span><?php echo __( 'None', $this->get_domain() ); ?></span>
									<?php endif; ?>
								</fieldset>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="bc-about-author-product-sep">&nbsp;</div>
						</td>
					</tr>

				<?php endforeach; ?>
			</table>

		<?php else: ?>

			<p><?php echo __( 'Couldn\'t retrieve products from remote server. Please try again later.', $this->get_domain() ); ?>

		<?php endif; ?>
<?php

	}

	### methods

   /**
	* Retrieves the domain name to use for location strings.
	*/
   public function get_domain() {
	   return $this->domain;
   }

   /**
	* Retrieves the plugin version.
	*/
   public function get_version() {
	   return $this->version;
   }

   /**
	* Retrieves the about parent page slug.
	*/
   public function get_parent_page_slug() {
		if ( $this->parent === $this->post_type ) {
			return 'edit.php?post_type=' . $this->post_type;
		}
		return $this->parent;
   }

   /**
	* Retrieves the about page slug.
	*/
   public function get_page_slug() {
	   return $this->get_plugin_id() . '_bc_about';
   }

   /**
	* Retrieves the plugin ID.
	*/
   public function get_plugin_id() {
	   return $this->plugin_id;
   }

   /**
	* Enqueue styles.
	*/
   public function enqueue_scripts( $hook ) {

		if ( stripos( $hook, $this->get_page_slug() ) !== FALSE ) {
		   wp_register_style( 'bc-about', plugin_dir_url( __FILE__ ) . 'styles.css', false, $this->get_version() );
		   wp_enqueue_style( 'bc-about');
	   }

   }

	/**
	* About me.
	*/
	public function get_author( $author_id = 0 ) {

		$author = array(
			'avatar'    => get_avatar( 'bcarreco@gmail.com', 55 ),
			'website'   => 'http://www.bruno-carreco.com',
			'contact'   => 'http://www.bruno-carreco.com/contact',
			'name'      => html_link( 'http://www.bruno-carreco.com/', 'SebeT' ),
			'bio'       => 'Web Developer and WordPress Ninja. Creative thinker, adept of the clean, simple and flexible.',
			'portfolio' => array(
				'elocriativo' => 'http://www.elocriativo.com',
			),
			'social' => array(
				'wordpress' => 'https://profiles.wordpress.org/sebet',
				'twitter'   => 'https://twitter.com/bruno_carreco',
				'email-alt' => 'http://www.bruno-carreco.com/contact',
			),
		);

		$authors[] = $author;
		$authors[] = $this->authors;

		if ( empty( $author_id ) || empty( $authors[ $author_id ] ) ) {
			return $authors;
		} else {
			return $authors[ $author_id ];
		}

   }

   /**
	* Retrieve meta for social networks.
	*/
   public function get_social( $social_id = '', $part = '' ) {

	   $social = array(
		   'wordpress' => array(
				'name'        => __( 'WordPress', $this->get_domain() ),
				'description' => __( 'WordPress Profile', $this->get_domain() ),
		   ),
		   'twitter' => array(
				'name'        => __( 'Twitter', $this->get_domain() ),
				'description' => __( 'Follow me on Twitter', $this->get_domain() ),
		   ),
		   'email-alt' => array(
				'name'        => __( 'Email', $this->get_domain() ),
				'description' => __( 'Contact Me', $this->get_domain() ),
		   ),
	   );

	   if ( empty( $social_id ) || empty( $social[ $social_id ] ) ) {
		   return $social;
	   } elseif( ! empty( $social[ $social_id ][ $part ] ) ) {
		   return $social[ $social_id ][ $part ];
	   } else {
		   return $social[ $social_id ];
	   }

   }

}
}