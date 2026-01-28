<?php

namespace CoreFly\Controllers;

use CoreFly\Models\AccountingAccount;
use CoreFly\Models\AccountingTransaction;
use CoreFly\Models\AccountingInvoice;
use CoreFly\Models\AccountingInvoiceItem;
use CoreFly\Utils\Logger;

class AccountingController extends BaseController {
    public function __construct() {
        parent::__construct();
    }

    private function seedDefaults() {
        $defaults = [
            ['code' => '100', 'name' => 'Merkez Kasa', 'type' => 'kasa', 'balance' => 0],
            ['code' => '102', 'name' => 'Merkez Banka', 'type' => 'banka', 'balance' => 0],
            ['code' => '600', 'name' => 'Yurtiçi Satışlar', 'type' => 'gelir', 'balance' => 0],
            ['code' => '770', 'name' => 'Genel Yönetim Giderleri', 'type' => 'gider', 'balance' => 0],
        ];

        foreach ($defaults as $acc) {
            // Ensure tenant_id is set if using multi-tenancy
            $acc['tenant_id'] = $this->getCurrentTenantId();
            $account = new AccountingAccount($acc);
            $account->save();
        }
    }

    // --- Stats ---
    public function stats() {
        try {
            $this->requirePermission('accounting.view');

            // Total Income (Transactions where type='income')
            $stmtIncome = $this->db->query("SELECT SUM(amount) as total FROM accounting_transactions WHERE type = 'income'");
            $rowIncome = $stmtIncome ? $stmtIncome->fetch() : null;
            $totalIncome = $rowIncome ? ($rowIncome['total'] ?? 0) : 0;

            // Total Expense
            $stmtExpense = $this->db->query("SELECT SUM(amount) as total FROM accounting_transactions WHERE type = 'expense'");
            $rowExpense = $stmtExpense ? $stmtExpense->fetch() : null;
            $totalExpense = $rowExpense ? ($rowExpense['total'] ?? 0) : 0;

            // Net Profit
            $netProfit = $totalIncome - $totalExpense;

            // Recent Transactions
            $recent = AccountingTransaction::all(); 
            
            // Sort by date desc
            usort($recent, function($a, $b) {
                // Access object properties, handle potential nulls
                $t1 = $a->created_at ? strtotime($a->created_at) : 0;
                $t2 = $b->created_at ? strtotime($b->created_at) : 0;
                return $t2 - $t1;
            });
            $recent = array_slice($recent, 0, 5);

            // Map to array and enrich
            $recentArray = [];
            foreach ($recent as $tx) {
                $txArr = $tx->toArray();
                $account = AccountingAccount::find($tx->account_id);
                $txArr['account_name'] = $account ? $account->name : 'Bilinmiyor';
                $recentArray[] = $txArr;
            }

            return $this->successResponse([
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
                'recent_transactions' => $recentArray
            ]);
        } catch (\Throwable $e) {
            Logger::error("Accounting Stats Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    // --- Accounts ---
    public function indexAccounts() {
        try {
            $this->requirePermission('accounting.view');
            $accounts = AccountingAccount::all();
            // Convert objects to arrays
            $items = array_map(fn($a) => $a->toArray(), $accounts);
            return $this->successResponse(['items' => $items]);
        } catch (\Throwable $e) {
            Logger::error("Accounting Accounts Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function storeAccount() {
        try {
            $this->requirePermission('accounting.create');
            $data = $this->getJsonInput();
            
            if (empty($data['name']) || empty($data['type'])) {
                return $this->errorResponse('Hesap adı ve tipi zorunludur.');
            }

            $data['balance'] = 0; // Initial balance is 0
            $data['tenant_id'] = $this->getCurrentTenantId();
            
            // Allow new fields
            $allowed = ['code', 'name', 'type', 'balance', 'tenant_id', 'tax_number', 'tax_office', 'address', 'phone', 'email'];
            $saveData = array_intersect_key($data, array_flip($allowed));
            
            $account = new AccountingAccount($saveData);
            $account->save();
            
            return $this->successResponse(['id' => $account->id, 'message' => 'Hesap oluşturuldu.']);
        } catch (\Throwable $e) {
            Logger::error("Accounting Store Account Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function updateAccount($id) {
        try {
            $this->requirePermission('accounting.update');
            $data = $this->getJsonInput();
            
            $account = AccountingAccount::find($id);
            if (!$account) {
                return $this->errorResponse('Hesap bulunamadı.', 404);
            }

            $allowed = ['code', 'name', 'type', 'tax_number', 'tax_office', 'address', 'phone', 'email'];
            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $account->$field = $data[$field];
                }
            }
            
            $account->save();
            
            return $this->successResponse(['message' => 'Hesap güncellendi.']);
        } catch (\Throwable $e) {
            Logger::error("Accounting Update Account Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    // --- Transactions ---
    public function indexTransactions() {
        try {
            $this->requirePermission('accounting.view');
            $transactions = AccountingTransaction::all();
            
            // Sort by date desc
            usort($transactions, function($a, $b) {
                $t1 = $a->date ? strtotime($a->date) : 0;
                $t2 = $b->date ? strtotime($b->date) : 0;
                return $t2 - $t1;
            });

            $items = [];
            foreach ($transactions as $tx) {
                $arr = $tx->toArray();
                $account = AccountingAccount::find($tx->account_id);
                $arr['account_name'] = $account ? $account->name : '-';
                $items[] = $arr;
            }

            return $this->successResponse(['items' => $items]);
        } catch (\Throwable $e) {
            Logger::error("Accounting Transactions Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function storeTransaction() {
        try {
            $this->requirePermission('accounting.create');
            $data = $this->getJsonInput();

            if (empty($data['account_id']) || empty($data['amount']) || empty($data['type']) || empty($data['date'])) {
                return $this->errorResponse('Zorunlu alanları doldurunuz.');
            }

            $this->db->beginTransaction();
            try {
                // Create transaction
                $data['tenant_id'] = $this->getCurrentTenantId();
                $transaction = new AccountingTransaction($data);
                $transaction->save();

                // Update Account Balance
                $account = AccountingAccount::find($data['account_id']);
                if ($account) {
                    $newBalance = (float)$account->balance;
                    if ($data['type'] == 'income') {
                        $newBalance += (float)$data['amount'];
                    } else {
                        $newBalance -= (float)$data['amount'];
                    }
                    
                    // Update balance
                    $account->balance = $newBalance;
                    $account->save();
                }

                $this->db->commit();
                return $this->successResponse(['id' => $transaction->id, 'message' => 'İşlem kaydedildi.']);
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            Logger::error("Accounting Store Transaction Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function destroyTransaction($id) {
        try {
            $this->requirePermission('accounting.delete');
            
            $transaction = AccountingTransaction::find($id);
            if (!$transaction) {
                return $this->errorResponse('İşlem bulunamadı.', 404);
            }

            $this->db->beginTransaction();
            try {
                // Revert Balance
                $account = AccountingAccount::find($transaction->account_id);
                if ($account) {
                    $newBalance = (float)$account->balance;
                    if ($transaction->type == 'income') {
                        $newBalance -= (float)$transaction->amount;
                    } else {
                        $newBalance += (float)$transaction->amount;
                    }
                    $account->balance = $newBalance;
                    $account->save();
                }

                $transaction->delete();
                $this->db->commit();
                return $this->successResponse(['message' => 'İşlem silindi.']);
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            Logger::error("Accounting Delete Transaction Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    // --- Invoices ---
    public function indexInvoices() {
        try {
            $this->requirePermission('accounting.view');
            $invoices = AccountingInvoice::all();
            
            $items = [];
            foreach ($invoices as $inv) {
                $arr = $inv->toArray();
                $account = AccountingAccount::find($inv->account_id);
                $arr['account_name'] = $account ? $account->name : '-';
                $items[] = $arr;
            }
            
            // Sort by date desc
            usort($items, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return $this->successResponse(['items' => $items]);
        } catch (\Throwable $e) {
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function showInvoice($id) {
        try {
            $this->requirePermission('accounting.view');
            $invoice = AccountingInvoice::find($id);
            if (!$invoice) return $this->errorResponse('Fatura bulunamadı', 404);
            
            $data = $invoice->toArray();
            $data['items'] = array_map(fn($i) => $i->toArray(), $invoice->getItems());
            
            return $this->successResponse($data);
        } catch (\Throwable $e) {
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function storeInvoice() {
        try {
            $this->requirePermission('accounting.create');
            $data = $this->getJsonInput();
            
            if (empty($data['account_id']) || empty($data['date']) || empty($data['items'])) {
                return $this->errorResponse('Zorunlu alanları doldurunuz.');
            }

            $this->db->beginTransaction();
            try {
                // Calculate totals
                $subtotal = 0;
                $taxTotal = 0;
                
                foreach ($data['items'] as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                    $subtotal += $lineTotal;
                    $taxTotal += $lineTax;
                }
                $total = $subtotal + $taxTotal;

                $invoiceData = [
                    'tenant_id' => $this->getCurrentTenantId(),
                    'account_id' => $data['account_id'],
                    'number' => $data['number'] ?? 'INV-' . time(),
                    'date' => $data['date'],
                    'due_date' => $data['due_date'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'status' => $data['status'] ?? 'draft',
                    'notes' => $data['notes'] ?? null
                ];

                $invoice = new AccountingInvoice($invoiceData);
                $invoice->save();

                foreach ($data['items'] as $item) {
                    $itemData = [
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'tax_rate' => $item['tax_rate'],
                        'total' => ($item['quantity'] * $item['unit_price']) * (1 + $item['tax_rate']/100)
                    ];
                    $invItem = new AccountingInvoiceItem($itemData);
                    $invItem->save();
                }

                // If status is 'paid', create a transaction automatically? 
                // For now, let's keep it simple. User can create transaction separately or we can add a feature later.

                $this->db->commit();
                return $this->successResponse(['id' => $invoice->id, 'message' => 'Fatura oluşturuldu.']);
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            Logger::error("Accounting Store Invoice Error: " . $e->getMessage());
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function updateInvoice($id) {
        // Simpler update: Delete items and recreate. 
        try {
            $this->requirePermission('accounting.update');
            $data = $this->getJsonInput();
            $invoice = AccountingInvoice::find($id);
            
            if (!$invoice) return $this->errorResponse('Fatura bulunamadı', 404);

            $this->db->beginTransaction();
            try {
                // Update Invoice Details
                if (isset($data['account_id'])) $invoice->account_id = $data['account_id'];
                if (isset($data['date'])) $invoice->date = $data['date'];
                if (isset($data['due_date'])) $invoice->due_date = $data['due_date'];
                if (isset($data['number'])) $invoice->number = $data['number'];
                if (isset($data['status'])) $invoice->status = $data['status'];
                if (isset($data['notes'])) $invoice->notes = $data['notes'];

                // Recalculate if items provided
                if (isset($data['items'])) {
                    // Delete old items (Using raw SQL for speed/simplicity as BaseModel might not have deleteWhere)
                    $this->db->exec("DELETE FROM accounting_invoice_items WHERE invoice_id = '{$id}'");
                    
                    $subtotal = 0;
                    $taxTotal = 0;
                    
                    foreach ($data['items'] as $item) {
                        $lineTotal = $item['quantity'] * $item['unit_price'];
                        $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                        $subtotal += $lineTotal;
                        $taxTotal += $lineTax;

                        $itemData = [
                            'invoice_id' => $id,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'tax_rate' => $item['tax_rate'],
                            'total' => $lineTotal + $lineTax
                        ];
                        $invItem = new AccountingInvoiceItem($itemData);
                        $invItem->save();
                    }
                    $invoice->subtotal = $subtotal;
                    $invoice->tax_total = $taxTotal;
                    $invoice->total = $subtotal + $taxTotal;
                }

                $invoice->save();
                $this->db->commit();
                return $this->successResponse(['message' => 'Fatura güncellendi.']);
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }

    public function destroyInvoice($id) {
        try {
            $this->requirePermission('accounting.delete');
            $invoice = AccountingInvoice::find($id);
            if ($invoice) {
                $invoice->delete(); // Cascade delete should handle items if DB supports it, otherwise need manual
                // Since we used manual create table with ON DELETE CASCADE in ensureTablesExist, it should be fine.
                return $this->successResponse(['message' => 'Fatura silindi.']);
            }
            return $this->errorResponse('Fatura bulunamadı', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse('Bir hata oluştu: ' . $e->getMessage(), 500);
        }
    }
}
