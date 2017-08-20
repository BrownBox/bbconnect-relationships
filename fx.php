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

/**
 * Get groups for user
 * @param WP_User|integer $user User to retrieve groups for. Can be either user ID or WP_User object.
 * @return array|WP_Error List of groups or WP_Error
 */
function bbconnect_relationships_get_user_groups($user) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    $search_criteria = array(
            'field_filters' => array(
                    array(
                            'key' => '5',
                            'operator' => 'contains',
                            'value' => '"'.$user->ID.'"', // A little hacky, but because it's stored as a serialised array, this is the best way we've got to look for a specific ID
                    ),
            ),
    );
    return GFAPI::get_entries(bbconnect_relationships_get_group_form(), $search_criteria);
}

/**
 * Retrieve group details for the specified ID
 * @param integer $group_id
 * @return array|WP_Error GF entry or WP_Error
 */
function bbconnect_relationships_get_group($group_id) {
    return GFAPI::get_entry($group_id);
}

/**
 * Get list of users who are members of the specified group
 * @param array|integer $group GF entry or entry ID
 * @return array of WP_User objects
 */
function bbconnect_relationships_get_group_members($group) {
    if (!is_array($group)) {
        $group = bbconnect_relationships_get_group($group);
    }
    $user_ids = maybe_unserialize($group[5]);
    if (!empty($user_ids)) {
        $args = array(
                'include' => $user_ids,
        );
        return get_users($args);
    }
    return array();
}

/**
 * Add user to a group
 * @param WP_User|integer $user User to add to group. Can be either user ID or WP_User object.
 * @param array|integer $group GF entry or entry ID
 * @return boolean|WP_Error True on success, False if user already exists in group, WP_Error if something else went wrong
 */
function bbconnect_relationships_add_user_to_group($user, $group) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if (!is_array($group)) {
        $group = bbconnect_relationships_get_group($group);
    }
    $user_ids = maybe_unserialize($group[5]);
    if (!in_array($user->ID, $user_ids)) {
        $user_ids[] = (string)$user->ID; // Have to make sure it's a string else our search won't work
        $group[5] = maybe_serialize($user_ids);
        return GFAPI::update_entry($group);
    }
    return false;
}

/**
 * Remove user from a group
 * @param WP_User|integer $user User to remove from group. Can be either user ID or WP_User object.
 * @param array|integer $group GF entry or entry ID
 * @return boolean|WP_Error True on success, False if user doesn't exist in group, WP_Error if something else went wrong
 */
function bbconnect_relationships_remove_user_from_group($user, $group) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if (!is_array($group)) {
        $group = bbconnect_relationships_get_group($group);
    }
    $user_ids = maybe_unserialize($group[5]);
    if (false !== ($key = array_search($user->ID, $user_ids))) {
        unset($user_ids[$key]);
        $user_ids = array_values($user_ids); // reindex array
        $group[5] = maybe_serialize($user_ids);
        return GFAPI::update_entry($group);
    }
    return false;
}

function bbconnect_relationships_get_user_group_suggestions($user, array $groups = array(), array $relationships = array()) {
    // @todo
}

function bbconnect_relationships_get_group_form() {
    $group_form_id = get_option('bbconnect_relationships_group_form_id');
    $group_form = array(
            'title' => '[Connexions] Groups',
            'is_active' => true,
            'cssClass' => 'bbconnect',
            'button' => array(
                    'type' => 'text',
                    'text' => 'Save',
                    'imageUrl' => ''
            ),
            'confirmations' => array(
                    0 => array(
                            'id' => '5952f70811946',
                            'name' => 'Default Confirmation',
                            'isDefault' => true,
                            'type' => 'message',
                            'message' => 'Group saved successfully.',
                    ),
            ),
            'fields' => array(
                    0 => array(
                            'type' => 'text',
                            'id' => 1,
                            'label' => 'Group Name',
                            'isRequired' => true,
                    ),
                    1 => array(
                            'type' => 'select',
                            'id' => 2,
                            'label' => 'Group Type',
                            'isRequired' => true,
                            'choices' => array(
                                    0 => array(
                                            'text' => 'Family',
                                            'value' => 'Family',
                                    ),
                                    1 => array(
                                            'text' => 'Business',
                                            'value' => 'Business',
                                    ),
                                    2 => array(
                                            'text' => 'Church',
                                            'value' => 'Church',
                                    ),
                                    3 => array(
                                            'text' => 'Event',
                                            'value' => 'Event',
                                    ),
                                    4 => array(
                                            'text' => 'Other',
                                            'value' => 'Other',
                                    ),
                            ),
                    ),
                    2 => array(
                            'type' => 'fileupload',
                            'id' => 3,
                            'label' => 'Icon',
                            'isRequired' => false,
                            'description' => 'Recommended dimensions 150x150px.',
                            'maxFileSize' => 1,
                            'multipleFiles' => false,
                            'allowedExtensions' => 'jpg,jpeg,gif,png',
                    ),
                    3 => array(
                            'type' => 'textarea',
                            'id' => 4,
                            'label' => 'Description',
                            'isRequired' => false,
                            'useRichTextEditor' => true,
                    ),
                    4 => array(
                            'type' => 'list',
                            'id' => 5,
                            'label' => 'Members',
                            'isRequired' => false,
                    ),
            ),
    );

    if (!$group_form_id || !GFAPI::form_id_exists($group_form_id)) { // If form doesn't exist, create it
        $group_form_id = GFAPI::add_form($group_form);
        update_option('bbconnect_relationships_group_form_id', $group_form_id);
    } else { // Otherwise if we've created it previously, just update it to make sure it hasn't been modified and is the latest version
        $group_form['id'] = $group_form_id;
        GFAPI::update_form($group_form);
    }

    return $group_form_id;
}

add_filter('bbconnect_get_crm_forms', 'bbconnect_relationships_get_crm_forms', 0);
function bbconnect_relationships_get_crm_forms(array $forms) {
    $forms[] = bbconnect_relationships_get_group_form();
    return $forms;
}
