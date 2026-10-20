<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_auth();
$search = trim((string) ($_GET['search'] ?? ''));
$conversations = get_conversations((int) $user['id'], $search);
$selectedId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT) ?: (int) ($conversations[0]['id'] ?? 0);

if ($selectedId && !user_can_access_conversation($selectedId, (int) $user['id'])) {
    $selectedId = 0;
}

$messages = $selectedId ? get_conversation_messages($selectedId, (int) $user['id']) : [];
$members = $selectedId ? get_conversation_members($selectedId) : [];
$titleMembers = array_values(array_filter($members, fn ($member) => (int) $member['user_id'] !== (int) $user['id']));
$conversationTitle = $titleMembers ? implode(', ', array_column($titleMembers, 'display_name')) : 'Conversation';
$wallet = ensure_wallet_account((int) $user['id']);
$giftCatalog = get_gift_catalog();
$giftIconMap = [];
foreach ($giftCatalog as $gift) {
    $giftIconMap[(int) $gift['id']] = $gift['icon'];
}
$conversationGifts = $selectedId ? get_conversation_gifts($selectedId, (int) $user['id']) : [];
$walletTransactions = get_wallet_transactions((int) $user['id']);
$walletTopups = get_wallet_topups((int) $user['id']);
$paystackChargePercent = (float) config_value('paystack.transaction_charge_percent', 3);
$paystackReady = paystack_secret_key() !== '';

$pageTitle = 'Messages';
$activePage = 'messages';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold mb-0"><i class="bi bi-chat-dots-fill me-2 brand-icon"></i>Messages</h1>
</div>

