<?php
function bbconnect_relationships_updates() {
    // Get current version
    $dbv = get_option('_bbconnect_relationships_version', 0);

    // If it's not the latest, run our updates
    if (version_compare($dbv, BBCONNECT_RELATIONSHIPS_VERSION, '<')) {
        // List of versions that involved a DB update - each one must have a corresponding function below
        $db_versions = array(
                '0.1',
        );

        foreach ($db_versions as $version) {
            if (version_compare($version, $dbv, '>')) {
                call_user_func('bbconnect_relationships_db_update_'.str_replace('.', '_', $version));
                update_option('_bbconnect_relationships_version', $version);
            }
        }
        update_option('_bbconnect_relationships_version', BBCONNECT_RELATIONSHIPS_VERSION);
    }
}

function bbconnect_relationships_db_update_0_1() {
    global $wpdb;
    $new_table = "CREATE TABLE IF NOT EXISTS ".$wpdb->bbconnect_relationships." (
                    type VARCHAR(32) DEFAULT 'alias',
                    user_id_a BIGINT(20),
                    user_id_b BIGINT(20),
                    PRIMARY KEY (type, user_id_a, user_id_b),
                    KEY (user_id_a)
                ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $wpdb->query($new_table);
}
