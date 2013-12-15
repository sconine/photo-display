<?php
require 'vendor/autoload.php';

use Aws\Common\Aws;

// Create a service building using shared credentials for each service
$aws = Aws::factory(array(
    'key'    => 'your-aws-access-key-id',
    'secret' => 'your-aws-secret-access-key'
   // ,'region' => 'us-west-2' -> not needed for S3
));

$aws = Aws::factory('/path/to/my_config.json');
$s3 = $aws->get('s3');

?>
