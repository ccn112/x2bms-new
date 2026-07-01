<?php

namespace App\Filament\Resources\AccessDevices;

use App\Filament\Resources\AccessDevices\Pages\CreateAccessDevice;
use App\Filament\Resources\AccessDevices\Pages\EditAccessDevice;
use App\Filament\Resources\AccessDevices\Pages\ListAccessDevices;
use App\Filament\Resources\AccessDevices\Schemas\AccessDeviceForm;
use App\Filament\Resources\AccessDevices\Tables\AccessDevicesTable;
use App\Models\AccessDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccessDeviceResource extends Resource
{
    protected static ?string $model = AccessDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AccessDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccessDevicesTable::configure($table);
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
            'index' => ListAccessDevices::route('/'),
            'create' => CreateAccessDevice::route('/create'),
            'edit' => EditAccessDevice::route('/{record}/edit'),
        ];
    }
}
