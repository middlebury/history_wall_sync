<?php
/**
 * @package history_wall_sync
 * 
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */ 

require_once(dirname(__FILE__).'/FlickrWallPhoto.php');

/**
 * A
 * 
 * @package history_wall_sync
 * 
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */
class PhotoUpdater {

	protected $categories;
	protected $image_cache_dir;

	/**
	 * Constructor
	 * 
	 * @access public
	 */
	public function __construct (array $categories, $image_cache_dir) {
		$this->categories = $categories;
		$this->image_cache_dir = $image_cache_dir;
	}
	
	/**
	 * Print out the table for a PhotoIterator
	 * 
	 * @param PhotoIterator $photos
	 * @return null
	 * @access public
	 */
	public function update (PhotoIterator $photos) {
		foreach ($photos as $photo) {
			$flickr_photo = new FlickrWallPhoto($photo, $this->categories);
			$cms_photo = $this->getCmsPhoto($flickr_photo->getId());
			if ($cms_photo) {
				$this->updateCmsPhoto($cms_photo, $flickr_photo);
			} else {
				$this->createCmsPhoto($flickr_photo);
			}
		}
	}
	
	/**
	 * Look up a CMS photo by id.
	 * 
	 * @param string $flickr_id
	 * @return mixed object or FALSE
	 * @access protected
	 */
	protected function getCmsPhoto ($flickr_id) {
		// for now, just return FALSE
		// @todo: do the lookup so that we can update.
		return FALSE;
	}
	
	/**
	 * Create a new CmsPhoto from a flickr photo
	 * 
	 * @param FlickrWallPhoto $flickr_photo
	 * @return null
	 * @access protected
	 */
	protected function createCmsPhoto (FlickrWallPhoto $flickr_photo) {
		print "Processing ".$flickr_photo->getId()." \"".$flickr_photo->getTitle()."\"\n";
		
		$flickr_photo_url = $flickr_photo->getLargeUrl();
		$filename = basename($flickr_photo_url);
		$temp_file = realpath($this->image_cache_dir).'/'.$filename;
		
		// Download the image_file temporarily
		if (!file_exists($temp_file) || !filesize($temp_file)) {
			print "Downloading from flickr:\n\tFrom:\t".$flickr_photo_url."\n\tTo:\t".$temp_file."\n";
			$flickr_photo_handle = fopen($flickr_photo_url, 'rb');
			
			if (!$flickr_photo_handle)
				throw new Exception("Could not open $flickr_photo_handle for reading.");
			$temp_file_handle = fopen($temp_file, 'wb');
			if (!$temp_file_handle)
				throw new Exception("Could not open $temp_file for writing.");
			
			while (!feof($flickr_photo_handle)) {
				fwrite($temp_file_handle, fread($flickr_photo_handle, 8192));
			}
			fclose($flickr_photo_handle);
			fclose($temp_file_handle);
		}
		// Verify that we have the image file.
		if (!file_exists($temp_file) || !filesize($temp_file)) {
			throw new Exception("Couldn't download the photo from ".$flickr_photo_url." to ".$temp_file);
		}
		
		$cms_url = 'http://middlebury.dev.localprojects.net/admin/grid/new/';
		$data = array(
			'active' => 'y',
			'title' => $flickr_photo->getTitle(),
			'description' => $flickr_photo->getDescription(),
			'tag_list' => implode(',', $flickr_photo->getCategories()),
			'h_crop' => $flickr_photo->getHCrop(),
			'v_crop' => $flickr_photo->getVCrop(),
			'image_date' => $flickr_photo->getDate(),
			'decade' => $flickr_photo->getDecade(),
			'image_file' => '@'.$temp_file.';type=image/jpeg;filename='.$filename,
		);
		
		if (strlen($data['title']) > 66) {
			$data['title'] = substr($data['title'], 0, 66);
			print "WARNING: Title exceeded 66 characters and had been truncated.\n";
		}
		if (strlen($data['description']) > 240) {
			$data['description'] = substr($data['description'], 0, 240);
			print "WARNING: Description exceeded 240 characters and had been truncated.\n";
		}
		
		$curl_options = array(
			CURLOPT_URL => $cms_url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTP_VERSION  => 1.0,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_VERBOSE => false,
			CURLOPT_HEADER => false,
		);
// 		var_dump($data);
		print "Uploading to CMS...";
		$curl = curl_init();
		curl_setopt_array( $curl, $curl_options );
		$result = curl_exec( $curl );
		if (curl_errno($curl)) {
			print "Error uploading to CMS: ".curl_error($curl)."\n";
		}
		$response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close ($curl);
		
		if ($response_code == 302) {
			print "   ...done.\n";
		} else if ($response_code == 413) {
			print "   ...ERROR:\n".$result;
			// Then continue.
		} else {
			print "   ...ERROR:\n".$result;
			exit(2);
		}
		

		print "\n";
	}
}