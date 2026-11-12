<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji Karyawan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px double #333;
            padding-bottom: 8px;
            margin-bottom: 12px;
            text-align: center;
        }
        .company-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-subtitle {
            font-size: 9px;
            color: #666;
        }
        .slip-title {
            font-size: 13px;
            font-weight: bold;
            margin: 8px 0 3px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .slip-meta {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            width: 90px;
        }
        .info-colon {
            width: 10px;
            text-align: center;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .grid-table th {
            background-color: #f5f5f5;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 5px;
            font-weight: bold;
            text-align: left;
        }
        .grid-table td {
            padding: 5px;
            border-bottom: 1px dashed #eee;
        }
        .number-cell {
            text-align: right;
        }
        .total-box {
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            padding: 8px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .total-label {
            float: left;
        }
        .total-val {
            float: right;
            color: #1a56db;
        }
        .footer-table {
            width: 100%;
            margin-top: 25px;
            text-align: center;
        }
        .footer-table td {
            width: 50%;
        }
        .signature-line {
            margin-top: 45px;
            border-top: 1px solid #333;
            width: 140px;
            display: inline-block;
        }
        .note {
            font-size: 8px;
            color: #888;
            font-style: italic;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-title">Sumber PVC Outsole Tali Jepit</div>
        <div class="company-subtitle">Jl. Raya Industri Dampyak No. 12, Tegal, Jawa Tengah. Telp: (0283) 351421</div>
    </div>

    <div class="slip-title">Slip Gaji Karyawan</div>
    <div class="slip-meta">Periode: {{ $payroll->periode }} (Bulan Terkait)</div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Karyawan</td>
            <td class="info-colon">:</td>
            <td>{{ $payroll->karyawan->nama }}</td>
            
            <td class="info-label">Jabatan</td>
            <td class="info-colon">:</td>
            <td>{{ $payroll->karyawan->jabatan }}</td>
        </tr>
        <tr>
            <td class="info-label">Tanggal Masuk</td>
            <td class="info-colon">:</td>
            <td>{{ $payroll->karyawan->tanggal_masuk->format('d M Y') }}</td>
            
            <td class="info-label">Masa Kerja</td>
            <td class="info-colon">:</td>
            <td>
                {{ ($payroll->rincian['kategori_masa_kerja'] ?? $payroll->karyawan->kategori_masa_kerja) === 'B' ? '>= 5 Tahun (Kategori B)' : '< 5 Tahun (Kategori A)' }}
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th>Komponen Pendapatan</th>
                <th class="number-cell">Jumlah</th>
                <th>Keterangan / Potongan</th>
                <th class="number-cell">Nilai</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Upah Jam Normal ({{ number_format($payroll->total_jam_normal, 2, ',', '.') }} jam)</td>
                <td class="number-cell">Rp {{ number_format($payroll->gaji_pokok, 0, ',', '.') }}</td>
                <td>Tarif per Jam</td>
                <td class="number-cell">Rp {{ number_format($payroll->tarif_per_jam, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Upah Lembur ({{ number_format($payroll->jam_lembur, 2, ',', '.') }} jam)</td>
                <td class="number-cell">Rp {{ number_format($payroll->insentif_lembur, 0, ',', '.') }}</td>
                <td>Aturan Lembur</td>
                <td class="number-cell">Mulai 14 jam</td>
            </tr>
            <tr>
                <td>Jumlah Hari Hadir</td>
                <td class="number-cell">{{ $payroll->hari_hadir }} hari</td>
                <td>Total Potongan</td>
                <td class="number-cell">Rp {{ number_format($payroll->potongan, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-box">
        <span class="total-label">GAJI BERSIH YANG DITERIMA (NETTO)</span>
        <span class="total-val">Rp {{ number_format($payroll->total_gaji, 0, ',', '.') }}</span>
    </div>

    <table class="footer-table">
        <tr>
            <td>
                Penerima,
                <div class="signature-line"></div>
                <br>{{ $payroll->karyawan->nama }}
            </td>
            <td>
                Tegal, {{ $tanggal_cetak }}<br>
                Staff Administrasi (HRD),
                <div class="signature-line"></div>
                <br>{{ $petugas ?? 'Staff HRD' }}
            </td>
        </tr>
    </table>

    <div class="note">
        * Catatan: Gaji dihitung dari absensi terverifikasi. Durasi 8–13 jam adalah kerja reguler dan pilihan 14–15 jam adalah lembur.
    </div>

</body>
</html>
