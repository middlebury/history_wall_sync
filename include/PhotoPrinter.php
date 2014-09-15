<?php
/**
 * @package history_wall_sync
 * 
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */ 

require_once(dirname(__FILE__).'/FlickrWallPhoto.php');

/**
 * An iterator class for performing photo searches.
 * 
 * @package history_wall_sync
 * 
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */
class PhotoPrinter {

	protected $num_per_page;
	protected $categories;
	protected $head_html = '';

	/**
	 * Constructor
	 * 
	 * @param optional int $num_per_page
	 * @access public
	 */
	public function __construct (array $categories, $num_per_page = 100) {
		if (!is_int($num_per_page) || $num_per_page < 0)
			throw new Exception('$num_per_page must be a positive integer, '.$num_per_page.' given.');
		$this->num_per_page = $num_per_page;
		$this->categories = $categories;
	}
	
	/**
	 * Add some HTML to the pagination head element.
	 * 
	 * @param string $html
	 * @access public
	 */
	public function addHeadHtml ($html) {
		$this->head_html = $html;
	}
	
	/**
	 * Answer the current page number
	 * 
	 * @return int
	 * @access public
	 */
	public function getCurrentPage () {
		if (!empty($_GET['page']) && intval($_GET['page']) > 0)
			return intval($_GET['page']);
		else
			return 1;
	}
	
	/**
	 * Answer the zero-based offset of the first image on this page.
	 * 
	 * @return int
	 * @access public
	 */
	public function getStartingPhotoOffset () {
		return $this->num_per_page * ($this->getCurrentPage() - 1);
	}
	
	/**
	 * Print out the table for a PhotoIterator
	 * 
	 * @param PhotoIterator $photos
	 * @return null
	 * @access public
	 */
	public function output (PhotoIterator $photos) {
		$columns = $this->getColumnHeaders();
		print "\n<table border='1' class='report'>";
		
		print "\n\t<thead>";
		// Pager
		$pager = $this->getPager(count($photos));
		print "\n\t\t<tr>";
		print "\n\t\t\t<td colspan='".count($columns)."' class='pager'>";
		print $this->head_html;
		print "\n\t\t\t\t<div class='pager'>".$pager."</div>";
		print "\n\t\t\t</td>";
		print "\n\t\t<tr>";
		// Headings
		print "\n\t\t<tr>";
		foreach ($columns as $class => $column) {
			print "\n\t\t\t<th class='".$class."'>".$column."</th>";
		}
		print "\n\t\t<tr>";
		print "\n\t</thead>";
		
		print "\n\t<tbody>";
		$last_photo_offset = min(count($photos) - 1, $this->getStartingPhotoOffset() + $this->num_per_page - 1);
		for ($i = $this->getStartingPhotoOffset(); $i <= $last_photo_offset; $i++) {
			print "\n\t\t<tr>";
			$photo = $photos[$i];
			foreach ($columns as $class => $column) {
				print "\n\t\t\t<td class='".$class."'>".$this->getPhotoDatum($class, $photo)."</td>";
			}
			print "\n\t\t</tr>";
		}
		print "\n\t</tbody>";
		
		print "\n\t<tfoot>";
		// Pager
		print "\n\t\t<tr>";
		print "\n\t\t\t<td colspan='".count($columns)."' class='pager'>";
		print $this->head_html;
		print "\n\t\t\t\t<div class='pager'>".$pager."</div>";
		print "\n\t\t\t</td>";
		print "\n\t\t<tr>";
		print "\n\t</tfoot>";
		
		print "\n</table>";
	}
	
	/**
	 * Answer a pager.
	 * 
	 * @param $total
	 * @return string
	 * @access protected
	 */
	protected function getPager ($total) {
		$links = array();
		$path = $_SERVER['SCRIPT_NAME'];
		parse_str($_SERVER['QUERY_STRING'], $args);
		
		$pages = ceil($total / $this->num_per_page);
		$current = $this->getCurrentPage();
		for ($i = 1; $i <= $pages; $i++) {
			if ($i == $current) {
				$links[] = $i;
			} else {
				$args['page'] = $i;
				$url = $path.'?'.http_build_query($args, '', '&amp;');
				$links[] = '<a href="'.$url.'">'.$i.'</a>';
			}
		}
		return 'Page: '.implode(' ', $links);
	}
	
	/**
	 * Anser the column headers.
	 * 
	 * @return array
	 * @access protected
	 */
	protected function getColumnHeaders () {
		return array(
			'thumbnail' => 'Thumbnail',
			'title' => 'Title',
			'description' => 'Description',
			'date' => 'Date',
			'categories' => 'Categories',
			'crop' => 'Crop',
			'warnings' => 'Warnings',
// 			'raw' => 'Raw',
		);
	}
	
	/**
	 * Answer a datum for a photo
	 * 
	 * @param string $field
	 * @param object $photo
	 * @return string
	 * @access protected
	 */
	protected function getPhotoDatum ($field, $photo) {
		$wall_photo = new FlickrWallPhoto($photo, $this->categories);
		switch ($field) {
			case 'thumbnail':
				return '<a href="https://www.flickr.com/photos/middarchive/'.$photo->id.'" target="_blank"><img src="'.$photo->url_t.'"></a>';
			case 'title':
				return $wall_photo->getTitle();
			case 'description':
				return nl2br($wall_photo->getDescription());
			case 'date':
				return $wall_photo->getDate();
			case 'categories':
				return '<ul><li>'.implode("</li><li>", $wall_photo->getCategories()).'</li></ul>';
			case 'crop':
				return '<dl><dt>H-Crop:</dt><dd>'.$wall_photo->getHCrop().'</dd><dt>V-Crop</dt><dd>'.$wall_photo->getVCrop().'</dd></dl>';
			case 'warnings':
				return '<p>'.implode("</p>\n<p>", $wall_photo->getWarnings()).'</p>';
			case 'raw':
				ob_start();
				var_dump($photo);
				return ob_get_clean();
			default:
				return 'unknown field "'.$field.'"';
		}
	}
}