<?php
/**
 * Create Group From Emails quicklink
 * @author markparnell
 */
class reports_30_create_group_from_emails_quicklink extends bb_form_quicklink {
    public function __construct() {
        parent::__construct();
        $this->title = 'Create Group From Emails';
    }

    protected function form_contents(array $user_ids = array(), array $args = array()) {
        echo '<div class="modal-row"><label for="group_name">Group Name:</label><input type="text" id="group_name" name="group_name"></div>';
        echo '<div class="modal-row"><label for="group_type">Group Type:</label>';
        echo '<select id="group_type" name="group_type">';
        $group_form = GFAPI::get_form(bbconnect_relationships_get_group_form());
        foreach ($group_form['fields'] as $field) {
            if ($field->id == 2) {
                foreach ($field->choices as $choice) {
                    echo '<option value="'.$choice['value'].'">'.$choice['text'].'</option>';
                }
            }
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="modal-row"><label for="emails" class="full-width">Email Addresses (one email per line):</label><textarea id="emails" name="emails" rows="10"></textarea></div>';
    }

    public static function post_submission() {
        extract($_POST);
        if (empty($group_name) || empty($group_type) || empty($emails)) {
            echo 'All fields are required.';
            return;
        }

        $emails = explode("\n", $emails);
        return bbconnect_relationships_create_group_from_emails($group_name, $group_type, $emails);
    }
}
