<?php if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Fichier du controlleur principal de l'extension digirisk pour wordpress / Main controller file for digirisk plugin
 *
 * @author Evarisk development team <dev@evarisk.com>
 * @version 6.0
 */

/**
 * Classe du controlleur principal de l'extension digirisk pour wordpress / Main controller class for digirisk plugin
 *
 * @author Evarisk development team <dev@evarisk.com>
 * @version 6.0
 */
class group_duer_class extends singleton_util {
	/**
	* Le constructeur
	*/
	protected function construct() {}

	/**
	* Génère le DUER
	*
	* @param array $data Les data à mettre dans le ODT
	*/
	public function generate( $data ) {
		if ( empty( $data ) || empty( $data['element_id'] ) ) {
			return false;
		}

		$id = (int) $data['element_id'];
		$element = group_class::get()->show( $id );

		/**	Définition des composants du fichier / Define the file component	*/
		$src_logo = $this->get_logo();
		$data['wpdigi_duer'] = $this->securize_duer_data( $data );
		$data_to_document = $this->prepare_skeleton();
		$data_to_document = $this->fill_data_duer( $data['wpdigi_duer'], $data_to_document, $element );
		$data_to_document = $this->fill_data_risk( $data_to_document, $element );

		/**	Possibilité de filtrer les données envoyées au document pour ajout, suppression, traitement supplémentaire / Add capability to filter datas sended to the document for addition, deletion or other treatment	*/
		$data_to_document = apply_filters( 'wpdigi_element_duer_details', $data_to_document );

		/**	Call document creation function / Appel de la fonction de création du document	*/
		$document_creation_response = document_class::get()->create_document( $element, array( 'document_unique' ), $data_to_document );

		if ( !empty( $document_creation_response[ 'id' ] ) ) {
			$element->option[ 'associated_document_id' ][ 'document' ][] = $document_creation_response[ 'id' ];
			group_class::get()->update( $element );
		}

		$all_file = $this->generate_child( $element );
		$all_file[] = $document_creation_response;

		$element = group_class::get()->show( $element->id );

		/**	Generate a zip file with all sheet for current group, sub groups, and sub work units / Génération du fichier zip contenant les fiches du groupement actuel, des sous groupements et des unités de travail	*/
		$version = document_class::get()->get_document_type_next_revision( array( 'zip' ), $element->id );
		$zip_generation_result = document_class::get()->create_zip( document_class::get()->get_digirisk_dir_path() . '/' . $element->type . '/' . $element->id . '/' . mysql2date( 'Ymd', current_time( 'mysql', 0 ) ) . '_' . $element->option[ 'unique_identifier' ] . '_' . sanitize_title( str_replace( ' ', '_', $element->title ) ) . '_zip_V' . $version . '.zip', $all_file, $element, $version );

		return true;
	}

	/**
	* Securises toutes les données
	*
	* @param array $data Les données à sécuriser
	*
	* @return array Les données sécurisées
	*/
	public function securize_duer_data( $data ) {
		$data_duer = !empty( $data['wpdigi_duer'] ) ? (array) $data['wpdigi_duer'] : array();

		$data_duer['company_name'] 				= !empty( $data_duer['company_name'] ) ? sanitize_text_field( $data_duer['company_name'] ) : '';
		$data_duer['date_audit'] 					= $this->formatte_audit_date( $data_duer );
		$data_duer['emetteurDUER'] 				= !empty( $data_duer['document_transmitter'] ) ? sanitize_text_field( $data_duer['document_transmitter'] ) : '';
		$data_duer['destinataireDUER'] 		= !empty( $data_duer['document_recipient'] ) ? sanitize_text_field( $data_duer['document_recipient'] ) : '';
		$data_duer['telephone'] 					= !empty( $data_duer['document_recipient_telephone'] ) ? sanitize_text_field( $data_duer['document_recipient_telephone'] ) : '';
		$data_duer['portable'] 						= !empty( $data_duer['document_recipient_cellphone'] ) ? sanitize_text_field( $data_duer['document_recipient_cellphone'] ) : '';

		$data_duer['methodologie'] 				= !empty( $data_duer['audit_methodology'] ) ? sanitize_text_field( $data_duer['audit_methodology'] ) : '';
		$data_duer['sources'] 						= !empty( $data_duer['audit_sources'] ) ? sanitize_text_field( $data_duer['audit_sources'] ) : '';
		$data_duer['remarqueImportante'] 	= !empty( $data_duer['audit_important_note'] ) ? sanitize_text_field( $data_duer['audit_important_note'] ) : '';
		$data_duer['dispoDesPlans'] 			= !empty( $data_duer['audit_location'] ) ? sanitize_text_field( $data_duer['audit_location'] ) : '';

		return $data_duer;
	}

	/**
	* Prépares un squelette des données
	*
	* @return array Le squelette des données
	*/
	public function prepare_skeleton() {
		/**	Définition de la structure des données du document par défaut / Define the default data structure for document	*/
		$skeleton = array(
			'identifiantElement'	=> '',
			'nomEntreprise'				=> '',
			'dateAudit'						=> '',
			'emetteurDUER'				=> '',
			'destinataireDUER'		=> '',
			'dateGeneration'			=> '',
			'telephone'						=> '',
			'portable'						=> '',

			'methodologie'				=> '',
			'sources'							=> '',
			'remarqueImportante'	=> '',
			'dispoDesPlans'				=> '',

			'elementParHierarchie' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),

			'risq' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),

