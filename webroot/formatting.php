<?php
require_once (dirname(__FILE__).'/../include/init.php');
?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Middlebury Flickr - History Wall formatting details</title>
	<link rel="stylesheet" href="report.css" type="text/css" />
</head>
<body>
  <?php include(dirname(__FILE__).'/header.php'); ?>

	<h1>Middlebury Flickr - History Wall Formatting Details</h1>
	<p>The History wall has a number of limitation on the data that can be entered. Images that have data that can not fit in the wall will not be synced to the Wall CMS</p>

  <h2>1. Titles</h2>
  <a name="title-length"/>
  <h3>1.1. Title Length</h3>
  <p>Titles in the Wall can be at most 66 characters, so images in Flickr must have titles that are less than 66 characters long.</p>

  <h2>2. Descriptions</h2>
  <a name="description-length"/>
  <h3>2.1. Description Length</h3>
  <p>Descriptions in the Wall can be at most 240 characters.</p>
  <p><strong>Note:</strong> You <em>can</em> add more than 240 characters of text to an image description in Flickr, a useful tool if there is interesting caption information that simply can't fit in 240 characters. To include an extended description longer than 240 characters, separate it from the short description using a new-line followed by four dashes ("----") followed by another new-line as shown in this example:</p>
  <div class="description_example">A short description of the image that is less than 240 characters. Lorem ipsum dolar domit somehting lesee.

----

This is the extended description that can be as long as you want. It can go on for many paragraphs and include much more information.

We're still going with this longer description. It won't be visible in the Wall, just in Flickr.</div>

  <a name="description-quotes"/>
  <h3>2.2. Quotations in descriptions</h3>
  <p>Quotations in the wall have special formatting considerations, so when adding a quotation to an image in Flickr, it must follow a particular convention to be valid. First, the quotation itself must start and end with double-quote characters. Second, the attribute line must follow on a new-line that begins with two dashes. Here is an example of a valid quote in a Flickr image description:</>
  <div class="description_example">"Two roads diverged in a yellow wood, And sorry I could not travel both..."

-- Robert Frost, from "The Road Not Taken"</div>

  <p>Also note that the quotation and attribution (including quotation marks, new-lines, and dashes) must be less than 240 characters.</p>

  <h3>2.2. Quotations and descriptive captions</h3>
  <p>As with extended descriptions, you can add additional descriptive details to the Flickr image description after a 4-dash break. This will let the quote go to the wall without additional caption information. Example:</p>
  <div class="description_example">"Two roads diverged in a yellow wood, And sorry I could not travel both..."

-- Robert Frost, from "The Road Not Taken"

----

This is the extended description that can be as long as you want. It can go on for many paragraphs and include much more information.

We're still going with this longer description. It won't be visible in the Wall, just in Flickr.</div>

  <a name="categories-tags"/>
  <h2>3. Categories</h3>
  <p>"Categories" in the Wall CMS map to Flickr "Tags". Images must have at least one valid Category/Tag to be synced to the wall -- they would never be seen if they didn't have any.</p>
  <p>The following are the list of valid Categories/Tags defined in the Wall CMS:</p>
  <?php if (!empty($messages)) { print '<p class="error">Error: '.implode("; ", $messages). "</p>"; } ?>
  <?php print '<ul><li>'.implode("</li><li>", $WALL_CATEGORIES).'</li></ul>'; ?>


</body>
</html>
