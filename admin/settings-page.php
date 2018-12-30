<?php
add_action('admin_init', 'register_and_build_fields');
function register_and_build_fields() {
    register_setting('up_vote_plugin_options', 'up_vote_plugin_options', 'validate_setting');
    add_settings_section('main_section', 'Main Settings', 'section_cb', __FILE__);
    add_settings_field('vote_icon', 'Icon:', 'vote_icon_setting', __FILE__, 'main_section');
    add_settings_field('vote_enable', 'Enable in the post:', 'enable_in_the_post_setting', __FILE__, 'main_section');  
 }

function options_page_fn() {
?>
   <div id="theme-options-wrap" class="widefat">
      <h2>Up Vote settings</h2>
      <p>Change upVote plugin settins here</p>
      <hr />

      <form method="post" action="options.php" enctype="multipart/form-data">
         <?php settings_fields('up_vote_plugin_options'); ?>
         <?php do_settings_sections(__FILE__); ?>
         <p class="submit">
            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
         </p>
   </form>
   <h3>Shortcode usage :</h3>
      <div>
       In php: echo do_shortcode('[superawesomeupvote]'); 
       <br>
       In the post: [superawesomeupvote]
      </div>
</div>
<?php
}
// Vote Icon
function vote_icon_setting() {
   $options = get_option('up_vote_plugin_options');
   $items = array("Text", "ThumbsUp");
   echo "<select name='up_vote_plugin_options[vote_icon]'>";
   foreach ($items as $item) {
      $selected = ( $options['vote_icon'] === $item ) ? 'selected = "selected"' : '';
      echo "<option value='$item' $selected>$item</option>";
   }
   echo "</select>";
}

// Enable in the post
function enable_in_the_post_setting() {
    $options = get_option('up_vote_plugin_options');
    $items = array("Disable", "Enable");
    echo "<select name='up_vote_plugin_options[vote_enable]'>";
    foreach ($items as $item) {
       $selected = ( $options['vote_enable'] === $item ) ? 'selected = "selected"' : '';
       echo "<option value='$item' $selected>$item</option>";
    }
    echo "</select>";
 }


function validate_setting($up_vote_plugin_options) {
   $keys = array_keys($_FILES);
   $i = 0;
   foreach ($_FILES as $image) {
      // if a files was upload
      if ($image['size']) {
         // if it is an image
         if (preg_match('/(jpg|jpeg|png|gif)$/', $image['type'])) {
            $override = array('test_form' => false);
            $file = wp_handle_upload($image, $override);
            $up_vote_plugin_options[$keys[$i]] = $file['url'];
         } else {
            $options = get_option('up_vote_plugin_options');
            $up_vote_plugin_options[$keys[$i]] = $options[$logo];
            wp_die('No image was uploaded.');
         }
      }
      // else, retain the image that's already on file.
      else {
         $options = get_option('up_vote_plugin_options');
         $up_vote_plugin_options[$keys[$i]] = $options[$keys[$i]];
      }
      $i++;
   }
   return $up_vote_plugin_options;
}
function section_cb() {}
