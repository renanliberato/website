<?php

use App\TemplateBuilder;

Flight::route('GET /', function() {
    TemplateBuilder::viewSingleTemplate('./src/templates/index.phtml');
});