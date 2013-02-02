<?php

require('../../../wp-load.php');
require_once('src/Fitocracy.php');

$f = new Fitocracy('rb-cohen', '');
var_dump($f->getUser('rb-cohen'));
