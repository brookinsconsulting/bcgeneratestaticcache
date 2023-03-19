#!/usr/bin/env php
<?php
/**
 * File containing the bcgeneratestaticcache copyright information file
 *
 * @copyright Copyright (C) 1999 - 2023 Brookins Consutling and 2007 Damien POBEL. All rights reserved.
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
$quiet = false; //$options['quiet'];
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

/**
 * File contains an eZ Publish cli script to automatically
 * fetch all the content of the eZ Publish siteaccess database content
 * tree content nodes, as defined by settings, then fetch and store the content
 * in var/site/static dir traditionally.
 *
 * Static cache is based on custom extension settings (array of siteaccess name strings),
 * this script iterate over each siteaccess building an array of site languages
 * (site locale and site url), then iterating over site language information fetch
 * the root node of the content tree (settings based) in each language and then all
 * child nodes in each language. Next iterating over an array of all nodes in all
 * locales, for each node, generate the static cache html representing that node.
 */

/**
 * Test to replace php8 only function in php7
 */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
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

/**
 * Get a reference to eZINI. append.php will be added automatically.
 */
$ini = eZINI::instance( 'site.ini' );
$bcgeneratestaticcacheINI = eZINI::instance( 'bcgeneratestaticcache.ini' );

/**
 * BC: Testing for settings required by the script and defining other variables required by the script
 */
if ( $bcgeneratestaticcacheINI->hasVariable( 'BCGenerateStaticCacheSettings', 'StaticCacheRootNodeID' ) &&
     $bcgeneratestaticcacheINI->hasVariable( 'BCGenerateStaticCacheSettings', 'Path' ) &&
     $bcgeneratestaticcacheINI->hasVariable( 'BCGenerateStaticCacheSettings', 'Protocol' ) &&
     $bcgeneratestaticcacheINI->hasVariable( 'Classes', 'ClassFilterType' ) &&
     $bcgeneratestaticcacheINI->hasVariable( 'Classes', 'ClassFilterArray' ) &&
     $ini->hasVariable( 'SiteSettings', 'SiteURL' ) &&
     $ini->hasVariable( 'FileSettings', 'VarDir' )
)
{
    /**
     * BC: Define root content tree node ID
     */
    $staticCacheRootNodeID = $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'StaticCacheRootNodeID' );

    /**
     * BC: Define the sitemap basename and output file suffix
     */
    //$sitemapName = $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'Filename' );
    //$sitemapSuffix = $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'Filesuffix' );

    /**
     * BC: Define the sitemap base path, output file directory path. Path to directory to write out generated sitemaps
     */
    if( $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'Path' ) != false )
    {
        $sitemapPath = $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'Path' );
    }
    else
    {
        $sitemapPath = $ini->variable( 'FileSettings', 'VarDir' );
    }

    /**
     * BC: Define the sitemap link protocol. Default http
     */
    $sitemapLinkProtocol = $bcgeneratestaticcacheINI->variable( 'BCGenerateStaticCacheSettings', 'Protocol' );

    /**
     * BC: Define content tree node fetch class filter. Array of class identifiers and whether to include or exclude them.
     */
    $classFilterType = $bcgeneratestaticcacheINI->variable( 'Classes', 'ClassFilterType' );
    $classFilterArray = $bcgeneratestaticcacheINI->variable( 'Classes', 'ClassFilterArray' );

    /**
     * BC: Define content tree node iteration node_id exclusion filter. Array of node_ids to exclude.
     */
    $excludeNodeIDs = $bcgeneratestaticcacheINI->variable( 'NodeSettings', 'ExcludedNodeIDs' );
}
else
{
    /**
     * BC: Alert user of missing ini settings variables
     */
    $cli->output( 'Missing INI Variables in configuration block GeneralSettings.' );
    return;
}

/**
 * BC: Fetch the array of siteaccess names (multi siteaccess; multi language)
 * which should be used to fetch content for the sitemap or the default
 * siteaccess name (one siteaccess; one language) when the custom settings are unavailable
 */
if( $bcgeneratestaticcacheINI->hasVariable( 'SiteAccessSettings', 'SiteAccessArray' ) )
{
    $siteAccessArray = $bcgeneratestaticcacheINI->variable( 'SiteAccessSettings', 'SiteAccessArray' );
}
else
{
    $siteAccessArray = array( $ini->variable( 'SiteSettings', 'DefaultAccess' ) );
}

/**
 * BC: Array to store all siteacces related information
 */
$siteaccesses = array();

/**
 * BC: Iterate over each siteaccess and collect siteaccess local settings (site languages)
 */
