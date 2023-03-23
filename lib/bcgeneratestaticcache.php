<?php
/**
 * File containing the bcgeneratestaticcache class file
 *
 * @copyright Copyright (C) 1999 - 2014 Brookins Consutling and 2007 Damien POBEL. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 * @package bcgeneratestaticcache
 */

class BCGenerateStaticCache extends BCStaticCache
{

    /**
     * User-Agent string
     */
    const USER_AGENT = 'eZ Publish static cache generator';

    private static $actionList = array();

    /**
     * The name of the host to fetch HTML data from.
     *
     * @deprecated deprecated since version 4.4, site.ini.[SiteSettings].SiteURL is used instead
     * @var string
     */
    private $hostName;

    /**
     * The base path for the directory where static files are placed.
     *
     * @var string
     */
    private $staticStorage;

    /**
     * The maximum depth of URLs that will be cached.
     *
     * @var int
     */
    private $maxCacheDepth;

    /**
     * Array of URLs to cache.
     *
     * @var array(int=>string)
     */
    private $cachedURLArray = array();

    /**
     * An array with siteaccesses names that will be cached.
     *
     * @var array(int=>string)
     */
    private $cachedSiteAccesses = array();

    /**
     * An array with URLs that is to always be updated.
     *
     * @var array(int=>string)
     */
    private $alwaysUpdate;

    function __construct()
    {
        $ini = eZINI::instance( 'staticcache.ini' );
        $this->hostName = $ini->variable( 'CacheSettings', 'HostName' );
        $this->staticStorageDir = $ini->variable( 'CacheSettings', 'StaticStorageDir' );
        $this->maxCacheDepth = $ini->variable( 'CacheSettings', 'MaxCacheDepth' );
        $this->cachedURLArray = $ini->variable( 'CacheSettings', 'CachedURLArray' );
        $this->cachedSiteAccesses = $ini->variable( 'CacheSettings', 'CachedSiteAccesses' );
        $this->alwaysUpdate = $ini->variable( 'CacheSettings', 'AlwaysUpdateArray' );
    }

    function level($url)
    {
        if ( $url == '' )
            return 0;
        return substr_count( $url, '/' )+1;
    }

    /**
     * Generates the static cache from the configured INI settings.
     *
     * @param bool $force If true then it will create all static caches even if it is not outdated.
     * @param bool $quiet If true then the function will not output anything.
     * @param eZCLI|false $cli The eZCLI object or false if no output can be done.
     * @param bool $delay
     */
    public function generateCache( $force = false, $quiet = false, $cli = false, $subtreeUrl = '/', $maxLevel = 0, $delay = true, $debug = true )
    {
        $quiet = false;
        $staticURLArray = array( $subtreeUrl ); //$this->cachedURLArray();
        $db = eZDB::instance();
        $configSettingCount = count( $staticURLArray );
        $currentSetting = 0;

        // $subtree = trim( $subtreeUrl, '/' );
        $subtreeMessage = $subtreeUrl;

        if ( $subtreeUrl == '/' )
        {
            $subtree = '';
            $subtreeMessage = '/';
        }
        else {
            $subtree = trim( $subtreeUrl, '/' );
        }

        $subtreeLevel = BCGenerateStaticCache::level( $subtree );

        if ( !$quiet && $cli && $debug )
	{
	    // print_r($cli);
            $cli->output( 'Using Subtree: ' . $subtreeMessage . '  level: ' . $subtreeLevel );
	}
        // This contains parent elements which must checked to find new urls and put them in $generateList
        // Each entry contains:
        // - url - Url of parent
        // - glob - A glob string to filter direct children based on name
        // - org_url - The original url which was requested
        // - parent_id - The element ID of the parent (optional)
        // The parent_id will be used to quickly fetch the children, if not it will use the url
        $parentList = array();
        // A list of urls which must generated, each entry is a string with the url
        $generateList = array();
        foreach ( $staticURLArray as $url )
        {
            $currentSetting++;
            if ( strpos( $url, '*') === false )
            {
                $generateList[] = $url;
            }
            else
            {
                $queryURL = ltrim( str_replace( '*', '', $url ), '/' );
                $dir = dirname( $queryURL );
                if ( $dir == '.' )
                    $dir = '';
                $glob = basename( $queryURL );
                $parentList[] = array( 'url' => $dir,
                                       'glob' => $glob,
                                       'org_url' => $url );
            }
        }

        // As long as we have urls to generate or parents to check we loop
        while ( count( $generateList ) > 0 || count( $parentList ) > 0 )
        {
            // First generate single urls
            foreach ( $generateList as $generateURL )
            {
                if ( !$quiet && $cli )
                    $cli->output( "Caching Url: $generateURL ", false );
                $this->cacheURL( $generateURL, false, !$force, $delay, $debug );
                if ( !$quiet and $cli )
                    $cli->output( "done" );
            }
            $generateList = array();

            // Then check for more data
            $newParentList = array();
            foreach ( $parentList as $parentURL )
            {
                if ( isset( $parentURL['parent_id'] ) )
                {
                    $elements = eZURLAliasML::fetchByParentID( $parentURL['parent_id'], true, true, false );
                    foreach ( $elements as $element )
                    {
                        $path = '/' . $element->getPath();
                        $generateList[] = $path;
			$searchIdItem = $this->searchForId( $element->attribute( 'id' ), $parentList );
			if ( $searachIdItem == null )
                        $newParentList[] = array( 'parent_id' => $element->attribute( 'id' ) );
                    }
                }
                else
                {
                    if ( !$quiet and $cli and $parentURL['glob'] )
                        $cli->output( "wildcard cache: " . $parentURL['url'] . '/' . $parentURL['glob'] . "*" );
                    $elements = eZURLAliasML::fetchByPath( $parentURL['url'], $parentURL['glob'] );
                    foreach ( $elements as $element )
                    {
                        $path = '/' . $element->getPath();
                        $generateList[] = $path;
			$searchIdItem = $this->searchForId( $element->attribute( 'id' ), $parentList );
			if ( $searachIdItem == null )
                        $newParentList[] = array( 'parent_id' => $element->attribute( 'id' ) );
                    }
                }
            }
            $parentList = $newParentList;
//	    print_r($parentList);
        }
    }

function searchForId($id, $array) {
   foreach ($array as $key => $val) {
       if ($val['uid'] === $id) {
           return $key;
       }
   }
   return null;
}

