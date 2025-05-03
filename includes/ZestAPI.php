<?php
/**
 * Special page for Zest extension.
 *
 * @author  Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author  Naresh Kumar Babu <nk2indian@gmail.com>
 * @author  Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author  Yaron Koren <yaron57@gmail.com>
 * @file
 * @ingroup Extensions
 */

 namespace MediaWiki\Extension\Zest;

 use ApiBase;
 use Exception;
 use ExtensionRegistry;
 use Wikimedia\ParamValidator\ParamValidator;

 /**
  * @ingroup Zest
  */
class ZestAPI extends ApiBase {

	private $loaderFile = __DIR__ . '/CustomLoader.php';

	function execute() {
		$data = $this->extractRequestParams();
		$type = $data[ 'wttype' ];
		$name = $data[ 'wtname' ];
		$action = $data[ 'wtaction' ];

		$extensionRoot = dirname( __DIR__, 1 );

		$registry = ExtensionRegistry::getInstance();
		$func = $type === 'extension' ? 'wfLoadExtension' : 'wfLoadSkin';
		$endPath = $type === 'extension' ? 'extensions' : 'skins';
		$line = "$func( '$name', '$extensionRoot/$endPath/$name/$type.json' );";

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

			if ( $data[ 'wtbundled' ] == false ) {
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
			if ( $data[ 'wtdbupdate' ] == true ) {
				$this->dbUpdate();
			}
			if ( $data[ 'wtcomposer' ] == true ) {
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
		$extensionRoot = dirname( __DIR__, 1 );
		switch ( $data[ 'wttype' ] ) {
			case 'extension':
				try {
					exec( 'git clone --branch ' . $data[ 'wtbranch' ]
						. ' https://github.com/wikimedia/mediawiki-extensions-'
						. $data[ 'wtname' ] . ' '. $extensionRoot . '//extensions/' . $data[ 'wtname' ] );
					if ( $data[ 'wtcommit' ] && $data[ 'wtcommit' ] !== 'HEAD' ) {
						exec(
							'cd ' . $extensionRoot . '//extensions/' . ' && git checkout ' . $data[ 'wtcommit' ]
						);
					}
				} catch (Exception $e) {
					$this->dieWithError( $e->getMessage() );
				}
				break;
			case 'skin':
				exec( 'git clone --branch ' . $data[ 'wtbranch' ]
					. ' https://github.com/wikimedia/mediawiki-skins-'
					. $data[ 'wtname' ] . ' ../skins/' . $data[ 'wtname' ] );
				if ( $data[ 'wtcommit' ] && $data[ 'wtcommit' ] !== 'HEAD' ) {
					exec(
						'cd ' . ' ../skins/' . ' && git checkout ' . $data[ 'wtcommit' ]
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
		$mediawikiRoot = dirname( __DIR__, 3 );
		$phpBinary = PHP_BINARY;
		$updateScript = "$mediawikiRoot/maintenance/update.php";

		if ( !file_exists( $updateScript ) ) {
			$this->dieWithError( 'Could not find the update.php script at ' . $updateScript );
		}
		exec( "$phpBinary $updateScript" );
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
		switch ( $data[ 'wttype' ] ) {
			case 'extension':
				exec( 'rm -rf ' . '../extensions/' . $data[ 'wtname' ] );
				break;
			case 'skin':
				exec( 'rm -rf ' . '../skins/' . $data[ 'wtname' ] );
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
			'wtaction' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'wtname' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'wttype' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'wtbundled' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'wtdbupdate' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'wtcomposer' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'wtcommit' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'wtbranch' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}
