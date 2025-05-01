<?php
/**
 * Installer class for the Wikitweak extension.
 *
 * Provides functionality to clone, enable, and update MediaWiki
 * extensions and skins.
 *
 * @author  Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author  Naresh Kumar Babu <nk2indian@gmail.com>
 * @author  Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author  Yaron Koren <yaron57@gmail.com>
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\Wikitweak;

class Installer {

	/**
	 * Install an extension from a Git repository.
	 *
	 * @param string $name Extension name.
	 * @param string $repository Git repository URL.
	 * @param string $branch Git branch name. Defaults to 'master'.
	 * @param string $commit Specific commit hash to checkout. Defaults to '' (HEAD).
	 *
	 * @return array [bool success, string message]
	 */
	public static function installExtension( $name, $repository, $branch = 'master', $commit = '' ) {
		return self::install( 'extensions', $name, $repository, $branch, $commit );
	}

	/**
	 * Install a skin from a Git repository.
	 *
	 * @param string $name Skin name.
	 * @param string $repository Git repository URL.
	 * @param string $branch Git branch name. Defaults to 'master'.
	 * @param string $commit Specific commit hash to checkout. Defaults to '' (HEAD).
	 *
	 * @return array [bool success, string message]
	 */
	public static function installSkin( $name, $repository, $branch = 'master', $commit = '' ) {
		return self::install( 'skins', $name, $repository, $branch, $commit );
	}

	/**
	 * Shared install logic for extensions and skins.
	 *
	 * @param string $type 'extensions' or 'skins'.
	 * @param string $name Name of extension/skin.
	 * @param string $repository Git repository URL.
	 * @param string $branch Git branch name.
	 * @param string $commit Specific commit hash to checkout.
	 *
	 * @return array [bool success, string message]
	 */
	protected static function install( $type, $name, $repository, $branch, $commit ) {
		$basePath = dirname( __DIR__, 3 ) . '/' . $type . '/' . $name;

		if ( is_dir( $basePath ) ) {
			return [ false, ucfirst( rtrim( $type, 's' ) ) . " '$name' already installed." ];
		}

		$gitCmd = "git clone --branch " . $branch . ' '
			. $repository . ' '
			. $basePath;

		exec( $gitCmd, $output, $status );

		if ( $status !== 0 ) {
			return [ false, implode( "\n", $output ) ];
		}

		if ( $commit && $commit !== 'HEAD' ) {
			exec(
				'cd ' . $basePath . ' && git checkout ' . $commit
			);
		}

		return [ true, ucfirst( rtrim( $type, 's' ) ) . " '$name' installed successfully." ];
	}

	/**
	 * Enable an extension in LocalSettings.php.
	 *
	 * If the line is commented (with // or #), it will be uncommented.
	 * If missing, it will be appended.
	 *
	 * @param string $name Extension name.
	 *
	 * @return string Status message.
	 */
	public static function smartEnableExtension( $name ) {
		$localSettingsPath = dirname( __DIR__, 3 ) . '/LocalSettings.php';
		if ( !file_exists( $localSettingsPath ) ) {
			return "LocalSettings.php not found.";
		}

		$contents = file_get_contents( $localSettingsPath );
		$patternCommented = "/^( *)(\/\/|#)\s*wfLoadExtension\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;/m";

		if ( preg_match( $patternCommented, $contents, $matches ) ) {
			// Uncomment by removing comment marker
			$replacement = $matches[1] . "wfLoadExtension( '$name' );";
			$newContents = preg_replace( $patternCommented, $replacement, $contents, 1 );
			file_put_contents( $localSettingsPath, $newContents );
			return "Extension '$name' re-enabled (uncommented).";
		}

		// If not found, append
		$loadString = "\nwfLoadExtension( '$name' );\n";
		if ( strpos( file_get_contents( $localSettingsPath ), $loadString ) === false ) {
			file_put_contents( $localSettingsPath, $loadString, FILE_APPEND );
		}

		return "Extension '$name' added and enabled.";
	}

	/**
	 * Disable an extension by commenting out its load line in LocalSettings.php.
	 *
	 * @param string $name Extension name.
	 *
	 * @return string Status message.
	 */
	public static function disableExtension( $name ) {
		$localSettingsPath = dirname( __DIR__, 2 ) . '/LocalSettings.php';
		if ( !file_exists( $localSettingsPath ) ) {
			return "LocalSettings.php not found.";
		}

		$contents = file_get_contents( $localSettingsPath );
		$patternEnabled = "/^( *)(wfLoadExtension\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;)/m";

		if ( preg_match( $patternEnabled, $contents, $matches ) ) {
			$replacement = $matches[1] . '# ' . $matches[2];
			$newContents = preg_replace( $patternEnabled, $replacement, $contents, 1 );
			file_put_contents( $localSettingsPath, $newContents );
			return "Extension '$name' disabled (commented).";
		}

		return "Extension '$name' was not enabled.";
	}

	/**
	 * Check if an extension is enabled or disabled in LocalSettings.php.
	 *
	 * @param string $name Extension name.
	 *
	 * @return string 'enabled', 'disabled', or 'notfound'
	 */
	public static function checkExtensionStatus( $name ) {
		$localSettingsPath = dirname( __DIR__, 2 ) . '/LocalSettings.php';
		if ( !file_exists( $localSettingsPath ) ) {
			return 'notfound';
		}

		$contents = file_get_contents( $localSettingsPath );
		$patternEnabled = "/^\s*wfLoadExtension\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;/m";
		$patternCommented = "/^\s*(\/\/|#)\s*wfLoadExtension\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;/m";

		if ( preg_match( $patternEnabled, $contents ) ) {
			return 'enabled';
		} elseif ( preg_match( $patternCommented, $contents ) ) {
			return 'disabled';
		} else {
			return 'notfound';
		}
	}

	/**
	 * Check if a skin is enabled or disabled in LocalSettings.php.
	 *
	 * @param string $name Skin name.
	 *
	 * @return string 'enabled', 'disabled', or 'notfound'
	 */
	public static function checkSkinStatus( $name ) {
		$localSettingsPath = dirname( __DIR__, 2 ) . '/LocalSettings.php';
		if ( !file_exists( $localSettingsPath ) ) {
			return 'notfound';
		}

		$contents = file_get_contents( $localSettingsPath );
		$patternEnabled = "/^\s*wfLoadSkin\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;/m";
		$patternCommented = "/^\s*(\/\/|#)\s*wfLoadSkin\(\s*'" . preg_quote( $name, '/' ) . "'\s*\)\s*;/m";

		if ( preg_match( $patternEnabled, $contents ) ) {
			return 'enabled';
		} elseif ( preg_match( $patternCommented, $contents ) ) {
			return 'disabled';
		} else {
			return 'notfound';
		}
	}

	/**
	 * Install and enable a skin (smart logic).
	 *
	 * @param string $name Skin name.
	 * @param string $repository Git repository URL.
	 * @param string $branch Git branch name.
	 * @param string $commit Specific commit hash.
	 *
	 * @return array [bool success, string message]
	 */
	public static function installAndEnableSkin( $name, $repository, $branch = 'master', $commit = '' ) {
		$installResult = self::installSkin( $name, $repository, $branch, $commit );
		if ( !$installResult[0] ) {
			return $installResult;
		}

		$localSettingsPath = dirname( __DIR__, 2 ) . '/LocalSettings.php';
		$skinString = "\n\$wgValidSkinNames['$name'] = '$name';\n";
		file_put_contents( $localSettingsPath, $skinString, FILE_APPEND );

		return [ true, "Skin '$name' installed and enabled." ];
	}

	/**
	 * Run MediaWiki's update.php maintenance script.
	 *
	 * @return string Status message.
	 */
	public static function runUpdateScript() {
		$cmd = "php maintenance/update.php";
		exec( $cmd );
		return "Database updated.";
	}
}