    function generateCacheDeprecated($force = false, $quiet = false, $cli = false, $subtreeUrl = '/', $maxLevel = 0, $debug = true )
    {
        // $subtree = trim( $subtreeUrl, '/' );
        $subtreeMessage = $subtreeUrl;

        if ( $subtreeUrl == '/' )
        {
            $subtree = '';
            $subtreeMessage = '/';
        }
        else {
            $subtree = trim( $subtreeUrl, '/' );
        }

        $subtreeLevel = BCGenerateStaticCache::level( $subtree );

        if ( !$quiet && $cli )
            $cli->output( 'Using Subtree: ' . $subtreeMessage . '  level: ' . $subtreeLevel );

        $pageArray = array();

        if ( !$quiet && $cli && $debug )
            $cli->output( 'Input Level: ' . $maxLevel . '  Detected Level: ' . $subtreeLevel );

        if ( $subtreeLevel == $maxLevel )
        {
            if ( !$quiet && $cli )
                $cli->output( 'Caching: ' . $subtreeUrl . '  level: '.$subtreeLevel );

            // only the page indicated by $subtree
            $this->cacheUrlSubtree( $subtreeUrl, !$force );
        }
        elseif ( $maxLevel > $subtreeLevel )
        {
            // a real subtree
            $db = eZDB::instance();
            $queryLike = $db->escapeString( $subtree . '%' );

            $aliasQuery = "SELECT source_url FROM ezurlalias
                                                    WHERE source_url LIKE '$queryLike'
                                                          AND source_url NOT LIKE '%*'
                                                    ORDER BY source_url";
        if ( !$quiet && $cli && $debug )
            $cli->output( "Alias Query: $aliasQuery" );

            $aliasArray = $db->arrayQuery( $aliasQuery );
            $urlCount = count( $aliasArray );
            $currentURL = 0;

            // print_r( $aliasArray ); echo "\n\n";

            foreach( $aliasArray as $aliasInfo )
            {
                $currentURL++;
                $url = $aliasInfo['source_url'];
                $level = BCGenerateStaticCache::level( $url );
                if ( $level <= $maxLevel )
                {
                    if ( !$quiet && $cli )
                        $cli->output( sprintf("   %5.1f%% Caching $url", 100 * ($currentURL / $urlCount)));
                    $this->cacheUrlSubtree( $url, !$force );
                }
            }

        }
        else
        {
            // nothing to do
            return ;
        }

    }

