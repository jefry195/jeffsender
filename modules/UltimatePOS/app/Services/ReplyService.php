<?php

namespace Modules\UltimatePOS\App\Services;

use App\Abstracts\ReplyServiceAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReplyService extends ReplyServiceAbstract
{
    /**
     * Memproses permintaan info tagihan dengan mencari data langsung di database UltimatePOS.
     */
    public function process(): static
    {
        $jid = $this->getData('chat_id');
        if (!$jid) {
            return $this;
        }

        // Ambil nomor HP bersih dari JID pengirim
        $phone = str_replace(['@s.whatsapp.net', '@g.us'], '', $jid);
        $phone = preg_replace('/\D/', '', $phone); // Hanya angka

        try {
            // 1. Cari data Kontak di database UltimatePOS
            // Kami mencari di kolom 'mobile' dan 'alternate_number'
            $contact = DB::connection('ultimatepos')
                ->table('contacts')
                ->where('mobile', 'like', "%" . substr($phone, -10) . "%") // Cocokkan 10 digit terakhir untuk akurasi
                ->orWhere('alternate_number', 'like', "%" . substr($phone, -10) . "%")
                ->first();

            if (!$contact) {
                Log::info('UltimatePOS: Kontak tidak ditemukan untuk nomor ' . $phone);
                return $this;
            }

            // 2. Hitung total tagihan (Transaksi Jual yang belum lunas)
            $transactions = DB::connection('ultimatepos')
                ->table('transactions')
                ->where('contact_id', $contact->id)
                ->where('type', 'sell')
                ->whereIn('payment_status', ['due', 'partial']) // Status: Belum Lunas atau Dibayar Sebagian
                ->where('status', 'final') // Pastikan transaksi sudah final
                ->select(
                    DB::raw('SUM(final_total) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->first();

            $totalDue = $transactions->total ?? 0;
            $invoiceCount = $transactions->count ?? 0;

            // 3. Susun isi pesan balasan
            if ($totalDue > 0) {
                $reply = "Halo *{$contact->name}*,\n\n";
                $reply .= "Terima kasih sudah menghubungi *Dooren'z Percetakan*.\n\n";
                $reply .= "Saat ini Anda memiliki *{$invoiceCount} tagihan* yang belum lunas dengan total:\n";
                $reply .= "💰 *Rp " . number_format($totalDue, 0, ',', '.') . "*\n\n";
                $reply .= "Silakan lakukan pembayaran agar pesanan Anda dapat segera kami proses. Terima kasih! 🙏";
                
                $this->addMessage('text', [
                    'text' => $reply
                ]);
            } else {
                $this->addMessage('text', [
                    'text' => "Halo *{$contact->name}*,\n\nTagihan Anda saat ini sudah *LUNAS* atau tidak ada nota yang menggantung. Terima kasih sudah berlangganan di *Dooren'z Percetakan*! ✨"
                ]);
            }

        } catch (\Throwable $th) {
            Log::error('UltimatePOS: Gagal mengambil data tagihan: ' . $th->getMessage());
        }

        return $this;
    }
}
