<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$logger = new Logger('info');
$streamHandler = new StreamHandler(__DIR__ . '/errors.log', Logger::DEBUG);
$logger->pushHandler($streamHandler);

$logger->info('START');

/* @var $db PDO instance */
require __DIR__ . '/db_conn.php';

$file = __DIR__ . '/unprocessed/students.xls';

function processXls($file) {
    global $logger;
    try {
        // Create a new Reader of the type defined in $inputFileType
        $reader = IOFactory::createReader('Xls');
        // Load $inputFileName to a PhpSpreadsheet Object
        $spreadsheet = $reader->load($file);
        $totalSheets = $spreadsheet->getSheetCount();

        echo "Number of sheets : {$totalSheets}" . PHP_EOL;
        for ($sheetNumber = 0; $sheetNumber < $totalSheets; $sheetNumber++) {
            $sheet = $spreadsheet->getSheet($sheetNumber)->toArray(null, true, true, true);
            $rowCount = count($sheet);
            echo "Sheet {$sheetNumber}, {$rowCount} rows" . PHP_EOL;
            $headers = [
                trim($sheet[1]['A']),
                trim($sheet[1]['B']),
                trim($sheet[1]['C']),
                trim($sheet[1]['D']),
                trim($sheet[1]['E']),
                trim($sheet[1]['F']),
                trim($sheet[1]['G']),
                trim($sheet[1]['H']),
                trim($sheet[1]['I']),
            ];

            for ($row = 2; $row <= $rowCount; $row++) {
                $values = [
                    trim($sheet[$row]['A']),
                    trim($sheet[$row]['B']),
                    trim($sheet[$row]['C']),
                    trim($sheet[$row]['D']),
                    trim($sheet[$row]['E']),
                    trim($sheet[$row]['F']),
                    trim($sheet[$row]['G']),
                    trim($sheet[$row]['H']),
                    trim($sheet[$row]['I']),
                ];

                yield array_combine($headers, $values);
            }
        }
        //$sheetNames = $spreadsheet->getSheetNames();
    } catch (ReaderException | PhpSpreadsheetException $e) {
        $logger->error($e->getMessage());
    } finally {
        unset($spreadsheet);
        unset($reader);
    }
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
foreach (processXls($file) as $row) {
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
    } catch (\PDOException $e) {
        $db->rollBack();
        $logger->warning($e->getMessage());
        $hasErrors = true;
    }
}

if ($hasErrors) {
    $newName = __DIR__ . '/incomplete/' . (new DateTime())->format('Ymd_His') . '.xls';
} else {
    $newName = __DIR__ . '/processed/' . (new DateTime())->format('Ymd_His') . '.xls';
}

rename($file, $newName);

$peakMemoryUsage = " Peak memory usage: ". (memory_get_peak_usage(true) / 1024 / 1024) . " MB";
$logger->info($peakMemoryUsage);
$logger->info('DONE');
