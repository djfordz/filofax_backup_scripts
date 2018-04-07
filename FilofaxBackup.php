<?php

require 'vendor/autoload.php';

$client = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => 'eu-central-1a'
]);

$source = '/home/';
$dest = 's3://bucket/awseu';

$manager = new \Aws\S3\Transfer($client, $source, $dest);

$manager->transfer();
