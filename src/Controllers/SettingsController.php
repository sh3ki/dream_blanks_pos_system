<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Services\AuditService;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_SETTINGS, ACTION_VIEW);
        $settings = Setting::allAsArray();

        if ($request->isApi()) return $this->success($settings);
        return $this->view('settings/general', ['settings' => $settings, 'title' => 'Settings']);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_SETTINGS, ACTION_EDIT);
        $allowed = [
            'business_name', 'business_address', 'business_email', 'business_phone',
            'currency_symbol', 'currency_code',
            'date_format', 'time_format', 'timezone',
            'low_stock_alert_default',
            'invoice_prefix', 'invoice_next_number', 'invoice_reset_frequency',
            'tax_rate', 'receipt_footer',
        ];

        $old = Setting::allAsArray();
        foreach ($allowed as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        AuditService::log(AUDIT_UPDATE, MODULE_SETTINGS, null, $old, Setting::allAsArray(), 'Updated system settings');
        return $this->success(null, 'Settings updated');
    }
}
