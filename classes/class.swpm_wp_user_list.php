<?php
if(!class_exists('WP_List_Table')) {require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');}
class SwpmWpUserList extends WP_List_Table {
    private $membership_level_dropdown;
    function __construct() {
        parent::__construct(array(
            'singular' => BUtils::_('Member'),
            'plural' => BUtils::_('Members'),
            'ajax' => false
        ));
        $this->membership_level_dropdown = BUtils::membership_level_dropdown();
    }

    function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />'
            , 'ID' => BUtils::_('ID')
            , 'user_login' => BUtils::_('User Name')
            , 'user_email' => BUtils::_('Email')
            , 'membership_level' => BUtils::_('Membership Level')
            , 'subscription_starts' => BUtils::_('Subscription Starts')
            , 'account_state' => BUtils::_('Account State')
            , 'preserve_role' => BUtils::_('Preserve Role')
        );
    }

    function get_sortable_columns() {
        return array(
            'user_login' => array('user_login', true)
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'add_selective' => BUtils::_('Import Selected')
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        switch($column_name){
            case 'membership_level':
                return sprintf('<select name="membership_level[%s]">', $item["ID"] )
                    . $this->membership_level_dropdown . '</select>';
            case 'subscription_starts':
                return sprintf('<input type="date" class="date_field" name="subscription_starts[%s]" value="%s" />',
                        $item["ID"], date('Y-m-d'));
            case 'account_state':
                return sprintf('<select name="account_state[%s]">', $item["ID"] )
                    . BUtils::account_state_dropdown() . '</select>';
            case 'preserve_role':
                return sprintf('<input type="checkbox" checked name="preserve_role[%s]" value="1" />',$item["ID"]);
        }
        return $item[$column_name];
    }

    /*function column_member_id($item) {
        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=%s&member_action=edit&member_id=%s">Edit</a>', $_REQUEST['page'], $item['member_id']),
            'delete' => sprintf('<a href="?page=%s&member_action=delete&member_id=%s"
                                    onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>', $_REQUEST['page'], $item['member_id']),
        );
        return $item['member_id'] . $this->row_actions($actions);
    }*/

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="wp_users[%s]" value="%s" />', $item['ID'], $item['ID']
        );
    }

    function prepare_items() {
        global $wpdb;
        $query = "SELECT * FROM " . $wpdb->users ;
        if (isset($_POST['s'])){
            $query .= " WHERE  user_login LIKE '%" . strip_tags($_POST['s']) . "%' "
                    . " OR user_email LIKE '%" . strip_tags($_POST['s']) . "%' "
                    . " OR display_name LIKE '%" . strip_tags($_POST['s']) . "%' ";
        }
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query.=' ORDER BY ' . $orderby . ' ' . $order;
        }
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 20;
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    function no_items() {
        _e('No Member found.');
    }
}
