<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\PlatformContent;
use App\Models\PlatformContentCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-02 — CMS tin tức / thông báo / banner / hướng dẫn dùng chung.
 *
 * Vòng đời: draft → pending_review → published → archived (AC-09).
 * Đăng/lưu trữ cần quyền platform + ghi audit (AC-10). Nội dung có phạm vi (AC-11).
 */
class PlatformContentCms extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'platform_content';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Nội dung & thông báo';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Tin tức, thông báo & banner nền tảng';

    protected static ?string $slug = 'platform/content';

    protected string $view = 'filament.pages.platform-content-cms';

    public const TYPE = [
        'news' => 'Tin tức', 'announcement' => 'Thông báo', 'banner' => 'Banner',
        'guide' => 'Hướng dẫn', 'public_project' => 'Dự án',
    ];

    public const STATUS = [
        'draft' => ['Nháp', 'gray'],
        'pending_review' => ['Chờ duyệt', 'warning'],
        'published' => ['Đã đăng', 'success'],
        'archived' => ['Lưu trữ', 'gray'],
    ];

    public const SCOPE = [
        'platform' => 'Nền tảng', 'tenant' => 'Công ty', 'company' => 'Tập đoàn',
        'project' => 'Dự án', 'building' => 'Tòa', 'public' => 'Công khai',
    ];

    protected function getViewData(): array
    {
        $c = fn (string $s) => PlatformContent::where('status', $s)->count();

        return [
            'kpis' => [
                ['label' => 'Nháp', 'value' => $c('draft'), 'accent' => 'blue'],
                ['label' => 'Chờ duyệt', 'value' => $c('pending_review'), 'accent' => 'amber'],
                ['label' => 'Đã đăng', 'value' => $c('published'), 'accent' => 'green'],
                ['label' => 'Lưu trữ', 'value' => $c('archived'), 'accent' => 'gray'],
            ],
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private function formSchema(): array
    {
        return [
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
            Select::make('content_type')->label('Loại')->options(self::TYPE)->required()->default('news'),
            Select::make('category_id')->label('Danh mục')->options(fn () => PlatformContentCategory::pluck('name', 'id')),
            Select::make('publish_scope')->label('Phạm vi')->options(self::SCOPE)->required()->default('platform'),
            Select::make('language')->label('Ngôn ngữ')->options(['vi' => 'Tiếng Việt', 'en' => 'English'])->default('vi'),
            Textarea::make('summary')->label('Tóm tắt')->rows(2)->columnSpanFull(),
            RichEditor::make('body')->label('Nội dung')->columnSpanFull(),
            FileUpload::make('cover_image')->label('Ảnh bìa / banner')->image()->disk('public')->directory('platform-content'),
            DateTimePicker::make('expired_at')->label('Hết hạn'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PlatformContent::query()->with(['category', 'creator', 'approver']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->weight('medium')->wrap()
                    ->description(fn (PlatformContent $c) => $c->summary ? \Illuminate\Support\Str::limit($c->summary, 60) : null),
                TextColumn::make('content_type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('publish_scope')->label('Phạm vi')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => self::SCOPE[$state] ?? $state)->toggleable(),
                TextColumn::make('language')->label('Ngôn ngữ')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y')->placeholder('—'),
                TextColumn::make('creator.name')->label('Tác giả')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('content_type')->label('Loại')->options(self::TYPE),
            ])
            ->headerActions([
                Action::make('create')->label('Tạo nội dung')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->formSchema())
                    ->action(function (array $data): void {
                        $data['status'] = 'draft';
                        $data['created_by'] = Auth::id();
                        $c = PlatformContent::create($data);
                        $this->audit('content.create', 'Tạo nội dung: '.$c->title, PlatformContent::class, $c->id);
                        Notification::make()->title('Đã tạo nội dung (nháp)')->success()->send();
                    }),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->visible(fn (PlatformContent $c) => $c->status !== 'archived')
                    ->fillForm(fn (PlatformContent $c) => $c->only(['title', 'content_type', 'category_id', 'publish_scope', 'language', 'summary', 'body', 'cover_image', 'expired_at']))
                    ->schema($this->formSchema())
                    ->action(function (PlatformContent $c, array $data): void {
                        $c->update($data);
                        $this->audit('content.update', 'Sửa nội dung: '.$c->title, PlatformContent::class, $c->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('submit')->label('Gửi duyệt')->iconButton()->icon('heroicon-m-paper-airplane')->color('warning')
                    ->visible(fn (PlatformContent $c) => $c->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (PlatformContent $c) => $this->setStatus($c, 'pending_review', 'content.submit')),
                Action::make('publish')->label('Duyệt & đăng')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (PlatformContent $c) => in_array($c->status, ['pending_review', 'draft'], true) && Auth::user()->isPlatformAdmin())
                    ->requiresConfirmation()->modalDescription('Đăng nội dung này ra phạm vi đã chọn.')
                    ->action(function (PlatformContent $c): void {
                        $c->update(['status' => 'published', 'published_at' => now(), 'approved_by' => Auth::id()]);
                        $this->audit('content.publish', 'Đăng nội dung: '.$c->title, PlatformContent::class, $c->id);
                        Notification::make()->title('Đã đăng nội dung')->success()->send();
                    }),
                Action::make('archive')->label('Lưu trữ')->iconButton()->icon('heroicon-m-archive-box')->color('gray')
                    ->visible(fn (PlatformContent $c) => $c->status === 'published' && Auth::user()->isPlatformAdmin())
                    ->requiresConfirmation()
                    ->action(fn (PlatformContent $c) => $this->setStatus($c, 'archived', 'content.archive')),
                Action::make('duplicate')->label('Nhân bản')->iconButton()->icon('heroicon-m-document-duplicate')->color('gray')
                    ->requiresConfirmation()
                    ->action(function (PlatformContent $c): void {
                        $copy = $c->replicate(['published_at', 'approved_by']);
                        $copy->title = $c->title.' (bản sao)';
                        $copy->status = 'draft';
                        $copy->created_by = Auth::id();
                        $copy->save();
                        $this->audit('content.duplicate', 'Nhân bản nội dung: '.$c->title, PlatformContent::class, $copy->id);
                        Notification::make()->title('Đã nhân bản')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có nội dung')
            ->emptyStateIcon('heroicon-o-newspaper')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Xem trước')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (PlatformContent $c) => $c->title)
            ->modalContent(fn (PlatformContent $c) => view('filament.pages.content-preview', [
                'record' => $c->load(['category', 'creator']),
                'typeMap' => self::TYPE, 'scopeMap' => self::SCOPE, 'statusMap' => self::STATUS,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function setStatus(PlatformContent $c, string $status, string $action): void
    {
        $c->update(['status' => $status]);
        $this->audit($action, self::STATUS[$status][0].': '.$c->title, PlatformContent::class, $c->id);
        Notification::make()->title(self::STATUS[$status][0])->success()->send();
    }
}
