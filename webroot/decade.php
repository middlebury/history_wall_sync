<?php

require_once (dirname(__FILE__).'/../include/init.php');

$decade = floor(intval($_GET['decade'])/10) * 10;
if ($decade >= 1910) {
	$min_date = $decade.'-01-01 00:00:00';
	$max_date = ($decade + 9).'-12-30 23:59:59';
} else {
	$decade = 1900;
	$min_date = null;
	$max_date = '1909-12-30 23:59:59';
}

if (!empty($_GET['sort']) && in_array($_GET['sort'], array_keys($FLICKR_SORT_KEYS))) {
	$sort = $_GET['sort'];
} else {
	$sort = 'date-taken-asc';
}

?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Decade <?php print $decade;?>s Photos Report</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
	<?php include(dirname(__FILE__).'/header.php'); ?>

	<h1>Middlebury Flickr - History Wall Report</h1>
	<h2>Decade: <?php print $decade;?>s &nbsp; (<?php print $min_date.' to '.$max_date; ?>)</h2>

	<div class='decades'>
	<?php
		foreach ($DECADES as $dec) {
			print "\n\t\t<a href='decade.php?decade=".$dec."'>".$dec."s</a> &nbsp; ";
		}
	?>
	</div>

<?php

$args = array(
	'user_id' => $FLICKR_USERID,
	'min_taken_date' => $min_date,
	'max_taken_date' => $max_date,
	'sort' => $sort,
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

ob_start();
print "\n<form class='sort' action='decade.php' method='GET'><label for='sort'>Sort by: </label>";
print "\n\t<input type='hidden' name='decade' value='".$decade."'/>";
print "\n\t<select name='sort' onchange='this.form.submit();'>";
foreach ($FLICKR_SORT_KEYS as $key => $label) {
	print "\n\t\t<option value='".$key."' ".(($key == $sort)?" selected='selected'":"").">".$label."</option>";
}
print "\n\t</select>";
print "\n</form>";
$sort_html = ob_get_clean();

$printer = new PhotoPrinter($WALL_CATEGORIES);
$printer->addHeadHTML($sort_html);
$printer->output(
	new PhotoSearchIterator($flickr, $args, $printer->getStartingPhotoOffset(), 100)
);

?>

</body>
</html>