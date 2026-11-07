<?php

namespace App\Filament\Resources\AiMappings\Pages;

use App\Filament\Resources\AiMappings\AiMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiMapping extends EditRecord
{
    protected static string $resource = AiMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
