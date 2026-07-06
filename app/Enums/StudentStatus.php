<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Aktif = 'aktif';
    case Cuti = 'cuti';
    case DO = 'DO';
    case Lulus = 'lulus';
}
