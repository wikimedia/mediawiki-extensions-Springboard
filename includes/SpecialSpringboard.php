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
	 * Add metadata flags to each extension/skin item.
	 *
	 * Flags include:
	 * - installed: whether the extension/skin is installed
	 * - enabled: whether it is currently loaded
	 * - disabled: whether it is installed but not included in Springboard loader
	 *
	 * @param array $items
	 * @param string $type
	 *
	 * @return array
	 */
	private function addFlagsToItems( $items, $type ) {
		// Get the full list of currently loaded extensions/skins
		$loadedComponents = ExtensionRegistry::getInstance()->getAllThings();
		$extensionRoot = dirname( __DIR__, 1 );
		$loadFunction = $type === 'extension' ? 'wfLoadExtension' : 'wfLoadSkin';
		$componentDir = $type === 'extension' ? 'extensions' : 'skins';
		$loaderFileLines = file_exists( $this->loaderFile )
			? array_filter(
				file( $this->loaderFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ),
				static fn ( $line ) => trim( $line ) !== '<?php'
			)
			: [];

		foreach ( $items as $index => $entry ) {
			foreach ( $entry as $name => $metadata ) {
				// The expected line in loader file
				$expectedLoaderLine = "$loadFunction( '$name', '$extensionRoot/$componentDir/$name/$type.json' );";
				// Check if extension/skin is loaded in loader file
				$isInLoaderFile = in_array( $expectedLoaderLine, $loaderFileLines );
				// Check if extension/skin exists
				$isInstalled = is_file( "$extensionRoot/$componentDir/$name/$type.json" );
				$metadata['installed'] = $isInstalled;
				// Check if extension/skin is currently loaded
				$isLoaded = $this->isPresent( $name, $loadedComponents );
				$metadata['enabled'] = $isLoaded;
				// Mark disabled if not loaded via Springboard
				if ( $isLoaded && !$isInLoaderFile ) {
					$metadata['disabled'] = true;
				}
				$items[$index] = [ $name => $metadata ];
			}
		}

		return $items;
	}

}
