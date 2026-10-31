<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
require __DIR__ . '/period.php';

$datasets = [
    'consultations' => ['SELECT r.resident_code, CONCAT(r.last_name, ", ", r.first_name) AS resident, c.consult_date, c.symptoms, c.blood_pressure, c.temperature_c, c.weight_kg, c.diagnosis, c.treatment, c.follow_up_date, c.status
                        FROM consultations c JOIN residents r ON r.id = c.resident_id WHERE c.is_archived = 0 AND c.consult_date BETWEEN ? AND ? ORDER BY c.consult_date', ['Resident ID', 'Resident', 'Date', 'Symptoms', 'BP', 'Temp (C)', 'Weight (kg)', 'Diagnosis', 'Treatment', 'Follow-up', 'Status']],
    'immunizations' => ['SELECT r.resident_code, CONCAT(r.last_name, ", ", r.first_name) AS resident, i.vaccine_name, i.dose_no, i.date_administered, i.next_dose_date, i.remarks
                        FROM immunizations i JOIN residents r ON r.id = i.resident_id WHERE i.is_archived = 0 AND i.date_administered BETWEEN ? AND ? ORDER BY i.date_administered', ['Resident ID', 'Resident', 'Vaccine', 'Dose', 'Date administered', 'Next dose', 'Remarks']],
    'residents' => ['SELECT resident_code, last_name, first_name, sex, birthdate, purok, contact_no, DATE(created_at) AS registered
                        FROM residents WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at', ['Resident ID', 'Last name', 'First name', 'Sex', 'Birthdate', 'Purok', 'Contact', 'Registered']],
    'maternal_child' => ['SELECT r.resident_code, CONCAT(r.last_name, ", ", r.first_name) AS resident, m.record_type, m.visit_date, m.gestational_weeks, m.blood_pressure, m.weight_kg, m.height_cm, m.nutrition_status, m.next_visit_date
                        FROM maternal_child_records m JOIN residents r ON r.id = m.resident_id WHERE m.is_archived = 0 AND m.visit_date BETWEEN ? AND ? ORDER BY m.visit_date', ['Resident ID', 'Resident', 'Type', 'Visit date', 'Weeks', 'BP', 'Weight (kg)', 'Height (cm)', 'Nutrition', 'Next visit']],
];
$ds = get('dataset');
if (!isset($datasets[$ds])) { http_response_code(400); exit('Unknown dataset.'); }
[$sql, $headers] = $datasets[$ds];
$st = db()->prepare($sql);
$st->execute([$from, $to]);

log_activity('export', 'reports', null, "CSV export: $ds ($period $from to $to)");
$fname = sprintf('bhrs_%s_%s_%s.csv', $ds, $period, $from);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows accents correctly
fputcsv($out, $headers);
while ($row = $st->fetch(PDO::FETCH_NUM)) {
    // Neutralise spreadsheet formula injection
    $row = array_map(fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v, $row);
    fputcsv($out, $row);
}
fclose($out);
