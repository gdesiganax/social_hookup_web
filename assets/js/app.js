const chatMessages = document.querySelector('[data-chat-messages]');

if (chatMessages) {
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!window.confirm(button.dataset.confirm || 'Are you sure?')) {
      event.preventDefault();
    }
  });
});

const swipeStage = document.querySelector('[data-swipe-stage]');
const swipeCards = Array.from(document.querySelectorAll('[data-swipe-card]'));
let activeSwipeIndex = 0;
let dragStartX = 0;
let dragStartY = 0;
let isDragging = false;

function normalizeIndex(index) {
  if (!swipeCards.length) return 0;
  return (index + swipeCards.length) % swipeCards.length;
}

function paintSwipeDeck() {
  swipeCards.forEach((card, index) => {
    card.classList.remove('active', 'is-active', 'is-next', 'is-third', 'peek-like', 'peek-nope');
    card.style.transform = '';
    card.style.transition = '';

    if (index === activeSwipeIndex) {
      card.classList.add('is-active');
    } else if (index === normalizeIndex(activeSwipeIndex + 1)) {
      card.classList.add('is-next');
    } else if (index === normalizeIndex(activeSwipeIndex + 2)) {
      card.classList.add('is-third');
    }
  });
}

function advanceSwipe(direction = 1) {
  if (!swipeCards.length) return;
  const current = swipeCards[activeSwipeIndex];
  current.classList.add(direction > 0 ? 'throw-right' : 'throw-left');

  window.setTimeout(() => {
    current.classList.remove('throw-right', 'throw-left');
    activeSwipeIndex = normalizeIndex(activeSwipeIndex + 1);
    paintSwipeDeck();
  }, 280);
}

function previousSwipe() {
  if (!swipeCards.length) return;
  activeSwipeIndex = normalizeIndex(activeSwipeIndex - 1);
  paintSwipeDeck();
}

document.querySelectorAll('[data-swipe-next]').forEach((button) => {
  button.addEventListener('click', () => advanceSwipe(1));
});

document.querySelectorAll('[data-swipe-skip]').forEach((button) => {
  button.addEventListener('click', () => advanceSwipe(-1));
});

document.querySelectorAll('[data-swipe-prev]').forEach((button) => {
  button.addEventListener('click', previousSwipe);
});

if (swipeStage && swipeCards.length) {
  paintSwipeDeck();

  swipeStage.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowRight') advanceSwipe(1);
    if (event.key === 'ArrowLeft') advanceSwipe(-1);
  });

  swipeStage.addEventListener('pointerdown', (event) => {
    const activeCard = swipeCards[activeSwipeIndex];
    if (!activeCard || event.target.closest('button, a, input, select, textarea')) return;
    isDragging = true;
    dragStartX = event.clientX;
    dragStartY = event.clientY;
    activeCard.setPointerCapture(event.pointerId);
    activeCard.style.transition = 'none';
  });

  swipeStage.addEventListener('pointermove', (event) => {
    if (!isDragging) return;
    const activeCard = swipeCards[activeSwipeIndex];
    const dx = event.clientX - dragStartX;
    const dy = event.clientY - dragStartY;
    const rotation = Math.max(-14, Math.min(14, dx / 18));

    activeCard.style.transform = `translate(${dx}px, ${dy * 0.25}px) rotate(${rotation}deg)`;
    activeCard.classList.toggle('peek-like', dx > 55);
    activeCard.classList.toggle('peek-nope', dx < -55);
  });

  swipeStage.addEventListener('pointerup', (event) => {
    if (!isDragging) return;
    isDragging = false;
    const activeCard = swipeCards[activeSwipeIndex];
    const dx = event.clientX - dragStartX;
    activeCard.style.transition = '';

    if (Math.abs(dx) > 110) {
      advanceSwipe(dx > 0 ? 1 : -1);
    } else {
      activeCard.style.transform = '';
      activeCard.classList.remove('peek-like', 'peek-nope');
    }
  });

  swipeStage.addEventListener('pointercancel', () => {
    isDragging = false;
    const activeCard = swipeCards[activeSwipeIndex];
    if (activeCard) {
      activeCard.style.transform = '';
      activeCard.classList.remove('peek-like', 'peek-nope');
    }
  });
}

const postFeed = document.querySelector('[data-post-feed]');
const postLoader = document.querySelector('[data-post-loader]');
const postEnd = document.querySelector('[data-post-end]');
let postsAreLoading = false;

async function loadMorePosts() {
  if (!postFeed || postsAreLoading || postFeed.dataset.hasMore !== '1') return;

  postsAreLoading = true;
  if (postLoader) postLoader.classList.remove('d-none');

  const url = new URL(postFeed.dataset.loadUrl, window.location.href);
  url.searchParams.set('offset', postFeed.dataset.nextOffset || '0');
  url.searchParams.set('limit', postFeed.dataset.pageSize || '5');

  try {
    const response = await fetch(url.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (!response.ok) throw new Error('Could not load posts');
    const html = await response.text();
    if (html.trim()) {
      postFeed.insertAdjacentHTML('beforeend', html);
    }

    postFeed.dataset.nextOffset = response.headers.get('X-Next-Offset') || postFeed.dataset.nextOffset;
    postFeed.dataset.hasMore = response.headers.get('X-Has-More-Posts') || '0';

    if (postFeed.dataset.hasMore !== '1' && postEnd) {
      postEnd.classList.remove('d-none');
    }
  } catch (error) {
    if (postEnd) {
      postEnd.textContent = 'More posts could not be loaded right now.';
      postEnd.classList.remove('d-none');
    }
  } finally {
    postsAreLoading = false;
    if (postLoader) postLoader.classList.add('d-none');
  }
}

if (postFeed) {
  window.addEventListener('scroll', () => {
    const nearBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 420;
    if (nearBottom) loadMorePosts();
  }, { passive: true });
}
