<?php
add_filter('bbconnect_user_tabs', 'bbconnect_relationships_register_profile_tab', 10, 1);
function bbconnect_relationships_register_profile_tab(array $tabs) {
    $tabs['relationships'] = array(
        'title' => 'Relationships and Groups',
        'subs' => false,
    );
    return $tabs;
}

add_action('bbconnect_admin_profile_relationships', 'bbconnect_relationships_profile_tab');
function bbconnect_relationships_profile_tab() {
    // Set up a few variables
    global $user_id;
    $all_types = bbconnect_relationships_get_relationship_types();
    $selected_rel_type = $_REQUEST['rel_type'];
    $clean_url = remove_query_arg(array('rel_action', 'relation_id', 'old_type'));

    // Handle actions
    if (!empty($_REQUEST['rel_action']) && !empty($selected_rel_type)) {
        $relation_id = $_REQUEST['relation_id'];
        switch ($_REQUEST['rel_action']) {
            case 'add':
                if (bbconnect_relationships_add_relationship($selected_rel_type, $user_id, $relation_id)) {
                    echo '<div class="notice notice-success is-closable"><p>Relationship added successfully</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-closable"><p>Something went wrong while attempting to add the relationship. Do these contacts already have that type of relationship?</p></div>';
                }
                break;
            case 'edit':
                $old_type = $_REQUEST['old_type'];
                if (bbconnect_relationships_update_relationship($old_type, $user_id, $relation_id, $selected_rel_type)) {
                    echo '<div class="notice notice-success is-closable"><p>Relationship updated successfully</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-closable"><p>Something went wrong while attempting to update the selected relationship. Do these contacts already have that type of relationship?</p></div>';
                }
                break;
            case 'remove':
                if (bbconnect_relationships_remove_relationship($selected_rel_type, $user_id, $relation_id)) {
                    echo '<div class="notice notice-success is-closable"><p>Deleted successfully</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-closable"><p>An issue occured while attempting to delete the selected relationship. Please try again.</p></div>';
                }
                break;
        }
    }

    // Get list of relationships
    $relationships = bbconnect_relationships_get_user_relationships($user_id);
    if (count($relationships) > 0) {
        $second_level_relationships = bbconnect_relationships_get_user_second_level_relationships($user_id, $relationships);
?>
    <h2>Relationships <a class="page-title-action thickbox" href="#TB_inline?width=600&height=550&inlineId=add_rel">Add</a></h2>
    <table class="bbconnect-relationships widefat striped">
        <thead>
            <tr>
                <th scope="col">
                    <select id="relationship_type">
<?php
        foreach ($relationships as $type => $relations) {
            if (empty($selected_rel_type)) {
                $selected_rel_type = $type;
            }
?>
                        <option value="<?php echo $type; ?>" <?php selected($type, $selected_rel_type); ?>><?php echo ucwords($type); ?> (<?php echo count($relations); ?>)</option>
<?php
        }
?>
                    </select>
                </th>
                <th style="text-align: right;" scope="col"><?php echo __('Total Transactions ($)', 'bbconnect'); ?></th>
                <th style="text-align: right;" scope="col"><?php echo __('Transaction Count', 'bbconnect'); ?></th>
                <th style="text-align: right;" scope="col"><?php echo __('Last Transaction Date', 'bbconnect'); ?></th>
                <th style="text-align: right;" scope="col"><?php echo __('Days Since Last Transaction', 'bbconnect'); ?></th>
            </tr>
        </thead>
<?php
        foreach ($relationships as $type => $relations) {
            $type_style = $selected_rel_type == $type ? '' : 'display: none;';
?>
        <tbody class="relations-<?php echo $type; ?>" style="<?php echo $type_style; ?>">
<?php
            $totals = array(
                    'kpi_transaction_amount' => 0,
                    'kpi_transaction_count' => 0,
                    'kpi_last_transaction_date' => null,
                    'kpi_days_since_last_transaction' => null,
            );
            foreach ($relations as $relation_id) {
                $relation = new WP_User($relation_id);
                $meta = get_user_meta($relation_id);
                $transaction_amount = (float)$meta['kpi_transaction_amount'][0];
                $transaction_count = (int)$meta['kpi_transaction_count'][0];
                $last_transaction_date = $meta['kpi_last_transaction_date'][0];
                $days_since_transaction = $meta['kpi_days_since_last_transaction'][0];

                $totals['kpi_transaction_amount'] += $transaction_amount;
                $totals['kpi_transaction_count'] += $transaction_count;
                if (!empty($last_transaction_date)) {
                    $last_transaction_time = strtotime($last_transaction_date);
                    $last_transaction_date = date('d F Y', $last_transaction_time);
                    if ($last_transaction_time > $totals['kpi_last_transaction_date']) {
                        $totals['kpi_last_transaction_date'] = $last_transaction_date;
                    }
                }
                if (!empty($days_since_transaction)) {
                    if ($days_since_transaction > $totals['kpi_days_since_last_transaction']) {
                        $totals['kpi_days_since_last_transaction'] = $days_since_transaction;
                    }
                }
?>
            <tr>
                <th scope="row">
                    <a href="<?php echo add_query_arg(array('user_id' => $relation_id, 'rel_type' => $type), $clean_url); ?>"><?php echo $relation->display_name; ?></a> <?php echo $relation->user_email; ?>
                    <div class="row-actions">
                        <span class="edit"><a class="thickbox" href="#TB_inline?width=600&height=300&inlineId=edit_rel_<?php echo $relation_id; ?>">Edit Relationship</a> | </span>
                        <span class="trash"><a href="<?php echo add_query_arg(array('rel_action' => 'remove', 'relation_id' => $relation_id, 'rel_type' => $type), $clean_url); ?>" class="submitdelete" onclick="return confirm('Are you sure you want to remove this relationship?');">Remove Relationship</a></span>
                    </div>
                    <div id="edit_rel_<?php echo $relation_id; ?>" style="display: none;">
                        <div style="overflow: scroll;">
                            <h2>Update Relationship</h2>
                            <form action="<?php echo remove_query_arg('rel_type', $clean_url); ?>" method="post">
                                <div class="modal-row">
                                    <label for="rel_type" class="full-width">Relationship Type</label><br>
                                    <select id="rel_type" name="rel_type">
<?php
                foreach ($all_types as $rel_type) {
?>
                                        <option value="<?php echo $rel_type; ?>" <?php selected($type, $rel_type); ?>><?php echo ucwords($rel_type); ?></option>
<?php
                }
?>
                                    </select>
                                </div>
                                <input type="hidden" name="old_type" value="<?php echo $type; ?>">
                                <input type="hidden" name="relation_id" value="<?php echo $relation_id; ?>">
                                <input type="hidden" name="rel_action" value="edit">
                                <input class="button action" value="Submit" type="submit">
                            </form>
                        </div>
                    </div>
                </th>
                <td style="text-align: right;"><?php echo '$'.number_format($transaction_amount, 2); ?></td>
                <td style="text-align: right;"><?php echo $transaction_count; ?></td>
                <td style="text-align: right;"><?php echo $last_transaction_date; ?></td>
                <td style="text-align: right;"><?php echo $days_since_transaction; ?></td>
            </tr>
<?php
            }
?>
        </tbody>
        <tfoot class="relations-<?php echo $type; ?>" style="<?php echo $type_style; ?>">
            <tr>
                <th style="text-align: right;" scope="row">Totals:</th>
                <td style="text-align: right;"><?php echo '$'.number_format($totals['kpi_transaction_amount'], 2); ?></td>
                <td style="text-align: right;"><?php echo $totals['kpi_transaction_count']; ?></td>
                <td style="text-align: right;"><?php echo $totals['kpi_last_transaction_date']; ?></td>
                <td style="text-align: right;"><?php echo $totals['kpi_days_since_last_transaction']; ?></td>
            </tr>
        </tfoot>
<?php
        }
?>
    </table>
<?php
    } else {
        echo '<p>No relationships found.</p>';
    }

    if (count($second_level_relationships) > 0) {
?>
    <div style="width: 47.5%; float: left;">
        <h3>Suggested Relationships</h3>
        <table class="bbconnect-second-relationships widefat striped">
            <thead>
                <tr>
                    <th scope="col">
                        <select id="second_relationship_type">
<?php
        foreach ($second_level_relationships as $type => $relations) {
            if (empty($selected_second_rel_type) || $type == $selected_rel_type) {
                $selected_second_rel_type = $type;
            }
?>
                            <option value="<?php echo $type; ?>" <?php selected($type, $selected_second_rel_type); ?>><?php echo ucwords($type); ?> (<?php echo count($relations); ?>)</option>
<?php
        }
?>
                        </select>
                    </th>
                    <th scope="col"></th>
                </tr>
            </thead>
<?php
        foreach ($second_level_relationships as $type => $relations) {
            $type_style = $selected_second_rel_type == $type ? '' : 'display: none;';
?>
            <tbody class="relations-<?php echo $type; ?>" style="<?php echo $type_style; ?>">
<?php
            foreach ($relations as $relation_id) {
                $relation = new WP_User($relation_id);
?>
                <tr>
                    <th scope="row">
                        <a href="<?php echo add_query_arg(array('user_id' => $relation_id, 'rel_type' => $type), $clean_url); ?>"><?php echo $relation->display_name; ?></a> <?php echo $relation->user_email; ?>
                    </th>
                    <td><a class="button" href="<?php echo add_query_arg(array('rel_action' => 'add', 'relation_id' => $relation_id, 'rel_type' => $type), $clean_url); ?>">Add</a>
                </tr>
<?php
            }
?>
            </tbody>
<?php
        }
?>
        </table>
    </div>
<?php
    }
?>
    <div id="add_rel" style="display: none;">
        <div style="overflow: scroll;">
            <h2>Add New Relationship</h2>
            <form action="<?php echo remove_query_arg('rel_type', $clean_url); ?>" method="post" id="form_add_rel">
                <div class="modal-row">
                    <label for="rel_type" class="full-width">Relationship Type</label><br>
                    <select id="rel_type" name="rel_type">
<?php
    foreach ($all_types as $rel_type) {
?>
                        <option value="<?php echo $rel_type; ?>" <?php selected($selected_rel_type, $rel_type); ?>><?php echo ucwords($rel_type); ?></option>
<?php
    }
?>
                    </select>
                </div>
                <div class="modal-row">
                    <label for="rel_search" class="full-width">Find User</label><br>
                    <input type="text" id="rel_search" name="rel_search"><i id="do_rel_search" class="dashicons dashicons-search" style="cursor: pointer;"></i>
                </div>
                <input type="hidden" name="rel_action" value="add">
                <input type="hidden" id="add_relation_id" name="relation_id">
            </form>
            <div id="rel_search_results"></div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function() {
            jQuery('select#relationship_type').on('change', function() {
                jQuery('table.bbconnect-relationships tbody, table.bbconnect-relationships tfoot').hide();
                jQuery('table.bbconnect-relationships tbody.relations-'+jQuery(this).val()+', table.bbconnect-relationships tfoot.relations-'+jQuery(this).val()).show();
                jQuery('select#rel_type').val(jQuery(this).val());
            });
            jQuery('select#second_relationship_type').on('change', function() {
                jQuery('table.bbconnect-second-relationships tbody').hide();
                jQuery('table.bbconnect-second-relationships tbody.relations-'+jQuery(this).val()).show();
                jQuery('select#rel_type').val(jQuery(this).val());
            });
        });
        var process_add = false;
        jQuery(document).on('submit', '#form_add_rel', function(event) {
            if (!process_add) {
                event.preventDefault();
            }
        });
        jQuery(document).on('click', '#do_rel_search', function(event) {
            bbconnect_relationships_do_user_search();
        });
        jQuery(document).on('keypress', '#rel_search', function(event) {
            if (event.which == 13) { // Enter/Return key
                bbconnect_relationships_do_user_search();
            }
        });
        function bbconnect_relationships_do_user_search() {
            jQuery('#do_rel_search').removeClass('dashicons-search').addClass('dashicons-clock');
            var search = jQuery('input#rel_search').val();
            jQuery.post(ajaxurl,
                    {
                            action: 'bbconnect_relationships_do_search',
                            search: search
                    },
                    function(data) {
                        jQuery('#rel_search_results').html(data);
                        jQuery('#do_rel_search').removeClass('dashicons-clock').addClass('dashicons-search');
                    }
            );
        }
        jQuery(document).on('click', 'a.select_rel_user', function(event) {
            jQuery('#add_relation_id').val(jQuery(this).data('user-id'));
            process_add = true;
            jQuery('#form_add_rel').submit();
        });
    </script>
