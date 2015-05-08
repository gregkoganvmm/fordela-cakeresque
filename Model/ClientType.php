<?php
/**
 * Short description for client_type.php
 *
 * Long description for client_type.php
 *
 *$Id: client_type.php 7550 2011-04-04 23:18:53Z joel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * ClientType class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class ClientType extends AppModel {

	var $name = 'ClientType';
	var $actsAs = array('Containable', 'Acl' => array('type'=> 'requester'));
	
	var $useTable = 'client_types';
	var $primaryKey = 'id';
	/*
	var $hasMany = array(
		'Client' => array(
			'className'  => 'Client',
			'dependent'  =>  false,
			'foreignKey' => 'client_type_id'
		)
	);
	*/
	function parentNode() {
        return null;
    }

}
