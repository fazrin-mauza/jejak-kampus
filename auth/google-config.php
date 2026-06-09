<?php
require 'vendor/autoload.php';

$client = new Google_Client();

$client->setClientId('927285193182-g9o8g3nhr4fp9pttpmceatdjdha8uci7.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://jejak-kampus.web.id/auth/google-callback.php');

$client->addScope("email");
$client->addScope("profile");