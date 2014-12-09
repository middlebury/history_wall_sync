<?php

require_once (dirname(__FILE__).'/../include/init.php');

if (empty($_GET['photo']))
	throw new Exception("No photo specified.");

if (!preg_match('#^(?:https?://www.flickr.com/photos/[a-z]+/)?([0-9]+)(?:/.*)?$#', $_GET['photo'], $m))
	throw new Exception("The value specified doesn't look look like a photo id (integer) or photo URL.");

$photo_id = intval($m[1]);
$photos = $flickr->photos_getInfo($photo_id);
$photo = (object)$photos['photo'];
$sizes = $flickr->photos_getSizes($photo_id);

// Set up the photo object to match the search result extras
$photo->title = implode(' ', $photo->title);
$photo->datetaken = $photo->dates['taken'];

$tags = array();
foreach ($photo->tags['tag'] as $tag) {
	$tags[] = $tag['_content'];
}
$photo->tags = implode(' ', $tags);

$photo->url_t = $sizes[2]['source'];
$photo->url_0 = $sizes[11]['source'];

// var_dump($photo);


?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Single Photo Report - <?php print htmlspecialchars($photo->title); ?></title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>

	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Single Photo: <?php print htmlspecialchars($photo->title); ?></h2>


<?php


$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->output(new SinglePhotoIterator($photo));

?>

</body>
</html>