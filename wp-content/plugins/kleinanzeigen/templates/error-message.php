<section>
  <h2>Fehler</h2>
  <p>
    <?php echo 'Daten konnten nicht abgerufen werden';
    ?>
  </p>
  <p>
    <code><?php echo $message ?></code>
  </p>
  <p>
    <a href="<?php echo admin_url('/admin.php?page=kleinanzeigen') ?>">Erneut versuchen</a>
  </p>
</section>