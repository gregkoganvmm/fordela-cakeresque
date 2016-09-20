<?php return array(
  'includes' => array('_aws'),
  'services' => array(
      'default_settings' => array(
          'params' => array(
              'key'    => 'AKIAIS6EDGLXIW7J23SA',
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
              'private_key' => APP.'Config'.DS.'cloudfront-fordela-key.pem',
              'key_pair_id' => 'APKAJERUBIT6YAVYSQGQ'
          )
      )
  )
);
?>
