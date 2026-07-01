<?php

namespace App\Filament\Resources\TenantPartnerAssignments;

use App\Filament\Resources\TenantPartnerAssignments\Pages\CreateTenantPartnerAssignment;
use App\Filament\Resources\TenantPartnerAssignments\Pages\EditTenantPartnerAssignment;
use App\Filament\Resources\TenantPartnerAssignments\Pages\ListTenantPartnerAssignments;
use App\Filament\Resources\TenantPartnerAssignments\Schemas\TenantPartnerAssignmentForm;
use App\Filament\Resources\TenantPartnerAssignments\Tables\TenantPartnerAssignmentsTable;
use App\Models\TenantPartnerAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantPartnerAssignmentResource extends Resource
{
    protected static ?string $model = TenantPartnerAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TenantPartnerAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantPartnerAssignmentsTable::configure($table);
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
            'index' => ListTenantPartnerAssignments::route('/'),
            'create' => CreateTenantPartnerAssignment::route('/create'),
            'edit' => EditTenantPartnerAssignment::route('/{record}/edit'),
        ];
    }
}
