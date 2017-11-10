<?php
/**
 * Classe héritant de Document_Class. Permet de définir les attributs "protected".
 *
 * @author Jimmy Latour <jimmy@evarisk.com>
 * @since 6.2.0
 * @version 6.4.0
 * @copyright 2015-2017 Evarisk
 * @package DigiRisk
 */

namespace digi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe héritant de Document_Class. Permet de définir les attributs "protected".
 */
class Diffusion_Informations_A3_Class extends Document_Class {
	/**
	 * Le nom du modèle
	 *
	 * @var string
	 */
	protected $model_name = '\digi\diffusion_informations_a3_model';

	/**
	 * Le post type
	 *
	 * @var string
	 */
	protected $post_type = 'diffusion_info_A3';

	/**
	 * Le type du document
	 *
	 * @var string
	 */
	public $attached_taxonomy_type = 'attachment_category';

	/**
	 * La clé principale du modèle
	 *
	 * @var string
	 */
	protected $meta_key = '_wpdigi_document';

	/**
	 * La route pour accéder à l'objet dans la rest API
	 *
	 * @var string
	 */
	protected $base = 'diffusion_informations_a3';

	/**
	 * La version de l'objet
	 *
	 * @var string
	 */
	protected $version = '0.1';

	/**
	 * Le préfixe de l'objet dans DigiRisk
	 *
	 * @var string
	 */
	public $element_prefix = 'DI-A3-';

	/**
	 * La fonction appelée automatiquement avant la création de l'objet dans la base de donnée
	 *
	 * @var array
	 */
	protected $before_put_function = array( '\digi\construct_identifier' );

	/**
	 * La fonction appelée automatiquement après la récupération de l'objet dans la base de donnée
	 *
	 * @var array
	 */
	protected $after_get_function = array( '\digi\get_identifier' );

	/**
	 * Le nom pour le resgister post type
	 *
	 * @var string
	 */
	protected $post_type_name = 'Diffusion Informations A3';

	/**
	 * Le nom de l'ODT sans l'extension; exemple: document_unique
	 *
	 * @var string
	 */
	protected $odt_name = 'diffusion_informations_A3';
}

Diffusion_Informations_A3_Class::g();
