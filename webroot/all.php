<?php

require_once (dirname(__FILE__).'/../include/init.php');

?>
<html>
<head>
	<title>All Photos Report</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>
	
	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>All Photos</h2>
	
<?php

$args = array(
	'user_id' => $FLICKR_USERID,
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->output(
	new PhotoSearchIterator($flickr, $args, $printer->getStartingPhotoOffset(), 100)
);

?>

</body>
</html>