<?php
/**
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */

/**
 * An iterator class for loading lists of images
 *
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */
class MultiPhotoIterator extends PhotoIterator {

	protected $flickr = null;
	protected $photo_ids = array();
	protected $fetches = 0;

	/**
	 * Constructor
	 *
	 * @param The photo.
	 * @access public
	 */
	public function __construct (phpFlickr $flickr, array $photo_ids) {
		$this->flickr = $flickr;
		$this->photo_ids = $photo_ids;
	}

	/**
	 * Fetch a single photo's info.
	 *
	 * @return object
	 */
	protected function _fetchPhoto($photo_id) {
		$this->fetches++;
		if ($this->fetches > 11) {
			exit;
		}
		$photo_id = intval($photo_id);
		$photos = $this->flickr->photos_getInfo($photo_id);
		$photo = (object)$photos['photo'];
		$sizes = $this->flickr->photos_getSizes($photo_id);

		// Set up the photo object to match the search result extras
		$photo->title = implode(' ', $photo->title);
		$photo->datetaken = $photo->dates['taken'];

		$tags = array();
		foreach ($photo->tags['tag'] as $tag) {
			$tags[] = $tag['_content'];
		}
		$photo->tags = implode(' ', $tags);

		if (!empty($sizes[2])) {
			$photo->url_t = $sizes[2]['source'];
		}
		if (!empty($sizes[11])) {
			$photo->url_0 = $sizes[11]['source'];
		}

		return $photo;
	}

	/**
	 * Fetch a page of results
	 *
	 * @param int $pagenum
	 * @return array
	 * @access protected
	 */
	protected function _fetch_page ($pagenum) {
		// do nothing.
	}

	/*********************************************************
	 * ArrayAccess methods
	 *********************************************************/

	public function offsetExists($offset) {
		return ($offset >= 0 && $offset < count($this->photo_ids));
	}

	public function offsetGet($offset) {
		return $this->_fetchPhoto($this->photo_ids[$offset]);
	}

	/*********************************************************
	 * Countable methods
	 *********************************************************/

	public function count ($mode = COUNT_NORMAL) {
		return count($this->photo_ids);
	}
}
