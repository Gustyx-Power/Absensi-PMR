<?php
/**
 * DATABASE SESSION HANDLER
 * Solves Vercel/Serverless session persistence issues by storing sessions in MySQL/TiDB.
 */

// Include database connection (using __DIR__ for safety)
require_once __DIR__ . '/database.php';

class DbSessionHandler implements SessionHandlerInterface
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Required methods for SessionHandlerInterface
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['data'];
            }
        }
        return '';
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data)
    {
        $timestamp = time();
        // Use REPLACE INTO to handle both inserts and updates
        $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $id, $data, $timestamp);
        return $stmt->execute();
    }

    #[\ReturnTypeWillChange]
    public function destroy($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        $old = time() - $maxlifetime;
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE timestamp < ?");
        $stmt->bind_param("i", $old);
        return $stmt->execute();
    }
}

// Only use DB Sessions if we are on Vercel (Cloud)
// Localhost can keep using files for speed, or switch if you want consistency.
// We detect Vercel via DB_HOST usually, but let's check VERCEL env too.
$isVercel = getenv('VERCEL') || isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('DB_HOST');

if (session_status() === PHP_SESSION_NONE) {
    if ($isVercel) {
        $handler = new DbSessionHandler($conn);
        session_set_save_handler($handler, true);
    }
    session_start();
}
?>