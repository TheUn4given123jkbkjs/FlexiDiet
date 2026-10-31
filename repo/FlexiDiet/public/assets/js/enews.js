(function () {
  const state = {
    pendingFiles: [],
    existingImages: [],
    editPostId: null,
    posts: [],
    profilePosts: [],
    profileOffset: 0,
    profileHasMore: false,
    profileFilter: 'all',
    feedOffset: 0,
    feedHasMore: false,
    commentPages: new Map(),
    likeBusy: new Set(),
    notificationOffset: 0,
    notificationHasMore: false,
    notificationsTimer: null
  };

  function escapeHtml(value = '') {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function initials(name = '') {
    return String(name || 'N').trim().split(/\s+/).filter(Boolean).slice(0, 2)
      .map(part => part[0]).join('').toUpperCase() || 'N';
  }

  function avatar(author, className = '') {
    const id = Number(author?.id || author?.user_id || 0);
    const image = id > 0
      ? `<img data-avatar-image src="api/index.php?route=%2Fprofile%2Favatar%2F${id}" alt="">`
      : '';
    return `<span class="fd-enews-avatar ${className}">${escapeHtml(initials(author?.display_name))}${image}</span>`;
  }

  function bindAvatarFallback(root) {
    root.querySelectorAll('[data-avatar-image]').forEach(image => {
      image.addEventListener('error', () => image.remove(), { once: true });
    });
  }

  function relativeTime(value) {
    if (!value) return 'Mới đây';
    const date = new Date(value.replace(' ', 'T') + (value.includes('Z') ? '' : '+07:00'));
    if (Number.isNaN(date.getTime())) return 'Mới đây';
    const minutes = Math.max(0, Math.round((Date.now() - date.getTime()) / 60000));
    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút trước`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours} giờ trước`;
    const days = Math.round(hours / 24);
    return days < 7 ? `${days} ngày trước` : date.toLocaleDateString('vi-VN');
  }

  function setStatus(message, tone = 'neutral') {
    const node = document.getElementById('enewsStatus');
    if (!node) return;
    node.textContent = message;
    node.dataset.tone = tone;
  }

  function privacyUrl(route) {
    return `api/index.php?route=${encodeURIComponent(route)}`;
  }

  function refreshPendingPreview() {
    const preview = document.getElementById('enewsPreview');
    const clearButton = document.getElementById('enewsClearDraft');
    const uploadLabel = document.querySelector('.fd-enews-upload-button span');
    const uploadInput = document.getElementById('enewsMediaInput');
    if (!preview || !clearButton) return;
    preview.innerHTML = '';
    const entries = [
      ...state.existingImages.map((entry, index) => ({ ...entry, kind: 'existing', index })),
      ...state.pendingFiles.map((entry, index) => ({ ...entry, kind: 'pending', index }))
    ];
    clearButton.hidden = entries.length === 0;
    if (uploadLabel) uploadLabel.textContent = entries.length ? 'Thêm ảnh khác' : 'Thêm ảnh';
    if (uploadInput) uploadInput.disabled = entries.length >= 6;
    entries.forEach(entry => {
      const item = document.createElement('div');
      item.className = 'fd-enews-preview-item';
      item.innerHTML = `<img src="${escapeHtml(entry.previewUrl)}" alt="${escapeHtml(entry.name)}">
        <button type="button" class="fd-enews-preview-remove" data-action="remove-pending" data-kind="${entry.kind}" data-index="${entry.index}" aria-label="Xóa ảnh">×</button>`;
      preview.appendChild(item);
    });
    preview.dataset.imageCount = String(entries.length);
  }

  function removePreviewImage(event) {
    const button = event.target.closest('[data-action="remove-pending"]');
    if (!button) return;
    if (button.dataset.kind === 'existing') {
      state.existingImages.splice(Number(button.dataset.index), 1);
    } else {
      const entry = state.pendingFiles.splice(Number(button.dataset.index), 1)[0];
      if (entry) URL.revokeObjectURL(entry.previewUrl);
    }
    refreshPendingPreview();
    setStatus('Đã xóa ảnh khỏi bài viết.', 'neutral');
  }

  function postMarkup(post) {
    const shared = post.entry_type === 'share' && post.share;
    const images = (post.images || []).map(image =>
      `<img class="fd-enews-image" src="${privacyUrl(`/enews/media/${Number(image.id)}`)}" alt="Ảnh bài viết" loading="lazy">`
    ).join('');
    const shareHeader = shared
      ? `<div class="fd-enews-shared-by">${avatar(post.share, 'fd-enews-avatar-small')}
          <span>          <strong><button type="button" class="fd-enews-profile-link" data-profile-user="${Number(post.share.user_id)}">${escapeHtml(post.share.display_name)}</button></strong> đã chia sẻ bài viết này</span>
          <small>${escapeHtml(relativeTime(post.entry_at))}</small>
        </div>${post.share.caption ? `<p class="fd-enews-share-caption">${escapeHtml(post.share.caption)}</p>` : ''}`
      : '';
    const canShare = post.privacy === 'public';
    const commentPage = state.commentPages.get(Number(post.id));
    return `<article class="fd-enews-post" data-post-id="${Number(post.id)}">
      ${shareHeader}
      <div class="fd-enews-post-head">
        ${avatar(post.author)}
        <div class="fd-enews-post-meta">
          <strong><button type="button" class="fd-enews-profile-link" data-profile-user="${Number(post.author?.id || 0)}">${escapeHtml(post.author?.display_name || 'Người dùng')}</button></strong>
          <small>${escapeHtml(relativeTime(post.created_at))} · ${post.privacy === 'private' ? 'Chỉ mình tôi' : 'Công khai'}</small>
        </div>
        ${post.can_edit ? `<div class="fd-enews-post-tools">
          <button type="button" data-action="edit-post" data-post-id="${post.id}">Sửa</button>
          <button type="button" data-action="delete-post" data-post-id="${post.id}">Xóa</button>
        </div>` : ''}
      </div>
      <div class="fd-enews-content">${escapeHtml(post.content || '')}</div>
      ${images ? `<div class="fd-enews-gallery">${images}</div>` : ''}
      <div class="fd-enews-actions">
        <button type="button" class="fd-enews-action ${post.liked_by_me ? 'active' : ''}"
          data-action="toggle-like" data-post-id="${post.id}" aria-pressed="${post.liked_by_me ? 'true' : 'false'}">
          <i class="fa-${post.liked_by_me ? 'solid' : 'regular'} fa-thumbs-up"></i>
          <span>${post.liked_by_me ? 'Đã thích' : 'Thích'}</span><span data-likes-count>${Number(post.likes_count || 0)}</span>
        </button>
        <button type="button" class="fd-enews-action" data-action="toggle-comments" data-post-id="${post.id}" aria-expanded="${commentPage ? 'true' : 'false'}">
          <i class="fa-regular fa-comment"></i><span>Bình luận</span><span data-comments-count>${Number(post.comments_count || 0)}</span>
        </button>
        <button type="button" class="fd-enews-action" data-action="share-post" data-post-id="${post.id}" ${canShare ? '' : 'disabled'}>
          <i class="fa-solid fa-share"></i><span>Chia sẻ</span><span data-shares-count>${Number(post.shares_count || 0)}</span>
        </button>
      </div>
      <section class="fd-enews-comments" data-comments-panel="${post.id}" ${commentPage ? '' : 'hidden'}>
        <div class="fd-enews-comments-list" data-comments-list></div>
        <button type="button" class="fd-enews-more" data-action="more-comments" data-post-id="${post.id}" hidden>Tải thêm bình luận</button>
        <div class="fd-enews-replying" data-replying hidden>
          Đang trả lời <strong data-replying-name></strong>
          <button type="button" data-action="cancel-reply" aria-label="Hủy trả lời">Hủy</button>
        </div>
        <form class="fd-enews-comment-form" data-post-id="${post.id}">
          <textarea name="content" rows="2" maxlength="2000" placeholder="Viết bình luận..." required></textarea>
          <button type="submit" class="fd-enews-primary">Gửi</button>
        </form>
      </section>
    </article>`;
  }

  function renderPosts(posts, append = false) {
    const feed = document.getElementById('enewsFeed');
    const empty = document.getElementById('enewsEmptyState');
    if (!feed) return;
    if (!append) feed.innerHTML = '';
    if (!posts.length && !append) {
      empty.hidden = false;
      return;
    }
    empty.hidden = true;
    feed.insertAdjacentHTML('beforeend', posts.map(postMarkup).join(''));
    bindAvatarFallback(feed);
    const more = document.getElementById('enewsFeedMore');
    more.hidden = !state.feedHasMore;
  }

  async function loadFeed({ append = false } = {}) {
    const feed = document.getElementById('enewsFeed');
    if (!feed) return;
    const offset = append ? state.feedOffset : 0;
    if (!append) {
      state.commentPages.clear();
      feed.innerHTML = '<div class="fd-enews-empty"><i class="fa-solid fa-spinner fa-spin"></i><p>Đang tải bài viết...</p></div>';
    }
    try {
      const result = await FlexiAPI.request(`/enews/feed?limit=12&offset=${offset}`);
      const posts = Array.isArray(result.posts) ? result.posts : [];
      state.posts = append ? state.posts.concat(posts) : posts;
      state.feedOffset = Number(result.next_offset || 0);
      state.feedHasMore = Boolean(result.has_more);
      renderPosts(posts, append);
    } catch (error) {
      console.error(error);
      setStatus(error.message || 'Không thể tải bảng tin.', 'error');
      feed.innerHTML = '<div class="fd-enews-empty"><h3>Không tải được bảng tin</h3><p>Vui lòng thử lại sau.</p></div>';
    }
  }

  function commentMarkup(comment) {
    const reply = comment.parent_id !== null;
    return `<article class="fd-enews-comment ${reply ? 'fd-enews-comment-reply' : ''}" data-comment-id="${comment.id}">
      ${avatar(comment.author, 'fd-enews-avatar-small')}
      <div class="fd-enews-comment-body">
        <div class="fd-enews-comment-bubble">
          <strong><button type="button" class="fd-enews-profile-link" data-profile-user="${Number(comment.author?.id || 0)}">${escapeHtml(comment.author?.display_name || 'Người dùng')}</button></strong>
          <p>${escapeHtml(comment.content)}</p>
        </div>
        <div class="fd-enews-comment-meta"><time>${escapeHtml(relativeTime(comment.created_at))}</time>
          ${!comment.deleted && !reply ? `<button type="button" data-action="reply-comment" data-comment-id="${comment.id}" data-post-id="${comment.post_id}">Trả lời</button>` : ''}
          ${comment.can_edit ? `<button type="button" data-action="edit-comment" data-comment-id="${comment.id}">Sửa</button>` : ''}
          ${comment.can_delete ? `<button type="button" data-action="delete-comment" data-comment-id="${comment.id}" data-post-id="${comment.post_id}">Xóa</button>` : ''}
        </div>
      </div>
    </article>`;
  }

  async function loadComments(postId, { append = false } = {}) {
    const article = document.querySelector(`.fd-enews-post[data-post-id="${postId}"]`);
    const panel = article?.querySelector('[data-comments-panel]');
    const list = panel?.querySelector('[data-comments-list]');
    if (!panel || !list) return;
    const current = state.commentPages.get(postId) || { offset: 0, hasMore: false };
    const offset = append ? current.offset : 0;
    if (!append) list.innerHTML = '<p class="fd-enews-comment-loading">Đang tải bình luận...</p>';
    try {
      const result = await FlexiAPI.request(`/enews/posts/${postId}/comments?limit=30&offset=${offset}`);
      const comments = Array.isArray(result.comments) ? result.comments : [];
      state.commentPages.set(postId, { offset: Number(result.next_offset || 0), hasMore: Boolean(result.has_more) });
      if (!append) list.innerHTML = '';
      if (comments.length) list.insertAdjacentHTML('beforeend', comments.map(commentMarkup).join(''));
      else if (!append) list.innerHTML = '<p class="fd-enews-comment-loading">Chưa có bình luận nào.</p>';
      bindAvatarFallback(list);
      panel.querySelector('[data-action="more-comments"]').hidden = !result.has_more;
      const post = state.posts.find(item => Number(item.id) === postId);
      if (post) post.comments_count = Number(result.total_count || 0);
      const count = article.querySelector('[data-comments-count]');
      if (count) count.textContent = String(result.total_count || 0);
    } catch (error) {
      console.error(error);
      list.innerHTML = '<p class="fd-enews-comment-loading">Không thể tải bình luận.</p>';
      setStatus(error.message || 'Không thể tải bình luận.', 'error');
    }
  }

  function clearPendingFiles() {
    state.pendingFiles.forEach(file => URL.revokeObjectURL(file.previewUrl));
    state.pendingFiles = [];
    refreshPendingPreview();
  }

  async function handleComposerSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const content = document.getElementById('enewsContent').value.trim();
    const submit = document.getElementById('enewsSubmit');
    if (!content && !state.pendingFiles.length && !state.existingImages.length) {
      setStatus('Nhập nội dung hoặc thêm ít nhất một ảnh.', 'error');
      return;
    }
    if (state.existingImages.length + state.pendingFiles.length > 6) {
      setStatus('Bài viết tối đa 6 ảnh.', 'error');
      return;
    }
    submit.disabled = true;
    try {
      let images = [];
      if (state.pendingFiles.length) {
        const upload = new FormData();
        state.pendingFiles.forEach(entry => upload.append('media[]', entry.file));
        const uploaded = await FlexiAPI.request('/enews/media', { method: 'POST', data: upload });
        images = (uploaded.files || []).map(file => file.path);
      }
      const payload = {
        content,
        privacy: document.getElementById('enewsPrivacy').value,
        images
      };
      if (state.editPostId) {
        payload.keep_image_ids = state.existingImages.map(image => Number(image.id));
        await FlexiAPI.request(`/enews/posts/${state.editPostId}`, { method: 'PATCH', data: payload });
        setStatus('Đã cập nhật bài viết.', 'success');
      } else {
        await FlexiAPI.request('/enews/posts', { method: 'POST', data: payload });
        setStatus('Đăng bài thành công.', 'success');
      }
      form.reset();
      clearPendingFiles();
      state.existingImages = [];
      refreshPendingPreview();
      state.editPostId = null;
      document.getElementById('enewsCancelEdit').hidden = true;
      document.getElementById('enewsMediaInput').disabled = false;
      document.getElementById('enewsSubmit').textContent = 'Đăng bài';
      await loadFeed();
      if (location.hash === '#enews-profile') await loadSocialProfile();
    } catch (error) {
      console.error(error);
      setStatus(error.message || 'Không thể lưu bài viết.', 'error');
    } finally {
      submit.disabled = false;
    }
  }

  function updateInteractionCount(article, selector, value) {
    const node = article?.querySelector(selector);
    if (node) node.textContent = String(value);
  }

  async function handleFeedClick(event) {
    const profileLink = event.target.closest('[data-profile-user]');
    if (profileLink) {
      await openSocialProfile(Number(profileLink.dataset.profileUser));
      return;
    }
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const action = button.dataset.action;
    const postId = Number(button.dataset.postId);
    const article = button.closest('.fd-enews-post');

    if (action === 'toggle-like' && postId && !state.likeBusy.has(postId)) {
      state.likeBusy.add(postId);
      button.disabled = true;
      const post = state.posts.find(item => Number(item.id) === postId);
      try {
        const result = await FlexiAPI.request(`/enews/posts/${postId}/like`, {
          method: post?.liked_by_me ? 'DELETE' : 'POST'
        });
        if (post) {
          post.liked_by_me = Boolean(result.liked);
          post.likes_count = Number(result.likes_count);
        }
        button.classList.toggle('active', Boolean(result.liked));
        button.setAttribute('aria-pressed', String(Boolean(result.liked)));
        button.querySelector('i').className = `fa-${result.liked ? 'solid' : 'regular'} fa-thumbs-up`;
        button.querySelector('span').textContent = result.liked ? 'Đã thích' : 'Thích';
        updateInteractionCount(article, '[data-likes-count]', result.likes_count);
        loadNotifications();
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể cập nhật lượt thích.', 'error');
      } finally {
        state.likeBusy.delete(postId);
        button.disabled = false;
      }
      return;
    }

    if (action === 'toggle-comments' && article) {
      const panel = article.querySelector('[data-comments-panel]');
      const opening = panel.hidden;
      panel.hidden = !opening;
      button.setAttribute('aria-expanded', String(opening));
      if (opening && !state.commentPages.has(postId)) await loadComments(postId);
      return;
    }

    if (action === 'more-comments' && postId) {
      await loadComments(postId, { append: true });
      return;
    }

    if (action === 'reply-comment' && article) {
      const reply = article.querySelector('[data-replying]');
      const comment = button.closest('[data-comment-id]');
      reply.hidden = false;
      reply.dataset.parentId = button.dataset.commentId;
      reply.querySelector('[data-replying-name]').textContent = comment.querySelector('.fd-enews-comment-bubble strong').textContent;
      article.querySelector('.fd-enews-comment-form textarea').focus();
      return;
    }

    if (action === 'cancel-reply' && article) {
      const reply = article.querySelector('[data-replying]');
      reply.hidden = true;
      delete reply.dataset.parentId;
      return;
    }

    if (action === 'edit-comment') {
      const comment = button.closest('[data-comment-id]');
      const commentId = Number(button.dataset.commentId);
      const content = comment.querySelector('.fd-enews-comment-bubble p').textContent;
      const updated = window.prompt('Sửa bình luận:', content);
      if (updated === null) return;
      try {
        await FlexiAPI.request(`/enews/comments/${commentId}`, { method: 'PATCH', data: { content: updated } });
        const root = comment.closest('.fd-enews-post');
        await loadComments(Number(root.dataset.postId));
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể sửa bình luận.', 'error');
      }
      return;
    }

    if (action === 'delete-comment') {
      const commentId = Number(button.dataset.commentId);
      if (!window.confirm('Gỡ bình luận này?')) return;
      try {
        await FlexiAPI.request(`/enews/comments/${commentId}`, { method: 'DELETE' });
        state.commentPages.delete(postId);
        await loadComments(postId);
        loadNotifications();
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể gỡ bình luận.', 'error');
      }
      return;
    }

    if (action === 'share-post' && postId) {
      const caption = window.prompt('Thêm lời dẫn khi chia sẻ (có thể để trống):', '');
      if (caption === null) return;
      button.disabled = true;
      try {
        const result = await FlexiAPI.request(`/enews/posts/${postId}/share`, { method: 'POST', data: { caption } });
        const post = state.posts.find(item => Number(item.id) === postId);
        if (post) post.shares_count = Number(result.shares_count);
        updateInteractionCount(article, '[data-shares-count]', result.shares_count);
        setStatus('Đã chia sẻ bài viết vào bảng tin.', 'success');
        await loadFeed();
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể chia sẻ bài viết.', 'error');
      } finally {
        button.disabled = false;
      }
      return;
    }

    if (action === 'edit-post') {
      const post = state.posts.find(item => Number(item.id) === postId);
      if (!post) return;
      if (location.hash === '#enews-profile' && typeof switchTab === 'function') switchTab('enews');
      state.editPostId = postId;
      clearPendingFiles();
      state.existingImages = (post.images || []).map(image => ({
        id: Number(image.id),
        previewUrl: privacyUrl(`/enews/media/${Number(image.id)}`),
        name: 'Ảnh bài viết'
      }));
      refreshPendingPreview();
      document.getElementById('enewsContent').value = post.content || '';
      document.getElementById('enewsPrivacy').value = post.privacy || 'public';
      document.getElementById('enewsMediaInput').disabled = false;
      document.getElementById('enewsSubmit').textContent = 'Lưu thay đổi';
      document.getElementById('enewsCancelEdit').hidden = false;
      setStatus('Đang sửa bài viết.', 'neutral');
      document.getElementById('enewsContent').focus();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    if (action === 'delete-post') {
      if (!window.confirm('Bạn có chắc muốn xóa bài viết này?')) return;
      try {
        await FlexiAPI.request(`/enews/posts/${postId}`, { method: 'DELETE' });
        state.commentPages.delete(postId);
        await loadFeed();
        if (location.hash === '#enews-profile') await loadSocialProfile();
        setStatus('Bài viết đã được xóa.', 'success');
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể xóa bài viết.', 'error');
      }
      return;
    }

    if (action === 'remove-pending') {
      if (button.dataset.kind === 'existing') {
        state.existingImages.splice(Number(button.dataset.index), 1);
      } else {
        const entry = state.pendingFiles.splice(Number(button.dataset.index), 1)[0];
        if (entry) URL.revokeObjectURL(entry.previewUrl);
      }
      refreshPendingPreview();
    }

  }

  async function handleCommentSubmit(event) {
    const form = event.target.closest('.fd-enews-comment-form');
    if (!form) return;
    event.preventDefault();
    const postId = Number(form.dataset.postId);
    const reply = form.closest('[data-comments-panel]').querySelector('[data-replying]');
    const payload = { content: form.elements.content.value };
    if (!reply.hidden && reply.dataset.parentId) payload.parent_id = Number(reply.dataset.parentId);
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    try {
      const result = await FlexiAPI.request(`/enews/posts/${postId}/comments`, { method: 'POST', data: payload });
      form.reset();
      reply.hidden = true;
      delete reply.dataset.parentId;
      const post = state.posts.find(item => Number(item.id) === postId);
      if (post && result.comments_count !== undefined) post.comments_count = Number(result.comments_count);
      const article = form.closest('.fd-enews-post');
      updateInteractionCount(article, '[data-comments-count]', result.comments_count);
      state.commentPages.delete(postId);
      await loadComments(postId);
      loadNotifications();
    } catch (error) {
      console.error(error);
      setStatus(error.message || 'Không thể gửi bình luận.', 'error');
    } finally {
      submit.disabled = false;
    }
  }

  function notificationMarkup(notification) {
    return `<button type="button" class="fd-enews-notification ${notification.read_at ? '' : 'unread'}"
      data-action="read-notification" data-notification-id="${notification.id}"
      data-post-id="${notification.post_id || ''}" data-comment-id="${notification.comment_id || ''}"
      data-target-user-id="${notification.target_user_id || ''}" data-notification-type="${escapeHtml(notification.type)}">
      <span class="fd-enews-notification-dot"></span>
      <span><strong>${escapeHtml(notification.actor_name)}</strong> ${escapeHtml(notification.message)}
        <small>${escapeHtml(relativeTime(notification.created_at))}</small>
      </span>
    </button>`;
  }

  async function loadNotifications({ append = false } = {}) {
    const list = document.getElementById('enewsNotificationsList');
    if (!list) return;
    const offset = append ? state.notificationOffset : 0;
    try {
      const result = await FlexiAPI.request(`/enews/notifications?limit=20&offset=${offset}`);
      const items = result.notifications || [];
      if (!append) list.innerHTML = '';
      if (items.length) list.insertAdjacentHTML('beforeend', items.map(notificationMarkup).join(''));
      else if (!append) list.innerHTML = '<p class="fd-enews-comment-loading">Chưa có thông báo.</p>';
      state.notificationOffset = Number(result.next_offset || 0);
      state.notificationHasMore = Boolean(result.has_more);
      document.getElementById('enewsNotificationsMore').hidden = !state.notificationHasMore;
      const badge = document.getElementById('enewsUnreadCount');
      const unread = Number(result.unread_count || 0);
      badge.textContent = unread > 99 ? '99+' : String(unread);
      badge.hidden = unread === 0;
      document.getElementById('enewsNotificationsStatus').textContent = unread ? `${unread} chưa đọc` : 'Đã cập nhật';
    } catch (error) {
      console.error(error);
      document.getElementById('enewsNotificationsStatus').textContent = 'Không tải được';
    }
  }

    async function openPostFromNotification(button) {
      const postId = Number(button.dataset.postId);
      if (!postId) return;
      try {
        const post = await FlexiAPI.request(`/enews/posts/${postId}`);
        post.entry_type = 'post';
        post.entry_at = post.created_at;
        const currentIndex = state.posts.findIndex(item => Number(item.id) === postId && item.entry_type !== 'share');
        if (currentIndex >= 0) state.posts[currentIndex] = post;
        else state.posts.unshift(post);
        renderPosts(state.posts);
        const article = document.querySelector(`.fd-enews-post[data-post-id="${postId}"]`);
        if (article) {
          const panel = article.querySelector('[data-comments-panel]');
          if (button.dataset.commentId && panel) {
            panel.hidden = false;
            article.querySelector('[data-action="toggle-comments"]')?.setAttribute('aria-expanded', 'true');
            await loadComments(postId);
            const target = article.querySelector(`[data-comment-id="${Number(button.dataset.commentId)}"]`);
            target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            article.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể mở bài viết từ thông báo.', 'error');
      }
    }

    async function openSocialProfile(userId) {
      if (!Number.isSafeInteger(userId) || userId < 1) return;
      history.pushState(null, '', `?user=${userId}#enews-profile`);
      if (typeof switchTab === 'function') switchTab('enews-profile');
      await loadSocialProfile();
    }

    function profilePostListMarkup(posts) {
      const filtered = posts.filter(post => state.profileFilter === 'all' || post.privacy === state.profileFilter);
      const feed = document.getElementById('enewsProfileFeed');
      if (!feed) return;
      if (!filtered.length) {
        feed.innerHTML = '<div class="fd-enews-empty"><h3>Chưa có bài viết nào</h3><p>Bài viết sẽ xuất hiện ở đây khi được chia sẻ.</p></div>';
        return;
      }
      feed.innerHTML = filtered.map(post => `<article class="fd-enews-profile-post" data-post-id="${Number(post.id)}">
        <div class="fd-enews-profile-post-heading">
          <small>${escapeHtml(relativeTime(post.created_at))} · ${post.privacy === 'private' ? 'Chỉ mình tôi' : 'Công khai'}</small>
          ${post.can_edit ? `<span><button type="button" data-action="edit-post" data-post-id="${Number(post.id)}">Sửa</button>
            <button type="button" data-action="delete-post" data-post-id="${Number(post.id)}">Xóa</button></span>` : ''}
        </div>
        <p>${escapeHtml(post.content || '')}</p>
        ${(post.images || []).map(image => `<img src="${privacyUrl(`/enews/media/${Number(image.id)}`)}" alt="Ảnh bài viết" loading="lazy">`).join('')}
      </article>`).join('');
    }

    async function loadSocialProfile({ append = false } = {}) {
      const root = document.getElementById('view-enews-profile');
      if (!root) return;
      const userId = Number(new URLSearchParams(location.search).get('user') || 0);
      const route = userId > 0 ? `/enews/profile/${userId}` : '/enews/profile';
      const offset = append ? state.profileOffset : 0;
      const page = await FlexiAPI.request(`${route}?limit=12&offset=${offset}`);
      const profile = page.profile;
      state.profilePosts = append ? state.profilePosts.concat(page.posts || []) : (page.posts || []);
      state.profileOffset = Number(page.next_offset || 0);
      state.profileHasMore = Boolean(page.has_more);
      const avatarNode = document.getElementById('enewsProfileAvatar');
      avatarNode.textContent = initials(profile.display_name);
      avatarNode.querySelector('img')?.remove();
      const image = document.createElement('img');
      image.src = `api/index.php?route=%2Fprofile%2Favatar%2F${Number(profile.id)}`;
      image.alt = '';
      image.addEventListener('error', () => image.remove(), { once: true });
      avatarNode.appendChild(image);
      document.getElementById('enewsProfileName').textContent = profile.display_name;
      document.getElementById('enewsProfileJoined').textContent = `Tham gia ${new Date(profile.joined_at.replace(' ', 'T') + '+07:00').toLocaleDateString('vi-VN')}`;
      document.getElementById('enewsProfileBio').textContent = profile.bio || 'Chưa có thông tin giới thiệu.';
      document.getElementById('enewsProfileEdit').hidden = !profile.is_me;
      document.getElementById('enewsProfileEditForm').hidden = true;
      document.getElementById('enewsProfileFeedMore').hidden = !state.profileHasMore;
      renderFriendship(profile);
      document.querySelector('[data-profile-private]').hidden = !profile.is_me;
      if (!profile.is_me && state.profileFilter === 'private') state.profileFilter = 'all';
      document.querySelectorAll('[data-profile-filter]').forEach(button => button.classList.toggle('active', button.dataset.profileFilter === state.profileFilter));
      document.getElementById('enewsProfileBioInput').value = profile.bio || '';
      state.posts = state.posts.filter(post => !state.profilePosts.some(profilePost => Number(profilePost.id) === Number(post.id)));
      state.posts.push(...state.profilePosts);
      profilePostListMarkup(state.profilePosts);
      if (profile.is_me && !append) await Promise.all([loadFriendRequests(), loadFriends()]);
    }

  function renderFriendship(profile) {
    const relationship = profile.relationship || { status: profile.is_me ? 'self' : 'none' };
    const button = document.getElementById('enewsProfileFriend');
    const response = document.getElementById('enewsProfileFriendResponse');
    const status = document.getElementById('enewsProfileRelationshipStatus');
    button.hidden = Boolean(profile.is_me) || relationship.status === 'pending' && !relationship.initiated_by_me;
    response.hidden = Boolean(profile.is_me) || relationship.status !== 'pending' || Boolean(relationship.initiated_by_me);
    const labels = {
      self: '',
      none: '',
      accepted: 'Hai bạn hiện là bạn bè.',
      pending: relationship.initiated_by_me ? 'Đã gửi lời mời kết bạn.' : 'Người này đã gửi lời mời kết bạn.'
    };
    status.textContent = labels[relationship.status] || '';
    button.dataset.profileUser = String(profile.id);
    button.dataset.relationship = relationship.status;
    button.dataset.requestId = String(relationship.request_id || '');
    button.textContent = relationship.status === 'accepted'
      ? 'Hủy kết bạn'
      : relationship.status === 'pending'
        ? 'Hủy lời mời'
        : 'Kết bạn';
  }

  async function loadFriendRequests() {
    const panel = document.getElementById('enewsFriendRequestsPanel');
    const list = document.getElementById('enewsFriendRequestsList');
    const result = await FlexiAPI.request('/enews/friends/requests');
    const requests = result.requests || [];
    panel.hidden = requests.length === 0;
    if (!requests.length) {
      list.innerHTML = '';
      return;
    }
    list.innerHTML = requests.map(request => `<div class="fd-enews-friend-request">
      <button type="button" class="fd-enews-profile-link" data-profile-user="${Number(request.user_id)}">${escapeHtml(request.display_name)}</button>
      <small>${request.direction === 'received' ? 'Muốn kết bạn với bạn' : 'Đang chờ phản hồi'}</small>
      ${request.direction === 'received'
        ? `<span><button type="button" class="fd-enews-primary" data-action="accept-friend-request" data-request-id="${Number(request.id)}">Chấp nhận</button>
           <button type="button" class="fd-enews-secondary" data-action="decline-friend-request" data-request-id="${Number(request.id)}">Từ chối</button></span>`
        : `<button type="button" class="fd-enews-secondary" data-action="cancel-friend-request" data-user-id="${Number(request.user_id)}">Hủy</button>`}
    </div>`).join('');
  }

  async function loadFriends() {
    const panel = document.getElementById('enewsFriendsPanel');
    const list = document.getElementById('enewsFriendsList');
    const result = await FlexiAPI.request('/enews/friends');
    const friends = result.friends || [];
    panel.hidden = friends.length === 0;
    list.innerHTML = friends.map(friend => `<div class="fd-enews-friend-request">
      <button type="button" class="fd-enews-profile-link" data-profile-user="${Number(friend.user_id)}">${escapeHtml(friend.display_name)}</button>
      <small>Bạn bè</small>
    </div>`).join('');
  }

  async function handleFriendAction(action, userId, requestId) {
    if (action === 'accept-profile-friend' || action === 'decline-profile-friend'
      || action === 'accept-friend-request' || action === 'decline-friend-request') {
      const accept = action.startsWith('accept');
      await FlexiAPI.request(`/enews/friends/requests/${requestId}/${accept ? 'accept' : 'decline'}`, {
        method: 'POST',
        data: {}
      });
    } else if (action === 'cancel-friend-request') {
      await FlexiAPI.request(`/enews/friends/${userId}/request`, { method: 'DELETE' });
    } else if (action === 'profile-friend') {
      const button = document.getElementById('enewsProfileFriend');
      const currentStatus = button.dataset.relationship;
      if (currentStatus === 'accepted') {
        if (!window.confirm('Hủy kết bạn với người này?')) return;
        await FlexiAPI.request(`/enews/friends/${userId}`, { method: 'DELETE' });
      } else if (currentStatus === 'pending') {
        await FlexiAPI.request(`/enews/friends/${userId}/request`, { method: 'DELETE' });
      } else {
        await FlexiAPI.request(`/enews/friends/${userId}/request`, { method: 'POST', data: {} });
      }
    }
    await loadSocialProfile();
  }

    async function saveSocialProfile(event) {
      event.preventDefault();
      const input = document.getElementById('enewsProfileBioInput');
      const button = event.currentTarget.querySelector('button[type="submit"]');
      button.disabled = true;
      try {
        await FlexiAPI.request('/enews/profile', { method: 'PATCH', data: { bio: input.value } });
        document.getElementById('enewsProfileBio').textContent = input.value.trim() || 'Chưa có thông tin giới thiệu.';
        document.getElementById('enewsProfileEditForm').hidden = true;
        setStatus('Đã cập nhật thông tin giới thiệu.', 'success');
      } catch (error) {
        console.error(error);
        document.getElementById('enewsProfileStatus').textContent = error.message || 'Không thể lưu hồ sơ.';
      } finally {
        button.disabled = false;
      }
    }

  function attachListeners() {
    const form = document.getElementById('enewsComposerForm');
    if (!form || form.dataset.bound === 'true') return;
    form.dataset.bound = 'true';
    form.addEventListener('submit', handleComposerSubmit);
    document.getElementById('enewsMediaInput').addEventListener('change', event => {
      const selected = Array.from(event.target.files || []).filter(file => file && file.type.startsWith('image/'));
      const remaining = 6 - state.pendingFiles.length - state.existingImages.length;
      if (!selected.length) {
        setStatus('Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP.', 'error');
        return;
      }
      if (remaining <= 0) {
        setStatus('Bạn đã chọn tối đa 6 ảnh.', 'error');
        event.target.value = '';
        return;
      }
      selected.slice(0, remaining).forEach(file => {
        state.pendingFiles.push({ file, previewUrl: URL.createObjectURL(file), name: file.name || 'image' });
      });
      refreshPendingPreview();
      event.target.value = '';
    });
    document.getElementById('enewsPreview').addEventListener('click', removePreviewImage);
    document.getElementById('enewsClearDraft').addEventListener('click', () => {
      clearPendingFiles();
      form.reset();
      state.editPostId = null;
      state.existingImages = [];
      refreshPendingPreview();
      document.getElementById('enewsCancelEdit').hidden = true;
      document.getElementById('enewsMediaInput').disabled = false;
      document.getElementById('enewsSubmit').textContent = 'Đăng bài';
      setStatus('Đã xóa bản nháp.', 'neutral');
    });
    document.getElementById('enewsCancelEdit').addEventListener('click', () => {
      state.editPostId = null;
      state.existingImages = [];
      form.reset();
      refreshPendingPreview();
      document.getElementById('enewsMediaInput').disabled = false;
      document.getElementById('enewsCancelEdit').hidden = true;
      document.getElementById('enewsSubmit').textContent = 'Đăng bài';
      setStatus('Đã hủy chỉnh sửa.', 'neutral');
    });
    document.getElementById('enewsFeed').addEventListener('click', handleFeedClick);
    document.getElementById('enewsFeed').addEventListener('submit', handleCommentSubmit);
    document.getElementById('enewsProfileFeed').addEventListener('click', handleFeedClick);
    document.getElementById('enewsProfileFriend').addEventListener('click', async event => {
      const button = event.currentTarget;
      button.disabled = true;
      try {
        await handleFriendAction('profile-friend', Number(button.dataset.profileUser), Number(button.dataset.requestId));
      } catch (error) {
        console.error(error);
        document.getElementById('enewsProfileRelationshipStatus').textContent = error.message || 'Không thể cập nhật quan hệ bạn bè.';
      } finally {
        button.disabled = false;
      }
    });
    document.getElementById('enewsProfileFriendResponse').addEventListener('click', async event => {
      const button = event.target.closest('[data-action]');
      if (!button) return;
      const action = button.dataset.action;
      const requestId = Number(document.getElementById('enewsProfileFriend').dataset.requestId);
      try {
        await handleFriendAction(action, 0, requestId);
      } catch (error) {
        console.error(error);
        document.getElementById('enewsProfileRelationshipStatus').textContent = error.message || 'Không thể xử lý lời mời.';
      }
    });
    document.getElementById('enewsFriendRequestsList').addEventListener('click', async event => {
      const button = event.target.closest('[data-action]');
      if (!button) {
        const profileLink = event.target.closest('[data-profile-user]');
        if (profileLink) await openSocialProfile(Number(profileLink.dataset.profileUser));
        return;
      }
      try {
        await handleFriendAction(button.dataset.action, Number(button.dataset.userId), Number(button.dataset.requestId));
      } catch (error) {
        console.error(error);
        document.getElementById('enewsProfileStatus').textContent = error.message || 'Không thể cập nhật lời mời.';
      }
    });
    document.getElementById('enewsFriendsList').addEventListener('click', event => {
      const link = event.target.closest('[data-profile-user]');
      if (link) openSocialProfile(Number(link.dataset.profileUser));
    });
    document.getElementById('enewsProfileFeedMore').addEventListener('click', () => loadSocialProfile({ append: true }));
    document.getElementById('enewsProfileEdit').addEventListener('click', () => {
      document.getElementById('enewsProfileEditForm').hidden = false;
      document.getElementById('enewsProfileBioInput').focus();
    });
    document.getElementById('enewsProfileEditForm').addEventListener('submit', saveSocialProfile);
    document.getElementById('enewsProfileCancel').addEventListener('click', () => {
      document.getElementById('enewsProfileEditForm').hidden = true;
      document.getElementById('enewsProfileStatus').textContent = '';
    });
    document.getElementById('enewsProfileFilters').addEventListener('click', event => {
      const filter = event.target.closest('[data-profile-filter]');
      if (!filter) return;
      state.profileFilter = filter.dataset.profileFilter;
      document.querySelectorAll('[data-profile-filter]').forEach(button => button.classList.toggle('active', button === filter));
      profilePostListMarkup(state.profilePosts);
    });
    document.getElementById('enewsFeedMore').addEventListener('click', () => loadFeed({ append: true }));
    document.getElementById('enewsNotificationsToggle').addEventListener('click', async event => {
      const panel = document.getElementById('enewsNotificationsPanel');
      panel.hidden = !panel.hidden;
      event.currentTarget.setAttribute('aria-expanded', String(!panel.hidden));
      if (!panel.hidden) {
        state.notificationOffset = 0;
        await loadNotifications();
      }
    });
    document.getElementById('enewsOpenOwnProfile').addEventListener('click', async () => {
      history.pushState(null, '', `${location.pathname}#enews-profile`);
      if (typeof switchTab === 'function') switchTab('enews-profile');
      await loadSocialProfile();
    });
    document.getElementById('enewsNotificationsMore').addEventListener('click', () => loadNotifications({ append: true }));
    document.getElementById('enewsNotificationsList').addEventListener('click', async event => {
      const button = event.target.closest('[data-action="read-notification"]');
      if (!button) return;
      try {
        if (button.classList.contains('unread')) {
          await FlexiAPI.request(`/enews/notifications/${button.dataset.notificationId}`, { method: 'PATCH', data: {} });
          button.classList.remove('unread');
          await loadNotifications();
        }
        if (typeof switchTab === 'function') switchTab('enews');
        if (button.dataset.postId) {
          await openPostFromNotification(button);
        } else if (button.dataset.targetUserId) {
          await openSocialProfile(Number(button.dataset.targetUserId));
        }
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể cập nhật thông báo.', 'error');
      }
    });
  }

  async function initEnews() {
    const root = document.getElementById('view-enews');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';
    attachListeners();
    window.addEventListener('popstate', () => {
      if (location.hash === '#enews-profile') {
        loadSocialProfile().catch(error => {
          console.error(error);
          document.getElementById('enewsProfileStatus').textContent = error.message || 'Không thể tải hồ sơ.';
        });
      }
    });
    try {
      const me = await FlexiAPI.request('/auth/me');
      const user = me?.user || {};
      document.getElementById('enewsUserName').textContent = user.display_name || 'Người dùng';
      document.getElementById('enewsUserAvatar').textContent = initials(user.display_name);
      if (user.id) {
        const image = document.createElement('img');
        image.src = `api/index.php?route=%2Fprofile%2Favatar%2F${Number(user.id)}`;
        image.alt = '';
        image.addEventListener('error', () => image.remove(), { once: true });
        document.getElementById('enewsUserAvatar').appendChild(image);
      }
    } catch (error) {
      console.error(error);
      setStatus('Không thể xác thực phiên đăng nhập.', 'error');
    }
    await Promise.all([loadFeed(), loadNotifications()]);
    if (!state.notificationsTimer) {
      state.notificationsTimer = window.setInterval(() => {
        if (!document.hidden) loadNotifications();
      }, 60000);
    }
    if (location.hash === '#enews-profile') await loadSocialProfile().catch(error => {
      console.error(error);
      document.getElementById('enewsProfileStatus').textContent = error.message || 'Không thể tải hồ sơ.';
    });
  }

  document.addEventListener('flexidiet:pages-ready', initEnews);
})();
