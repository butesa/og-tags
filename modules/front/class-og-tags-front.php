<?php

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/**
 * OG_Tags_Front
 * Class responsible to create the HTML Tags
 *
 * @package           OG_Tags_Front
 * @since             2.0.0
 *
 */

if ( ! class_exists( 'OG_Tags_Front' ) ) {

	class OG_Tags_Front {

		/**
		 * The Core object
		 *
		 * @since    2.0.0
		 * @access   public
		 * @var      OG_Tags    $core	The core class
		 */
		private $core;

		/**
		 * Options
		 *
		 * @since    2.0.0
		 * @access   public
		 * @var      array    $options	The options
		 */
		private $options = array();

		/**
		 * The Module Indentify
		 *
		 * @since    2.0.0
		 * @access   public
		 * @var      OG_Tags    $core	The core class
		 */
		const MODULE_SLUG = "front";

		/**
		 * Define the core functionality of the plugin.
		 *
		 * @since    2.0.0
		 * @param    array		$core	The Core object
		 * @param    array		$tag	The Core Tag
		 */
		public function __construct( OG_Tags $core ) {

			$this->core = $core;

		}

		/**
		 * Register all the hooks for this module
		 *
		 * @since    2.0.0
		 * @access   private
		 */
		private function define_hooks() {
			
			$this->add_action( 'wp_head', array( $this, 'insert_tags' ) );

		}

		/**
		 * Add a new action to the collection to be registered with WordPress.
		 *
		 * @since    2.0.0
		 * @see    OG_Tags->add_action
		 */
		private function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {

			if ( $this->core != null ) {
				$this->core->add_action( $hook, $callback, $priority, $accepted_args );
			} else {
				if ( WP_DEBUG ) {
					trigger_error( __( 'Core was not passed in "OG_Tags_Front".' ), E_USER_WARNING );
				}
			}

		}

		/**
		 * Add a new filter to the collection to be registered with WordPress.
		 *
		 * @since    2.0.0
		 * @see    OG_Tags->add_filter
		 */
		private function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {

			if ( $this->core != null ) {
				$this->core->add_filter( $hook, $callback, $priority, $accepted_args );
			} else {
				if ( WP_DEBUG ) {
					trigger_error( __( 'Core was not passed in "OG_Tags_Front".' ), E_USER_WARNING );
				}
			}

		}

		/**
		 * Just insert tags on head
		 *
		 * @since    2.0.0
		 */
		public function insert_tags() {
		$meta_tags= array();

			// Tags comuns
			$this->insert_commons_tags($meta_tags);

			if ( is_single() ) {
				$this->insert_post_tags($meta_tags);				
			} else {
				$this->insert_site_tags($meta_tags);
			}

			$meta_tags_filtered = array();
			foreach ($meta_tags as $name => $value) {
				if (!empty($value) && !empty($this->options['ogtags_tags'][$name])) {
					$meta_tags_filtered[$name] = $value;
				}
			}

			$meta_tags_filtered = apply_filters('og-tags-values', $meta_tags_filtered);

			echo '<!-- OG TAGS -->' . "\n";
			foreach ($meta_tags_filtered as $name => $value) {
				echo '<meta property="' . esc_attr($name) . '" content="' . esc_attr($value) . '">' . "\n";
			}
		}

		/**
		 * Tags for all pages
		 *
		 * @since    2.0.0
		 */
		private function insert_commons_tags(&$meta_tags) {

			$options = $this->get_options();

			$meta_tags['og:site_name'] = $options['ogtags_nomedoblog'];

			$fbadmins = explode( " ", $options['ogtags_fbadmins'] );
			foreach ( $fbadmins as $admin ) {
				$meta_tags['fb:admins'] = $admin;
			}

		}

		/**
		 * Tags for posts
		 *
		 * @since    2.0.0
		 */
		private function insert_post_tags(&$meta_tags) {

			$options = $this->get_options();

			// Título
			if ( $options['ogtags_debug_filter'] ) {
				$ogtitle = wp_title( '', false );
			} else {
				$ogtitle = wp_title( '|', false, 'right' ) . $options['ogtags_nomedoblog'];
			}

			// Descrioção
			if ($options['ogtags_compat_excerpt']) {
				$post = get_post();

				if (empty($post)) {
					$ogdescription = '';
				}
				elseif (post_password_required($post)) {
					$ogdescription = __( 'There is no excerpt because this is a protected post.' );
				}
				else {
					$ogdescription = wp_trim_excerpt($post->post_excerpt, $post);
				}
			} else {
				$ogdescription = get_the_excerpt(); 
			}

			// URL
			$ogurl = get_permalink();

			// Imagem
			if ( has_post_thumbnail() ) { 

				$ogimage = get_post_thumbnail_id();
				$ogimage = wp_get_attachment_image_src( $ogimage,'full', true );
				$ogimage = $ogimage[0];

			} else {

				$ogimage = $options['ogtags_image_default']; 

			}
			
			// Autor e Seção
			$articleauthor = get_the_author();
			$articlesection = get_the_category(); 
			$articlesection = $articlesection[0]->cat_name;

			// Tags
			$tags = wp_get_post_tags( get_the_ID() );
			
			foreach ( $tags as $tag ) {
				$articletag = $tag->name;
				$meta_tags['article:tag'] = $articletag;
			}

			$meta_tags['og:title'] = $ogtitle;
			$meta_tags['og:description'] = $ogdescription;
			$meta_tags['og:url'] = $ogurl;
			$meta_tags['og:type'] = 'article';
			$meta_tags['og:image'] = $ogimage;
			$meta_tags['article:section'] = $articlesection;
			$meta_tags['article:publisher'] = $options['ogtags_publisher'];

		}

		/**
		 * Tags for rest site
		 *
		 * @since    2.0.0
		 */
		private function insert_site_tags(&$meta_tags) {

			$options = $this->get_options();

			// Título
			if ( $options['ogtags_debug_filter'] ) {
				$ogtitle = wp_title( '', false );
			} else {
				$ogtitle = wp_title( '|', false, 'right' ) . " " . $options['ogtags_nomedoblog'];
			}

			$ogurl = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			$meta_tags['og:title'] = $ogtitle;
			$meta_tags['og:description'] = $options['ogtags_descricaodoblog'];
			$meta_tags['og:url'] = $ogurl;
			$meta_tags['og:type'] = 'website';
			$meta_tags['og:image'] = $options['ogtags_image_default'];

		}

		/**
		 * Get options
		 *
		 * @since    2.0.0
		 * @param 	 bool 	Retrieve from database
		 */
		private function get_options( $force = false ) {

			if ( $force || empty( $this->options ) ) {
				$this->options = get_option( 'ogtags_options' );
			}

			return $this->options;

		}

		/**
		 * Run the plugin.
		 *
		 * @since    2.0.0
		 */
		public function run() {

			$this->define_hooks();

		}

	}
}
