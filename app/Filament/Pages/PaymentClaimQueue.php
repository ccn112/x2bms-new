<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Concerns\AdminListingBreadcrumbs;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Duyệt chứng từ chuyển khoản cư dân nộp qua app — /admin (chốt 2026-07-30 ý 2).
 *
 * Cư dân tự chuyển khoản rồi up ảnh chứng từ (`POST resident/payments/claim`),
 * khoản nằm ở `payments.status = pending`. Màn này là nơi BQL đối chiếu với sao
 * kê rồi quyết. **Không** viết lại logic ghi nhận tiền: gọi
 * `ResidentPaymentClaimReviewer` (transaction + lockForUpdate) để không có đường
 * nào ghi nhận tiền mà bỏ qua các bất biến ở đó.
 *
 * ## Vì sao KHÔNG đặt màn này trên /sa như màn duyệt tin rao
 *
 * Tin rao thì SuperAdmin duyệt thay được khi BQL không có người — nội dung ai đọc
 * cũng đánh giá được. Chứng từ chuyển khoản thì KHÔNG: duyệt nghĩa là xác nhận
 * tiền đã vào **tài khoản ngân hàng của công ty vận hành**, và chỉ BQL/kế toán
 * của chính công ty đó xem được sao kê. SuperAdmin bấm duyệt là xác nhận một
 * việc mình không có cách nào biết. Nếu sau này cần, đường đúng là cho SA *xem*
 * hàng chờ để nhắc BQL, không phải cho SA *duyệt*.
 *
 * ## Phạm vi dữ liệu
 * `Payment` có `BelongsToProject` (giải qua `building_id`), nhưng vẫn lọc tường
 * minh theo `CurrentContext::buildingIds()` — cùng cách `StatementApprovalQueue`
 * làm. Filament resolve record cho MỌI action từ chính query này, nên khoản
 * ngoài phạm vi không thể bị tác động dù sửa ID trên request.
 */
class PaymentClaimQueue extends Page implements HasTable
{
    use AdminListingBreadcrumbs;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Duyệt chứng từ CK';

    protected static ?int $navigationSort = 25;

    protected static ?string $title = 'Duyệt chứng từ chuyển khoản của cư dân';

    protected static ?string $slug = 'payments/claims';

    protected string $view = 'filament.pages.payment-claim-queue';

