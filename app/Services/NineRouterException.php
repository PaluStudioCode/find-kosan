<?php

namespace App\Services;

use RuntimeException;

/**
 * Exception yang dilempar oleh NineRouterService
 * ketika request ke 9Router gagal (timeout, HTTP error, response invalid).
 */
class NineRouterException extends RuntimeException
{
}
