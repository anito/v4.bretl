<?php
/**
 * Product loop adult flash
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $product, $wc_cpdf;;

$badge_style = get_theme_mod('bubble_style','style1');

if($badge_style == 'style1') $badge_style = 'circle';
if($badge_style == 'style2') $badge_style = 'square';
if($badge_style == 'style3') $badge_style = 'frame';
?>

<div class="badge-container absolute for-adult loop left top z-1">
<?php if ( is_adult_product() ) : ?>
	<?php
		$text = __( 'Ab 18!', 'halehmann' );
	?>
	<?php echo apply_filters( 'woocommerce_sale_flash', '<div class="callout badge badge-'.$badge_style.'"><div class="badge-inner secondary for-adult"><span class="for-adult">' .  $text . '</span></div></div>', $post, $product ); ?>

<?php endif; ?>
<?php echo apply_filters( 'flatsome_product_labels', '', $post, $product, $badge_style); ?>
</div>
