<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-08 — Chi tiết ticket. */
class TicketDetail extends Page {
    use HqScreen;
    public static function shouldRegisterNavigation(): bool { return false; }
    protected static ?string $slug = 'support-tickets/{ticket}';
    protected string $view = 'filament.hq.pages.ticket-detail';
    public SupportTicket $ticket;
    public function mount(SupportTicket $ticket): void {
        $user = auth()->user();
        if(! $user->isPlatformAdmin()) abort_unless((int)$ticket->tenant_id === (int)$user->tenant_id, 404);
        $this->ticket = $ticket;
    }
    public function getTitle(): string { return $this->ticket->ticket_no.' — '.$this->ticket->subject; }
    protected function getViewData(): array {
        return ['t'=>$this->ticket,'messages'=>SupportTicketMessage::where('support_ticket_id',$this->ticket->id)->orderBy('id')->get()];
    }
}
