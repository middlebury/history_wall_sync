<?php

require_once (dirname(__FILE__).'/../phpflickr/phpFlickr.php');
require_once (dirname(__FILE__).'/../include/PhotoSearchIterator.php');
require_once (dirname(__FILE__).'/../include/PhotoSetIterator.php');
require_once (dirname(__FILE__).'/../include/PhotoPrinter.php');
require_once (dirname(__FILE__).'/../config.php');


$flickr = new phpFlickr($FLICKR_API_KEY);

// Set up caching
if (!empty($FLICKR_API_CACHE_TYPE)) {
	if (empty($FLICKR_API_CACHE_LIFETIME) || !is_int($FLICKR_API_CACHE_LIFETIME)) {
		$FLICKR_API_CACHE_LIFETIME = 600;
	}
	if ($FLICKR_API_CACHE_TYPE == 'fs') {
		if (!file_exists($FLICKR_API_CACHE_LOCATION)) {
			mkdir($FLICKR_API_CACHE_LOCATION);
		}
	}
	$flickr->enableCache($FLICKR_API_CACHE_TYPE, $FLICKR_API_CACHE_LOCATION, $FLICKR_API_CACHE_LIFETIME);	
}