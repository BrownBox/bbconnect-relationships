<?php
/**
 * Plugin Name: Connexions Relationships and Groups
 * Plugin URI: http://connexionscrm.com/
 * Description: Get a real understanding of how your contacts relate to each other using relationships and groups.
 * Version: 0.1.1
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * License: Proprietary Brown Box
 */
define('BBCONNECT_RELATIONSHIPS_VERSION', '0.1.1');
define('BBCONNECT_RELATIONSHIPS_DIR', plugin_dir_path(__FILE__));
define('BBCONNECT_RELATIONSHIPS_URL', plugin_dir_url(__FILE__));

require_once (BBCONNECT_RELATIONSHIPS_DIR.'db.php');
require_once (BBCONNECT_RELATIONSHIPS_DIR.'fx.php');
require_once (BBCONNECT_RELATIONSHIPS_DIR.'profile.php');

function bbconnect_relationships_init() {
    if (!defined('BBCONNECT_VER') || version_compare(BBCONNECT_VER, '2.5.7', '<')) {
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
    echo '<div class="updated"><p><strong>Connexions Relationships and Groups</strong> has been <strong>deactivated</strong> as it requires Connexions (v2.5.7 or later).</p></div>';
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

add_filter('bbconnect_activity_types', 'bbconnect_relationships_activity_types');
function bbconnect_relationships_activity_types($types) {
    $types['relationships'] = 'Relationship';
    $types['groups'] = 'Groups';
    return $types;
}

add_filter('bbconnect_activity_icon', 'bbconnect_relationships_activity_icon', 10, 2);
function bbconnect_relationships_activity_icon($icon, $activity_type) {
    if ($activity_type == 'relationships' || $activity_type == 'groups') {
        $icon = plugin_dir_url(__FILE__).'images/activity-icon.png';
    }
    return $icon;
}
