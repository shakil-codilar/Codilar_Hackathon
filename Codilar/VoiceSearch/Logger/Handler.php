<?php
/*******************************************************************************
 * Codilar Hackathon 2023
 * Team Innovation Squad
 *
 * Copyright 2023 Codilar
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Codilar and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Codilar
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Codilar permits you to use and modify this file with few restriction
 * If you have received this file from a source other than Codilar,
 * then your use, modification, or distribution of it
 * requires the prior written permission from Codilar.
 *****************************************************************************/

declare(strict_types=1);

namespace Codilar\VoiceSearch\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/voice-search.log';
}
