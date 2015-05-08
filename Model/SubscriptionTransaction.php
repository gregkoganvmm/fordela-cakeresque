<?php 
/* $Id$ */
class SubscriptionTransaction extends AppModel {
	
	var $name = 'SubscriptionTransaction';

	var $belongsTo = array('Subscription' =>
  		array('className'  => 'Subscription',
    	'conditions' => '',
    	'order'      => '',
    	'foreignKey' => 'subscription_id'
  		)
	);
	
}
?>