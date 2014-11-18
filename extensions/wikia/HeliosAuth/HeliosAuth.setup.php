<?php
// Terminate the script when called out of MediaWiki context.
if ( !defined( 'MEDIAWIKI' ) ) {
    echo  'Invalid entry point. '
        . 'This code is a MediaWiki extension and is not meant to be executed standalone. '
        . 'Try vising the main page: /index.php or running a different script.';
    exit( 1 );
}

$wgAutoloadClasses['HeliosAuth'] =  __DIR__ . '/HeliosAuth.class.php';