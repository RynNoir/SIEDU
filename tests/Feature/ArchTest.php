<?php

arch('tidak ada debug statement tertinggal')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('controller diberi suffix Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('form request diberi suffix Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('enum di namespace App\Enums adalah enum')
    ->expect('App\Enums')
    ->toBeEnums();
