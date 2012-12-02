<?php
include '../lib/fluf.php';

fluf\get('/:what', function($what) {
    echo $what;
});

fluf\get('/', function () {
    echo 'Hello World!';
});