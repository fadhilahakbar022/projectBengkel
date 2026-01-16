<?php
session_start();
include 'db/koneksi.php';
date_default_timezone_set('Asia/Jakarta');
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) die("Akses ditolak.");
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID tidak valid.");

$id_pesanan = (int)$_GET['id'];
$id_user = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];

// Ambil Data Pesanan
$sql_pesanan = "SELECT p.*, u.username FROM pesanan p JOIN users u ON p.id_user = u.id_user WHERE p.id_pesanan = ? AND p.id_user = ?";
$stmt = $conn->prepare($sql_pesanan);
$stmt->bind_param("ii", $id_pesanan, $id_user);
$stmt->execute();
$result_pesanan = $stmt->get_result();
if ($result_pesanan->num_rows == 0) die("Invoice tidak ditemukan.");
$pesanan = $result_pesanan->fetch_assoc();

// Ambil Detail Barang
$sql_detail = "SELECT dp.*, p.nama AS nama_produk FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pesanan = ?";
$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->bind_param("i", $id_pesanan);
$stmt_detail->execute();
$items_pesanan = $stmt_detail->get_result();

// Data Ongkir
$ongkir = $pesanan['ongkos_kirim'] ?? 0;
$kurir = !empty($pesanan['jasa_pengiriman']) ? $pesanan['jasa_pengiriman'] : 'Reguler';

$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Invoice #' . $id_pesanan . '</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
        .header table { width: 100%; } .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
        .company-info { text-align: right; font-size: 12px; color: #666; }
        .details-box { margin-bottom: 30px; } .details-box table { width: 100%; }
        .content { background: #f8fafc; padding: 15px; border-radius: 5px; font-size: 13px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #2563eb; color: #fff; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; } .text-center { text-align: center; }
        .totals-table { width: 45%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 8px; text-align: right; }
        .grand-total { font-size: 16px; color: #2563eb; border-top: 2px solid #eee; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table><tr><td class="logo">IDA JAYA OIL</td><td class="company-info">Jl. Sentosa Raya No. 105, Depok<br>Telp: +62 812-3456-7890</td></tr></table>
    </div>
    <div class="details-box">
        <table>
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <strong style="color: #999; font-size: 12px;">DITAGIHKAN KEPADA:</strong>
                    <div class="content">' . nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])) . '</div>
                </td>
                <td style="width: 45%; text-align: right; vertical-align: top;">
                    <h2 style="margin: 0;">INVOICE #' . $pesanan['id_pesanan'] . '</h2>
                    <p>Tanggal: ' . date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])) . '<br>
                    Metode: ' . htmlspecialchars($pesanan['metode_pembayaran']) . '<br>
                    Kurir: ' . htmlspecialchars($kurir) . '</p>
                </td>
            </tr>
        </table>
    </div>
    <table class="items-table">
        <thead><tr><th>NO</th><th>PRODUK</th><th class="text-center">QTY</th><th class="text-right">HARGA</th><th class="text-right">TOTAL</th></tr></thead>
        <tbody>';

$no = 1; $subtotal_barang = 0;
while($item = $items_pesanan->fetch_assoc()) {
    $harga = $item['harga_satuan'];
    $qty = $item['kuantitas'];
    $row_total = $harga * $qty;
    $subtotal_barang += $row_total;
    $html .= '<tr><td class="text-center">' . $no++ . '</td><td>' . htmlspecialchars($item['nama_produk']) . '</td><td class="text-center">' . $qty . '</td><td class="text-right">Rp ' . number_format($harga,0,',','.') . '</td><td class="text-right">Rp ' . number_format($row_total,0,',','.') . '</td></tr>';
}

$html .= '</tbody></table>
    <table class="totals-table">
        <tr><td>Subtotal Barang</td><td>Rp ' . number_format($subtotal_barang,0,',','.') . '</td></tr>
        <tr><td>Ongkos Kirim (' . htmlspecialchars($kurir) . ')</td><td>Rp ' . number_format($ongkir,0,',','.') . '</td></tr>
        <tr><td class="grand-total">TOTAL BAYAR</td><td class="grand-total">Rp ' . number_format($pesanan['total_harga'],0,',','.') . '</td></tr>
    </table>
</body></html>';

$options = new Options(); $options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
$dompdf->stream("Invoice-" . $id_pesanan . ".pdf", ["Attachment" => true]);
?>