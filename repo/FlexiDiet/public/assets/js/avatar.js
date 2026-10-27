(() => {
  const modal = document.getElementById('avatarModal');
  const preview = document.getElementById('avatarPreviewImage');
  const fileInput = document.getElementById('avatarFileInput');
  const chooseButton = document.getElementById('chooseAvatar');
  const deleteButton = document.getElementById('deleteAvatar');
  const cropPanel = document.getElementById('avatarCrop');
  const canvas = document.getElementById('avatarCropCanvas');
  const context = canvas.getContext('2d');
  const zoomInput = document.getElementById('avatarZoom');
  const saveButton = document.getElementById('saveAvatarCrop');
  const cancelButton = document.getElementById('cancelAvatarCrop');
  const help = document.getElementById('avatarHelp');
  const cropSize = canvas.width;
  let hasAvatar = false;
  let sourceImage = null;
  let sourceObjectUrl = '';
  let imageScale = 1;
  let imageX = 0;
  let imageY = 0;
  let dragStart = null;

  function applyAvatarState(image, exists) {
    image.hidden = !exists;
    const icon = image.nextElementSibling;
    if (icon) icon.hidden = exists;
  }

  function refreshUserAvatar() {
    const avatarUrl = `api/index.php?route=%2Fprofile%2Favatar&v=${Date.now()}`;
    const images = [...document.querySelectorAll('.fd-user-avatar-image'), preview];
    hasAvatar = false;
    for (const image of images) {
      image.hidden = true;
      image.onload = () => {
        image.hidden = false;
        hasAvatar = true;
        deleteButton.hidden = false;
        if (image === preview && cropPanel.hidden) {
          document.getElementById('avatarCurrent').hidden = false;
        }
      };
      image.onerror = () => {
        image.hidden = true;
        hasAvatar = false;
        deleteButton.hidden = true;
        if (image === preview) document.getElementById('avatarCurrent').hidden = true;
      };
      image.src = avatarUrl;
    }
  }

  function resetCrop() {
    cropPanel.hidden = true;
    saveButton.hidden = true;
    cancelButton.hidden = true;
    chooseButton.hidden = false;
    help.hidden = false;
    document.getElementById('avatarCurrent').hidden = false;
    sourceImage = null;
    if (sourceObjectUrl) URL.revokeObjectURL(sourceObjectUrl);
    sourceObjectUrl = '';
  }

  function openModal() {
    modal.hidden = false;
    document.getElementById('closeAvatar').focus();
    refreshUserAvatar();
  }

  function closeModal() {
    resetCrop();
    modal.hidden = true;
  }

  function constrainImage() {
    const drawnWidth = sourceImage.width * imageScale;
    const drawnHeight = sourceImage.height * imageScale;
    imageX = Math.min(0, Math.max(cropSize - drawnWidth, imageX));
    imageY = Math.min(0, Math.max(cropSize - drawnHeight, imageY));
  }

  function renderCrop() {
    if (!sourceImage) return;
    context.clearRect(0, 0, cropSize, cropSize);
    context.drawImage(
      sourceImage,
      imageX,
      imageY,
      sourceImage.width * imageScale,
      sourceImage.height * imageScale
    );
  }

  function startCrop(image) {
    sourceImage = image;
    const coverScale = cropSize / Math.min(image.width, image.height);
    zoomInput.value = '1';
    imageScale = coverScale;
    imageX = (cropSize - image.width * imageScale) / 2;
    imageY = (cropSize - image.height * imageScale) / 2;
    constrainImage();
    renderCrop();
    preview.hidden = true;
    document.getElementById('avatarCurrent').hidden = true;
    cropPanel.hidden = false;
    saveButton.hidden = false;
    cancelButton.hidden = false;
    chooseButton.hidden = true;
    deleteButton.hidden = true;
    help.hidden = true;
  }

  document.addEventListener('click', event => {
    if (event.target.closest('#openAvatar, #openAvatarProfile')) openModal();
  });
  document.getElementById('closeAvatar').addEventListener('click', closeModal);
  modal.addEventListener('click', event => {
    if (event.target === modal) closeModal();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });
  chooseButton.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    fileInput.value = '';
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      showToast('Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP.', 'warning');
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      showToast('Ảnh phải có dung lượng tối đa 2 MB.', 'warning');
      return;
    }

    resetCrop();
    sourceObjectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      if (image.width > 6000 || image.height > 6000 || image.width * image.height > 20000000) {
        resetCrop();
        showToast('Kích thước ảnh quá lớn. Hãy chọn ảnh tối đa 6000 × 6000 pixel.', 'warning');
        return;
      }
      startCrop(image);
    };
    image.onerror = () => {
      resetCrop();
      showToast('Không thể đọc ảnh này. Hãy chọn một ảnh hợp lệ.', 'warning');
    };
    image.src = sourceObjectUrl;
  });

  zoomInput.addEventListener('input', () => {
    if (!sourceImage) return;
    const oldScale = imageScale;
    const centerX = cropSize / 2;
    const centerY = cropSize / 2;
    const zoom = Number(zoomInput.value);
    imageScale = cropSize / Math.min(sourceImage.width, sourceImage.height) * zoom;
    const factor = imageScale / oldScale;
    imageX = centerX - (centerX - imageX) * factor;
    imageY = centerY - (centerY - imageY) * factor;
    constrainImage();
    renderCrop();
  });

  canvas.addEventListener('pointerdown', event => {
    if (!sourceImage) return;
    canvas.setPointerCapture(event.pointerId);
    dragStart = {x:event.clientX, y:event.clientY, imageX, imageY};
  });
  canvas.addEventListener('pointermove', event => {
    if (!dragStart) return;
    const rect = canvas.getBoundingClientRect();
    imageX = dragStart.imageX + (event.clientX - dragStart.x) * cropSize / rect.width;
    imageY = dragStart.imageY + (event.clientY - dragStart.y) * cropSize / rect.height;
    constrainImage();
    renderCrop();
  });
  canvas.addEventListener('pointerup', () => { dragStart = null; });
  canvas.addEventListener('pointercancel', () => { dragStart = null; });

  cancelButton.addEventListener('click', () => {
    resetCrop();
    refreshUserAvatar();
  });
  saveButton.addEventListener('click', async () => {
    if (!sourceImage) return;
    saveButton.disabled = true;
    try {
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
      if (!blob) throw new Error('Không thể tạo ảnh đã cắt.');
      const formData = new FormData();
      formData.append('avatar', blob, 'avatar.jpg');
      await FlexiAPI.request('/profile/avatar', {method:'POST', data:formData});
      resetCrop();
      refreshUserAvatar();
      showToast('Đã lưu ảnh đại diện theo khung bạn chọn.', 'success');
    } catch (error) {
      showToast(error.message || 'Không thể cập nhật ảnh đại diện.', 'warning');
    } finally {
      saveButton.disabled = false;
    }
  });

  deleteButton.addEventListener('click', async () => {
    if (!hasAvatar || !window.confirm('Bạn muốn xóa ảnh đại diện hiện tại?')) return;
    deleteButton.disabled = true;
    try {
      await FlexiAPI.request('/profile/avatar', {method:'DELETE'});
      refreshUserAvatar();
      showToast('Đã xóa ảnh đại diện.', 'success');
    } catch (error) {
      showToast(error.message || 'Không thể xóa ảnh đại diện.', 'warning');
    } finally {
      deleteButton.disabled = false;
    }
  });

  window.refreshUserAvatar = refreshUserAvatar;
  document.addEventListener('flexidiet:pages-ready', refreshUserAvatar);
  refreshUserAvatar();
})();
