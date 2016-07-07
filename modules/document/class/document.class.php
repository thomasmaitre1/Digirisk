<?php if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Fichier du controlleur principal pour les catégories de documents dans Digirisk / Controller file for attachment categories for Digirisk
 *
 * @author Evarisk development team <dev@evarisk.com>
 * @version 6.0
 */

/**
 * Classe du controlleur principal pour les catégories de documents dans Digirisk / Controller class for attachment categories for Digirisk
 *
 * @author Evarisk development team <dev@evarisk.com>
 * @version 6.0
 */
class document_class extends post_class {

	/**	Définition des différents type pour le module des documents / Define type for document module	*/
	protected $model_name   = 'wpdigi_document_mdl_01';
	protected $post_type    = 'attachment';
	public $attached_taxonomy_type    = 'attachment_category';
	protected $meta_key    	= '_wpdigi_document';

	/**	Défini la route par défaut permettant d'accèder aux sociétés depuis WP Rest API  / Define the default route for accessing to risk from WP Rest API	*/
	protected $base = 'digirisk/printed-document';
	protected $version = '0.1';

	public $element_prefix = 'DOC';

	/**	Définition du nombre de document a afficher par page / Define number of document to display per page	*/
	protected $limit_document_per_page = -1;

	/**
	 * Instanciation de la gestion des document imprimés / Instanciate printes document
	 */
	protected function construct() {
		/**	Inclusion du modèle pour les groupements / Include groups' model	*/
		include_once( WPDIGI_DOC_PATH . '/model/document.model.01.php' );

		/**	Define taxonomy for attachment categories	*/
		add_action( 'init', array( $this, 'custom_type_creation' ), 5  );

		/**	Ajoute le bouton d'impression du document unique dans la partie gauche de l'écran / Add the button for main document print into left part of screen	*/
		add_filter( 'wpdigi_society_tree_footer', array( $this, 'filter_display_group_sheet_print_button' ), 10, 2 );
	}

	public function index( $args_where = array( 'parent_id' => 0 ), $cropped = false ) {
		$array_model = array();

		$args = array(
			'post_status' 		=> 'inherit',
			'post_type' 		=> $this->post_type,
			'posts_per_page' 	=> -1,
			'post_mime_type' 	=> 'application/vnd.oasis.opendocument.text',
		);

		$args = array_merge( $args, $args_where );
		$array_post = get_posts( $args );

		if( !empty( $array_post ) ) {
			foreach( $array_post as $key => $post ) {
				$array_model[$key] = new $this->model_name( $post, $this->meta_key, $cropped );
			}
		}

		return $array_model;
	}

	public function get_document_path( $path_type = 'basedir' ) {
		$upload_dir = wp_upload_dir();
		return $upload_dir[ $path_type ] . '/digirisk';
	}

	/**
	 * Création du type d'élément interne a wordpress pour gérer les catégories de documents / Create wordpress element type for managing attachment categories
	 *
	 * @uses register_taxonomy()
	 */
	function custom_type_creation() {
		$labels = array(
			'name'              => 'Categories',
			'singular_name'     => 'Category',
			'search_items'      => 'Search Categories',
			'all_items'         => 'All Categories',
			'parent_item'       => 'Parent Category',
			'parent_item_colon' => 'Parent Category:',
			'edit_item'         => 'Edit Category',
			'update_item'       => 'Update Category',
			'add_new_item'      => 'Add New Category',
			'new_item_name'     => 'New Category Name',
			'menu_name'         => 'Category',
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'query_var' => 'true',
			'rewrite' => 'true',
			'show_admin_column' => 'true',
		);

		register_taxonomy( $this->attached_taxonomy_type, $this->post_type, $args );
	}

