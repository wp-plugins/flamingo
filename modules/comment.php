<?php
/**
** Module for WordPress comment.
**/

add_filter( 'flamingo_contact_history_column', 'flamingo_comment_contact_history_column', 10, 2 );

function flamingo_comment_contact_history_column( $output, $item ) {
	if ( empty( $item->email ) )
		return $output;

	$count = (int) get_comments( array(
		'count' => true,
		'author_email' => $item->email,
		'status' => 'approve',
		'type' => 'comment' ) );

	if ( 0 == $count ) {
		return $output;
	} elseif ( 1 == $count ) {
		$history = __( '1 Comment', 'flamingo' );
	} else {
		$history = str_replace( '%',
			number_format_i18n( $count ), __( '% Comments', 'flamingo' ) );
	}

	if ( 0 < $count ) {
		$link = sprintf( 'edit-comments.php?s=%s', urlencode( $item->email ) );
		$history = '<a href="' . admin_url( $link ) . '">' . $history . '</a>';
	}

	if ( ! empty( $output ) )
		$output .= '<br />';

	$output .= $history;

	return $output;
}

add_action( 'wp_insert_comment', 'flamingo_insert_comment' );

function flamingo_insert_comment( $comment_id ) {
	$comment = get_comment( $comment_id );

	if ( 1 != (int) $comment->comment_approved )
		return;

	Flamingo_Contact::add( array(
		'email' => $comment->comment_author_email,
		'name' => $comment->comment_author,
		'channel' => 'comment' ) );
}

add_action( 'transition_comment_status', 'flamingo_transition_comment_status', 10, 3 );

function flamingo_transition_comment_status( $new_status, $old_status, $comment ) {
	if ( 'approved' != $new_status )
		return;

	$email = $comment->comment_author_email;
	$name = $comment->comment_author;

	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name,
		'channel' => 'comment' ) );
}

/* Collect contact info from existing comments when activating plugin */
add_action( 'activate_' . FLAMINGO_PLUGIN_BASENAME, 'flamingo_collect_contacts_from_comments' );

function flamingo_collect_contacts_from_comments() {
	$comments = get_comments( array(
		'status' => 'approve',
		'type' => 'comment',
		'number' => 100 ) );

	foreach ( $comments as $comment ) {
		$email = $comment->comment_author_email;
		$name = $comment->comment_author;

		if ( empty( $email ) )
			continue;

		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'channel' => 'comment' ) );
	}
}

?>