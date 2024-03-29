<?php
/**
 * Get list of relationship types
 * @return array
 */
function bbconnect_relationships_get_relationship_types() {
    $types = apply_filters('bbconnect_relationships_relationship_types', array(
            'alias',
            'family',
            'personal',
            'professional',
    ));

    // Do a bit of cleanup
    $types = array_unique($types);
    sort($types);

    return $types;
}

/**
 * Does the specified relationship already exist?
 * @param string $type
 * @param integer $user_id_a
 * @param integer $user_id_b
 */
function bbconnect_relationships_relationship_exists($type, $user_id_a, $user_id_b) {
    global $wpdb;
    $format = array('%s', '%d', '%d');
    $data = array(
            'type' => $type,
            'user_id_a' => $user_id_a,
            'user_id_b' => $user_id_b,
    );
    return $wpdb->get_var($wpdb->prepare('SELECT count(*) FROM '.$wpdb->bbconnect_relationships.' WHERE type = %s AND user_id_a = %d AND user_id_b = %d', $type, $user_id_a, $user_id_b)) > 0;
}

/**
 * Add relationship between 2 users
 * @param string $type Type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 * @param boolean $track Whether to add entry to activity log. Default true.
 */
function bbconnect_relationships_add_relationship($type, $user_id_a, $user_id_b, $track = true) {
    if (bbconnect_relationships_relationship_exists($type, $user_id_a, $user_id_b)) {
        return false;
    }
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
        $user_a = new WP_User($user_id_a);
        $user_b = new WP_User($user_id_b);
        if ($track) {
            $tracking_args = array(
                    'type' => 'relationships',
                    'source' => 'bbconnect-relationships',
                    'title' => 'Relationship Added',
                    'description' => $user_a->display_name.' now has a '.$type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_b->ID.'">'.$user_b->display_name.'</a>',
                    'user_id' => $user_id_a,
                    'email' => $user_a->user_email,
            );
            bbconnect_track_activity($tracking_args);
        }

        // And the inverse
        $data['user_id_a'] = $user_id_b;
        $data['user_id_b'] = $user_id_a;
        $success = $wpdb->insert($wpdb->bbconnect_relationships, $data, $format);
        if ($success && $track) {
            $tracking_args['description'] = $user_b->display_name.' now has a '.$type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_a->ID.'">'.$user_a->display_name.'</a>';
            $tracking_args['user_id'] = $user_id_b;
            $tracking_args['email'] = $user_b->user_email;
            bbconnect_track_activity($tracking_args);
        }
    }
    return $success;
}

/**
 * Update relationship between 2 users
 * @param string $old_type Existing type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 * @param string $new_type New relationship type
 * @param boolean $track Whether to add entry to activity log. Default true.
 */
function bbconnect_relationships_update_relationship($old_type, $user_id_a, $user_id_b, $new_type, $track = true) {
    if (!bbconnect_relationships_relationship_exists($old_type, $user_id_a, $user_id_b) || bbconnect_relationships_relationship_exists($new_type, $user_id_a, $user_id_b)) {
        return false;
    }
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
        $user_a = new WP_User($user_id_a);
        $user_b = new WP_User($user_id_b);
        if ($track) {
            $tracking_args = array(
                    'type' => 'relationships',
                    'source' => 'bbconnect-relationships',
                    'title' => 'Relationship Updated',
                    'description' => $user_a->display_name.' now has a '.$new_type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_b->ID.'">'.$user_b->display_name.'</a>',
                    'user_id' => $user_id_a,
                    'email' => $user_a->user_email,
            );
            bbconnect_track_activity($tracking_args);
        }

        // And the inverse
        $where['user_id_a'] = $user_id_b;
        $where['user_id_b'] = $user_id_a;
        $success = $wpdb->update($wpdb->bbconnect_relationships, $data, $where, $format, $where_format);
        if ($success && $track) {
            $tracking_args['description'] = $user_b->display_name.' now has a '.$new_type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_a->ID.'">'.$user_a->display_name.'</a>';
            $tracking_args['user_id'] = $user_id_b;
            $tracking_args['email'] = $user_b->user_email;
            bbconnect_track_activity($tracking_args);
        }
    }
    return $success;
}

