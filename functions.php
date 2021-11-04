<?php


function divichild_enqueue_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_script( 'custom-js', get_stylesheet_directory_uri() . '/js/scripts.js', array( 'jquery' ));
}
add_action( 'wp_enqueue_scripts', 'divichild_enqueue_scripts' );


//you can add custom functions below this line:

if( function_exists('acf_add_options_page') ) {
    function ywpt_register_users() {
        acf_add_options_page(
            [
                'page_title' 	=> 'Register User',
                'menu_title'	=> 'Register User',
                'menu_slug' 	=> 'register-user',
                'capability'	=> 'edit_posts',
                'redirect'		=> false,
                'position'	    => '4.1'
            ]
        );
    }
    add_action('acf/init', 'ywpt_register_users');
    function ywpt_send_email() {
        // $screen = get_current_screen();
        // if( strpos($screen->ID, 'register-user' == true) ) {

            $specificEmail = get_field('email', 'options');
            $role = get_field('role', 'options');
            $userData = get_user_by('email', $specificEmail);

            if( $userData == false || !isset($userData) ) {
                wp_create_user( $specificEmail, wp_generate_password(), $specificEmail );
                $wp_user_object = get_user_by('email', $specificEmail);
                $wp_user_object->set_role($role);
                update_field( 'status', 'pending', 'user_' . $wp_user_object->ID);
            }
            update_field( 'email', '', 'options' );
            update_field( 'role', 'coach', 'options' );



            $userData = get_user_by('email', $specificEmail);
            if( $userData == false || !isset($userData) ) {
                return;
            }
            $key = get_password_reset_key($userData);
            $resetLink = $rp_link = '<a href="' .
                network_site_url("wp-login.php?action=rp&key=$key&login=" .
                rawurlencode($specificEmail), 'login') . '">' .
                "Click Here To Set Your Password" .
                '</a>';
            // $userData = $userData->ID;

            // $key = get_password_reset_key( $userData );
            $message = '<html><body>';
            $message .= '';
            $message .= $resetLink;
            $message .= '</html></body>';

            $fromEmail = get_option('admin_email');
            $subject = 'Welcome!';

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ASU <' . $fromEmail . '>'
            );

            wp_mail($specificEmail, $subject, $message, $headers);
        }

        // }
        add_action('acf/save_post', 'ywpt_send_email', 15);

        add_action( 'show_user_profile', 'ywpt_user_profile_fields' );
        add_action( 'edit_user_profile', 'ywpt_user_profile_fields' );
        function ywpt_user_profile_fields() {
            if( !isset( $_GET['user_id'] ) ) {
                return;
            }
            $user_id = $_GET['user_id'];
            // if( !isset( $user_id ) ) {
            //     return;
            // }
            $user_meta = get_userdata( $user_id );
            $user_status = get_field( 'status', 'user_' . $user_id );
            // update_field( 'status', 'active', 'user' . $user_id);
            // $user_status = get_field( 'status', 'user' . $user_id );
            if( !isset($user_status) && in_array( 'caregiver', $user_meta->roles ) ) {
                update_field( 'status', 'pending', 'user_' . $user_id);
                $user_status = get_field( 'status', 'user_' . $user_id );
            }
            ?>
            <?php if( in_array( 'caregiver', $user_meta->roles) ) { ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>Account Status</th>
                        <td><input type="text" readonly value="<?php echo esc_attr( $user_status ); ?>"></td>
                    </tr>
                </tbody>

            </table>
            <?php } ?>
        <?php # var_dump($user_id, $user_status);
         }
    }