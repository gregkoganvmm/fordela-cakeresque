<?php
/**
 * Short description for attachment.php
 *
 * Long description for attachment.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi-base
 * @subpackage    mi-base.app.models
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Attachment class
 *
 * @uses          AppModel
 * @package       mi-base
 * @subpackage    mi-base.app.models
 */
class Image extends AppModel {
/**
 * name property
 *
 * @var string 'Attachment'
 * @access public
 */
	var $name = 'Image';
/**
 * displayField property
 *
 * @var string 'description'
 * @access public
 */
	var $displayField = 'description';
/**
 * validate property
 *
 * @var array
 * @access public
 */
	var $validate = array(
		//'user_id' => array('numeric'),
		//'class' => array('alphaNumeric'),
		//'foreign_id' => array('rule' => array('minLength', 1)),
	);

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Client');
}
?>
