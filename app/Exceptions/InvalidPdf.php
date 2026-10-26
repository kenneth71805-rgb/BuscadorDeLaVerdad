<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown with a message that is safe to show to the person who uploaded the file. */
class InvalidPdf extends RuntimeException
{
}
