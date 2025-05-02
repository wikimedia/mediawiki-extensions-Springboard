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

 use ApiBase;
 use ExtensionRegistry;
 use Wikimedia\ParamValidator\ParamValidator;

 /**
  * @ingroup Wikitweak
  */
class WikitweakAPI extends ApiBase {

	private $loaderFile = __DIR__ . '/CustomLoader.php';

	function execute() {
		$data = $this->extractRequestParams();
		$type = $data[ 'type' ];
		$name = $data[ 'name' ];
		$action = $data[ 'action' ];

		$registry = ExtensionRegistry::getInstance();
		$func = $type === 'extension' ? 'wfLoadExtension' : 'wfLoadSkin';
		$line = "$func( '$name' );";

		$lines = file_exists( $this->loaderFile )
			? array_filter( file( $this->loaderFile,
			FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), fn( $l ) => trim( $l ) !== '<?php' )
			: [];

		$inLoader = in_array( $line, $lines );
		$isLoaded = $registry->isLoaded( $name );

		if ( $action === 'install' ) {
			if ( $isLoaded && !$inLoader ) {
				$this->dieWithError( $name . ' is already loaded elsewhere (LocalSettings.php)' );
			}
			if ( $inLoader ) {
				$this->dieWithError( $name . ' is already installed' );
			}
			$lines[] = $line;

			if ( $data[ 'bundled' ] != false ) {
				$this->download( $data );
			}

		} else {
			if ( $isLoaded && !$inLoader ) {
				$this->dieWithError( $name . 'is loaded elsewhere (LocalSettings.php); not disabling.' );
			}
			if ( !$inLoader ) {
				$this->dieWithError( $name . ' is not installed yet' );
			}
			$lines = array_filter( $lines, fn( $l ) => trim( $l ) !== $line );
		}

		file_put_contents( $this->loaderFile, "<?php\n" . implode( "\n", $lines ) . "\n" );

		if ( $action == 'install' ) {
			if ( $data[ 'dbupdate' ] == true ) {
				$this->dbUpdate();
			}
			if ( $data[ 'composer' ] == true ) {
				$this->composerInstall();
			}
		}

		$result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			[
				'action' => $action,
				'result' => 'success'
			]
		);
	}

	/**
	 * Download extension / skin using Git clone
	 * @param mixed $data
	 * @return void
	 */
	function download( $data ) {
		switch ( $data[ 'type' ] ) {
			case 'extension':
				exec( 'git clone --branch ' . $data[ 'branch' ]
					. 'https://github.com/wikimedia/mediawiki-extensions-'
					. $data[ 'name' ] . ' ../extensions/' . $data[ 'name' ] );
				if ( $data[ 'commit' ] && $data[ 'commit' ] !== 'HEAD' ) {
					exec(
						'cd ' . ' ../extensions/' . ' && git checkout ' . $data[ 'commit' ]
					);
				}
				break;
			case 'skin':
				exec( 'git clone --branch ' . $data[ 'branch' ]
					. 'https://github.com/wikimedia/mediawiki-skins-'
					. $data[ 'name' ] . ' ../skins/' . $data[ 'name' ] );
				if ( $data[ 'commit' ] && $data[ 'commit' ] !== 'HEAD' ) {
					exec(
						'cd ' . ' ../skins/' . ' && git checkout ' . $data[ 'commit' ]
					);
				}
				break;
			default:
				$this->dieWithError( 'Invalid type. Available types => [extension, skin]' );
				break;
		}
	}

	/**
	 * Run MW maintenance update script to create / modify necessary tables for the extension / skin
	 * @return void
	 */
	function dbUpdate() {
		$mediawikiRoot = dirname( __DIR__, 2 );
		$phpBinary = PHP_BINARY;
		$updateScript = "$mediawikiRoot/maintenance/update.php";

		if ( !file_exists( $updateScript ) ) {
			$this->dieWithError( 'Could not find the update.php script at ' . $updateScript );
		}

		$cmd = escapeshellcmd( "$phpBinary $updateScript" );

		exec( $cmd . " 2>&1", $output, $status );
	}

	/**
	 * Run Composer install command to fetch the composer-based dependencies for the extension / skin
	 * @return void
	 */
	function composerInstall() {
		$extensionRoot = dirname( __DIR__, 1 );
		$composerFilePath = "$extensionRoot/composer.json";

		if ( !file_exists( $composerFilePath ) ) {
			$this->dieWithError( 'Could not find the update.php script at ' . $composerFilePath );
		}
		exec( 'cd ' . $extensionRoot );
		exec( 'composer install' );
	}

	/**
	 * Delete extension / skin directory
	 * @param mixed $data
	 * @return void
	 */
	function delete( $data ) {
		switch ( $data[ 'type' ] ) {
			case 'extension':
				exec( 'rm -rf ' . '../extensions/' . $data[ 'name' ] );
				break;
			case 'skin':
				exec( 'rm -rf ' . '../skins/' . $data[ 'name' ] );
				break;
			default:
				break;
		}
	}

	private static function isInCustomLoader( $type, $name ): bool {
		$function = self::getLoaderFunction( $type );
		$line = "$function( '$name' );";
		$lines = self::loadLines();
		return in_array( $line, $lines );
	}

	private static function getLoaderFunction( $type ) {
		return match ( strtolower( $type ) ) {
			'extension' => 'wfLoadExtension',
			'skin'      => 'wfLoadSkin',
			default     => null,
		};
	}

	private static function loadLines(): array {
		if ( !file_exists( self::$loaderFile ) ) {
			file_put_contents( self::$loaderFile, "<?php\n" );
			return [];
		}

		$lines = file( self::$loaderFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		return array_filter( $lines, fn( $l ) => trim( $l ) !== "<?php" );
	}

	private static function writeLines( array $lines ): void {
		$output = "<?php\n" . implode( "\n", $lines ) . "\n";
		file_put_contents( self::$loaderFile, $output );
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'action' => [
			],
			'name' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'type' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'bundled' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'dbupdate' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'composer' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'commit' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'branch' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}
