<?php
include plugin_dir_path( __FILE__ ) . 'settings-page.php';
add_action('admin_menu', 'create_plugin_options_page');
function create_plugin_options_page() {
   add_options_page('Up Vote', 'Up Vote Settings', 'administrator', __FILE__, 'options_page_fn');
}