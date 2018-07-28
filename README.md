# MODX CDN Plugin
Plugin for MODX CDN integration

    /*
     * Plugin for MODX CDN integration.
     * The plugin replaces source URLs of images, CSS and JS to CDN URLs. The plugin
     * replaces URL only if the corresponding CDN URL returns 200 response code.
     * To increase the performance the plugin does not replace (and test) URLs on the fly but
     * it creates an array of already successful replacement pairs and stores the array
     * in the local MODX cache. Please note that as a consequence of this  
     * to "reset the plugin" you need to flush the local MODX cache.
     * 
     * Please note that the plugin replaces only relative URLs, so for example
     * it will replace src="/photos/photo.jpg" but it will not replace src="http://domain.com/photos/photo.jpg"
     * 
     * To configure the plugin just adjust the $this->_cdnServiceUrl variable in the __construct function
     * 
     */

    More details: http://cdnsun.com/knowledgebase/integrations/modx-cdn-integration
    
CONTACT

* W: https://cdnsun.com
* E: info@cdnsun.com
