<?php
/**
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */

/**
 * An iterator class for performing photo searches.
 *
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */
abstract class PhotoIterator implements Iterator, ArrayAccess, Countable {

	protected $total_photos;
	protected $photos = array();
	protected $total_pages;
	protected $loaded_pages = array();
	protected $perpage = 100;

	protected $postition = 0;
	protected $starting_photo = 0;

	/**
	 * Constructor
	 *
	 * @param optional int $starting_photo
	 *		A photo offset to start fetching on. Prevents loading the first page if
	 *		it won't be accessed.
	 * @param optional int $perpage
	 *		The number of results to fetch in each load.
	 * @access public
	 */
	public function __construct ($starting_photo = 0, $perpage = 100) {
		$this->perpage = $perpage;

		if (!is_int($starting_photo) || $starting_photo < 0)
			throw new Exception ('$starting_photo must be a positive integer, '.$starting_photo.' given.');
		$this->starting_photo = $starting_photo;
	}

	/**
	 * Answer the page number a photo offset will be found on.
	 *
	 * @param int $photo_offset
	 * @return int
	 * @access protected
	 */
	protected function _get_photo_page_num ($photo_offset) {
		return floor($photo_offset / $this->perpage) + 1;
	}

	/**
	 * Load a page of results
	 *
	 * @param int $pagenum
	 * @return null
	 * @access protected
	 */
	protected function _load_page ($pagenum = 1) {
		if ($pagenum < 1)
			throw new Exception('$pagenum must be greater than 0, '.$pagenum.' given.');
		if (isset($total_pages) && $pagenum > $total_pages)
			throw new Exception('$pagenum must be less than the resulting number of pages: '.$total_pages.', '.$pagenum.' given.');
		if (in_array($pagenum, $this->loaded_pages)) {
			// We've already loaded this page, so just skip it.
			return;
		}

		$result = $this->_fetch_page($pagenum);

		// Set up our totals if haven't been initialized yet.
		if (!isset($this->total_pages)) {
			$this->total_pages = intval($result['pages']);
			$this->total_photos = intval($result['total']);

			// check for a case where the total photos isn't a positive int.
			if ($this->total_pages > 1 && $this->total_photos < 1)
				throw new Exception('Found '.$result['pages'].' pages, but a non-integer number of photos: '.$result['total']);

			$this->photos = array_pad(array(), $this->total_photos, NULL);
		}

		// Insert our photos from the result
		$page_offset = ($pagenum - 1) * $this->perpage;
		foreach ($result['photo'] as $offset => $photo) {
			$photo_key = $page_offset + $offset;
			$this->photos[$photo_key] = (object)$photo;
		}
		// Mark our page as loaded.
		$this->loaded_pages[] = $pagenum;
	}

	/**
	 * Fetch a page of results
	 *
	 * @param int $pagenum
	 * @return array
	 * @access protected
	 */
	abstract protected function _fetch_page ($pagenum);

	/*********************************************************
	 * Iterator interface methods
	 *********************************************************/

	function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->offsetGet($this->position);
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return $this->offsetExists($this->position);
    }

    /*********************************************************
     * ArrayAccess methods
     *********************************************************/

    public function offsetSet($offset, $value) {
        throw new Exception('This object is not writeable.');
    }

    public function offsetExists($offset) {
        if (!isset($this->total_photos)) {
        	$this->_load_page($this->_get_photo_page_num($this->starting_photo));
        }
        return ($offset >= 0 && $offset < $this->total_photos);
    }

    public function offsetUnset($offset) {
        throw new Exception('This object is not writeable.');
    }

    public function offsetGet($offset) {
        if (empty($this->photos[$offset])) {
        	$this->_load_page($this->_get_photo_page_num($offset));
        }
        return $this->photos[$offset];
    }

    /*********************************************************
     * Countable methods
     *********************************************************/

    public function count ($mode = COUNT_NORMAL) {
        if (!isset($this->total_photos)) {
        	$this->_load_page($this->_get_photo_page_num($this->starting_photo));
        }
    	return $this->total_photos;
    }
}

