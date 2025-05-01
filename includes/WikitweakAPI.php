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

 /**
 * @ingroup Wikitweak
 */
class WikitweakAPI extends ApiBase {

    /**
	 * Evaluates the parameters, performs the requested API query, and sets up
	 * the result.
	 *
	 * The execute() method will be invoked when an API call is processed.
	 *
	 * The result data is stored in the ApiResult object available through
	 * getResult().
	 */
	function execute() {
		$data = $this->extractRequestParams();

        if ( $data[ 'action' ] == 'install' ) {
            if ( $data[ 'bundled' ] == true ) {
                // Just enable and do not try to download
            } else {
                // Clone first
                // Enable
            }
        } elseif ( $data[ 'action' ] == 'uninstall' ) {
            if ( $data[ 'bundled' ] == true ) {
                // Just disable and do not delete any directory
            } else {
                // Disable first
                // Delete the directory
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