    public static function getNavigationBadge(): ?string
    {
        $count = static::baseQuery()->where('status', Payment::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Chỉ khoản do CƯ DÂN khai qua app; khoản BQL nhập tay không thuộc hàng chờ này. */
    protected static function baseQuery(): Builder
    {
        $buildingIds = app(CurrentContext::class)->buildingIds() ?: [0];

        return Payment::query()
            ->where('source', Payment::SOURCE_RESIDENT_APP)
            ->whereIn('building_id', $buildingIds);
    }

    /** @return array<string, int|string> */
    public function getViewData(): array
    {
        $pending = (clone $this->tableQueryBase())->where('status', Payment::STATUS_PENDING);

        return [
            'kpis' => [
                [
                    'label' => 'Chờ duyệt',
                    'value' => (clone $pending)->count(),
                    'accent' => 'warning',
                ],
                [
                    // Chờ quá 1 ngày làm việc là cư dân bắt đầu gọi hỏi — đó là
                    // lúc hàng chờ này trở thành việc của tổng đài, nên phải nhìn
                    // thấy ngay chứ không nằm sau một filter.
                    'label' => 'Chờ quá 24 giờ',
                    'value' => (clone $pending)->where('submitted_at', '<=', now()->subDay())->count(),
                    'accent' => 'danger',
                ],
                [
                    'label' => 'Tổng tiền đang chờ',
                    'value' => number_format((float) (clone $pending)->sum('amount'), 0, ',', '.').' đ',
                    'accent' => 'primary',
                ],
                [
                    'label' => 'Đã duyệt hôm nay',
                    'value' => (clone $this->tableQueryBase())
                        ->where('status', Payment::STATUS_CONFIRMED)
                        ->whereDate('reviewed_at', today())->count(),
                    'accent' => 'success',
                ],
            ],
        ];
    }

    protected function tableQueryBase(): Builder
    {
        return static::baseQuery();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->tableQueryBase()->with(['apartment', 'submittedBy', 'claimedStatement', 'attachments']))
            ->defaultSort('submitted_at', 'asc')  // cũ nhất trước: ai chờ lâu nhất được xử lý trước
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('apartment.code')->label('Căn hộ')->searchable()->placeholder('—'),
                TextColumn::make('submittedBy.name')->label('Người gửi')->placeholder('—'),
                TextColumn::make('amount')->label('Số tiền')->money('VND')->sortable(),
                TextColumn::make('paid_at')->label('CK lúc')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reference_no')->label('Mã tham chiếu')->placeholder('—')->searchable(),
                TextColumn::make('claimedStatement.code')->label('Hoá đơn khai')
                    ->placeholder('— chưa gán —'),
                TextColumn::make('attachments_count')->label('Ảnh')
                    ->counts('attachments')
                    // Khai báo không có ảnh là không đối chiếu được — phải nhìn ra
                    // ngay chứ không phải mở chi tiết mới biết.
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state.' ảnh' : 'KHÔNG có ảnh')
                    ->color(fn ($state) => $state > 0 ? 'gray' : 'danger'),
                TextColumn::make('submitted_at')->label('Gửi lúc')->since()->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => [
                        Payment::STATUS_PENDING => 'Chờ duyệt',
                        Payment::STATUS_CONFIRMED => 'Đã ghi nhận',
                        Payment::STATUS_REJECTED => 'Đã từ chối',
                        Payment::STATUS_REVERSED => 'Đã đảo',
                    ][$state] ?? $state)
                    ->color(fn (string $state) => [
                        Payment::STATUS_PENDING => 'warning',
                        Payment::STATUS_CONFIRMED => 'success',
                        Payment::STATUS_REJECTED => 'danger',
                    ][$state] ?? 'gray'),
                TextColumn::make('review_note')->label('Ghi chú duyệt')->wrap()->limit(60)
                    ->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options([
                        Payment::STATUS_PENDING => 'Chờ duyệt',
                        Payment::STATUS_CONFIRMED => 'Đã ghi nhận',
                        Payment::STATUS_REJECTED => 'Đã từ chối',
                    ])
                    ->default(Payment::STATUS_PENDING),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Duyệt')->icon('heroicon-m-check')->color('success')
                    ->visible(fn (Payment $record) => $record->isAwaitingReview())
                    ->schema([
                        Textarea::make('note')->label('Ghi chú (không bắt buộc)')
                            ->helperText('Ví dụ: đã khớp sao kê VCB ngày 30/07.')
                            ->rows(2)->maxLength(500),
                    ])
                    ->modalHeading('Xác nhận đã nhận được tiền')
                    ->modalDescription(fn (Payment $record) => sprintf(
                        'Ghi nhận %s đ vào %s. Công nợ của cư dân sẽ giảm ngay và biên lai được phát hành. '
                        .'Chỉ duyệt sau khi đã thấy khoản này trong sao kê ngân hàng.',
                        number_format((float) $record->amount, 0, ',', '.'),
                        $record->claimedStatement?->code
                            ? 'hoá đơn '.$record->claimedStatement->code
                            : 'công nợ chung (chưa gán hoá đơn)'
                    ))
                    ->action(function (Payment $record, array $data): void {
                        app(ResidentPaymentClaimReviewer::class)
                            ->approve($record, auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Đã ghi nhận thanh toán')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Từ chối')->icon('heroicon-m-x-mark')->color('danger')
                    ->visible(fn (Payment $record) => $record->isAwaitingReview())
                    ->schema([
                        Textarea::make('reason')->label('Lý do từ chối')
                            ->helperText('CƯ DÂN ĐỌC ĐƯỢC lý do này trong app — viết để họ biết cần sửa gì.')
                            ->required()->rows(3)->maxLength(500),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        app(ResidentPaymentClaimReviewer::class)
                            ->reject($record, auth()->user(), (string) $data['reason']);

                        Notification::make()->title('Đã từ chối chứng từ')->warning()->send();
                    }),
                Action::make('viewProof')
                    ->label('Xem ảnh')->icon('heroicon-m-photo')->color('gray')
                    ->visible(fn (Payment $record) => $record->attachments()->exists())
                    ->modalHeading('Ảnh chứng từ chuyển khoản')
                    ->modalContent(fn (Payment $record) => view(
                        'filament.pages.partials.payment-claim-proof',
                        ['attachments' => $record->attachments]
                    ))
                    ->modalSubmitAction(false),
            ]);
    }
}
