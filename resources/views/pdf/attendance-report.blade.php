<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran Karyawan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 15px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px double #333;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 9px;
            color: #666;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .report-meta {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-bottom: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            padding: 6px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
        }
        .data-table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        .center-cell {
            text-align: center;
        }
        .right-cell {
            text-align: right;
        }
        .footer-table {
            width: 100%;
            margin-top: 30px;
            text-align: center;
        }
        .footer-table td {
            width: 50%;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 150px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-title">Sumber PVC Outsole Tali Jepit</div>
        <div class="company-subtitle">Jl. Raya Industri Dampyak No. 12, Tegal, Jawa Tengah. Telp: (0283) 351421</div>
    </div>

    <div class="report-title">Laporan Rekapitulasi Kehadiran Karyawan</div>
    <div class="report-meta">Bulan Periode: {{ $bulan }}</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 30%;">Nama Karyawan</th>
                <th style="width: 25%;">Jabatan</th>
                <th style="width: 10%; text-align: center;">Hadir Normal</th>
                <th style="width: 10%; text-align: center;">Jam Lebih</th>
                <th style="width: 10%; text-align: center;">Lembur</th>
                <th style="width: 10%; text-align: center;">Setengah Hari</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap as $index => $r)
                <tr>
                    <td class="center-cell">{{ $index + 1 }}</td>
                    <td>{{ $r['nama'] }}</td>
                    <td>{{ $r['jabatan'] }}</td>
                    <td class="center-cell">{{ $r['penuh'] }}</td>
                    <td class="center-cell">{{ $r['jam_lebih'] }}</td>
                    <td class="center-cell">{{ $r['lembur'] }}</td>
                    <td class="center-cell">{{ $r['setengah_hari'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td>
                Mengetahui,<br>
                Pimpinan Sumber PVC Outsole Tali Jepit
                <div class="signature-line"></div>
                <br>Drs. Haryanto, M.M.
            </td>
            <td>
                Tegal, {{ $tanggal_cetak }}<br>
                Dibuat Oleh (HRD Staff),
                <div class="signature-line"></div>
                <br>{{ $dibuat_oleh ?? 'Staff HRD' }}
            </td>
        </tr>
    </table>

</body>
</html>
