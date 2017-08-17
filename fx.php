<?php
/**
 * Get list of relationship types
 * @return array
 */
function bbconnect_relationships_get_relationship_types() {
    return array(
            'alias',
            'family',
            'personal',
            'professional',
    );
}

/**
 * Add relationship between 2 users
 * @param string $type Type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 */
function bbconnect_relationships_add_relationship($type, $user_id_a, $user_id_b) {
    global $wpdb;
    $format = array('%s', '%d', '%d');
    // Store relationship
    $data = array(
            'type' => $type,
            'user_id_a' => $user_id_a,
            'user_id_b' => $user_id_b,
    );
    $success = $wpdb->insert($wpdb->bbconnect_relationships, $data, $format);

    if ($success) {
        // And the inverse
        $data['user_id_a'] = $user_id_b;
        $data['user_id_b'] = $user_id_a;
        $success = $wpdb->insert($wpdb->bbconnect_relationships, $data, $format);
    }
    return $success;
}

/**
 * Update relationship between 2 users
 * @param string $old_type Existing type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 * @param string $new_type New relationship type
 */
function bbconnect_relationships_update_relationship($old_type, $user_id_a, $user_id_b, $new_type) {
    global $wpdb;
    $format = array('%s');
    $where_format = array('%s', '%d', '%d');
    // Update relationship
    $data = array(
            'type' => $new_type,
    );
    $where = array(
            'type' => $old_type,
            'user_id_a' => $user_id_a,
            'user_id_b' => $user_id_b,
    );
    $success = $wpdb->update($wpdb->bbconnect_relationships, $data, $where, $format, $where_format);

    if ($success) {
        // And the inverse
        $where['user_id_a'] = $user_id_b;
        $where['user_id_b'] = $user_id_a;
        $success = $wpdb->update($wpdb->bbconnect_relationships, $data, $where, $format, $where_format);
    }
    return $success;
}

/**
 * Remove relationship between 2 users
 * @param string $type Type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 */
function bbconnect_relationships_remove_relationship($type, $user_id_a, $user_id_b) {
    global $wpdb;
    $format = array('%s', '%d', '%d');
    // Delete relationship
    $where = array(
            'type' => $type,
            'user_id_a' => $user_id_a,
            'user_id_b' => $user_id_b,
    );
    $success = $wpdb->delete($wpdb->bbconnect_relationships, $where, $format);

    if ($success) {
        // And the inverse
        $where['user_id_a'] = $user_id_b;
        $where['user_id_b'] = $user_id_a;
        $success = $wpdb->delete($wpdb->bbconnect_relationships, $where, $format);
    }
    return $success;
}

/**
 * Get relationships for user
 * @param WP_User|integer $user User to retrieve relationships for. Can be either user ID or WP_User object.
 * @return array|boolean List of related users grouped by relationship type or false on error
 */
function bbconnect_relationships_get_user_relationships($user) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if ($user instanceof WP_User) {
        $relationships = array();
        global $wpdb;
        $sql = $wpdb->prepare('SELECT * FROM '.$wpdb->bbconnect_relationships.' WHERE user_id_a = %d', $user->ID);
        $results = $wpdb->get_results($sql);
        foreach ($results as $row) {
            $relationships[$row->type][$row->user_id_b] = $row->user_id_b;
        }
        ksort($relationships);
        return $relationships;
    }
    return false;
}

/**
 * Get second-level relationships for user
 * @param WP_User|integer $user User to retrieve relationships for. Can be either user ID or WP_User object.
 * @param array $relationships Optional. List of direct relationships grouped by relationship type to base query on.
 * @return array|boolean List of related users or false on error
 */
function bbconnect_relationships_get_user_second_level_relationships($user, array $relationships = array()) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if ($user instanceof WP_User) {
        if (empty($relationships)) {
            $relationships = bbconnect_relationships_get_user_relationships($user);
        }
        $second_relationships = array();
        // Get all relationships for the user's direct connections
        foreach ($relationships as $rel_type => $related_users) {
            $second_relationships[$rel_type] = array();
            foreach ($related_users as $rel_user_id) {
                $second_relationships[$rel_type] += bbconnect_relationships_get_user_relationships_by_type($rel_user_id, $rel_type);
            }
            // Now filter out the user themself and their direct connections
            foreach ($second_relationships[$rel_type] as $idx => $second_rel_user_id) {
                if ($second_rel_user_id == $user->ID || in_array($second_rel_user_id, $related_users)) {
                    unset($second_relationships[$rel_type][$idx]);
                }
            }
            if (empty($second_relationships[$rel_type])) {
                unset($second_relationships[$rel_type]);
            }
        }
        ksort($second_relationships);
        return $second_relationships;
    }
    return false;
}

/**
 * Get relationships of a specific type for user
 * @param WP_User|integer $user User to retrieve relationships for. Can be either user ID or WP_User object.
 * @param string $type Type of relationship to find.
 * @return array|boolean List of related users or false on error
 */
function bbconnect_relationships_get_user_relationships_by_type($user, $type) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if ($user instanceof WP_User) {
        $relationships = array();
        global $wpdb;
        $sql = $wpdb->prepare('SELECT * FROM '.$wpdb->bbconnect_relationships.' WHERE user_id_a = %d AND type = %s', $user->ID, $type);
        $results = $wpdb->get_results($sql);
        foreach ($results as $row) {
            $relationships[$row->user_id_b] = $row->user_id_b;
        }
        return $relationships;
    }
    return false;
}

