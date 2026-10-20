<?php
$likesCount = (int) ($post['likes_count'] ?? 0);
$commentsCount = (int) ($post['comments_count'] ?? 0);
$comments = get_post_comments((int) $post['id'], 5);
$canEarn = $likesCount >= 1000000;
$commentsModalId = 'postCommentsModal' . (int) $post['id'];
?>
<article class="card mb-4 p-4 post-card" data-post-id="<?= (int) $post['id'] ?>">
  <div class="d-flex align-items-center gap-3">
    <?= profile_avatar($post, 'profile-avatar', ['alt' => $post['display_name']]) ?>
    <div class="flex-grow-1">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h3 class="h6 fw-bold mb-0"><?= e($post['display_name']) ?></h3>
        <?php if ($canEarn): ?>
          <span class="earning-sticker"><i class="bi bi-stars"></i>Future earning eligible</span>
        <?php endif; ?>
      </div>
      <span class="text-muted small"><?= e(time_ago($post['created_at'])) ?> ago | <i class="bi bi-geo-alt"></i> <?= e($post['city']) ?></span>
    </div>
  </div>

  <p class="mt-3"><?= nl2br(e($post['body'])) ?></p>

  <?php if (!empty($post['media_url'])): ?>
    <div class="post-media-frame mt-3">
      <?php if (($post['media_type'] ?? '') === 'video'): ?>
        <video src="<?= e($post['media_url']) ?>" controls preload="metadata"></video>
      <?php else: ?>
        <img src="<?= e($post['media_url']) ?>" alt="Post media">
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="post-action-row">
    <form method="post" action="<?= e(url('actions/post_like.php')) ?>" class="m-0">
      <?= csrf_field() ?>
      <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
      <button class="post-pill-action" title="Like post">
        <i class="bi bi-heart-fill text-danger"></i>
        <span><?= e(compact_count($likesCount)) ?></span>
        <small><?= $likesCount === 1 ? 'like' : 'likes' ?></small>
      </button>
    </form>
    <button class="post-pill-action passive" type="button" data-bs-toggle="modal" data-bs-target="#<?= e($commentsModalId) ?>" title="View comments">
      <i class="bi bi-chat-dots"></i>
      <span><?= e(compact_count($commentsCount)) ?></span>
      <small><?= $commentsCount === 1 ? 'comment' : 'comments' ?></small>
    </button>
  </div>
</article>

<div class="modal fade post-comments-modal" id="<?= e($commentsModalId) ?>" tabindex="-1" aria-labelledby="<?= e($commentsModalId) ?>Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <span class="tool-eyebrow mb-0"><i class="bi bi-chat-dots"></i> Comments</span>
          <h4 class="modal-title h6 fw-bold mb-0" id="<?= e($commentsModalId) ?>Label"><?= e(compact_count($commentsCount)) ?> <?= $commentsCount === 1 ? 'comment' : 'comments' ?></h4>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="post-comments">
          <?php if ($comments): ?>
            <div class="post-comment-list">
              <?php foreach ($comments as $comment): ?>
                <div class="post-comment">
                  <?= profile_avatar($comment, '', ['alt' => $comment['display_name'], 'size' => 36]) ?>
                  <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <strong><?= e($comment['display_name']) ?></strong>
                      <span><?= e(time_ago($comment['created_at'])) ?> ago</span>
                    </div>
                    <p><?= nl2br(e($comment['body'])) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-muted small">No comments yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="modal-footer">
        <form method="post" action="<?= e(url('actions/create_comment.php')) ?>" class="post-comment-form">
          <?= csrf_field() ?>
          <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
          <input class="form-control" name="body" maxlength="500" placeholder="Write a comment..." required>
          <button class="btn btn-primary rounded-pill">Comment</button>
        </form>
      </div>
    </div>
  </div>
</div>
