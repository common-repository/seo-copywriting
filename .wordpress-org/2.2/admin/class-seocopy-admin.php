<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.seocopy.com
 * @since      1.0.0
 *
 * @package    seocopy
 * @subpackage seocopy/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    seocopy
 * @subpackage seocopy/admin
 * @author     seocopy <support@seocopy.com>
 */
class seocopy_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $seocopy The ID of this plugin.
     */
    private $seocopy;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $seocopy The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($seocopy, $version)
    {

        $this->seocopy = $seocopy;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in seocopy_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The seocopy_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->seocopy, plugin_dir_url(__FILE__) . 'css/seocopy-admin.css', array(), $this->version, 'all');
        wp_enqueue_style($this->seocopy . '-bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in seocopy_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The seocopy_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->seocopy.'-findAndReplaceDOMText', plugin_dir_url(__FILE__) . 'js/seocopy-findAndReplaceDOMText.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->seocopy.'-tinyMce', plugin_dir_url(__FILE__) . 'js/seocopy-tinyMce.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->seocopy.'-bootstrap', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->seocopy, plugin_dir_url(__FILE__) . 'js/seocopy-admin.js', array('jquery',
            $this->seocopy.'-findAndReplaceDOMText',
            $this->seocopy.'-tinyMce'
            ), $this->version, false);

    }

    public function start_session(){
        if (!session_id())
            session_start();
    }

    public function settings(){
        if ( empty ( $GLOBALS['admin_page_hooks']['wp-seo-plugins-login'] ) ) {
            add_menu_page("SEO Plugins", "SEO Plugins", "manage_options", "wp-seo-plugins-login", array($this, "settings_view"), 'dashicons-analytics' );
        }
    }

    public function settings_view(){
        include_once 'partials/seocopy-admin-settings.php';
    }

    public function admin_menu()
    {
        //add_menu_page('Seo Copy Settings', 'Seo Copy', 'manage_options', 'seocopy_menu_page', array($this, 'seocopy_options_page'), '', '95');
//        add_submenu_page( 'testing_menu_page', 'Test Links', 'Test Title', 'manage_options',
//            'get_test_links' );
    }

    private function wp_seo_plugins_get_credits() {
        $sc_api_key = get_option('sc_api_key');
        if( !empty( $sc_api_key ) ) {
            $server_uri = ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME']  . $_SERVER['REQUEST_URI'];
            $remote_get = WP_SEO_PLUGINS_BACKEND_URL . 'apikey/credits?api_key=' . $sc_api_key . '&domain=' . ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME'] . '&remote_server_uri=' . base64_encode( $server_uri );

            $args = array(
                'timeout'     => 10,
                'sslverify' => false
            );
            $data = wp_remote_get( $remote_get, $args );
            if( is_array( $data ) && !is_wp_error( $data ) ) {
                $rowData = json_decode( $data['body'] );

                $response = $rowData->response;
                $credits = new stdClass();
                foreach( $response as $key => $val ) {
                    if( strpos( $key, 'api_limit') !== false ) {
                        $p = str_replace( 'api_limit_', '', $key );
                        $api_limit = $response->{ $key } ?? 0;
                        $api_call = $response->{ 'api_call_' . $p } ?? 0;
                        $credits->{ $p } = $api_limit - $api_call;
                    }
                }

                return $credits;
            } else {
                file_put_contents( $_SERVER['DOCUMENT_ROOT'] . '/wp_seo_plugins_debug.log', "Remote Get: " . $remote_get . "\n" . print_r( $data, true ), FILE_APPEND | LOCK_EX );
                return 0;
            }
        }
    }


    function seocopy_options_page()
    {
        // set this var to be used in the settings-display view
        $active_tab = isset($_GET['tab']) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        if (isset($_GET['error_message'])) {
            add_action('admin_notices', array($this, 'seocopySettingsMessages'));
            do_action('admin_notices', sanitize_text_field($_GET['error_message']));
        }
        require_once 'partials/' . $this->seocopy . '-admin-display.php';
    }

    public function seocopySettingsMessages($error_message)
    {
        switch ($error_message) {
            case '1':
                $message = __('There was an error adding this setting. Please try again.  If this persists, shoot us an email.', seocopy_DOMAIN);
                $err_code = esc_attr('seocopy_apikey_setting');
                $setting_field = 'seocopy_apikey_setting';
                break;
        }
        $type = 'error';
        add_settings_error($setting_field, $err_code, $message, $type);
    }

    public function registerAndBuildFields()
    {
        /**
         * First, we add_settings_section. This is necessary since all future settings must belong to one.
         * Second, add_settings_field
         * Third, register_setting
         */
        add_settings_section(// ID used to identify this section and with which to register options
            'seocopy_general_section', // Title to be displayed on the administration page
            '', // Callback used to render the description of the section
            array($this, 'seocopy_display_general_account'), // Page on which to add this section of options
            'seocopy_general_settings');
        unset($args);
        $args = array('type' => 'input', 'subtype' => 'text', 'id' => 'seocopy_apikey_setting', 'name' => 'seocopy_apikey_setting', 'required' => 'true', 'get_options_list' => '', 'value_type' => 'normal', 'wp_data' => 'option');
        add_settings_field('seocopy_apikey_setting', 'Api KEY', array($this, 'seocopy_render_settings_field'), 'seocopy_general_settings', 'seocopy_general_section', $args);


        register_setting('seocopy_general_settings', 'seocopy_apikey_setting', array($this,'validate_settings'));

    }

    public function validate_settings($plugin_options)
    {
        $apikey = get_option('seocopy_apikey_setting');
        if(isset($_REQUEST['seocopy_apikey_setting'])){
            $apikey = sanitize_text_field( $_REQUEST['seocopy_apikey_setting'] );
        }

        try{
            $validKey = seocopyApi::keyIsValid($apikey);
        }catch(\Exception $e){
            add_settings_error( 'seocopy_general_settings', 'settings_updated', __('Key not valid', seocopy_DOMAIN), 'error');
        }

        return $plugin_options;
    }

    public function seocopy_display_general_account()
    {
        if(!get_option('seocopy_apikey_setting')) {
            echo '<p class="seocopy-settings-imp">' . __('You can register to seocopy for free at <a target="_blank" ref="nofollow" href="https://www.wpseoplugins.org/">wpseoplugins.org</a>', seocopy_DOMAIN) . '</p>';
        }
    }

    public function seocopy_render_settings_field($args)
    {
        /* EXAMPLE INPUT
                  'type'      => 'input',
                  'subtype'   => '',
                  'id'    => $this->seocopy.'_example_setting',
                  'name'      => $this->seocopy.'_example_setting',
                  'required' => 'required="required"',
                  'get_option_list' => "",
                    'value_type' = serialized OR normal,
        'wp_data'=>(option or post_meta),
        'post_id' =>
        */
        if ($args['wp_data'] == 'option') {
            $wp_data_value = get_option($args['name']);
        } elseif ($args['wp_data'] == 'post_meta') {
            $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
        }

        switch ($args['type']) {

            case 'input':
                $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
                if ($args['subtype'] != 'checkbox') {
                    $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                    $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                    $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
                    $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
                    $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
                    if (isset($args['disabled'])) {
                        // hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                        echo esc_html( $prependStart ) . '<input type="' . esc_html($args['subtype']) . '" id="' . esc_html($args['id']) . '_disabled" ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_html($args['name']) . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . esc_html($args['id']) . '" ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_html($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . esc_html($prependEnd);
                    } else {
                        echo esc_html( $prependStart ) . '<input type="' . esc_html($args['subtype']) . '" id="' . esc_html($args['id']) . '" "' . esc_html($args['required']) . '" ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_html($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . esc_html($prependEnd);
                    }
                    /*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->seocopy.'_cost2" name="'.$this->seocopy.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->seocopy.'_cost" step="any" name="'.$this->seocopy.'_cost" value="' . esc_attr( $cost ) . '" />*/

                } else {
                    $checked = ($value) ? 'checked' : '';
                    echo '<input type="' . esc_html($args['subtype'] ) . '" id="' . esc_html($args['id']) . '" "' . esc_html($args['required']) . '" name="' . esc_html($args['name']) . '" size="40" value="1" ' . esc_html($checked) . ' />';
                }
                break;
            default:
                # code...
                break;
        }
    }


    function getApiAllowedLanguages(){
        return ['en'=>__('English','seocopy_DOMAIN'),'it'=>__('Italian','seocopy_DOMAIN'),'fr'=>__('French','seocopy_DOMAIN'),'es'=>__('Spanish','seocopy_DOMAIN')];
    }

    function getApiLanguage(){
        $lang = 'en';
        $curlang = substr(get_locale(), 0,2);
        if(defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE){
            //$curlang = ICL_LANGUAGE_CODE;
            $curlang = apply_filters( 'wpml_current_language', NULL );
        }
        foreach(array_keys($this->getApiAllowedLanguages()) as $l){
            if($curlang === $l){
                $lang = $l;
            }
        }
        return $lang;
    }

    function seocopy_add_custom_box() {
        $screens = [
            'post',
//            'wporg_cpt'
        ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'seocopy_box_id',                 // Unique ID
                'Seo Copy',      // Box title
                array($this,'seocopy_custom_box_html'),  // Content callback, must be of type callable
                $screen,                            // Post type
                'side',
                'high', // core
                array(
                    '__block_editor_compatible_meta_box' => true,
                    '__back_compat_meta_box'             => false,
                )
            );
        }
    }

    function seocopy_custom_box_html( $post ) {
        require_once 'partials/' . $this->seocopy . '-admin-custom-box.php';
    }

    public function seocopy_login() {
        $nonce = sanitize_text_field($_POST['security']);
        if(!wp_verify_nonce($nonce,'seocopy_login_nonce') || !current_user_can( 'administrator' )){
            header('Location:'.$_SERVER["HTTP_REFERER"].'?error=unauthenticated');
            exit();
        }

        $post_data = array();
        $post_data['email'] = sanitize_text_field( $_POST['email'] ) ?? '';
        $post_data['password'] = sanitize_text_field( $_POST['password'] ) ?? '';

        $args = array(
            'body'        => $post_data,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'cookies'     => array(),
        );
        $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'login', $args );
        $data = json_decode(wp_remote_retrieve_body( $response ));

        $_SESSION['status'] = $data->status;
        $_SESSION['message'] = $data->message;

        if($data->status == 0) {
            // Generating a new api key

            $server_name = $_SERVER['SERVER_NAME'];
            $server_port = $_SERVER['SERVER_PORT'];
            $server_uri = ( $server_port == 80 ? 'http://' : 'https://' ) . $server_name;

            $args = array(
                'body'        => array('user_id' => $data->user_id ?? 0),
                'timeout'     => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'Siteurl' => $server_uri
                ),
                'cookies'     => array(),
            );
            $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'apikey/generate', $args );
            $data = json_decode(wp_remote_retrieve_body( $response ));

            $_SESSION['status'] = $data->status;
            $_SESSION['message'] = $data->message;
            $_SESSION['api_key'] = $data->api_key ?? '';

            if( $_SESSION['api_key'] != '' ) {
                update_option('sc_api_key', sanitize_text_field( $_SESSION['api_key']) );
            }
        }

        header('Location: '.site_url().'/wp-admin/admin.php?page=seocopy_menu_page');
        exit();
    }

    public function seocopy_registration() {
        $nonce = sanitize_text_field($_POST['security']);
        if(!wp_verify_nonce($nonce,'seocopy_registration_nonce') || !current_user_can( 'administrator' )){
            header('Location:'.$_SERVER["HTTP_REFERER"].'?error=unauthenticated');
            exit();
        }

        $server_name = $_SERVER['SERVER_NAME'];
        $server_port = $_SERVER['SERVER_PORT'];
        $server_uri = ( $server_port == 80 ? 'http://' : 'https://' ) . $server_name;

        $post_data = array();
        $post_data['name'] = sanitize_text_field( $_POST['name'] ) ?? '';
        $post_data['surname'] = sanitize_text_field( $_POST['surname'] ) ?? '';
        $post_data['email'] = sanitize_email( $_POST['email'] ) ?? '';
        $post_data['password'] = sanitize_text_field( $_POST['password'] ) ?? '';

        $args = array(
            'body'        => $post_data,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Siteurl' => $server_uri
            ),
            'cookies'     => array(),
        );
        $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'registration', $args );
        $data = json_decode(wp_remote_retrieve_body( $response ));

        $_SESSION['status'] = $data->status;
        $_SESSION['message'] = $data->message;
        $_SESSION['api_key'] = $data->api_key ?? '';

        if( $_SESSION['api_key'] != '' ) {
            update_option('sc_api_key', sanitize_text_field( $_SESSION['api_key'] ));
        }
        header('Location: '.site_url().'/wp-admin/admin.php?page=seocopy_menu_page');
        exit();
    }

    /**
     * Get residual credits
     */
    public function seocopy_get_credits() {
        $sc_api_key = get_option('sc_api_key');
        if( !empty( $sc_api_key ) ) {

            $server_uri = ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            $remote_get = WP_SEO_PLUGINS_BACKEND_URL . 'apikey/credits?api_key=' . $sc_api_key . '&domain=' . ( $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME'] . '&remote_server_uri=' . base64_encode( $server_uri );

            $args = array(
                'timeout'     => 10,
                'sslverify' => false
            );
            $data = wp_remote_get( $remote_get, $args );
            if( !is_wp_error( $data ) ) {
                $rowData = json_decode( $data['body'] );

                $api_limit = $rowData->response->api_limit_seo_copy ?? 0;
                $api_call = $rowData->response->api_call_seo_copy ?? 0;

                return $api_limit - $api_call;
            }
        }
    }

    /**
     * QuerySuggestion Metabox
     */
    function seocopy_add_querysuggestion_box() {
        $screens = [
            'post',
//            'wporg_cpt'
        ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'seocopy_querysuggestion_box_id',                 // Unique ID
                'Seo Copy - People Also Ask',      // Box title
                array($this,'seocopy_querysuggestion_box_html'),  // Content callback, must be of type callable
                $screen,                            // Post type
                'side',
                'high', // core
                array(
                    '__block_editor_compatible_meta_box' => true,
                    '__back_compat_meta_box'             => false,
                )
            );
        }
    }

    function seocopy_querysuggestion_box_html( $post ) {
        require_once 'partials/' . $this->seocopy . '-admin-querysuggestion-box.php';
    }

    public function login() {
        $nonce = sanitize_text_field($_POST['security']);
        if(!wp_verify_nonce($nonce,'wp_seo_plugins_login_nonce') || !current_user_can( 'administrator' )){
            wp_redirect( $_SERVER["HTTP_REFERER"].'?error=unauthenticated' );
            exit;
        }

        $post_data = array();
        $post_data['email'] = sanitize_text_field( $_POST['email'] ) ?? '';
        $post_data['password'] = sanitize_text_field( $_POST['password'] ) ?? '';

        $args = array(
            'body'        => $post_data,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'cookies'     => array(),
        );
        $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'login', $args );
        $data = json_decode(wp_remote_retrieve_body( $response ));

        $_SESSION['wp_seo_plugins_status'] = $data->status;
        $_SESSION['wp_seo_plugins_message'] = $data->message;

        if($data->status == 0) {
            // Generating a new api key

            $server_uri = SEOCOPY_SITE_URL;

            $args = array(
                'body'        => array('user_id' => $data->user_id ?? 0),
                'timeout'     => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'Siteurl' => $server_uri
                ),
                'cookies'     => array(),
            );
            $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'apikey/generate', $args );
            $data = json_decode(wp_remote_retrieve_body( $response ));

            $_SESSION['wp_seo_plugins_status'] = $data->status;
            $_SESSION['wp_seo_plugins_message'] = $data->message;
            $_SESSION['wp_seo_plugins_api_key'] = $data->api_key ?? '';

            if( $_SESSION['wp_seo_plugins_api_key'] != '' ) {
                update_option('sc_api_key', sanitize_text_field( $_SESSION['wp_seo_plugins_api_key']) );
                $user = $data->user ?? new stdClass();
                update_option('wp_seo_plugins_user_display_name', $user->data->display_name );
                update_option('wp_seo_plugins_user_email', $user->data->user_email );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=wp-seo-plugins-login' ) );
        exit;
    }

    public function registration() {
        $nonce = sanitize_text_field($_POST['security']);
        if(!wp_verify_nonce($nonce,'wp_seo_plugins_registration_nonce') || !current_user_can( 'administrator' )){
            wp_redirect( $_SERVER["HTTP_REFERER"].'?error=unauthenticated' );
            exit;
        }

        $server_uri = SEOCOPY_SITE_URL;

        $post_data = array();
        $post_data['name'] = sanitize_text_field( $_POST['name'] ) ?? '';
        $post_data['surname'] = sanitize_text_field( $_POST['surname'] ) ?? '';
        $post_data['email'] = sanitize_email( $_POST['email'] ) ?? '';
        $post_data['password'] = sanitize_text_field( $_POST['password'] ) ?? '';

        $args = array(
            'body'        => $post_data,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Siteurl' => $server_uri
            ),
            'cookies'     => array(),
        );
        $response = wp_remote_post( WP_SEO_PLUGINS_BACKEND_URL . 'registration', $args );
        $data = json_decode(wp_remote_retrieve_body( $response ));

        $_SESSION['wp_seo_plugins_status'] = $data->status;
        $_SESSION['wp_seo_plugins_message'] = $data->message;
        $_SESSION['wp_seo_plugins_api_key'] = $data->api_key ?? '';

        if( $_SESSION['wp_seo_plugins_api_key'] != '' ) {
            update_option('sc_api_key', sanitize_text_field( $_SESSION['wp_seo_plugins_api_key'] ));
            $user = $data->user ?? new stdClass();
            update_option('wp_seo_plugins_user_display_name', $user->data->display_name );
            update_option('wp_seo_plugins_user_email', $user->data->user_email );
        }

        wp_redirect( admin_url( 'admin.php?page=wp-seo-plugins-login' ) );
        exit;
    }

    public function logout() {
        delete_option('sc_api_key');
        delete_option('wp_seo_plugins_user_display_name');
        delete_option('wp_seo_plugins_user_email');
        wp_redirect(admin_url('admin.php?page=wp-seo-plugins-login'));
        exit;
    }

}
