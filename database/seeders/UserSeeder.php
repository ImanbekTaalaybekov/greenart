<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * Seed baseline users for every role.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Администратор',
                'email' => 'admin@greenart.test',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Садовник',
                'email' => 'worker@greenart.test',
                'role' => User::ROLE_WORKER,
            ],
            [
                'name' => 'Клиент',
                'email' => 'client@greenart.test',
                'role' => User::ROLE_CLIENT,
            ],
            [
                'name' => 'Бухгалтер',
                'email' => 'accountant@greenart.test',
                'role' => User::ROLE_ACCOUNTANT,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => $user['role'],
                ]
            );
        }
    }
}
