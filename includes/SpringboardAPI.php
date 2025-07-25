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

 use ApiBase;
 use Exception;
 use ExtensionRegistry;
 use Wikimedia\ParamValidator\ParamValidator;

 /**
  * @ingroup Springboard
  */
class SpringboardAPI extends ApiBase {

	private $loaderFile = __DIR__ . '/CustomLoader.php';

	function execute() {
		$data = $this->extractRequestParams();
		$type = $data[ 'sbtype' ];
		$name = $data[ 'sbname' ];
		$action = $data[ 'sbaction' ];

		$extensionRoot = dirname( __DIR__, 1 );

		$registry = ExtensionRegistry::getInstance();
		$func = $type === 'extension' ? 'wfLoadExtension' : 'wfLoadSkin';
		$endPath = $type === 'extension' ? 'extensions' : 'skins';
		$line = "$func( '$name', '$extensionRoot/$endPath/$name/$type.json' );";

		$lines = file_exists( $this->loaderFile )
			? array_filter( file( $this->loaderFile,
			FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), static fn ( $l ) => trim( $l ) !== '<?php' )
			: [];

		$inLoader = in_array( $line, $lines );
		$isLoaded = $registry->isLoaded( $name );

		if ( $action === 'install' ) {
			if ( $isLoaded && !$inLoader ) {
				$this->dieWithError( $this->msg( 'springboard-api-error-loadedelsewhere', $name ) );
			}
			if ( $inLoader ) {
				$this->dieWithError( $this->msg( 'springboard-api-error-alreadyinstalled', $name ) );
			}
			$lines[] = $line;

			if ( $data[ 'sbbundled' ] == false ) {
				$this->download( $data );
			}

		} else {
			if ( $isLoaded && !$inLoader ) {
				$this->dieWithError( $this->msg( 'springboard-api-error-loadedelsewhere', $name ) );
			}
			if ( !$inLoader ) {
				$this->dieWithError( $this->msg( 'springboard-api-error-notinstalled', $name ) );
			}
			$lines = array_filter( $lines, static fn ( $l ) => trim( $l ) !== $line );
		}

		file_put_contents( $this->loaderFile, "<?php\n" . implode( "\n", $lines ) . "\n" );

		if ( $action == 'install' ) {
			if ( $data[ 'sbdbupdate' ] == true ) {
				$this->dbUpdate();
			}
			if ( $data[ 'sbcomposer' ] == true ) {
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
		switch ( $data[ 'sbtype' ] ) {
			case 'extension':
				$repositoryLink = isset( $data['sbrepo'] ) && $data['sbrepo']
						? $data['sbrepo']
						: 'https://github.com/wikimedia/mediawiki-extensions-' . $data['sbname'];
				try {
					exec( 'git clone --branch ' . $data[ 'sbbranch' ]
						. ' ' . $repositoryLink . ' ' . $extensionRoot . '//extensions/' . $data[ 'sbname' ] );
					if ( $data[ 'sbcommit' ] && $data[ 'sbcommit' ] !== 'HEAD' ) {
						exec(
							'cd ' . $extensionRoot . '//extensions/' . ' && git checkout ' . $data[ 'sbcommit' ]
						);
					}
				} catch ( Exception $e ) {
					$this->dieWithError( $e->getMessage() );
				}
				break;
			case 'skin':
				$repositoryLink = isset( $data['sbrepo'] ) && $data['sbrepo']
						? $data['sbrepo']
						: 'https://github.com/wikimedia/mediawiki-skins-' . $data['sbname'];
				exec( 'git clone --branch ' . $data[ 'sbbranch' ]
					. ' ' . $repositoryLink . ' ' . $extensionRoot . '//skins/' . $data[ 'sbname' ] );
				if ( $data[ 'sbcommit' ] && $data[ 'sbcommit' ] !== 'HEAD' ) {
					exec(
						'cd ' . $extensionRoot . '//skins/' . ' && git checkout ' . $data[ 'sbcommit' ]
					);
				}
				break;
			default:
				$this->dieWithError( $this->msg( 'springboard-api-error-invalidtype' ) );
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
			$this->dieWithError( $this->msg( 'springboard-api-error-composerfile', $composerFilePath ) );
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
		switch ( $data[ 'sbtype' ] ) {
			case 'extension':
				exec( 'rm -rf ' . '../extensions/' . $data[ 'sbname' ] );
				break;
			case 'skin':
				exec( 'rm -rf ' . '../skins/' . $data[ 'sbname' ] );
				break;
			default:
				break;
		}
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
			'sbaction' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'sbname' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'sbtype' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'sbbundled' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'sbdbupdate' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'sbcomposer' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'sbcommit' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'sbbranch' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'sbrepo' => [
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_TYPE => 'string',
			]
		];
	}
}
