<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateCategory;
use App\Models\DocumentTemplateShare;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-09 — Chính sách kế thừa/chia sẻ mẫu tài liệu Platform → Company → Project.
 *
 * Áp dụng chính sách theo danh mục (xem trước số mẫu ảnh hưởng), rollback tạo audit.
 * force_apply cần quyền SuperAdmin (AC-19). Chế độ: view_only|use_as_template|clone_allowed|force_apply.
 */
class TemplateInheritancePolicy extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'kb_inheritance';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-pointing-out';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Chính sách kế thừa mẫu';

    protected static ?int $navigationSort = 51;

    protected static ?string $title = 'Chính sách kế thừa & chia sẻ mẫu';

    protected static ?string $slug = 'platform/template-inheritance';

    protected string $view = 'filament.pages.template-inheritance-policy';

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Chính sách đang áp', 'value' => DocumentTemplateShare::where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Áp dụng bắt buộc', 'value' => DocumentTemplateShare::where('share_mode', 'force_apply')->where('status', 'active')->count(), 'accent' => 'red'],
                ['label' => 'Cho phép clone', 'value' => DocumentTemplateShare::where('share_mode', 'clone_allowed')->where('status', 'active')->count(), 'accent' => 'blue'],
                ['label' => 'Mẫu có thể chia sẻ', 'value' => DocumentTemplate::where('status', 'active')->count(), 'accent' => 'blue'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DocumentTemplateShare::query()->with('template.category'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('template.title')->label('Mẫu')->searchable()->weight('medium')
                    ->description(fn (DocumentTemplateShare $s) => $s->template?->category?->name),
                TextColumn::make('from_scope')->label('Từ cấp')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => DocumentTemplateLibrary::SCOPE[$state] ?? $state),
                TextColumn::make('to_scope')->label('Tới cấp')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => DocumentTemplateLibrary::SCOPE[$state] ?? $state),
                TextColumn::make('share_mode')->label('Chế độ')->badge()
                    ->formatStateUsing(fn (string $state) => DocumentTemplateLibrary::SHARE_MODE[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'force_apply' ? 'danger' : ($state === 'clone_allowed' ? 'info' : 'gray')),
                IconColumn::make('can_ai_read')->label('AI')->boolean()->alignCenter(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('effective_from')->label('Hiệu lực')->date('d/m/Y')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('share_mode')->label('Chế độ')->options(DocumentTemplateLibrary::SHARE_MODE),
                SelectFilter::make('status')->label('Trạng thái')->options(['active' => 'Đang áp', 'inactive' => 'Đã gỡ']),
            ])
            ->headerActions([
                Action::make('applyPolicy')->label('Áp dụng chính sách')->icon('heroicon-m-bolt')->color('primary')
                    ->schema([
                        Select::make('category_id')->label('Danh mục mẫu')->required()->live()
                            ->options(fn () => DocumentTemplateCategory::pluck('name', 'id')),
                        Select::make('to_scope')->label('Chia sẻ tới cấp')->options(DocumentTemplateLibrary::SCOPE)->required()->default('tenant'),
                        Select::make('to_owner_id')->label('Công ty (tùy chọn)')->options(fn () => Tenant::pluck('name', 'id')),
                        Select::make('share_mode')->label('Chế độ')->options(DocumentTemplateLibrary::SHARE_MODE)->required()->default('use_as_template'),
                        Toggle::make('can_ai_read')->label('Cho AI đọc')->default(true),
                    ])
                    ->modalDescription(fn (array $arguments, $livewire) => 'Chính sách sẽ áp cho toàn bộ mẫu ĐANG DÙNG trong danh mục đã chọn.')
                    ->action(fn (array $data) => $this->applyPolicy($data)),
            ])
            ->recordActions([
                Action::make('rollback')->label('Gỡ chính sách')->iconButton()->icon('heroicon-m-arrow-uturn-left')->color('danger')
                    ->visible(fn (DocumentTemplateShare $s) => $s->status === 'active')
                    ->requiresConfirmation()->modalHeading('Gỡ chính sách chia sẻ')
                    ->action(function (DocumentTemplateShare $s): void {
                        $s->update(['status' => 'inactive', 'effective_to' => now()]);
                        $this->audit('template.policy_rollback', 'Gỡ chia sẻ mẫu #'.$s->template_id.' → '.$s->to_scope, DocumentTemplateShare::class, $s->id);
                        Notification::make()->title('Đã gỡ chính sách')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có chính sách chia sẻ')
            ->emptyStateIcon('heroicon-o-arrows-pointing-out')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function applyPolicy(array $data): void
    {
        // force_apply cần quyền SuperAdmin (AC-19).
        if ($data['share_mode'] === 'force_apply' && ! Auth::user()->isPlatformAdmin()) {
            Notification::make()->title('Áp dụng bắt buộc cần quyền SuperAdmin')->danger()->send();

            return;
        }

        $templates = DocumentTemplate::where('category_id', $data['category_id'])->where('status', 'active')->get();
        $created = 0;
        foreach ($templates as $t) {
            $share = DocumentTemplateShare::firstOrCreate(
                ['template_id' => $t->id, 'to_scope' => $data['to_scope'], 'to_owner_id' => $data['to_owner_id'] ?? null, 'status' => 'active'],
                [
                    'from_scope' => $t->owner_scope, 'from_owner_id' => $t->owner_id,
                    'share_mode' => $data['share_mode'], 'can_ai_read' => $data['can_ai_read'] ?? true, 'effective_from' => now(),
                ]
            );
            if ($share->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->audit('template.policy_apply', 'Áp chính sách chia sẻ danh mục #'.$data['category_id'].' → '.$data['to_scope'].' ('.$created.' mẫu)', DocumentTemplateCategory::class, (int) $data['category_id']);
        Notification::make()->title("Đã áp chính sách cho {$created}/{$templates->count()} mẫu")->success()->send();
    }
}
