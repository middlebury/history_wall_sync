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

	public $verbose = true;
	protected $categories;
	protected $image_cache_dir;
	protected $wall_base_url;
	protected $wall_auth_token = null;
	protected $source_flickr_ids;

	protected $total_evaluated = 0;
	protected $num_skipped = 0;
	protected $num_no_changes = 0;
	protected $num_updated = 0;
	protected $num_created = 0;
	protected $num_deleted = 0;
	protected $num_errors = 0;

	protected $skipped = array();

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
		$this->wall_auth_token = $wall_config['auth_token'];
		$this->source_flickr_ids = array();
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
			$this->total_evaluated++;
			try {
				ob_start();
				if (!is_object($photo)) {
					throw new Exception('$photo is not an object. Maybe we got bad data from Flickr?. '.$photos->get_debug(), 777);
				}
				$flickr_photo = new FlickrWallPhoto($photo, $this->categories);
				$this->source_flickr_ids[] = $flickr_photo->getId();
				$cms_photo = $this->getCmsPhoto($flickr_photo->getId());
				if ($cms_photo) {
					$this->updateCmsPhoto($flickr_photo, $cms_photo);
				} else {
					$this->createCmsPhoto($flickr_photo);
				}
				if ($this->verbose) {
					ob_end_flush();
				} else {
					ob_end_clean();
				}
			} catch (Exception $e) {
				$this->num_errors++;
				ob_end_flush();
				print date('c')." ".$e->getMessage()."\n";
				if ($e->getCode() == 777) {
					// Stop if we are hitting non-objects. somthing is wrong.
					Mailer::send($e->getMessage()."\n\nFlickr URL: https://www.flickr.com/photos/middarchive/??");
					throw $e;
				} else {
					Mailer::send($e->getMessage()."\n\nFlickr URL: https://www.flickr.com/photos/middarchive/".$flickr_photo->getId());
				}
				// Stop if we have an error loading the grid image list, don't keep trying each photo.
				if ($e->getCode() == 500 || $e->getCode() == 501) {
					throw $e;
				}
			}
		}
	}

	function printSummary() {
		print date('c').sprintf(" %d Flickr Photos Evaluated, %d skipped due to data errors, %d had no changes, %d created, %d updated, %d deleted, %d errors.\n", $this->total_evaluated, $this->num_skipped, $this->num_no_changes, $this->num_created, $this->num_updated, $this->num_deleted, $this->num_errors);
	}

	/**
	 * Delete photos that were not in the input list.
	 *
	 * @return nul
	 */
	function deletePhotosNotInSource () {
		try {
			ob_start();
			print "\nComparing photos for delete...\n";

			$this->loadCmsPhotos();
			if (empty($this->cms_photo_map))
				throw new Exception('No CMS photos loaded, not deleting.');
			if (empty($this->source_flickr_ids))
				throw new Exception('No Flickr photos in search, not deleting.');

			$to_delete = array();
			foreach ($this->cms_photo_map as $flickr_id => $cms_photo) {
				if (!in_array($flickr_id, $this->source_flickr_ids)) {
					$to_delete[] = $cms_photo;
				}
			}

			if (count($to_delete) > (count($this->cms_photo_map) * 0.20))
				throw new Exception("\nWARNING!!!\nTrying to delete more than 20% of the CMS photos. Something may have gone wrong -- not deleting.");

			if (empty($to_delete)) {
				print "No photos to delete from the CMS.\n";
			} else {
				$cms_url = $this->wall_base_url.'admin/grid/delete/';
				foreach ($to_delete as $cms_photo) {
					$this->num_deleted++;
					print "Deleting ".$cms_photo->id." '".$cms_photo->title."'. Flickr id ".$cms_photo->flickr_id." wasn't in the source.\n";
					$data = array();
					$this->postToCms($cms_url.'?id='.$cms_photo->id, $data);
				}
			}
			if ($this->verbose) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}
		} catch (Exception $e) {
			ob_end_flush();
			print $e->getMessage();
		}
	}

	/**
	 * Answer an array of skipped photo-ids.
	 *
	 * @return array()
	 */
	public function getSkipped() {
		return $this->skipped;
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
			$opts = array(
				'http' => array(
					'timeout' => 1200,
				),
			);
			$context = stream_context_create($opts);
			$json = file_get_contents($cms_url, false, $context);
			if (empty($json))
				throw new Exception('Could not load the list of grid images from the CMS at '.$cms_url, 500);
			$results = json_decode($json);
			if (!is_object($results)) {
				ob_start();
				var_dump($json);
				throw new Exception('json_decode failed for '.$cms_url.' : '.ob_get_clean(), 501);
			}
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
	 * Look up a CMS asset by id.
	 *
	 * @param string $cms_asset_id
	 * @return mixed object or FALSE
	 * @access protected
	 */
	protected function getCmsAsset ($cms_asset_id) {
		// for now, just return FALSE
		$this->loadCmsAssets();
		if (isset($this->cms_assets[$cms_asset_id]))
			return $this->cms_assets[$cms_asset_id];
		else
			return FALSE;
	}

	/**
	 * Load photo-info from the CMS.
	 *
	 * @access protected
	 */
	protected function loadCmsAssets () {
		if (!isset($this->cms_assets)) {
			$cms_url = $this->wall_base_url.'api/assets/';
			$json = file_get_contents($cms_url);
			if (empty($json))
				throw new Exception('Could not load the list of asset images from the CMS at '.$cms_url);
			$results = json_decode($json);
			$assets = $results->data;
			$this->cms_assets = array();
			foreach ($assets as $asset) {
				$this->cms_assets[$asset->id] = $asset;
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

		// Check for errors
		$errors = $flickr_photo->getErrors();
		if (count($errors)) {
			$this->num_skipped++;
			$this->skipped[] = $flickr_photo->getId();
			print "Skipping import due to the following errors:\n\t";
			print implode("\n\t", $errors);
			print "\n\n";
			return;
		}

		$data = $this->getCmsMetadataPostFields($flickr_photo);
		$data['image_file'] = $this->getCmsImagePostData($flickr_photo);

		$this->num_created++;
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

		$data = $this->getCmsMetadataPostFields($flickr_photo);

		// Check for errors
		$errors = $flickr_photo->getErrors();
		if (count($errors)) {
			$this->num_skipped++;
			$this->skipped[] = $flickr_photo->getId();
			print "Skipping import due to the following errors:\n\t";
			print implode("\n\t", $errors);
			print "\n";
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
		// The form now wants dates without the time portion (00:00:00), so only compare the date and not the time.
		$cms_image_date = preg_replace('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', '\1 ', $cms_photo->image_date);
		$flickr_image_date = preg_replace('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', '\1 ', $data['image_date']);
		if ($cms_image_date != $flickr_image_date) {
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
		if (is_null($cms_photo->image)) {
			print "	the CMS doesn't have an image file, deleting and recreating...\n";
			// Delete the image
			$cms_url = $this->wall_base_url.'admin/grid/delete/';
			print "Deleting ".$cms_photo->id." '".$cms_photo->title."'. Flickr id ".$cms_photo->flickr_id." wasn't in the source.\n";
			$data = array();
			$this->postToCms($this->wall_base_url.'admin/grid/delete/?id='.$cms_photo->id, $data);
			// Recreate the image.
			return $this->createCmsPhoto($flickr_photo);
		}

		if ($changed) {
			print "Changes detected, updating...\n";
			$this->num_updated++;
			$this->postToCms($cms_url, $data);
			$this->updateCmsAsset($cms_photo->image->id, $flickr_photo);
		} else {
			$this->num_no_changes++;
			print "No changes detected, skipping update.\n";
		}

		print "\n";
	}

	/**
	 * Update an asset in the CMS by id
	 *
	 * @param string $cms_asset_id
	 * @param FlickrWallPhoto $flickr_photo
	 * @return null
	 */
	private function updateCmsAsset ($cms_asset_id, FlickrWallPhoto $flickr_photo) {
		$cms_url = $this->wall_base_url.'admin/assets/edit/?url=%2Fadmin%2Fassets%2F%3Fsort%3D3%26desc%3D1&id='.$cms_asset_id;
		$cms_asset = $this->getCmsAsset($cms_asset_id);
		$data = array(
			'title' => $cms_asset->title,
			'path_prefix' => $cms_asset->path_prefix,
			'active' => $cms_asset->active ? '1':'',
			'uri' => $this->getCmsImagePostData($flickr_photo),
		);
		$this->postToCms($cms_url, $data);
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
		if (!empty($this->wall_auth_token)) {
			$data['auth_token'] = $this->wall_auth_token;
		}

		// The form now wants dates without the time portion (00:00:00), so trim that off.
		if (!empty($data['image_date'])) {
			$data['image_date'] = preg_replace('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', '\1', $data['image_date']);
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

		// Look for an error message burried in the HTML.
		$error_message = $this->extractErrorMessageFromHtml($result);
		if (!$error_message) {
			$error_message = $result;
		}

		if ($response_code == 302) {
			print "   ...done.\n";
		} else if ($response_code == 413) {
			print "   ...ERROR:\n".(empty($error_message)?$result:$error_message);
			// Then continue.
		} else {
			print "   ...ERROR".(empty($error_message)?":\n".$result:"\n");
			throw new Exception("Error posting to the CMS: '".$error_message."'\ndata=".print_r($data, true));
		}

		return TRUE;
	}

	/**
	 * Answer an error message burried in the HTML response.
	 *
	 * @param string $html
	 * @return string
	 * @access protected
	 */
	protected function extractErrorMessageFromHtml($htmlString) {
		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		$html->loadHTML($htmlString);
		if ($html) {
			$xpath = new DOMXPath($html);
			$errors = $xpath->query('//div[@class="alert alert-error"]');
			if ($errors->length) {
				$error_messages = array();
				foreach ($errors as $error) {
					$close_links = $xpath->query('./a[@class="close"]', $error);
					foreach ($close_links as $link) {
						$error->removeChild($link);
					}
					$message = trim(strip_tags($error->nodeValue));
					if (strlen($message)) {
						$error_messages[] = $message;
					}
					return implode("\n", $error_messages);
				}
			}
		}
		return '';
	}

	/**
	 * Answer an array of data fields to send to the CMS.
	 *
	 * @param FlickrWallPhoto $flickr_photo
	 * @return array
	 * @access protected
	 */
	protected function getCmsMetadataPostFields (FlickrWallPhoto $flickr_photo) {
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
		);
	}

	/**
	 * Answer the POST field for a file-upload.
	 *
	 * @param FlickrWallPhoto $flickr_photo
	 * @return array
	 * @access protected
	 */
	protected function getCmsImagePostData (FlickrWallPhoto $flickr_photo) {
		$temp_file = $this->downloadFlickrImage($flickr_photo);
		$extension = pathinfo($temp_file, PATHINFO_EXTENSION);
		$mimetype = image_type_to_mime_type(exif_imagetype($temp_file));
		$filename = basename($temp_file);

		if (class_exists('CURLFile')) {
			return new CURLFile($temp_file, $mimetype, $flickr_photo->getId().'.'.$extension);
		} else {
			return '@'.$temp_file.';type='.$mimetype.';filename='.$flickr_photo->getId().'.'.$extension;
		}
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
