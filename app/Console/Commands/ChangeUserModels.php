<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\AiUser;
use Illuminate\Console\Command;

class ChangeUserModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change_user_models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign free AI models to all AI users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $freeModelIds = AiModel::query()
            ->where('is_text', true)
            ->where('is_free', true)
            ->pluck('model_id')
            ->all();

        if (count($freeModelIds) === 0) {
            $this->error('Nessun modello free disponibile.');

            return 1;
        }

        $updatedUsers = 0;

        AiUser::query()
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($freeModelIds, &$updatedUsers): void {
                foreach ($users as $user) {
                    $selectedModelId = $freeModelIds[array_rand($freeModelIds)];

                    $user->generated_by_model = $selectedModelId;
                    $user->is_pay = false;
                    $user->save();

                    $updatedUsers++;
                }
            });

        $this->info("Aggiornati {$updatedUsers} utenti con modelli free.");

        return 0;
    }
}