<?php
}

add_action('wp_ajax_bbconnect_relationships_do_search', 'bbconnect_relationships_do_search');
function bbconnect_relationships_do_search() {
    $search = $_POST['search'];
    if (!empty($search)) {
        add_filter('user_search_columns', function($search_columns) {
            $search_columns[] = 'display_name';
            return $search_columns;
        });
        $args = array(
                'number' => 10,
                'count_total' => true,
                'search' => '*'.$search.'*',
                'search_columns' => array('display_name', 'user_email'),
                'orderby' => 'display_name',
        );
        $query = new WP_User_Query($args);
        $users = $query->get_results();
        $total_count = $query->get_total();
        if ($total_count == 0) {
            echo '<p>No matching users found.</p>';
        } else {
?>
    <table class="widefat striped">
<?php
            foreach ($users as $user) {
?>
        <tr>
            <td><?php echo $user->display_name; ?></td>
            <td><?php echo $user->user_email; ?></td>
            <td><a class="button select_rel_user" data-user-id="<?php echo $user->ID; ?>">Select</a>
        </tr>
<?php
            }
?>
    </table>
<?php
            if ($total_count > count($users)) {
                echo '<p>'.$total_count.' users matched your query. Please refine your search criteria and try again.</p>';
            }
        }
    } else {
        echo '<p>You must enter a search term.</p>';
    }
    die();
}
