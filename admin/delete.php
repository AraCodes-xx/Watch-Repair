<?php
require_once '../config/config.php';
require_once 'config/entities.php';
require_admin();

$entity = $_GET['entity'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!entity_exists($entity) || $id <= 0) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$config = get_entity_config($entity);
$conn = getDBConnection();

// Delete the record
$stmt = $conn->prepare("DELETE FROM {$config['table']} WHERE {$config['primary_key']} = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

closeDBConnection($conn);

redirect(SITE_URL . "/admin/manage.php?entity={$entity}&success=deleted");
?>
