(function () {
  const enewsState = {
    pendingFiles: [],
    editPostId: null,
    posts: []
  };

  function escapeHtml(value = '') {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getInitials(name = '') {
    const cleaned = String(name || 'N').trim();
    if (!cleaned) return 'N';
    const parts = cleaned.split(/\s+/).filter(Boolean).slice(0, 2);
    return parts.map(part => part[0]).join('').toUpperCase();
  }

  function formatRelativeTime(isoString) {
    if (!isoString) return 'Mới đây';
    const then = new Date(isoString);
    const now = new Date();
    const diffMinutes = Math.max(0, Math.round((now - then) / 60000));
    if (diffMinutes < 1) return 'Vừa xong';
    if (diffMinutes < 60) return `${diffMinutes} phút trước`;
    const diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours} giờ trước`;
    const diffDays = Math.round(diffHours / 24);
    if (diffDays < 7) return `${diffDays} ngày trước`;
    return then.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  function setStatus(message, tone = 'neutral') {
    const statusNode = document.getElementById('enewsStatus');
    if (!statusNode) return;
    statusNode.textContent = message;
    statusNode.style.color = tone === 'error' ? 'var(--fd-red, #dc2626)' : tone === 'success' ? 'var(--fd-green, #1ea96d)' : 'var(--fd-muted, #475569)';
  }

  function refreshPendingPreview() {
    const preview = document.getElementById('enewsPreview');
    const clearButton = document.getElementById('enewsClearDraft');
    if (!preview || !clearButton) return;
    preview.innerHTML = '';
    if (enewsState.pendingFiles.length === 0) {
      clearButton.hidden = true;
      return;
    }
    clearButton.hidden = false;
    enewsState.pendingFiles.forEach((fileEntry, index) => {
      const item = document.createElement('div');
      item.className = 'fd-enews-preview-item';
      item.innerHTML = `
        <img src="${fileEntry.previewUrl}" alt="${escapeHtml(fileEntry.name)}">
        <button type="button" class="fd-enews-preview-remove" data-action="remove-pending" data-index="${index}" aria-label="Xóa ảnh đã chọn">×</button>
      `;
      preview.appendChild(item);
    });
  }

  function buildPostMarkup(post) {
    const authorName = post.author?.display_name || 'Người dùng';
    const content = post.content ? escapeHtml(post.content) : 'Đã cập nhật tin mới.';
    const images = Array.isArray(post.images) ? post.images : [];
    const isLiked = Boolean(post.liked_by_me);
    const canEdit = Boolean(post.can_edit);
    const likeLabel = isLiked ? 'Đã thích' : 'Thích';
    const imagesMarkup = images.length ? `
      <div class="fd-enews-gallery">
        ${images.map(image => `<img class="fd-enews-image" src="${escapeHtml(image.path || image.file_path || '')}" alt="Hình ảnh bài đăng" loading="lazy">`).join('')}
      </div>
    ` : '';
    return `
      <article class="fd-enews-post" data-post-id="${post.id}">
        <div class="fd-enews-post-head">
          <div class="fd-enews-avatar">${escapeHtml(getInitials(authorName))}</div>
          <div class="fd-enews-post-meta">
            <strong>${escapeHtml(authorName)}</strong>
            <small>${escapeHtml(formatRelativeTime(post.created_at))}</small>
          </div>
          ${canEdit ? `
            <div class="fd-enews-post-tools">
              <button type="button" data-action="edit-post" data-post-id="${post.id}">Sửa</button>
              <button type="button" data-action="delete-post" data-post-id="${post.id}">Xóa</button>
            </div>
          ` : ''}
        </div>
        <div class="fd-enews-content">${content}</div>
        ${imagesMarkup}
        <div class="fd-enews-actions">
          <button type="button" class="fd-enews-action ${isLiked ? 'active' : ''}" data-action="toggle-like" data-post-id="${post.id}">
            <i class="fa-${isLiked ? 'solid' : 'regular'} fa-thumbs-up"></i>
            <span>${likeLabel}</span>
            <span>${Number(post.likes_count || 0)}</span>
          </button>
          <button type="button" class="fd-enews-action" data-action="comment-post" data-post-id="${post.id}" disabled>
            <i class="fa-regular fa-comment"></i>
            <span>Bình luận</span>
            <span>${Number(post.comments_count || 0)}</span>
          </button>
          <button type="button" class="fd-enews-action" data-action="share-post" data-post-id="${post.id}" disabled>
            <i class="fa-solid fa-share"></i>
            <span>Chia sẻ</span>
          </button>
        </div>
      </article>
    `;
  }

  function renderFeed(posts) {
    const feed = document.getElementById('enewsFeed');
    const emptyState = document.getElementById('enewsEmptyState');
    if (!feed) return;
    if (!Array.isArray(posts) || posts.length === 0) {
      feed.innerHTML = '';
      emptyState.hidden = false;
      return;
    }
    emptyState.hidden = true;
    feed.innerHTML = posts.map(post => buildPostMarkup(post)).join('');
  }

  async function loadFeed() {
    const feed = document.getElementById('enewsFeed');
    if (!feed) return;
    try {
      feed.innerHTML = '<div class="fd-enews-empty"><i class="fa-solid fa-spinner fa-spin"></i><p>Đang tải bài viết...</p></div>';
      const result = await FlexiAPI.request('/enews/feed?limit=12&offset=0');
      enewsState.posts = Array.isArray(result.posts) ? result.posts : [];
      renderFeed(enewsState.posts);
    } catch (error) {
      console.error(error);
      setStatus(error.message || 'Không thể tải eNews.', 'error');
      feed.innerHTML = '<div class="fd-enews-empty"><h3>Không tải được bảng tin</h3><p>Vui lòng thử lại sau.</p></div>';
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const content = document.getElementById('enewsContent')?.value.trim() || '';
    const submitBtn = document.getElementById('enewsSubmit');
    if (!content && enewsState.pendingFiles.length === 0) {
      setStatus('Vui lòng nhập nội dung hoặc chọn ít nhất một ảnh.', 'error');
      return;
    }
    submitBtn.disabled = true;
    setStatus('Đang xử lý bài viết...', 'neutral');
    try {
      let imagePaths = [];
      if (enewsState.pendingFiles.length > 0) {
        const uploadForm = new FormData();
        enewsState.pendingFiles.forEach(item => uploadForm.append('media[]', item.file));
        const uploadResult = await FlexiAPI.request('/enews/media', { method: 'POST', data: uploadForm });
        imagePaths = Array.isArray(uploadResult.files) ? uploadResult.files.map(item => item.path) : [];
      }

      const payload = {
        content,
        images: imagePaths
      };

      if (enewsState.editPostId) {
        await FlexiAPI.request(`/enews/posts/${enewsState.editPostId}`, { method: 'PATCH', data: payload });
        setStatus('Đã cập nhật bài viết.', 'success');
      } else {
        await FlexiAPI.request('/enews/posts', { method: 'POST', data: payload });
        setStatus('Đăng bài thành công.', 'success');
      }

      form.reset();
      enewsState.pendingFiles = [];
      enewsState.editPostId = null;
      document.getElementById('enewsCancelEdit').hidden = true;
      document.getElementById('enewsSubmit').textContent = 'Đăng bài';
      refreshPendingPreview();
      await loadFeed();
    } catch (error) {
      console.error(error);
      setStatus(error.message || 'Không thể đăng bài. Vui lòng thử lại.', 'error');
    } finally {
      submitBtn.disabled = false;
    }
  }

  async function handleFeedClick(event) {
    const actionButton = event.target.closest('[data-action]');
    if (!actionButton) return;
    const action = actionButton.dataset.action;
    const postId = Number(actionButton.dataset.postId);
    if (!postId) return;

    if (action === 'toggle-like') {
      const post = enewsState.posts.find(item => Number(item.id) === postId);
      const liked = Boolean(post?.liked_by_me);
      try {
        await FlexiAPI.request(`/enews/posts/${postId}/like`, { method: liked ? 'DELETE' : 'POST' });
        await loadFeed();
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể cập nhật thích.', 'error');
      }
      return;
    }

    if (action === 'edit-post') {
      const post = enewsState.posts.find(item => Number(item.id) === postId);
      if (!post) return;
      const textarea = document.getElementById('enewsContent');
      const cancelBtn = document.getElementById('enewsCancelEdit');
      const submitBtn = document.getElementById('enewsSubmit');
      enewsState.editPostId = postId;
      textarea.value = post.content || '';
      submitBtn.textContent = 'Lưu thay đổi';
      cancelBtn.hidden = false;
      setStatus('Đang chỉnh sửa bài đăng.', 'neutral');
      textarea.focus();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    if (action === 'delete-post') {
      if (!window.confirm('Bạn có chắc muốn xóa bài viết này?')) return;
      try {
        await FlexiAPI.request(`/enews/posts/${postId}`, { method: 'DELETE' });
        await loadFeed();
        setStatus('Bài viết đã được xóa.', 'success');
      } catch (error) {
        console.error(error);
        setStatus(error.message || 'Không thể xóa bài viết.', 'error');
      }
      return;
    }

    if (action === 'remove-pending') {
      const index = Number(actionButton.dataset.index);
      enewsState.pendingFiles.splice(index, 1);
      refreshPendingPreview();
    }
  }

  function attachListeners() {
    const form = document.getElementById('enewsComposerForm');
    if (!form || form.dataset.bound === 'true') return;
    form.dataset.bound = 'true';
    form.addEventListener('submit', handleSubmit);

    const input = document.getElementById('enewsMediaInput');
    if (input) {
      input.addEventListener('change', event => {
        const selected = Array.from(event.target.files || []).filter(file => file && file.type.startsWith('image/'));
        if (selected.length === 0) {
          setStatus('Chỉ hỗ trợ ảnh JPG, PNG, WebP.', 'error');
          return;
        }
        const remainingSlots = 6 - enewsState.pendingFiles.length;
        if (remainingSlots <= 0) {
          setStatus('Bạn đã chọn tối đa 6 ảnh. Vui lòng bỏ bớt trước khi tiếp tục.', 'error');
          input.value = '';
          return;
        }
        const imageEntries = selected.slice(0, remainingSlots).map(file => ({
          file,
          previewUrl: URL.createObjectURL(file),
          name: file.name || 'image-upload'
        }));
        enewsState.pendingFiles.push(...imageEntries);
        refreshPendingPreview();
        input.value = '';
      });
    }

    const clearButton = document.getElementById('enewsClearDraft');
    if (clearButton) {
      clearButton.addEventListener('click', () => {
        enewsState.pendingFiles = [];
        refreshPendingPreview();
        document.getElementById('enewsContent').value = '';
        const cancelBtn = document.getElementById('enewsCancelEdit');
        const submitBtn = document.getElementById('enewsSubmit');
        enewsState.editPostId = null;
        cancelBtn.hidden = true;
        submitBtn.textContent = 'Đăng bài';
        setStatus('Nháp đã được xóa.', 'neutral');
      });
    }

    const cancelEditButton = document.getElementById('enewsCancelEdit');
    if (cancelEditButton) {
      cancelEditButton.addEventListener('click', () => {
        enewsState.editPostId = null;
        document.getElementById('enewsContent').value = '';
        document.getElementById('enewsSubmit').textContent = 'Đăng bài';
        cancelEditButton.hidden = true;
        setStatus('Đã hủy chỉnh sửa.', 'neutral');
      });
    }

    const feed = document.getElementById('enewsFeed');
    if (feed) {
      feed.addEventListener('click', handleFeedClick);
    }
  }

  async function initEnNews() {
    const root = document.getElementById('view-enews');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';

    attachListeners();
    const userName = document.getElementById('enewsUserName');
    const userAvatar = document.getElementById('enewsUserAvatar');
    try {
      const me = await FlexiAPI.request('/auth/me');
      const displayName = me?.user?.display_name || 'Người dùng';
      if (userName) userName.textContent = displayName;
      if (userAvatar) userAvatar.textContent = getInitials(displayName);
    } catch (error) {
      console.warn('Unable to load session user for eNews', error);
    }

    await loadFeed();
  }

  document.addEventListener('flexidiet:pages-ready', initEnNews);
})();
