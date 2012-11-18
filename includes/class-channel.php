<?php

class Flamingo_Channel {

	const post_type = 'flamingo_channel';

	public static $found_items = 0;

	public $id;
	public $title;
	public $name;
	public $parent;
	public $fields;

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Flamingo Channels', 'flamingo' ),
				'singular_name' => __( 'Flamingo Channel', 'flamingo' ) ),
			'rewrite' => false,
			'query_var' => false,
			'hierarchical' => true ) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'posts_per_page' => 10,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC',
			'meta_key' => '',
			'meta_value' => '',
			'post_status' => 'any' );

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post )
			$objs[] = new self( $post );

		return $objs;
	}

	public static function add( $args = '' ) {
		$defaults = array(
			'title' => '',
			'name' => '',
			'parent' => 0,
			'fields' => array() );

		$args = wp_parse_args( $args, $defaults );

		$obj = new self();

		$obj->title = $args['title'];
		$obj->name = $args['name'];
		$obj->parent = $args['parent'];
		$obj->fields = $args['fields'];

		$obj->save();

		return $obj;
	}

	public function __construct( $post = null ) {
		if ( ! empty( $post ) && ( $post = get_post( $post ) ) ) {
			$this->id = $post->ID;

			$this->title = $post->post_title;
			$this->name = $post->post_name;
			$this->parent = $post->post_parent;
			$this->fields = get_post_meta( $post->ID, '_fields', true );
		}
	}

	public function save() {
		if ( ! empty( $this->title ) )
			$post_title = $this->title;
		else
			$post_title = __( '(No Title)', 'flamingo' );

		$postarr = array(
			'ID' => absint( $this->id ),
			'post_type' => self::post_type,
			'post_status' => 'publish',
			'post_title' => $post_title,
			'post_name' => $this->name,
			'post_parent' => $this->parent );

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			$this->id = $post_id;
			update_post_meta( $post_id, '_fields', $this->fields );
		}

		return $post_id;
	}
}

?>