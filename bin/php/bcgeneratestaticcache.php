#!/usr/bin/env php
<?php
/**
 * File containing the bcgeneratestaticcache copyright information file
 *
 * @copyright Copyright (C) 1999 - 2014 Brookins Consutling and 2007 Damien POBEL. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 * @package bcgeneratestaticcache
 */

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Subtree Static Cache Generator Script\n" .
                                                        "\n" .
                                                        "./extension/bcgeneratestaticcache/bin/bcgeneratestaticcache.php [-f|--force] [-q|--quiet] " .
                                                        "--subtree=/url/alias/path/to/node --max-level=3" ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true ) );

$script->startup();

// Script options
$options = $script->getOptions( "[q|quiet][f|force][subtree:][max-level:][c|children][d|debug][delay]",
                                "",
                                array( 'subtree' => "Subtree to use to generate static cache",
                                       'max-level' => "Maximum URL level to go",
                                       'quiet'	=> "Don't write anything",
                                       'force'	=> "Generate cache even if a cache file exists",
                                       'children' => "Generate cache for child objects of url",
                                       'debug'	=> "Display addition script execution debug output",
                                       'delay'	=> "Delay actual fetching of static cache content only store requests for cronjob to process" ) );

$subtree = $options['subtree'];
$maxLevel = $options['max-level'];
$quiet = $options['quiet'];
$force = $options['force'];
$children = $options['children'];
$delay = $options['delay'];
$debug = $options['debug'];

// Initialize script
$script->initialize();

// Test script options for required option values
if ( ( $subtree === false ) || ( $maxLevel === false ) )
{
    $cli->error( '--subtree and --max-level are required.' );
    $script->showHelp();
    $script->shutdown( 1 );
}

// Generate static cache based on script options
$generateStaticCache = new BCGenerateStaticCache();

$subtreeLevel = substr_count( $subtree, '/' );

if ( $children === true && ($maxLevel - $subtreeLevel) <= 0 )
{
    $maxSubtreeLevel = 1;
}
else
{
    $maxSubtreeLevel = $maxLevel - $subtreeLevel;
}

// Test for script option children
if ( $children === true )
{
    // Fetch child node objects
    $contentTreeNodeURLAliasML = eZURLAliasML::fetchByPath( $subtree );
    $contentTreeNodeID = eZURLAliasML::nodeIDFromAction( $contentTreeNodeURLAliasML[0]->Action );
    $contentTreeNode = eZContentObjectTreeNode::fetch( $contentTreeNodeID );
    $contentTreeNodeParams = array( 'SortBy'=> array( 'name', true ), 'Depth' => $maxSubtreeLevel );
    $contentTreeNodeList = array_merge( array( $contentTreeNode ), eZContentObjectTreeNode::subTreeByNodeID( $contentTreeNodeParams, $contentTreeNodeID ) );

    // Iterate over child nodes and generate static cache for each
    foreach( $contentTreeNodeList as $child )
    {
        $generateStaticCache->generateCache( $force, $quiet, $cli, '/'. $child->attribute('url_alias'), $maxLevel, $delay, $debug );
    }
}
else
{
    // Generate static cache for subtree
    $generateStaticCache->generateCache( $force, $quiet, $cli, $subtree, $maxLevel, $delay, $debug );
}

// Shut down script
$script->shutdown();

?>