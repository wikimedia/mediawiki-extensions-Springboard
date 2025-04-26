<?php
namespace MediaWiki\Extension\Wikitweak;

use MediaWiki\MediaWikiServices;

class Installer {

    public static function installExtension( $name, $repository, $branch = 'master', $commit = '' ) {
        $basePath = dirname( __DIR__, 2 ) . '/extensions/' . $name;

        if ( is_dir( $basePath ) ) {
            return [ false, "Extension $name already installed." ];
        }

        $gitCmd = "git clone --branch $branch $repository $basePath";
        exec( $gitCmd, $output, $status );

        if ( $status !== 0 ) {
            return [ false, implode( "\n", $output ) ];
        }

        if ( $commit ) {
            exec( "cd $basePath && git checkout $commit" );
        }

        return [ true, "Extension $name installed successfully." ];
    }

    public static function enableExtension( $name ) {
        $localSettingsPath = dirname( __DIR__, 2 ) . '/LocalSettings.php';

        $loadString = "wfLoadExtension( '$name' );\n";

        file_put_contents( $localSettingsPath, "\n" . $loadString, FILE_APPEND );

        return "Extension $name enabled in LocalSettings.php.";
    }

    public static function runUpdateScript() {
        $cmd = "php maintenance/update.php";
        exec( $cmd );
        return "Database updated.";
    }
}