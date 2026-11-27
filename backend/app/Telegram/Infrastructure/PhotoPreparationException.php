<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use RuntimeException;

/** Возникает до начала HTTP-запроса, поэтому можно освободить резерв отправки. */
final class PhotoPreparationException extends RuntimeException {}
