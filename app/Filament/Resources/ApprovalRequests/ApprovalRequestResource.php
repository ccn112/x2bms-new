<?php

namespace App\Filament\Resources\ApprovalRequests;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\ApprovalRequests\Pages\CreateApprovalRequest;
use App\Filament\Resources\ApprovalRequests\Pages\EditApprovalRequest;
use App\Filament\Resources\ApprovalRequests\Pages\ListApprovalRequests;
use App\Filament\Resources\ApprovalRequests\Schemas\ApprovalRequestForm;
use App\Filament\Resources\ApprovalRequests\Tables\ApprovalRequestsTable;
use App\Models\ApprovalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApprovalRequestResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = ApprovalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ApprovalRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalRequestsTable::configure($table);
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
            'index' => ListApprovalRequests::route('/'),
            'create' => CreateApprovalRequest::route('/create'),
            'edit' => EditApprovalRequest::route('/{record}/edit'),
        ];
    }
}
