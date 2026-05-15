<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');
ini_set('serialize_precision', '-1');

const VIP_HOURLY_AMOUNT = 1000.00;
const VIP_CLAIM_COOLDOWN = 3600;
const VIP_RAKEBACK_RATE = 0.08;

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

function profile_respond($payload) {
    echo json_encode($payload);
    exit;
}

function profile_table_has_column($conn, $table, $column) {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");

    return $result && $result->num_rows > 0;
}

function profile_ranks($total_wagered) {
    $ranks = [
        ['name' => 'Unranked', 'threshold' => 0],
        ['name' => 'Bronze I', 'threshold' => 10000],
        ['name' => 'Bronze II', 'threshold' => 25000],
        ['name' => 'Bronze III', 'threshold' => 40000],
        ['name' => 'Silver I', 'threshold' => 50000],
        ['name' => 'Silver II', 'threshold' => 70000],
        ['name' => 'Silver III', 'threshold' => 90000],
        ['name' => 'Gold I', 'threshold' => 100000],
        ['name' => 'Gold II', 'threshold' => 150000],
        ['name' => 'Gold III', 'threshold' => 200000],
        ['name' => 'Platinum I', 'threshold' => 250000],
        ['name' => 'Platinum II', 'threshold' => 500000],
        ['name' => 'Platinum III', 'threshold' => 1000000],
        ['name' => 'Diamond I', 'threshold' => 25000000],
        ['name' => 'Diamond II', 'threshold' => 50000000],
        ['name' => 'Diamond III', 'threshold' => 100000000],
        ['name' => 'Obsidian', 'threshold' => 1000000000],
    ];

    $rankIndex = 0;
    $wagered = max(0, (float)$total_wagered);

    foreach ($ranks as $index => $rank) {
        if ($wagered >= $rank['threshold']) {
            $rankIndex = $index;
        }
    }

    $current = $ranks[$rankIndex];
    $next = $ranks[min($rankIndex + 1, count($ranks) - 1)];
    $isMax = $current['name'] === $next['name'];
    $span = max(1, $next['threshold'] - $current['threshold']);
    $percent = $isMax ? 100 : min(100, max(0, round((($wagered - $current['threshold']) / $span) * 100)));

    return [
        'current' => $current['name'],
        'next' => $isMax ? 'Max level' : $next['name'],
        'target' => $next['threshold'],
        'remaining' => $isMax ? 0 : max(0, $next['threshold'] - $wagered),
        'percent' => $percent,
    ];
}

function profile_claimed_amount($conn, $user_id, $claim_type) {
    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM vip_claims WHERE user_id = ? AND claim_type = ?');
    $stmt->bind_param('is', $user_id, $claim_type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (float)($row['total'] ?? 0);
}

function profile_available_rakeback($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN net_result < 0 THEN ABS(net_result) ELSE 0 END), 0) AS losses
        FROM latest_bets
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $losses = (float)($row['losses'] ?? 0);
    $claimed = profile_claimed_amount($conn, $user_id, 'rakeback');

    return round(max(0, ($losses * VIP_RAKEBACK_RATE) - $claimed), 2);
}

