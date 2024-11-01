<?php
/*
Plugin Name: Simple Related Products for WooCommerce
Description: Simple Related Products for WooCommerce allows you to choose custom related products for your WooCommerce products, under Linked Products.
Version:     1.5.1
Plugin URI:  https://agarwalmohit.com
Author:      Mohit Agarwal
*/

 function my_admin_notice(){
    global $pagenow;
    if ( $pagenow == 'plugins.php' ) {
		if (!is_plugin_active('woocommerce/woocommerce.php') ){
			 echo '<div class="error">
				 <p>';
				 
				 _e( 'Plugin NOT activated: WooCommerce plugin is not active ', 'woocommerce' ) ;
				 
			 echo'</p>
			 </div>';
			deactivate_plugins(plugin_basename(__FILE__));
		}
    }
}
add_action('admin_notices', 'my_admin_notice');



function srpw_plugin_deactivation( $plugin, $network_activation ) {
    if ($plugin=='woocommerce/woocommerce.php')
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
add_action( 'deactivated_plugin', 'srpw_plugin_deactivation', 10, 2 );


// Returns compulsory display bool.
function srpw_comp_display( $result, $product_id ) {
	$all_related_prods = get_post_meta( $product_id, '_all_related_prods', true );
	return empty( $all_related_prods ) ? $result : true;
}
add_filter( 'woocommerce_product_related_posts_force_display', 'srpw_comp_display', 10, 2 );

// Returns taxanomy requirements. Depends if user selected something or not.
function srpw_taxonomy_relation( $result, $product_id ) {
	$all_related_prods = get_post_meta( $product_id, '_all_related_prods', true );
	if ( ! empty( $all_related_prods ) ) {
		return false;
	} else {
		return $result;
	}
}
add_filter( 'woocommerce_product_related_posts_relate_by_tag', 'srpw_taxonomy_relation', 10, 2 );
add_filter( 'woocommerce_product_related_posts_relate_by_category', 'srpw_taxonomy_relation', 10, 2 );

// Add products selector to edit screen
function srpw_select_related_products() {
	global $post, $woocommerce;
	$product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_all_related_prods', true ) ) );
	?>
	<div class="options_group">
		<?php if ( $woocommerce->version >= '3.0' ) : ?>
			<p class="form-field">
				<label for="all_related_prods"><?php _e( 'Related Products', 'woocommerce' ); ?></label>
				<select class="wc-product-search" name="all_related_prods[]" data-exclude="<?php echo intval( $post->ID ); ?>" multiple="multiple" style="width: 50%;" id="all_related_prods" data-placeholder="<?php esc_attr_e( 'Search for a product', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products">
					<?php
						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
							}
						}
					?>
				</select> <?php echo wc_help_tip( __( 'Related products are displayed on the product details page. If you keep this empty, a random product from the same category would show.', 'woocommerce' ) ); ?>
			</p>
		<?php else: ?>
			<h1> Your version of WooCommerce is not compatible with this plugin.</h1>
		<?php endif; ?>
	</div>
	<?php
}
add_action('woocommerce_product_options_related', 'srpw_select_related_products');

//Save info from srpw_select_related_products screen
function srpw_save_related_products( $post_id, $post ) {
	global $woocommerce;
	if ( isset( $_POST['all_related_prods'] ) ) {
		
			$related = array();
			$ids = array_map( 'sanitize_text_field', $_POST['all_related_prods'] );
			foreach ( $ids as $id ) {
				if ( $id && $id > 0 ) { $related[] = absint( $id ); }
			}
			
		update_post_meta( $post_id, '_all_related_prods', $related );
	} else {
		delete_post_meta( $post_id, '_all_related_prods' );
	}
}
add_action( 'woocommerce_process_product_meta', 'srpw_save_related_products', 10, 2 );

//modify the product args.
function srpw_filter_related_products_legacy( $args ) {
	global $post;
	$related = get_post_meta( $post->ID, '_all_related_prods', true );
	if ($related) { // no filter
		$args['post__in'] = $related;
	}
	return $args;
}
add_filter( 'woocommerce_related_products_args', 'srpw_filter_related_products_legacy' );


//modify the query.
function srpw_filter_related_products( $query, $product_id ) {
	$all_related_prods = get_post_meta( $product_id, '_all_related_prods', true );
	if ( ! empty( $all_related_prods ) && is_array( $all_related_prods ) ) {
		$all_related_prods = implode( ',', array_map( 'absint', $all_related_prods ) );
		$query['where'] .= " AND p.ID IN ( {$all_related_prods} )";
	}
	return $query;
}
add_filter( 'woocommerce_product_related_posts_query', 'srpw_filter_related_products', 20, 2 );


