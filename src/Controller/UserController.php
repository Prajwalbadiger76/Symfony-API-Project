<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    private $conn;

    // Constructor to connect with PostgreSQL database
    public function __construct()
    {
        // Fetching DB details from .env
        $dbHost = $_ENV['DB_HOST'];
        $dbPort = $_ENV['DB_PORT'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASSWORD'];

        // Establishing database connection using pg_connect
        $this->conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass");
    }

    // GET API to fetch all users
    #[Route('/users', name: 'get_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        // SQL query to select all users
        $result = pg_query($this->conn, "SELECT id, name, email FROM users");

        $users = [];

        // Looping through each row and pushing into array
        while ($row = pg_fetch_assoc($result)) {
            $users[] = $row;
        }

        // Returning users in JSON format
        return $this->json($users);
    }

    // POST API to add a new user
    #[Route('/users', name: 'add_user', methods: ['POST'])]
    public function addUser(Request $request): JsonResponse
    {
        // Getting JSON data from request
        $data = json_decode($request->getContent(), true);

        // Escaping input data to avoid SQL injection
        $name = pg_escape_string($data['name'] ?? '');
        $email = pg_escape_string($data['email'] ?? '');

        // Validating required fields
        if (empty($name) || empty($email)) {
            return $this->json(['error' => 'Name and email are required.'], Response::HTTP_BAD_REQUEST);
        }

        // SQL query to insert the user
        $query = "INSERT INTO users(name, email) VALUES('$name', '$email')";
        $result = pg_query($this->conn, $query);

        // If insert failed
        if (!$result) {
            return $this->json(['error' => 'Failed to insert user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // On successful insert
        return $this->json(['message' => 'User added successfully.']);
    }
}