    /**
     * Generates the caches for the url $url using the currently configured storageDirectory().
     *
     * @param string $url The URL to cache, e.g /news
     * @param int|false $nodeID The ID of the node to cache, if supplied it will also cache content/view/full/xxx.
     * @param bool $skipExisting If true it will not unlink existing cache files.
     * @return bool
     */
    public function cacheURL( $url, $nodeID = false, $skipExisting = false, $delay = true, $debug = false )
    {
        // Check if URL should be cached
        if ( substr_count( $url, "/") >= $this->maxCacheDepth )
            return false;

        $doCacheURL = false;
        foreach ( $this->cachedURLArray as $cacheURL )
        {
            if ( $url == $cacheURL )
            {
                $doCacheURL = true;
                break;
            }
            else if ( strpos( $cacheURL, '*') !== false )
            {
                if ( strpos( $url, str_replace( '*', '', $cacheURL ) ) === 0 )
                {
                    $doCacheURL = true;
                    break;
                }
            }
        }

        if ( $doCacheURL == false )
        {
            return false;
        }
        //print_r($this->staticStorageDir);
        $this->storeCache( $url, $this->staticStorageDir, $nodeID ? array( "/content/view/full/$nodeID" ) : array(), $skipExisting, $delay, $debug );

        return true;
    }

    function cacheUrlSubtree($url, $skipUnlink)
    {
        $ini = eZINI::instance();
        $hostname = $ini->variable( 'SiteSettings', 'SiteURL' );

        $iniStatic = eZINI::instance( 'staticcache.ini');
        $staticStorageDir = $iniStatic->variable( 'CacheSettings', 'StaticStorageDir' );
        $CachedSiteAccesses = $iniStatic->variable( 'CacheSettings', 'CachedSiteAccesses' );

        if ( is_array( $CachedSiteAccesses ) and count ( $CachedSiteAccesses ) )
        {
            $dirs = array();
            foreach ( $CachedSiteAccesses as $dir )
                $dirs[] = '/' . $dir ;
        }
        else
            $dirs = array ('/');

        foreach ( $dirs as $dir )
        {
            $file = '';
            if ( !is_dir( $dir ) )
                eZDir::mkdir( $dir, 0777, true );

            $file = $this->buildCacheFilename( $staticStorageDir, $dir . '/' . $url );

            if ( !$skipUnlink || !file_exists( $file ) )
            {
                $fileName = "http://$hostname/$url";

                // print_r( $url );

                $content = $this->storeCache( $url, $staticStorageDir ); // BCGenerateStaticCache::fileGetContents( $fileName );

                // print_r( $content );

                if ( $content === false )
                    eZDebug::writeNotice( 'Could not grab content, is the hostname correct and Apache running?', 'Static Cache' );
                else
                    $this->storeCachedFile( $file, $content );
            }
        }
    }


    /**
     * Generates a cache directory parts including path, siteaccess name, site URL
     * depending on the match order type.
     *
     * @param string $siteAccess
     * @return array
     */
    private function buildCacheDirPath( $siteAccess )
    {
        $dirParts = array();

        $ini = eZINI::instance();

        $matchOderArray = $ini->variableArray( 'SiteAccessSettings', 'MatchOrder' );

        foreach ( $matchOderArray as $matchOrderItem )
        {
            switch ( $matchOrderItem )
            {
                case 'host_uri':
                    foreach ( $ini->variable( 'SiteAccessSettings', 'HostUriMatchMapItems' ) as $hostUriMatchMapItem )
                    {
                        $parts = explode( ';', $hostUriMatchMapItem );

                                 if ( $parts[2] === $siteAccess  )
                        {
                            $dirParts[] = $this->buildCacheDirPart( ( $parts[0] ? '/' . $parts[0] : '' ) .
                                                                    ( $parts[1] ? '/' . $parts[1] : '' ), $siteAccess );
                        }
                    }
                    break;
                case 'host':
                    foreach ( $ini->variable( 'SiteAccessSettings', 'HostMatchMapItems' ) as $hostMatchMapItem )
                    {
                        $parts = explode( ';', $hostMatchMapItem );

                        if ( $parts[1] === $siteAccess && $parts[0] === $ini->variable( 'SiteSettings', 'SiteURL' ) )
                        {
                            $dirParts[] = $this->buildCacheDirPart( ( $parts[0] ? '/' . $parts[0] : '' ), $siteAccess );
                        }
                    }
                    break;
                default:
                    $dirParts[] = $this->buildCacheDirPart( '/' . $siteAccess, $siteAccess );
                    break;
            }
        }

        return $dirParts;
    }

    /**
     * A helper method used to create directory parts array
     *
     * @param string $dir
     * @param string $siteAccess
     * @return array
     */
    private function buildCacheDirPart( $dir, $siteAccess )
    {
        return array( 'dir' => $dir,
                      'access_name' => $siteAccess,
                      'site_url' => eZSiteAccess::getIni( $siteAccess, 'site.ini' )->variable( 'SiteSettings', 'SiteURL' ) );
    }

