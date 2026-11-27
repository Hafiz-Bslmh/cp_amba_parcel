<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class potential_simulation extends Controller
{
    /**
     * Halaman tunggal "Simulasi Usaha Armada Parcel & FAQ" untuk Amba Parcel.
     * Semua angka bersifat ILUSTRATIF untuk keperluan simulasi akademik
     * mata kuliah Manajemen Proyek.
     *
     * Controller adalah satu-satunya sumber kebenaran data. Rumus perhitungan
     * di Alpine.js (calc() pada view) harus selaras dengan monthlyNet() di bawah.
     */
    public function simulation(Request $request): View
    {
        $company     = $this->getCompany();
        $baseline    = $this->getAssumptions();
        $fleets      = $this->getFleets();
        $models      = $this->getBusinessModels();
        $managements = $this->getManagements($baseline);
        $fundings    = $this->getFundings();
        $days        = $this->getOperatingDays();
        $contracts   = $this->getContracts();

        return view('dashboard.potential_simulation', [
            'pageTitle'   => 'Simulasi Usaha Armada Parcel & FAQ | Amba Parcel',
            'company'     => $company,
            'fleets'      => $fleets,
            'models'      => $models,
            'managements' => $managements,
            'fundings'    => $fundings,
            'days'        => $days,
            'contracts'   => $contracts,
            'baseline'    => $baseline,
            // Data yang dikirim ke Alpine.js.
            'config'      => [
                'fleets'      => $fleets,
                'models'      => $models,
                'managements' => $managements,
                'fundings'    => $fundings,
                'assumptions' => $baseline,
                'defaults'    => $this->getDefaults(),
                'faqs'        => $this->getFaqs($fleets, $models, $managements, $fundings, $baseline),
            ],
        ]);
    }

    /** Identitas perusahaan untuk navbar dan footer. */
    private function getCompany(): array
    {
        return [
            'name'    => 'Amba Parcel',
            'tagline' => 'Solusi Armada & Bisnis Parcel Terintegrasi',
        ];
    }

    /**
     * Asumsi acuan model keuangan (per unit, per bulan).
     * - base_utilization : utilisasi acuan (%) untuk angka pendapatan dan opex
     * - base_days        : hari operasi acuan per bulan
     * - fixed_cost_share : porsi biaya tetap acuan (dipakai skema Turnkey)
     */
    private function getAssumptions(): array
    {
        return [
            'base_utilization' => 85,
            'base_days'        => 26,
            'fixed_cost_share' => 0.60,
        ];
    }

    /** Pilihan awal saat halaman dibuka dan saat tombol Reset ditekan. */
    private function getDefaults(): array
    {
        return [
            'units'       => '1',
            'fleet'       => 'blindvan',
            'model'       => 'contract',
            'management'  => 'turnkey',
            'funding'     => 'cash',
            'years'       => 1,
            'days'        => 26,
            'utilization' => 85,
        ];
    }

    /**
     * Katalog armada parcel.
     * price / revenue / opex dalam Rupiah per unit.
     * revenue & opex berlaku pada utilisasi acuan dan hari operasi acuan.
     * parcels_per_day = kapasitas paket per hari pada utilisasi 100%.
     */
    private function getFleets(): array
    {
        return [
            [
                'key' => 'motor', 'name' => 'Motor Box Kurir', 'type' => 'Roda dua',
                'capacity' => '±50 kg', 'volume' => '±0,3 m³', 'volume_m3' => 0.3,
                'parcels_per_day' => 90, 'trips_per_day' => 3,
                'price' => 32000000, 'revenue' => 9000000, 'opex' => 5100000,
                'tags' => ['Last-mile', 'Zona padat', 'Modal ringan'],
                'desc' => 'Kurir roda dua dengan box untuk pengantaran last-mile di perumahan, gang sempit, dan kawasan padat. Modal paling kecil, cocok untuk menguji satu zona sebelum menambah unit.',
            ],
            [
                'key' => 'pickup', 'name' => 'Pickup Box Parcel', 'type' => 'Pickup tertutup',
                'capacity' => '±700 kg', 'volume' => '±1,8 m³', 'volume_m3' => 1.8,
                'parcels_per_day' => 220, 'trips_per_day' => 3,
                'price' => 205000000, 'revenue' => 22000000, 'opex' => 10500000,
                'tags' => ['First-mile', 'Jemput seller', 'Feeder agen'],
                'desc' => 'Pickup tertutup untuk menjemput paket dari seller dan UMKM (first-mile), lalu menyuplai drop point atau agen. Fleksibel untuk rute campuran jemput dan antar.',
            ],
            [
                'key' => 'blindvan', 'name' => 'Blind Van Parcel', 'type' => 'Van tertutup',
                'capacity' => '±800 kg', 'volume' => '±4,5 m³', 'volume_m3' => 4.5,
                'parcels_per_day' => 380, 'trips_per_day' => 3,
                'price' => 275000000, 'revenue' => 28000000, 'opex' => 13500000,
                'tags' => ['Multi-drop', 'Paket bernilai', 'Rute antar kecamatan'],
                'desc' => 'Van tertutup yang aman untuk paket bernilai tinggi dan rute multi-drop antar kecamatan. Seimbang antara kapasitas, kelincahan di dalam kota, dan biaya operasional.',
            ],
            [
                'key' => 'cde', 'name' => 'Light Truck CDE Box', 'type' => 'Truk ringan',
                'capacity' => '±2,5 ton', 'volume' => '±12 m³', 'volume_m3' => 12,
                'parcels_per_day' => 900, 'trips_per_day' => 2,
                'price' => 360000000, 'revenue' => 40000000, 'opex' => 20000000,
                'tags' => ['Hub ke agen', 'Antar kota dekat', 'Volume menengah'],
                'desc' => 'Truk box ringan untuk distribusi dari hub ke banyak agen dan drop point, atau antar kota yang berdekatan. Cocok untuk rute tetap dengan volume paket menengah.',
            ],
            [
                'key' => 'cdd', 'name' => 'Medium Truck CDD Box', 'type' => 'Truk sedang',
                'capacity' => '±4 ton', 'volume' => '±20 m³', 'volume_m3' => 20,
                'parcels_per_day' => 1500, 'trips_per_day' => 2,
                'price' => 520000000, 'revenue' => 60000000, 'opex' => 31000000,
                'tags' => ['Line-haul', 'Hub ke hub', 'Volume tinggi'],
                'desc' => 'Truk box sedang untuk line-haul antar hub dengan volume paket tinggi. Investasi terbesar, paling efisien per paket pada rute tetap yang terisi penuh.',
            ],
        ];
    }

    /**
     * Model usaha armada (sisi pendapatan).
     * - revenue_mult     : pengali tarif terhadap pendapatan acuan
     * - util_sensitivity : porsi pendapatan yang ikut berubah mengikuti utilisasi
     */
    private function getBusinessModels(): array
    {
        return [
            [
                'key' => 'contract', 'label' => 'Kontrak Rute / Dedicated Hub', 'short' => 'Kontrak Rute',
                'badge' => 'Pendapatan stabil',
                'desc' => 'Armada ditugaskan ke rute atau hub tertentu dengan tarif harian atau bulanan. Sebagian besar pendapatan tetap meski volume paket naik turun.',
                'revenue_mult' => 0.94, 'util_sensitivity' => 0.35,
            ],
            [
                'key' => 'per_parcel', 'label' => 'Performa Per Paket / Volume Parcel', 'short' => 'Per Paket',
                'badge' => 'Margin tinggi saat ramai',
                'desc' => 'Pendapatan dihitung per paket terkirim. Naik penuh saat volume tinggi, tetapi juga turun penuh saat volume sepi.',
                'revenue_mult' => 1.10, 'util_sensitivity' => 1.0,
            ],
        ];
    }

    /**
     * Skema pengelolaan armada (sisi biaya).
     * - fee_pct          : fee manajemen sebagai persen dari pendapatan kotor
     * - opex_mult        : pengali biaya operasional terhadap biaya acuan
     * - fixed_cost_share : porsi biaya tetap dari biaya operasional
     */
    private function getManagements(array $baseline): array
    {
        return [
            [
                'key' => 'turnkey', 'label' => 'Mitra Kelola Penuh (Turnkey)', 'short' => 'Turnkey',
                'desc' => 'Amba Parcel mengelola kurir dan driver, rute, sortasi, servis, dan pelaporan. Mitra cukup memiliki unit.',
                'fee_pct' => 0.10, 'opex_mult' => 1.00,
                'fixed_cost_share' => $baseline['fixed_cost_share'],
            ],
            [
                'key' => 'semi', 'label' => 'Semi-Mandiri (Driver Mitra)', 'short' => 'Semi-Mandiri',
                'desc' => 'Mitra menyediakan dan membayar driver sendiri. Fee manajemen lebih rendah dan biaya lebih variabel mengikuti ritase, tetapi tanpa efisiensi pembelian kolektif.',
                'fee_pct' => 0.05, 'opex_mult' => 1.05,
                'fixed_cost_share' => 0.52,
            ],
        ];
    }

    /**
     * Skema kepemilikan dan pendanaan.
     * Cicilan memakai bunga flat: cicilan = pokok x (1 + bunga x tenor/12) / tenor.
     */
    private function getFundings(): array
    {
        return [
            [
                'key' => 'cash', 'label' => 'Pembelian Tunai', 'short' => 'Tunai',
                'desc' => 'Unit dibayar lunas di awal. Tidak ada bunga dan cicilan.',
                'dp_pct' => 1.0, 'tenor_months' => 0, 'flat_rate' => 0.0,
            ],
            [
                'key' => 'credit', 'label' => 'Cicilan / DP', 'short' => 'Cicilan',
                'desc' => 'DP 25%, sisanya dicicil 36 bulan dengan bunga flat 8% per tahun (asumsi simulasi).',
                'dp_pct' => 0.25, 'tenor_months' => 36, 'flat_rate' => 0.08,
            ],
        ];
    }

    /** Pilihan hari operasi per bulan. */
    private function getOperatingDays(): array
    {
        return [
            ['days' => 22, 'label' => '22 hari', 'note' => 'Senin sampai Jumat'],
            ['days' => 26, 'label' => '26 hari', 'note' => 'Senin sampai Sabtu'],
        ];
    }

    /** Pilihan jangka waktu kontrak. */
    private function getContracts(): array
    {
        return [
            ['years' => 1, 'label' => '1 Tahun'],
            ['years' => 2, 'label' => '2 Tahun'],
            ['years' => 3, 'label' => '3 Tahun'],
        ];
    }

    /**
     * Net profit per unit per bulan pada utilisasi dan hari operasi acuan.
     * Rumus ini identik dengan calc() di view saat utilisasi dan hari = acuan.
     */
    private function monthlyNet(array $fleet, array $model, array $mgmt): float
    {
        $revenue = $fleet['revenue'] * $model['revenue_mult'];
        $opex    = $fleet['opex'] * $mgmt['opex_mult'];
        $fee     = $revenue * $mgmt['fee_pct'];

        return $revenue - $opex - $fee;
    }

    /**
     * Rentang estimasi balik modal (bulan) pembelian tunai pada utilisasi acuan,
     * di seluruh kombinasi armada, model usaha, dan skema pengelolaan.
     */
    private function getBepRange(array $fleets, array $models, array $managements): array
    {
        $bep = [];
        foreach ($fleets as $fleet) {
            foreach ($models as $model) {
                foreach ($managements as $mgmt) {
                    $net = $this->monthlyNet($fleet, $model, $mgmt);
                    if ($net > 0) {
                        $bep[] = $fleet['price'] / $net;
                    }
                }
            }
        }

        return [(int) floor(min($bep)), (int) ceil(max($bep))];
    }

    /** Daftar FAQ. Field "tags" dipakai sebagai kata kunci tambahan pencarian. */
    private function getFaqs(array $fleets, array $models, array $managements, array $fundings, array $baseline): array
    {
        [$bepMin, $bepMax] = $this->getBepRange($fleets, $models, $managements);

        $byKey = static function (array $list, string $key): array {
            foreach ($list as $item) {
                if ($item['key'] === $key) {
                    return $item;
                }
            }
            return $list[0];
        };

        $contract = $byKey($models, 'contract');
        $perParcel = $byKey($models, 'per_parcel');
        $turnkey  = $byKey($managements, 'turnkey');
        $semi     = $byKey($managements, 'semi');
        $credit   = $byKey($fundings, 'credit');

        $contractSens = (int) round($contract['util_sensitivity'] * 100);
        $perParcelSens = (int) round($perParcel['util_sensitivity'] * 100);
        $feeTurnkey   = (int) round($turnkey['fee_pct'] * 100);
        $feeSemi      = (int) round($semi['fee_pct'] * 100);
        $fixedTurnkey = (int) round($turnkey['fixed_cost_share'] * 100);
        $fixedSemi    = (int) round($semi['fixed_cost_share'] * 100);
        $dpPercent    = (int) round($credit['dp_pct'] * 100);
        $ratePercent  = rtrim(rtrim(number_format($credit['flat_rate'] * 100, 1, ',', ''), '0'), ',');
        $tenor        = $credit['tenor_months'];
        $baseUtil     = $baseline['base_utilization'];
        $baseDays     = $baseline['base_days'];

        return [
            [
                'id'   => 1,
                'q'    => 'Bagaimana sistem pembagian hasil dan SLA pengiriman paket?',
                'a'    => "Pendapatan kotor dikurangi biaya operasional dan fee manajemen menjadi keuntungan bersih mitra. Fee manajemen sebesar {$feeTurnkey}% dari pendapatan pada skema Turnkey dan {$feeSemi}% pada skema Semi-Mandiri. Laporan keuangan dikirim setiap bulan. SLA mencakup ketepatan waktu antar minimal 95%, keberhasilan antar percobaan pertama minimal 90%, ketersediaan unit minimal 90%, dan respon insiden maksimal 2 jam. Angka SLA ini adalah asumsi simulasi.",
                'tags' => 'bagi hasil sla pendapatan keuntungan fee target ketepatan waktu',
            ],
            [
                'id'   => 2,
                'q'    => 'Apa beda Kontrak Rute / Dedicated Hub dengan Performa Per Paket?',
                'a'    => "Pada Kontrak Rute, armada ditugaskan ke rute atau hub tertentu dengan tarif harian atau bulanan, sehingga hanya {$contractSens}% pendapatan yang ikut berubah mengikuti utilisasi. Pada Performa Per Paket, pendapatan dihitung per paket terkirim ({$perParcelSens}% mengikuti utilisasi) dengan tarif lebih tinggi. Per Paket cenderung lebih menguntungkan saat volume ramai, sedangkan Kontrak Rute lebih aman saat volume sepi. Bagian Bandingkan skema pada simulator menunjukkan selisihnya secara langsung.",
                'tags' => 'model usaha kontrak rute dedicated hub per paket volume tarif',
            ],
            [
                'id'   => 3,
                'q'    => 'Apa beda skema Turnkey dan Semi-Mandiri (Driver Mitra)?',
                'a'    => "Pada Turnkey, Amba Parcel mengelola kurir dan driver, rute, sortasi, servis, dan pelaporan dengan fee {$feeTurnkey}% dari pendapatan. Pada Semi-Mandiri, mitra menyediakan dan membayar driver sendiri sehingga fee turun menjadi {$feeSemi}%, tetapi biaya operasional diasumsikan sedikit lebih tinggi karena tidak ada efisiensi pembelian kolektif. Porsi biaya tetap pada Turnkey {$fixedTurnkey}% dan pada Semi-Mandiri {$fixedSemi}% karena gaji driver lebih banyak dibayar per ritase.",
                'tags' => 'turnkey semi mandiri driver mitra kurir fee manajemen kelola',
            ],
            [
                'id'   => 4,
                'q'    => 'Berapa lama estimasi balik modal (BEP)?',
                'a'    => "Pada utilisasi acuan {$baseUtil}% dan {$baseDays} hari operasi per bulan, estimasi balik modal pembelian tunai berada sekitar {$bepMin}–{$bepMax} bulan, tergantung jenis armada, model usaha, dan skema pengelolaan. Simulator menghitungnya secara langsung dan akan berubah jika Anda mengubah utilisasi, hari operasi, atau pendanaan.",
                'tags' => 'balik modal bep roi investasi payback',
            ],
            [
                'id'   => 5,
                'q'    => 'Bagaimana jika volume paket atau utilisasi menurun?',
                'a'    => "Pendapatan turun sesuai model usaha ({$contractSens}% ikut utilisasi pada Kontrak Rute, {$perParcelSens}% pada Per Paket), sedangkan sebagian biaya bersifat tetap ({$fixedTurnkey}% pada Turnkey, {$fixedSemi}% pada Semi-Mandiri). Karena itu keuntungan bersih turun lebih tajam dari pendapatan. Geser slider utilisasi untuk melihat skenario konservatif, misalnya saat low season setelah periode promo besar.",
                'tags' => 'utilisasi volume risiko skenario turun sepi low season',
            ],
            [
                'id'   => 6,
                'q'    => 'Bagaimana skema cicilan atau DP dihitung?',
                'a'    => "Asumsi simulasi: DP {$dpPercent}% dari harga unit, sisanya dicicil {$tenor} bulan dengan bunga flat {$ratePercent}% per tahun. Cicilan bulanan dikurangkan dari net profit untuk menghasilkan arus kas bulanan. Estimasi BEP dan ROI kontrak memakai total biaya kepemilikan, yaitu harga unit ditambah total bunga. Suku bunga dan DP sebenarnya mengikuti penawaran lembaga pembiayaan.",
                'tags' => 'cicilan dp kredit pembiayaan bunga tenor tunai arus kas leasing',
            ],
            [
                'id'   => 7,
                'q'    => 'Apa saja yang ditangani Amba Parcel untuk operasional parcel?',
                'a'    => 'Penentuan zona dan rute, penjadwalan servis berkala, pemasangan perangkat pelacak, aplikasi kurir untuk bukti serah terima (POD), serta pengelolaan sortasi dan bongkar muat di hub. Pada skema Turnkey, rekrutmen dan pembinaan kurir atau driver juga dikelola oleh Amba Parcel, sehingga mitra tidak perlu mengelola operasional harian sendiri.',
                'tags' => 'sortasi bongkar muat hub rute zona kurir pod perawatan servis operasional',
            ],
            [
                'id'   => 8,
                'q'    => 'Bagaimana mekanisme pelaporan progres unit harian?',
                'a'    => 'Setiap unit melaporkan status (jalan, bongkar muat, standby, servis), jumlah paket terkirim, paket gagal antar, retur, dan jarak tempuh. Ringkasan harian dikirim ke mitra melalui dashboard dan notifikasi, lalu direkap menjadi laporan mingguan dan bulanan.',
                'tags' => 'laporan harian progres monitoring dashboard paket gagal retur',
            ],
            [
                'id'   => 9,
                'q'    => 'Apa saja risiko utama dan bagaimana mitigasinya?',
                'a'    => 'Risiko utama: lonjakan atau penurunan volume musiman, paket retur dan COD bermasalah, kenaikan biaya BBM, kerusakan unit, serta keterlambatan antar. Mitigasi: kontrak rute dengan tarif minimum, perawatan preventif, klausul penyesuaian biaya BBM, penjadwalan kurir cadangan saat musim puncak, dan monitoring harian untuk deteksi dini.',
                'tags' => 'risiko mitigasi bbm kerusakan retur cod musiman peak manajemen risiko',
            ],
            [
                'id'   => 10,
                'q'    => 'Siapa yang menanggung asuransi, pajak, pengemudi, dan klaim paket rusak?',
                'a'    => 'Dalam asumsi simulasi, asuransi unit, pajak kendaraan, gaji kurir atau driver, dan BBM sudah dimasukkan ke komponen biaya operasional. Klaim paket hilang atau rusak mengikuti ketentuan SLA dan asuransi barang yang ditetapkan dalam perjanjian kemitraan. Rincian pastinya ditetapkan di perjanjian.',
                'tags' => 'asuransi pajak pengemudi supir kurir bbm klaim paket rusak hilang biaya',
            ],
            [
                'id'   => 11,
                'q'    => 'Bagaimana alur dari pendaftaran sampai unit beroperasi?',
                'a'    => 'Konsultasi dan simulasi, studi kelayakan zona atau rute, kontrak, registrasi dan pemasangan perangkat pelacak, penempatan kurir atau driver, lalu unit mulai beroperasi. Estimasi total proses 2–4 minggu.',
                'tags' => 'daftar onboarding alur proses mulai studi kelayakan',
            ],
            [
                'id'   => 12,
                'q'    => 'Apakah kontrak bisa diperpanjang atau unit dijual kembali?',
                'a'    => 'Kontrak dapat diperpanjang setelah evaluasi kinerja. Nilai sisa unit di akhir kontrak tidak dihitung pada simulator ini sehingga hasil ROI bersifat konservatif.',
                'tags' => 'kontrak perpanjang jual kembali nilai sisa',
            ],
        ];
    }
}
