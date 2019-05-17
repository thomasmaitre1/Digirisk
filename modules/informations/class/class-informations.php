<?php
/**
 * Appelle la vue pour afficher le formulaire de configuration d'une société
 *
 * @author Evarisk <dev@evarisk.com>
 * @since 6.2.1
 * @copyright 2015-2018 Evarisk
 * @package DigiRisk
 */

namespace digi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appelle la vue pour afficher le formulaire de configuration d'une société
 */
class Informations_Class extends \eoxia\Singleton_Util {

	/**
	 * Le constructeur
	 */
	protected function construct() {}

	/**
	 * Charges le responsable et l'addresse du groupement.
	 * Envois les données à la vue group/configuration-form.view.php
	 *
	 * @param  Group_Model $element L'objet groupement.
	 *
	 * @since 7.2.0
	 */
	public function display( $element ) {
		$duers        = DUER_Class::g()->get( array( 'posts_per_page' => 2 ), true );
		$current_duer = is_array( $duers ) ? $duers[0] : $duers;
		$old_duer     = is_array( $duers ) ? $duers[1] : null;

		$accident    = Accident_Class::g()->get( array( 'posts_per_page' => 1 ), true );

		$count_users = count_users();

		$historic_update = get_post_meta( $element->data['id'], \eoxia\Config_Util::$init['digirisk']->historic->key_historic, true );

		if ( empty( $historic_update ) ) {
			$historic_update = array(
				'date'    => 'Indisponible',
				'content' => 'Indisponible',
			);
		} else {
			$historic_update['date'] = \eoxia\Date_Util::g()->fill_date( $historic_update['date'] );
			$historic_update['parent'] = Society_Class::g()->show_by_type( $historic_update['parent_id'] );
		}

		$evaluator_ids = array();

		$groups          = Group_Class::g()->get( array(
			'posts_per_page' => -1,
		) );
		$groups          = array_merge( $groups, Workunit_Class::g()->get( array(
			'posts_per_page' => -1,
		) ) );

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				if ( ! empty( $group->data['user_info']['affected_id']['evaluator'] ) ) {
					foreach ( $group->data['user_info']['affected_id']['evaluator'] as $user_affected_id => $affected_info ) {
						if ( ! in_array( $user_affected_id, $evaluator_ids, true ) ) {
							$evaluator_ids[] = $user_affected_id;
						}
					}
				}
			}
		}

		$number_evaluator = count( $evaluator_ids );

		$total_users = $count_users['total_users'] - 1;
		$average = 'N/A';

		if ( $total_users != 0 ) {
			$average = round( $number_evaluator / $total_users * 100 );
			$average .= '%';
		}

		$diff_info = array(
			'total_risk'      => 'N/A',
			'quotation_total' => 'N/A',
			'average'         => 'N/A',
		);

		$current_duer_info = $this->duer_info( $current_duer->data );
		$old_duer_info     = $this->duer_info( array() );
		if ( ! empty( $old_duer ) ) {
			$old_duer_info = $this->duer_info( $old_duer->data );

			$diff_info['total_risk']      = $current_duer_info['total_risk'] - $old_duer_info['total_risk'];
			$diff_info['quotation_total'] = $current_duer_info['quotation_total'] - $old_duer_info['quotation_total'];
			$diff_info['average']         = $current_duer_info['average'] - $old_duer_info['average'];

			$diff_info['number_risk'] = array(
				1 => $current_duer_info['number_risk'][1] - $old_duer_info['number_risk'][1],
				2 => $current_duer_info['number_risk'][2] - $old_duer_info['number_risk'][2],
				3 => $current_duer_info['number_risk'][3] - $old_duer_info['number_risk'][3],
				4 => $current_duer_info['number_risk'][4] - $old_duer_info['number_risk'][4],
			);
		}

		$risks_categories = Risk_Category_Class::g()->get( array(
			'meta_key' => '_position',
			'orderby'  => 'meta_value_num',
		) );

		if ( ! empty( $risks_categories ) ) {
			foreach ( $risks_categories as &$risk_category ) {
				for ( $i = 1; $i < 5; $i++ ) {
					$risk_category->data['level' . $i ] = 0;

					if ( ! empty( $current_duer->data['document_meta'][ 'risq' . $i ]['value'] ) ) {
						foreach ( $current_duer->data['document_meta'][ 'risq' . $i ]['value'] as $risk ) {
							if ( $risk_category->data['name'] === $risk['nomDanger'] ) {
								$risk_category->data['level' . $i ]++;
							}
						}
					}
				}
			}
		}

		unset( $risk_category );

		\eoxia\View_Util::exec( 'digirisk', 'informations', 'main', array(
			'current_duer'      => $current_duer,
			'current_duer_info' => $current_duer_info,
			'old_duer_info'     => $old_duer_info,
			'accident'          => $accident,
			'count_users'       => $count_users,
			'historic_update'   => $historic_update,
			'number_evaluator'  => count( $evaluator_ids ),
			'average'           => $average,
			'total_users'       => $total_users,
			'diff_info'         => $diff_info,
			'risks_categories'  => $risks_categories,
		) );
	}

	public function duer_info( $duer ) {
		$total_risk      = 'N/A';
		$quotation_total = 'N/A';
		$average         = 'N/A';
		$number_risk = array( 1 => 'N/A', 2 => 'N/A', 3 => 'N/A', 4 => 'N/A' );

		if ( ! empty( $duer['document_meta'] ) ) {
			$total_risk      = 0;
			$quotation_total = 0;
			$average         = 0;
			$number_risk     = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0 );
			$risk_level      = array( 1, 2, 3, 4 );

			if ( ! empty( $risk_level ) ) {
				foreach ( $risk_level as $level ) {
					$number_risk[ $level ] = count( $duer['document_meta'][ 'risq' . $level ]['value'] );
					$total_risk += count( $duer['document_meta'][ 'risq' . $level ]['value'] );
				}
			}

			if ( ! empty( $duer['document_meta']['risqueFiche']['value'] ) ) {
				foreach ( $duer['document_meta']['risqueFiche']['value'] as $element ) {
					$quotation_total += $element['quotationTotale'];
				}
			}

			$average = $quotation_total / $total_risk;
		}

		return array(
			'total_risk'      => $total_risk,
			'quotation_total' => $quotation_total,
			'average'         => $average,
			'number_risk'     => $number_risk,
		);
	}
}

Informations_Class::g();
