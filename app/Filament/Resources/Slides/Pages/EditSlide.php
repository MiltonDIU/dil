<?php

namespace App\Filament\Resources\Slides\Pages;

use App\Filament\Resources\Slides\SlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlide extends EditRecord
{
    protected static string $resource = SlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
