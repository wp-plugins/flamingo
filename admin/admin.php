<?php

require_once FLAMINGO_PLUGIN_DIR . '/admin/admin-functions.php';

add_action( 'admin_menu', 'flamingo_admin_menu' );

function flamingo_admin_menu() {
	add_menu_page(
		__( 'Flamingo Address Book', 'flamingo' ), __( 'Flamingo', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo', 'flamingo_contact_admin_page' );

	$contact_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Address Book', 'flamingo' ), __( 'Address Book', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo', 'flamingo_contact_admin_page' );

	add_action( 'load-' . $contact_admin, 'flamingo_load_contact_admin' );

	$inbound_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Inbound Messages', 'flamingo' ), __( 'Inbound Messages', 'flamingo' ),
		'flamingo_edit_inbound_messages', 'flamingo_inbound', 'flamingo_inbound_admin_page' );

	add_action( 'load-' . $inbound_admin, 'flamingo_load_inbound_admin' );
}

add_filter( 'set-screen-option', 'flamingo_set_screen_options', 10, 3 );

function flamingo_set_screen_options( $result, $option, $value ) {
	$flamingo_screens = array(
		'toplevel_page_flamingo_per_page',
		'flamingo_page_flamingo_inbound_per_page' );

	if ( in_array( $option, $flamingo_screens ) )
		$result = $value;

	return $result;
}

add_action( 'admin_enqueue_scripts', 'flamingo_admin_enqueue_scripts' );

function flamingo_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'flamingo' ) )
		return;

	wp_enqueue_script( 'thickbox' );
	wp_enqueue_script( 'postbox' );

	wp_enqueue_script( 'flamingo-admin',
		plugins_url( 'admin/script.js', FLAMINGO_PLUGIN_BASENAME ),
		array( 'jquery' ), FLAMINGO_VERSION, true );

	wp_enqueue_style( 'flamingo-admin',
		plugins_url( 'admin/style.css', FLAMINGO_PLUGIN_BASENAME ),
		array(), FLAMINGO_VERSION, 'all' );
}

/* Updated Message */

add_action( 'flamingo_admin_updated_message', 'flamingo_admin_updated_message' );

function flamingo_admin_updated_message() {
	if ( ! empty( $_REQUEST['message'] ) ) {
		if ( 'contactupdated' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Contact updated.', 'flamingo' ) );
		elseif ( 'inboundtrashed' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages trashed.', 'flamingo' ) );
		elseif ( 'inbounduntrashed' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages restored.', 'flamingo' ) );
		elseif ( 'inbounddeleted' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages deleted.', 'flamingo' ) );
		else
			return;
	} else {
		return;
	}

	if ( empty( $updated_message ) )
		return;

?>
<div id="message" class="updated"><p><?php echo $updated_message; ?></p></div>
<?php
}

/* Contact */

function flamingo_load_contact_admin() {
	$action = flamingo_current_action();

	$redirect_to = admin_url( 'admin.php?page=flamingo' );

	if ( 'save' == $action && ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Contact( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_contact', $post->id ) )
				wp_die( __( 'You are not allowed to edit this item.', 'flamingo' ) );

			check_admin_referer( 'flamingo-update-contact_' . $post->id );

			$post->props = (array) $_POST['contact'];

			$post->name = trim( $_POST['contact']['name'] );

			$post->tags = ! empty( $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				? explode( ',', $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				: array();

			$post->save();

			$redirect_to = add_query_arg( array(
				'action' => 'edit',
				'post' => $post->id,
				'message' => 'contactupdated' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$current_screen = get_current_screen();

	if ( ! class_exists( 'Flamingo_Contacts_List_Table' ) )
		require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-contacts-list-table.php';

	add_filter( 'manage_' . $current_screen->id . '_columns',
		array( 'Flamingo_Contacts_List_Table', 'define_columns' ) );

	add_screen_option( 'per_page', array(
		'label' => __( 'Contacts', 'flamingo' ),
		'default' => 20 ) );
}

function flamingo_contact_admin_page() {
	if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['post'] ) ) {
		$post_id = absint( $_REQUEST['post'] );

		if ( Flamingo_Contact::post_type == get_post_type( $post_id ) ) {
			if ( 'edit' == $_REQUEST['action'] ) {
				flamingo_contact_edit_page();
				return;
			}
		}
	}

	$list_table = new Flamingo_Contacts_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">
<?php screen_icon( 'users' ); ?>

<h2><?php
	echo esc_html( __( 'Flamingo Address Book', 'flamingo' ) );

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h2>

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Contacts', 'flamingo' ), 'flamingo-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_contact_edit_page() {
	$post = new Flamingo_Contact( $_REQUEST['post'] );

	if ( empty( $post ) )
		return;

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

/* Inbound Message */

function flamingo_load_inbound_admin() {
	$action = flamingo_current_action();

	$redirect_to = admin_url( 'admin.php?page=flamingo_inbound' );

	if ( 'trash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-trash-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$trashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to move this item to the Trash.', 'flamingo' ) );

			if ( ! $post->trash() )
				wp_die( __( 'Error in moving to Trash.', 'flamingo' ) );

			$trashed += 1;
		}

		if ( ! empty( $trashed ) )
			$redirect_to = add_query_arg( array( 'message' => 'inboundtrashed' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'untrash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-untrash-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$untrashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to restore this item from the Trash.', 'flamingo' ) );

			if ( ! $post->untrash() )
				wp_die( __( 'Error in restoring from Trash.', 'flamingo' ) );

			$untrashed += 1;
		}

		if ( ! empty( $untrashed ) )
			$redirect_to = add_query_arg( array( 'message' => 'inbounduntrashed' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete_all' == $action ) {
		$_REQUEST['post'] = flamingo_get_all_ids_in_trash(
			Flamingo_Inbound_Message::post_type );

		$action = 'delete';
	}

	if ( 'delete' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-delete-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to delete this item.', 'flamingo' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'flamingo' ) );

			$deleted += 1;
		}

		if ( ! empty( $deleted ) )
			$redirect_to = add_query_arg( array( 'message' => 'inbounddeleted' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$current_screen = get_current_screen();

	if ( ! class_exists( 'Flamingo_Inbound_Messages_List_Table' ) )
		require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-inbound-messages-list-table.php';

	add_filter( 'manage_' . $current_screen->id . '_columns',
		array( 'Flamingo_Inbound_Messages_List_Table', 'define_columns' ) );

	add_screen_option( 'per_page', array(
		'label' => __( 'Messages', 'flamingo' ),
		'default' => 20 ) );
}

function flamingo_inbound_admin_page() {
	if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['post'] ) ) {
		$post_id = absint( $_REQUEST['post'] );

		if ( Flamingo_Inbound_Message::post_type == get_post_type( $post_id ) ) {
			if ( 'edit' == $_REQUEST['action'] ) {
				flamingo_inbound_edit_page();
				return;
			}
		}
	}

	$list_table = new Flamingo_Inbound_Messages_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">
<?php screen_icon( 'edit-comments' ); ?>

<h2><?php
	echo esc_html( __( 'Inbound Messages', 'flamingo' ) );

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h2>

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<?php $list_table->views(); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Messages', 'flamingo' ), 'flamingo-inbound' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_inbound_edit_page() {
	$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

	if ( empty( $post ) )
		return;

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-inbound-form.php';

}

?>