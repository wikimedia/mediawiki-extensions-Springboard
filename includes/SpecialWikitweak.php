<?php
/**
 * Special page for Wikitweak extension.
 *
 * @author  Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author  Naresh Kumar Babu <nk2indian@gmail.com>
 * @author  Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author  Yaron Koren <yaron57@gmail.com>
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\Wikitweak;

use Html;
use PermissionsError;
use SpecialPage;
use Symfony\Component\Yaml\Yaml;

class SpecialWikitweak extends SpecialPage {
	/**
	 * @var string
	 */
	private $mwVersion;

	public function __construct() {
		parent::__construct( 'Wikitweak', 'wikitweak' );
		$version = explode( '.', $this->getConfig()->get( 'Version' ) );
		$this->mwVersion = "REL$version[0]_$version[1]";
	}

	public function execute( $query ) {
		$user = $this->getUser();
		if ( !$user->isAllowed( 'wikitweak' ) ) {
			throw new PermissionsError( 'wikitweak' );
		}

		$out = $this->getOutput();
		
		$recs = $this->fetchRecommendedPage();


		$out->addJsConfigVars( 'WTExtensions', $recs[ 'extensions' ] );
		$out->addJsConfigVars( 'WTSkins', $recs[ 'skins' ] );
		$out->addModules( [ 'ext.Wikitweak' ] );

		$out->addHTML( 
			Html::rawElement( 'div', [
				'id' => 'wikitweak-vue-root'
			] )
		);
	}

	/**
	 * Fetch and parse YAML block.
	 *
	 * @return array
	 */
	private function fetchRecommendedPage() {
		$configURL = $this->getConfig()->get( 'WTDistributionListURL' );
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
