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
$file_exists = FALSE;
if (!empty($REMOTE_SKIPPED_FILE)) {
	$file = $REMOTE_SKIPPED_FILE;
	if (!preg_match('#^https?://.+#', $file)) {
		print "<p class='error'>I only understand http[s]:// URLS for \$REMOTE_SKIPPED_FILE.</p>";
	} else {
		$headers = get_headers($file, 1);
		if (preg_match('/^HTTP.+200 OK$/', $headers[0])) {
			$date = strtotime($headers['Date']);
			$mod_time = date('r', $date);
			$file_exists = TRUE;
			$skipped_contents = file_get_contents($file);
		}
	}

} else {
	$file = dirname(__FILE__).'/skipped.txt';
	$file_exists = file_exists($file);
	if ($file_exists) {
		$mod_time = date('r', filemtime($file));
		$skipped_contents = file_get_contents($file);
	}
}
if (!$file_exists) {
	print "<p class='error'>No record of skipped files exists.</p>";
} else if (empty($skipped_contents)) {
	print "<p class='error'>No skipped files are listed.</p>";
} else {
	$photo_ids = explode("\n", trim($skipped_contents));
	print "<p>".count($photo_ids)." images were skipped due to data errors during the last sync on ".$mod_time."</p>";
	$printer = new PhotoPrinter($WALL_CATEGORIES, 10);
	$printer->output(
		new MultiPhotoIterator($flickr, $photo_ids, $printer->getStartingPhotoOffset(), 10)
	);
}

?>

</body>
</html>
