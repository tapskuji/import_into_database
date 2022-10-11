<?php

if (PHP_SAPI != 'cli') {
    die('Run me from the command line');
}

// Preventing Cron Collisions
$running = exec("ps aux|grep ". basename(__FILE__) ."|grep -v grep|wc -l");
if($running > 1) {
    die('I am already running.');
}

$logfile = __DIR__ . '/errors.log';
error_log(date('Ymd - H:i:s -> ') . 'START', 3, $logfile);

/* @var $db PDO instance */
require __DIR__ . '/db_conn.php';

$file = __DIR__ . '/unprocessed/students.xml';

function processXml($file) {
    $reader = new XMLReader();
    $reader->open($file);
    while ($reader->read()) {
        if($reader->nodeType == XMLReader::ELEMENT && $reader->name == "student") {
            $elements = $reader->readInnerXML();
            $xml = '<student>'.$elements.'</student>';
            $record = simplexml_load_string($xml);
            $record = (array) $record;
            yield $record;
            $reader->next();
        }
    }
    $reader->close();
}

// Initialize prepared statements to insert values into database
$stmt1 = $db->prepare('INSERT INTO states (state) VALUES (:state)');
$stmt2 = $db->prepare('INSERT INTO programs (program) VALUES (:program)');
$stmt3 = $db->prepare('INSERT INTO students (student_id, last_name, first_name, state_id, email, gradyear, program_id)
                      VALUES (:id, :last, :first, :state, :email, :year, :prog)');

$stmt4 = $db->prepare('SELECT state_id FROM states WHERE state = :state');
$stmt5 = $db->prepare('SELECT program_id FROM programs WHERE program = :program');

// Initialize arrays and counters
$states = [];
$programs = [];
$st = 0;
$pr = 0;

$hasErrors = false;

// Process each line of the flat file
foreach (processXml($file) as $row) {
    try {
        $db->beginTransaction();

        // If state is already cached locally, get its index (primary key)
        $state_id = array_search($row['state'], $states);
        if ($state_id === false) {
            // search for state in database
            $stmt4->execute([':state' => $row['state']]);
            $result = $stmt4->fetch();
            if ($result !== false) {
                $state_id = $result['state_id'];
            } else {
                // If state hasn't been registered, insert into database
                $stmt1->execute([':state' => $row['state']]);
                $state_id = $db->lastInsertId();
            }
            // register state
            $states[$state_id] = $row['state'];
        }

        // If program is already cached locally, get its index (primary key)
        $program_id = array_search($row['program'], $programs);
        if ($program_id === false) {
            // search for program in database
            $stmt5->execute([':program' => $row['program']]);
            $result = $stmt5->fetch();
            if ($result !== false) {
                $program_id = $result['program_id'];
            } else {
                // If program hasn't been registered, insert into database
                $stmt2->execute([':program' => $row['program']]);
                $program_id = $db->lastInsertId();
            }
            // register state
            $programs[$program_id] = $row['program'];
        }

        /* @var $state_id int state's primary key */
        /* @var $program_id int program's primary key */
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

        // commit the transaction
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log(date('Ymd - H:i:s -> ') . $e->getMessage() . "\n", 3, $logfile);
        $hasErrors = true;
    }
}

if ($hasErrors) {
    $newName = __DIR__ . '/incomplete/' . (new DateTime())->format('Ymd_His') . '.xml';
} else {
    $newName = __DIR__ . '/processed/' . (new DateTime())->format('Ymd_His') . '.xml';
}

rename($file, $newName);

$peakMemoryUsage = " Peak memory usage: ". (memory_get_peak_usage(true) / 1024 / 1024) . " MB";
error_log(date('Ymd - H:i:s -> ') . $peakMemoryUsage . "\n", 3, $logfile);
error_log(date('Ymd - H:i:s -> ') . 'DONE' . "\n", 3, $logfile);
