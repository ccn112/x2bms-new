<?php

namespace App\Filament\Resources\IotDevices;

use App\Filament\Resources\IotDevices\Pages\CreateIotDevice;
use App\Filament\Resources\IotDevices\Pages\EditIotDevice;
use App\Filament\Resources\IotDevices\Pages\ListIotDevices;
use App\Filament\Resources\IotDevices\Schemas\IotDeviceForm;
use App\Filament\Resources\IotDevices\Tables\IotDevicesTable;
use App\Models\IotDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IotDeviceResource extends Resource
{
    protected static ?string $model = IotDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IotDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IotDevicesTable::configure($table);
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
            'index' => ListIotDevices::route('/'),
            'create' => CreateIotDevice::route('/create'),
            'edit' => EditIotDevice::route('/{record}/edit'),
        ];
    }
}
