<?php
// reports.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';

// Get filter dates
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build Query
$query = "SELECT PIN, FirstName, Surname, OtherNames, ConsentGiven, ConsentDate, Gender, Email, MobileNumber, NIN FROM pencare_consent";
$whereClauses = [];
$params = [];

if ($startDate) {
    $whereClauses[] = "CAST(ConsentDate AS DATE) >= :start_date";
    $params[':start_date'] = $startDate;
}
if ($endDate) {
    $whereClauses[] = "CAST(ConsentDate AS DATE) <= :end_date";
    $params[':end_date'] = $endDate;
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY ConsentDate DESC";

// Handle Export
if (isset($_GET['export'])) {
    $format = $_GET['export'];

    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=pencare_consent_report_' . date('Ymd_His') . '.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['PIN', 'FIRSTNAME', 'SURNAME', 'OTHERNAMES', 'CONSENT STATUS', 'CONSENT DATE', 'GENDER', 'EMAIL', 'PHONE NUMBER', 'NIN']);
            foreach ($rows as $row) {
                $status = ($row['ConsentGiven'] == 1) ? 'YES' : 'NO';
                fputcsv($output, [
                    $row['PIN'],
                    $row['FirstName'],
                    $row['Surname'],
                    $row['OtherNames'],
                    $status,
                    $row['ConsentDate'],
                    $row['Gender'],
                    $row['Email'],
                    $row['MobileNumber'],
                    $row['NIN']
                ]);
            }
            fclose($output);
            exit;
        }
    } catch (PDOException $e) {
        die("Export failed: " . $e->getMessage());
    }
}

// Fetch data for display
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Failed to fetch records: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consent Records Report - Pencare</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container-wide {
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
            margin: 0 auto;
        }

        .card-wide {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .report-header h1 {
            margin: 0;
        }

        /* Filter Form Styles */
        .filter-section {
            background: rgba(15, 10, 5, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            letter-spacing: 0.05em;
        }

        .filter-input {
            width: 100%;
            padding: 0.6rem 1rem;
            background: rgba(25, 10, 5, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            color: white;
            font-family: inherit;
        }

        .btn-filter {
            padding: 0.65rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            height: 38px;
        }

        .btn-filter:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-reset {
            padding: 0.65rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: 38px;
            display: flex;
            align-items: center;
        }

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .actions {
            display: flex;
            gap: 1rem;
        }

        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .btn-export:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-pdf {
            background: #ef4444;
            border-color: #ef4444;
        }

        .btn-excel {
            background: #10b981;
            border-color: #10b981;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.75rem;
            background: rgba(15, 10, 5, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }

        th {
            background: rgba(245, 57, 0, 0.15);
            color: var(--primary-color);
            padding: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(251, 146, 60, 0.1);
            color: var(--text-muted);
        }

        tr:hover td {
            background: rgba(245, 57, 0, 0.05);
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-yes {
            background: rgba(16, 185, 129, 0.2);
            color: #86efac;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-no {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }

            .container-wide {
                max-width: none;
                padding: 0;
            }

            .card-wide {
                box-shadow: none;
                border: none;
                background: white;
            }

            .actions,
            .back-nav,
            .filter-section {
                display: none !important;
            }

            th {
                background: #f3f4f6 !important;
                color: black !important;
                border-bottom: 2px solid #000;
            }

            td {
                color: black !important;
                border-bottom: 1px solid #ddd;
            }

            .status-badge {
                border: 1px solid #000;
                color: black !important;
                background: transparent !important;
            }
        }

        .back-nav {
            margin-bottom: 1rem;
        }

        .back-nav a {
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .back-nav a:hover {
            color: var(--primary-color);
        }
    </style>
</head>

<body style="display: block;"> <!-- Overriding flex center for report page -->

    <div class="container-wide">

        <div class="back-nav">

        </div>

        <div class="card-wide">
            <header class="report-header">
                <div>
                    <h1>CONSENT RECORDS</h1>
                    <p class="subtitle">Detailed report of all submitted consents &bull;
                        <strong><?= count($records) ?></strong> records found
                    </p>
                </div>
                <div class="actions">
                    <?php
                    $queryString = http_build_query(array_merge($_GET, ['export' => 'excel']));
                    ?>
                    <a href="reports.php?<?= $queryString ?>" class="btn-export btn-excel">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z" />
                            <path
                                d="M4.5 12a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z" />
                        </svg>
                        Export Excel
                    </a>
                    <a href="javascript:window.print()" class="btn-export btn-pdf">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z" />
                            <path
                                d="M5 11.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                        </svg>
                        PDF / Print
                    </a>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="filter-section">
                <form action="reports.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="start_date">From Date</label>
                        <input type="date" id="start_date" name="start_date" class="filter-input"
                            value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="end_date">To Date</label>
                        <input type="date" id="end_date" name="end_date" class="filter-input"
                            value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <button type="submit" class="btn-filter">Apply Filter</button>
                    <?php if ($startDate || $endDate): ?>
                        <a href="reports.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>PIN</th>
                            <th>First Name</th>
                            <th>Surname</th>
                            <th>Other Names</th>
                            <th>Status</th>
                            <th>Consent Date</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>NIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="no-data">No records match your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($record['PIN']) ?></strong></td>
                                    <td><?= htmlspecialchars($record['FirstName']) ?></td>
                                    <td><?= htmlspecialchars($record['Surname']) ?></td>
                                    <td><?= htmlspecialchars($record['OtherNames']) ?></td>
                                    <td>
                                        <span
                                            class="status-badge <?= ($record['ConsentGiven'] == 1) ? 'status-yes' : 'status-no' ?>">
                                            <?= ($record['ConsentGiven'] == 1) ? 'YES' : 'NO' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($record['ConsentDate'])) ?></td>
                                    <td><?= htmlspecialchars($record['Gender']) ?></td>
                                    <td><?= htmlspecialchars($record['Email']) ?></td>
                                    <td><?= htmlspecialchars($record['MobileNumber']) ?></td>
                                    <td><?= htmlspecialchars($record['NIN']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>