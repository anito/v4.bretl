<?php

// If this file is called directly, abort.
if (!defined('WPINC'))
{
  die();
}

// If class `Kleinanzeigen_Register_WC_Taxonomies` doesn't exists yet.
if (!class_exists('Kleinanzeigen_Register_WC_Taxonomies'))
{

  class Kleinanzeigen_Register_WC_Taxonomies extends Kleinanzeigen
  {

    private static $instance = null;

    private static $tax_definitions;

    private static $prefix = 'product';

    public function __construct()
    {

      self::$tax_definitions = array(
        'label' => array(
          'labels' => array(
            'name' => __('Product labels', 'kleinanzeigen'),
            'singular_name'              => __( 'Product label', 'kleinanzeigen' ),
            'menu_name'                  => __( 'Labels', 'kleinanzeigen'  ),
            'search_items'               => __( 'Search product labels', 'kleinanzeigen' ),
						'all_items'                  => __( 'All product labels', 'kleinanzeigen' ),
						'edit_item'                  => __( 'Edit product brand', 'kleinanzeigen' ),
						'update_item'                => __( 'Update product brand', 'kleinanzeigen' ),
						'add_new_item'               => __( 'Add new product brand', 'kleinanzeigen' ),
						'new_item_name'              => __( 'New product brand name', 'kleinanzeigen' ),
						'popular_items'              => __( 'Popular product labels', 'kleinanzeigen' ),
						'separate_items_with_commas' => __( 'Separate product labels with commas', 'kleinanzeigen' ),
						'add_or_remove_items'        => __( 'Add or remove product labels', 'kleinanzeigen' ),
						'choose_from_most_used'      => __( 'Choose from the most used product labels', 'kleinanzeigen' ),
						'not_found'                  => __( 'No product labels found', 'kleinanzeigen' ),
						'item_link'                  => __( 'Product brand link', 'kleinanzeigen' ),
						'item_link_description'      => __( 'A link to a product brand.', 'kleinanzeigen' ),
          ),
          'metaboxes' => array(
            'types' => array(
              'colorpicker' => array(
                '_background' => array('label' => __('Background', 'kleinanzeigen'), 'description' => __('Enter a background color.', 'kleinanzeigen')),
              ),
            )
          ),
        ),
        'brand' => array(
          'labels' => array(
            'name'                       => __( 'Product brands', 'kleinanzeigen' ),
            'singular_name'              => __( 'Product brand', 'kleinanzeigen' ),
            'menu_name'                  => __( 'Brands', 'kleinanzeigen' ),
            'search_items'               => __( 'Search product brands', 'kleinanzeigen' ),
						'all_items'                  => __( 'All product brands', 'kleinanzeigen' ),
						'edit_item'                  => __( 'Edit product brand', 'kleinanzeigen' ),
						'update_item'                => __( 'Update product brand', 'kleinanzeigen' ),
						'add_new_item'               => __( 'Add new product brand', 'kleinanzeigen' ),
						'new_item_name'              => __( 'New product brand name', 'kleinanzeigen' ),
						'popular_items'              => __( 'Popular product brands', 'kleinanzeigen' ),
						'separate_items_with_commas' => __( 'Separate product brands with commas', 'kleinanzeigen' ),
						'add_or_remove_items'        => __( 'Add or remove product brands', 'kleinanzeigen' ),
						'choose_from_most_used'      => __( 'Choose from the most used product brands', 'kleinanzeigen' ),
						'not_found'                  => __( 'No product brands found', 'kleinanzeigen' ),
						'item_link'                  => __( 'Product brand link', 'kleinanzeigen' ),
						'item_link_description'      => __( 'A link to a product brand.', 'kleinanzeigen' ),
          ),
          'metaboxes' => array(
            'types' => array(
              'image' => array(
                'image' => array('label' => __('Logo', 'kleinanzeigen'), 'description' => __('Upload brand logo.', 'kleinanzeigen')),
                'hero-image' => array('label' => __('Hero image', 'kleinanzeigen'), 'description' => __('Upload hero image.', 'kleinanzeigen'))
              ),
              'text' => array(
                'template-shortcode' => array('label' => __('Template shortcode', 'kleinanzeigen'), 'description' => __('Enter a shortcode.', 'kleinanzeigen')),
                'quellenangabe' => array('label' => __('Source reference', 'kleinanzeigen'), 'description' => __('Enter a source reference.', 'kleinanzeigen')),
              )
            )
          ),
        )
      );

      $this->load_dependencies();
      $this->create_taxonomies();
      $this->register();
    }

    public function load_dependencies()
    {
      require_once wbp_ka()->plugin_path('includes/class-utils.php');
    }

    public function register()
    {
      add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
      add_action('init', array($this, 'copy'), 999);
    }

    public function enqueue_scripts()
    {
      $screen = get_current_screen();
      foreach (self::$tax_definitions as $key => $val)
      {
        if ("edit-product_{$key}" === $screen->id)
        {
          $admin_dir_url = Kleinanzeigen_Admin::get_instance()->admin_dir_url();
          wp_enqueue_media();
          wp_enqueue_style('wp-color-picker');
          wp_enqueue_script(self::$plugin_name . '-wp-color-picker', $admin_dir_url . 'js/wp-color-picker-alpha.min.js', array('wp-color-picker'), self::$version, true);

          wp_enqueue_style(self::$plugin_name . '-custom-taxonomies', $admin_dir_url . 'css/admin-taxonomy-styles.css');
          break;
        }
      }
    }

    public function create_taxonomies()
    {
      $taxonomies = self::$tax_definitions;

      foreach ($taxonomies as $taxonomy => $args)
      {
        $name = self::$prefix . "_{$taxonomy}";
        add_action("{$name}_add_form", array($this, "add_nonce"));
        add_action("{$name}_edit_form", array($this, "add_nonce"));
        $this->create_taxonomy($name, $args);
      }
    }

    public function create_taxonomy($_taxonomy, $args)
    {
      $args = wp_parse_args($args, array(
        'labels' => array(),
        'metaboxes' => array(
          'types' => array()
        )
      ));

      register_taxonomy(
        $_taxonomy,
        'product',
        array(
          'hierarchical'          => false,
          'labels'                => $args['labels'],
          'query_var'             => true,
          'rewrite'               => true,
          'public'                => true,
          'show_ui'               => true,
          'show_admin_column'     => false,
          'capabilities'          => array(
            'manage_terms' => 'manage_product_terms',
            'edit_terms'   => 'edit_product_terms',
            'delete_terms' => 'delete_product_terms',
            'assign_terms' => 'assign_product_terms',
          ),
          'show_in_rest'          => true,
          'rest_base'             => $_taxonomy,
          'rest_controller_class' => 'WP_REST_Terms_Controller',
        )
      );

      foreach ($args['metaboxes']['types'] as $type => $val)
      {
        add_action("{$_taxonomy}_add_form_fields", array($this, "add_{$type}_meta_field"), 999);
        add_action("{$_taxonomy}_edit_form_fields", array($this, "add_{$type}_meta_field"), 999);
        add_action("created_term", array($this, 'save_meta'), 10, 3);
        add_action("edited_term", array($this, 'save_meta'), 10, 3);
      }
    }

    public function add_nonce()
    {
      echo wp_nonce_field("edit_nonce", "edit_nonce");
    }

    /**
     * This will add the custom meta field to the add new term page.
     *
     * @return void
     */
    function add_text_meta_field($term)
    {
      $result = $this->get_fields($term, 'text');
      extract($result);

      foreach ($keys as $key => $val)
      {
        $value = 'add' === $hook_type ? '' : get_term_meta($term->term_id, $key, true);
        $term_value = $is_empty ? '' : (is_array($value) ? (count($value) ? $value[0] : '') : $value);
        $is_empty = empty($term_value);
        $label = $val['label'];
        $description = $val['description'];
        $taxonomy_key = $taxonomy . "[{$key}]";
?>

        <tr class="form-field term-slug-wrap">
          <th scope="row"><label for="<?php echo $taxonomy_key; ?>" class="form-field-label"><?php echo $label ?></label></th>
          <td>
            <input type="text" name="<?php echo $taxonomy_key; ?>" id="<?php echo $taxonomy_key; ?>" value="<?php echo $term_value; ?>" />
            <p class="description" id="<?php echo $taxonomy . '-description' ?>">
              <?php echo esc_html($description); ?>
            </p>
          </td>
        </tr>

        <?php
      }
    }

    function add_image_meta_field($term)
    {
      $result = $this->get_fields($term, 'image');
      extract($result);

      foreach ($keys as $key => $val)
      {
        $admin_dir_url = Kleinanzeigen_Admin::get_instance()->admin_dir_url();
        $placeholder = $admin_dir_url . 'images/placeholder.png';
        $value = 'add' === $hook_type ? '' : get_term_meta($term->term_id, $key, true);
        $term_value = $is_empty ? '' : (is_array($value) ? (count($value) ? $value[0] : '') : $value);
        $is_empty = empty($term_value);
        $img_value = $is_empty ? $placeholder : wp_get_attachment_url($term_value);
        $label = $val['label'];
        $description = $val['description'];
        $taxonomy_key = $taxonomy . "[{$key}]";
        $image_id = $taxonomy . "_{$key}";

        if ('add' === $hook_type) : ?>
          <div class="image-uploader image-uploader-<?php echo $image_id; ?>">
          <?php else : ?>
            <tr class="form-field term-slug-wrap image-uploader image-uploader-<?php echo $image_id; ?>">
            <?php endif ?>
            <th scope="row"><label for="<?php echo $taxonomy_key; ?>" class="form-field-label"><?php echo $label ?></label></th>
            <td>
              <input type="hidden" value="<?php echo $term_value; ?>" name="<?php echo $taxonomy_key; ?>" class="image_post_id" />
              <div class="image-preview" style="position: relative; display: inline-block;">
                <img src="<?php echo $img_value; ?>" width="100" id="<?php echo $image_id; ?>" />
                <div role="button" class="cancel cancel-button" style="position: absolute; top: 0px; right: 5px; color: red; font-weight: 600; display: none;" id="cancel_button">x</div>
              </div>
              <input type="button" value="Upload" class="button upload_image_trigger" data-key="<?php echo $taxonomy_key; ?>" />
              <p class="description" id="<?php echo $taxonomy . '-description' ?>">
                <?php echo esc_html($description); ?>
              </p>
            </td>
            <?php if ('add' === $hook_type) : ?>
          </div>
        <?php else : ?>
          </tr>
        <?php endif ?>

        <script>
          jQuery(document).ready(function($) {

            const image_id = "<?php echo $image_id; ?>"
            const placeholder = "<?php echo $placeholder; ?>"
            const is_empty = "<?php echo $is_empty; ?>"
            const {
              uploader_init
            } = TaxonomyUtils;
            TaxonomyUtils = {
              ...TaxonomyUtils,
              placeholder
            }
            uploader_init(image_id, is_empty)

          })
        </script>

      <?php
      }
    }

    function add_colorpicker_meta_field($term)
    {

      $result = $this->get_fields($term, 'colorpicker');
      extract($result);

      foreach ($keys as $key => $val)
      {
        $value = 'add' === $hook_type ? '' : get_term_meta($term->term_id, $key, true);
        $term_value = empty($value) ? "#0000ff" : (is_array($value) ? (count($value) ? $value[0] : '') : $value);
        $is_empty = empty($term_value);
        $label = $val['label'];
        $description = $val['description'];
        $taxonomy_key = $taxonomy . "[{$key}]";
      ?>

        <tr class="form-field term-slug-wrap">
          <th scope="row"><label for="<?php echo $taxonomy_key; ?>" class="form-field-label"><?php echo $label ?></label></th>
          <td>
            <input type="text" data-alpha-enabled="true" value="<?php echo $term_value; ?>" name="<?php echo $taxonomy_key; ?>" data-default-color="#ffff00" id="<?php echo $taxonomy_key; ?>" class="color-picker" />
            <p class="description" id="<?php echo $taxonomy . '-description' ?>">
              <?php echo esc_html($description); ?>
            </p>
          </td>
        </tr>

<?php
      }
    }

    private function get_fields($term, $field)
    {
      // Suffice both the {taxonomy}_add_form_field and {taxonomy}_edit_form_field hooks with different signatures...
      $hook_type = is_string($term) ? 'add' : 'edit';
      $_taxonomy = 'edit' === $hook_type ? $term->taxonomy : ('add' === $hook_type ? $term : '');

      if (!empty($_taxonomy) && isset($_GET['taxonomy']))
      {
        $taxonomy = $_GET['taxonomy'];
        $key = str_replace(self::$prefix . '_', '', $taxonomy);

        if (isset(self::$tax_definitions[$key]['metaboxes']['types'][$field]))
        {
          $keys = self::$tax_definitions[$key]['metaboxes']['types'][$field];
          return  compact('keys', 'taxonomy', 'hook_type');
        }
      }
      else
      {
        die();
      }
    }

    public function save_meta($id, $tt_id, $taxonomy)
    {
      if (isset($_POST['edit_nonce']) && !wp_verify_nonce($_POST['edit_nonce'], 'edit_nonce'))
      {
        return;
      }
      if (isset($_POST['taxonomy']) && isset($_POST[$taxonomy]))
      {
        $terms = $_POST[$taxonomy];
        foreach ($terms as $key => $val)
        {
          update_term_meta($id, $key, $val);
        }
      }
    }

    public function copy()
    {
      // $this->copy_terms('brand_74', 'brand');
      // $this->copy_terms('label_74', 'label');
    }

    public function copy_terms($from, $to)
    {
      $_to = "product_{$to}";
      $_from = "product_{$from}";
      $args = array('taxonomy' => $_from, 'hide_empty' => false);
      $terms = get_terms($args);

      foreach ($terms as $term)
      {
        $info = true;
        if (term_exists($term->slug, $_to))
        {
          $info = false;
          $_term = get_term_by('slug', $term->slug, $_to);
          if($_term) {
            $info = wp_delete_term($_term->term_id, $_to);
          }
        }
        if($info) {
          $data = wp_insert_term($term->name, $_to, array(
            'alias_of'    => '',
            'description' => $term->description,
            'parent'      => 0,
            'slug'        => $term->slug,
          ));
          if(!is_wp_error($data)) {
            $_term = get_term_by('slug', $term->slug, $_to);
            $term_meta = get_term_meta($term->term_id);
            Utils::write_log($term_meta);
            foreach($term_meta as $key => $val) {
              update_term_meta($_term->term_id, $key, $val);
            }
            Utils::write_log(get_term_meta($_term->term_id));
          }
        }
      }

      $products = wc_get_products(array(
        'status' => array('draft', 'pending', 'private', 'publish'),
        'limit' => -1
      ));

      foreach ($products as $product)
      {
        $post_ID = $product->get_id();

        $terms = wbp_th()->get_product_terms($post_ID, $from);
        if ($terms)
        {
          $term_slugs = wp_list_pluck($terms, 'slug');
          foreach ($terms as $term)
          {
            $data = wp_set_object_terms($post_ID, $term_slugs, $_to, false);
          }
        }
      }
    }

    public function create_terms()
    {
      if (defined('WC_TERMS'))
      {

        $tags = get_terms('product_tag', array('hide_empty' => false));
        $tag_names = wp_list_pluck($tags, 'name');

        // $this->term_handler->
        foreach (WC_TERMS as $slug => $names)
        {
          if (!is_array($names))
          {
            $names = array($names);
          }

          foreach ($names as $name)
          {
            if (!in_array($name, $tag_names))
            {
              wbp_th()->add_the_product_term($name, 'tag');
            }
          }
        }
      }
    }

    public function create_attribute_taxonomies()
    {
      if (!class_exists('WooCommerce', false))
      {
        return;
      }

      $taxonomies = wc_get_attribute_taxonomies();
      if (defined('WC_CUSTOM_PRODUCT_ATTRIBUTES'))
      {
        foreach (WC_CUSTOM_PRODUCT_ATTRIBUTES as $name)
        {
          $labels = wp_list_pluck($taxonomies, 'attribute_label');
          $labels = array_flip($labels);
          if (empty($labels[$name]))
          {
            wc_create_attribute([
              'name' => $name,
              'has_archives' => 1
            ]);
          }
        }
      }
    }

    public function create_taxonomy_product_labels()
    {
      $terms = get_terms('product_label', array('hide_empty' => false));
      $term_names = wp_list_pluck($terms, 'name');

      if (defined('WC_PRODUCT_LABELS'))
      {

        foreach (WC_PRODUCT_LABELS as $name)
        {

          if (!in_array($name, $term_names))
          {
            wbp_th()->add_the_product_term($name, 'label');
          }
        }
      }
    }

    public static function get_instance($file = null)
    {
      // If the single instance hasn't been set, set it now.
      if (null == self::$instance)
      {
        self::$instance = new self;
      }
      return self::$instance;
    }
  }
}

if (!function_exists('wbp_rt'))
{

  /**
   * Returns instance of the plugin class.
   *
   * @since  1.0.0
   * @return Kleinanzeigen_Register_WC_Taxonomies
   */
  function wbp_rt(): Kleinanzeigen_Register_WC_Taxonomies
  {
    return Kleinanzeigen_Register_WC_Taxonomies::get_instance();
  }
}

wbp_rt();
