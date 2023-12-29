<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$host = '127.0.0.1';
$port = 8889;
$database = "diduenjoy_db";
$username = "root";
$password = "root";

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $spreadsheet = IOFactory::load('Orders.xlsx');
    $sheet = $spreadsheet->getActiveSheet();

    $packages = [];
    $items = [];
    $orders = [];

    $currentPackage = null;
    $currentOrder = null;

    foreach ($sheet->getRowIterator(2) as $row) {
        $rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':' . $sheet->getHighestColumn() . $row->getRowIndex(), null, true, false)[0];

        $packageId = $rowData[0];
        $orderId = $rowData[1];
        $label = $rowData[2];
        $value = $rowData[3];

        if (!empty($packageId)) {
            $currentPackage = ['package_id' => $packageId, 'order_id' => $orderId];
            $packages[] = $currentPackage;
        }

        if ($label == 'name') {
            $currentOrder = ['order_id' => $orderId, 'order_name' => $value];
            $orders[] = $currentOrder;
        }

        if (!empty($currentOrder) && in_array($label, ['name', 'price', 'warranty', 'duration'])) {
            $items[] = [
                'item_id' => $packageId,
                'name' => $label,
                'value' => $value
            ];
        }
    }

    echo 'Packages:' . PHP_EOL;
    print_r($packages);
    echo 'Items:' . PHP_EOL;
    print_r($items);
    echo 'Orders:' . PHP_EOL;
    print_r($orders);

    foreach ($packages as $package) {
        $stmt = $conn->prepare("INSERT INTO packages (packageid, orderid) VALUES (:package_id, :order_id)");
        $stmt->bindParam(':package_id', $package['package_id']);
        $stmt->bindParam(':order_id', $package['order_id']);
        $stmt->execute();
    }

    foreach ($items as $item) {
        $stmt = $conn->prepare("INSERT INTO items (itemid, name, value) VALUES (:item_id, :name, :value)");
        $stmt->bindParam(':item_id', $item['item_id']);
        $stmt->bindParam(':name', $item['name']);
        $stmt->bindParam(':value', $item['value']);
        $stmt->execute();
    }

    foreach ($orders as $order) {
        $stmt = $conn->prepare("INSERT INTO orders (orderid, order_name) VALUES (:order_id, :order_name)");
        $stmt->bindParam(':order_id', $order['order_id']);
        $stmt->bindParam(':order_name', $order['order_name']);
        $stmt->execute();
    }

} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
}

$conn = null;

?>
