<?php

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        return $_ENV['APP_BASE_PATH'] ?? '';
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $basePath = app_base_path();
        $path = '/' . ltrim($path, '/');

        if ($basePath === '' || $basePath === '/') {
            return $path === '//' ? '/' : $path;
        }

        return rtrim($basePath, '/') . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        return app_url('/' . ltrim($path, '/'));
    }
}

/**
 * Render an enhanced pagination bar with first/prev/next/last controls,
 * ellipsis for large page ranges, and a per-page selector.
 *
 * @param array  $pagination   Must contain: current_page, last_page, per_page, total
 * @param array  $queryParams  All current query params EXCEPT the page key and 'per_page'
 * @param string $pageKey      Query param name for the page number (default: 'page')
 * @param string $perPageKey   Query param name for the per-page number (default: 'per_page')
 */
if (!function_exists('renderPagination')) {
    function renderPagination(array $pagination, array $queryParams, string $pageKey = 'page', string $perPageKey = 'per_page'): string
    {
        $current = (int)($pagination['current_page'] ?? 1);
        $last    = (int)($pagination['last_page']    ?? 1);
        $perPage = (int)($pagination['per_page']     ?? 10);
        $total   = (int)($pagination['total']        ?? 0);

        $base = array_filter($queryParams, fn($v) => $v !== '' && $v !== null);
        $qs   = $base ? http_build_query($base) . '&' : '';

        $pageUrl  = fn(int $p) => '?' . $qs . $perPageKey . '=' . $perPage . '&' . $pageKey . '=' . $p;
        $ppUrl    = fn(int $n) => '?' . $qs . $perPageKey . '=' . $n . '&' . $pageKey . '=1';

        $html  = '<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:10px 0">';

        // Per-page + total info
        $html .= '<div style="display:flex;align-items:center;gap:6px;padding-left:16px;font-size:.82rem;color:var(--color-gray-500)">';
        $html .= '<span>Rows per page:</span>';
        $html .= '<select onchange="location.href=this.value" style="border:1px solid var(--color-gray-200);border-radius:6px;padding:2px 6px;font-size:.82rem;height:28px">';
        foreach ([10, 20, 50, 100] as $n) {
            $sel  = ($n === $perPage) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($ppUrl($n)) . '"' . $sel . '>' . $n . '</option>';
        }
        $html .= '</select>';
        $from  = min($total, ($current - 1) * $perPage + 1);
        $to    = min($total, $current * $perPage);
        $html .= '<span>' . $from . '–' . $to . ' of ' . $total . '</span>';
        $html .= '</div>';

        // Page controls
        $html .= '<div class="pagination">';

        // First & Prev
        if ($current > 1) {
            $html .= '<a href="' . htmlspecialchars($pageUrl(1)) . '" class="page-link" title="First">«</a>';
            $html .= '<a href="' . htmlspecialchars($pageUrl($current - 1)) . '" class="page-link" title="Previous">‹</a>';
        } else {
            $html .= '<span class="page-link" style="opacity:.35;cursor:default">«</span>';
            $html .= '<span class="page-link" style="opacity:.35;cursor:default">‹</span>';
        }

        // Page numbers with ellipsis
        $range = 2;
        $pages = [];
        for ($i = 1; $i <= $last; $i++) {
            if ($i === 1 || $i === $last || ($i >= $current - $range && $i <= $current + $range)) {
                $pages[] = $i;
            }
        }
        $prev = null;
        foreach ($pages as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $html .= '<span class="page-link" style="cursor:default;pointer-events:none">…</span>';
            }
            $cls   = $p === $current ? 'page-link active' : 'page-link';
            $html .= '<a href="' . htmlspecialchars($pageUrl($p)) . '" class="' . $cls . '">' . $p . '</a>';
            $prev  = $p;
        }

        // Next & Last
        if ($current < $last) {
            $html .= '<a href="' . htmlspecialchars($pageUrl($current + 1)) . '" class="page-link" title="Next">›</a>';
            $html .= '<a href="' . htmlspecialchars($pageUrl($last)) . '" class="page-link" title="Last">»</a>';
        } else {
            $html .= '<span class="page-link" style="opacity:.35;cursor:default">›</span>';
            $html .= '<span class="page-link" style="opacity:.35;cursor:default">»</span>';
        }

        $html .= '</div>'; // .pagination
        $html .= '</div>'; // wrapper

        return $html;
    }
}