<div class="row g-3 message-layout">
  <div class="col-xl-3 col-lg-4">
    <div class="chat-sidebar">
      <form class="p-3 border-bottom" method="get">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search conversations">
        </div>
      </form>
      <div class="list-group list-group-flush">
        <?php foreach ($conversations as $conversation): ?>
          <a class="list-group-item d-flex align-items-center gap-3 py-3 <?= (int) $conversation['id'] === $selectedId ? 'active' : '' ?>" href="<?= e(url('messages.php?conversation_id=' . (int) $conversation['id'])) ?>">
            <div class="group-icon"><?= e(mb_substr($conversation['title'] ?: $conversation['member_names'], 0, 1)) ?></div>
            <div class="flex-grow-1 min-width-0">
              <div class="d-flex justify-content-between">
                <h2 class="h6 fw-bold mb-0 text-truncate"><?= e($conversation['title'] ?: $conversation['member_names']) ?></h2>
                <small class="text-muted"><?= e(time_ago($conversation['last_message_at'])) ?></small>
              </div>
              <p class="small text-muted mb-0 text-truncate"><?= e($conversation['last_message'] ?: 'No messages yet') ?></p>
            </div>
          </a>
        <?php endforeach; ?>
        <?php if (!$conversations): ?>
          <div class="p-3 text-muted small">No active message threads yet. Start a chat, then send a message to keep it here.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-9 col-lg-8">
    <div class="row g-3 align-items-stretch">
      <div class="col-xl-8">
        <div class="chat-window">
          <div class="p-3 border-bottom d-flex align-items-center gap-3">
            <div class="group-icon"><?= e(mb_substr($conversationTitle, 0, 1)) ?></div>
            <div class="flex-grow-1">
              <h2 class="h6 fw-bold mb-0"><?= e($conversationTitle) ?></h2>
              <span class="small text-muted"><?= count($members) ?> member<?= count($members) === 1 ? '' : 's' ?></span>
            </div>
          </div>

          <div class="chat-messages" data-chat-messages>
            <?php foreach ($messages as $message): ?>
              <?php
                $sent = (int) $message['sender_id'] === (int) $user['id'];
                $isGiftMessage = preg_match('/^\[gift:(\d+)\]\s*(.*)$/', (string) $message['body'], $giftMatch);
                $giftIconClass = $isGiftMessage ? ($giftIconMap[(int) $giftMatch[1]] ?? 'bi-gift-fill') : '';
                $messageBody = $isGiftMessage ? $giftMatch[2] : $message['body'];
              ?>
              <div class="chat-bubble <?= $sent ? 'sent' : 'received' ?> <?= $isGiftMessage ? 'gift-message-bubble' : '' ?>">
                <?php if (!$sent): ?><div class="small fw-semibold mb-1"><?= e($message['display_name']) ?></div><?php endif; ?>
                <?php if ($isGiftMessage): ?>
                  <div class="gift-message-line"><span><i class="bi <?= e($giftIconClass) ?>"></i></span><?= e($messageBody) ?></div>
                <?php else: ?>
                  <div><?= nl2br(e($messageBody)) ?></div>
                <?php endif; ?>
                <div class="small opacity-75 mt-1"><?= e(date('M j, g:ia', strtotime($message['created_at']))) ?></div>
              </div>
            <?php endforeach; ?>
            <?php if (!$selectedId): ?>
              <div class="text-center text-muted mt-5">Choose a conversation or message someone from Matches.</div>
            <?php endif; ?>
          </div>

          <?php if ($selectedId): ?>
            <form method="post" action="<?= e(url('actions/send_message.php')) ?>" class="border-top p-3">
              <?= csrf_field() ?>
              <input type="hidden" name="conversation_id" value="<?= (int) $selectedId ?>">
              <div class="d-flex gap-2">
                <input type="text" class="form-control rounded-pill" name="body" placeholder="Type a message" maxlength="2000" required>
                <button class="btn btn-primary rounded-circle" title="Send"><i class="bi bi-send"></i></button>
              </div>
            </form>
          <?php endif; ?>
        </div>

      </div>

      <div class="col-xl-4">
        <?php if ($selectedId): ?>
          <div class="card p-4 gift-card-panel side-gift-panel">
            <div class="mb-3">
              <span class="tool-eyebrow">Private gifting</span>
              <h2 class="h5 fw-bold mb-1"><i class="bi bi-gift me-2 brand-icon"></i>Send a secure gift</h2>
              <p class="text-muted small mb-0">Debits your cash wallet and credits the receiver instantly.</p>
            </div>
            <div class="wallet-balance-pill mb-3"><?= e(money_naira((float) $wallet['cash_balance'])) ?> cash</div>

            <div class="gift-grid side-gift-grid">
              <?php foreach ($giftCatalog as $gift): ?>
                <?php $canAffordGift = (float) $wallet['cash_balance'] >= (float) $gift['price']; ?>
                <form method="post" action="<?= e(url('actions/send_gift.php')) ?>" class="gift-option">
                  <?= csrf_field() ?>
                  <input type="hidden" name="conversation_id" value="<?= (int) $selectedId ?>">
                  <input type="hidden" name="gift_id" value="<?= (int) $gift['id'] ?>">
                  <?php if (count($titleMembers) === 1): ?>
                    <input type="hidden" name="recipient_id" value="<?= (int) $titleMembers[0]['user_id'] ?>">
                  <?php else: ?>
                    <select class="form-select form-select-sm mb-2" name="recipient_id" required>
                      <?php foreach ($titleMembers as $member): ?>
                        <option value="<?= (int) $member['user_id'] ?>"><?= e($member['display_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                  <button class="gift-button <?= $canAffordGift ? '' : 'disabled-gift' ?>" type="submit" <?= $canAffordGift ? '' : 'disabled' ?>>
                    <span class="gift-icon"><i class="bi <?= e($gift['icon']) ?>"></i></span>
                    <strong><?= e($gift['name']) ?></strong>
                    <small><?= e(money_naira((float) $gift['price'])) ?> · receiver gets <?= e(money_naira((float) $gift['redeemable_amount'])) ?></small>
                    <?php if (!$canAffordGift): ?><em>Insufficient balance</em><?php endif; ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </div>

            <?php if ($conversationGifts): ?>
              <div class="mt-4">
                <h3 class="h6 fw-bold mb-2">Recent gifts</h3>
                <div class="list-group list-group-flush">
                  <?php foreach ($conversationGifts as $gift): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                      <div><strong><i class="bi <?= e($gift['gift_icon']) ?> me-1"></i><?= e($gift['gift_name']) ?></strong><br><span class="small text-muted"><?= e($gift['sender_name']) ?> to <?= e($gift['recipient_name']) ?></span></div>
                      <span class="small text-muted"><?= e(money_naira((float) $gift['redeemable_amount'])) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card p-4 wallet-panel horizontal-wallet-panel message-wallet-below mt-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
      <div>
        <span class="tool-eyebrow">Naira wallet</span>
        <h2 class="h5 fw-bold mb-1"><i class="bi bi-wallet2 me-2 brand-icon"></i>Wallet center</h2>
        <p class="text-muted small mb-0">Manage balances, fund with Paystack, and request withdrawals.</p>
      </div>
      <span class="wallet-balance-pill"><?= e(money_naira((float) $wallet['cash_balance'])) ?> available cash</span>
    </div>

    <div class="wallet-center-grid">
      <section class="wallet-center-section wallet-center-balances">
        <div class="wallet-section-heading">
          <span><i class="bi bi-cash-stack"></i></span>
          <div>
            <h3>Wallet balance</h3>
            <p>Cash, gift balance, and pending withdrawals.</p>
          </div>
        </div>
        <div class="wallet-metrics horizontal-wallet-metrics">
          <div><span>Cash</span><strong><?= e(money_naira((float) $wallet['cash_balance'])) ?></strong></div>
          <div><span>Gifts</span><strong><?= e(money_naira((float) $wallet['gift_balance'])) ?></strong></div>
          <div><span>Pending</span><strong><?= e(money_naira((float) $wallet['pending_withdrawal'])) ?></strong></div>
        </div>
        <form method="post" action="<?= e(url('actions/convert_gifts.php')) ?>" class="wallet-inner-form">
          <?= csrf_field() ?>
          <input type="hidden" name="conversation_id" value="<?= (int) $selectedId ?>">
          <label class="form-label small fw-semibold">Convert gifts to cash</label>
          <div class="input-group">
            <span class="input-group-text">&#8358;</span>
            <input class="form-control" type="number" step="0.01" min="1" max="<?= e((string) $wallet['gift_balance']) ?>" name="amount" placeholder="Amount">
            <button class="btn btn-outline-primary">Convert</button>
          </div>
        </form>
      </section>

      <section class="wallet-center-section">
        <div class="wallet-section-heading">
          <span><i class="bi bi-credit-card"></i></span>
          <div>
            <h3>Fund wallet</h3>
            <p>Pay securely with Paystack.</p>
          </div>
        </div>
        <form method="post" action="<?= e(url('actions/fund_wallet.php')) ?>" class="mb-3">
          <?= csrf_field() ?>
          <?php if ($selectedId): ?><input type="hidden" name="conversation_id" value="<?= (int) $selectedId ?>"><?php endif; ?>
          <label class="form-label small fw-semibold">Amount to credit</label>
          <div class="input-group">
            <span class="input-group-text">&#8358;</span>
            <input class="form-control" type="number" step="0.01" min="100" name="amount" placeholder="Amount">
            <button class="btn btn-primary" <?= $paystackReady ? '' : 'disabled' ?>>Paystack</button>
          </div>
          <div class="text-muted small mt-1">
            <?= e(number_format($paystackChargePercent, 1)) ?>% Paystack charge is added at checkout.
            <?php if (!$paystackReady): ?> Add PAYSTACK_SECRET_KEY to enable live funding.<?php endif; ?>
          </div>
        </form>
      </section>

      <section class="wallet-center-section">
        <div class="wallet-section-heading">
          <span><i class="bi bi-bank"></i></span>
          <div>
            <h3>Request withdrawal</h3>
            <p>Send available cash to your payout account.</p>
          </div>
        </div>
        <form method="post" action="<?= e(url('actions/request_withdrawal.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="conversation_id" value="<?= (int) $selectedId ?>">
          <div class="row g-2">
            <div class="col-12"><input class="form-control" type="number" step="0.01" min="1" max="<?= e((string) $wallet['cash_balance']) ?>" name="amount" placeholder="Amount"></div>
            <div class="col-12"><input class="form-control" name="payout_method" maxlength="80" placeholder="Bank, Opay"></div>
            <div class="col-12"><input class="form-control" name="payout_details" maxlength="255" placeholder="Payout details"></div>
            <div class="col-12"><button class="btn btn-primary rounded-pill w-100">Submit withdrawal</button></div>
          </div>
        </form>
      </section>
    </div>

    <?php if ($walletTransactions || $walletTopups): ?>
      <div class="wallet-ledger-strip mt-3">
        <?php foreach ($walletTransactions as $transaction): ?>
          <div class="wallet-ledger-row">
            <span><?= e($transaction['description']) ?></span>
            <strong><?= (float) $transaction['amount'] >= 0 ? '+' : '' ?><?= e(money_naira((float) $transaction['amount'])) ?></strong>
          </div>
        <?php endforeach; ?>
        <?php foreach ($walletTopups as $topup): ?>
          <div class="wallet-ledger-row">
            <span>Paystack top-up <?= e($topup['status']) ?></span>
            <strong><?= e(money_naira((float) $topup['amount'])) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
