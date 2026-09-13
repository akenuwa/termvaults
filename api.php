<?php
/**
 * ==============================================================================
 * SecOps TermVault - Backend REST API
 * Lead Architect: 0745
 * Organization: KoiTech
 * Operational Codename: 0745
 * Description: Core REST API for terminal command retrieval & security terms
 * ==============================================================================
 */

declare(strict_types=1);

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$dataFile = __DIR__ . '/data/commands.json';

// Helper: Response Formatter
function send_json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Helper: Load Commands
function load_commands(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// Helper: Save Commands (Atomic with file locking)
function save_commands(string $path, array $commands): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $tmpFile = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($commands, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if (file_put_contents($tmpFile, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmpFile, $path);
}

// Helper: Sanitize Strings
function sanitize_input(?string $input): string {
    if ($input === null) return '';
    return trim($input);
}

// Helper: Parse Request Body (JSON or POST)
function get_request_data(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $parsed = json_decode($raw, true);
        return is_array($parsed) ? $parsed : [];
    }
    return $_POST;
}

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($action) {
        case 'list':
            $commands = load_commands($dataFile);
            $q = strtolower(sanitize_input($_GET['q'] ?? ''));
            $category = sanitize_input($_GET['category'] ?? '');
            $tag = strtolower(sanitize_input($_GET['tag'] ?? ''));
            $favoriteOnly = isset($_GET['favorite']) && ($_GET['favorite'] === '1' || $_GET['favorite'] === 'true');
            $sort = sanitize_input($_GET['sort'] ?? 'most_used');

            // Filtering
            $filtered = array_filter($commands, function(array $item) use ($q, $category, $tag, $favoriteOnly): bool {
                if ($favoriteOnly && empty($item['is_favorite'])) {
                    return false;
                }

                if ($category !== '' && strtolower($category) !== 'all' && strcasecmp($item['category'] ?? '', $category) !== 0) {
                    return false;
                }

                if ($tag !== '') {
                    $itemTags = array_map('strtolower', $item['tags'] ?? []);
                    if (!in_array($tag, $itemTags, true)) {
                        return false;
                    }
                }

                if ($q !== '') {
                    $searchPool = strtolower(
                        ($item['title'] ?? '') . ' ' .
                        ($item['command'] ?? '') . ' ' .
                        ($item['description'] ?? '') . ' ' .
                        ($item['category'] ?? '') . ' ' .
                        implode(' ', $item['tags'] ?? [])
                    );
                    // Match all search tokens
                    $keywords = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($keywords as $kw) {
                        if (strpos($searchPool, $kw) === false) {
                            return false;
                        }
                    }
                }

                return true;
            });

            // Reindex
            $filtered = array_values($filtered);

            // Sorting
            usort($filtered, function(array $a, array $b) use ($sort): int {
                switch ($sort) {
                    case 'alpha':
                        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
                    case 'recent':
                        return strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? '');
                    case 'created':
                        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
                    case 'most_used':
                    default:
                        $countA = (int)($a['copy_count'] ?? 0);
                        $countB = (int)($b['copy_count'] ?? 0);
                        if ($countA === $countB) {
                            // Secondary sort: alphabetical
                            return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
                        }
                        return $countB <=> $countA;
                }
            });

            send_json([
                'success' => true,
                'count' => count($filtered),
                'total' => count($commands),
                'data' => $filtered
            ]);
            break;

        case 'categories':
            $commands = load_commands($dataFile);
            $categories = [];
            foreach ($commands as $cmd) {
                $cat = trim($cmd['category'] ?? 'Uncategorized');
                if ($cat === '') $cat = 'Uncategorized';
                $categories[$cat] = ($categories[$cat] ?? 0) + 1;
            }
            ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);
            send_json(['success' => true, 'data' => $categories]);
            break;

        case 'tags':
            $commands = load_commands($dataFile);
            $tags = [];
            foreach ($commands as $cmd) {
                if (isset($cmd['tags']) && is_array($cmd['tags'])) {
                    foreach ($cmd['tags'] as $t) {
                        $cleanTag = strtolower(trim($t));
                        if ($cleanTag !== '') {
                            $tags[$cleanTag] = ($tags[$cleanTag] ?? 0) + 1;
                        }
                    }
                }
            }
            arsort($tags);
            send_json(['success' => true, 'data' => $tags]);
            break;

        case 'stats':
            $commands = load_commands($dataFile);
            $totalCommands = count($commands);
            $totalFavorites = count(array_filter($commands, fn($c) => !empty($c['is_favorite'])));
            $totalCopies = array_sum(array_map(fn($c) => (int)($c['copy_count'] ?? 0), $commands));
            
            $catMap = [];
            $tagMap = [];
            foreach ($commands as $c) {
                $cat = trim($c['category'] ?? 'Uncategorized');
                $catMap[$cat] = true;
                foreach ($c['tags'] ?? [] as $tg) {
                    $clean = strtolower(trim($tg));
                    if ($clean) $tagMap[$clean] = ($tagMap[$clean] ?? 0) + 1;
                }
            }

            // Top copied
            $sortedByCopy = $commands;
            usort($sortedByCopy, fn($a, $b) => ($b['copy_count'] ?? 0) <=> ($a['copy_count'] ?? 0));
            $top5 = array_slice($sortedByCopy, 0, 5);

            send_json([
                'success' => true,
                'data' => [
                    'system' => [
                        'architect' => '0745',
                        'organization' => 'KoiTech',
                        'codename' => '0745',
                        'version' => '2.0',
                        'status' => 'operational'
                    ],
                    'total_commands' => $totalCommands,
                    'total_favorites' => $totalFavorites,
                    'total_categories' => count($catMap),
                    'total_tags' => count($tagMap),
                    'total_copies' => $totalCopies,
                    'top_used' => array_map(fn($i) => [
                        'id' => $i['id'],
                        'title' => $i['title'],
                        'command' => $i['command'],
                        'copy_count' => $i['copy_count'] ?? 0
                    ], $top5)
                ]
            ]);
            break;

        case 'create':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            $input = get_request_data();
            $title = sanitize_input($input['title'] ?? '');
            $commandText = sanitize_input($input['command'] ?? '');
            
            if ($title === '' || $commandText === '') {
                send_json(['success' => false, 'error' => 'Title and Command are required.'], 400);
            }

            $category = sanitize_input($input['category'] ?? 'CLI & Workflows');
            if ($category === '') $category = 'CLI & Workflows';

            // Process tags
            $rawTags = $input['tags'] ?? [];
            if (is_string($rawTags)) {
                $rawTags = explode(',', $rawTags);
            }
            $tags = [];
            foreach ($rawTags as $t) {
                $clean = trim((string)$t);
                $clean = ltrim($clean, '#');
                if ($clean !== '') {
                    $tags[] = $clean;
                }
            }
            $tags = array_values(array_unique($tags));

            $risk = strtolower(sanitize_input($input['risk_level'] ?? 'safe'));
            if (!in_array($risk, ['safe', 'notice', 'elevated', 'dangerous'], true)) {
                $risk = 'safe';
            }

            $now = date('Y-m-d\TH:i:s\Z');
            $newItem = [
                'id' => 'cmd_' . bin2hex(random_bytes(6)),
                'title' => $title,
                'command' => $commandText,
                'category' => $category,
                'tags' => $tags,
                'description' => sanitize_input($input['description'] ?? ''),
                'risk_level' => $risk,
                'is_favorite' => !empty($input['is_favorite']),
                'copy_count' => 0,
                'created_at' => $now,
                'updated_at' => $now
            ];

            $commands = load_commands($dataFile);
            // Prepend new item so it appears at top of lists
            array_unshift($commands, $newItem);
            if (!save_commands($dataFile, $commands)) {
                send_json(['success' => false, 'error' => 'Failed to save data to disk.'], 500);
            }

            send_json(['success' => true, 'message' => 'Command saved successfully.', 'data' => $newItem], 201);
            break;

        case 'update':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            $input = get_request_data();
            $id = sanitize_input($input['id'] ?? '');
            if ($id === '') {
                send_json(['success' => false, 'error' => 'Command ID is required for update.'], 400);
            }

            $commands = load_commands($dataFile);
            $foundIndex = -1;
            foreach ($commands as $idx => $item) {
                if ($item['id'] === $id) {
                    $foundIndex = $idx;
                    break;
                }
            }

            if ($foundIndex === -1) {
                send_json(['success' => false, 'error' => 'Item not found.'], 404);
            }

            $existing = $commands[$foundIndex];

            // Extract tags
            $rawTags = $input['tags'] ?? $existing['tags'];
            if (is_string($rawTags)) {
                $rawTags = explode(',', $rawTags);
            }
            $tags = [];
            foreach ($rawTags as $t) {
                $clean = trim((string)$t);
                $clean = ltrim($clean, '#');
                if ($clean !== '') {
                    $tags[] = $clean;
                }
            }
            $tags = array_values(array_unique($tags));

            $risk = strtolower(sanitize_input($input['risk_level'] ?? $existing['risk_level'] ?? 'safe'));
            if (!in_array($risk, ['safe', 'notice', 'elevated', 'dangerous'], true)) {
                $risk = $existing['risk_level'] ?? 'safe';
            }

            $updatedItem = [
                'id' => $existing['id'],
                'title' => sanitize_input($input['title'] ?? $existing['title']),
                'command' => sanitize_input($input['command'] ?? $existing['command']),
                'category' => sanitize_input($input['category'] ?? $existing['category']),
                'tags' => $tags,
                'description' => sanitize_input($input['description'] ?? $existing['description'] ?? ''),
                'risk_level' => $risk,
                'is_favorite' => isset($input['is_favorite']) ? !empty($input['is_favorite']) : ($existing['is_favorite'] ?? false),
                'copy_count' => (int)($existing['copy_count'] ?? 0),
                'created_at' => $existing['created_at'] ?? date('Y-m-d\TH:i:s\Z'),
                'updated_at' => date('Y-m-d\TH:i:s\Z')
            ];

            $commands[$foundIndex] = $updatedItem;
            if (!save_commands($dataFile, $commands)) {
                send_json(['success' => false, 'error' => 'Failed to save updated command.'], 500);
            }

            send_json(['success' => true, 'message' => 'Command updated successfully.', 'data' => $updatedItem]);
            break;

        case 'delete':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            $input = get_request_data();
            $id = sanitize_input($input['id'] ?? '');
            if ($id === '') {
                send_json(['success' => false, 'error' => 'Command ID is required for deletion.'], 400);
            }

            $commands = load_commands($dataFile);
            $initialCount = count($commands);
            $commands = array_values(array_filter($commands, fn($i) => $i['id'] !== $id));

            if (count($commands) === $initialCount) {
                send_json(['success' => false, 'error' => 'Command not found.'], 404);
            }

            if (!save_commands($dataFile, $commands)) {
                send_json(['success' => false, 'error' => 'Failed to remove command from storage.'], 500);
            }

            send_json(['success' => true, 'message' => 'Command deleted.']);
            break;

        case 'toggle_favorite':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            $input = get_request_data();
            $id = sanitize_input($input['id'] ?? '');
            if ($id === '') {
                send_json(['success' => false, 'error' => 'Command ID is required.'], 400);
            }

            $commands = load_commands($dataFile);
            $targetIndex = -1;
            foreach ($commands as $idx => $c) {
                if ($c['id'] === $id) {
                    $targetIndex = $idx;
                    break;
                }
            }

            if ($targetIndex === -1) {
                send_json(['success' => false, 'error' => 'Item not found.'], 404);
            }

            $commands[$targetIndex]['is_favorite'] = empty($commands[$targetIndex]['is_favorite']);
            $commands[$targetIndex]['updated_at'] = date('Y-m-d\TH:i:s\Z');

            save_commands($dataFile, $commands);
            send_json([
                'success' => true,
                'is_favorite' => $commands[$targetIndex]['is_favorite']
            ]);
            break;

        case 'increment_copy':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            $input = get_request_data();
            $id = sanitize_input($input['id'] ?? '');
            if ($id === '') {
                send_json(['success' => false, 'error' => 'Command ID is required.'], 400);
            }

            $commands = load_commands($dataFile);
            $targetIndex = -1;
            foreach ($commands as $idx => $c) {
                if ($c['id'] === $id) {
                    $targetIndex = $idx;
                    break;
                }
            }

            if ($targetIndex === -1) {
                send_json(['success' => false, 'error' => 'Item not found.'], 404);
            }

            $commands[$targetIndex]['copy_count'] = ((int)($commands[$targetIndex]['copy_count'] ?? 0)) + 1;
            save_commands($dataFile, $commands);

            send_json([
                'success' => true,
                'copy_count' => $commands[$targetIndex]['copy_count']
            ]);
            break;

        case 'export':
            $commands = load_commands($dataFile);
            $filename = 'termvault_backup_' . date('Y-m-d_His') . '.json';
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo json_encode($commands, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;

        case 'import':
            if ($method !== 'POST') {
                send_json(['success' => false, 'error' => 'Method Not Allowed'], 405);
            }
            
            $jsonString = '';
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $jsonString = file_get_contents($_FILES['file']['tmp_name']);
            } else {
                $input = get_request_data();
                $jsonString = $input['json_data'] ?? '';
            }

            if (trim($jsonString) === '') {
                send_json(['success' => false, 'error' => 'No import data provided.'], 400);
            }

            $imported = json_decode($jsonString, true);
            if (!is_array($imported)) {
                send_json(['success' => false, 'error' => 'Invalid JSON structure.'], 400);
            }

            $mode = sanitize_input($_GET['mode'] ?? 'merge');
            $existing = load_commands($dataFile);

            $validItems = [];
            foreach ($imported as $item) {
                if (!is_array($item) || empty($item['title']) || empty($item['command'])) {
                    continue;
                }
                $validItems[] = [
                    'id' => !empty($item['id']) ? (string)$item['id'] : ('cmd_' . bin2hex(random_bytes(6))),
                    'title' => sanitize_input((string)$item['title']),
                    'command' => sanitize_input((string)$item['command']),
                    'category' => sanitize_input((string)($item['category'] ?? 'CLI & Workflows')),
                    'tags' => is_array($item['tags'] ?? null) ? array_values(array_unique(array_map('strval', $item['tags']))) : [],
                    'description' => sanitize_input((string)($item['description'] ?? '')),
                    'risk_level' => in_array($item['risk_level'] ?? '', ['safe', 'notice', 'elevated', 'dangerous']) ? $item['risk_level'] : 'safe',
                    'is_favorite' => !empty($item['is_favorite']),
                    'copy_count' => (int)($item['copy_count'] ?? 0),
                    'created_at' => (string)($item['created_at'] ?? date('Y-m-d\TH:i:s\Z')),
                    'updated_at' => date('Y-m-d\TH:i:s\Z')
                ];
            }

            if ($mode === 'replace') {
                $final = $validItems;
            } else {
                // Merge: update existing by ID, append new
                $byId = [];
                foreach ($existing as $c) {
                    $byId[$c['id']] = $c;
                }
                foreach ($validItems as $c) {
                    $byId[$c['id']] = $c;
                }
                $final = array_values($byId);
            }

            if (!save_commands($dataFile, $final)) {
                send_json(['success' => false, 'error' => 'Failed to save imported items.'], 500);
            }

            send_json([
                'success' => true,
                'message' => 'Successfully imported ' . count($validItems) . ' entries.',
                'total_now' => count($final)
            ]);
            break;

        default:
            send_json(['success' => false, 'error' => 'Invalid action requested.'], 404);
            break;
    }
} catch (\Throwable $e) {
    send_json([
        'success' => false,
        'error' => 'Server Error: ' . $e->getMessage()
    ], 500);
}
