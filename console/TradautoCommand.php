<?php

namespace Waka\Utils\Console;

use Illuminate\Console\Command;
use System\Console\BaseScaffoldCommand;

class TradautoCommand extends BaseScaffoldCommand
{
    protected $signature = 'waka:tradauto {action} {text} {translatedText?}';
    protected $description = 'Auto translation command';

    public function handle()
    {
        $action = $this->argument('action');
        $text = $this->argument('text');
        if($text) {
            $text = trim($text, "'");
        }
        $translatedText = $this->argument('translatedText');
        if($translatedText) {
            //trace_log($translatedText);
            $translatedText = trim($translatedText, "'");
            //trace_log($translatedText);
            $translatedText = preg_replace(["/''/", "/\\\\'/"], ["'", ""], $translatedText);
            //trace_log($translatedText);

        }


        switch ($action) {
            case 'create':
                $result = $this->create($text);
                break;
            case 'insert':
                $result = $this->insert($text, $translatedText);
                break;
            case 'get':
            default:
                $result = $this->get($text);
                break;
        }

        $this->info($result);
    }

    private function create(string $text)
    {
        $basePath = base_path('plugins');
        $codeData = $this->parseCodeLang($text);
        $langContent = [];
        array_set($langContent, $codeData['code'], $codeData['code']);
        $filePath = $basePath. '/' .$codeData['vendor']. '/' .$codeData['plugin'].'/lang/fr/'.$codeData['file'].'.php';
        $fileContent = '<?php' . PHP_EOL . PHP_EOL;
        $jsonContent = json_encode($langContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $modernArraySyntax = str_replace(['{', '}', '":'], ['[', ']', '" =>'], $jsonContent);
        $fileContent .= 'return ' . $modernArraySyntax . ';' . PHP_EOL;
        file_put_contents($filePath, $fileContent);
        //trace_log("Created: {$text}");
        return "Created: {$text}";
    }

    private function insert(string $text, string $translatedText)
    {
        // Insérez la logique d'insertion ici.
        //trace_log('insert');
        $codeData = $this->parseCodeLang($text);
        $filePath = $this->getFilePath($codeData);
        //trace_log($codeData);
        //trace_log($filePath);
        //trace_log($translatedText);
        $this->insertCodeInFile($filePath, $codeData['code'], $translatedText);
        //trace_log("Inserted: {$text} - {$translatedText}");
        return "Inserted: {$text} - {$translatedText}";
    }

    private function get(string $text)
    {
        // Insérez la logique de récupération ici.
        //trace_log($text);
        $codeData = $this->parseCodeLang($text);
        //trace_log($codeData);
        $filePath = $this->getFilePath($codeData);
        $code = null;
        $return = [];
        if($filePath) {
            $codeFromFile =  $this->getCodeFromFile($filePath, $codeData['code']);
            //trace_log('codeFromFile : '.$codeFromFile);
            $return = [
                'code' => $text,
                'translation' => $codeFromFile ?  $codeFromFile : '',
            ];
        } else {
            $return = [
                'code' => 'error_file',
            ];

        }
        //trace_log(json_encode($return));
        return json_encode($return);
    }

    private function insertCodeInFile($filePath, $code, $string) {
        $langContent = include $filePath;
        array_set($langContent, $code, $string);
        //trace_log($langContent);
        $fileContent = '<?php' . PHP_EOL . PHP_EOL;
        $jsonContent = json_encode($langContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $modernArraySyntax = str_replace(['{', '}', '":'], ['[', ']', '" =>'], $jsonContent);
        $fileContent .= 'return ' . $modernArraySyntax . ';' . PHP_EOL;
        file_put_contents($filePath, $fileContent);

    }

    private function getCodeFromFile($filePath, $code) {
        $langContent = include $filePath;
        //trace_log($langContent);
        $text = array_get($langContent, $code);
        return $text;
    }

    private function getFilePath($codeData) {
        $basePath = base_path('plugins');
        $path = $basePath. '/' .$codeData['vendor']. '/' .$codeData['plugin'].'/lang/fr/'.$codeData['file'].'.php';
        if(!file_exists($path)) return false;
        return  $path;
    }

    private function parseCodeLang($code) {
        $codeArray = explode('::',$code);
        $vendorPlugin = $codeArray[0];
        $vendorPluginArray = explode('.', $vendorPlugin);
        $vendor = $vendorPluginArray[0];
        $plugin = $vendorPluginArray[1];
        $langData = explode('.',$codeArray[1]);
        $langFile = array_shift($langData);
        $finalCode = implode('.',$langData);
        return [
            'vendor' => $vendor,
            'plugin' => $plugin,
            'file' => $langFile,
            'code' => $finalCode,
        ];
    }
}