	/**
	 * Accrochage au filtre permettant d'ajouter des éléments d'affichages dans la partie gauche de l'écran sous la liste des unités de travail / Hook filter allowing to extend left part of screen below workunit list
	 *
	 * @param integer $group_id l'identifiant du groupement pour lequel il faut afficher la page de génération du document unique / The group identifier we have to display the DUER print interface
	 * @param string $display_mode Le mode d'affichage de l'interface / The main display mode of digirisk interface
	 */
	function filter_display_group_sheet_print_button( $group_id, $display_mode ) {
		require( wpdigi_utils::get_template_part( WPDIGI_DOC_DIR, WPDIGI_DOC_TEMPLATES_MAIN_DIR, $display_mode, "print", "button" ) );
	}

	/**
	 * Filtrage de l'affichage des documents dans la fiche d'un élément (unité de travail/groupement/etc) / Filter documents' display into a element sheet
	 *
	 * @param string $output Le contenu actuel a afficher, contenu que l'on va agrémenter / The current content to update before return and display
	 * @param JSon_Object $element L'élément sur le quel on se trouve et pour lequel on veut afficher les documents / Current element we are on and we want to display documents' for
	 * @param string $tab_to_display L'onglet sur lequel on se trouve actuellement défini par le filtre principal ( wpdigi-workunit-default-tab ) puis par l'ajax / Current tab we are on defined par main filter ( wpdigi-workunit-default-tab ) and then by ajax
	 *
	 * @return string Le contenu a afficher pour l'onglet et l'élément actuel / The content to display for current tab and element we are one
	 */
	function filter_display_doc_in_element( $output, $element, $tab_to_display ) {
		if ( 'sheet' == $tab_to_display ) {
			ob_start();
			require_once( wpdigi_utils::get_template_part( WPDIGI_DOC_DIR, WPDIGI_DOC_TEMPLATES_MAIN_DIR, 'simple', "sheet", "generation-form" ) );
			$this->display_document_list( $element );
			$output .= ob_get_contents();
			ob_end_clean();
		}

		return $output;
	}

	/**
	 * AFFICHAGE/DISPLAY - Affiche une liste de document associés à un élément selon la liste passée en paramètre du shortcode ou si elle est vide des documents liés dans la base de données / Display a document list associated to a given element passed through shortcode parameters if defined or by getting list into database if nothing tis given in args

	 * @param array $args Les différents paramètres passés au shortcode lors de son utilisation / Different parameters passed through shortcode when used by user
	 */
	function display_document_list( $element ) {
		if ( empty( $element ) )
			return false;

		$list_document_id = !empty( $element->option[ 'associated_document_id' ] ) && !empty( $element->option[ 'associated_document_id' ][ 'document' ] ) ? $element->option[ 'associated_document_id' ][ 'document' ] : null;
		if ( 0 < $this->limit_document_per_page ) {
			$current_page = !empty( $_GET[ 'current_page' ] ) ? (int)$_GET[ 'current_page' ] : 1;
			$number_page = ceil( count ( $list_document_id ) ) / $this->limit_document_per_page;

			$list_document_id = array_slice( $list_document_id, ($current_page - 1) * $this->limit_document_per_page, $this->limit_document_per_page );
		}

		require_once( wpdigi_utils::get_template_part( WPDIGI_DOC_DIR, WPDIGI_DOC_TEMPLATES_MAIN_DIR, 'common', "printed", "list" ) );
	}

