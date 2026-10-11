<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamSetting;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    public function next(Team $team, string $type): string
    {
        return DB::transaction(function () use ($team, $type) {
            $settings = TeamSetting::query()
                ->where('team_id', $team->id)
                ->lockForUpdate()
                ->first();

            if (! $settings) {
                $settings = TeamSetting::forTeam($team);
                $settings = TeamSetting::query()->where('team_id', $team->id)->lockForUpdate()->firstOrFail();
            }

            [$column, $prefix] = match ($type) {
                'request' => ['next_request_number', 'SR'],
                'estimate' => ['next_estimate_number', 'EST'],
                'work_order' => ['next_work_order_number', 'WO'],
                'invoice' => ['next_invoice_number', 'INV'],
                default => throw new \InvalidArgumentException("Unknown document type [{$type}]."),
            };

            $number = (int) $settings->{$column};
            $settings->{$column} = $number + 1;
            $settings->save();

            return sprintf('%s-%s-%d', $prefix, strtoupper($team->slug), $number);
        });
    }
}
