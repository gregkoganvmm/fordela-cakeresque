<?php
class UsageStat extends AppModel {
	var $name = 'UsageStat';
	
	public $actsAs = array('Search.Search');



    public $filterArgs = array(

        array('name' => 'type', 'type' => 'value'),
        array('name' => 'client_id', 'type' => 'value'),
        array('name' => 'start_date', 'type' => 'value', 'field' => 'UsageStat.date >='),
        array('name' => 'end_date', 'type' => 'value', 'field' => 'UsageStat.date <='),
        
    );

 	public function findByTags($data = array()) {
        $this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
        $this->Tagged->Behaviors->attach('Search.Searchable');
        $query = $this->Tagged->getQuery('all', array(
            'conditions' => array('Tag.name'  => $data['tags']),
            'fields' => array('foreign_key'),
            'contain' => array('Tag')
        ));
        return $query;
    }

    public function orConditions($data = array()) {
        $filter = $data['filter'];
        $cond = array(
            'OR' => array(
                $this->alias . '.title LIKE' => '%' . $filter . '%',
                $this->alias . '.body LIKE' => '%' . $filter . '%',
            ));
        return $cond;
    }
    
    function makeDateRange($data = array()){
    	$range = $data['month'];
    	//debug($range);
    	$month = date('F');
    	
    	debug($month);
    	$start = strtotime('First day of '.$month);
    	$last = strtotime('Last day of '.$month);
    	
    	debug($start);
    	debug($last);
    	
    }
    
    var $validate = array(
		'client_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'type' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'value' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'date' => array(
			'date' => array(
				'rule' => array('date'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Client' => array(
			'className' => 'Client',
			'foreignKey' => 'client_id',
			'conditions' => '',
			'fields' => 'id,name',
			'order' => ''
		)
	);
}
?>