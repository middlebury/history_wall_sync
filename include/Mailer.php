<?php

class Mailer {

	public static function send($message) {
		if (count(self::$to)) {
			$message = str_replace("\n", "\r\n", $message);
			mail(implode(',', self::$to), 'History Wall sync error', $message);
		}
	}

	public static function addTo($to) {
		$filtered = filter_var($to, FILTER_VALIDATE_EMAIL);
		if ($to) {
			self::$to[] = $filtered;
		} else {
			throw new Exception("Not a valid email address, '$to'.");
		}
	}

	private static $to = array();
}
