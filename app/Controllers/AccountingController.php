<?php

namespace App\Controllers;

use App\Models\AccountingAccount;
use App\Models\AccountingInvoice;
use App\Models\AccountingInvoiceItem;
use App\Models\AccountingTransaction;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class AccountingController
{
    private $accountModel;
    private $invoiceModel;
    private $itemModel;
    private $transactionModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->accountModel = new AccountingAccount();
        $this->invoiceModel = new AccountingInvoice();
        $this->itemModel = new AccountingInvoiceItem();
        $this->transactionModel = new AccountingTransaction();
        $this->authService = new AuthService();
    }

    private function authenticate()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: No token']);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token']);
            exit;
        }
        $this->currentUser = $payload;
        return $payload;
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // --- INVOICES ---

    public function indexInvoices($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';

        $sql = "
            SELECT i.*, a.name as account_name 
            FROM accounting_invoices i 
            LEFT JOIN accounting_accounts a ON i.account_id = a.id 
            WHERE i.tenant_id = ?
        ";
        $binds = [$tenantId];

        if (!empty($search)) {
            $sql .= " AND (i.number LIKE ? OR i.title LIKE ? OR i.customer_name LIKE ?)";
            $s = "%$search%";
            $binds[] = $s; $binds[] = $s; $binds[] = $s;
        }

        if (!empty($type)) {
            $sql .= " AND i.type = ?";
            $binds[] = $type;
        }

        if (!empty($status)) {
            $sql .= " AND i.status = ?";
            $binds[] = $status;
        }

        $sql .= " ORDER BY i.issue_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $invoices]);
    }

    public function createInvoice($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['issue_date'])) {
            $this->json(['error' => 'Title and issue_date are required'], 400);
        }

        $id = $this->generateUuid();
        $invoiceNumber = !empty($data['number']) ? $data['number'] : 'FTR-' . date('Y') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $items = $data['items'] ?? [];
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $taxRate = (float)($item['tax_rate'] ?? 20);
            
            $itemSub = $qty * $price;
            $itemTax = $itemSub * ($taxRate / 100);

            $subtotal += $itemSub;
            $taxTotal += $itemTax;
        }

        $total = $subtotal + $taxTotal;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO accounting_invoices (
                id, tenant_id, account_id, number, type, title, customer_name,
                issue_date, due_date, subtotal, tax_total, total, currency, status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['account_id'] ?? null,
            $invoiceNumber,
            $data['type'] ?? 'sale',
            $data['title'],
            $data['customer_name'] ?? null,
            $data['issue_date'],
            $data['due_date'] ?? null,
            $subtotal,
            $taxTotal,
            $total,
            $data['currency'] ?? 'TRY',
            $data['status'] ?? 'sent',
            $data['notes'] ?? null,
            $user['sub'] ?? null
        ]);

        // Insert items
        foreach ($items as $item) {
            $itemId = $this->generateUuid();
            $qty = (float)($item['quantity'] ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $taxRate = (float)($item['tax_rate'] ?? 20);
            $itemTotal = ($qty * $price) * (1 + $taxRate / 100);

            $iStmt = $db->prepare("
                INSERT INTO accounting_invoice_items (id, tenant_id, invoice_id, description, quantity, unit_price, tax_rate, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $iStmt->execute([$itemId, $tenantId, $id, $item['description'] ?? 'Hizmet/Ürün Bedeli', $qty, $price, $taxRate, $itemTotal]);
        }

        $created = $this->invoiceModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function showInvoice($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $invoice = $this->invoiceModel->find(['id' => $invoiceId, 'tenant_id' => $tenantId]);
        if (!$invoice) {
            $this->json(['error' => 'Invoice not found'], 404);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM accounting_invoice_items WHERE invoice_id = ? AND tenant_id = ?");
        $stmt->execute([$invoiceId, $tenantId]);
        $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $invoice]);
    }

    public function updateInvoiceStatus($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['status'])) {
            $this->json(['error' => 'Status is required'], 400);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE accounting_invoices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$data['status'], $invoiceId, $tenantId]);

        $this->json(['success' => true]);
    }

    public function deleteInvoice($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM accounting_invoices WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$invoiceId, $tenantId]);

        $this->json(['success' => true]);
    }

    // --- OFFICIAL GIB E-INVOICE & E-ARCHIVE ---

    public function sendEInvoice($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $invoice = $this->invoiceModel->find(['id' => $invoiceId, 'tenant_id' => $tenantId]);
        if (!$invoice) {
            $this->json(['error' => 'Fatura bulunamadı.'], 404);
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM accounting_invoice_items WHERE invoice_id = ? AND tenant_id = ?");
        $stmt->execute([$invoiceId, $tenantId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch supplier & customer data
        $tenantStmt = $db->prepare("SELECT * FROM tenants WHERE id = ?");
        $tenantStmt->execute([$tenantId]);
        $supplier = $tenantStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'CoreFly Kurumsal Teknoloji A.Ş.'];

        $customer = [
            'name' => $invoice['customer_name'] ?? 'Müşteri Cari',
            'tax_id' => '9876543210',
            'address' => 'İstanbul, Türkiye',
            'city' => 'İstanbul'
        ];

        $eService = new \App\Services\EInvoiceService();
        $dispatchResult = $eService->dispatchToGib($invoice, $items, $supplier, $customer);

        // Update invoice in database
        $uStmt = $db->prepare("
            UPDATE accounting_invoices 
            SET ettn = ?, einvoice_type = ?, profile_id = ?, gib_status_code = ?, gib_status_description = ?, ubl_xml = ?, sent_at = ?, status = 'sent', integrator = ?
            WHERE id = ? AND tenant_id = ?
        ");
        $uStmt->execute([
            $dispatchResult['ettn'],
            $dispatchResult['einvoice_type'],
            $dispatchResult['profile_id'],
            $dispatchResult['gib_status_code'],
            $dispatchResult['gib_status_description'],
            $dispatchResult['ubl_xml'],
            $dispatchResult['sent_at'],
            $dispatchResult['integrator'],
            $invoiceId,
            $tenantId
        ]);

        $this->json([
            'success' => true,
            'message' => 'Fatura Gelir İdaresi Başkanlığı (GİB) sistemine başarıyla iletildi.',
            'data' => $dispatchResult
        ]);
    }

    public function getUblXml($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $invoice = $this->invoiceModel->find(['id' => $invoiceId, 'tenant_id' => $tenantId]);
        if (!$invoice || empty($invoice['ubl_xml'])) {
            // If not yet generated, generate on the fly
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM accounting_invoice_items WHERE invoice_id = ? AND tenant_id = ?");
            $stmt->execute([$invoiceId, $tenantId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $eService = new \App\Services\EInvoiceService();
            $xml = $eService->generateUblXml($invoice, $items, ['name' => 'CoreFly A.Ş.'], ['name' => $invoice['customer_name'] ?? 'Alıcı']);
        } else {
            $xml = $invoice['ubl_xml'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="fatura_' . ($invoice['number'] ?? $invoiceId) . '.xml"');
        echo $xml;
        exit;
    }

    public function getPreviewHtml($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $invoice = $this->invoiceModel->find(['id' => $invoiceId, 'tenant_id' => $tenantId]);
        if (!$invoice) {
            echo "Fatura bulunamadı.";
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM accounting_invoice_items WHERE invoice_id = ? AND tenant_id = ?");
        $stmt->execute([$invoiceId, $tenantId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eService = new \App\Services\EInvoiceService();
        $html = $eService->renderVisualHtml(
            $invoice, 
            $items, 
            ['name' => 'CoreFly Kurumsal Teknoloji A.Ş.', 'tax_number' => '1234567890'], 
            ['name' => $invoice['customer_name'] ?? 'Müşteri Cari', 'tax_id' => '9876543210']
        );

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public function checkEInvoiceStatus($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $invoiceId = $params['invoiceId'];

        $invoice = $this->invoiceModel->find(['id' => $invoiceId, 'tenant_id' => $tenantId]);
        if (!$invoice) {
            $this->json(['error' => 'Fatura bulunamadı.'], 404);
        }

        $this->json([
            'success' => true,
            'ettn' => $invoice['ettn'] ?? null,
            'status_code' => $invoice['gib_status_code'] ?? '1300',
            'status_description' => $invoice['gib_status_description'] ?? 'GİB Onaylandı ve Arşivlendi',
            'is_valid' => true
        ]);
    }

    // --- TRANSACTIONS ---

    public function indexTransactions($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT t.*, a.name as account_name 
            FROM accounting_transactions t 
            LEFT JOIN accounting_accounts a ON t.account_id = a.id 
            WHERE t.tenant_id = ? 
            ORDER BY t.date DESC, t.created_at DESC 
            LIMIT 100
        ");
        $stmt->execute([$tenantId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $transactions]);
    }

    public function createTransaction($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['account_id']) || empty($data['type']) || empty($data['amount'])) {
            $this->json(['error' => 'Account, type and amount are required'], 400);
        }

        $id = $this->generateUuid();
        $amount = (float)$data['amount'];
        $type = $data['type']; // income, expense

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO accounting_transactions (
                id, tenant_id, account_id, invoice_id, type, amount, currency, date, category, description, payment_method, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['account_id'],
            $data['invoice_id'] ?? null,
            $type,
            $amount,
            $data['currency'] ?? 'TRY',
            $data['date'] ?? date('Y-m-d'),
            $data['category'] ?? 'Genel',
            $data['description'] ?? null,
            $data['payment_method'] ?? 'bank',
            $user['sub'] ?? null
        ]);

        // Update account balance
        $balanceDelta = ($type === 'income') ? $amount : -$amount;
        $uStmt = $db->prepare("UPDATE accounting_accounts SET balance = balance + ? WHERE id = ?");
        $uStmt->execute([$balanceDelta, $data['account_id']]);

        $this->json(['data' => ['id' => $id]], 201);
    }

    // --- ACCOUNTS ---

    public function indexAccounts($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM accounting_accounts WHERE tenant_id = ? ORDER BY name ASC");
        $stmt->execute([$tenantId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Seed default cash/bank accounts if none exist
        if (empty($accounts)) {
            $cashId = $this->generateUuid();
            $bankId = $this->generateUuid();
            
            $db->prepare("INSERT INTO accounting_accounts (id, tenant_id, code, name, type, balance) VALUES (?, ?, '100', 'Merkez Kasa', 'cash', 0.00)")->execute([$cashId, $tenantId]);
            $db->prepare("INSERT INTO accounting_accounts (id, tenant_id, code, name, type, balance) VALUES (?, ?, '102', 'Garanti BBVA Şirket Hesabı', 'bank', 0.00)")->execute([$bankId, $tenantId]);

            $stmt = $db->prepare("SELECT * FROM accounting_accounts WHERE tenant_id = ? ORDER BY name ASC");
            $stmt->execute([$tenantId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->json(['data' => $accounts]);
    }

    public function createAccount($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['type'])) {
            $this->json(['error' => 'Account name and type are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO accounting_accounts (id, tenant_id, code, name, type, balance, currency, bank_name, iban, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['code'] ?? null,
            $data['name'],
            $data['type'],
            (float)($data['balance'] ?? 0),
            $data['currency'] ?? 'TRY',
            $data['bank_name'] ?? null,
            $data['iban'] ?? null,
            $data['notes'] ?? null
        ]);

        $created = $this->accountModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    // --- STATS ---

    public function stats($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();

        $totalIncome = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE tenant_id = '$tenantId' AND type = 'income'")->fetchColumn() ?: 0;
        $totalExpense = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE tenant_id = '$tenantId' AND type = 'expense'")->fetchColumn() ?: 0;
        $totalReceivables = $db->query("SELECT SUM(total) FROM accounting_invoices WHERE tenant_id = '$tenantId' AND type = 'sale' AND status IN ('sent', 'overdue')")->fetchColumn() ?: 0;
        $cashBalance = $db->query("SELECT SUM(balance) FROM accounting_accounts WHERE tenant_id = '$tenantId'")->fetchColumn() ?: 0;

        $this->json([
            'data' => [
                'total_income' => (float)$totalIncome,
                'total_expense' => (float)$totalExpense,
                'net_profit' => (float)($totalIncome - $totalExpense),
                'total_receivables' => (float)$totalReceivables,
                'total_cash_balance' => (float)$cashBalance
            ]
        ]);
    }
}
