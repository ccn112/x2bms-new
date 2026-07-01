<?php

namespace App\Filament\Resources\StaffProfiles;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\StaffProfiles\Pages\CreateStaffProfile;
use App\Filament\Resources\StaffProfiles\Pages\EditStaffProfile;
use App\Filament\Resources\StaffProfiles\Pages\ListStaffProfiles;
use App\Filament\Resources\StaffProfiles\Schemas\StaffProfileForm;
use App\Filament\Resources\StaffProfiles\Tables\StaffProfilesTable;
use App\Models\StaffProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffProfileResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = StaffProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Cơ cấu & Tổ chức';

    protected static ?string $navigationLabel = 'Nhân sự';

    protected static ?string $modelLabel = 'nhân sự';

    protected static ?string $pluralModelLabel = 'nhân sự';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return StaffProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffProfiles::route('/'),
            'create' => CreateStaffProfile::route('/create'),
            'edit' => EditStaffProfile::route('/{record}/edit'),
        ];
    }
}
