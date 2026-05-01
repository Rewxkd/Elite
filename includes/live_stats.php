<?php
$liveStatsUserId = isset($user_id) ? (int)$user_id : null;
$liveStatsBets = [];
$liveStatsGameAssets = [
    'blackjack' => [
        'name' => 'Blackjack',
        'image' => 'assets/img/blackjack-logo.png',
    ],
    'mines' => [
        'name' => 'Mines',
        'image' => 'assets/img/mines-logo.png',
    ],
];

if (isset($conn)) {
    $liveStatsQuery = $conn->query("
        SELECT lb.user_id, lb.game_type, lb.game_name, lb.wager_amount, lb.payout_amount, lb.net_result, lb.created_at, u.username
        FROM latest_bets lb
        JOIN users u ON u.user_id = lb.user_id
        ORDER BY lb.created_at DESC
        LIMIT 12
    ");

    if ($liveStatsQuery) {
        while ($row = $liveStatsQuery->fetch_assoc()) {
            $liveStatsBets[] = $row;
        }
    }
}

if (!function_exists('live_stats_mask_username')) {
    function live_stats_mask_username($username) {
        $username = trim((string)$username);
        if ($username === '') {
            return 'Hidden';
        }

        return strlen($username) > 6 ? substr($username, 0, 6) . '...' : $username;
    }
}

$liveStatsScript = function_exists('elite_url') ? elite_url('assets/js/live_stats.js') : 'assets/js/live_stats.js';
?>
<section class="bets-stats-section" aria-labelledby="betsStatsTitle">
    <div class="bets-stats-header">
        <div>
            <h2 class="games-kicker" id="betsStatsTitle">Live activity</h2>
        </div>
        <div class="bets-filter-tabs" role="tablist" aria-label="Bet filters">
            <button class="bets-filter-tab active" type="button" data-bets-filter="live" role="tab" aria-selected="true">Live Bets</button>
            <button class="bets-filter-tab" type="button" data-bets-filter="mine" role="tab" aria-selected="false">My Bets</button>
        </div>
    </div>

    <div class="bets-stats-table-wrap">
        <?php if (empty($liveStatsBets)): ?>
            <div class="latest-bets-empty">No bets yet.</div>
        <?php else: ?>
            <table class="bets-stats-table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>User</th>
                        <th>Bet Amount</th>
                        <th>Multiplier</th>
                        <th>Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liveStatsBets as $bet): ?>
                        <?php
                            $gameType = strtolower(trim((string)$bet['game_type']));
                            $gameAsset = $liveStatsGameAssets[$gameType] ?? null;
                            $gameName = $bet['game_name'];
                            $gameImage = $gameAsset['image'] ?? '';
                            $gameImageUrl = $gameImage && function_exists('elite_url') ? elite_url($gameImage) : htmlspecialchars($gameImage, ENT_QUOTES, 'UTF-8');
                            $amount = (float)$bet['wager_amount'];
                            $payout = (float)$bet['payout_amount'];
                            $multiplier = $amount > 0 ? $payout / $amount : 0;
                        ?>
                        <tr data-bet-row data-is-mine="<?php echo $liveStatsUserId && (int)$bet['user_id'] === $liveStatsUserId ? 'true' : 'false'; ?>">
                            <td data-label="Game">
                                <span class="bets-game-cell">
                                    <span class="bets-game-mark <?php echo $gameImage ? 'has-logo' : ''; ?>">
                                        <?php if ($gameImage): ?>
                                            <img src="<?php echo $gameImageUrl; ?>" alt="">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(strtoupper(substr($gameName, 0, 2)), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </td>
                            <td data-label="User"><?php echo htmlspecialchars(live_stats_mask_username($bet['username']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Bet Amount">$<?php echo number_format($amount, 2); ?></td>
                            <td data-label="Multiplier"><?php echo number_format($multiplier, 2); ?>x</td>
                            <td data-label="Payout" class="<?php echo $payout > 0 ? 'is-positive' : 'is-muted'; ?>">$<?php echo number_format($payout, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bets-empty-row" data-bets-empty="mine" hidden>
                        <td colspan="5">No bets from you yet.</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<script src="<?php echo $liveStatsScript; ?>"></script>
