<?php

namespace App\Services;

use App\Models\Player;
use App\Http\Requests\PlayerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PlayerService
{

    public function firstBySlug(string $slug): array
    {
        try {

            $player = Player::where('slug', $slug)->orWhere('email', $slug)->orWhere('mobile', $slug)->first();

            if (!$player) {
                return [
                    'status' => 'error',
                    'message' => 'Player not found.',
                ];
            }

            return [
                'status' => 'success',
                'data' => $player,
            ];
        } catch (Exception $exception) {
            Log::error('Player get failed.', [
                'exception' => $exception,
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to get player. Please try again.',
            ];
        }
    }

    public function save(PlayerRequest $request)
    {
        try {
            $player = DB::transaction(function () use ($request) {
                $slug = $request->input('slug');
                $email = $request->input('email');
                $mobile = $request->input('mobile');

                $player = $slug ? Player::where('slug', $slug)->first() : null;

                if (! $player && ($email || $mobile)) {
                    $query = Player::query();

                    if ($email) {
                        $query->where('email', $email);
                    }

                    if ($mobile) {
                        $query->orWhere('mobile', $mobile);
                    }

                    $player = $query->first();
                }

                if (! $player) {
                    $player = new Player();

                    $player->name = $request->input('name');
                    $player->email = $email;
                    $player->mobile = $mobile;
                    $player->address = $request->input('address');

                    $player->save();

                    return $player;
                }

                $player->name = $request->input('name');
                $player->email = $email;
                $player->mobile = $mobile;
                $player->address = $request->input('address');

                if ($player->isDirty()) {
                    $player->save();
                }

                return $player;
            });

            return [
                'status' => 'success',
                'data' => $player,
            ];
        } catch (Exception $exception) {
            Log::error('Player save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to save user. Please try again.',
            ];
        }
    }
}
