<?php

require_once (dirname(__FILE__).'/../include/init.php');

$term = $_GET['query'];

?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Search "<?php print htmlentities($term);?>" Photos Report</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>
	
	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Search: <?php print htmlentities($term);?></h2>
	
<?php

$args = array(
	'user_id' => $FLICKR_USERID,
	'text' => $term,
	'sort' => 'relevance',
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->output(
	new PhotoSearchIterator($flickr, $args, $printer->getStartingPhotoOffset(), 100)
);

?>

</body>
</html>