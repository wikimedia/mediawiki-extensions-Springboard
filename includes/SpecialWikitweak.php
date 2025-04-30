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

use OOUI\ButtonInputWidget;
use OOUI\DropdownInputWidget;
use OOUI\FieldLayout;
use OOUI\FieldsetLayout;
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
		$out->enableOOUI();
		$this->setHeaders();

		$req = $this->getRequest();
		$action = $req->getText( 'action' );

		if ( $action === 'install' ) {
			$this->doInstall( $req->getText( 'item' ) );
		} elseif ( $action === 'uninstall' ) {
			$this->doUninstall( $req->getText( 'item' ) );
		}

		$this->showForms();
	}

	private function doInstall( $item ) {
		list( $typeString, $data ) = explode( '|', $item, 2 );
		list( $type, $name ) = explode( ':', $typeString, 2 );
		$data = json_decode( $data, true );
		$commit = $data[ 'commit' ];
		$repository = $data['repository'] ?? null;
		$branch  = $data['branch'] ?? null;
		if ( $repository === null ) {
			$repository = "https://github.com/wikimedia/mediawiki-$type-$name";
		}
		if ( $branch === null ) {
			$branch = $this->mwVersion;
		}
		if ( !$repository ) {
			$this->getOutput()->addHTML( "<div class='errorbox'>Cannot find repo for $name.</div>" );
			return;
		}

		if ( $type === 'extensions' ) {
			list( $ok, $msg ) = Installer::installExtension( $name, $repository, $branch, $commit );
			if ( $ok ) {
				Installer::smartEnableExtension( $name );
			}
		} else {
			list( $ok, $msg ) = Installer::installAndEnableSkin( $name, $repository, $branch, $commit );
		}

		$this->getOutput()->addHTML( "<div class='successbox'>$msg</div>" );
	}

	private function doUninstall( $item ) {
		list( $type, $name ) = explode( ':', $item, 2 );
		if ( $type === 'extension' ) {
			$msg1 = Installer::disableExtension( $name );
			$this->removeDirectory( 'extensions', $name );
		} else {
			$msg1 = Installer::disableSkin( $name );
			$this->removeDirectory( 'skins', $name );
		}
		$this->getOutput()->addHTML( "<div class='successbox'>$msg1</div>" );
	}

	private function showForms() {
		$out = $this->getOutput();
		$recs = $this->fetchRecommendedPage();

		$extensionsToInstall = [];
		$skinsToInstall = [];

		foreach ( $recs['extensions'] as $extInfo ) {
			foreach ( $extInfo as $name => $details ) {
				if ( Installer::checkExtensionStatus( $name ) === 'notfound' ) {
					$extensionsToInstall[] = [ "data" => "extensions:$name|" . json_encode( $details ), "label" => "$name" ];
				}
			}
		}

		foreach ( $recs['skins'] as $skinInfo ) {
			foreach ( $skinInfo as $name => $details ) {
				if ( Installer::checkSkinStatus( $name ) === 'notfound' ) {
					$skinsToInstall[] = [ "data" => "skins:$name", "label" => "$name" ];
				}
			}
		}

		$extensionsInstalled = [];
		$skinsInstalled = [];

		foreach ( $recs['extensions'] as $extInfo ) {
			foreach ( $extInfo as $name => $details ) {
				if ( Installer::checkExtensionStatus( $name ) === 'enabled' ) {
					$extensionsInstalled[] = [ "data" => "extensions:$name|" . json_encode( $details ), "label" => $name ];
				}
			}
		}

		foreach ( $recs['skins'] as $skinInfo ) {
			foreach ( $skinInfo as $name => $details ) {
				if ( Installer::checkSkinStatus( $name ) === 'enabled' ) {
					$skinsInstalled[] = [ "data" => "skins:$name", "label" => $name ];
				}
			}
		}

		// --- Extension Install Form ---
		if ( !empty( $extensionsToInstall ) ) {
			$extInstallForm = new FieldsetLayout(
				[
				'label' => 'Install Recommended Extensions',
				'items' => [
					new FieldLayout(
						new DropdownInputWidget(
							[
							'name' => 'item',
							'options' => $extensionsToInstall
							 ]
						),
						[ 'label' => 'Choose Extension to Install' ]
					),
					new ButtonInputWidget(
						[
						'name' => 'action',
						'value' => 'install',
						'type' => 'submit',
						'flags' => [ 'primary', 'progressive' ],
						'label' => 'Install Extension'
						 ]
					)
				]
				 ]
			);
			$out->addHTML( '<form method="post">' . $extInstallForm . '</form>' );
		}

		// --- Skin Install Form ---
		if ( !empty( $skinsToInstall ) ) {
			$skinInstallForm = new FieldsetLayout(
				[
				'label' => 'Install Recommended Skins',
				'items' => [
					new FieldLayout(
						new DropdownInputWidget(
							[
							'name' => 'item',
							'options' => $skinsToInstall
							 ]
						),
						[ 'label' => 'Choose Skin to Install' ]
					),
					new ButtonInputWidget(
						[
						'name' => 'action',
						'value' => 'install',
						'type' => 'submit',
						'flags' => [ 'primary', 'progressive' ],
						'label' => 'Install Skin'
						 ]
					)
				]
				 ]
			);
			$out->addHTML( '<form method="post">' . $skinInstallForm . '</form>' );
		}

		// --- Extension Uninstall Form ---
		if ( !empty( $extensionsInstalled ) ) {
			$extUninstallForm = new FieldsetLayout(
				[
				'label' => 'Uninstall Installed Extensions',
				'items' => [
					new FieldLayout(
						new DropdownInputWidget(
							[
							'name' => 'item',
							'options' => $extensionsInstalled
							 ]
						),
						[ 'label' => 'Choose Extension to Uninstall' ]
					),
					new ButtonInputWidget(
						[
						'name' => 'action',
						'value' => 'uninstall',
						'type' => 'submit',
						'flags' => [ 'destructive' ],
						'label' => 'Uninstall Extension'
						 ]
					)
				]
				 ]
			);
			$out->addHTML( '<form method="post">' . $extUninstallForm . '</form>' );
		}

		// --- Skin Uninstall Form ---
		if ( !empty( $skinsInstalled ) ) {
			$skinUninstallForm = new FieldsetLayout(
				[
				'label' => 'Uninstall Installed Skins',
				'items' => [
					new FieldLayout(
						new DropdownInputWidget(
							[
							'name' => 'item',
							'options' => $skinsInstalled
							 ]
						),
						[ 'label' => 'Choose Skin to Uninstall' ]
					),
					new ButtonInputWidget(
						[
						'name' => 'action',
						'value' => 'uninstall',
						'type' => 'submit',
						'flags' => [ 'destructive' ],
						'label' => 'Uninstall Skin'
						 ]
					)
				]
				 ]
			);
			$out->addHTML( '<form method="post">' . $skinUninstallForm . '</form>' );
		}
	}

	/**
	 * Fetch and parse Recommended_revisions/1.43 YAML block.
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

	/**
	 * Lookup a single recommended commit for an item.
	 *
	 * @param string $type 'extension' or 'skin'
	 * @param string $name
	 * @return string
	 */
	private function getRecommendedCommit( $type, $name ) {
		$all = $this->fetchRecommendedPage();
		return $all[$type][$name] ?? '';
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $type 'extensions' or 'skins'
	 * @param string $name
	 */
	private function removeDirectory( $type, $name ) {
		$path = $GLOBALS['IP'] . "/$type/$name";
		if ( is_dir( $path ) ) {
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $it as $file ) {
				$file->isDir() ? rmdir( $file ) : unlink( $file );
			}
			rmdir( $path );
		}
	}
}