/**
 * Remove relationship between 2 users
 * @param string $type Type of relationship, e.g. alias, professional, family, personal
 * @param integer $user_id_a ID of one of the users
 * @param integer $user_id_b ID of the other user
 * @param boolean $track Whether to add entry to activity log. Default true.
 */
function bbconnect_relationships_remove_relationship($type, $user_id_a, $user_id_b, $track = true) {
    if (!bbconnect_relationships_relationship_exists($type, $user_id_a, $user_id_b)) {
        return false;
    }
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
        $user_a = new WP_User($user_id_a);
        $user_b = new WP_User($user_id_b);
        if ($track) {
            $tracking_args = array(
                    'type' => 'relationships',
                    'source' => 'bbconnect-relationships',
                    'title' => 'Relationship Removed',
                    'description' => $user_a->display_name.' no longer has a '.$type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_b->ID.'">'.$user_b->display_name.'</a>',
                    'user_id' => $user_id_a,
                    'email' => $user_a->user_email,
            );
            bbconnect_track_activity($tracking_args);
        }

        // And the inverse
        $where['user_id_a'] = $user_id_b;
        $where['user_id_b'] = $user_id_a;
        $success = $wpdb->delete($wpdb->bbconnect_relationships, $where, $format);
        if ($success && $track) {
            $tracking_args['description'] = $user_b->display_name.' no longer has a '.$type.' relationship with <a href="users.php?page=bbconnect_edit_user&user_id='.$user_a->ID.'">'.$user_a->display_name.'</a>';
            $tracking_args['user_id'] = $user_id_b;
            $tracking_args['email'] = $user_b->user_email;
            bbconnect_track_activity($tracking_args);
        }
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
                if (false !== ($relations_relations = bbconnect_relationships_get_user_relationships_by_type($rel_user_id, $rel_type))) {
                    $second_relationships[$rel_type] += $relations_relations;
                }
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
 * @param boolean $track Whether to add entry to activity log. Default true.
 * @return boolean|WP_Error True on success, False if user already exists in group, WP_Error if something else went wrong
 */
function bbconnect_relationships_add_user_to_group($user, $group, $track = true) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if (!is_array($group)) {
        $group = bbconnect_relationships_get_group($group);
    }
    $user_ids = maybe_unserialize($group[5]);
    if (empty($user_ids)) {
    	$user_ids = array();
    }
    if (!in_array($user->ID, $user_ids)) {
        $user_ids[] = (string)$user->ID; // Have to make sure it's a string else our search won't work
        $group[5] = maybe_serialize($user_ids);
        if (GFAPI::update_entry($group)) {
            if ($track) {
                $tracking_args = array(
                        'type' => 'groups',
                        'source' => 'bbconnect-relationships',
                        'title' => 'Group Added',
                        'description' => $user->display_name.' is now a member of '.$group[1],
                        'user_id' => $user->ID,
                        'email' => $user->user_email,
                );
                bbconnect_track_activity($tracking_args);
            }
            return true;
        }
    }
    return false;
}

/**
 * Remove user from a group
 * @param WP_User|integer $user User to remove from group. Can be either user ID or WP_User object.
 * @param array|integer $group GF entry or entry ID
 * @param boolean $track Whether to add entry to activity log. Default true.
 * @return boolean|WP_Error True on success, False if user doesn't exist in group, WP_Error if something else went wrong
 */
