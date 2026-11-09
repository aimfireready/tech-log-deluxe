<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * @var string
     */
    protected $description = 'Create a login (there is no public sign-up)';

    public function handle(): int
    {
        $data = [
            'name' => text('Name', required: true),
            'email' => text('Email', required: true),
            'password' => password('Password', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create($validator->validated());

        $this->info("Created {$data['email']}.");

        return self::SUCCESS;
    }
}
