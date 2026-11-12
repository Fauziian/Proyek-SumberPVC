<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Laporan Gudang</title><style>
body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:10px;margin:20px}h1{text-align:center;font-size:18px;margin:0 0 5px}.period{text-align:center;color:#475569;margin-bottom:18px}.summary{width:100%;margin-bottom:16px;border-spacing:8px}.summary td{padding:10px;background:#eff6ff;border:1px solid #bfdbfe;text-align:center}.summary strong{display:block;font-size:16px;margin-top:4px}table.data{width:100%;border-collapse:collapse}table.data th{background:#2563eb;color:#fff;padding:8px;text-align:left}table.data td{padding:7px;border-bottom:1px solid #e2e8f0}.number{text-align:right}.total td{font-weight:bold;background:#e2e8f0;border-top:1px solid #94a3b8}.footer{margin-top:18px;color:#64748b;font-size:8px;text-align:right}
</style></head><body>
<h1>LAPORAN MUTASI BARANG JADI - SUMBER PVC</h1>
<div class="period">{{ $period['label'] }}: {{ $period['start'] }} - {{ $period['end'] }}</div>
<table class="summary"><tr><td>Barang Masuk<strong>{{ $totals['masuk'] }} kodi</strong></td><td>Barang Keluar<strong>{{ $totals['keluar'] }} kodi</strong></td><td>Selisih<strong>{{ $totals['selisih'] }} kodi</strong></td></tr></table>
<table class="data"><thead><tr><th>No</th><th>Kategori</th><th>Jenis</th><th>Warna</th><th class="number">Masuk</th><th class="number">Keluar</th><th class="number">Selisih</th></tr></thead><tbody>
@forelse($rows as $index => $row)<tr><td>{{ $index + 1 }}</td><td>{{ $row['kategori'] }}</td><td>{{ $row['jenis'] }}</td><td>{{ $row['warna'] }}</td><td class="number">{{ $row['masuk'] }} kodi</td><td class="number">{{ $row['keluar'] }} kodi</td><td class="number">{{ $row['selisih'] }} kodi</td></tr>@empty<tr><td colspan="7" style="text-align:center;padding:20px">Belum ada transaksi pada periode ini.</td></tr>@endforelse
<tr class="total"><td colspan="4" class="number">TOTAL</td><td class="number">{{ $totals['masuk'] }} kodi</td><td class="number">{{ $totals['keluar'] }} kodi</td><td class="number">{{ $totals['selisih'] }} kodi</td></tr>
</tbody></table><div class="footer">Dicetak {{ now()->format('d-m-Y H:i') }}</div>
</body></html>
