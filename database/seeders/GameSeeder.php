<?php

namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\SeederHelper;
use App\Models\Game;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Game::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='games'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Game::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Game::truncate();
        }

        $games = SeederHelper::GAMES;

        foreach ($games as $game) {
            $saveGame = Game::factory()->state([
                'name' => $game['name'],
                'brief' => $game['brief'],
                'how_to_play' => $game['how_to_play'],
            ])->create();

            if($saveGame && $game["logo_path"]){
                $logoAssetUrl = asset($game["logo_path"]);

                try {
                    $headers = get_headers($logoAssetUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $logoAssetExtension = pathinfo($logoAssetUrl, PATHINFO_EXTENSION);
                        $logoAssetExtension = in_array($logoAssetExtension, ["png", "jpg", "jpeg"]) ? $logoAssetExtension : "png";
                        $logoAssetFileName  = MediaHelper::generateMediaName($saveGame->name, $logoAssetExtension, 200);
                        $saveGame->addMediaFromUrl($logoAssetUrl)
                            ->usingName($saveGame->name)
                            ->usingFileName($logoAssetFileName)
                            ->withCustomProperties(['caption' => $saveGame->name, 'alt' => $saveGame->name, "role" => MediaHelper::ROLE_GAME_LOGO])
                            ->toMediaCollection($saveGame->media_collection_name);
                    } else {
                        Log::info("Image not accessable game: {$saveGame->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for game {$saveGame->name}: {$ex->getMessage()}");
                }

            }
        }
    }
}
