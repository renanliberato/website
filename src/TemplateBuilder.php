<?php

namespace App;

use App\Template;

function commitFile($file, $message) {
    exec('git reset');
    exec('git add '.$file);
    exec('git commit -m "[MANAGER] '.$message.'"');
}

class TemplateBuilder
{
    public static function build() {
        $parsedContents = self::getParsedContents();

        $templates = scandir('./src/templates');
        $templates = array_filter($templates, function($path) {
            return !is_dir('./src/templates/'.$path) && strpos($path, '_item') === false;
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

        $existentTemplates = scandir('.');
        $existentTemplates = array_filter($existentTemplates, function($path) {
            return strpos($path, '.html') !== false;
        });

        foreach ($existentTemplates as $template) {
            @unlink('./'.$template);
        }

        $templateNames = array_map(function ($template) { return $template['name'].'.html'; }, $templates);

        foreach ($templates as $template) {
            $builtPath = './build/'.$template['name'].'.html';
            Template::viewAndSaveToFile($template['path'], $parsedContents, $builtPath);
            commitFile($builtPath, 'built template '.$template['name'].'.html');
        }

        $templatesToRemove = array_diff($existentTemplates, $templateNames);

        foreach ($templatesToRemove as $templateToRemove) {
            commitFile('./'.$templateToRemove, 'removed built template '.$templateToRemove);
        }

        foreach ($templates as $template) {
            if ($template['name'] == 'index') {
                TemplateBuilder::buildItems('posts', $parsedContents['posts']);
                continue;
            }

            if (isset($parsedContents[$template['name']])) {
                $contentsForKey = $parsedContents[$template['name']];

                TemplateBuilder::buildItems($template['name'], $contentsForKey);
            }
        }
    }

    public static function buildSingleTemplate($path, $additionalContent = [], $customBuildTemplateName = '')
    {
        $name = self::getTemplateName($path);
        $parsedContents = self::getParsedContents();
        if ($additionalContent) {
            foreach ($additionalContent as $key => $value) {
                $parsedContents[$key] = $value;
            }
        }


        $builtPath = $customBuildTemplateName != '' ? $customBuildTemplateName : './build/'.$name.'.html';

        Template::viewAndSaveToFile($path, $parsedContents, $builtPath);

        commitFile($builtPath, 'built template '.$name.'.html');
    }

    public static function viewSingleTemplate($path, $additionalContent = [], $customBuildTemplateName = '')
    {
        $name = self::getTemplateName($path);
        $parsedContents = self::getParsedContents();
        if ($additionalContent) {
            foreach ($additionalContent as $key => $value) {
                $parsedContents[$key] = $value;
            }
        }

        Template::view($path, $parsedContents);
    }

    public static function viewSingleTemplateWithData($path, $data = [])
    {
        Template::view($path, $data);
    }

    public static function buildItems($name, $items) {
        $itemsDir = './build/'.$name;
        if (!is_dir($itemsDir)) {
            mkdir('./build/'.$name);
        }
        foreach ($items as $item) {
            self::buildSingleTemplate('./src/templates/'.$name.'_item.phtml', ["item" => $item], './build/'.$name.'/'.$item['slug'].'.html');
        }
    }

    public static function getParsedContents()
    {
        $contents = json_decode(file_get_contents('./data/contents.json'), true);

        return array_reduce($contents, function($parsed, $content) {
            $fields = $content['fields'];
            $theContent = array_reduce($content['items'], function($innerContent, $item) use ($content) {
                $theContentitem = array_reduce($content['fields'], function($itemData, $field) use ($item) {
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

                $innerContent[] = $theContentitem;

                return $innerContent;
            }, []);

            $parsed[$content['name']] = $theContent;

            return $parsed;
        }, []);
    }

    public static function getTemplateName($path)
    {
        $splitedPath = explode('/', $path);
        return str_replace('.phtml', '', array_pop($splitedPath));
    }

    public static function removeBuiltTemplate($path)
    {
        $name = self::getTemplateName($path).'.html';
        @unlink('./build/'.$name);
        commitFile('./build/'.$name, 'removed built template '.$name);
    }
}