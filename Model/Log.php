<?php
class Log extends AppModel {

	var $name = 'Log';
	var $order = 'created DESC';
	var $virtualFields = array(
		'subdomain' => "SUBSTRING_INDEX(Log.host, '.', 1)"
	);
}
?>