<?php
include '../lib/http.php';

http\get('/:what', function($what) {
    echo $what;
});

http\get('/', function () {
    echo 'Hello World!';
});