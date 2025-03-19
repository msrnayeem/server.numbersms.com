<?php

use App\Console\Commands\TextVerifyApimake;
use App\Console\Commands\TextVerifyPrice;
use App\Console\Commands\ServicesTimeOutGet;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// make new service list
Schedule::command(TextVerifyApimake::class)->daily();
// get prices
Schedule::command(TextVerifyPrice::class)->everyFiveSeconds();
Schedule::command(ServicesTimeOutGet::class)->everyFiveSeconds();