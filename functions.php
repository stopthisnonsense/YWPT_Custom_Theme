<?php


function divichild_enqueue_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_script( 'custom-js', get_stylesheet_directory_uri() . '/js/scripts.js', array( 'jquery' ));
}
add_action( 'wp_enqueue_scripts', 'divichild_enqueue_scripts' );


//you can add custom functions below this line:

//Disable admin bar for non admins.


function ds_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}

add_action('after_setup_theme', 'ds_admin_bar');

function ds_login_redirect( $redirect_to, $request, $user ) {
    //is there a user to check?
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        //check for admins
        if ( in_array( 'administrator', $user->roles ) ) {
            // redirect them to the default place
            return $redirect_to;
        }
        if( function_exists( 'ds_site_options' ) ) {
            $dashboard_object = get_field( 'dashboard', 'options' );
            if( isset( $dashboard_object ) ) {
                return get_the_permalink( $dashboard_object );
            }
        }
        return home_url();
        // return get_the_permalink( 'dashboard' );
    }
    return $redirect_to;
}

add_filter( 'login_redirect', 'ds_login_redirect', 10, 3 );


// add option to register user.
if( function_exists('acf_add_options_page') ) {
    // function ds_register_users() {
    //     acf_add_options_page(
    //         [
    //             'page_title' 	=> 'Register User',
    //             'menu_title'	=> 'Register User',
    //             'menu_slug' 	=> 'register-user',
    //             'capability'	=> 'edit_posts',
    //             'redirect'		=> false,
    //             'position'	    => '4.1'
    //         ]
    //     );
    // }
    // add_action('acf/init', 'ds_register_users');

    function ds_site_options() {
        acf_add_options_page(
            [
                'page_title' 	=> 'Site Options',
                'menu_title'	=> 'Site Options',
                'menu_slug' 	=> 'site-options',
                'capability'	=> 'edit_posts',
                'redirect'		=> false,
                'position'	    => '4.2'
            ]
        );
    }
    add_action('acf/init', 'ds_site_options');

    function ds_send_email() {
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
        // add_action('acf/save_post', 'ds_send_email', 15);

        add_action( 'show_user_profile', 'ds_user_profile_fields' );
        add_action( 'edit_user_profile', 'ds_user_profile_fields' );
        function ds_user_profile_fields() {
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



    function my_login_logo() {
        //TODO #1
        ?>
        <style type="text/css">
            body.login {
                background:  url(<?= get_bloginfo( 'url' ); ?>/wp-content/uploads/2021/11/AdobeStock_340923854-scaled.jpeg);
                background-size: cover;
            }
            #login h1 a {
                background-image: url(<?= get_bloginfo( 'url' ); ?>/wp-content/uploads/2021/11/Reaching-Out-Logo_web.png);
                /* background-image: url(<?php echo esc_url( wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ) ); ?>); */
                background-size: contain;
                height: 100px;
                width: 100px;
            }
        </style>
    <?php }
    add_action( 'login_enqueue_scripts', 'my_login_logo' );

    function ds_courses_cpt() {
        $ds_course_args = [
            'label' => 'Courses',
            'description' => '',
            'public' => true,
            'show_in_rest' => true,
            'menu_position' => 7,
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'can_export' => false,
            'delete_with_user' => false,
        ];
        register_post_type( 'course', $ds_course_args );
    }
    function ds_flush_rewrite() {
        ds_courses_cpt();
        flush_rewrite_rules();
    }
    // add_action( 'after_switch_theme', 'ds_flush_rewrite' );
    // add_action( 'init', 'ds_courses_cpt' );


    function ds_restrict_caregivers( $query ) {
        if( !is_admin() && $query->is_main_query() && is_archive( 'course' ) ) {
            $current_user = get_current_user_id();
            // We want the current user
            $current_user_meta = get_userdata( $current_user );
            $user_roles = $current_user_meta->roles;
            //check if this is a caregiver.
            $user_status = get_field( 'status', 'user_' . $current_user );
            if( in_array( 'caregiver', $user_roles ) ) {
                $query->set( 'posts_per_page', 10 );
                if( $user_status == 'active' ) {
                    $meta_args = $query->get('meta_query');
                    $meta_args = [
                        'relation' => 'AND',
                        [
                        'key' => 'paused',
                        'value' => 'unpaused',
                        'compare' => '=',
                        ]
                    ];
                    $query->set( 'meta_query', $meta_args );
                    return $query;
                }
                return $query;
            }
            // $query->set('posts_per_page', 1);
            // return $query;
        }

    }
    // add_action( 'pre_get_posts', 'ds_restrict_caregivers', 11 );


    /**
     *  @param INT $interval the number of days
     *  @param INT $current_user the current user ID
     *  @return object the interval date.
    **/
    function interval_date( $interval, $current_user ) {
        if( empty($interval) || $interval === false ) {
            $interval = 0;
        }
        if( empty( $current_user ) ) {
            $current_user = get_current_user_id();
        }

        $current_user_meta = get_userdata( $current_user );
        // var_dump( $current_user_meta );
        $user_status = get_field( 'status', 'user_' . $current_user );
        $user_roles = $current_user_meta->roles;
        $register_date = date_create($current_user_meta->register_date);

        $interval =  date_interval_create_from_date_string("$interval days");
        $interv_date = $register_date;
        $interv_date = date_add($interv_date , $interval);
        return $interv_date;
    }

    function course_list_shortcode()
{
	$params = array(
        'limit' => -1,
        'orderby' => 'ASC'
    );

    $courses = pods('course', $params);

    $current_date = date_create(date('Ymd'));
    $current_user = get_current_user_id();
    $current_user_meta = get_userdata( $current_user );
    // var_dump( $current_user_meta );
    $user_status = get_field( 'status', 'user_' . $current_user );
    $user_roles = $current_user_meta->roles;
    $register_date = date_create($current_user_meta->register_date);
    var_dump( $register_date );

    $returnCode = '<div class="grid grid--courses">';

        if ($courses->total() > 0)
        {
            $release_dates = [];
            while ($courses->fetch())
            {

                $id = $courses->field('ID');
                $class = 'card card--courses';
                $class = esc_attr( implode( ' ', get_post_class( $class, $id ) ) );
                $title = $courses->display('post_title');
                $embedCode = $courses->display('embed_code');
                $interval = $courses->display('unlock_interval');


                $interv_date = interval_date( $interval, $current_user );
                // if( empty($interv_date) ) {
                //     var_dump( $interv_date );
                // }
                $prev_course = $courses->field('previous_course');
                $postImage = '';
                $content = get_the_excerpt($id);
                $release_date = date_create( $courses->field( 'release_date' ) );
                if( empty( $courses->field( 'release_date' ) ) ) {
                    $release_date = $interv_date;
                }

                $course_data[] = [
                    'id' => $id,
                    'interval_date' => $interv_date,
                     'release_date' => $release_date ];
                $release_dates[] = [
                    'id' => $id,
                    'release_date' => $release_date
                ];
                // var_dump( $release_dates );
                // var_dump( $course_data );
                if( has_post_thumbnail( $id ) ) {
                    $postImage = get_the_post_thumbnail( $id, 'large', [
                        'class' => 'card__image card__image--courses',
                        'height' => '',
                        'width' => '',
                    ] );
                }

                $url =  'href="' . get_the_permalink( $id ) . '"';
                $releases = 'Released';
                if( in_array( 'caregiver', $user_roles ) || !is_user_logged_in() ) {
                    if( $user_status != 'active' || $release_date > $current_date || $interv_date > $current_date ) {
                        $url =  '';
                        $class .= ' card--paused';

                    }
                }
                if( $release_date > $current_date || $interv_date > $current_date ) {
                    $releases = 'Releases';
                }
                $course_date = $release_date;
                if( $interv_date > $release_date ) {
                    $course_date = $interv_date;
                }
                $previous_course_template = '';
                // var_dump( $prev_course );
                if( $prev_course && $id != $prev_course['ID'] ) {
                    $previous_course_template = '<p> Previous Course: ' . get_the_title($prev_course['ID']) . ' </p>';
                    $previous_course_data = pods( 'course', $prev_course['ID'] );

                    $previous_course_data = [
                        'ID' => $prev_course['ID'],
                        'release_date' => $previous_course_data->field('release_date'),
                        'interval_date' => $courses->field('unlock_interval'),
                    ];
                    var_dump($previous_course_data);
                }

                $returnCode .= '<a id="' . $id . '" class="' . $class .'"' . $url . ' >
                    ' . $postImage . '
                    <h2 class="card__title card__title--courses">'.$title.'</h2>
                    <div class="card__content card__content--courses">
                        <h3 class="card__time card__time--courses"> ' . $releases .' ' . date_format($course_date, 'M d, Y') . '</h3> <p>' . get_the_excerpt( $id ) . '</p>' . $previous_course_template .  '
                    </div>
                </a>';
                // unset($previous_course_template);
            }
        }

	$returnCode .= '</div>';

	return $returnCode;
}

add_shortcode('course_list', 'course_list_shortcode');

add_action('frm_before_destroy_entry', 'asu_delete_user_with_entry');

function asu_delete_user_with_entry( $entry_id ) {
    $form_id = 3;// Replace 10 with the ID of your form
    $field_id = 18;//Replace 25 with the ID of your userID field
    $entry = FrmEntry::getOne( $entry_id, true );
    if ( $entry->form_id == $form_id ) {
        if ( isset( $entry->metas[ $field_id ] ) && $entry->metas[ $field_id ] ) {
            $user_id = $entry->metas[ $field_id ];
            $user = new WP_User( $user_id );
            if ( ! in_array( 'administrator', $user->roles ) ) {
                wp_delete_user( $user->ID );
            }
        }
    }
}

add_action('frm_after_update_entry', 'update_user_role', 100, 2);
add_action('frm_after_create_entry', 'update_user_role', 100, 2);
function update_user_role($entry_id, $form_id){
    if ( $form_id == 3 ) {

        $userid = $_POST['item_meta'][18];
        $role = $_POST['item_meta'][25];

        if ( $userid ) {
            $user = get_userdata( $userid );
            if ( $user && ! $user->has_cap('administrator') ) {
                $user->set_role( $role );
            }
        }
    }
}