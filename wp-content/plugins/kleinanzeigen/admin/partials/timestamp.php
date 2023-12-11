<?php setcookie('kleinanzeigen-job-items-count', 12) ?>
<div class="timestamp-wrap">
  <div class="timestamp" id="wbp-timestamp">
    <span><?php echo __('Loading', 'kleinanzeigen') ?> ...</span>
  </div>
</div>


<script>
  jQuery(document).ready(async ($) => {

    const {
      admin_ajax,
      displayTime,
      getCronJobs,
      getCookie,
      display,
      CreateScheduledJobs,
    } = {
      ...KleinanzeigenUtils,
      ...KleinanzeigenAjax
    };

    const cronlistCallback = async (cb) => {

      if (cb && 'function' === typeof cb) cb();

      // clear all scheduled jobs
      scheduledJobs.clear();

      const {
        jobs,
        jobResults
      } = JSON.parse(await getCronJobs()).data;

      init(jobs, jobResults);
    }

    let intervalId;
    let intervalId_wait;
    const el = () => $('#wbp-timestamp');

    const init = (jobs, jobResults) => {

      const sortByNext = (a, b) => a.timestamp < b.timestamp;
      jobs = jobs.sort(sortByNext);

      const renderQueue = [];
      jobs.forEach((job) => {

        const {
          timestamp
        } = job;

        const remaining = timestamp - new Date().getTime();


        remaining > 0 && renderQueue.push({
          job,
          remaining
        });
      })

      const next = renderQueue.shift();
      if (next) {

        const {
          job,
          remaining
        } = next;

        // Add to schedule (to be rendered as count down)
        scheduledJobs.add(job, remaining);

        /**
         * Refresh table list only if any jobs have been done
         * Not available on kleinanzeigen-settings view
         */
        const counts = jobResults.filter(jr => parseInt(jr.count) !== 0)
        if (counts.length) {
          display?.();
        }

        clearInterval(intervalId);
        intervalId = setInterval(render, 1000, el(), {
          job,
          template: templates('tmr') // Template for displaying remaining
        })

      } else {
        render(el(), {
          template: templates('ntd') // Template "Nothing to do"
        })
        intervalId_wait = setInterval(cronlistCallback, 5000, () => clearInterval(intervalId_wait));
      }
    }

    const templates = (id) => {

      const tmpl = {
        tmr: (job) => {
          const delta = job.timestamp - new Date().getTime();
          return delta >= 0 ? `<span class="label">Next job: ${job.slug}</span><span class="value">${displayTime(delta)}</span>` : `<span>busy...</span>`;
        },
        ntd: `<span class="label">Nothing to do</span>`
      }
      return tmpl[id];
    }

    const render = (el, {
      job,
      template
    }) => {
      if ('function' === typeof template) {
        html = template(job);
      } else {
        html = template;
      }
      el.html(html)
    }

    const scheduledJobs = CreateScheduledJobs.getInstance();
    scheduledJobs._callback = cronlistCallback;

    cronlistCallback()

  });
</script>

<style>
  .timestamp-wrap {
    position: fixed;
    right: 0px;
    bottom: 0px;
    z-index: 999;
    margin-bottom: 10px;
    margin-right: 10px;
  }

  #wbp-timestamp {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.8em;
    min-width: 130px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #3c434a;
    color: #ddd;
    border-radius: 99px;
    padding: 4px 10px 3px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    line-height: 1em;
    font-weight: 300;
  }

  #wbp-timestamp .label {
    margin-right: 10px;
  }

  #wbp-timestamp .value {
    font-weight: 500;
  }
</style>