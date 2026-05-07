<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$gameAssets = [
    'blackjack' => [
        'name' => 'Blackjack',
        'href' => 'pages/blackjack.php',
        'image' => 'assets/img/blackjack-logo.png',
        'code' => 'BJ',
    ],
    'mines' => [
        'name' => 'Mines',
        'href' => 'pages/mines.php',
        'image' => 'assets/img/mines-logo.png',
        'code' => 'MI',
    ],
    'plinko' => [
        'name' => 'Plinko',
        'href' => 'pages/plinko.php',
        'image' => 'assets/img/plinko-logo.png',
        'code' => 'PL',
    ],
    'dice' => [
        'name' => 'Dice',
        'href' => 'pages/dice.php',
        'image' => 'assets/img/dice-logo.png',
        'code' => 'DI',
    ],
];

function mask_bets_username($username) {
    $username = trim((string)$username);
    if ($username === '') {
        return 'Hidden';
    }

    return strlen($username) > 6 ? substr($username, 0, 6) . '...' : $username;
}

$bets = [];
$query = $conn->query("
    SELECT lb.bet_id, lb.user_id, lb.game_type, lb.game_name, lb.wager_amount, lb.payout_amount, lb.net_result, lb.created_at, u.username
    FROM latest_bets lb
    JOIN users u ON u.user_id = lb.user_id
    ORDER BY lb.created_at DESC, lb.bet_id DESC
    LIMIT 12
");

if ($query) {
    while ($row = $query->fetch_assoc()) {
        $gameType = strtolower(trim((string)$row['game_type']));
        $asset = $gameAssets[$gameType] ?? null;
        $gameName = $asset['name'] ?? $row['game_name'];
        $wager = (float)$row['wager_amount'];
        $payout = (float)$row['payout_amount'];

        $bets[] = [
            'bet_id' => (int)$row['bet_id'],
            'user_id' => (int)$row['user_id'],
            'username' => mask_bets_username($row['username']),
            'game_type' => $gameType,
            'game_name' => $gameName,
            'href' => $asset['href'] ?? ('pages/' . $gameType . '.php'),
            'image' => $asset['image'] ?? '',
            'code' => $asset['code'] ?? strtoupper(substr($gameName, 0, 2)),
            'wager_amount' => $wager,
            'payout_amount' => $payout,
            'net_result' => (float)$row['net_result'],
            'multiplier' => $wager > 0 ? $payout / $wager : 0,
            'is_mine' => $user_id !== null && (int)$row['user_id'] === $user_id,
            'created_at' => $row['created_at'],
        ];
    }
}

echo json_encode([
    'success' => true,
    'bets' => $bets,
]);
