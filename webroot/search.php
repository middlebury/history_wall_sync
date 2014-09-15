<?php

require_once (dirname(__FILE__).'/../include/init.php');

$term = $_GET['query'];

if (!empty($_GET['sort']) && in_array($_GET['sort'], array_keys($FLICKR_SORT_KEYS))) {
	$sort = $_GET['sort'];
} else {
	$sort = 'relevance';
}

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
	'sort' => $sort,
	'extras' => 'description,date_taken,url_o,url_t,tags,machine_tags',
);

ob_start();
print "\n<form class='sort' action='search.php' method='GET'><label for='sort'>Sort by: </label>";
print "\n\t<input type='hidden' name='query' value='".$term."'/>";
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