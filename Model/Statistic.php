<?php
/**
 * Short description for statistic.php
 *
 * Long description for statistic.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Statistic class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Statistic extends AppModel {

  
	 var $belongsTo = array('StatisticType');


}
?>