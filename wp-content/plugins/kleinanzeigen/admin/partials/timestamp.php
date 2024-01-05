<div class="timestamp-wrap">
  <div class="timestamp" id="wbp-timestamp">
    <span><?php echo __('Loading', 'kleinanzeigen') ?>...</span>
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
        todos,
        completed
      } = JSON.parse(await getCronJobs()).data;

      init(jobs, todos, completed);
    }

    let intervalId;
    let intervalId_wait;
    const el = () => $('#wbp-timestamp');

    const init = (jobs, todos, completed) => {

      console.log('jobs', jobs);
      console.log('todos', todos);
      console.log('completed', completed);

      /**
       * Refresh table list if any jobs have been done
       * Not available on kleinanzeigen-settings view
       */
      if (completed.length) {
        display?.();
      }

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

        clearInterval(intervalId);
        intervalId = setInterval(render, 1000, el(), {
          job,
          template: templates('timer') // Template for displaying remaining time and job name
        })

      } else {
        render(el(), {
          template: templates('bored') // Template "Bored"
        })
        intervalId_wait = setInterval(cronlistCallback, 5000, () => clearInterval(intervalId_wait));
      }
    }

    const templates = (id) => {

      const tmpl = {
        timer: (job) => {
          const delta = job.timestamp - new Date().getTime();
          return delta >= 0 ? `<span class="value">${displayTime(delta)}</span><span class="label">${job.name}</span>` : `<span>Busy...</span>`;
        },
        bored: `<span class="label">Boring...</span>`,
        stop: `<span class="label">Cron stopped</span>`
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


    function cron_start() {
      cronlistCallback();
    }

    function cron_stop() {
      clearInterval(intervalId_wait);
      clearInterval(intervalId);

      render(el(), {
        template: templates('stop') // Template "Cron stopped"
      })
    }

    cron_start();
    // cron_stop();

  });
</script>

<style>
  #wbp-timestamp {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.8em;
    display: flex;
    align-items: center;
    background: #3c434a;
    color: #ddd;
    border-radius: 0 9px 9px;
    padding: 4px 10px 3px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    line-height: 1em;
    font-weight: 300;
  }

  #wbp-timestamp .label {
    display: flex;
    align-items: center;
    text-transform: capitalize;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 80%;
  }

  #wbp-timestamp .value {
    margin-right: 10px;
  }
</style>