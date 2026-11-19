<?php require '../inc.php'; guard('customer'); tampil_struk((int)($_GET['id'] ?? 0), $_SESSION['uid']);
