<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 */
App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

	var $inserted_ids = array();

	/**
	 * Used for getting the last IDs after a saveAll() as part of function afterSave()
	 * since $this->Model->id alone won't show all the IDs
	 */
	function afterSave($created, $options = array())
	{
		if($created)
		{
			$this->inserted_ids[] = $this->getInsertID();
		}

		return true;
	}

}
