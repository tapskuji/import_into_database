# Importing data into Mysql

Importing data into mysql using PHP.

Import data from:
- csv file
- flat file
- json
- xls file
- xlsx file
- xml file

## How to run the scripts

The scripts can be run manually from the command line or automated by using a cron job.

`*/1 * * * * cd /path/to/file && php -q script.php >> /dev/null 2>&1`

## Setup

1. Copy or rename the .env.example file to .env and provide your own credentials.

2. Build and run containers

```bash
docker-compose up --build -d
```

3. Check if containers are running

```bash
docker container ls
```

## Import data from csv file into mysql

1. Connect to php container

```bash
docker-compose run php /bin/bash
```

2. Run script

```bash
php /apps/import_csv_file/process_csv.php
```

If there are any errors, check the logfile

```bash
less /apps/import_csv_file/errors.log
```

## Import data from txt file into mysql

1. Connect to php container

```bash
docker-compose run php /bin/bash
```

2. Run script

```bash
php /apps/import_txt_file/process_txt.php
```

If there are any errors, check the logfile

```bash
less /apps/import_txt_file/errors.log
```

## Import data from json file into mysql

1. Connect to php container

```bash
docker-compose run php /bin/bash
```

2. Run script

```bash
php /apps/import_json_file/process_json.php
```

If there are any errors, check the logfile

```bash
less /apps/import_json_file/errors.log
```

## Import data from xml file into mysql

1. Connect to php container

```bash
docker-compose run php /bin/bash
```

2. Run script

```bash
php /apps/import_xml_file/process_xml.php
```

If there are any errors, check the logfile

```bash
less /apps/import_xml_file/errors.log
```

## Mysql database

Commands to check if the database, and the database tables were created.

Connect to the mysql container. Replace **your-username** with the username of you provided in .env 

```bash
docker exec -it imports-database mysql -u **your-username** -p
```

Some sql commands that are useful.

```sql
SHOW databases;
```

```sql
USE school_db;
```

```sql
SHOW tables;
```

```sql
SELECT
    students.student_id,
    students.last_name,
    students.first_name,
    s.state,
    students.email,
    students.gradyear,
    p.program
FROM students
         LEFT JOIN states s on s.state_id = students.state_id
         LEFT JOIN programs p on p.program_id = students.program_id
ORDER BY students.student_id ASC;
```

## Tear down the containers

To tear down the containers use the command:

```bash
docker-compose down --rmi all -v
```