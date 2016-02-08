<?php

require_once (dirname(__FILE__).'/../include/init.php');

if (empty($_GET['photo']))
	throw new Exception("No photo specified.");

if (!preg_match('#^(?:https?://www.flickr.com/photos/[a-z]+/)?([0-9]+)(?:/.*)?$#', $_GET['photo'], $m))
	throw new Exception("The value specified doesn't look look like a photo id (integer) or photo URL.");

$photo_id = intval($m[1]);


// var_dump($photo);


?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Single Photo Report - <?php print htmlspecialchars($photo_id); ?></title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>

	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Single Photo: <?php print htmlspecialchars($photo_id); ?></h2>


<?php


$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->output(new MultiPhotoIterator($flickr, array($photo_id)));

?>

</body>
</html>
