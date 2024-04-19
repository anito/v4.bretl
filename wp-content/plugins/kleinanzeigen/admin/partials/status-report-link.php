<span id="status-report">
  <a href="." class="send-report" style="margin-right: 20px;"><?php echo __('Get the report now', 'kleinanzeigen'); ?></a>
  <span class="status-fields-wrapper">
    <span id="status-report-fields" class="boxed">
      <i class="dashicons indicator"></i>
      <span class="message"></span>
    </span>
  </span>
</span>

<style>
  #status-report {
    display: inline-flex;
    align-items: center;
  }

  #status-report-fields {
    display: flex;
    align-items: center;
  }

  #status-report-fields .indicator {
    padding-right: 10px;
  }

  #status-report .status-fields-wrapper {
    transition: all .3s ease-in-out;
    opacity: 0;
  }

  #status-report .status-fields-wrapper.active {
    opacity: 1;
  }
</style>