			'risqueFiche' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),

			'risqPA' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),

			'planDactionRisq' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),

			'planDaction' => array(
				'type'	=> 'segment',
				'value'	=> array(),
			),
		);

		$level_list = array( 48, 51, 80, );
		foreach ( $level_list as $level ) {
			$skeleton[ 'risq' . $level ] = array(
				'type'	=> 'segment',
				'value'	=> array(),
			);
			$skeleton[ 'risqPA' . $level ] = array(
				'type'	=> 'segment',
				'value'	=> array(),
			);
			$skeleton[ 'planDactionRisq' . $level ] = array(
				'type'	=> 'segment',
				'value'	=> array(),
			);
		}

		return $skeleton;
	}

	/**
	* Récupères le logo: todo: Pas utiliser
	*
	* @return string Le chemin vers le logo
	*/
	public function get_logo() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$src_logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
		return $src_logo;
	}

	/**
	* Remplis les données du duer
	*
	* @param array $data_duer Les données sécurisées
	* @param array $data_to_document Les données qui seront insérées dans le document
	* @param object $element L'objet groupement
	*
	* @return array Les données qui seront insérées dans le document
	*/
	public function fill_data_duer( $data_duer, $data_to_document, $element ) {
		if ( !is_array( $data_duer ) || !is_array( $data_to_document ) || !is_object( $element ) ) {
			return false;
		}

		$data_to_document = array_merge( $data_to_document, $data_duer );
		$data_to_document['identifiantElement'] = $element->option['unique_identifier'];
		$data_to_document['dateAudit'] = $this->formatte_audit_date( $data_duer );
		$data_to_document['dateGeneration'] = mysql2date( get_option( 'date_format' ), current_time( 'mysql', 0 ), true );
		return $data_to_document;
	}

	/**
	* Remplis les données du duer des risques
	*
	* @param array $data_to_document Les données qui seront insérées dans le document
	* @param object $element L'objet groupement
	*
	* @return array Les données qui seront insérées dans le document
	*/
	public function fill_data_risk( $data_to_document, $element ) {
		$list_risk = group_class::get()->get_element_tree_risk( $element );
		$risk_per_element = array();

		if ( !empty( $list_risk ) ) {
		  foreach ( $list_risk as $element ) {
				$final_level = evaluation_method_class::get()->list_scale[$element[ 'niveauRisque' ]];
				$data_to_document[ 'risq' . $final_level ][ 'value' ][] = $element;
				$data_to_document[ 'risqPA' . $final_level ][ 'value' ][] = $element;
				$data_to_document[ 'planDactionRisq' . $final_level ][ 'value' ][] = $element;

				if ( !isset( $risk_per_element[ $element[ 'idElement' ] ] ) ) {
					$risk_per_element[ $element[ 'idElement' ] ][ 'quotationTotale' ] = 0;
				}
				$risk_per_element[ $element[ 'idElement' ] ][ 'quotationTotale' ] += $element[ 'quotationRisque' ];
		  }
		}

		return $data_to_document;
	}

	/**
	* Formattes la date de l'audit
	*
	* @param array $data_duer Les données sécurisées
	*
	* @return string La date de l'audit formatté
	*/
	public function formatte_audit_date( $data_duer ) {
		$audit_date = '';

		if ( !empty( $data_duer['audit_start_date' ] ) ) {
			$audit_date .= sanitize_text_field( $data_duer['audit_start_date'] );
		}

		if ( !empty( $data_duer['audit_end_date'] ) && $audit_date != $data_duer['audit_end_date'] ) {
			if ( !empty( $audit_date ) ) {
				$audit_date .= ' - ';
			}

			$audit_date .= sanitize_text_field( $data_duer['audit_end_date'] );
		}

		return $audit_date;
	}

	/**
	* Génère les ODT enfants de ce DUER
	*
	* @param object $element L'élement groupement
	*
	* @return array La liste des ODT enfants
	*/
	public function generate_child( $element ) {
		// if ( !is_object( $element ) ) {
		// 	return false;
		// }
		// Generate children
		$list_id = array();

		/**	Build a file list to set into the final zip / Contruit la liste des fichiers a ajouter dans le zip lorsque les générations sont terminées	*/
		$response = array();
		$response[] = sheet_groupment_class::get()->generate_sheet( $element->id );

		/**	Get workunit list for the current group / Récupération de la liste des unités de travail pour le groupement actuel	*/
		$work_unit_list = workunit_class::get()->index( array( 'posts_per_page' => -1, 'post_parent' => $element->id, 'post_status' => array( 'publish', 'draft', ), ), false );
		foreach( $work_unit_list as $workunit ) {
			$response[] = workunit_class::get()->generate_workunit_sheet( $workunit->id );
		}

		$list_id = group_class::get()->get_element_sub_tree_id( $element->id, $list_id );
		if ( !empty( $list_id ) ) {
			foreach( $list_id as $element ) {
				if( !empty( $element['workunit'] ) ) {
					if( !empty( $element['id'] ) ) {
						$response[] = sheet_groupment_class::get()->generate_sheet( $element['id'] );
					}
					foreach( $element['workunit'] as $workunit_id ) {
						$response[] = workunit_class::get()->generate_workunit_sheet( $workunit_id['id'] );
					}
				}
				else {
					if( !empty( $element['id'] ) ) {
						$response[] = sheet_groupment_class::get()->generate_sheet( $element['id'] );
					}
				}
			}
		}

		return $response;
	}
}
