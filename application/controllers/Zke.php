<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Zke extends CI_Controller {
    private $device_ip = '124.29.228.65';
    private $device_port = 4370;
    
    public function __construct() {
        parent::__construct();
        // $this->load->model('Attendance_model'); // Load model if needed
    }

    public function index() {
         // Create TCP socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            die("Socket creation failed: " . socket_strerror(socket_last_error()));
        }

        // Connect to device
        $result = @socket_connect($socket, $this->device_ip, $this->device_port);
        if (!$result) {
            die("Connection failed: " . socket_strerror(socket_last_error($socket)));
        }

        echo "Connected to ZKTeco device at {$this->device_ip}:{$this->device_port}";

        // Example: send handshake or command (binary)
        // $packet = pack(...); // depends on protocol
        // socket_write($socket, $packet, strlen($packet));

        // Example: read response
        // $response = socket_read($socket, 1024);
        // echo "Response: " . bin2hex($response);

        socket_close($socket);
    }

    public function fetch() {
        echo "Attendance synced!";
    }
}
