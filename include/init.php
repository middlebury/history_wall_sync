<?php

require_once (dirname(__FILE__).'/../phpflickr/phpFlickr.php');
require_once (dirname(__FILE__).'/../include/PhotoIterator.php');
require_once (dirname(__FILE__).'/../include/SinglePhotoIterator.php');
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

$WALL_CATEGORIES = array(
	"Women's Basketball",
	"Men's Basketball",
	"Baseball",
	"Softball",
	"Women's Cross Country",
	"Men's Cross Country",
	"Field Hockey",
	"Football",
	"Women's Golf",
	"Men's Golf",
	"Women's Indoor Track",
	"Men's Indoor Track",
	"Women's Hockey",
	"Men's Hockey",
	"Women's Soccer",
	"Men's Soccer",
	"Women's Skiing",
	"Men's Skiing",
	"Women's Lacrosse",
	"Men's Lacrosse",
	"Women's Squash",
	"Men's Squash",
	"Women's Track and Field",
	"Men's Track and Field",
	"Women's Tennis",
	"Men's Tennis",
	"Women's Swimming and Diving",
	"Men's Swimming and Diving",
	"Volleyball",
	"Champions",
	"Traditions",
	"Women in Sports",
	"Team Photo",
	"Celebrations",
	"Recreational Sports",
	"Facilities",
	"Headlines ",
	"Coaches",
	"Community and Service",
	"Rivalries",
	"Olympians and Pros",
	"Club Sports",
	"Cheer and Spirit",
	"Legacy",
	"Campus Life",
	"Intramurals",
	"Ephemera",
	"Illustrations",			
);

$DECADES = array(1800);
$now = intval(date('Y'));
for ($i = 1900; $i <= $now; $i = $i + 10) {
	$DECADES[] = $i;
}