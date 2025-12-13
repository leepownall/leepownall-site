<?php

namespace App\Filament\Pages;

use App\Jobs\FetchBodyBatteryJob;
use App\Jobs\FetchSleepJob;
use App\Jobs\FetchStepsJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchSteps')
                ->label('Fetch Steps')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (): void {
                    FetchStepsJob::dispatch();

                    Notification::make()
                        ->title('Steps job dispatched')
                        ->success()
                        ->body('The fetch steps job has been queued successfully.')
                        ->send();
                }),
            Action::make('fetchBodyBattery')
                ->label('Fetch Body Battery')
                ->icon('heroicon-o-battery-100')
                ->color('primary')
                ->action(function (): void {
                    FetchBodyBatteryJob::dispatch();

                    Notification::make()
                        ->title('Body Battery job dispatched')
                        ->success()
                        ->body('The fetch body battery job has been queued successfully.')
                        ->send();
                }),
            Action::make('fetchSleep')
                ->label('Fetch Sleep')
                ->icon('heroicon-o-moon')
                ->color('primary')
                ->action(function (): void {
                    FetchSleepJob::dispatch();

                    Notification::make()
                        ->title('Sleep job dispatched')
                        ->success()
                        ->body('The fetch sleep job has been queued successfully.')
                        ->send();
                }),
        ];
    }
}
