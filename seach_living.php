<?php

require_once 'classes/init.php';

checking(2);

$search_value = trim($_POST['value'] ?? '');
$department_filter = trim($_POST['department'] ?? '');
$status_filter = trim($_POST['status'] ?? '');
$salary_min = (int) ($_POST['salary_min'] ?? 0);
$salary_max = (int) ($_POST['salary_max'] ?? 10000);
$offset = max(0, (int) ($_POST['offset'] ?? 0));
$limit = (int) ($_POST['limit'] ?? 10);
if ($limit <= 0) {
    $limit = 10;
}
if ($limit > 100) {
    $limit = 100;
}
$response_format = strtolower(trim($_POST['format'] ?? 'html'));

$select_sql = "SELECT u.*, d.name AS department_name, r.name AS role_name
               FROM users u
               LEFT JOIN departments d ON u.department_id = d.id
               LEFT JOIN roles r ON u.role_id = r.id";

$count_sql = "SELECT COUNT(*) AS total
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.id
              LEFT JOIN roles r ON u.role_id = r.id";

$where_parts = [];

if ($search_value !== '') {
    $normalized_search = preg_replace('/\s+/', ' ', $search_value);
    $safe_value = $db->escape($normalized_search);

    $conditions = [
        "CONCAT_WS(' ', u.first_name, u.last_name) LIKE '%{$safe_value}%'",
        "u.first_name LIKE '%{$safe_value}%'",
        "u.last_name LIKE '%{$safe_value}%'",
        "u.email LIKE '%{$safe_value}%'",
        "d.name LIKE '%{$safe_value}%'",
        "r.name LIKE '%{$safe_value}%'"
    ];

    $parts = array_values(array_filter(array_map('trim', explode(' ', $normalized_search))));
    if (count($parts) > 1) {
        $all_parts_groups = [];

        foreach ($parts as $part) {
            $safe_part = $db->escape($part);
            $all_parts_groups[] = "(u.first_name LIKE '%{$safe_part}%'
                                  OR u.last_name LIKE '%{$safe_part}%'
                                  OR CONCAT_WS(' ', u.first_name, u.last_name) LIKE '%{$safe_part}%'
                                  OR u.email LIKE '%{$safe_part}%')";
        }

        $conditions[] = '(' . implode(' AND ', $all_parts_groups) . ')';
    }

    $where_parts[] = '(' . implode(' OR ', $conditions) . ')';
}

if ($department_filter !== '') {
    $safe_department = $db->escape($department_filter);
    $where_parts[] = "LOWER(d.name) = LOWER('{$safe_department}')";
}

if ($status_filter !== '') {
    $safe_status = $db->escape($status_filter);
    $where_parts[] = "LOWER(u.status) = LOWER('{$safe_status}')";
}

if ($salary_min > $salary_max) {
    $temp = $salary_min;
    $salary_min = $salary_max;
    $salary_max = $temp;
}

$where_parts[] = "u.salary >= {$salary_min} AND u.salary <= {$salary_max}";

if (!empty($where_parts)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_parts);
    $select_sql .= $where_clause;
    $count_sql .= $where_clause;
}

$select_sql .= " ORDER BY u.id DESC LIMIT {$offset}, {$limit}";

$count_result = $db->query($count_sql);
$result = $db->query($select_sql);

if (!$count_result || !$result) {
    exit;
}

$count_row = $count_result->fetch_assoc();
$total_rows = (int) ($count_row['total'] ?? 0);
$loaded_rows = 0;

ob_start();

while ($row = $result->fetch_assoc()) {
    $loaded_rows++;
    $dept_name = strtolower(trim((string) ($row['department_name'] ?? '')));
    $dept_palette = [
        'engineering' => 'background:#dbeafe;color:#1d4ed8;',
        'design' => 'background:#ede9fe;color:#7c3aed;',
        'product' => 'background:#fef3c7;color:#92400e;',
        'hr' => 'background:#fce7f3;color:#9d174d;',
        'data' => 'background:#ccfbf1;color:#0f766e;'
    ];

    if (isset($dept_palette[$dept_name])) {
        $dept_style = $dept_palette[$dept_name];
    } else {
        $fallback_styles = [
            'background:#e0f2fe;color:#0369a1;',
            'background:#ecfccb;color:#3f6212;',
            'background:#fef3c7;color:#92400e;',
            'background:#fee2e2;color:#b91c1c;',
            'background:#ede9fe;color:#6d28d9;'
        ];
        $idx = abs(crc32($dept_name)) % count($fallback_styles);
        $dept_style = $fallback_styles[$idx];
    }

    $first_letter = substr((string) ($row['first_name'] ?? ''), 0, 1);
    $last_letter = substr((string) ($row['last_name'] ?? ''), 0, 1);
    $avatar_html = strtoupper(htmlspecialchars($first_letter . $last_letter));

    if (!empty($row['image_name'])) {
        $safe_image = htmlspecialchars($row['image_name']);
        $safe_name = htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $avatar_html = '<img src="assets/uploads/thumbs/' . $safe_image . '" alt="' . $safe_name . '" width="38" height="38" loading="lazy" onerror="this.onerror=null;this.src=\'assets/uploads/' . $safe_image . '\';">';
    }

    echo '<tr class="fade-in" data-id="' . (int) $row['id'] . '" data-department="' . htmlspecialchars(strtolower((string) ($row['department_name'] ?? ''))) . '" data-status="' . htmlspecialchars(strtolower((string) ($row['status'] ?? ''))) . '" data-salary="' . (int) ($row['salary'] ?? 0) . '">
        <td>
            <div class="user-cell">
                <div class="avatar av-blue">' . $avatar_html . '</div>
                <div>
                    <div class="user-name">' . htmlspecialchars($row['first_name'] ?? '') . ' ' . htmlspecialchars($row['last_name'] ?? '') . '</div>
                    <div class="user-email">' . htmlspecialchars($row['email'] ?? '') . '</div>
                </div>
            </div>
        </td>
        <td><span class="tag" style="' . $dept_style . '">' . htmlspecialchars($row['department_name'] ?? '') . '</span></td>
        <td style="color:var(--text-muted);font-size:13px">' . htmlspecialchars($row['role_name'] ?? '') . '</td>
        <td><span class="salary-val"><span style="color:var(--text-muted);font-size:14px"> $</span>' . htmlspecialchars(number_format((int) ($row['salary'] ?? 0), 0)) . '</span></td>
        <td><span class="tag ' . (htmlspecialchars($row['status'] ?? '') === 'Active' ? 'tag-active' : 'tag-inactive') . '">' . htmlspecialchars($row['status'] ?? '') . '</span></td>
        <td><span class="info-cell">' . htmlspecialchars($row['info'] ?? '') . '</span></td>
        <td>
            <div class="actions-cell">
                <a href="index.php?edit_id=' . (int) $row['id'] . '" class="btn btn-ghost btn-sm" data-action="edit" data-id="' . (int) $row['id'] . '">Edit</a>
                <a href="#" class="btn btn-danger-outline btn-sm" data-action="delete" data-id="' . (int) $row['id'] . '">Delete</a>
            </div>
        </td>
    </tr>';
}

$rows_html = ob_get_clean();

if ($response_format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html' => $rows_html,
        'total' => $total_rows,
        'loaded' => $loaded_rows,
        'offset' => $offset,
        'limit' => $limit
    ]);
    exit;
}

echo $rows_html;

?>