<?php namespace digi;

if ( !defined( 'ABSPATH' ) ) exit;

class recommendation_category_term_model extends term_model {

	public function __construct( $object, $field_wanted = array()) {
		$this->model = array_merge( $this->model, array(
			'unique_key' => array(
				'type' 				=> 'string',
				'meta_type'		=> 'single',
				'field'				=> '_wpdigi_unique_key',
			),
			'unique_identifier' => array(
				'type' 			=> 'string',
				'meta_type'	=> 'single',
				'field'			=> '_wpdigi_unique_identifier',
			),
			'thumbnail_id' => array(
				'type' 			=> 'integer',
				'meta_type'	=> 'multiple',
			),
			'associated_document_id' => array(
				'type' 		=> 'array',
				'meta_type'	=> 'multiple',
			),
			'recommendation_category_print_option' => array(
				'type' 		=> 'array',
				'meta_type'	=> 'multiple',
			),
			'recommendation_print_option' => array(
				'type' 		=> 'array',
				'meta_type'	=> 'multiple',
			),
			'child' => array(
				'recommendation_term'	=> array(
					'export'			=> true,
					'type'				=> 'taxonomy',
					'controller'	=> '\digi\recommendation_term_class',
					'field'					=> 'parent',
					'value'					=> 'post_id',
					'custom_field'	=> 'post_id',
				),
			),
		) );

		parent::__construct( $object, $field_wanted );
	}

}
