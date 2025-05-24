# Springboard

Springboard is an extension to MediaWiki that allows user to 
manage, install, enable and disable MediaWiki extensions and 
skins.

## Installation  
1) <a href = "https://github.com/sanjay-thiyagarajan/Springboard/archive/refs/heads/main.zip">Download</a> the extension and place it in the ```extensions/``` directory.  
2) Add the following lines in **LocalSettings.php**  
```
wfLoadExtension( 'Springboard' );
require_once(__DIR__ . '/extensions/Springboard/includes/CustomLoader.php');
```
3) ✅ Done - Navigate to [Special:Version](https://www.mediawiki.org/wiki/Special:Version) on your wiki to verify that the extension is successfully installed.

Instead of downloading the zip archive you may also check this extension out via Git:
```
git clone https://github.com/sanjay-thiyagarajan/Springboard.git
```

**REQUIRED**: Make sure to have the right permissions over the extension directory `Springboard`   

   ```
    chmod -R a+rxw *
   ```
## Configuration  
```
$wgSpringboardURL = "https://your-custom-list-url";
```  
Defaults to https://www.mediawiki.org/wiki/Recommended_revisions/1.43 (can be taken as an example too)
### Parameters
#### User Rights  
Allows users to use the "Springboard" page action in order to manage extensions and skins for the wiki. Defaults to:
```
$wgGroupPermissions['sysop']['springboard'] = true;
```  
