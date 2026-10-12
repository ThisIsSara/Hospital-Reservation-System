<?php
// Aryam: queries used by Ghaliah's doctors page.
function get_doctors(PDO $connection): array
{
    return $connection->query('SELECT id, full_name, specialty FROM doctors ORDER BY full_name')->fetchAll();
}

function get_available_slots(PDO $connection): array
{
    return $connection->query(
        'SELECT s.id, s.doctor_id, s.starts_at FROM appointment_slots s
         LEFT JOIN appointments a ON a.slot_id = s.id
         WHERE s.starts_at > NOW() AND a.id IS NULL ORDER BY s.starts_at'
    )->fetchAll();
}
