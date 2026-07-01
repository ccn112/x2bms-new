<?php

namespace App\Filament\Resources\SmartDevices;

use App\Filament\Concerns\SoftDeletableResource;

use App\Filament\Resources\SmartDevices\Pages\CreateSmartDevice;
use App\Filament\Resources\SmartDevices\Pages\EditSmartDevice;
use App\Filament\Resources\SmartDevices\Pages\ListSmartDevices;
use App\Filament\Resources\SmartDevices\Schemas\SmartDeviceForm;
use App\Filament\Resources\SmartDevices\Tables\SmartDevicesTable;
use App\Models\SmartDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmartDeviceResource extends Resource
{
    use SoftDeletableResource;

    protected static ?string $model = SmartDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SmartDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmartDevicesTable::configure($table);
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
            'index' => ListSmartDevices::route('/'),
            'create' => CreateSmartDevice::route('/create'),
            'edit' => EditSmartDevice::route('/{record}/edit'),
        ];
    }
}
