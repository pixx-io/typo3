<?php
defined('TYPO3') || die();

$GLOBALS['TCA']['sys_file_reference']['columns']['crop']['displayCond'] =
    'USER:Pixxio\\PixxioExtension\\Condition\\ShowCropIfNotPixxioDirectLink->evaluate';
