<?php

namespace App\Filament\Resources\AiMappings\Pages;

use App\Filament\Resources\AiMappings\AiMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiMappings extends ListRecords
{
    protected static string $resource = AiMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
