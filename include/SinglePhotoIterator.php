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
class SinglePhotoIterator extends PhotoIterator {
		
	protected $photo;
		
	/**
	 * Constructor
	 * 
	 * @param The photo.
	 * @access public
	 */
	public function __construct ($photo) {
		$this->photo = $photo;
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
	 * Iterator interface methods
	 *********************************************************/

    public function valid() {
        return ($this->position === 0);
    }
    
    /*********************************************************
     * ArrayAccess methods
     *********************************************************/
    
    public function offsetSet($offset, $value) {
        throw new Exception('This object is not writeable.');
    }
    
    public function offsetExists($offset) {
        return ($offset === 0);
    }
    
    public function offsetUnset($offset) {
        throw new Exception('This object is not writeable.');
    }
    
    public function offsetGet($offset) {
        return $this->photo;
    }
    
    /*********************************************************
     * Countable methods
     *********************************************************/
    
    public function count ($mode = COUNT_NORMAL) {
        return 1;
    }
}

