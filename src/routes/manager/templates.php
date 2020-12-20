<?php

use App\Template;

Flight::route('GET /manager/templates', function() {
    $templates = scandir('./src/templates');
    $templates = array_filter($templates, function($path) {
        return !is_dir('./src/templates/'.$path);
    });

    $templates = array_map(function($templateName) {
        $templatePath = './src/templates/'.$templateName;
        $template = [
            'name' => str_replace('.phtml', '', $templateName),
            'path' => $templatePath,
            'length' => strlen(file_get_contents($templatePath))
        ];

        return $template;
    }, $templates);

    return Template::view('./src/templates/manager/templates.phtml', [
        'templates' => $templates
    ]);
});

Flight::route('GET /manager/templates/new', function() {
    return Template::view('./src/templates/manager/templates_new.phtml');
});

Flight::route('POST /manager/templates/new', function() {
    $name = Flight::request()->data->name;
    $path = './src/templates/'.$name.'.phtml';
    file_put_contents($path, Flight::request()->data->content);
    
    commitFile($path, 'created template '.$name.' on '.$path);

    Flight::redirect('/manager/templates');
});

Flight::route('GET /manager/templates/edit', function() {
    $templateName = Flight::request()->query['name'];
    $templatePath = Flight::request()->query['path'];

    return Template::view('./src/templates/manager/templates_edit.phtml', [
        'template' => [
            'name' => $templateName,
            'path' => $templatePath,
            'content' => file_get_contents($templatePath)
        ]
    ]);
});

Flight::route('POST /manager/templates/edit', function() {
    $name = Flight::request()->query['name'];
    $path = Flight::request()->query['path'];

    file_put_contents($path, Flight::request()->data->content);


    commitFile($path, 'updated template '.$name.' on '.$path);

    Flight::redirect('/manager/templates');
});

Flight::route('POST /manager/templates/delete', function() {
    $path = Flight::request()->query->path;
    $name = Flight::request()->query->name;
    
    @unlink($path);
    commitFile($path, 'removed template on '.$path);

    Flight::redirect('/manager/templates');
});