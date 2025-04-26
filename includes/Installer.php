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

use MediaWiki\MediaWikiServices;

/**
 * Class Installer
 * 
 * Handles installing and enabling MediaWiki extensions and skins.
 * 
 * @author Jayanth Vikash Saminathan <jayanthvikashs@gmail.com>
 * @author Naresh Kumar Babu <nk2indian@gmail.com>
 * @author Sanjay Thiyagarajan <sanjayipscoc@gmail.com>
 * @author Yaron Koren <yaron57@gmail.com>
 */
class Installer
{

    /**
     * Install an extension from a Git repository.
     *
     * @param string $name       Extension name.
     * @param string $repository Git repository URL.
     * @param string $branch     Git branch name. Defaults to 'master'.
     * @param string $commit     Specific commit hash to checkout. Defaults to '' (HEAD).
     *
     * @return array [bool success, string message]
     */
    public static function installExtension( 
        $name, $repository, $branch = 'master', $commit = '' 
    ) {
        return self::install('extensions', $name, $repository, $branch, $commit);
    }

    /**
     * Install a skin from a Git repository.
     *
     * @param string $name       Skin name.
     * @param string $repository Git repository URL.
     * @param string $branch     Git branch name. Defaults to 'master'.
     * @param string $commit     Specific commit hash to checkout. Defaults to '' (HEAD).
     *
     * @return array [bool success, string message]
     */
    public static function installSkin( 
        $name, $repository, $branch = 'master', $commit = '' 
    ) {
        return self::install('skins', $name, $repository, $branch, $commit);
    }

    /**
     * Shared install logic for extensions and skins.
     *
     * @param string $type       'extensions' or 'skins'.
     * @param string $name       Name of extension/skin.
     * @param string $repository Git repository URL.
     * @param string $branch     Git branch name.
     * @param string $commit     Specific commit hash to checkout.
     *
     * @return array [bool success, string message]
     */
    protected static function install( $type, $name, $repository, $branch, $commit )
    {
        $basePath = dirname(__DIR__, 2) . '/' . $type . '/' . $name;

        if (is_dir($basePath) ) {
            return [
                false,
                ucfirst(rtrim($type, 's')) . " '$name' already installed."
            ];
        }

        $gitCmd = "git clone --branch " . escapeshellarg($branch) . ' '
            . escapeshellarg($repository) . ' '
            . escapeshellarg($basePath);

        exec($gitCmd, $output, $status);

        if ($status !== 0 ) {
            return [ false, implode("\n", $output) ];
        }

        if ($commit && $commit !== 'HEAD' ) {
            exec(
                'cd ' . escapeshellarg($basePath) . ' && git checkout ' . escapeshellarg($commit)
            );
        }

        return [
            true,
            ucfirst(rtrim($type, 's')) . " '$name' installed successfully."
        ];
    }

    /**
     * Enable an extension by adding it to LocalSettings.php.
     *
     * @param string $name Extension name.
     *
     * @return string Status message.
     */
    public static function enableExtension( $name )
    {
        $localSettingsPath = dirname(__DIR__, 2) . '/LocalSettings.php';
        $loadString = "wfLoadExtension( '$name' );\n";

        file_put_contents($localSettingsPath, "\n" . $loadString, FILE_APPEND);

        return "Extension '$name' enabled in LocalSettings.php.";
    }

    /**
     * Enable a skin by adding it to LocalSettings.php.
     *
     * @param string $name Skin name.
     *
     * @return string Status message.
     */
    public static function enableSkin( $name )
    {
        $localSettingsPath = dirname(__DIR__, 2) . '/LocalSettings.php';
        $loadString = "\$wgValidSkinNames['" . $name . "'] = '$name';\n";

        file_put_contents($localSettingsPath, "\n" . $loadString, FILE_APPEND);

        return "Skin '$name' enabled in LocalSettings.php.";
    }

    /**
     * Run MediaWiki's update.php maintenance script.
     *
     * @return string Status message.
     */
    public static function runUpdateScript()
    {
        $cmd = "php maintenance/update.php";
        exec($cmd);

        return "Database updated.";
    }
}
