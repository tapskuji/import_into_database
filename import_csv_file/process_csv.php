<?php

if (PHP_SAPI != 'cli') {
    die('Run me from the command line');
}

// Preventing Cron Collisions
$running = exec("ps aux|grep ". basename(__FILE__) ."|grep -v grep|wc -l");
if($running > 1) {
    die('I am already running.');
}

error_log(date('Ymd - H:i:s -> ') . 'START', 3, __DIR__ . 'errors.log');

/* @var $db PDO instance */
require __DIR__ . '/db_conn.php';

// absolute file path
$file = __DIR__ . '/unprocessed/students.csv';

// Generator to access CSV file as an associative array one line at a time
function processCsv($file) {
    $csv = fopen($file, 'r');
    // Get first row of column headers
    $headers = fgetcsv($csv);
    while (($row = fgetcsv($csv)) !== false) {
        // Use headers as array keys
        yield array_combine($headers, $row);
    }
    fclose($csv);
}

// Initialize prepared statements to insert values into database
$stmt1 = $db->prepare('INSERT INTO states (state) VALUES (:state)');
$stmt2 = $db->prepare('INSERT INTO programs (program) VALUES (:program)');
$stmt3 = $db->prepare('INSERT INTO students (student_id, last_name, first_name, state_id, email, gradyear, program_id)
                      VALUES (:id, :last, :first, :state, :email, :year, :prog)');

// Initialize arrays and counters
$states = [];
$programs = [];
$st = 0;
$pr = 0;

// Process each line of the CSV file
foreach (processCsv($file) as $row) {
    // If state hasn't been registered, insert into database
    if (!in_array($row['state'], $states)) {
        $states[++$st] = $row['state'];
        $stmt1->execute([':state' => $row['state']]);
        // Use the counter as the state's primary key
        $state_id = $st;
    } else {
        // If state is already registered, get its index (primary key)
        $state_id = array_search($row['state'], $states);
    }
    // If program hasn't been registered, insert into database
    if (!in_array($row['program'], $programs)) {
        $programs[++$pr] = $row['program'];
        $stmt2->execute([':program' => $row['program']]);
        // Use the counter as the program's primary key
        $program_id = $pr;
    } else {
        // If program is already registered, get its index (primary key)
        $program_id = array_search($row['program'], $programs);
    }
    // Insert current row into database, using foreign keys for state & program
    $stmt3->execute([
        ':id'    => $row['id'],
        ':last'  => $row['last'],
        ':first' => $row['first'],
        ':state' => $state_id,
        ':email' => $row['email'],
        ':year'  => $row['gradyear'],
        ':prog'  => $program_id
    ]);
}

$newName = __DIR__ . '/processed/' . (new DateTime())->format('Ymd_His') . '.csv';
rename($file, $newName);

$peakMemoryUsage = " Peak memory usage: ". (memory_get_peak_usage(true) / 1024 / 1024) . " MB";
error_log(date('Ymd - H:i:s -> ') . $peakMemoryUsage . "\n", 3, __DIR__ . '/errors.log');
error_log(date('Ymd - H:i:s -> ') . 'DONE' . "\n", 3, __DIR__ . '/errors.log');
