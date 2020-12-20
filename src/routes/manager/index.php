<?php

use App\Template;
use App\TemplateBuilder;

Flight::route('GET /manager', function(){
    return Flight::redirect('/manager/templates');
});

Flight::route('POST /manager/build', function(){

    TemplateBuilder::build();
    
    return Flight::redirect('/manager/templates');
});

require_once "contents.php";
require_once "templates.php";
require_once "layouts.php";