<?php
/**
 * Short description for alert.php
 *
 * Long description for alert.php
 *
 * $Id: alert.php 1865 2010-05-05 19:03:53Z joel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Alert class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Alert extends AppModel {

	var $name = 'Alert';
	var $useTable = 'alerts';
	var $primaryKey = 'id';

}
?>
