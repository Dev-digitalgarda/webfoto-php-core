<?php

namespace Webfoto\Core\Utils;

use DateTime;

use Webfoto\Core\Types\Image;

abstract class BaseDatabaseService {
    public abstract function getLastImageDate($name): ?DateTime;
    public abstract function getLastImagePath($name): ?string;
    public abstract function insertImage(Image $image): void;
    public abstract function getImages(string $name): array;
    public abstract function removeImage(string $path): void;
}