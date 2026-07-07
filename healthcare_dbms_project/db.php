<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME') ?: 'testuser';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'healthcare_db';

class AppDbResult {
    private array $rows;
    private int $index = 0;
    public int $num_rows;

    public function __construct(array $rows) {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array {
        if ($this->index < count($this->rows)) {
            return $this->rows[$this->index++];
        }
        return null;
    }

    public function fetch_array(): ?array {
        return $this->fetch_assoc();
    }
}

class AppDb {
    private ?PDO $pdo;
    public string $error = '';

    public function __construct(?PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function query(string $sql) {
        if (!$this->pdo) {
            $this->error = 'No database connection';
            return false;
        }

        $trimmed = ltrim($sql);
        if (preg_match('/^SELECT\b|^WITH\b|^SHOW\b|^PRAGMA\b/i', $trimmed)) {
            try {
                $stmt = $this->pdo->query($sql);
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $this->error = '';
                return new AppDbResult($rows);
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        try {
            $affected = $this->pdo->exec($sql);
            $this->error = '';
            return $affected !== false;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function real_escape_string($value): string {
        return str_replace(["\\", "'"], ["\\\\", "''"], (string) $value);
    }

    public function set_charset($charset): bool {
        return true;
    }
}

$dbFile = __DIR__ . '/clinic.db';

if (class_exists('PDO') && class_exists('SQLite3')) {
    if (!file_exists($dbFile)) {
        $sqlite = new SQLite3($dbFile);
        $sqlite->exec("
            CREATE TABLE IF NOT EXISTS PATIENT (
              Patient_ID INTEGER PRIMARY KEY,
              Name TEXT NOT NULL,
              Gender TEXT,
              DOB TEXT,
              Age INTEGER,
              Blood_Group TEXT,
              Address TEXT,
              City TEXT,
              State TEXT
            );
            CREATE TABLE IF NOT EXISTS PATIENT_PHONE (
              Patient_ID INTEGER NOT NULL,
              Phone_Number TEXT NOT NULL,
              PRIMARY KEY (Patient_ID, Phone_Number)
            );
            CREATE TABLE IF NOT EXISTS EMPLOYEE (
              Employee_ID INTEGER PRIMARY KEY,
              Name TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS DOCTOR (
              Doctor_ID INTEGER PRIMARY KEY,
              Employee_ID INTEGER,
              Name TEXT NOT NULL,
              Specialty TEXT,
              Specialization TEXT,
              Contact TEXT
            );
            CREATE TABLE IF NOT EXISTS CLINIC (
              Clinic_ID INTEGER PRIMARY KEY,
              Clinic_Name TEXT NOT NULL,
              Location TEXT
            );
            CREATE TABLE IF NOT EXISTS VISIT (
              Visit_ID INTEGER PRIMARY KEY,
              Visit_Date TEXT NOT NULL,
              Patient_ID INTEGER NOT NULL,
              Doctor_ID INTEGER,
              Clinic_ID INTEGER
            );
            CREATE TABLE IF NOT EXISTS VISIT_SYMPTOMS (
              Visit_ID INTEGER NOT NULL,
              Symptoms TEXT NOT NULL,
              PRIMARY KEY (Visit_ID, Symptoms)
            );
            CREATE TABLE IF NOT EXISTS TEST (
              Test_ID INTEGER PRIMARY KEY,
              Test_Name TEXT NOT NULL,
              Result TEXT,
              Visit_ID INTEGER NOT NULL
            );
            CREATE TABLE IF NOT EXISTS PRESCRIPTION (
              Prescription_ID INTEGER PRIMARY KEY,
              Dosage TEXT,
              Duration TEXT,
              Visit_ID INTEGER NOT NULL
            );
            CREATE TABLE IF NOT EXISTS PRESCRIPTION_MEDICINE (
              Prescription_ID INTEGER NOT NULL,
              Medicines TEXT NOT NULL,
              PRIMARY KEY (Prescription_ID, Medicines)
            );
            CREATE TABLE IF NOT EXISTS ASSISTS (
              Nurse_ID INTEGER NOT NULL,
              Visit_ID INTEGER NOT NULL,
              PRIMARY KEY (Nurse_ID, Visit_ID)
            );
            INSERT OR IGNORE INTO EMPLOYEE (Employee_ID, Name) VALUES (1, 'Asha Rao');
            INSERT OR IGNORE INTO DOCTOR (Doctor_ID, Employee_ID, Name, Specialty, Specialization, Contact) VALUES (1, 1, 'Dr. Asha Rao', 'General Medicine', 'General Medicine', '9876543210');
            INSERT OR IGNORE INTO CLINIC (Clinic_ID, Clinic_Name, Location) VALUES (1, 'Rural Care Center', 'Mysuru');
            INSERT OR IGNORE INTO PATIENT (Patient_ID, Name, Gender, DOB, Age, Blood_Group, Address, City, State) VALUES (1, 'Sundar Kumar', 'Male', '1988-05-12', 38, 'O+', '1st Cross, Village Road', 'Mysuru', 'Karnataka');
        ");
        $sqlite->close();
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');
    $conn = new AppDb($pdo);
} else {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new AppDb(null);
        $conn->pdo = null;
    } catch (Throwable $e) {
        http_response_code(500);
        die("Database connection failed. Error: " . $e->getMessage());
    }
}
?>