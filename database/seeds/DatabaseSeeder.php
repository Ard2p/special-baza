<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('proposal_statuses')->insert([
                [
                    'name' => 'Открыта',
                    'proposal_status_id' => 0,
                ],
                [
                    'name' => 'Закрыта',
                    'proposal_status_id' => 1,
                ],
                [
                    'name' => 'В работе',
                    'proposal_status_id' => 2,
                ],
                [
                    'name' => 'Завершен',
                    'proposal_status_id' => 3,
                ],
                [
                    'name' => 'Заблокирован',
                    'proposal_status_id' => 4,
                ],
                [
                    'name' => 'Горящая',
                    'proposal_status_id' => 5,
                ],
            ]
        );

        DB::table('transaction_types')->insert([
                [
                    'name' => 'Пополнение счета.',
                    'transaction_type' => 'refill',
                ],
                [
                    'name' => 'Подтверждение вывода средств.',
                    'transaction_type' => 'withdrawal',
                ],
                [
                    'name' => 'Резервация средств.',
                    'transaction_type' => 'reserve',
                ],
                [
                    'name' => 'Возврат резервных средств.',
                    'transaction_type' => 'return_reserve',
                ],
                [
                    'name' => 'Выполненый заказ.',
                    'transaction_type' => 'reward',
                ],
                [
                    'name' => 'Штраф.',
                    'transaction_type' => 'fine',
                ],
            ]
        );
    }
}

