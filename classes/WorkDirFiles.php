<?php namespace Waka\Utils\Classes;

use Config;
use Storage;

class WorkDirFiles
{
    public $storageDir;
    public $storagePath;
    public $disk;

    public function __construct($disk = 'local')
    {
        $this->storageDir = 'media';
        $this->storagePath = 'app/media';
        $this->disk = $disk;
        if ($disk == 'unkown') {
            $this->storageDir = '/';
            $this->storagePath = '/';
        }
    }

    public function createDir($dirToCreate)
    {
        $dirToCreate = $this->storageDir . '/' . $dirToCreate;
        Storage::disk($this->disk)->makeDirectory($dirToCreate);
    }

    public function checkDirExiste($dirToCheck)
    {
        $dirToCheck = $this->storageDir . '/' . $dirToCheck;
        $existingDirectories = Storage::disk($this->disk)->disk($this->disk)->alldirectories($this->storageDir);
        if (!in_array($dirToCheck, $existingDirectories)) {
            return true;
        } else {
            return false;
        }
    }

    public function putFile($filepath, $directory, $rename = null)
    {
        $this->createDir($directory);
        $fileName = $filepath;
        if ($rename) {
            $fileName = $rename;
        } else {
            $fileName = $this->getNameFromPath($fileName);
        }
        $data = file_get_contents($filepath);
        Storage::disk($this->disk)->put($this->storageDir . '/' . $directory . '/' . $fileName, $data);
    }

    public function getNameFromPath($filepath)
    {
        $fileName = $filepath;
        if (str_contains($fileName, '\\')) {
            $exp = explode('\\', $fileName);
            $fileName = array_pop($exp);
        }
        if (str_contains($fileName, '/')) {
            $exp = explode('/', $fileName);
            $fileName = array_pop($exp);
        }
        return $fileName;
    }

    public function getFilePath($file, $filePath = null)
    {
        $file = htmlspecialchars($file);
        if ($filePath) {
            $file = $filePath . '/' . $file;
        }

        if (Storage::disk($this->disk)->exists($this->storageDir . '/' . $file)) {
            return storage_path($this->storagePath . '/' . $file);
        } else {
            return false;
        }
    }

    public function getFileUrl($file, $filePath = null)
    {
        $file = htmlspecialchars($file);
        if ($filePath) {
            $file = $filePath . '/' . $file;
        }

        if (Storage::disk($this->disk)->exists($this->storageDir . '/' . $file)) {
            return url(Config::get('cms.storage.media.path') . '/' . $file);
        } else {
            return false;
        }
    }
}
