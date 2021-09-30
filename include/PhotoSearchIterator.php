<?php
/**
 * @since 9/11/14
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 *
 * @version $Id$
 */

require_once(dirname(__FILE__).'/PhotoIterator.php');

/**
 * An iterator class for performing photo searches.
 *
 * @since 9/11/14
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 *
 * @version $Id$
 */
class PhotoSearchIterator extends PhotoIterator {

	protected $flickr;
	protected $search_args;

	/**
	 * Constructor
	 *
	 * @param phpFlickr $flickr
	 *		The phpFlickr API object.
	 * @param array $search_args
	 *		Arguments to pass to photo.search.
	 * 		See: https://www.flickr.com/services/api/flickr.photos.search.html
	 * @param optional int $starting_photo
	 *		A photo offset to start fetching on. Prevents loading the first page if
	 *		it won't be accessed.
	 * @param optional int $perpage
	 *		The number of results to fetch in each load.
	 * @access public
	 * @since 9/11/14
	 */
	public function __construct (phpFlickr $flickr, array $search_args, $starting_photo = 0, $perpage = 100) {
		$this->flickr = $flickr;
		$this->search_args = $search_args;

		parent::__construct($starting_photo, $perpage);
	}

	/**
	 * Answer some debug info about our current state.
	 *
	 * @return string
	 * @access public
	 */
	public function get_debug () {
		return parent::get_debug()." search_args=".print_r($this->search_args, true)." last_response=".print_r($this->flickr->response, true);
	}

	/**
	 * Fetch a page of results
	 *
	 * @param int $pagenum
	 * @return array
	 * @access protected
	 * @since 9/11/14
	 */
	protected function _fetch_page ($pagenum) {
		if ($pagenum < 1)
			throw new Exception('$pagenum must be greater than 0, '.$pagenum.' given.');

		$args = array_merge(
			$this->search_args,
			array(
				'per_page' => $this->perpage,
				'page' => $pagenum,
			)
		);
		sleep(1);
		return $this->flickr->photos_search($args);
	}
}