	/**
	 * Récupération de la liste des modèles de fichiers disponible pour un type d'élément / Get file model list for a given element type
	 *
	 * @todo Récupèrer le chemin des documents par défaut si rien n'est trouvé dans la base / Put default way for model into plugin if nothing found into database
	 *
	 * @param array $current_element_type La liste des types pour lesquels il faut récupérer les modèles de documents / Type list we have to get document model list for
	 *
	 * @return array Un statut pour la réponse, un message si une erreur est survenue, le ou les identifiants des modèles si existants / Response status, a text message if an error occured, model identifier if exists
	 */
	function get_model_for_element( $current_element_type ) {
		$response = array(
			'status'		=> false,
			'message'		=> __( 'An error occured while getting model to use for generation', 'digirisk' ),
			'model_id'		=> null,
			'model_path'	=> null,
		);

		$get_model_args = array(
			'post_type' 	=> $this->get_post_type(),
			'post_status'	=> 'inherit',
			'tax_query' => array(
				array(
					'taxonomy' => $this->attached_taxonomy_type,
					'field'    => 'slug',
					'terms'    => wp_parse_args( $current_element_type, array( 'default_model' ) ),
					'operator' => 'AND',
				),
			),
		);
		$element_sheet_default_model = new WP_Query( $get_model_args );
		if ( $element_sheet_default_model->have_posts() ) {
			if ( 2 <= $element_sheet_default_model->post_count ) {
				foreach ( $element_sheet_default_model->posts as $attachment ) {
					//todo: if there are several model which one to choose
				}
			}
			else {
				$workunit_model_to_use = get_attached_file( $element_sheet_default_model->post->ID );
				if ( is_file( $workunit_model_to_use ) ) {
					$response = array(
						'status'		=> true,
						'message'		=> '',
						'model_id'		=> $element_sheet_default_model->post->ID,
						'model_path'	=> $workunit_model_to_use,
					);
				}
			}
		}

		if ( !$response[ 'status' ] ) {
			foreach ( $current_element_type as $document_type ) {
				if ( is_file( WPDIGI_PATH . 'core/assets/document_template/' . $document_type . '.odt' ) ) {
					$response = array(
						'status'			=> true,
						'message'			=> '',
						'model_id'		=> null,
						'model_path'	=> WPDIGI_PATH . 'core/assets/document_template/' . $document_type . '.odt',
					);
					break;
				}
			}
		}

		return $response;
	}

	/**
	 * Création d'un fichier odt a partir d'un modèle de document donné et d'un modèle de donnée / Create an "odt" file from a given document model and a data model
	 *
	 * @param string $model_path Le chemin vers le fichier modèle a utiliser pour la génération / The path to model file to use for generate the final document
	 * @param array $document_content Un tableau contenant le contenu du fichier a écrire selon l'élément en cours d'impression / An array with the content for building file to print
	 * @param Object $element L'élément courant sur lequel on souhaite générer un document / Current element where the user want to generate a file for
	 *
	 */
	function generate_document( $model_path, $document_content, $document_name ) {
		$response = array(
			'status'	=> false,
			'message'	=> '',
			'link'		=> '',
		);

		require_once( WPDIGI_PATH . '/core/odtPhpLibrary/odf.php');

		$digirisk_directory = $this->get_document_path();
		$document_path = $digirisk_directory . '/' . $document_name;

		$config = array(
			'PATH_TO_TMP' => $digirisk_directory . '/tmp',
		);
		if( !is_dir( $config[ 'PATH_TO_TMP' ] ) ) {
			wp_mkdir_p( $config[ 'PATH_TO_TMP' ] );
		}

		/**	On créé l'instance pour la génération du document odt / Create instance for document generation */
		@ini_set( 'memory_limit', '256M' );
		$DigiOdf = new DigiOdf( $model_path, $config );

		/**	Vérification de l'existence d'un contenu a écrire dans le document / Check if there is content to put into file	*/
		if ( !empty( $document_content ) ) {
			/**	Lecture du contenu à écrire dans le document / Read the content to write into document	*/
			foreach ( $document_content as $data_key => $data_value ) {
				$DigiOdf = $this->set_document_data( $data_key, $data_value, $DigiOdf );
			}
		}

		/**	Vérification de l'existence du dossier de destination / Check if final directory exists	*/
		if( !is_dir( dirname( $document_path ) ) ) {
			wp_mkdir_p( dirname( $document_path ) );
		}

		/**	Enregistrement du document sur le disque / Save the file on disk	*/
		$DigiOdf->saveToDisk( $document_path );

		/**	Dans le cas ou le fichier a bien été généré, on met a jour les informations dans la base de données / In case the file have been saved successfully, save information into database	*/
		if ( is_file( $document_path ) ) {
			$response[ 'status' ] = true;
			$response[ 'link' ] = $document_path;
		}

		return $response;
	}

