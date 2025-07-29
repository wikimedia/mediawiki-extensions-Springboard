<?php

namespace MediaWiki\Extension\Springboard;

use Symfony\Component\Yaml\Yaml;

class SpringboardUtils {
	/**
	 * Fetch and parse YAML block.
	 *
	 * @param array $configURL
	 * @return array
	 */
	public static function fetchRecommendedPage( $configURL ) {
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
			return [ 'extensions' => [], 'skins' => [] ];
		}

		if ( preg_match( '/<syntaxhighlight\s+lang=["\']yaml["\']>(.*?)<\/syntaxhighlight>/si', $wikitext, $matches ) ) {
			// Decode HTML entities if syntaxhighlight is found
			$yamlText = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5 );
		} else {
			$yamlText = trim( $wikitext );
		}

		try {
			$parsed = Yaml::parse( $yamlText );
			return $parsed ?? [ 'extensions' => [], 'skins' => [] ];
		} catch ( \Symfony\Component\Yaml\Exception\ParseException $e ) {
			return [ 'extensions' => [], 'skins' => [] ];
		}
	}

	/**
	 * Extract extensions/skins names from YAML extract
	 *
	 * @param array $recs
	 * @return array
	 */
	public static function extractNames( $recs ) {
		$names = [];
		foreach ( $recs as $rec ) {
			$names[] = array_keys( $rec )[0];
		}
		return $names;
	}
}
