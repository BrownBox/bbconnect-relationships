<?php
/**
 * Plugin Name: Connexions Relationships and Groups
 * Plugin URI: http://connexionscrm.com/
 * Description: Get a real understanding of how your contacts relate to each other using relationships and groups.
 * Version: 0.1
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * License: Proprietary Brown Box
 *
 * @todo Set name and description of plugin
 * @todo Replace all instances of "module" or "template" with relevant module name
 */
define('BBCONNECT_RELATIONSHIPS_VERSION', '0.1');
define('BBCONNECT_RELATIONSHIPS_DIR', plugin_dir_path(__FILE__));
define('BBCONNECT_RELATIONSHIPS_URL', plugin_dir_url(__FILE__));

require_once (BBCONNECT_RELATIONSHIPS_DIR.'db.php');
require_once (BBCONNECT_RELATIONSHIPS_DIR.'fx.php');
require_once (BBCONNECT_RELATIONSHIPS_DIR.'profile.php');

function bbconnect_relationships_init() {
    if (!defined('BBCONNECT_VER')) {
        add_action('admin_init', 'bbconnect_relationships_deactivate');
        add_action('admin_notices', 'bbconnect_relationships_deactivate_notice');
        return;
    }

    global $wpdb;
    $wpdb->bbconnect_relationships = $wpdb->prefix.'bbconnect_relationships';

    if (is_admin()) {
        // DB updates
        bbconnect_relationships_updates();
        // Plugin updates
        new BbConnectUpdates(__FILE__, 'BrownBox', 'bbconnect-relationships');
    }
}
add_action('plugins_loaded', 'bbconnect_relationships_init');

function bbconnect_relationships_deactivate() {
    deactivate_plugins(plugin_basename(__FILE__));
}

function bbconnect_relationships_deactivate_notice() {
    echo '<div class="updated"><p><strong>Connexions Relationships and Groups</strong> has been <strong>deactivated</strong> as it requires Connexions.</p></div>';
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}
