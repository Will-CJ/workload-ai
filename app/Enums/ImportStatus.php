<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';          // semua baris valid
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';                // file tidak bisa dibaca / header salah
}