function profile_summary($conn, $user_id) {
    $hasCreatedAt = profile_table_has_column($conn, 'users', 'created_at');
    $userSql = $hasCreatedAt
        ? 'SELECT username, email, created_at FROM users WHERE user_id = ? LIMIT 1'
        : 'SELECT username, email FROM users WHERE user_id = ? LIMIT 1';

    $stmt = $conn->prepare($userSql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $memberSince = $user['created_at'] ?? '';

    if ($memberSince === '') {
        $stmt = $conn->prepare('SELECT MIN(created_at) AS first_seen FROM latest_bets WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $firstSeen = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $memberSince = $firstSeen['first_seen'] ?? date('Y-m-d H:i:s');
    }

    $stmt = $conn->prepare('SELECT balance, total_wagered FROM wallets WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $balance = (float)($wallet['balance'] ?? 0);
    $totalWagered = (float)($wallet['total_wagered'] ?? 0);

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_bets,
               COALESCE(SUM(wager_amount), 0) AS total_bet_wagered,
               COALESCE(SUM(net_result), 0) AS net_result,
               COALESCE(SUM(CASE WHEN net_result < 0 THEN ABS(net_result) ELSE 0 END), 0) AS losses
        FROM latest_bets
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $bets = [];
    $stmt = $conn->prepare("
        SELECT bet_id, game_type, game_name, wager_amount, payout_amount, net_result, created_at
        FROM latest_bets
        WHERE user_id = ?
        ORDER BY created_at DESC, bet_id DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $bets[] = [
            'bet_id' => (int)$row['bet_id'],
            'game_type' => strtolower((string)$row['game_type']),
            'game_name' => $row['game_name'],
            'wager_amount' => (float)$row['wager_amount'],
            'payout_amount' => (float)$row['payout_amount'],
            'net_result' => (float)$row['net_result'],
            'created_at' => $row['created_at'],
        ];
    }

    $stmt->close();

    $stmt = $conn->prepare("
        SELECT amount, created_at, TIMESTAMPDIFF(SECOND, created_at, CURRENT_TIMESTAMP) AS elapsed_seconds
        FROM vip_claims
        WHERE user_id = ? AND claim_type = 'hourly'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $lastHourly = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $hourlyRemaining = 0;

    if ($lastHourly && !empty($lastHourly['created_at'])) {
        $elapsed = max(0, (int)($lastHourly['elapsed_seconds'] ?? 0));
        $hourlyRemaining = max(0, VIP_CLAIM_COOLDOWN - $elapsed);
    }

    $ranks = profile_ranks($totalWagered);
    $availableRakeback = profile_available_rakeback($conn, $user_id);
    $memberTimestamp = strtotime($memberSince) ?: time();

    return [
        'user' => [
            'username' => $user['username'] ?? 'Player',
            'email' => $user['email'] ?? '',
            'member_since' => date('M j, Y', $memberTimestamp),
        ],
        'wallet' => [
            'balance' => $balance,
            'total_wagered' => $totalWagered,
        ],
        'ranks' => $ranks,
        'stats' => [
            'total_bets' => (int)($stats['total_bets'] ?? 0),
            'total_bet_wagered' => (float)($stats['total_bet_wagered'] ?? 0),
            'net_result' => (float)($stats['net_result'] ?? 0),
            'losses' => (float)($stats['losses'] ?? 0),
        ],
        'vip' => [
            'hourly_amount' => VIP_HOURLY_AMOUNT,
            'hourly_remaining_seconds' => $hourlyRemaining,
            'hourly_can_claim' => $hourlyRemaining <= 0,
            'rakeback_rate' => round(VIP_RAKEBACK_RATE, 2),
            'rakeback_available' => $availableRakeback,
            'rakeback_claimed' => profile_claimed_amount($conn, $user_id, 'rakeback'),
        ],
        'bets' => $bets,
    ];
}

function profile_add_wallet_amount($conn, $user_id, $amount) {
    $stmt = $conn->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?');
    $stmt->bind_param('di', $amount, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) {
        $zero = 0.00;
        $stmt = $conn->prepare('INSERT INTO wallets (user_id, balance, total_wagered) VALUES (?, ?, ?)');
        $stmt->bind_param('idd', $user_id, $amount, $zero);
        $stmt->execute();
        $stmt->close();
    }
}

if (!$user_id) {
    profile_respond(['success' => false, 'message' => 'Please log in first.']);
}

$action = $_POST['action'] ?? 'summary';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_hourly') {
    $summary = profile_summary($conn, $user_id);

    if (!$summary['vip']['hourly_can_claim']) {
        profile_respond([
            'success' => false,
            'message' => 'Hourly bonus is still cooling down.',
            'summary' => $summary,
        ]);
    }

    $conn->begin_transaction();

    try {
        profile_add_wallet_amount($conn, $user_id, VIP_HOURLY_AMOUNT);

        $claimType = 'hourly';
        $amount = VIP_HOURLY_AMOUNT;
        $stmt = $conn->prepare('INSERT INTO vip_claims (user_id, claim_type, amount) VALUES (?, ?, ?)');
        $stmt->bind_param('isd', $user_id, $claimType, $amount);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        profile_respond(['success' => false, 'message' => 'Could not claim the hourly bonus.']);
    }

    profile_respond([
        'success' => true,
        'message' => 'Claimed $1,000.00 VIP bonus.',
        'summary' => profile_summary($conn, $user_id),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_rakeback') {
    $available = profile_available_rakeback($conn, $user_id);

    if ($available < 0.01) {
        profile_respond([
            'success' => false,
            'message' => 'No rakeback is available yet.',
            'summary' => profile_summary($conn, $user_id),
        ]);
    }

    $conn->begin_transaction();

    try {
        profile_add_wallet_amount($conn, $user_id, $available);

        $claimType = 'rakeback';
        $stmt = $conn->prepare('INSERT INTO vip_claims (user_id, claim_type, amount) VALUES (?, ?, ?)');
        $stmt->bind_param('isd', $user_id, $claimType, $available);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        profile_respond(['success' => false, 'message' => 'Could not claim rakeback.']);
    }

    profile_respond([
        'success' => true,
        'message' => 'Rakeback added to your balance.',
        'summary' => profile_summary($conn, $user_id),
    ]);
}

profile_respond([
    'success' => true,
    'summary' => profile_summary($conn, $user_id),
]);
