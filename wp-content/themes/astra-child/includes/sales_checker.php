<?php

####### Fix Sales ########
function fix_sales_handler_from_post($id) {

	if ( !empty($_POST["fix_all_sales"]) ) {
		foreach ($_POST as $collection => $value)  {
            if ( !empty( $value['product_id'] ) && !empty($value['sales_id'] ) ) {
				$product_id = $value['product_id'];
                $sales_id = $value['sales_id'];

				process_sales_cat($product_id, $sales_id);
			}
		}
	} else {
		foreach ($_POST as $collection) {
            if ( array_key_exists('submit', $collection) && !empty( $collection['product_id'] ) && !empty( $collection['sales_id'] ) ) {
                $product_id = $collection['product_id'];
                $sales_id = $collection['sales_id'];

                process_sales_cat($product_id, $sales_id);
            }
		}
    }
}
function add_shortcode_sales_checker() {

    add_shortcode( 'my_sales','shortcode_check_sales_handler' );

}
function shortcode_check_sales_handler($atts) {

    $default_atts =[];

    $atts = shortcode_atts($atts, $default_atts );
    $sales_id = $atts['cat_id'];

    return sales_checker_start($sales_id);

}
function delete_sales_output ($sales_id) {

	$args = array(
		'taxonomy' => 'product_cat',
		'include'    => $sales_id
	);

	$product_category = get_terms( $args )[0];

	$my_sales_name = $product_category->name;
	$my_sales_count = $product_category->count;

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => 10000000,
		'product_cat' => $my_sales_name
	);

	$products = get_posts( $args );

	$results = array("output" => [], "error_count" => 0);

    $html = '';
    $error_count = 0;
	foreach( $products as $product )  {
		$product = wc_get_product($product->ID);

		if($product->is_on_sale() === FALSE) {

			set_query_var( 'product', $product );
            set_query_var( 'sales_id', $sales_id );
            set_query_var( 'button_title', 'Reparieren' );

            ob_start();
            get_template_part('custom-templates/custom', 'sales-checker-item');
            $html .= ob_get_clean();

			$error_count++;
			$results["output"] = $html;
		}
		$results["error_count"] = $error_count;
	}
	return $results;
}

function add_sales_output($sales_id) {

	$ids = wc_get_product_ids_on_sale();

	$error_count = 0;

	$results = array("output" => [], "product_ids" => [], "error_count" => 0);

    $html = '';
	foreach($ids as $id) {
		$product = wc_get_product( $id );
		$product_id = $id;

		if($product->is_type('variation')) {
			$variation = new WC_Product_Variation($product);
			$product_id = $variation->get_parent_id();
			$product = wc_get_product($product_id);
		}

		if(!in_array( $product_id, $results["product_ids"] )) {

            $cat_ids = $product->get_category_ids();

			if( !in_array($sales_id, $cat_ids) ) {
//				build_output();

                set_query_var( 'product', $product );
                set_query_var( 'sales_id', $sales_id );
                set_query_var( 'button_title', 'Reparieren' );

                ob_start();
                get_template_part('custom-templates/custom', 'sales-checker-item');
                $html .= ob_get_clean();

				$error_count++;

			}
			$results["output"] = $html;
			$results["product_ids"][] = $product_id;
		}
		$results["error_count"] = $error_count;
    }

    return $results;
}

function sales_checker_start($sales_id) {

    wp_enqueue_style('sales-checker', get_stylesheet_directory_uri() . '/css/sales-checker.css', wp_get_theme()->get('Version'));

    set_query_var( 'sales_id', $sales_id );

    ob_start();
    get_template_part('custom-templates/custom', 'sales-checker');
    return ob_get_clean();

}
