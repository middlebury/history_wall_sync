<?php

require_once (dirname(__FILE__).'/../include/init.php');

if (!empty($_GET['sort']) && in_array($_GET['sort'], array_keys($FLICKR_SORT_KEYS))) {
	$sort = $_GET['sort'];
} else {
	$sort = 'date-posted-desc';
}

?>
<html>
<head>
	<meta charset="UTF-8">
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
	'sort' => $sort,
);


ob_start();
print "\n<form class='sort' action='all.php' method='GET'><label for='sort'>Sort by: </label>";
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