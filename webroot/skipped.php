<?php

require_once (dirname(__FILE__).'/../include/init.php');

?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Skipped Photos Report</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>

	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Images skipped last sync</h2>

<?php
if (!empty($REMOTE_SKIPPED_FILE)) {
	$file = $REMOTE_SKIPPED_FILE;
} else {
	$file = dirname(__FILE__).'/skipped.txt';
}
if (!file_exists($file)) {
	print "<p class='error'>No record of skipped files exists.</p>";
} else {
	$skipped_contents = file_get_contents($file);
	if (empty($skipped_contents)) {
		print "<p class='error'>No skipped files are listed.</p>";
	} else {
		$photo_ids = explode("\n", trim($skipped_contents));
		print "<p>".count($photo_ids)." images were skipped due to data errors during the last sync on ".date('c', filemtime($file))."</p>";
		$printer = new PhotoPrinter($WALL_CATEGORIES, 10);
		$printer->output(
			new MultiPhotoIterator($flickr, $photo_ids, $printer->getStartingPhotoOffset(), 10)
		);
	}
}


?>

</body>
</html>
