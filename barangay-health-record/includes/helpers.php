<?php
declare(strict_types=1);

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Destroys the current session and starts a brand-new one (new id, new cookie). */
function fresh_session(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    session_id(bin2hex(random_bytes(16)));
    session_start();
}

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Validates the CSRF token for a POST request; also blocks duplicate form submissions. */
function verify_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please go back, refresh the page and try again.');
    }
    // One-time form id prevents double submission (double-click / refresh re-post).
    $fid = $_POST['form_id'] ?? null;
    if ($fid !== null) {
        $used = $_SESSION['used_forms'] ?? [];
        if (in_array($fid, $used, true)) {
            flash('warning', 'This form was already submitted.');
            redirect($_POST['_back'] ?? 'index.php');
        }
        $used[] = $fid;
        $_SESSION['used_forms'] = array_slice($used, -50);
    }
}

function form_id_field(): string
{
    return '<input type="hidden" name="form_id" value="' . e(bin2hex(random_bytes(8))) . '">';
}

/* ---------- input ---------- */
function post(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $k, string $default = ''): string
{
    $v = $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function null_if_empty(string $v): ?string
{
    return $v === '' ? null : $v;
}

function fmt_date(?string $d): string
{
    return $d ? date('M d, Y', strtotime($d)) : '—';
}

function age_from(?string $birth): string
{
    if (!$birth) {
        return '—';
    }
    $b = new DateTime($birth);
    $diff = $b->diff(new DateTime('today'));
    if ($diff->y >= 1) {
        return $diff->y . ' yr' . ($diff->y > 1 ? 's' : '');
    }
    return $diff->m . ' mo';
}

/* ---------- pagination ---------- */
function paginate(int $total, int $page, int $per = PER_PAGE): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per, 'total' => $total];
}

function pager(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $q = $_GET;
    $out = '<nav><ul class="pagination pagination-sm mb-0 justify-content-end">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q['page'] = $i;
        $out .= '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="?'
              . e(http_build_query($q)) . '">' . $i . '</a></li>';
    }
    return $out . '</ul></nav>';
}

/* ---------- activity log (never stores detailed medical data) ---------- */
function log_activity(string $action, string $module, ?int $targetId = null, string $desc = ''): void
{
    try {
        $st = db()->prepare('INSERT INTO activity_logs (user_id, action, module, target_id, description, ip_address) VALUES (?,?,?,?,?,?)');
        $st->execute([$_SESSION['user']['id'] ?? null, $action, $module, $targetId, mb_substr($desc, 0, 250), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        error_log('BHRS log failure: ' . $e->getMessage());
    }
}

/* ---------- assets: use local vendor copy when present, otherwise CDN ---------- */
function vendor(string $localRel, string $cdn): string
{
    return is_file(ROOT_PATH . '/assets/vendor/' . $localRel) ? url('assets/vendor/' . $localRel) : $cdn;
}

/* ---------- select option helpers ---------- */
function options(array $list, ?string $selected): string
{
    $o = '';
    foreach ($list as $v => $label) {
        if (is_int($v)) {
            $v = $label;
        }
        $o .= '<option value="' . e((string)$v) . '"' . ((string)$v === (string)$selected ? ' selected' : '') . '>' . e((string)$label) . '</option>';
    }
    return $o;
}

function resident_options(?int $selected = null): string
{
    $rows = db()->query('SELECT id, resident_code, last_name, first_name FROM residents WHERE is_archived = 0 ORDER BY last_name, first_name')->fetchAll();
    $o = '<option value="">— Select resident —</option>';
    foreach ($rows as $r) {
        $o .= '<option value="' . (int)$r['id'] . '"' . ($selected === (int)$r['id'] ? ' selected' : '') . '>'
            . e($r['last_name'] . ', ' . $r['first_name'] . ' (' . $r['resident_code'] . ')') . '</option>';
    }
    return $o;
}