    /**
     * This function adds an action to the list that is used at the end of the
     * request to remove and regenerate static cache files.
     *
     * @param string $action
     * @param array $parameters
     */
    private function addAction( $action, $parameters )
    {
        self::$actionList[] = array( $action, $parameters );
    }

    /**
     * Stores the static cache for $url and hostname defined in site.ini.[SiteSettings].SiteURL for cached siteaccess
     * by fetching the web page using {@link eZHTTPTool::getDataByURL()} and storing the fetched HTML data.
     *
     * @param string $url The URL to cache, e.g /news
     * @param string $staticStorageDir The base directory for storing cache files.
     * @param array $alternativeStaticLocations
     * @param bool $skipUnlink If true it will not unlink existing cache files.
     * @param bool $delay
     */
    private function storeCache( $url, $staticStorageDir, $alternativeStaticLocations = array(), $skipUnlink = false, $delay = false, $debug = false )
    {
        $dirs = array();

        foreach ( $this->cachedSiteAccesses as $cachedSiteAccess )
        {
            $dirs[] = $this->buildCacheDirPath( $cachedSiteAccess );
        }

//        print_r($this->cachedSiteAccesses);
//        print_r( $dirs );

        foreach ( $dirs as $dirParts )
        {
            foreach ( $dirParts as $dirPart )
            {
                $dir = $dirPart['dir'];
                $siteURL = $dirPart['site_url'];

                $cacheFiles = array();

                $cacheFiles[] = $this->buildCacheFilename( $staticStorageDir, $dir . $url );
                foreach ( $alternativeStaticLocations as $location )
                {
                    $cacheFiles[] = $this->buildCacheFilename( $staticStorageDir, $dir . $location );
                }

                // print_r( $cacheFiles );

                // Store new content
                $content = false;
                foreach ( $cacheFiles as $file )
                {
                    if ( $debug && file_exists( $file ) )
                    {
                        print_r( "\nNote: Static cache already exists in file system @ $file\n" );
                    }

                    if ( $debug && !$skipUnlink )
                    {
                        print_r( "Using force to regenerate static cache\n" );
                    }

                    if ( !$skipUnlink || !file_exists( $file ) )
                    {
                        // Deprecated since 4.4, will be removed in future version
                        $fileName = "http://{$this->hostName}{$dir}{$url}";

                        // staticcache.ini.[CacheSettings].HostName has been deprecated since version 4.4
                        // hostname is read from site.ini.[SiteSettings].SiteURL per siteaccess
                        // defined in staticcache.ini.[CacheSettings].CachedSiteAccesses
                        if ( !$this->hostName )
                        {
                            $fileName = "http://{$siteURL}{$url}";
                        }

                        if ( $delay )
                        {
                            $this->addAction( 'store', array( $file, $fileName ) );
                        }
                        else
                        {
                            if ( $debug && $fileName )
                            {
                                print_r( "\nNote: Url call to fetch static cache content: $fileName\n" );
                            }

                            // Generate content, if required
                            if ( $content === false )
                            {
                                if ( eZHTTPTool::getDataByURL( $fileName, true, BCGenerateStaticCache::USER_AGENT ) )
                                    $content = eZHTTPTool::getDataByURL( $fileName, false, BCGenerateStaticCache::USER_AGENT );

                            // print_r( $content );

                            }
                            if ( $content === false )
                            {
                                eZDebug::writeError( "Could not grab content (from $fileName), is the hostname correct and Apache running?", 'Static Cache' );
                            }
                            else
                            {
                                if ( $debug && $file )
                                {
                                    print_r( "Storing static cache file: $file \n");
                                }

                                self::storeCachedFile( $file, $content );
                            }
                        }
                    }
                }
            }
        }
    }



    private function buildCacheFilename( $staticStorageDir, $url )
    {
        $file = "{$staticStorageDir}{$url}/index.html";
        $file = preg_replace( '#//+#', '/', $file );
        return $file;
    }

    function fileGetContents( $url )
    {
        $fp = fopen( $url, 'r' );
        if ( ! $fp )
            return false;
        $meta_data = stream_get_meta_data( $fp );

        // print_r( $meta_data['wrapper_data'][0] ); echo "\n\n\n";

        if( preg_match('/^HTTP\/1.1 30[12]/', $meta_data['wrapper_data'][0] ) )
            return false;
        $tmp = '';
        $result = '';
        while ( ( $tmp = fgets( $fp, 4096 ) ) !== false )
            $result .= $tmp;
        return $result;
    }
}

?>