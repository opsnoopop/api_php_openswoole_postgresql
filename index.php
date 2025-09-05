<?php
use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

$objServer = new Server("0.0.0.0", 9501);

$objServer->on("start", function (Server $objServer) {
    echo "Swoole HTTP Server started at http://0.0.0.0:9501\n";
});

$objServer->on("request", function (Request $objRequest, Response $objResponse) {
    $strRequestUri      = $objRequest->server['request_uri'] ?? '/';
    $strRequestMethod   = strtoupper($objRequest->server['request_method'] ?? 'GET');
    $objResponse->header("Content-Type", "application/json");

    if ($strRequestUri === '/' && $strRequestMethod === 'GET') {
        // Health Check
        $objResponse->status(200);
        $objResponse->end(json_encode([
            "message" => "Hello World from PHP (OpenSwoole + PostgreSQL)"
        ]));

    } else if ($strRequestUri === '/users' && $strRequestMethod === 'POST') {
        // Create user

        $arrData = json_decode($objRequest->rawContent(), true);
        if (isset($arrData['username']) && isset($arrData['email'])) {
            try {
                $arrOptions = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => true,
                    PDO::ATTR_TIMEOUT            => 60,
                ];
                $objPDO = new PDO("pgsql:host=container_postgresql;dbname=testdb;", "testuser", "testpass", $arrOptions);

                // $objPDO->beginTransaction(); // ACID (Atomicity Consistency Isolation Durability) step 1
                
                $objStatement = $objPDO->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
                $objStatement->execute([$arrData['username'], $arrData['email']]);
                $intUserId = $objPDO->lastInsertId();

                // $objPDO->commit(); // ACID (Atomicity Consistency Isolation Durability) step 2

                $objResponse->status(201);
                $objResponse->end(json_encode([
                    "message" => "User created successfully",
                    "user_id" => $intUserId
                ]));
            } catch (PDOException $e) {
                // $objPDO->rollback(); // ACID (Atomicity Consistency Isolation Durability) step 3
                $objResponse->status(500);
                $objResponse->end(json_encode(["error" => $e->getMessage()]));
            }
        } else {
            $objResponse->status(400);
            $objResponse->end(json_encode(["error" => "Name and email are required"]));
        }

    } else if (str_starts_with($strRequestUri, '/users/') && $strRequestMethod === 'GET') {
        // Get user

        $arrParts = explode('/', $strRequestUri);
        $intUserId = $arrParts[2] ?? null;
        if ($intUserId && ctype_digit($intUserId)) {
            try {
                $arrOptions = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => true,
                    PDO::ATTR_TIMEOUT            => 60,
                ];
                $objPDO = new PDO("pgsql:host=container_postgresql;dbname=testdb;", "testuser", "testpass", $arrOptions);
                $objStatement = $objPDO->prepare("SELECT user_id, username, email FROM users WHERE user_id = ?");
                $objStatement->execute([$intUserId]);
                $arrRow = $objStatement->fetch();

                $objResponse->status(200);
                $objResponse->end(json_encode(["user_id" => $arrRow['user_id'], "username" => $arrRow['username'], "email" => $arrRow['email']]));
            } catch (PDOException $e) {
                $objResponse->status(500);
                $objResponse->end(json_encode(["error" => $e->getMessage()]));
            }
        } else {
            $objResponse->status(400);
            $objResponse->end(json_encode(["error" => "Invalid user_id"]));
        }
        
    } else {

        $objResponse->status(404);
        $objResponse->end(json_encode(["error" => "Not Found"]));

    }
});

$objServer->start();