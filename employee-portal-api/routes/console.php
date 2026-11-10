<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Password as PasswordRule;

Artisan::command('portal:create-admin', function () {
    $name = $this->ask('Administrator name');
    $email = strtolower(trim((string) $this->ask('Administrator email')));
    $password = $this->secret('Password (at least 12 characters, letters and numbers)');
    $validator = validator(compact('name', 'email', 'password'), [
        'name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'max:255', PasswordRule::min(12)->letters()->numbers()],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    $user = new User($validator->validated());
    $user->is_admin = true;
    $user->save();
    $this->info('Administrator created.');

    return 0;
})->purpose('Create a portal administrator with a securely entered password');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
