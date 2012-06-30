<?php

function flamingo_htmlize( $val ) {
	if ( is_array( $val ) ) {
		$result = '';

		foreach ( $val as $v )
			$result .= '<li>' . flamingo_htmlize( $v ) . '</li>';

		return '<ul>' . $result . '</ul>';
	}

	return wpautop( esc_html( (string) $val ) );
}

?>