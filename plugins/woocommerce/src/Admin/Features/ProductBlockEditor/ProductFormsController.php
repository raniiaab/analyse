<?php
/**
 * WooCommerce Product Forms Controller
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;

/**
 * Handle retrieval of product forms.
 */
class ProductFormsController {

	/**
	 * Product form templates.
	 *
	 * @var array
	 */
	private $product_form_templates = array(
		'simple',
	);

	/**
	 * Set up the product forms controller.
	 */
	public function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingFinal, WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag -- Not an injection.
		add_action( 'upgrader_process_complete', array( $this, 'migrate_templates_when_plugin_updated' ), 10, 2 );
		add_action( 'the_content', array( $this, 'maybe_add_product_form_templates' ), 10, 2 );
	}

	/**
	 * Maybe add product form templates to the posts array.
	 */
	public function maybe_add_product_form_templates( $content ) {
		$post = get_post();

		if ( 'product_form' === $post->post_type && 'Simple' === $post->post_title ) {
			$simple = new SimpleProductTemplate();
			return $simple->get_comment_delimited_template();
		}
		return $content;
	}

	/**
	 * Migrate form templates after WooCommerce plugin update.
	 *
	 * @param \WP_Upgrader $upgrader The WP_Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 * @return void
	 */
	public function migrate_templates_when_plugin_updated( \WP_Upgrader $upgrader, array $hook_extra ) {
		// If it is not a plugin hook type, bail early.
		$type = isset( $hook_extra['type'] ) ? $hook_extra['type'] : '';
		if ( 'plugin' !== $type ) {
			return;
		}

		// If it is not the WooCommerce plugin, bail early.
		$plugins = isset( $hook_extra['plugins'] ) ? $hook_extra['plugins'] : array();
		if (
			! in_array( 'woocommerce/woocommerce.php', $plugins, true )
		) {
			return;
		}

		// If the action is not install or update, bail early.
		$action = isset( $hook_extra['action'] ) ? $hook_extra['action'] : '';
		if ( 'install' !== $action && 'update' !== $action ) {
			return;
		}

		// Trigger the migration process.
		$this->migrate_product_form_posts( $action );
	}

	/**
	 * Create or update a product_form post for each product form template.
	 * If the post already exists, it will be updated.
	 * If the post does not exist, it will be created even if the action is `update`.
	 *
	 * @param string $action - The action to perform. `insert` | `update`.
	 * @return void
	 */
	public function migrate_product_form_posts( $action ) {
		/**
		 * Allow extend the list of templates that should be auto-generated.
		 *
		 * @since 9.1.0
		 * @param array $templates List of templates to auto-generate.
		 */
		$templates = apply_filters(
			'woocommerce_product_form_templates',
			$this->product_form_templates
		);

		foreach ( $templates as $slug ) {
			$file_path = BlockTemplateUtils::get_block_template_path( $slug );

			if ( ! $file_path ) {
				continue;
			}

			$file_data = BlockTemplateUtils::get_template_file_data( $file_path );

			$posts = get_posts(
				array(
					'name'           => $slug,
					'post_type'      => 'product_form',
					'post_status'    => 'any',
					'posts_per_page' => 1,
				)
			);

			/*
			 * Update the the CPT post if it already exists,
			 * and the action is `update`.
			 */
			if ( 'update' === $action ) {
				$post = $posts[0] ?? null;

				if ( ! empty( $post ) ) {
					wp_update_post(
						wp_slash(
							array(
								'ID'           => $post->ID,
								'post_title'   => $file_data['title'],
								'post_content' => BlockTemplateUtils::get_template_content( $file_path ),
								'post_excerpt' => $file_data['description'],
							)
						)
					);
				}
			}

			/*
			 * Skip the post creation if the post already exists.
			 */
			if ( ! empty( $posts ) ) {
				continue;
			}

			$post = wp_insert_post(
				wp_slash(
					array(
						'post_title'   => $file_data['title'],
						'post_name'    => $slug,
						'post_status'  => 'publish',
						'post_type'    => 'product_form',
						'post_content' => BlockTemplateUtils::get_template_content( $file_path ),
						'post_excerpt' => $file_data['description'],
					)
				)
			);
		}
	}
}
