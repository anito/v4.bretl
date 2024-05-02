<?php $prefix = $prefix ?? __('An error occurred', 'kleinanzeigen'); ?>
<section>
  <h2><?php echo __('Error', 'kleinanzeigen') ?></h2>
  <div class="notice notice-error is-dismissible">
    <p>
      <?php echo sprintf('<strong>%1$s:</strong> %2$s', $prefix, $message); ?>
    </p>
  </div>

  <p>
    <a href="<?php echo admin_url('/admin.php?page=kleinanzeigen') ?>">Erneut versuchen</a>
  </p>
</section>