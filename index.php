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

function getAdminButton() {
    if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
        return;

    $a = "<a style='position: absolute; top: 20px; right: 20px' href='/manager'>Access manager dashboard </a>";

    return $a;
}

$GLOBALS['static'] = json_decode(file_get_contents('./data/static.json'), true);

require_once "./src/routes/index.php";
require_once "./src/routes/manager/index.php";

Flight::route('GET *', function() {
    $path = Flight::request()->url;
    
    
    $pathExploded = explode('/', $path);
    
    if (count($pathExploded) <= 2) {
        TemplateBuilder::viewSingleTemplate("./src/templates{$path}.phtml");
    } else {
        $contentKey = $pathExploded[1];
        $slug = $pathExploded[2];

        $parsedContents = TemplateBuilder::getParsedContents();
        $contentsFromKey = $parsedContents[$contentKey];
        $item = current(array_filter($contentsFromKey, function($i) use ($slug) { return $i['slug'] == $slug; }));
        
        TemplateBuilder::viewSingleTemplateWithData("./src/templates/{$contentKey}_item.phtml", ['item' => $item]);
    }


    // $lastPart = $pathExploded[count($pathExploded) - 1];

    // if (strpos($lastPart, '.') === false) {
    //     echo file_get_contents('./build/'.$path.'.html').getAdminButton();
    // } else {
    //     echo file_get_contents('./build/'.$path).getAdminButton();
    // }
});

Flight::start();