<!doctype html>
<html lang="vi">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Hóa đơn phòng {{ $hoaDon['phong'] ?? '' }} - {{ $hoaDon['thang'] ?? '' }}</title>

    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url("{{ storage_path('fonts/DejaVuSans.ttf') }}") format('truetype');
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #222;
            margin: 25px 40px;
            font-size: 13px;
            line-height: 1.5;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 6px;
            margin-bottom: 20px;
        }

        .header-left {
            font-weight: bold;
        }

        .header-left small {
            display: block;
            font-weight: normal;
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
            font-size: 12px;
        }

        h2.title {
            text-align: center;
            color: #1a237e;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .section {
            margin-top: 20px;
        }

        .total {
            text-align: right;
            font-weight: bold;
            color: #1a237e;
            margin-top: 10px;
            font-size: 15px;
        }

        footer {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .signature {
            text-align: center;
            width: 45%;
        }

        .signature p {
            margin: 3px 0;
        }

        .note {
            font-style: italic;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-left">
            Dãy <b>{{ $hoaDon['day_tro'] ?? 'N/A' }}</b>
            <small>Đ/c: {{ $hoaDon['dia_chi_day_tro'] ?? 'Chưa cập nhật' }}</small>
            <small>Sđt chủ trọ: {{ $hoaDon['sdt_chu_tro'] ?? 'Chưa có' }}</small>
            <small>Chủ trọ: {{ $hoaDon['chu_tro'] ?? 'Chưa có' }}</small>
        </div>
        <div class="header-right">
            Người thuê: <b>{{ $hoaDon['khach_thue'] ?? 'N/A' }}</b><br>
            Sđt: {{ $hoaDon['sdt_khach_thue'] ?? 'N/A' }}<br>
            Ngày in: {{ now()->format('d/m/Y') }}<br>
            Hạn thanh toán: {{ $hoaDon['han_thanh_toan'] ?? '-' }}
        </div>
    </header>

    <h2 class="title">HÓA ĐƠN TIỀN PHÒNG THÁNG {{ \Carbon\Carbon::parse($hoaDon['thang'] . '-01')->format('m/Y') }}</h2>

    <p>
        <b>Phòng:</b> {{ $hoaDon['phong'] ?? 'N/A' }}<br>
        <b>Trạng thái:</b> {{ strtoupper(str_replace('_', ' ', $hoaDon['trang_thai'] ?? '')) }}
    </p>

    <div class="section">
        <h3>Dịch vụ</h3>
        <table>
            <thead>
                <tr>
                    <th>Tên dịch vụ</th>
                    <th class="text-right">Số lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hoaDon['chi_tiet_dich_vu'] as $dv)
                    <tr>
                        <td>{{ $dv['ten_dich_vu'] }}</td>
                        <td class="text-right">{{ number_format($dv['so_luong'], 2) }}</td>
                        <td class="text-right">{{ number_format($dv['don_gia']) }} đ</td>
                        <td class="text-right">{{ number_format($dv['thanh_tien']) }} đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Không có dịch vụ</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Điện / Nước</h3>
        <table>
            <thead>
                <tr>
                    <th>Loại</th>
                    <th class="text-right">Chỉ số cũ</th>
                    <th class="text-right">Chỉ số mới</th>
                    <th class="text-right">Sản lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hoaDon['chi_tiet_dien_nuoc'] as $dongho)
                    <tr>
                        <td>{{ $dongho['ten_dich_vu'] }}</td>
                        <td class="text-right">{{ number_format($dongho['chi_so_cu']) }}</td>
                        <td class="text-right">{{ number_format($dongho['chi_so_moi']) }}</td>
                        <td class="text-right">{{ number_format($dongho['san_luong']) }}</td>
                        <td class="text-right">{{ number_format($dongho['don_gia']) }} đ</td>
                        <td class="text-right">{{ number_format($dongho['thanh_tien']) }} đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Không có dữ liệu điện nước</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="total">Tổng cộng: {{ number_format($hoaDon['tong_tien']) }} đ</p>

    <footer>
        <table style="width: 100%; text-align: center; border: none; margin-top: 40px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <p><b>Người lập hóa đơn (Chủ trọ)</b></p>
                    <div style="height: 70px;"></div>
                    <p>_________________________</p>
                    <p class="note">(Ký, ghi rõ họ tên)</p>
                </td>

                <td style="width: 50%; vertical-align: top;">
                    <p><b>Khách thuê</b></p>
                    <div style="height: 70px;"></div>
                    <p>_________________________</p>
                    <p class="note">(Ký, ghi rõ họ tên)</p>
                </td>
            </tr>
        </table>
    </footer>

</body>

</html>