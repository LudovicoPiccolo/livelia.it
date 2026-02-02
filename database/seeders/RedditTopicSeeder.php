<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RedditTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            'Italia',
            'News',
            'Technology',
            'WorldNews',
            'Europe',
            'Sport',
            'Calcio',
            'seriea',
            'CasualIT',
            'ItalyInformatica',
            'politicaITA',
            'innovazione',
        ];

        foreach ($topics as $topic) {
            \App\Models\RedditTopic::firstOrCreate(
                ['name' => $topic],
                ['is_active' => true]
            );
        }
    }
}
