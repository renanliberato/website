<?php

use App\Template;

function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
}

Flight::route('GET /manager/contents', function() {
    $contents = array_values(getContents());

    return Template::view('./src/templates/manager/contents.phtml', [
        'contents' => $contents
    ]);
});

Flight::route('GET /manager/contents/new', function() {
    return Template::view('./src/templates/manager/contents_new.phtml');
});

Flight::route('POST /manager/contents/new', function() {
    $contents = getContents();
    $name = Flight::request()->data->name;
    $fields = json_decode(Flight::request()->data->fields, true);

    $hasSlug = false;

    foreach ($fields as $key => $value) {
        $hasSlug = $value['name'] == 'slug';

        if ($hasSlug)
            break;
    }

    if (!$hasSlug)
        $fields[] = [
            'name' => 'slug',
            'type' => 'text'
        ];

    $contents[$name] = [
        'name' => $name,
        'fields' => $fields,
        'items' => []
    ];

    saveContents($contents);
    
    commitFile('./data/contents.json', 'added content '.$name);

    // item template

    $itemTemplateName = $name.'_item';
    $path = './src/templates/'.$itemTemplateName.'.phtml';
    file_put_contents($path, '');
    
    commitFile($path, 'created template '.$itemTemplateName.' on '.$path);

    Flight::redirect('/manager/contents');
});

Flight::route('GET /manager/contents/edit', function() {
    $contents = getContents();
    $content = $contents[Flight::request()->query->name];

    $content['fields'] == array_filter($content['fields'], function($field) {
        return $field['name'] != 'slug';
    });

    return Template::view('./src/templates/manager/contents_edit.phtml', [
        'content' => $content
    ]);
});

Flight::route('POST /manager/contents/edit', function() {
    $name = Flight::request()->query['name'];
    $fields = json_decode(Flight::request()->data['fields'], true);

    foreach ($fields as $key => $value) {
        $hasSlug = $value['name'] == 'slug';

        if ($hasSlug)
            break;
    }

    if (!$hasSlug)
        $fields[] = [
            'name' => 'slug',
            'type' => 'text'
        ];

    $contents = getContents();
    $contents[$name] = [
        'name' => $name,
        'fields' => $fields,
        'items' => array_map(function ($item) use ($fields) {
            foreach ($fields as $field) {
                if (!isset($item[$field['name']])) {
                    $item[$field['name']] = '';
                }
            }

            return $item;
        }, $contents[$name]['items'])
    ];

    saveContents($contents);
    
    commitFile('./data/contents.json', 'updated content '.$name);

    Flight::redirect('/manager/contents');
});

Flight::route('POST /manager/contents/delete', function() {
    $name = Flight::request()->query->name;
    $contents = getContents();
    unset($contents[$name]);

    saveContents($contents);

    commitFile('./data/contents.json', 'removed content '.$name);

    $itemTemplateName = $name.'_item';
    $itemTemplatePath = './src/templates/'.$itemTemplateName.'.phtml';

    @unlink($itemTemplatePath);

    commitFile($itemTemplatePath, 'removed item template '.$itemTemplateName.' on '.$itemTemplatePath);

    @rrmdir('./build/'.$name);
    commitFile('./build/'.$name, 'removed item template folder '.$name);

    Flight::redirect('/manager/contents');
});



// content items


Flight::route('GET /manager/contents/items', function() {
    $name = Flight::request()->query->name;
    $contents = getContents();

    return Template::view('./src/templates/manager/contents_items.phtml', [
        'content' => $contents[$name]
    ]);
});


Flight::route('GET /manager/contents/items/new', function() {
    $name = Flight::request()->query->name;
    $contents = getContents();
    $content = $contents[$name];

    return Template::view('./src/templates/manager/contents_items_new.phtml', [
        'content' => $content
    ]);
});

