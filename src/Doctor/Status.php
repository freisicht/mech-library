<?php

declare(strict_types=1);

namespace App\Doctor;

enum Status: string
{
    case Ok   = 'ok';
    case Warn = 'warn';
    case Fail = 'fail';
}
