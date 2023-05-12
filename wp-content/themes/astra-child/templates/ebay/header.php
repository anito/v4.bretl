<section class="wbp-ebay-sub-header">
	<h2><small><?php echo sprintf(__('    Anzeigen: %s (Gesamt: %d)', 'wbp'), count($data->ads), $total); ?></small></h2>
	<h4><small>
		<?php foreach ($categories as $category) { ?>
			<?php echo sprintf(__('%s (%s)', 'wbp'), $category->title, $category->totalAds) ?>
			<?php } ?>
		</small></h4>
		
	<h3><?php echo sprintf(__('Seite: %s', 'wbp'), $page) ?></h3>
	<div class="pagination">
		<?php for ($i = 1; $i <= $pages; $i++) {
		?>
			<a href="<?php echo EBAY_CUSTOMER_URL . '?pageNum=' . $i ?>" type="button" class="button <?php echo ($i == (int) $page ? ' button-primary' : '') ?>" name="page_number"><?php echo $i ?></a>
		<?php } ?>
	</div>
</section>