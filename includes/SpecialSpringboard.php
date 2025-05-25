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
use Symfony\Component\Yaml\Yaml;

class SpecialSpringboard extends SpecialPage {

	private $loaderFile = __DIR__ . '/CustomLoader.php';

	/**
	 * @var string
	 */
	private $mwVersion;

	public function __construct() {
		parent::__construct( 'Springboard', 'springboard' );
		$version = explode( '.', $this->getConfig()->get( 'Version' ) );
		$this->mwVersion = "REL$version[0]_$version[1]";
	}

	public function execute( $query ) {
		$this->setHeaders();
		$user = $this->getUser();
		if ( !$user->isAllowed( 'springboard' ) ) {
			throw new PermissionsError( 'springboard' );
		}

		$out = $this->getOutput();

		$recs = $this->fetchRecommendedPage();

		if ( isset( $recs['extensions'] ) ) {
			$recs['extensions'] = $this->addFlagsToItems( $recs['extensions'], 'extension' );
		}

		if ( isset( $recs['skins'] ) ) {
			$recs['skins'] = $this->addFlagsToItems( $recs['skins'], 'skin' );
		}

		$out->addJsConfigVars( 'SpringboardExtensions', $recs[ 'extensions' ] );
		$out->addJsConfigVars( 'SprinbgoardSkins', $recs[ 'skins' ] );
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
			FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), fn( $l ) => trim( $l ) !== '<?php' )
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

	/**
	 * Fetch and parse YAML block.
	 *
	 * @return array
	 */
	private function fetchRecommendedPage() {
		$configURL = $this->getConfig()->get( 'wgSpringboardURL' );
		if ( is_array( $configURL ) ) {
			$wikitext = false;
			// Get e.g. "1.23" from "1.23.4-alpha"
			preg_match( "/^\d\.\d+/", MW_VERSION, $match );
			$currentVersion = $match[0];
			if ( array_key_exists( $currentVersion, $configURL ) ) {
				$wikitext = file_get_contents( $configURL[$currentVersion] );
			}
		} else {
			$wikitext = file_get_contents( $configURL );
		}
		if ( $wikitext === false ) {
			return [ 'extension' => [], 'skin' => [] ];
		}

		if ( !preg_match( '/<syntaxhighlight\s+lang=["\']yaml["\']>(.*?)<\/syntaxhighlight>/si', $wikitext, $matches ) ) {
			return [ 'extension' => [], 'skin' => [] ];
		}

		$yamlText = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5 );
		try {
			$parsed = Yaml::parse( $yamlText );
			return $parsed ?? [ 'extension' => [], 'skin' => [] ];
		} catch ( \Symfony\Component\Yaml\Exception\ParseException $e ) {
			return [ 'extension' => [], 'skin' => [] ];
		}
	}
}
