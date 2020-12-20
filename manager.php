<?php

require_once "./vendor/autoload.php";

use App\Template;
use App\TemplateBuilder;

function commitFile($file, $message) {
    exec('git reset');
    exec('git add '.$file);
    exec('git commit -m "[MANAGER] '.$message.'"');
}

function getContents() {
    return json_decode(file_get_contents('./data/contents.json'), true);;
}

function saveContents($contents) {
    return file_put_contents('./data/contents.json', json_encode($contents, JSON_PRETTY_PRINT));
}

require_once "./src/routes/index.php";
require_once "./src/routes/manager/index.php";

Flight::start();