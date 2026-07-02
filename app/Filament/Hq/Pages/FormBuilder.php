<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-04 — Tạo biểu mẫu động. */
class FormBuilder extends Page {
    use HqScreen;
    public static function shouldRegisterNavigation(): bool { return false; }
    protected static ?string $title = 'Tạo biểu mẫu động';
    protected static ?string $slug = 'form-templates/create';
    protected string $view = 'filament.hq.pages.form-builder';
}
