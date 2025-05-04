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

include "CustomLoader.php";

class SpecialSpringboard extends SpecialPage {
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
		$user = $this->getUser();
		if ( !$user->isAllowed( 'springboard' ) ) {
			throw new PermissionsError( 'springboard' );
		}

		$out = $this->getOutput();

		$recs = $this->fetchRecommendedPage();

		if ( isset( $recs['extensions'] ) ) {
			$recs['extensions'] = $this->addExistsFlagToItems( $recs['extensions'] );
		}

		if ( isset( $recs['skins'] ) ) {
			$recs['skins'] = $this->addExistsFlagToItems( $recs['skins'] );
		}

		$out->addJsConfigVars( 'WTExtensions', $recs[ 'extensions' ] );
		$out->addJsConfigVars( 'WTSkins', $recs[ 'skins' ] );
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

	private function addExistsFlagToItems( $items ) {
		$loadedList = ExtensionRegistry::getInstance()->getAllThings();
		// var_dump($loadedList);
		foreach ( $items as $i => $entry ) {
			foreach ( $entry as $name => $metadata ) {
				$metadata['exists'] = $this->isPresent( $name, $loadedList );
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
		$configURL = $this->getConfig()->get( 'SBDistributionListURL' );
		$wikitext = file_get_contents( $configURL . '?action=raw' );
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
