<?php

namespace App\Filament\Resources\AiApprovals;

use App\Filament\Resources\AiApprovals\Pages\CreateAiApproval;
use App\Filament\Resources\AiApprovals\Pages\EditAiApproval;
use App\Filament\Resources\AiApprovals\Pages\ListAiApprovals;
use App\Filament\Resources\AiApprovals\Schemas\AiApprovalForm;
use App\Filament\Resources\AiApprovals\Tables\AiApprovalsTable;
use App\Models\AiApproval;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiApprovalResource extends Resource
{
    protected static ?string $model = AiApproval::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AiApprovalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiApprovalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiApprovals::route('/'),
            'create' => CreateAiApproval::route('/create'),
            'edit' => EditAiApproval::route('/{record}/edit'),
        ];
    }
}
