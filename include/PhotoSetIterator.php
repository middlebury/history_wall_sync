<?php
/**
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 *
 */

require_once(dirname(__FILE__).'/PhotoIterator.php');

/**
 * An iterator class for performing photo searches.
 *
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 *
 */
class PhotoSetIterator extends PhotoIterator {

	protected $flickr;
	protected $photoset_id;

	/**
	 * Constructor
	 *
	 * @param phpFlickr $flickr
	 *		The phpFlickr API object.
	 * @param int $search_args
	 *		Arguments to pass to photos.getPhotos()
	 * 		See: https://www.flickr.com/services/api/flickr.photosets.getPhotos.html
	 *		Required: photoset_id
	 *		Optional: extras, privacy_filter, media
	 * @param optional int $starting_photo
	 *		A photo offset to start fetching on. Prevents loading the first page if
	 *		it won't be accessed.
	 * @param optional int $perpage
	 *		The number of results to fetch in each load.
	 * @access public
	 */
	public function __construct (phpFlickr $flickr, array $search_args, $starting_photo = 0, $perpage = 100) {
		$this->flickr = $flickr;
		$this->search_args = $search_args;

		parent::__construct($starting_photo, $perpage);
	}

	/**
	 * Fetch a page of results
	 *
	 * @param int $pagenum
	 * @return array
	 * @access protected
	 */
	protected function _fetch_page ($pagenum) {
		if ($pagenum < 1)
			throw new Exception('$pagenum must be greater than 0, '.$pagenum.' given.');

		if (empty($this->search_args['photoset_id']))
			throw new Exception('photoset_id is a required argument for the PhotoSetIterator.');
		if (empty($this->search_args['extras']))
			$this->search_args['extras'] = NULL;
		if (empty($this->search_args['privacy_filter']))
			$this->search_args['privacy_filter'] = NULL;
		if (empty($this->search_args['media']))
			$this->search_args['media'] = NULL;

		$results = $this->flickr->photosets_getPhotos(
			$this->search_args['photoset_id'],
			$this->search_args['extras'],
			$this->search_args['privacy_filter'],
			$this->perpage,
			$pagenum,
			$this->search_args['media']
		);

		return($results['photoset']);
	}
}
