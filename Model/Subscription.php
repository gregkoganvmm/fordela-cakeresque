<?php 
/* $Id$ */
class Subscription extends AppModel {
	
	var $name = 'Subscription';

	var $belongsTo = array('Client' =>
  		array('className'  => 'Client',
    	'conditions' => '',
    	'order'      => '',
    	'foreignKey' => 'client_id'
  		)
	);
	
	/**
	 * HasMany Association
	 * 
	 * @var array $hasMany Association
	 */
	var $hasMany = array(
		'SubscriptionTransaction' => array(
			'className'  => 'SubscriptionTransaction',
			'dependent'  =>  true,
			'foreignKey' => 'subscription_id'
		)
	);
	
}
?>