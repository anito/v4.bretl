<?php
function astra_child_admin_enqueue_scripts($hook)
{
  if ($hook === 'profile.php' || $hook === 'user-edit.php')
  {
    add_thickbox();
    wp_enqueue_script('media-upload');
    wp_enqueue_media();
  }
}
add_action("admin_enqueue_scripts", "astra_child_admin_enqueue_scripts");

function astra_child_admin_media_scripts()
{
  $title = __('Select or Upload an Custom Avatar', 'astra-child');
  $text = __('Select Avatar', 'astra-child');
?>
  <script>
    jQuery(document).ready(function($) {
      const title = "<?php echo $title; ?>";
      const text = "<?php echo $text; ?>";
      $(document).on('click', '.avatar-image-upload', function(e) {
        e.preventDefault();
        var $button = $(this);
        var file_frame = wp.media.frames.file_frame = wp.media({
          title,
          library: {
            type: 'image' // mime type
          },
          button: {
            text
          },
          multiple: false
        });
        file_frame.on('select', function() {
          var attachment = file_frame.state().get('selection').first().toJSON();
          $button.siblings('#wbp-custom-avatar').val(attachment.url);
          $button.siblings('.custom-avatar-preview').attr('src', attachment.url);
        });
        file_frame.open();
      });
    });
  </script>
<?php
}
add_action('admin_print_footer_scripts-profile.php', 'astra_child_admin_media_scripts');
add_action('admin_print_footer_scripts-user-edit.php', 'astra_child_admin_media_scripts');

function astra_child_custom_user_profile_fields($profileuser)
{
?>
  <h3><?php _e('Custom Local Avatar', 'astra-child'); ?></h3>
  <table class="form-table wbp-avatar-upload-options">
    <tr>
      <th>
        <label for="image"><?php _e('Avatar', 'astra-child'); ?></label>
      </th>
      <td>
        <?php
        // Check whether we saved the custom avatar, else return the default avatar.
        $custom_avatar = get_the_author_meta('wbp-custom-avatar', $profileuser->ID);
        if ($custom_avatar == '')
        {
          $custom_avatar = get_avatar_url($profileuser->ID);
        }
        else
        {
          $custom_avatar = esc_url_raw($custom_avatar);
        }
        ?>
        <img style="width: 96px; height: 96px; display: block; margin-bottom: 15px;" class="custom-avatar-preview" src="<?php echo $custom_avatar; ?>">
        <input type="text" name="wbp-custom-avatar" id="wbp-custom-avatar" value="<?php echo esc_attr(esc_url_raw(get_the_author_meta('wbp-custom-avatar', $profileuser->ID))); ?>" class="regular-text" />
        <input type='button' class="avatar-image-upload button-primary" value="<?php esc_attr_e("Upload Image", "astra-child"); ?>" id="uploadimage" /><br />
        <span class="description">
          <?php _e('Please upload a custom avatar for your profile, to remove the avatar simple delete the URL and click update.', 'astra-child'); ?>
        </span>
      </td>
    </tr>
  </table>
<?php
}
add_action('show_user_profile', 'astra_child_custom_user_profile_fields', 10, 1);
add_action('edit_user_profile', 'astra_child_custom_user_profile_fields', 10, 1);


function astra_child_save_local_avatar_fields($user_id)
{
  if (current_user_can('edit_user', $user_id))
  {
    if (isset($_POST['wbp-custom-avatar']))
    {
      $avatar = esc_url_raw($_POST['wbp-custom-avatar']);
      update_user_meta($user_id, 'wbp-custom-avatar', $avatar);
    }
  }
}
add_action('personal_options_update', 'astra_child_save_local_avatar_fields');
add_action('edit_user_profile_update', 'astra_child_save_local_avatar_fields');

function astra_child_get_avatar_url($url, $id_or_email, $args)
{
  $id = '';
  if (is_numeric($id_or_email))
  {
    $id = (int) $id_or_email;
  }
  elseif (is_object($id_or_email))
  {
    if (!empty($id_or_email->user_id))
    {
      $id = (int) $id_or_email->user_id;
    }
  }
  else
  {
    $user = get_user_by('email', $id_or_email);
    $id = !empty($user) ?  $user->data->ID : '';
  }
  $custom_url = $id ?  get_user_meta($id, 'wbp-custom-avatar', true) : '';

  if ($custom_url == '' || !empty($args['force_default']))
  {
    return $url;
  }
  else
  {
    return esc_url_raw($custom_url);
  }
}
add_filter('get_avatar_url', 'astra_child_get_avatar_url', 10, 3);
