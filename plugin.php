<?php
class CDNsunModxCdnIntegration
{
    /*
     * Plugin for MODX CDN integration.
     * The plugin replaces source URLs of your images, CSS and JS to CDN URLs. The plugin
     * replaces URL only if the corresponding CDN URL returns 200 response code.
     * To increase the performance the plugin does not replace (and test) URLs on the fly but
     * it creates an array of already successful replacement pairs and stores the array
     * in the local MODX cache. Please note that as a consequence of this  
     * to "reset the plugin" you need to flush the local MODX cache.
     * 
     * Please note that the plugin replaces only relative URLs, so for example
     * it will replace src="/photos/photo.jpg" but it will not replace src="http://domain.com/photos/photo.jpg"
     * 
     * To configure the plugin just adjust the $this->_cdnServiceUrl variable in the __construct function below
     * 
     */
    
    private $_cdnServiceUrl;             // http(s)://service_domain or http(s)://service_identifier (your CDN base URL)
    private $_replacementMap;            // associative array (stored in the local MODX cache)
                                         // of before_replacement (origin URL) => after_replacement pairs (CDN URL)
                                         // for replacement of image, CSS and JS source URLs to CDN URLs
    private $_replacementMapExpiryTime;  // expiry time of the _replacementMap (in seconds) in the local MODX cache
                                         // this ensures that the _replacementMap does not expire too often
                                         // and thus increases the performance

    public function __construct(&$modx) 
    {
        $this->modx = $modx;

        // http(s)://service_domain or http(s)://service_identifier (your CDN domain)
        $this->_cdnServiceUrl = 'http://static.mycompany.com';

        // expiry time of the _replacementMap in the local MODX cache (in seconds)
        $this->_replacementMapExpiryTime = 86400; // 1 day (you don't need to change this)

        // get the _replacementMap from the local MODX cache
        $this->_replacementMap = $this->modx->cacheManager->get('CDNsunModxCdnIntegrationReplacementMap');
        if(!is_array($this->_replacementMap)) 
        {
            // the first run or the _replacementMap has expired or the local MODX cache has been flushed
            $this->_replacementMap = array();
        }
    }

    public function replaceURLs($output) 
    {
        // adjust images
        $output = preg_replace_callback('|<img(?:.+?)src\=\"(\S+)\"|', array($this ,'_getURL'), $output);
        // adjust CSS
        $output = preg_replace_callback('|<link(?:.+?)href\=\"(\S+)\"|', array($this ,'_getURL'), $output);
        // adjust JS
        $output = preg_replace_callback('|<script(?:.+?)src\=\"(\S+)\"|', array($this ,'_getURL'), $output);
                
        // update the local MODX cache
        $this->modx->cacheManager->set('CDNsunModxCdnIntegrationReplacementMap', $this->_replacementMap, $this->_replacementMapExpiryTime);

        return $output;
    }

    private function _getURL($match) 
    {
        if( !stripos($match[1], '.js') && 
            !stripos($match[1], '.css') && 
            !stripos($match[1], '.jpg') && 
            !stripos($match[1], '.jpeg') && 
            !stripos($match[1], '.png') && 
            !stripos($match[1], '.gif') || 
            (stripos($match[1], '//') !== false) // for example http://
          ) 
        {
            return $match[0];
        } 
        else 
        {
            // URL to replace
            $replace = $match[1]; 
            if(array_key_exists($replace, $this->_replacementMap)) 
            {                
              $replaced = $this->_replacementMap[$replace];
              return str_replace($replace, $replaced, $match[0]);
            } 
            else 
            {
                // create CDN URL for the asset
                $replaced = $this->_cdnServiceUrl . (substr($match[1], 0, 1) == '/' ? '' : '/') . $match[1]; 
                if($this->_testURL($replaced)) 
                {
                    // add to the local MODX cache
                    $this->_replacementMap[$replace] = $replaced; 
                    return str_replace($replace, $replaced, $match[0]);
                } 
                else 
                {
                    return $match[0];
                }
            }
        }
    }

    private function _testURL($link) 
    {
        $urlParts = @parse_url($link);
        if(empty($urlParts["host"])) 
        {
            return false;
        }
        if(!empty($urlParts["path"])) 
        {
            $documentPath = $urlParts["path"];
        } 
        else 
        {
            $documentPath = "/";
        }
        if(!empty($urlParts["query"])) 
        {
            $documentPath .= "?" . $urlParts["query"];
        }
        $host = $urlParts["host"];
        $port = $urlParts["port"];
        if(empty($port)) 
        {
            $port = "80";
        }

        $socket = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) 
        {
            return false;
        } 
        else 
        {
            fwrite($socket, "HEAD " . $documentPath . " HTTP/1.0\r\nHost: $host\r\n\r\n");
            $httpResponse = fgets($socket, 22);
            if(stripos($httpResponse, "200 OK")) 
            {
                fclose($socket);
                return true;                
            } 
            else 
            {
                fclose($socket);
                return false;
            }
        }
    }
}

$output = &$modx->resource->_output;
$cdn = new CDNsunModxCdnIntegration($modx);
$output = $cdn->replaceURLs($output);

// END CODE
