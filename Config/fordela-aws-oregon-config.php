<?php return array(
  'includes' => array('_aws'),
  'services' => array(
      'default_settings' => array(
          'params' => array(
              'key'    => 'AKIAI5Q4O7K47J7FXCEA',
              'secret' => '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',
              'region' => 'us-west-2'
          )
      ),
      's3' => array(
          'extends' => 's3',
          'params'  => array(
              'region' => 'us-west-2',
          )
      ),
      'cloudfront' => array(
          'extends' => 'cloudfront',
          'params'  => array(
              'private_key' => APP.'Config'.DS.'aws'.DS.'cloudfront-fordela-key.pem',
              'key_pair_id' => 'APKAJS3IDLJPXTWIFK3A'
          )
      )
  )
);
?>
