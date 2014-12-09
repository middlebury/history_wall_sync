<?php

require_once (dirname(__FILE__).'/../include/init.php');

?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Middlebury Flickr - History Wall reports</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>

	<h1>Middlebury Flickr - History Wall  reports</h1>
	<p>Below are reports of the parsed output of the Flickr archive that will eventually be sent to the History Wall. These reports are presented separately to allow easier location and evaluation of various groups of images.</p>

	<h2>Single Image</h2>
	<form action="single.php" method="GET">
		<label for="photo">Paste the photo's Flickr id or URL in the text area and submit to see the report for just that image:</label>
		<br><input type="text" name="photo" size="80">
		<input type="submit" value="Show Report"/>
	</form>

	<h2>Full-text search</h2>
	<form action="search.php" method="GET">
		<input type="text" name="query" size="80">
		<input type="submit" value="Search"/>
	</form>

	<h2>All Images</h2>
	<p>This report will show a paginated list of all images in the Flickr photostream.</p>
	<ul><li><a href="all.php">All Images</a></li></ul>

	<h2>By Decade</h2>
	<p>Choose a decade to view the report for all images with taken-dates in that decade.</p>
	<p class='decades'>
	<?php
		foreach ($DECADES as $dec) {
			print "\n\t\t<a href='decade.php?decade=".$dec."'>".$dec."s</a> &nbsp; ";
		}
	?>
	</p>

	<h2>By Album</h2>
	<p>Choose an album to see the report for all images in the album.</p>
	<ul class='albums'>
<?php
$photosets = $flickr->photosets_getList($FLICKR_USERID, null, null, 'url_sq,path_alias');
foreach ($photosets['photoset'] as $photoset) {
	$photoset = (object)$photoset;
	print "\n<li>";
	print "\n\t<a href='https://www.flickr.com/photos/".$photoset->primary_photo_extras['pathalias']."/sets/".$photoset->id."/'>";
	print "\n\t\t<img src='".$photoset->primary_photo_extras['url_sq']."'/>";
	print "\n\t</a>";
	print "\n\t<h4>";
	print "\n\t\t<a href='album.php?photoset=".$photoset->id."'>";
	print htmlspecialchars(implode(' ', $photoset->title));
	print "\n\t\t</a>";
	print " (".$photoset->photos." photos)";
	print "\n\t</h4>";
	print "\n\t<p>".nl2br(htmlspecialchars(implode(' ', $photoset->description)))."</p>";
	print "\n</li>";
}
?>
	</ul>
</body>
</html>

<?php

// foreach ($FLICKR_EXAMPLE_PHOTOS as $photo_id) {
// 	$photo = $flickr->photos_getInfo($photo_id);
// 	var_dump($photo);
// }

// $photos = $flickr->photos_search(array(
// 	'user_id' => $FLICKR_USERID,
// 	'extras' => 'description,date_taken,url_o,tags,machine_tags',
// 	'per_page' => 5,
// // 	'page' => 1,
// ));
// var_dump($photos);


// $args = array(
// 	'user_id' => $FLICKR_USERID,
// 	'extras' => 'description,date_taken,url_o,tags,machine_tags',
// );
// $photos = new PhotoSearchIterator($flickr, $args, 20, 10);
// var_dump(count($photos));
// for ($i = 20; $i <= 30; $i++) {
// 	print $i;
// 	var_dump($photos[$i]);
// }
//
// print "<hr/>";
// var_dump($photos);


// $args = array(
// 	'photoset_id' => $FLICKR_EXAMPLE_PHOTOSET,
// 	'extras' => 'description,date_taken,url_o,tags,machine_tags',
// );
// $photos = new PhotoSetIterator($flickr, $args, 0, 100);
// var_dump(count($photos));
// foreach($photos as $photo) {
// 	var_dump($photo);
// }
//
// print "<hr/>";
// var_dump($photos);


// $args = array(
// 	'user_id' => $FLICKR_USERID,
// 	'extras' => 'description,date_taken,url_o,tags,machine_tags',
// );
// $pager = new phpFlickr_pager($flickr, 'flickr.photos.search', $args);
//
// for ($i = 0; $i < 1; $i++) {
// 	var_dump ($pager->next());
// }


