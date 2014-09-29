	<div id='category_area'>
		<h4>Allowed Wall Categories</h4>
		<ul id='categories'>
			<li><?php print implode("</li>\n\t\t\t<li>", $WALL_CATEGORIES); ?></li>
		</ul>
	</div>
	<a href='index.php'>&laquo; Back</a>
	<div id='messages'><?php print implode("\n<br>", $messages); ?></div>