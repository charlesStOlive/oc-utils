<?php

namespace Waka\Utils\Classes;

use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use Storage;

class TmpFiles
{
    const KEEP_F = 'flash';
    const KEEP_M = 'month';
    const KEEP_Q = 'quarter';
    const KEEP_W = 'week';
    const KEEP_D = 'day';
    const BASE_PATH = 'uploads';

    /** @var string */
    protected static $tempFolderName;
    protected static $storageRacine;
    protected static $anonyme;

    /** @var string */
    protected $fileName;

    /** @var bool */
    protected $private;

    /** @var bool */
    protected $forceCreate = false;

    public static function createDirectory(String $keepMode = '', $folderName = null): self
    {
        //trace_log(self::BASE_PATH);
        //trace_log(self::$storageRacine);

        self::$tempFolderName = self::setTempFolderName($folderName);
        self::$storageRacine = self::setStorageRacine($keepMode);
        
        return new self;
    }

    

    protected static function setTempFolderName($folderName = null)
    {
        if(!$folderName) {
            self::$anonyme = true;
            return self::sanitizeName(mt_rand().'-'.str_replace([' ', '.'], '', microtime()));
        } else {
            self::$anonyme = false;
            return  self::sanitizeName($folderName);
        }
    }

    protected static function sanitizeName(string $name): string
    {
        if (! self::isValidDirectoryName($name)) {
            throw new Exception("The directory name `$name` contains invalid characters.");
        }

        return trim($name);
    }

    protected static function isValidDirectoryName(string $directoryName): bool
    {
        return strpbrk($directoryName, '\\/?%*:|"<>') === false;
    }

    protected static function setStorageRacine($_keepMode): string
    {
        $storagePath = self::BASE_PATH;
        $keepMode = self::getKeepModeDirectory($_keepMode);
        //trace_log($storagePath);
        //trace_log($keepMode);
        if($keepMode == self::KEEP_F) {
            $storagePath .= DIRECTORY_SEPARATOR.'public' .DIRECTORY_SEPARATOR. $keepMode;
        } else {
            $storagePath .= DIRECTORY_SEPARATOR.'public' .DIRECTORY_SEPARATOR. $keepMode;
        }
        return $storagePath;
    }

    protected static function getKeepModeDirectory(String $keepMode) {
        if(!$keepMode) {
            return self::KEEP_F;
        }
        switch ($keepMode) {
            case 'quarter':
                return self::KEEP_Q;
                break;
            case 'month':
                return  self::KEEP_M;
                break;
            case 'week':
                return  self::KEEP_W;
                break;
            case 'day':
                return  self::KEEP_D;
                break;
            default:
                throw new Exception("le mode de gard des fichers n'existe pas vous devez utiliser ".self::KEEP_Q .' '.self::KEEP_M.'...');
        }
    }


    protected function getStorageRacine() {
        //trace_log(self::$storageRacine);
        return self::$storageRacine;
    }
    protected function getTempFolderName() {
        return self::$tempFolderName;
    }
    protected function getFullRacine(): string
    {
        return $this->getStorageRacine().($this->getTempFolderName() ? DIRECTORY_SEPARATOR.$this->getTempFolderName() : '');
    }
    protected function getFullPath(): string
    {
        return 'app'.DIRECTORY_SEPARATOR.$this->getStorageRacine().($this->getTempFolderName() ? DIRECTORY_SEPARATOR.$this->getTempFolderName() : '');
    }

    public function putFile(string $pathOrFilename, $content) {
        //A terminer possibilité de mettre plusieurs fichiers et des sous dossier. 
        //trace_log("-------------- putFile ------------------");
        $this->fileName = trim($this->fileName,'/');
        $path = $this->getFullRacine().DIRECTORY_SEPARATOR.$this->fileName;
        Storage::put($path, $content);
        return $this;
    }

    public function putUrlFile(string $url, $forceName = null) {
        $file = file_get_contents($url); // to get file
        $this->fileName = trim(basename($url),'/'); // to get file name
        $ext = pathinfo($url, PATHINFO_EXTENSION); // to get extension
        if($forceName) {
            $this->fileName = trim($forceName, '/');
        }
        $path = $this->getFullRacine().DIRECTORY_SEPARATOR.$this->fileName;
        Storage::put($path, $file);
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
        
    }

    public function getFilePath(): string
    {
        if($this->fileName) {
            return storage_path($this->getFullPath().DIRECTORY_SEPARATOR.$this->fileName);
        } else {
            throw new Exception("Il manque le nom du fichier getFilePath ne fonctionne QUE si il y a eu un putFile");
        }
        
    }

     public function force(): self
    {
        $this->forceCreate = true;
        return $this;
    }

    public function empty(): self
    {
        $this->deleteDirectory($this->getFullRacine());
        //mkdir($this->getFullPath(), 0777, true);
        return $this;
    }

    public function delete(): bool
    {
        return $this->deleteDirectory($this->getFullRacine());
    }

    

    

    

    

    protected function sanitizePath(string $path): string
    {
        $path = rtrim($path);
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    

    protected function removeFilenameFromPath(string $path): string
    {
        if (! $this->isFilePath($path)) {
            return $path;
        }

        return substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
    }

    protected function isFilePath(string $path): bool
    {
        return strpos($path, '.') !== false;
    }

    protected function deleteDirectory(string $path): bool
    {
        if (is_link($path)) {
            return unlink($path);
        }

        if (! file_exists($path)) {
            return true;
        }

        if (! is_dir($path)) {
            return unlink($path);
        }

        Storage::deleteDirectory($this->getFullRacine());

        // foreach (new FilesystemIterator($path) as $item) {
        //     if (! $this->deleteDirectory($item)) {
        //         return false;
        //     }
        // }

        // /*
        //  * By forcing a php garbage collection cycle using gc_collect_cycles() we can ensure
        //  * that the rmdir does not fail due to files still being reserved in memory.
        //  */
        // gc_collect_cycles();

        // return rmdir($path);
    }
}
