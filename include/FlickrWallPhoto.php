<?php
/**
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */

/**
 * A wrapper for translating from a Flickr photo to the values desired by the wall.
 *
 * @package history_wall_sync
 *
 * @copyright Copyright &copy; 2014, Middlebury College
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */
class FlickrWallPhoto {

	protected $photo;
	protected $wall_categories;

	/**
	 * Constructor
	 *
	 * @param object $photo
	 * @access public
	 */
	public function __construct ($photo, array $wall_categories) {
		$this->photo = $photo;

		$this->wall_categories = array();
		foreach($wall_categories as $cat) {
			$this->wall_categories[$cat] = str_replace("'", '', str_replace(' ', '', strtolower($cat)));
		}

		$this->special_prefixes = array(
			'wallhcrop',
			'wallvcrop',
			'decade',
		);
	}

	public function getId() {
		return $this->photo->id;
	}

	public function getTitle () {
		$title = trim($this->photo->title);
		$title = str_replace('&quot;', '"', $title);
		$title = str_replace('&ldquo;', '“', $title);
		$title = str_replace('&rdquo;', '”', $title);
		$title = str_replace('&#39;', "'", $title);
		$title = str_replace('&lsquo;', '‘', $title);
		$title = str_replace('&rsquo;', '’', $title);
		$title = str_replace('&amp;', '&', $title);
		return $title;
	}

	public function getDescription () {
		$parts = explode('----', $this->photo->description['_content']);
		$description = trim($parts[0]);
		$description = str_replace('&quot;', '"', $description);
		$description = str_replace('&ldquo;', '“', $description);
		$description = str_replace('&rdquo;', '”', $description);
		$description = str_replace('&#39;', "'", $description);
		$description = str_replace('&lsquo;', '‘', $description);
		$description = str_replace('&rsquo;', '’', $description);
		$description = str_replace('&amp;', '&', $description);
		return $description;
	}

	public function descriptionIsQuote() {
		return is_array($this->getQuoteParts());
	}

	public function getQuoteParts() {
		if (preg_match('/^("|”|“|&quot;)(.+)("|”|“|&quot;)\s+--(.+)$/s', $this->getDescription(), $m)) {
			return array('quote' => $m[1], 'attribution' => $m[2]);
		} else {
			return FALSE;
		}
	}

	public function getDate() {
		return $this->photo->datetaken;
	}

	public function getLastUpdateDate() {
		if (isset($this->photo->lastupdate))
			$date = $this->photo->lastupdate;
		else if (isset($this->photo->dates['lastupdate']))
			$date = $this->photo->dates['lastupdate'];
		else {
			var_dump($this->photo);
			throw new Exception('No lastupdate date available. Maybe add "last_update" to your search extras.');
		}
		return DateTime::createFromFormat('U', $date, new DateTimeZone('GMT'));
	}

	public function getDecade() {
		$date = new DateTime($this->getDate());
		$year = intval($date->format('Y'));
		if ($year < 1910)
			return '1900s';
		$decade = floor(intval($year)/10) * 10;
		return $decade.'s';
	}

	public function getTags() {
		return explode(' ', $this->photo->tags);
	}

	public function getCategories() {
		$cats = array_intersect($this->wall_categories, $this->getTags());
		return array_keys($cats);
	}

	public function getSpecialTags() {
		$tags = array();
		foreach ($this->getTags() as $tag) {
			foreach ($this->special_prefixes as $prefix) {
				if (strpos($tag, $prefix) === 0) {
					$tags[] = $tag;
					break;
					break;
				}
			}
		}
		return $tags;
	}

	public function getNonMatchingTags() {
		$tags = array_diff($this->getTags(), $this->wall_categories, $this->getSpecialTags());
		// Delete empty tags.
		foreach($tags as $k => $v) {
			if (empty($v))
				unset($tags[$k]);
		}
		return $tags;
	}

	public function getHCrop() {
		$tags = $this->getTags();
		if (in_array('wallhcropleft', $tags))
			return 'left';
		if (in_array('wallhcropright', $tags))
			return 'right';
		return 'center';
	}

	public function getVCrop() {
		$tags = $this->getTags();
		if (in_array('wallvcroptop', $tags))
			return 'top';
		if (in_array('wallvcropbottom', $tags))
			return 'bottom';
		return 'center';
	}

	public function getOriginalUrl() {
		if (empty($this->photo->url_o))
			throw new Exception('Photo does not have a url_o property. Maybe it needs to be added to the search\'s "extras" field.');
		return $this->photo->url_o;
	}

	public function getLargeUrl() {
		if (empty($this->photo->url_l))
			return $this->getOriginalUrl();
		return $this->photo->url_l;
	}

	public function getThumbnailUrl() {
		if (empty($this->photo->url_t))
			return $this->getOriginalUrl();
		return $this->photo->url_t;
	}

	public function getWarnings() {
		$warnings = array();

		if (preg_match('/^("|“|&quot;).+/', $this->getDescription()) && !$this->descriptionIsQuote())
			$warnings[] = 'Description starts with a quotation mark, but isn\'t properly formatted as a quote. (See <a href="formatting.php#description-quotes" target="_blank">formatting details</a>)';

		$tags = $this->getNonMatchingTags();
		if (count($tags)) {
			$warnings[] = count($tags).' unknown tags will be skipped (See <a href="formatting.php#categories-tags" target="_blank">formatting details</a>): <ul><li>'.implode('</li> <li>', $tags).'</li></ul>';
		}

		return $warnings;
	}

	public function getErrors() {
		$warnings = array();

		$len = strlen($this->getTitle());
		if ($len > 66)
			$warnings[] = 'Title is '.$len.' characters, max is 66. (See <a href="formatting.php#title-length" target="_blank">formatting details</a>)';


		$len = strlen($this->getDescription());
		if ($len > 240)
			$warnings[] = 'Description is '.$len.' characters, max is 240. (See <a href="formatting.php#description-length" target="_blank">formatting details</a>)';

		if (!count($this->getCategories()))
			$warnings[] = 'No valid categories are specified.';

		return $warnings;
	}
}
