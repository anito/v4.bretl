<?php
function process_sales_cat($post_id, $term_id) {
	global $woocommerce;

	if( !defined( 'SALES_CAT_ID' ) )
		return;

	$post_id = intval($post_id);
	$term_id = intval($term_id);

	$product = wc_get_product($post_id);

	switch ($term_id) {
		case SALES_CAT_ID:
			$add = $product->is_on_sale();
			break;
		default:
			return 0;
	}
	set_product_cat($product, $term_id, $add);
}
function set_product_cat($product, $term_id, $add) {
	$term_ids = $product->get_category_ids();
	$term_ids = array_unique(array_map('intval', $term_ids));

	if ( !$add ) {
		# remove id
		$term_ids = array_diff($term_ids, array($term_id) );
	} else {
		# add id
		$term_ids[] = $term_id;
	}
	wp_set_object_terms( $product->get_id(), $term_ids, 'product_cat' );
}