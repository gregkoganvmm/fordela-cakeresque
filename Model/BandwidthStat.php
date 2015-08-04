<?php
App::uses('AppModel', 'Model');
/**
 * BandwidthStat Model
 *
 * @property Client $Client
 */
class BandwidthStat extends AppModel {

    /**
     * belongsTo associations
     *
     * @var array
     */
    public $belongsTo = array(
        'Client' => array(
            'className' => 'Client',
            'foreignKey' => 'client_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        )
    );
}
