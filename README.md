Springboard is a MediaWiki extension that allows users to install, enable, disable, and manage other MediaWiki extensions and skins directly from within the wiki interface.

It provides a special page that fetches and displays available extensions/skins, and allows system administrators to manage them without manually editing LocalSettings.php or accessing the server.

For more information, see the online documentation at:
https://www.mediawiki.org/wiki/Extension:Springboard

## Version
Springboard is currently at version 1.0.1. It works with MediaWiki version 1.41 and higher.

## Installing
Download the extension and place it in the `extensions/` directory in a folder named `Springboard`.

Add the following to the end of your LocalSettings.php:
`wfLoadExtension( 'Springboard' );`
`require_once(__DIR__ . '/extensions/Springboard/includes/CustomLoader.php');`

Alternatively, you may clone the extension using Git:
`cd extensions`
`git clone "https://gerrit.wikimedia.org/r/mediawiki/extensions/Springboard"`

Make sure the web server has the correct permissions:
`chmod -R a+rxw extensions/Springboard`

## Using
Once installed, go to the `Special:Springboard` page. From there, users with the appropriate permissions can:

- View a list of recommended or available extensions and skins
- Install new extensions and skins from the configured source
- Enable or disable installed extensions or skins

## Configuration

You can configure Springboard to load extension lists from a custom URL by adding the following to LocalSettings.php:
`$wgSpringboardURL = "https://your-custom-list-url";`

By default, it uses:
https://github.com/CanastaWiki/RecommendedRevisions/blob/main/1.43.yaml

## User Rights

Springboard access is controlled by the `springboard` user right. By default, only users in the `sysop` group can use the Springboard interface:
`$wgGroupPermissions['sysop']['springboard'] = true;`

You can add this right to other user groups using $wgGroupPermissions.

## Uninstalling

To uninstall Springboard, remove the following lines from your LocalSettings.php:
`wfLoadExtension( 'Springboard' );`
`require_once(__DIR__ . '/extensions/Springboard/includes/CustomLoader.php');`

No additional cleanup is required, as Springboard does not modify core settings or MediaWiki content directly. However, any changes made to extensions and skins through Springboard will persist unless manually reverted.
