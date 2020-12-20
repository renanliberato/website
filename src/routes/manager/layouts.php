<?php

use App\Template;

Flight::route('GET /manager/layouts', function() {
    $layouts = scandir('./src/templates/layout');
    $layouts = array_filter($layouts, function($path) {
        return !is_dir('./src/templates/layout/'.$path) && $path != 'manager.phtml';
    });

    $layouts = array_map(function($templateName) {
        $templatePath = './src/templates/layout/'.$templateName;
        $template = [
            'name' => str_replace('.phtml', '', $templateName),
            'path' => $templatePath,
            'length' => strlen(file_get_contents($templatePath))
        ];

        return $template;
    }, $layouts);

    return Template::view('./src/templates/manager/layouts.phtml', [
        'layouts' => $layouts
    ]);
});

Flight::route('GET /manager/layouts/new', function() {
    return Template::view('./src/templates/manager/layouts_new.phtml');
});

Flight::route('POST /manager/layouts/new', function() {
    $name = Flight::request()->data->name;
    $path = './src/templates/layout/'.$name.'.phtml';
    file_put_contents($path, Flight::request()->data->content);
    
    commitFile($path, 'created layout '.$name.' on '.$path);

    Flight::redirect('/manager/layouts');
});

Flight::route('GET /manager/layouts/edit', function() {
    $templateName = Flight::request()->query['name'];
    $templatePath = Flight::request()->query['path'];

    return Template::view('./src/templates/manager/layouts_edit.phtml', [
        'layout' => [
            'name' => $templateName,
            'path' => $templatePath,
            'content' => file_get_contents($templatePath)
        ]
    ]);
});

Flight::route('POST /manager/layouts/edit', function() {
    $name = Flight::request()->query['name'];
    $path = Flight::request()->query['path'];

    file_put_contents($path, Flight::request()->data->content);
    
    commitFile($path, 'updated layout '.$name.' on '.$path);

    Flight::redirect('/manager/layouts');
});

Flight::route('POST /manager/layouts/delete', function() {
    $path = Flight::request()->query->path;
    @unlink($path);

    commitFile($path, 'removed layout on '.$path);

    Flight::redirect('/manager/layouts');
});