<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Exceptions\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'status', 'date_from', 'date_to', 'sort', 'order', 'method', 'invoice_sent']);
        $result  = Invoice::search($filters, $page, $perPage);

        if ($request->isApi()) {
            return $this->success(['invoices' => $result['data'], 'pagination' => $result['pagination']]);
        }

        return $this->view('invoices/index', [
            'invoices'   => $result['data'],
            'pagination' => $result['pagination'],
            'filters'    => $filters,
            'title'      => 'Invoices',
            'pageTitle' => 'Invoices',
        ]);
    }

    public function show(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_VIEW);
        $id      = (int)$request->param('invoice_id');
        $invoice = Invoice::findWithDetails($id);
        if (!$invoice) return $this->error('Invoice not found', 404);

        if ($request->isApi()) return $this->success($invoice);
        return $this->view('invoices/show', ['invoice' => $invoice, 'title' => "Invoice #{$invoice['invoice_number']}"]);
    }

    public function addPayment(Request $request): Response
    {
        $this->requirePermission(MODULE_PAYMENTS, ACTION_ADD);
        $id      = (int)$request->param('invoice_id');
        $invoice = Invoice::findOrFail($id);

        $amount = (float)$request->input('payment_amount', 0);
        $mode   = $request->input('payment_mode');

        if ($amount <= 0) throw new ValidationException(['payment_amount' => ['Payment amount must be greater than 0']]);
        if (!in_array($mode, [PAYMENT_CASH, PAYMENT_BDO, PAYMENT_GCASH])) throw new ValidationException(['payment_mode' => ['Invalid payment mode']]);

        $paymentNumber = Payment::nextPaymentNumber($id);
        $paymentId = Payment::create([
            'invoice_id'       => $id,
            'payment_number'   => $paymentNumber,
            'payment_date'     => $request->input('payment_date', date('Y-m-d')),
            'payment_amount'   => $amount,
            'payment_mode'     => $mode,
            'reference_number' => $request->input('reference_number'),
            'notes'            => $request->input('notes'),
            'recorded_by'      => $this->currentUserId(),
        ]);

        Invoice::updatePaymentStatus($id);
        $updated = Invoice::find($id);

        NotificationService::paymentReceived($id, $amount, $invoice['invoice_number']);

        $refNumber = $request->input('reference_number');
        AuditService::log(AUDIT_PAYMENT, MODULE_PAYMENTS, $paymentId, null, [
            'invoice_id'       => $id,
            'invoice_number'   => $invoice['invoice_number'],
            'client'           => $invoice['client_name'] ?? null,
            'payment_number'   => $paymentNumber,
            'payment_date'     => $request->input('payment_date', date('Y-m-d')),
            'payment_amount'   => $amount,
            'payment_mode'     => $mode,
            'reference_number' => $refNumber ?: null,
            'notes'            => $request->input('notes') ?: null,
            'new_status'       => $updated['payment_status'],
            'total_paid_after' => (float)$updated['total_paid'],
            'balance_after'    => (float)$updated['total_amount'] - (float)$updated['total_paid'],
        ], "Payment #{$paymentNumber} of ₱" . number_format($amount, 2) . " added to Invoice #{$invoice['invoice_number']}");

        return $this->success([
            'payment_id'        => $paymentId,
            'new_payment_status'=> $updated['payment_status'],
            'total_paid'        => (float)$updated['total_paid'],
            'balance_due'       => (float)$updated['total_amount'] - (float)$updated['total_paid'],
        ], 'Payment recorded');
    }

    public function updatePayment(Request $request): Response
    {
        $this->requirePermission(MODULE_PAYMENTS, ACTION_EDIT);
        $paymentId = (int)$request->param('payment_id');
        $payment   = Payment::find($paymentId);
        if (!$payment) return $this->error('Payment not found', 404);

        $amount = (float)$request->input('payment_amount', $payment['payment_amount']);
        $mode   = $request->input('payment_mode', $payment['payment_mode']);

        if ($amount <= 0) throw new \App\Exceptions\ValidationException(['payment_amount' => ['Payment amount must be greater than 0']]);
        if (!in_array($mode, [PAYMENT_CASH, PAYMENT_BDO, PAYMENT_GCASH])) throw new \App\Exceptions\ValidationException(['payment_mode' => ['Invalid payment mode']]);

        $old = $payment;
        Payment::update($paymentId, [
            'payment_date'     => $request->input('payment_date', $payment['payment_date']),
            'payment_amount'   => $amount,
            'payment_mode'     => $mode,
            'reference_number' => $request->input('reference_number', $payment['reference_number']),
            'notes'            => $request->input('notes', $payment['notes']),
        ]);

        Invoice::updatePaymentStatus((int)$payment['invoice_id']);
        $updated = Invoice::find((int)$payment['invoice_id']);

        AuditService::log(AUDIT_UPDATE, MODULE_PAYMENTS, $paymentId, $old, [
            'payment_date'     => $request->input('payment_date', $payment['payment_date']),
            'payment_amount'   => $amount,
            'payment_mode'     => $mode,
            'reference_number' => $request->input('reference_number', $payment['reference_number']),
        ], "Payment #{$payment['payment_number']} updated");

        return $this->success([
            'new_payment_status' => $updated['payment_status'],
            'total_paid'         => (float)$updated['total_paid'],
            'balance_due'        => (float)$updated['total_amount'] - (float)$updated['total_paid'],
        ], 'Payment updated');
    }

    public function deletePayment(Request $request): Response
    {
        $this->requirePermission(MODULE_PAYMENTS, ACTION_DELETE);
        $paymentId = (int)$request->param('payment_id');
        $payment   = Payment::find($paymentId);
        if (!$payment) return $this->error('Payment not found', 404);

        $invoiceId = (int)$payment['invoice_id'];
        Payment::delete($paymentId);
        Invoice::updatePaymentStatus($invoiceId);
        $updated = Invoice::find($invoiceId);

        AuditService::log(AUDIT_DELETE, MODULE_PAYMENTS, $paymentId, $payment, null,
            "Payment #{$payment['payment_number']} deleted from Invoice #{$invoiceId}");

        return $this->success([
            'new_payment_status' => $updated['payment_status'],
            'total_paid'         => (float)$updated['total_paid'],
            'balance_due'        => (float)$updated['total_amount'] - (float)$updated['total_paid'],
        ], 'Payment deleted');
    }

    public function print(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_VIEW);
        $id      = (int)$request->param('invoice_id');
        $invoice = Invoice::findWithDetails($id);
        if (!$invoice) return $this->error('Invoice not found', 404);

        AuditService::log(AUDIT_VIEW, MODULE_INVOICES, $id, null, null, "Printed invoice #{$id}");
        return $this->view('invoices/print', ['invoice' => $invoice, 'title' => "Invoice #{$invoice['invoice_number']}"], 200);
    }

    public function toggleSent(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_EDIT);
        $id      = (int)$request->param('invoice_id');
        $invoice = Invoice::findOrFail($id);
        $newStatus = $invoice['invoice_sent'] === INVOICE_SENT ? 'not_sent' : INVOICE_SENT;
        Invoice::update($id, ['invoice_sent' => $newStatus]);
        return $this->success(['invoice_sent' => $newStatus], 'Updated');
    }

    public function sendEmail(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_EDIT);
        $id      = (int)$request->param('invoice_id');
        $invoice = Invoice::findWithDetails($id);
        if (!$invoice) return $this->error('Invoice not found', 404);

        $email = $request->input('recipient_email');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(['recipient_email' => ['Valid email required']]);
        }

        (new EmailService())->sendInvoice($email, $invoice);
        Invoice::update($id, ['invoice_sent' => INVOICE_SENT]);
        AuditService::log(AUDIT_UPDATE, MODULE_INVOICES, $id, null, null, "Invoice #{$id} emailed to {$email}");

        return $this->success(null, 'Invoice sent');
    }
}
