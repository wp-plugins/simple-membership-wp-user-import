<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class
 *
 * @author nur
 */
class SwpmWpImport {
    public function __construct() {
        if (class_exists('SimpleWpMembership')) {
            add_action('swpm_after_main_admin_menu', array(&$this, 'user_import_do_admin_menu' ) );
            add_action('load-wp-membership_page_swpm-wp-import', array(&$this, 'library'));
        }
    }
    public function library(){
        wp_enqueue_style('jquery.tools.dateinput', SIMPLE_WP_MEMBERSHIP_URL . '/css/jquery.tools.dateinput.css');
        wp_enqueue_script('jquery.tools', SIMPLE_WP_MEMBERSHIP_URL . '/js/jquery.tools18.min.js');
    }
    public function user_import_do_admin_menu($menu_parent_slug){
        add_submenu_page($menu_parent_slug, __("WP User Import", 'swpm'), __("WP User Import", 'swpm'), 'manage_options', 'swpm-wp-import', array(&$this, 'admin'));
    }
    public function admin(){
        $this->add();
        $this->show_list();
    }
    private function add(){
        $action = filter_input(INPUT_POST, 'add_all');
        if (!empty($action)){
            $this->add_all();
        }
        $action = filter_input(INPUT_POST, 'action');
        $action2 = filter_input(INPUT_POST, 'action2');
        if ('add_selective' == $action || 'add_selective' == $action2){
            foreach ($_POST['wp_users'] as $key=>$ID){
                $user = new stdClass();
                $user->ID = $ID;
                $user->preserve_wp_role = isset($_POST['preserve_role']) && isset($_POST['preserve_role'][$key]) ? 1: null;
                $user->subscription_starts = $_POST['subscription_starts'][$key];
                $user->membership_level = $_POST['membership_level'][$key];
                $user->account_state = $_POST['account_state'][$key];
                $this->add_one($user);
            }
        }
    }
    private function show_list(){
        $wp_user = new SwpmWpUserList();
        include_once (SWPM_WP_IMPORT_PATH . 'views/wp_user_list.php');
    }
    private function add_all(){
        global $wpdb;
        $preserve_role = filter_input(INPUT_POST, 'wp_users_preserve_wp_role');
        $subscription_starts = filter_input(INPUT_POST, 'wp_users_subscription_starts');
        $membership_level = filter_input(INPUT_POST, 'wp_users_membership_level');
        $account_state = filter_input(INPUT_POST, 'wp_users_account_state');

        $import_all = filter_input(INPUT_POST, 'wp_add_wp_member_to_swpm');
        if ($import_all){
            $users = $wpdb->get_results("SELECT ID FROM $wpdb->users");
            foreach ($users as $user){
                $user->membership_level = $membership_level;
                $user->account_state = $account_state;
                $user->subscription_starts = $subscription_starts;
                $user->preserve_wp_role = $preserve_role;
                $this->add_one($user);
            }
        }
    }
    private function add_one($row) {
        global $wpdb;
        $user_info = get_userdata($row->ID);
        $user_cap = is_array($user_info->wp_capabilities) ? array_keys($user_info->wp_capabilities) : array();
        $fields = array();
        $fields['user_name'] = $user_info->user_login;
        $fields['first_name'] = $user_info->user_firstname;
        $fields['last_name'] = $user_info->user_lastname;
        $fields['password'] = $user_info->user_pass;
        $fields['member_since'] = date('Y-m-d H:i:s');
        $fields['membership_level'] = $row->membership_level;
        $fields['account_state'] = $row->account_state;
        $fields['email'] = $user_info->user_email;
        $fields['address_street'] = '';
        $fields['address_city'] = '';
        $fields['address_state'] = '';
        $fields['address_zipcode'] = '';
        $fields['country'] = '';
        $fields['gender'] = 'not specified';
        $fields['referrer'] = '';
        $fields['last_accessed_from_ip'] = BTransfer::get_real_ip_addr();
        $fields['subscription_starts'] = $row->subscription_starts;
        $fields['extra_info'] = '';

        if (!empty($row->preserve_wp_role)) {
            $fields['flags'] = 1;
        } else {
            $fields['flags'] = 0;
            if (($row->account_state === 'active') && !in_array('administrator', $user_cap)){
                update_wp_user_Role($row->ID, $row->membership_level);
            }
        }
        $user_exists = BUtils::swpm_username_exists($fields['user_name']);

        if ($user_exists) {
            return $wpdb->update($wpdb->prefix . "swpm_members_tbl",  $fields, array('member_id'=>$user_exists));
        } else {
            //Insert a new user in emember
            return $wpdb->insert($wpdb->prefix . "swpm_members_tbl",  $fields);
       }
    }
}
