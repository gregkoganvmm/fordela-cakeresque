<?php 
class ClientTransfer extends AppModel
{

	/**
	*
	* @var unknown_type
	*/
	var $name = 'ClientTransfer';

	var $actsAs = array(
			  'Containable',
	);
	
	
	var $belongsTo = array(
		'SourceClient'=>array(
			'className'  => 'Client',
			'fields'=> 'id,name,subdomain,api_id',
		),
		'DestinationClient'=>array(
			'className'  => 'Client',
			'fields'=> 'id,name,subdomain,api_id',
		)
	);
	
	var $hasMany = 'ClientTransferLog';
	
}
