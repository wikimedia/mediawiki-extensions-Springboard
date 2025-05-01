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
 use Wikimedia\ParamValidator\ParamValidator;

 /**
 * @ingroup Wikitweak
 */
class WikitweakAPI extends ApiBase {

	function execute() {
		$data = $this->extractRequestParams();

        if ( $data[ 'action' ] == 'install' ) {
            if ( $data[ 'bundled' ] == true ) {
                // Just enable and do not try to download
            } else {
                // Clone first
                $this->download( $data );
                // Enable
            }
            if ( $data[ 'dbupdate' ] == true ) {
                $this->dbUpdate();
            }
            if ( $data[ 'composer' ] == true ) {
                $this->composerInstall();
            }
        } elseif ( $data[ 'action' ] == 'uninstall' ) {
            if ( $data[ 'bundled' ] == true ) {
                // Just disable and do not delete any directory
            } else {
                // Disable first
                // Delete the directory
                $this->delete( $data );
            }
        } else {
            // Throw an error in response here
        }

        $result = $this->getResult();
		$result->addValue(
			null,
			$this->getModuleName(),
			[]
		);
	}

    /**
     * Enable an extension or skin
     * @param array $data
     * @return void
     */
    function enable( $data ) {
        switch ( $data[ 'type' ] ) {
            case 'extension':
                wfLoadExtension( $data[ 'name' ] );
                break;
            case 'skin':
                wfLoadSkin( $data[ 'name' ] );
                break;
            default:
                break;
        }
    }

    /**
     * Disable an extension or skin
     * @param array $data
     * @return void
     */
    function disable( $data ) {
        switch ( $data[ 'type' ] ) {
            case 'extension':
                wfLoadExtension( $data[ 'name' ] );
                break;
            case 'skin':
                wfLoadSkin( $data[ 'name' ] );
                break;
            default:
                break;
        }
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
                break;
        }
    }

    /**
     * Run MW maintenance update script to create / modify necessary tables for the extension / skin
     * @return void
     */
    function dbUpdate() {
    }

    /**
     * Run Composer install command to fetch the composer-based dependencies for the extension / skin
     * @return void
     */
    function composerInstall() {

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
                ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
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