	function set_document_data( $data_key, $data_value, $current_odf ) {

		/**	Dans le cas où la donnée a écrire est une valeur "simple" (texte) / In case the data to write is a "simple" (text) data	*/
		if ( !is_array( $data_value ) ) {
			$current_odf->setVars( $data_key, stripslashes( $data_value ), true, 'UTF-8' );
		}
		else if ( is_array( $data_value ) && isset( $data_value[ 'type' ] ) && !empty( $data_value[ 'type' ] ) ) {
			switch ( $data_value[ 'type' ] ) {

				case 'picture':
// 					$current_odf->setImage( $data_key, $data_value[ 'value' ], ( !empty( $data_value[ 'option' ] ) && !empty( $data_value[ 'option' ][ 'size' ] ) ? $data_value[ 'option' ][ 'size' ] : 0 ) );
					break;

				case 'segment':
					$segment = $current_odf->setdigiSegment( $data_key );

					if ( $segment && is_array( $data_value[ 'value' ] ) ) {
						foreach ( $data_value[ 'value' ] as $segment_detail ) {
							foreach ( $segment_detail as $segment_detail_key => $segment_detail_value ) {
								if ( is_array( $segment_detail_value ) && array_key_exists( 'type', $segment_detail_value ) && ( 'sub_segment' == $segment_detail_value[ 'type' ] ) ) {
									foreach ( $segment_detail_value[ 'value' ] as $sub_segment_data ) {
										foreach ( $sub_segment_data as $sub_segment_data_key => $sub_segment_data_value ) {
											$segment->$segment_detail_key = $this->set_document_data( $sub_segment_data_key, $sub_segment_data_value, $segment );
										}
									}
								}
								else {
									$segment = $this->set_document_data( $segment_detail_key, $segment_detail_value, $segment );
								}
							}

							$segment->merge();
						}

						$current_odf->mergedigiSegment( $segment );
					}
					unset( $segment );
					break;
			}
		}

		return $current_odf;
	}

	/**
	 * Récupération de la prochaine version pour un type de document / Get next version number for a document type
	 *
	 * @param array $current_element_type Le type de document actuellement en cours de création / Currently being created document type
	 *
	 * @return integer La version du document actuellement en cours de création / Currently being created document version
	 */
	function get_document_type_next_revision( $current_element_type, $element_id ) {
		global $wpdb;

		/**	Récupération de la date courante / Get current date	*/
		$today = getdate();

		/**	Définition des paramètres de la requête de récupération des documents du type donné pour la date actuelle / Define parameters for query in order to get created document for current date	*/
		$get_model_args = array(
			'nopaging'		=> true,
			'post_parent'	=> $element_id,
			'post_type' 	=> $this->get_post_type(),
			'post_status'	=> 'inherit',
			'tax_query' => array(
				array(
					'taxonomy' => $this->attached_taxonomy_type,
					'field'    => 'slug',
					'terms'    => wp_parse_args( $current_element_type, array( 'printed' ) ),
					'operator' => 'AND',
				),
			),
			'date_query' => array(
				array(
					'year'  => $today['year'],
					'month' => $today['mon'],
					'day'   => $today['mday'],
				),
			),
		);
		$element_sheet_default_model = new WP_Query( $get_model_args );

		return ( $element_sheet_default_model->post_count + 1 );
	}

