<?php
// ============================================================
// api/customers.php
// Endpoint: /api/customers.php
//
// GET    /api/customers.php          → list all customers
// GET    /api/customers.php?id=1     → get one customer
// POST   /api/customers.php          → create customer
// PUT    /api/customers.php?id=1     → update customer
// DELETE /api/customers.php?id=1     → delete customer
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Customer.php';

requireAuth();
requireCsrf();
// Customer management is Admin-only
requireRole(['Admin']);

$customer = new Customer();
$method   = $_SERVER['REQUEST_METHOD'];
$id       = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $row = $customer->getById($id);
                $row
                    ? sendSuccess('Customer found.', $row)
                    : sendError('Customer not found.', 404);
            } else {
                sendSuccess('Customers retrieved.', $customer->getAll());
            }
            break;

        case 'POST':
            $data = getRequestBody();
            validateRequired($data, ['CID', 'Customer_Name', 'Address_ID', 'Address', 'Contact_Num']);

            $validatedData = [
                'CID' => validateInteger($data['CID'], 'CID'),
                'Customer_Name' => validateString($data['Customer_Name'], 'Customer_Name', 100),
                'Address_ID' => validateInteger($data['Address_ID'], 'Address_ID'),
                'Address' => validateString($data['Address'], 'Address', 100),
                'Contact_Num' => validateString($data['Contact_Num'], 'Contact_Num', 20)
            ];

            $customer->create($validatedData);
            sendSuccess('Customer created.', null, 201);
            break;

        case 'PUT':
            if (!$id) sendError('Customer ID is required for update.');
            $data = getRequestBody();
            validateRequired($data, ['Customer_Name', 'Address', 'Contact_Num']);

            $validatedData = [
                'Customer_Name' => validateString($data['Customer_Name'], 'Customer_Name', 100),
                'Address' => validateString($data['Address'], 'Address', 100),
                'Contact_Num' => validateString($data['Contact_Num'], 'Contact_Num', 20)
            ];

            $customer->update($id, $validatedData)
                ? sendSuccess('Customer updated.')
                : sendError('Customer not found or no changes made.', 404);
            break;

        case 'DELETE':
            if (!$id) sendError('Customer ID is required for delete.');
            $customer->delete($id)
                ? sendSuccess('Customer deleted.')
                : sendError('Customer not found.', 404);
            break;

        default:
            sendError('Method not allowed.', 405);
    }
} catch (PDOException $e) {
    sendError('A database error occurred. Please try again later.', 500);
}
