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
	protected $wall_base_url;

	/**
	 * Constructor
	 * 
	 * @access public
	 */
	public function __construct (array $categories, $image_cache_dir, $wall_config) {
		$this->categories = $categories;
		$this->image_cache_dir = $image_cache_dir;
		if (empty($wall_config['base_url']))
			throw new InvalidArgumentException('$wall_config[\'base_url\'] must be specified.');
		
		$this->wall_base_url = $wall_config['base_url'];
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
				$this->updateCmsPhoto($flickr_photo, $cms_photo);
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
		$this->loadCmsPhotos();
		if (isset($this->cms_photo_map[$flickr_id]))
			return $this->cms_photo_map[$flickr_id];
		else
			return FALSE;
	}
	
	/**
	 * Load photo-info from the CMS.
	 * 
	 * @access protected
	 */
	protected function loadCmsPhotos () {
		if (!isset($this->cms_photos)) {
			$cms_url = $this->wall_base_url.'api/grid/';
			$json = file_get_contents($cms_url);
			if (empty($json))
				throw new Exception('Could not load the list of grid images from the CMS at '.$cms_url);
			$results = json_decode($json);
			$this->cms_photos = $results->data;
			$this->cms_photo_map = array();
			foreach ($this->cms_photos as $cms_id => $cms_photo) {
				// add the id to the object so we can reference it later
				$cms_photo->id = $cms_id;
				// Map this photo to the flickr_id for lookup.
				if (!empty($cms_photo->flickr_id)) {
					$this->cms_photo_map[$cms_photo->flickr_id] = $cms_photo;
				}
			}
		}
	}
	
	/**
	 * Create a new CmsPhoto from a flickr photo
	 * 
	 * @param FlickrWallPhoto $flickr_photo
	 * @return null
	 * @access protected
	 */
	protected function createCmsPhoto (FlickrWallPhoto $flickr_photo) {
		print "Creating ".$flickr_photo->getId()." \"".$flickr_photo->getTitle()."\"\n";
		
		$cms_url = $this->wall_base_url.'admin/grid/new/';
		
		$data = $this->getCmsPostFields($flickr_photo);
		
		// Check field lengths
		if (strlen($data['title']) > 66) {
			print "ERROR: Title exceeded 66 characters -- skipping upload.\n\n";
			return;
		}
		if (strlen($data['description']) > 240) {
			print "ERROR: Description exceeded 240 characters -- skipping upload.\n\n";
			return;
		}
		
		$this->postToCms($cms_url, $data);
		
		print "\n";
	}
	
	/**
	 * Update a CmsPhoto from a flickr photo
	 * 
	 * @param FlickrWallPhoto $flickr_photo
	 * @return null
	 * @access protected
	 */
	protected function updateCmsPhoto (FlickrWallPhoto $flickr_photo, $cms_photo) {
		print "Evaluating for update: ".$flickr_photo->getId()." \"".$flickr_photo->getTitle()."\"\n";
		
		$cms_url = $this->wall_base_url.'admin/grid/edit/?url=%2Fadmin%2Fgrid%2F&id='.$cms_photo->id;
		
		$data = $this->getCmsPostFields($flickr_photo);
		
		// Check field lengths
		if (strlen($data['title']) > 66) {
			print "ERROR: Title exceeded 66 characters -- skipping upload.\n\n";
			return;
		}
		if (strlen($data['description']) > 240) {
			print "ERROR: Description exceeded 240 characters -- skipping upload.\n\n";
			return;
		}
		
		// Identify if the image has changed, skip if unchanged.
		$changed = FALSE;
		if ($cms_photo->title != $data['title']) {
			print "	title has changed.\n";
			$changed = TRUE;
		}
		if ($cms_photo->description != $data['description']) {
			print "	description has changed.\n";
			$changed = TRUE;
		}
		if ($cms_photo->decade != $data['decade']) {
			print "	decade has changed.\n";
			$changed = TRUE;
		}
// 		if ($cms_photo->active != $data['active']) {
//			print "	title has changed.\n";
// 			$changed = TRUE;
// 		}
		if ($cms_photo->image_date != $data['image_date']) {
			print "	image_date has changed.\n";
			$changed = TRUE;
		}
		if ($cms_photo->h_crop != $data['h_crop']) {
			print "	h_crop has changed.\n";
			$changed = TRUE;
		}
		if ($cms_photo->w_crop != $data['w_crop']) {
			print "	w_crop has changed.\n";
			$changed = TRUE;
		}
		$categories = $flickr_photo->getCategories();
		sort($categories);
		sort($cms_photo->tags);
		if ($cms_photo->tags != $categories) {
			print "	categories have changed.\n";
			$changed = TRUE;
		}
		$cms_updated_at = new DateTime($cms_photo->updated_at, new DateTimeZone('UTC'));
		if ($cms_updated_at < $flickr_photo->getLastUpdateDate()) {
			print "	the Flickr update date is later than the CMS update date.\n";
			$changed = TRUE;
		}
		
		if ($changed) {
			print "Changes detected, updating...\n";
			$this->postToCms($cms_url, $data);
		} else {
			print "No changes detected, skipping update.\n";
		}
		
		print "\n";
	}
	
	/**
	 * Post data to the CMS
	 * 
	 * @param string $cms_url The URL to POST to
	 * @param array $data The data to POST
	 * @return boolean TRUE on success
	 * @access protected
	 */
	protected function postToCms ($cms_url, $data) {
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
		
		return TRUE;
	}
	
	/**
	 * Answer an array of data fields to send to the CMS.
	 * 
	 * @param FlickrWallPhoto $flickr_photo
	 * @return array
	 * @access protected
	 */
	protected function getCmsPostFields (FlickrWallPhoto $flickr_photo) {
		$temp_file = $this->downloadFlickrImage($flickr_photo);
		$extension = pathinfo($temp_file, PATHINFO_EXTENSION);
		$mimetype = image_type_to_mime_type(exif_imagetype($temp_file));
		$filename = basename($temp_file);
		
		return array(
			'flickr_id' => $flickr_photo->getId(),
			'active' => 'y',
			'title' => $flickr_photo->getTitle(),
			'description' => $flickr_photo->getDescription(),
			'tag_list' => implode(',', $flickr_photo->getCategories()),
			'h_crop' => $flickr_photo->getVCrop(),
			'w_crop' => $flickr_photo->getHCrop(),
			'image_date' => $flickr_photo->getDate(),
			'decade' => $flickr_photo->getDecade(),
			'image_file' => '@'.$temp_file.';type='.$mimetype.';filename='.$flickr_photo->getId().'.'.$extension,
		);
	}
	
	/**
	 * Download the image file for a flickr photo.
	 * 
	 * @param FlickrWallPhoto $flickr_photo
	 * @return string The temporary file name.
	 * @access protected
	 */
	protected function downloadFlickrImage (FlickrWallPhoto $flickr_photo) {
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
		
		return $temp_file;
	}
}