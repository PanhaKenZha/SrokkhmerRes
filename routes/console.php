<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('fastfood:sync',function(){
    try {
        $count=app(\App\Services\FastFoodFeed::class)->import();
        $this->info("Imported {$count} fast-food change records. Restaurant menu prices are unchanged.");
    } catch(\Throwable $exception){
        $this->error('Fast-food sync failed. Existing records are unchanged. Check the provider connection or response format.');
        return 1;
    }
})->purpose('Sync the supplied McDonald\'s menu-change feed');

Artisan::command('meals:sync {--search=chicken : Meal name to search}', function () {
    try {
        $count=app(\App\Services\MealCatalog::class)->import((string)$this->option('search'));
        $this->info("Imported {$count} meals from TheMealDB. Existing prices preserved.");
    } catch (\Throwable $exception) {
        $this->error('Import failed. Existing menu is unchanged. Check API connectivity or the response format.');
        return 1;
    }
})->purpose('Import every meal returned by a TheMealDB name search');

Artisan::command('restaurant:manager', function () {
    $email = config('restaurant.staff_email');
    $password = $this->secret('Choose a manager password (at least 12 characters)');
    if (!is_string($password) || strlen($password) < 12) {
        $this->error('Password must contain at least 12 characters.');
        return 1;
    }
    \App\Models\User::updateOrCreate(['email'=>$email], ['name'=>'Restaurant Manager', 'password'=>\Illuminate\Support\Facades\Hash::make($password)]);
    $this->info('Manager account ready: '.$email);
})->purpose('Create or reset the restaurant manager');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
