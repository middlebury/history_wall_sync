<?php

require_once (dirname(__FILE__).'/../include/init.php');

$photoset_id = intval($_GET['photoset']);
if (empty($photoset_id))
	throw new Exception("No photoset specified.");
$photoset = (object)$flickr->photosets_getInfo($photoset_id);

?>
<html>
<head>
	<title>Album Report: Middlebury Flickr - History Wall</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<a href='index.php'>&laquo; Back</a>
	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Album: <?php print implode(' ', $photoset->title); ?></h2>
	
<?php

$categories = array(
	'Women’s Basketball',
	'Men’s Basketball',
	'Baseball',
	'Softball',
	'Women’s Cross Country',
	'Men’s Cross Country',
	'Field Hockey',
	'Football',
	'Women’s Golf',
	'Men’s Golf',
	'Women’s Indoor Track',
	'Men’s Indoor Track',
	'Women’s Hockey',
	'Men’s Hockey',
	'Women’s Soccer',
	'Men’s Soccer',
	'Women’s Skiing',
	'Men’s Skiing',
	'Women’s Lacrosse',
	'Men’s Lacrosse',
	'Women’s Squash',
	'Men’s Squash',
	'Women’s Track and Field',
	'Men’s Track and Field',
	'Women’s Tennis',
	'Men’s Tennis',
	'Women’s Swimming and Diving',
	'Men’s Swimming and Diving',
	'Volleyball',
	'Champions',
	'Traditions',
	'Women in Sports',
	'Team Photo',
	'Celebrations',
	'Recreational Sports',
	'Facilities',
	'Headlines ',
	'Coaches',
	'Community and Service',
	'Rivalries',
	'Olympians and Pros',
	'Club Sports',
	'Cheer and Spirit',
	'Legacy ',
	'Campus Life',
	'Intramurals',
	'Ephemera',
	'Illustrations',			
);

$args = array(
	'photoset_id' => $photoset_id,
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

$printer = new PhotoPrinter($categories);
$printer->output(
	new PhotoSetIterator($flickr, $args, $printer->getStartingPhotoOffset(), 100)
);

?>

</body>
</html>