foreach( $siteAccessArray as $siteAccess )
{
    $siteAccessINI = eZINI::instance( 'site.ini.append.php', 'settings/siteaccess/' . $siteAccess  );
    $siteacessLocale = $siteAccessINI->variable( 'RegionalSettings', 'Locale' );

    if ( $siteAccessINI->hasVariable( 'RegionalSettings', 'SiteLanguageList' ) )
    {
        $siteaccessLanguages = $siteAccessINI->variable( 'RegionalSettings', 'SiteLanguageList' );

        if( !in_array( $siteacessLocale, $siteaccessLanguages ) )
        {
            array_push( $siteacessLocale, $siteaccessLanguages );
        }

        array_push( $siteaccesses, array( 'siteaccess' => $siteAccess,
                                          'locale' => $siteacessLocale,
                                          'siteaccessLanguages' => $siteaccessLanguages,
                                          'siteurl' => $siteAccessINI->variable( 'SiteSettings', 'SiteURL' ) ) );
    }
    else
    {
        array_push( $siteaccesses, array( 'siteaccess' => $siteAccess,
                                          'locale' => $siteacessLocale,
                                          'siteaccessLanguages' => array( $siteacessLocale ),
                                          'siteurl' => $siteAccessINI->variable( 'SiteSettings', 'SiteURL' ) ) );
    }
}

/**
 * BC: Iterate over each siteaccess language
 */
foreach ( $siteaccesses as $siteaccess )
{
    /**
     * BC: Alert user of the generation of the sitemap for the current language siteacces (name)
     */
     var_dump($quiet);
    if ( !$quiet )
        $cli->output( "Generating static cache for siteaccess " . $siteaccess["siteaccess"] . " \n" );

    /**
     * BC: Fetch siteaccess site url
     */
    $siteURL = $siteaccess['siteurl'];
    if( substr( $siteURL, -1) != '/' ) {
        $siteURL .= '/';
    }

    /**
     * Get the Sitemap's root node
     */
    $rootNode = eZContentObjectTreeNode::fetch( $staticCacheRootNodeID, $siteaccess['locale'] );

    /**
     * Test for content object fetch (above) failure to return a valid object.
     * Alert the user and terminate execution of script
     */
    if ( !is_object( $rootNode ) )
    {
        $cli->output( "Invalid StaticCacheRootNodeID in configuration block GeneralSettings; OR StaticCacheRootNodeID does not not have language translation for current siteaccess language.\n" );
        return;
    }

    /**
     * Change siteaccess
     */
    eZSiteAccess::change( array("name" => $siteaccess["siteaccess"], "type" => eZSiteAccess::TYPE_URI ) );

    /**
     * Fetch the content tree nodes (children) of the above root node (in a given locale)
     */
    $nodeArray = $rootNode->subTree( array( 'Language' => $siteaccess['siteaccessLanguages'],
                                            'ClassFilterType' => $classFilterType,
                                            'ClassFilterArray' => $classFilterArray ) );
    $resultsArray = array_merge( array( $rootNode ), $nodeArray );

    //echo count($nodeArray); die();

    /**
     * BC: Generate Static Cache For Nearly Every Content Object
     * based on array of arrays containing content tree nodes in each language
     * for a given sitaccess or array of siteaccesses
     */
    foreach ( $resultsArray as $subTreeNode )
    {
        /**
         * BC: Site node url alias (calculation)
         */
        $urlAlias = $sitemapLinkProtocol . '://' . $siteURL . $subTreeNode->attribute( 'url_alias' );
        print_r($urlAlias);echo "\n";

        /**
         * BC: node_id exclusion (calculation)
         */
        $nodeID = $subTreeNode->attribute( 'node_id' );
        $nodeIDPath = $subTreeNode->attribute( 'path_string' );

        /**
         * BC: Test for exclude nodes
         */
         if ( in_array( $nodeID, $excludeNodeIDs ) || str_contains( $nodeIDPath, implode( $excludeNodeIDs ) ) )
         {
             $cli->output("Excluding: $nodeID\n");
             continue;
         }

        /**
         * BC: Fetch node's object
         */
        //$object = $subTreeNode->object();

        /**
         * BC: Fetch, generate and write cache file to disk
         */
        $generateStaticCache->generateCache( $force, $quiet, $cli, '/'. $subTreeNode->attribute('url_alias'), $maxLevel, $delay, $debug );
    }

    /**
     * BC: Alert user of script completion
     */
    if ( !$quiet )
    {
        $cli->output( "Static cache for siteaccess " . $siteaccess['siteaccess'] . " has been generated.\n\n" );
    }
}

/**
 * Terminate execution and exit system normally
 */

// Shut down script
$script->shutdown();

?>