#!/usr/bin/env php
<?php

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "eZ publish static cache generator\n" .
                                                         "\n" .
                                                         "./bin/makestaticcache_cli.php [-f|--force] [-q|--quiet] " .
														 "--subtree=/path/to/node --max-level=3" ),
                                      'use-session' => false,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[q|quiet][f|force][subtree:][max-level:]",
                                "",
                                array(	'subtree' 	=> "Subtree to use to generate static cache",
										'max-level' => "Maximum URL level to go",
										'quiet'		=> "Don't write anything",
										'force'		=> "Generate cache even if a cache file exists") );

$subtree = $options['subtree'];
$max_level = $options['max-level'];
$force = $options['force'];
$quiet = $options['quiet'];

$script->initialize();

if ( !$subtree || !$max_level )
{
	$cli->error( '--subtree and --max-level are required.' );
	$script->showHelp();
	$script->shutdown( 1 );
}

include_once( 'lib/ezutils/classes/ezdebug.php' );
include_once( 'lib/ezfile/classes/ezdir.php' );
include_once( 'lib/ezutils/classes/ezini.php' );
include_once( 'extension/makestaticcache_cli/lib/ezstaticcachecli.php' );

$staticCacheCLI = new eZStaticCacheCLI();
$staticCacheCLI->generateCache($force, $quiet, $cli, $subtree, $max_level);
$script->shutdown();

?>