	/**
	 * Définition du modèle de document a utiliser pour un type de document donné / Define document model to use for a given document type
	 *
	 * @param unknown_type $model_path
	 * @param unknown_type $document_type
	 */
	function set_default_document( $file, $document_type ) {
		$the_file_content = @file_get_contents( $file );

		/**	Check if file is a valid one	*/
		if ( $the_file_content !== FALSE ) {
			$attachment_args = array();
			$wp_upload_dir = wp_upload_dir();

			/**	Start by coping document into wordpress uploads directory	*/
			$default_upload_directory = get_option( 'upload_path', '' );
			$default_upload_sub_directory_behavior = get_option( 'uploads_use_yearmonth_folders', '' );
			update_option( 'upload_path', str_replace( ABSPATH, '', $this->get_document_path() . '/document_models' ) );
			update_option( 'uploads_use_yearmonth_folders', false );
			$upload_result = wp_upload_bits( basename( $file ), null, file_get_contents( $file ) );
			update_option( 'upload_path' , $default_upload_directory );
			update_option( 'uploads_use_yearmonth_folders', true );

			$attachment_args[ 'post_author' ] = get_current_user_id();
			$attachment_args[ 'post_date' ] = current_time( 'mysql', 0 );
			/**	Get informations about the picture	*/
			$filetype = wp_check_filetype( basename( $upload_result[ 'file' ] ), null );
			/**	Set the default values for the current attachement	*/
			$attachment_default_args = array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $upload_result[ 'file' ] ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $upload_result[ 'file' ] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			/**	Save new picture into database	*/
			$attach_id = wp_insert_attachment( wp_parse_args( $attachment_args, $attachment_default_args ), $upload_result[ 'file' ] );

			/**	Create the different size for the given picture and get metadatas for this picture	*/
			$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_result[ 'file' ] );
			/**	Finaly save pictures metadata	*/
			wp_update_attachment_metadata( $attach_id, $attach_data );

			wp_set_object_terms( $attach_id, array( 'model', $document_type, 'default_model' ), 'attachment_category' );
		}

	}

	/**
	 * Create a zip file with a list of file given in parameter / Créé un fichier au format zip a partir d'une liste de fichiers passé en paramètres
	 *
	 * @param string $final_file_path The zip file path where to save it / Le chemin vers lequel il faut sauvegarder le fichier zip
	 * @param array $file_list The file list to add to the zip file / La liste des fichiers à ajouter au fichier zip
	 * @param Object $element The current element where to associate the zip file to / L'élément auquel il faut associer le fichier zip
	 */
	function create_zip( $final_file_path, $file_list, $element ) {
		$zip = new ZipArchive();

		$response = array();

		if( $zip->open( $final_file_path, ZipArchive::CREATE ) !== TRUE ) {
			$response['status'] = false;
			$response['message'] = __( 'An error occured while getting element to generate sheet for.', 'digirisk' );
		}

		if( !empty( $file_list ) ) {
			foreach( $file_list as $file ) {
				$zip->addFile( $file['link'], $file['filename'] );
			}
		}
		$zip->close();

		$document_creation_response = document_class::get()->create_document( $element, array( 'zip' ), $file_list );
		$document_creation_response = wp_parse_args( $document_creation_response, $response );
		if ( !empty( $document_creation_response[ 'id' ] ) ) {
			$element->option[ 'associated_document_id' ][ 'document' ][] = $document_creation_response[ 'id' ];
			group_class::get()->update( $element );
		}

		return $document_creation_response;
	}

	/**
	 * Create the document into database and call the generation function / Création du document dans la base de données puis appel de la fonction de génération du fichier
	 *
	 * @param Object $element The element to create the document for / L'élément pour lequel il faut créer le document
	 * @param array $document_type The document's categories / Les catégories auxquelles associer le document généré
	 * @param array $document_data Datas to write into the document template / Les données a écrire dans le modèle de document
	 *
	 * @return mixed The result of document creation / le résultat de la création du document
	 */
	function create_document( $element, $document_type, $document_data ) {
		$response = array(
			'status' => true,
		);

		/**	Définition du modèle de document a utiliser pour l'impression / Define the document model to use for print sheet */
		$model_to_use = null;
		$model_response = $this->get_model_for_element( $document_type );
		$model_to_use = $model_response[ 'model_path' ];

  	/**	Définition de la révision du document / Define the document version	*/
  	$document_revision = $this->get_document_type_next_revision( $document_type, $element->id );

  	/**	Définition de la partie principale du nom de fichier / Define the main part of file name	*/
  	$main_title_part = $element->title;
  	if ( !empty( $document_type ) && is_array( $document_type ) ) {
  		$main_title_part = $main_title_part . '_' . $document_type[ 0 ];
  	}

  	/**	Enregistrement de la fiche dans la base de donnée / Save sheet into database	*/
  	$response[ 'filename' ] = mysql2date( 'Ymd', current_time( 'mysql', 0 ) ) . '_' . $element->option[ 'unique_identifier' ] . '_' . sanitize_title( str_replace( ' ', '_', $main_title_part ) ) . '_V' . $document_revision . '.odt';
  	$document_args = array(
			'post_content'	=> '',
			'post_status'	=> 'inherit',
			'post_author'	=> get_current_user_id(),
			'post_date'		=> current_time( 'mysql', 0 ),
			'post_title'	=> basename( $response[ 'filename' ], '.odt' ),
  	);

  	/**	On créé le document / Create the document	*/
  	$filetype = 'unknown';
		$path = '';

  	if ( null !== $model_to_use ) {
			$path = $element->type. '/' . $element->id . '/' . $response[ 'filename' ];
  		$document_creation = $this->generate_document( $model_to_use, $document_data, $path );

  		if ( !empty( $document_creation ) && !empty( $document_creation[ 'status' ] ) && !empty( $document_creation[ 'link' ] ) ) {
  			$filetype = wp_check_filetype( $document_creation[ 'link' ], null );
  			$response[ 'link' ] = $document_creation[ 'link' ];
  			$response[ 'message' ] = __( 'The sheet have been generated successfully. Please find it below', 'digirisk' );
  		}
  		else {
  			$response = wp_parse_args( $document_creation, $response );
  		}
  	}
  	else {
  		$response[ 'status' ] = false;
  		$response[ 'message' ] = $model_response[ 'message' ];
  	}

		$response[ 'id' ] = wp_insert_attachment( $document_args, $this->get_document_path() . '/' . $path, $element->id );

		$attach_data = wp_generate_attachment_metadata( $response['id'], $this->get_document_path() . '/' . $path );
		wp_update_attachment_metadata( $response['id'], $attach_data );

		wp_set_object_terms( $response[ 'id' ], wp_parse_args( $document_type, array( 'printed', ) ), $this->attached_taxonomy_type );

  	/**	On met à jour les informations concernant le document dans la base de données / Update data for document into database	*/
  	$next_document_key = ( wpdigi_utils::get_last_unique_key( 'post', $this->get_post_type() ) + 1 );
  	$document_args = array(
			'id'			=> $response[ 'id' ],
			'status'    	=> 'inherit',
			'author_id'		=> get_current_user_id(),
			'date'			=> current_time( 'mysql', 0 ),
			'mime_type'		=> !empty( $filetype[ 'type' ] ) ? $filetype['type'] : $filetype,
			'option'			=> array (
				'unique_key'		=> $next_document_key,
				'unique_identifier' => $this->element_prefix . $next_document_key,
				'model_id' 			=> $model_to_use,
				'document_meta' 	=> json_encode( $document_data ),
				'version'			=> '',
			),
  	);
  	$document = $this->update( $document_args );
  	$document = $this->show( $response[ 'id' ] );

  	$document_full_path = null;
  	if ( is_file( $this->get_document_path( 'basedir' ) . '/' . $element->type . '/' . $element->id . '/' . $document->title . '.odt' ) ) {
  		$document_full_path = $this->get_document_path( 'baseurl' ) . '/' . $element->type . '/' . $element->id . '/' . $document->title . '.odt';
  	}

  	ob_start();
  	require( wpdigi_utils::get_template_part( WPDIGI_DOC_DIR, WPDIGI_DOC_TEMPLATES_MAIN_DIR, 'common', 'printed-list', 'item' ) );
  	$response[ 'output' ] = ob_get_contents();
  	ob_end_clean();

  	return $response;
	}

}

document_class::get();