<?php
// Sarah: retrieve only the authenticated patient's reservations.
function get_patient_appointments(PDO $connection, int $patientId): array
{
    $statement = $connection->prepare(
        'SELECT a.id, a.starts_at, d.full_name, d.specialty
         FROM appointments a JOIN appointment_slots s ON s.id = a.slot_id
         JOIN doctors d ON d.id = s.doctor_id
         WHERE a.patient_id = ? ORDER BY a.starts_at'
    );
    $statement->execute([$patientId]);
    return $statement->fetchAll();
}
