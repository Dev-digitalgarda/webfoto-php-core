<?php

declare(strict_types=1);

// Load vendor libs

require_once(__DIR__ . '/vendor/autoload.php');

// Load types

require_once(__DIR__ . '/src/types/Image.php');
require_once(__DIR__ . '/src/types/InputImage.php');
require_once(__DIR__ . '/src/types/DriverType.php');

// Load drivers

require_once(__DIR__ . '/src/drivers/BaseDriver.php');
require_once(__DIR__ . '/src/drivers/DahuaDriver.php');
require_once(__DIR__ . '/src/drivers/HikvisionDriver.php');

// Load utils

require_once(__DIR__ . '/src/utils/Logger.php');
require_once(__DIR__ . '/src/utils/FtpService.php');
require_once(__DIR__ . '/src/utils/EmailService.php');
require_once(__DIR__ . '/src/utils/BaseDatabaseService.php');
require_once(__DIR__ . '/src/utils/ImagesHandler.php');