function bbconnect_relationships_remove_user_from_group($user, $group, $track = true) {
    if (is_numeric($user)) {
        $user = get_user_by('id', $user);
    }
    if (!is_array($group)) {
        $group = bbconnect_relationships_get_group($group);
    }
    $user_ids = maybe_unserialize($group[5]);
    if (empty($user_ids)) {
    	$user_ids = array();
    }
    if (false !== ($key = array_search($user->ID, $user_ids))) {
        unset($user_ids[$key]);
        $user_ids = array_values($user_ids); // reindex array
        $group[5] = maybe_serialize($user_ids);
        if (GFAPI::update_entry($group)) {
            if ($track) {
                $tracking_args = array(
                        'type' => 'relationships',
                        'source' => 'bbconnect-relationships',
                        'title' => 'Group Removed',
                        'description' => $user->display_name.' is no longer a member of '.$group[1],
                        'user_id' => $user->ID,
                        'email' => $user->user_email,
                );
                bbconnect_track_activity($tracking_args);
            }
            return true;
        }
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
                            'visibility' => 'hidden',
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

add_filter('bbconnect_gf_quicklink_form_list', 'bbconnect_relationships_gf_quicklink_hide_forms');
function bbconnect_relationships_gf_quicklink_hide_forms($forms) {
    foreach ($forms as $idx => $form) {
        if ($form['id'] == bbconnect_relationships_get_group_form()) {
            unset($forms[$idx]);
        }
    }
    return $forms;
}

add_filter('bbconnect_form_activity_details', 'bbconnect_relationships_form_activity_details', 1, 4);
function bbconnect_relationships_form_activity_details($activity, $form, $entry, $agent) {
    switch ($form['id']) {
        case bbconnect_relationships_get_group_form():
            $activity['title'] = 'New '.$entry[2].' group created: '.$entry[1];
            $activity['details'] = $entry[4];
            $activity['type'] = 'groups';
            break;
    }
    return $activity;
}

function bbconnect_relationships_create_group_from_emails($group_name, $group_type, array $emails) {
    $group_form = bbconnect_relationships_get_group_form();

    // Insert GF entry
//     $_POST = array(); // Hack to allow multiple form submissions via API in single process
    $entry = array(
            'input_1' => $group_name,
            'input_2' => $group_type,
    );
    GFAPI::submit_form($group_form, $entry);

    // Because submit_form doesn't return the entry ID we have to go looking for it ourselves
    $search_criteria = array(
            'field_filters' => array(
                    array(
                            'key' => 1,
                            'value' => $group_name,
                    ),
            ),
    );
    $groups = GFAPI::get_entries($group_form, $search_criteria);
    if (count($groups) > 0) {
        $group = array_shift($groups);
        $group_id = $group['id'];

        foreach ($emails as $email) {
            $user_data = array(
                    'email' => $email,
                    'firstname' => 'Unknown',
                    'lastname' => 'Unknown',
            );
            $user = bbconnect_get_user($user_data);
            bbconnect_relationships_add_user_to_group($user, $group_id);
        }
        return $group_id;
    }
    return false;
}

add_action('bbconnect_merge_users', 'bbconnect_relationships_merge_users', 10, 2);
function bbconnect_relationships_merge_users($to_user, $old_user) {
    $relationships = bbconnect_relationships_get_user_relationships($old_user);
    foreach ($relationships as $type => $rel_users) {
        foreach ($rel_users as $rel_user) {
            bbconnect_relationships_remove_relationship($type, $old_user->ID, $rel_user, false);
            if ($rel_user != $to_user && $rel_user != $old_user->ID) {
                bbconnect_relationships_add_relationship($type, $to_user, $rel_user, false);
            }
        }
    }
    $groups = bbconnect_relationships_get_user_groups($old_user);
    foreach ($groups as $group) {
        // Pass group ID rather than entire entry to force it to refresh from DB
        bbconnect_relationships_remove_user_from_group($old_user, $group['id'], false);
        bbconnect_relationships_add_user_to_group($to_user, $group['id'], false);
    }
}
