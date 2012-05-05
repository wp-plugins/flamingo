<?php
/**
** Module for WordPress user.
**/

add_filter( 'flamingo_contact_history_column', 'flamingo_user_contact_history_column', 10, 2 );

function flamingo_user_contact_history_column( $output, $item ) {
	if ( empty( $item->email ) )
		return $output;

	$user_search = new WP_User_Query( array(
		'search' => $item->email ) );

	$count = (int) $user_search->get_total();

	if ( 0 == $count ) {
		return $output;
	} elseif ( 1 == $count ) {
		$history = __( '1 User', 'flamingo' );
	} else {
		$history = str_replace( '%',
			number_format_i18n( $count ), __( '% Users', 'flamingo' ) );
	}

	if ( 0 < $count ) {
		$link = sprintf( 'users.php?s=%s', urlencode( $item->email ) );
		$history = '<a href="' . admin_url( $link ) . '">' . $history . '</a>';
	}

	if ( ! empty( $output ) )
		$output .= '<br />';

	$output .= $history;

	return $output;
}

add_action( 'profile_update', 'flamingo_user_profile_update' );
add_action( 'user_register', 'flamingo_user_profile_update' );

function flamingo_user_profile_update( $user_id ) {
	$user = new WP_User( $user_id );

	$email = $user->user_email;
	$name = $user->display_name;

	$props = array(
		'first_name' => $user->first_name,
		'last_name' => $user->last_name );

	if ( ! empty( $email ) ) {
		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'props' => $props,
			'channel' => 'user' ) );
	}
}

/* Collect contact info from existing users when activating plugin */
add_action( 'activate_' . FLAMINGO_PLUGIN_BASENAME, 'flamingo_collect_contacts_from_users' );

function flamingo_collect_contacts_from_users() {
	$users = get_users( array(
		'number' => 100 ) );

	foreach ( $users as $user ) {
		$email = $user->user_email;
		$name = $user->display_name;

		if ( empty( $email ) )
			continue;

		$props = array(
			'first_name' => $user->first_name,
			'last_name' => $user->last_name );

		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'props' => $props,
			'channel' => 'user' ) );
	}
}

?>