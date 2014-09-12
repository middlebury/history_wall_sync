<?php

require_once (dirname(__FILE__).'/../include/init.php');

$photoset_id = intval($_GET['photoset']);
if (empty($photoset_id))
	throw new Exception("No photoset specified.");
$photoset = (object)$flickr->photosets_getInfo($photoset_id);

?>
<html>
<head>
	<title>Album Report - <?php print htmlspecialchars(implode(' ', $photoset->title)); ?></title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>
	
	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Album: <?php print htmlspecialchars(implode(' ', $photoset->title)); ?></h2>
	
<?php

$args = array(
	'photoset_id' => $photoset_id,
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->output(
	new PhotoSetIterator($flickr, $args, $printer->getStartingPhotoOffset(), 100)
);

?>

</body>
</html>