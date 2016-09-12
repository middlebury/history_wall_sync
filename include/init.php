<?php

require_once (dirname(__FILE__).'/../phpflickr/phpFlickr.php');
require_once (dirname(__FILE__).'/../include/PhotoIterator.php');
require_once (dirname(__FILE__).'/../include/SinglePhotoIterator.php');
require_once (dirname(__FILE__).'/../include/MultiPhotoIterator.php');
require_once (dirname(__FILE__).'/../include/PhotoSearchIterator.php');
require_once (dirname(__FILE__).'/../include/PhotoSetIterator.php');
require_once (dirname(__FILE__).'/../include/PhotoPrinter.php');
require_once (dirname(__FILE__).'/../include/PhotoUpdater.php');
require_once (dirname(__FILE__).'/../include/ArgumentParser.php');
require_once (dirname(__FILE__).'/../include/Mailer.php');
require_once (dirname(__FILE__).'/../config.php');

$messages = array();

$flickr = new phpFlickr($FLICKR_API_KEY, $FLICKR_API_SECRET);
// Use an authentication token instead of making anonymous requests.
if (!empty($FLICKR_AUTH_TOKEN)) {
	$flickr->setToken($FLICKR_AUTH_TOKEN);
}

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

// Set up the image cache
if (empty($IMAGE_CACHE_DIR)) {
	throw new Exception('$IMAGE_CACHE_DIR must be set.');
} else {
	if (!file_exists($IMAGE_CACHE_DIR)) {
		mkdir($IMAGE_CACHE_DIR);
	}
	if (!is_dir($IMAGE_CACHE_DIR) || !is_writeable($IMAGE_CACHE_DIR)) {
		throw new Exception('$IMAGE_CACHE_DIR, \''.$IMAGE_CACHE_DIR.'\' must be a writeable directory.');
	}
}

/*********************************************************
 * Load categories from the wall CMS.
 *********************************************************/
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

if (empty($WALL_BASE_URL)) {
	$messages[] = 'No $WALL_BASE_URL specified, cannot load categories from CMS, using static list instead.';
} else {
	$category_cache = $IMAGE_CACHE_DIR.'/categories.cache';
	if (!file_exists($category_cache) || filemtime($category_cache) < time() - 60) {
		file_put_contents($category_cache, file_get_contents($WALL_BASE_URL.'api/tag/'));
	}
	if (!file_exists($category_cache) || !is_readable($category_cache)  || !filesize($category_cache)) {
		$messages[] = 'Could not cache the category listing, using static list instead.';
	} else {
		$categories = json_decode(file_get_contents($category_cache), TRUE);
		if (empty($categories['data'])) {
			$messages[] = 'Could not decode the category listing, using static list instead.';
		} else {
			$WALL_CATEGORIES = array(); // Clear the static list.
			foreach ($categories['data'] as $category) {
				$WALL_CATEGORIES[] = $category['name'];
			}
		}
	}
}
sort($WALL_CATEGORIES);

/*********************************************************
 * Define decades
 *********************************************************/

$DECADES = array();
$now = intval(date('Y'));
for ($i = 1900; $i <= $now; $i = $i + 10) {
	$DECADES[] = $i;
}

$FLICKR_SORT_KEYS = array(
	'date-posted-asc' => 'Date Uploaded - Ascending',
	'date-posted-desc' => 'Date Uploaded - Descending',
	'date-taken-asc' => 'Date Taken - Ascending',
	'date-taken-desc' => 'Date Taken - Descending',
	'relevance' => 'Search Relevance',
);
