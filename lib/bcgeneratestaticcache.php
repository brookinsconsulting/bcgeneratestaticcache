<?php
// SOFTWARE NAME: bcgeneratestaticcache
// COPYRIGHT NOTICE: Copyright (C) 2007 Damien POBEL and 1999 - 2014 Brookins Consulting
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.

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

    function generateCache($force = false, $quiet = false, $cli = false, $subtree = '/', $maxLevel = 0)
    {
        // $subtree = trim( $subtree, '/' );
        $subtreeMessage = $subtree;

        if ( $subtree == '/' )
        {
            $subtree = '';
            $subtreeMessage = '/';
        }
        else {
            $subtree = trim( $subtree, '/' );
        }

        $subtreeLevel = BCGenerateStaticCache::level( $subtree );

        if ( !$quiet && $cli )
            $cli->output( 'Using Subtree: ' . $subtreeMessage . '  level: '.$subtreeLevel );

        $pageArray = array();

        if ( $subtreeLevel == $maxLevel )
        {
            // only the page indicated by $subtree
            $this->cacheUrlSubtree( $subtree, !$force );
            if ( !$quiet && $cli )
                $cli->output( '  Caching: ' . $subtree );
        }
        elseif ( $maxLevel > $subtreeLevel )
        {
            // a real subtree
            $db = eZDB::instance();
            $queryLike = $db->escapeString( $subtree . '%' );
            $aliasArray = $db->arrayQuery( "SELECT source_url FROM ezurlalias
                                                    WHERE source_url LIKE '$queryLike'
                                                        AND source_url NOT LIKE '%*'
                                                    ORDER BY source_url" );
            $urlCount = count( $aliasArray );
            $currentURL = 0;
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
    private function storeCache( $url, $staticStorageDir, $alternativeStaticLocations = array(), $skipUnlink = false, $delay = false )
    {
        $dirs = array();

        foreach ( $this->cachedSiteAccesses as $cachedSiteAccess )
        {
            $dirs[] = $this->buildCacheDirPath( $cachedSiteAccess );
        }

        // print_r( $dirs );

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
                            // Generate content, if required
                            if ( $content === false )
                            {
                                if ( eZHTTPTool::getDataByURL( $fileName, true, eZecosystemStaticCache::USER_AGENT ) )
                                    $content = eZHTTPTool::getDataByURL( $fileName, false, eZecosystemStaticCache::USER_AGENT );

                            // print_r( $content );

                            }
                            if ( $content === false )
                            {
                                eZDebug::writeError( "Could not grab content (from $fileName), is the hostname correct and Apache running?", 'Static Cache' );
                            }
                            else
                            {
                                // print_r( $file );
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