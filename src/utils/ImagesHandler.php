<?php

namespace Webfoto\Core\Utils;

use DateTime;
use DateInterval;
use Exception;

use Webmozart\PathUtil\Path;

use Webfoto\Core\Types\DriverType;
use Webfoto\Core\Types\Image;

use Webfoto\Core\Drivers\BaseDriver;
use Webfoto\Core\Drivers\DahuaDriver;
use Webfoto\Core\Drivers\HikvisionDriver;

use Webfoto\Core\Utils\BaseDatabaseService;
use Webfoto\Core\Utils\FtpService;
use Webfoto\Core\Utils\Logger;

class ImagesHandler
{
    private string $name;
    private int $lastDaysToKeep;
    private string $inputPath;
    private string $outputDir;
    private string $outputPath;
    private DriverType $driverType;
    private int $keepEverySeconds;
    private BaseDatabaseService $db;
    private ?FtpService $ftp;

    private BaseDriver $driver;

    private function getDriver(): void
    {
        switch ($this->driverType) {
            case DriverType::DAHUA():
                $this->driver = new DahuaDriver($this->inputPath);
                break;
            case DriverType::HIKVISION():
                $this->driver = new HikvisionDriver($this->inputPath);
                break;
            default:
                throw new Exception("Driver {$this->driverType} not found");
        }
    }

    private function getNextMinimumTimetamp(?DateTime $timestamp): ?DateTime
    {
        $secs = $timestamp === null ? null : strtotime($timestamp->format('Y-m-d H:i:s \U\T\C')) + $this->keepEverySeconds;
        return $secs === null ? null : new Datetime("@{$secs}");
    }

    function __construct(array $settings, string $outputFotosPath, int $lastDaysToKeep, BaseDatabaseService $db, string $cwd)
    {
        $this->name = $settings['name'];
        $this->lastDaysToKeep = $lastDaysToKeep;
        $this->inputPath = $settings['inputPath'][0] === '/' ? $settings['inputPath'] : Path::join($cwd, $settings['inputPath']);
        $this->driverType = new DriverType($settings['driver']);
        $this->keepEverySeconds = $settings['keepEverySeconds'];

        $this->outputDir = $outputFotosPath;
        $this->outputPath = Path::join($outputFotosPath, $this->name);
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }

        $this->getDriver();

        $this->db = $db;

        $this->ftp = $settings['ftp'] ? new FtpService($settings['ftp']) : null;
    }

    public function handle(): void
    {
        Logger::$logger->info('Handling album images', [$this->name]);

        Logger::$logger->debug('Retrieving images', [$this->name]);
        $inputImages = $this->driver->analyzeAlbum();
        Logger::$logger->debug('Retrieving last timestamp', [$this->name]);
        $lastTimestamp = $this->db->getLastImageDate($this->name);

        $toDeleteImages = [];
        $toSaveImages = [];

        Logger::$logger->debug('Smisting images', [$this->name]);
        $currentTimestamp = $this->getNextMinimumTimetamp($lastTimestamp);
        foreach ($inputImages as $image) {
            if ($currentTimestamp === null || $image->timestamp >= $currentTimestamp) {
                array_push($toSaveImages, $image);
                $currentTimestamp = $this->getNextMinimumTimetamp($image->timestamp);
            } else {
                array_push($toDeleteImages, $image);
            }
        }

        Logger::$logger->debug('Deleting images', [$this->name]);
        foreach ($toDeleteImages as $image) {
            unlink($image->path);
        }
        Logger::$logger->debug('Saving images', [$this->name]);
        foreach ($toSaveImages as $index => $image) {
            $filename = $image->timestamp->format('Y-m-d\TH:i:s') . '.jpg';
            $imagePath = "/{$this->name}/{$filename}";
            $toSaveImage = new Image(
                $this->name,
                $imagePath,
                $image->timestamp
            );
            $this->db->insertImage($toSaveImage);
            rename($image->path, Path::join($this->outputPath, $filename));

            if ($index === count($toSaveImages) - 1 && $this->ftp !== null) {
                $filePath = Path::join($this->outputDir, $imagePath);
                $this->ftp->uploadImage($filePath);
            }
        }

        Logger::$logger->debug('Removing too old images', [$this->name]);
        $firstAcceptableDay = (new DateTime())
            ->setTime(0, 0)
            ->sub(new DateInterval("P{$this->lastDaysToKeep}D"));
        $images = $this->db->getImages($this->name);
        foreach ($images as $imageTimestamp) {
            if ($imageTimestamp < $firstAcceptableDay) {
                $filename = $imageTimestamp->format('Y-m-d\TH:i:s') . '.jpg';
                $imagePath = "/{$this->name}/{$filename}";
                $toRemovePath = Path::join($this->outputPath, $filename);
                unlink($toRemovePath);
                $this->db->removeImage($imagePath);
            }
        }

        Logger::$logger->info('Finished handling album', [$this->name]);
    }
}
