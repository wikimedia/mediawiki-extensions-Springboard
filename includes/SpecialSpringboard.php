<?php
/**
 * Special page for Springboard extension.
 *
 * @author  Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author  Naresh Kumar Babu <nk2indian@gmail.com>
 * @author  Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author  Yaron Koren <yaron57@gmail.com>
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\Springboard;

use ExtensionRegistry;
use MediaWiki\Html\Html;
use PermissionsError;
use SpecialPage;

class SpecialSpringboard extends SpecialPage {

	private $loaderFile = __DIR__ . '/CustomLoader.php';

	public function __construct() {
		parent::__construct( 'Springboard', 'springboard' );
	}

	public function execute( $query ) {
		$this->setHeaders();
		$user = $this->getUser();
		if ( !$user->isAllowed( 'springboard' ) ) {
			throw new PermissionsError( 'springboard' );
		}

		$out = $this->getOutput();

		$configURL = $this->getConfig()->get( 'SpringboardURL' );
		$recs = SpringboardUtils::fetchRecommendedPage( $configURL );

		if ( isset( $recs['extensions'] ) ) {
			$recs['extensions'] = $this->addFlagsToItems( $recs['extensions'], 'extension' );
		}

		if ( isset( $recs['skins'] ) ) {
			$recs['skins'] = $this->addFlagsToItems( $recs['skins'], 'skin' );
		}

		$out->addJsConfigVars( 'SpringboardExtensions', $recs[ 'extensions' ] );
		$out->addJsConfigVars( 'SpringboardSkins', $recs[ 'skins' ] );
		$out->addModules( [ 'ext.Springboard' ] );

		$out->addHTML(
			Html::rawElement( 'div', [
				'id' => 'springboard-vue-root'
			] )
		);
	}

	private function isPresent( string $name, array $loadedList ) {
		$normalizedTarget = strtolower( str_replace( ' ', '', $name ) );

		// var_dump($normalizedTarget);

		foreach ( $loadedList as $key => $info ) {
			$normalizedKey = strtolower( str_replace( ' ', '', $key ) );
			if ( $normalizedKey === $normalizedTarget ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add exists flag for installed items and disabled flag for items installed without Springboard
	 *
	 * @param array $items
	 * @param string $type
	 *
	 * @return array
	 */
	private function addFlagsToItems( $items, $type ) {
		$loadedList = ExtensionRegistry::getInstance()->getAllThings();
		$extensionRoot = dirname( __DIR__, 1 );
		$func = $type === 'extension' ? 'wfLoadExtension' : 'wfLoadSkin';
		$endPath = $type === 'extension' ? 'extensions' : 'skins';
		$lines = file_exists( $this->loaderFile )
			? array_filter( file( $this->loaderFile,
			FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), static fn ( $l ) => trim( $l ) !== '<?php' )
			: [];

		foreach ( $items as $i => $entry ) {
			foreach ( $entry as $name => $metadata ) {
				$line = "$func( '$name', '$extensionRoot/$endPath/$name/$type.json' );";
				$inLoader = in_array( $line, $lines );
				$isInstalled = $this->isPresent( $name, $loadedList );
				$metadata['exists'] = $isInstalled;
				if ( $isInstalled && !$inLoader ) {
					$metadata['disabled'] = true;
				}
				$items[$i] = [ $name => $metadata ];
			}
		}
		return $items;
	}
}
