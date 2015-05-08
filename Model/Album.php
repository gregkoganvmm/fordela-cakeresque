<?php
/**
 * Short description for album.php
 *
 * Long description for album.php
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
 * Edits 2010, Daniel Scherr
 */
/**
 * Album class
 *
 * @uses          AppModel
 * @package       mi-base
 * @subpackage    mi-base.app.models
 */
class Album extends AppModel {
/**
 * name property
 *
 * @var string 'Album'
 * @access public
 */
	var $name = 'Album';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array(
		  'Search.Search',
	);
	
	var $validate = array(
		'name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message'=>'Album Name is required'
			),
		)
	);

	/* SEARCH PLUGIN CODE */
	
	public $filterArgs = array(
    	array(
    	'name' => 'search_name', 
    	 'type' => 'query',
    	 'method' => 'nameSearch'
    	),
    	array(
    	'name' => 'search_artist', 
    	 'type' => 'query',
    	 'method' => 'artistSearch'
    	),
    	array(
    	'name' => 'search_location', 
    	 'type' => 'query',
    	 'method' => 'locationSearch'
    	),
    );
    
    public function nameSearch($data = array()) {
        $filter = $data['search_name'];
        $cond = array(
            'OR' => array(
                'Album.name LIKE' => '%' . $filter . '%'               
            ));
        return $cond;
    }	
    public function artistSearch($data = array()) {
        $filter = $data['search_artist'];
        $cond = array(
            'OR' => array(
                'Album.artist LIKE' => '%' . $filter . '%'
            ));
        return $cond;
    }	
    public function locationSearch($data = array()) {
        $filter = $data['search_location'];
        $cond = array(
            'OR' => array(
                'Album.location LIKE' => '%' . $filter . '%'         
            ));
        return $cond;
    }
    /*
     * $Id: album.php 13054 2012-02-14 23:55:38Z joel $
     * This is to get the next id for the catalog
     */
    public function getCatalogsNextId()
    {
    	$nextCatalogIDs = array();
    	$sql = "
    		(
			SELECT cast(substr(`albums`.`location`, 3) as unsigned) as datavalue, `albums`.`location`  FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'CL%' ORDER BY datavalue DESC LIMIT 1
			) 
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 3) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'CP%' ORDER BY datavalue DESC LIMIT 1
			) 
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 2) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'F%' ORDER BY datavalue DESC LIMIT 1
			)
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 2) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'G%' ORDER BY datavalue DESC LIMIT 1
			)
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 2) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'M%' ORDER BY datavalue DESC LIMIT 1
			)
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 2) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'I%' ORDER BY datavalue DESC LIMIT 1
			)
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 3) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'SP%' ORDER BY datavalue DESC LIMIT 1
			)
			UNION 
			(
			SELECT cast(substr(`albums`.`location`, 3) as unsigned) as datavalue, `albums`.`location` FROM `vms`.`albums` WHERE `albums`.`location` LIKE 'ST%' ORDER BY datavalue DESC LIMIT 1
			)
    	";
    	
    	$query_array = $this->query($sql);
    	
    	foreach($query_array As $key=>$value)
    	{
    		$nextID= $query_array[$key][0]['datavalue']+1;
    		$nextCatalog=preg_replace('~[^A-Z]*~', '', $query_array[$key][0]['location']);
    		$nextCatalogIDs[$nextCatalog] = $nextCatalog.$nextID;
    	}
    	
    	
    	return $nextCatalogIDs;
    	
    	
    }
}
?>