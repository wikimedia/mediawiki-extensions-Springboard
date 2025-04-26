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

use PermissionsError;
use SpecialPage;

/**
 * Defines Special:Wikitweak page.
 * 
 * @author Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author Naresh Kumar Babu <nk2indian@gmail.com>
 * @author Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author Yaron Koren <yaron57@gmail.com>
 */
class SpecialWikitweak extends SpecialPage
{

    /**
     * Constructor for SpecialWikitweak.
     */
    public function __construct()
    {
        parent::__construct('Wikitweak', 'wikitweak');
    }

    /**
     * Execute the special page.
     *
     * @param string|null $query Parameters passed to the page.
     * 
     * @throws PermissionsError If the user does not have 'wikitweak' permission.
     * 
     * @return void
     */
    public function execute( $query )
    {
        if (!$this->getUser()->isAllowed('wikitweak') ) {
            throw new PermissionsError('wikitweak');
        }

        $out = $this->getOutput();
        $out->enableOOUI();
        $this->setHeaders();
    }

    /**
     * Placeholder tweak function.
     *
     * @return void
     */
    public function tweak()
    {
        $out = $this->getOutput();
    }

}