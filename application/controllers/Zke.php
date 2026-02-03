<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Manually require autoloader if it exists, otherwise define dummy classes or handle error
if (file_exists(FCPATH . 'vendor/autoload.php')) {
    require_once FCPATH . 'vendor/autoload.php';
}

use Jmrashed\Zkteco\Lib\ZKTeco;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Zke extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // 1. Check Login
        $session = $this->session->userdata('username');
        if (empty($session)) {
            redirect('login');
        }

        // 2. Check Admin Role (ID 1)
        // Accessing model already loaded in MY_Controller or load it
        $this->load->model('Xin_model');
        $user_info = $this->Xin_model->read_user_info($session['user_id']);

        if ($user_info[0]->user_role_id != 1) {
            redirect('dashboard');
        }
    }

    public function index()
    {
        $data['title'] = 'Attendance System';
        $data['breadcrumbs'] = 'Attendance System';
        $data['path_url'] = 'attendance_zke';

        $session = $this->session->userdata('username');
        $role_resources_ids = $this->Xin_model->read_user_role_info($session['user_id']);

        $data['subview'] = $this->load->view('attendance/zke_dashboard', $data, TRUE);
        $this->load->view('layout_main', $data);
    }

    public function connect()
    {
        // Increase memory and time for large logs
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        header('Content-Type: application/json');

        $ip = $this->input->get('ip');
        $port = 4370; // Default port

        if (empty($ip)) {
            echo json_encode(['success' => false, 'message' => 'IP Address is required.']);
            return;
        }

        // Check if library exists
        if (!class_exists('Jmrashed\Zkteco\Lib\ZKTeco')) {
            echo json_encode([
                'success' => false,
                'message' => 'System Error: ZKTeco Library not found. Please run "composer install" in the terminal.'
            ]);
            return;
        }

        $zk = new ZKTeco($ip, $port);

        if ($zk->connect()) {
            try {
                // 1. Fetch Users
                $users = $zk->getUser(); // Library method might differ, checking generic usage
                // Note: The original code used Jmrashed\Zkteco\Lib\Helper\User::get($zk)
                // We'll use the direct library object if possible or the helper if installed.
                // Assuming standard ZKTeco lib usage:

                // 2. Fetch Attendance
                $attendance_logs = $zk->getAttendance();

                $zk->disconnect();

                // 3. Save to Cache in uploads directory
                $cacheDir = FCPATH . 'uploads/attendance/';
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0777, true);
                }

                file_put_contents($cacheDir . 'users.json', json_encode($users));
                file_put_contents($cacheDir . 'attendance.json', json_encode($attendance_logs));

                $userCount = count($users);
                $logCount = count($attendance_logs);

                echo json_encode([
                    'success' => true,
                    'message' => "Synced successfully! (Users: $userCount, Logs: $logCount)"
                ]);

            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Connection failed. Please check IP and network.']);
        }
    }

    public function get_users()
    {
        header('Content-Type: application/json');

        $cacheFile = FCPATH . 'uploads/attendance/users.json';

        if (file_exists($cacheFile)) {
            $users = json_decode(file_get_contents($cacheFile), true);

            $userList = [];
            foreach ($users as $user) {
                // Adjust keys based on ZKTeco lib output
                $uid = $user['uid'] ?? '';
                $userid = $user['userid'] ?? '';
                $name = $user['name'] ?? 'Unknown';

                $userList[] = [
                    'uid' => $uid,
                    'userid' => $userid,
                    'name' => $name
                ];
            }

            // Sort by name
            usort($userList, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            echo json_encode(['success' => true, 'users' => $userList]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No cached data found. Please connect and sync first.']);
        }
    }

    public function export()
    {
        // Increase memory and time for large logs
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $filter_user_id = $this->input->get('user_id');

        if (!$start_date || !$end_date) {
            die("Please select both Start Date and End Date.");
        }

        $cacheDir = FCPATH . 'uploads/attendance/';
        $usersFile = $cacheDir . 'users.json';
        $attendanceFile = $cacheDir . 'attendance.json';

        if (!file_exists($usersFile) || !file_exists($attendanceFile)) {
            die("<h1>No Data Found</h1><p>Please go back and click 'Connect & Sync' first.</p>");
        }

        $users = json_decode(file_get_contents($usersFile), true);
        $attendance_logs = json_decode(file_get_contents($attendanceFile), true);

        if (empty($attendance_logs)) {
            die("<h1>No Data</h1><p>No attendance records found in cache.</p>");
        }

        // Process Data (Similar to original export.php)
        $groupedData = [];
        $sample = reset($attendance_logs);
        // ZKTeco lib usually checks specific keys, check logic
        $timeKey = isset($sample['timestamp']) ? 'timestamp' : (isset($sample['time']) ? 'time' : 'datetime');

        foreach ($attendance_logs as $log) {
            $timestamp = $log[$timeKey];
            $date = date('Y-m-d', strtotime($timestamp));
            $uid = $log['id']; // This is the User ID in ZK

            if ($date < $start_date || $date > $end_date)
                continue;
            if (!empty($filter_user_id) && $uid != $filter_user_id)
                continue;

            if (!isset($groupedData[$uid]))
                $groupedData[$uid] = [];
            if (!isset($groupedData[$uid][$date])) {
                $groupedData[$uid][$date] = [
                    'punches' => [],
                    'check_in' => null,
                    'check_out' => null
                ];
            }
            $groupedData[$uid][$date]['punches'][] = $timestamp;
        }

        // Spreadsheet Generation
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['User ID', 'Name', 'Date', 'Day', 'Check-In', 'Check-Out', 'Work Hours', 'All Punches', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');

        // Style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        $row = 2;
        ksort($groupedData);

        foreach ($groupedData as $uid => $dates) {
            $userName = 'Unknown';
            // Find user name
            foreach ($users as $u) {
                if (($u['userid'] ?? '') == $uid || ($u['uid'] ?? '') == $uid || ($u['id'] ?? '') == $uid) {
                    $userName = $u['name'];
                    break;
                }
            }

            ksort($dates);

            foreach ($dates as $date => $data) {
                $punches = $data['punches'];
                sort($punches);

                $checkIn = $punches[0];
                $checkOut = end($punches);
                if (count($punches) == 1)
                    $checkOut = null;

                $workHours = '';
                if ($checkIn && $checkOut) {
                    $t1 = strtotime($checkIn);
                    $t2 = strtotime($checkOut);
                    $diff = $t2 - $t1;
                    $workHours = gmdate('H:i', $diff);
                }

                $fmtCheckIn = $checkIn ? date('h:i A', strtotime($checkIn)) : '';
                $fmtCheckOut = $checkOut ? date('h:i A', strtotime($checkOut)) : '';
                $allPunchesStr = implode(', ', array_map(function ($t) {
                    return date('h:i A', strtotime($t));
                }, $punches));
                $status = ($checkIn && $checkOut) ? 'Present' : 'Missing Out Punch';
                $dayName = date('l', strtotime($date));

                $sheet->setCellValue('A' . $row, $uid);
                $sheet->setCellValue('B' . $row, $userName);
                $sheet->setCellValue('C' . $row, $date);
                $sheet->setCellValue('D' . $row, $dayName);
                $sheet->setCellValue('E' . $row, $fmtCheckIn);
                $sheet->setCellValue('F' . $row, $fmtCheckOut);
                $sheet->setCellValue('G' . $row, $workHours);
                $sheet->setCellValue('H' . $row, $allPunchesStr);
                $sheet->setCellValue('I' . $row, $status);

                $row++;
            }
        }

        foreach (range('A', 'I') as $col)
            $sheet->getColumnDimension($col)->setAutoSize(true);

        $filename = "Attendance_Report_" . $start_date . "_to_" . $end_date . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
    public function get_live_stats()
    {
        header('Content-Type: application/json');

        $cacheDir = FCPATH . 'uploads/attendance/';
        $usersFile = $cacheDir . 'users.json';
        $attendanceFile = $cacheDir . 'attendance.json';

        if (!file_exists($usersFile) || !file_exists($attendanceFile)) {
            echo json_encode(['success' => false, 'message' => 'No data found. Sync first.']);
            return;
        }

        $users = json_decode(file_get_contents($usersFile), true);
        $attendance_logs = json_decode(file_get_contents($attendanceFile), true);

        // Map User IDs to Names
        $userMap = [];
        foreach ($users as $u) {
            $uid = $u['userid'] ?? $u['uid'] ?? $u['id'];
            $userMap[$uid] = $u['name'];
        }

        $today = date('Y-m-d');
        // For testing, if today has no data, you might want to look at the last available date, 
        // but "Live" implies Today. We will stick to Today.
        
        $present_users = [];
        $latest_punch = null;
        $latest_timestamp = 0;

        foreach ($attendance_logs as $log) {
            // Check key variations again
            $timestamp = $log['timestamp'] ?? $log['time'] ?? $log['datetime'];
            $logDate = date('Y-m-d', strtotime($timestamp));

            if ($logDate === $today) {
                $uid = $log['id'];
                
                // Track Unique Present Users
                if (!isset($present_users[$uid])) {
                    $present_users[$uid] = [
                        'name' => $userMap[$uid] ?? 'Unknown (ID:'.$uid.')',
                        'first_punch' => $timestamp,
                        'last_punch' => $timestamp
                    ];
                } else {
                    // Update last punch
                    if ($timestamp > $present_users[$uid]['last_punch']) {
                        $present_users[$uid]['last_punch'] = $timestamp;
                    }
                     // Update first punch (should be earliest)
                    if ($timestamp < $present_users[$uid]['first_punch']) {
                        $present_users[$uid]['first_punch'] = $timestamp;
                    }
                }

                // Track Global Latest Punch
                $ts = strtotime($timestamp);
                if ($ts > $latest_timestamp) {
                    $latest_timestamp = $ts;
                    $latest_punch = [
                        'name' => $userMap[$uid] ?? 'Unknown',
                        'time' => date('h:i A', $ts),
                        'id' => $uid
                    ];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_users' => count($users),
                'present_count' => count($present_users),
                'latest_punch' => $latest_punch,
                'present_list' => array_values($present_users)
            ]
        ]);
    }
}
