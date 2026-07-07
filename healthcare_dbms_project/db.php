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
            CREATE TABLE IF NOT EXISTS CLINIC_CONTACT (
              Clinic_ID INTEGER NOT NULL,
              Contact TEXT NOT NULL,
              PRIMARY KEY (Clinic_ID, Contact)
            );
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
            CREATE TABLE IF NOT EXISTS APP_USER (
              User_ID INTEGER PRIMARY KEY AUTOINCREMENT,
              Username TEXT UNIQUE NOT NULL,
              Email TEXT UNIQUE NOT NULL,
              Password TEXT NOT NULL,
              Created_At TEXT DEFAULT CURRENT_TIMESTAMP
            );
            INSERT OR IGNORE INTO APP_USER (Username, Email, Password) VALUES ('admin', 'admin@clinic.local', '\$2y\$10\$uWayFvZst/lnkplPcEMLc.4xOUFq56GmzEGq1NMTr8nxL4PDEpQ.W');
            INSERT OR IGNORE INTO EMPLOYEE (Employee_ID, Name) VALUES 
              (1, 'Dr Kumar'), (2, 'Dr Meena'), (3, 'Nurse Asha'), 
              (4, 'Nurse Rani'), (5, 'Dr Arun');
            INSERT OR IGNORE INTO DOCTOR (Doctor_ID, Employee_ID, Name, Specialty, Specialization, Contact) VALUES 
              (1, 1, 'Dr Kumar', 'Cardiology', 'Cardiology', '9876543210'),
              (2, 2, 'Dr Meena', 'General', 'General', '9876543211'),
              (3, 5, 'Dr Arun', 'Orthopedic', 'Orthopedic', '9876543212');
            INSERT OR IGNORE INTO CLINIC (Clinic_ID, Clinic_Name, Location) VALUES 
              (1, 'PHC A', 'Chennai'), (2, 'PHC B', 'Vellore'), (3, 'PHC C', 'Madurai'),
              (4, 'PHC D', 'Salem'), (5, 'PHC E', 'Trichy');
            INSERT OR IGNORE INTO CLINIC_CONTACT (Clinic_ID, Contact) VALUES 
              (1, '1111111111'), (2, '2222222222'), (3, '3333333333'), 
              (4, '4444444444'), (5, '5555555555');
            INSERT OR IGNORE INTO PATIENT (Patient_ID, Name, Gender, DOB, Age, Blood_Group, Address, City, State) VALUES 
              (1, 'Ravi', 'M', '2000-05-10', 24, 'O+', 'Street 1', 'Chennai', 'TN'),
              (2, 'Arjun', 'M', '1998-03-12', 26, 'A+', 'Street 2', 'Vellore', 'TN'),
              (3, 'Rahul', 'M', '1995-07-22', 29, 'B+', 'Street 3', 'Madurai', 'TN'),
              (4, 'Priya', 'F', '2001-11-05', 23, 'AB+', 'Street 4', 'Salem', 'TN'),
              (5, 'Kiran', 'M', '1999-09-18', 25, 'O-', 'Street 5', 'Trichy', 'TN'),
              (6, 'Amith', 'M', '1997-06-15', 27, 'B+', 'Street 6', 'Chennai', 'TN'),
              (7, 'Sneha', 'F', '2002-02-20', 22, 'A-', 'Street 7', 'Vellore', 'TN'),
              (8, 'Rohit', 'M', '1996-11-11', 28, 'O+', 'Street 8', 'Madurai', 'TN'),
              (9, 'Pooja', 'F', '1999-04-25', 25, 'AB-', 'Street 9', 'Salem', 'TN'),
              (10, 'Arjun', 'M', '2001-09-10', 23, 'B-', 'Street 10', 'Trichy', 'TN'),
              (11, 'Neha', 'F', '1998-08-18', 26, 'O-', 'Street 11', 'Chennai', 'TN'),
              (12, 'Karthik', 'M', '1995-03-05', 29, 'A+', 'Street 12', 'Vellore', 'TN'),
              (13, 'Divya', 'F', '2000-12-12', 24, 'B+', 'Street 13', 'Madurai', 'TN'),
              (14, 'Manoj', 'M', '1997-07-07', 27, 'O+', 'Street 14', 'Salem', 'TN'),
              (15, 'Isha', 'F', '2003-01-01', 21, 'A-', 'Street 15', 'Trichy', 'TN');
            INSERT OR IGNORE INTO PATIENT_PHONE (Patient_ID, Phone_Number) VALUES 
              (1, '9876543210'), (2, '9876543211'), (3, '9876543212'), (4, '9876543213'),
              (5, '9876543214'), (6, '9876500001'), (7, '9876500002'), (8, '9876500003'),
              (9, '9876500004'), (10, '9876500005'), (11, '9876500006'), (12, '9876500007'),
              (13, '9876500008'), (14, '9876500009'), (15, '9876500010');
            INSERT OR IGNORE INTO VISIT (Visit_ID, Visit_Date, Patient_ID, Doctor_ID, Clinic_ID) VALUES 
              (1, '2026-01-10', 1, 1, 1), (2, '2026-01-12', 2, 2, 2), (3, '2026-01-15', 3, 3, 3),
              (4, '2026-01-18', 4, 2, 4), (5, '2026-01-20', 5, 1, 5), (6, '2026-02-01', 1, 2, 2),
              (7, '2026-02-05', 1, 3, 3), (8, '2026-02-10', 2, 1, 1), (9, '2026-02-12', 2, 3, 2),
              (10, '2026-02-15', 3, 1, 3), (11, '2026-02-18', 4, 2, 4), (12, '2026-02-20', 5, 1, 5),
              (13, '2026-02-22', 6, 2, 1), (14, '2026-02-25', 7, 3, 2), (15, '2026-02-27', 8, 1, 3),
              (16, '2026-03-01', 9, 2, 4), (17, '2026-03-03', 10, 3, 5), (18, '2026-03-05', 11, 1, 1),
              (19, '2026-03-07', 12, 2, 2), (20, '2026-03-10', 13, 3, 3), (21, '2026-03-12', 14, 1, 4),
              (22, '2026-03-15', 15, 2, 5);
            INSERT OR IGNORE INTO VISIT_SYMPTOMS (Visit_ID, Symptoms) VALUES 
              (1, 'Fever'), (2, 'Cold'), (3, 'Fracture'), (4, 'Headache'), (5, 'Cough'),
              (6, 'Fever'), (7, 'Headache'), (8, 'Cold'), (9, 'Fracture'), (10, 'Fever'),
              (11, 'Cough'), (12, 'Cold'), (13, 'Headache'), (14, 'Fever'), (15, 'Fracture'),
              (16, 'Cold'), (17, 'Cough'), (18, 'Fever'), (19, 'Headache'), (20, 'Cold'),
              (21, 'Fracture'), (22, 'Cough');
            INSERT OR IGNORE INTO PRESCRIPTION (Prescription_ID, Dosage, Duration, Visit_ID) VALUES 
              (1, '500mg', '5 days', 1), (2, '10ml', '3 days', 2), (3, '250mg', '7 days', 3),
              (4, '500mg', '2 days', 4), (5, '500mg', '5 days', 5), (6, '500mg', '5 days', 6),
              (7, '250mg', '3 days', 7), (8, '100mg', '2 days', 8), (9, '400mg', '4 days', 9),
              (10, '500mg', '5 days', 10), (11, '200mg', '3 days', 11), (12, '300mg', '4 days', 12),
              (13, '150mg', '2 days', 13), (14, '500mg', '6 days', 14), (15, '250mg', '3 days', 15);
            INSERT OR IGNORE INTO PRESCRIPTION_MEDICINE (Prescription_ID, Medicines) VALUES 
              (1, 'Paracetamol'), (2, 'Cough Syrup'), (3, 'Painkiller'), (4, 'Tablet'),
              (5, 'Antibiotic'), (6, 'Paracetamol'), (7, 'Cough Syrup'), (8, 'Antibiotic'),
              (9, 'Painkiller'), (10, 'Tablet'), (11, 'Paracetamol'), (12, 'Antibiotic'),
              (13, 'Painkiller'), (14, 'Cough Syrup'), (15, 'Tablet');
            INSERT OR IGNORE INTO TEST (Test_ID, Test_Name, Result, Visit_ID) VALUES 
              (1, 'Blood Test', 'Normal', 1), (2, 'X-Ray', 'Clear', 3), (3, 'CT Scan', 'Normal', 4),
              (4, 'Urine Test', 'Normal', 2), (5, 'MRI', 'Normal', 5), (6, 'Blood Test', 'Normal', 6),
              (7, 'X-Ray', 'Clear', 7), (8, 'MRI', 'Normal', 8), (9, 'Urine Test', 'Normal', 9),
              (10, 'CT Scan', 'Normal', 10), (11, 'Blood Test', 'Normal', 11), (12, 'X-Ray', 'Clear', 12),
              (13, 'MRI', 'Normal', 13), (14, 'Urine Test', 'Normal', 14), (15, 'CT Scan', 'Normal', 15);
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