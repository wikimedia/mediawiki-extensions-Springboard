<?php

namespace MediaWiki\Extension\Wikitweak;

use PermissionsError;
use SpecialPage;

class SpecialWikitweak extends SpecialPage {
    public function __construct() {
        parent::__construct( 'Wikitweak', 'wikitweak' );
    }

    /**
	 * @param null|string $query
	 */
    function execute( $query ) {
        if ( !$this->getUser()->isAllowed( 'wikitweak' ) ) {
			throw new PermissionsError( 'wikitweak' );
		}

        $out = $this->getOutput();
		$out->enableOOUI();
		$this->setHeaders();
    }

    function tweak() {
        $out = $this->getOutput();
    }


}