Flight::route('POST /manager/contents/items/new', function() {
    $name = Flight::request()->query->name;
    $contents = getContents();
    $content = $contents[$name];
    $item = Flight::request()->data;

    $item = array_reduce($content['fields'], function($itemData, $field) use ($item, $content, $name) {
        switch($field['type']) {
            case 'text':
                $itemData[$field['name']] = $item[$field['name']];
                break;
            case 'html':
                $dirPath = './data/'.$content['name'].'/'.$field['name'];
                $itemData[$field['name']] = $dirPath.'/'.md5(uniqid(rand(), true)).'.html';

                if (!is_dir('./data/'.$content['name'])) {
                    mkdir('./data/'.$content['name']);
                }

                if (!is_dir($dirPath))
                    mkdir($dirPath);

                file_put_contents($itemData[$field['name']], $item[$field['name']]);
                commitFile($itemData[$field['name']], 'added content '.$name.', field '.$field['name'].' html file');
                break;
            case 'json':
                $itemData[$field['name']] = json_decode($item[$field['name']], true);
                break;
            default:
                var_dump('type not found');die;
        }

        return $itemData;
    }, []);

    $content['items'][] = $item;
    $contents[$name] = $content;

    saveContents($contents);
    
    commitFile('./data/contents.json', 'added content '.$name);



    Flight::redirect('/manager/contents/items?name='.$name);
});

Flight::route('GET /manager/contents/items/edit', function() {
    $contents = getContents();
    $name = Flight::request()->query->name;
    $content = $contents[$name];
    $item = $content['items'][Flight::request()->query->key];

    $item = array_reduce($content['fields'], function($itemData, $field) use ($item) {
        switch($field['type']) {
            case 'text':
                $itemData[$field['name']] = $item[$field['name']];
                break;
            case 'html':
                $itemData[$field['name']] = file_get_contents($item[$field['name']]);
                break;
            case 'json':
                $itemData[$field['name']] = $item[$field['name']];
                break;
            default:
                var_dump('type not found');die;
        }

        return $itemData;
    }, []);


    return Template::view('./src/templates/manager/contents_items_edit.phtml', [
        'content' => $content,
        'item' => $item
    ]);
});

Flight::route('POST /manager/contents/items/edit', function() {
    $name = Flight::request()->query->name;
    $key = Flight::request()->query->key;
    $contents = getContents();
    $content = $contents[$name];
    $item = Flight::request()->data;
    $existentItem = $content['items'][$key];

    $item = array_reduce($content['fields'], function($itemData, $field) use ($item, $content, $existentItem, $name) {
        switch($field['type']) {
            case 'text':
                $itemData[$field['name']] = $item[$field['name']];
                break;
            case 'html':
                $dirPath = './data/'.$content['name'].'/'.$field['name'];
                $itemData[$field['name']] = $existentItem[$field['name']];
                file_put_contents($existentItem[$field['name']], $item[$field['name']]);
                commitFile($existentItem[$field['name']], 'updated content '.$name.', field '.$field['name'].' html file');
                break;
            case 'json':
                $itemData[$field['name']] = json_decode($item[$field['name']], true);
                break;
            default:
                var_dump('type not found');die;
        }

        return $itemData;
    }, []);

    $item['slug'] = $existentItem['slug'];

    $content['items'][$key] = $item;
    $contents[$name] = $content;

    saveContents($contents);
    
    commitFile('./data/contents.json', 'updated content '.$name);


    Flight::redirect('/manager/contents/items?name='.$name);
});

Flight::route('POST /manager/contents/items/delete', function() {
    $name = Flight::request()->query->name;
    $key = Flight::request()->query->key;
    $contents = getContents();
    $content = $contents[$name];
    $items = $content['items'];
    $item = $items[$key];

    unset($items[$key]);
    $content['items'] = $items;
    $contents[$name] = $content;

    array_reduce($content['fields'], function($itemData, $field) use ($item, $content, $name) {
        switch($field['type']) {
            case 'html':
                @unlink($item[$field['name']]);
                commitFile($item[$field['name']], 'removed content '.$name.', field '.$field['name'].' html file');
                break;
        }

        return $itemData;
    }, []);

    saveContents($contents);

    commitFile('./data/contents.json', 'removed content from '.$name);


    Flight::redirect('/manager/contents/items?name='.$name.'&key='.$key);
});