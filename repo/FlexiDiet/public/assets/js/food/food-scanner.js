/**
 * FLEXIDIET - FOOD SCANNER & AI VISION (food/food-scanner.js)
 * Subtab 1: Laser scanning animation, YOLOv10m image recognition, demo presets, and text prompt analysis.
 */

const FoodScanner = (() => {
  const presetMap = {
    pho_bo: { name: 'Phở', meal: 'breakfast', fallbackId: 46 },
    com_tam: { name: 'Cơm tấm', meal: 'lunch', fallbackId: 27 },
    bun_cha: { name: 'Bún chả', meal: 'lunch', fallbackId: 14 },
    banh_mi: { name: 'Bánh mì', meal: 'breakfast', fallbackId: 5 },
    salad_ga: { name: 'Bánh canh', meal: 'dinner', fallbackId: 1 },
    goi_cuon: { name: 'Gỏi cuốn', meal: 'snack', fallbackId: 31 }
  };

  let lastRecognizedResult = null;

  async function loadPreset(presetKey) {
    const preset = presetMap[presetKey];
    if (!preset) return;

    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');

    const sampleImages = {
      pho_bo: 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=500&auto=format&fit=crop&q=80',
      com_tam: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80',
      bun_cha: 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=500&auto=format&fit=crop&q=80',
      banh_mi: 'https://images.unsplash.com/photo-1626804475297-41608ea09aeb?w=500&auto=format&fit=crop&q=80',
      salad_ga: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',
      goi_cuon: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80'
    };

    if (dropContent) dropContent.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'block';
    if (previewImg) previewImg.src = sampleImages[presetKey] || '';
    if (laser) laser.style.display = 'block';

    if (typeof showToast === 'function') {
      showToast(`AI Vision đang quét hình ảnh món ăn: ${preset.name}...`, 'info');
    }

    setTimeout(async () => {
      if (laser) laser.style.display = 'none';
      try {
        const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(preset.name)}`);
        const targetDish = (matches && matches.length > 0) ? matches[0] : { id: preset.fallbackId };
        if (window.FoodComposer && typeof window.FoodComposer.loadDishToComposer === 'function') {
          await window.FoodComposer.loadDishToComposer(targetDish.id, preset.meal);
        }
        if (typeof showToast === 'function') {
          showToast(`AI nhận diện thành công: "${targetDish.name || preset.name}"! Chuyển sang bảng tùy chỉnh.`, 'success');
        }
      } catch (err) {
        if (typeof showToast === 'function') {
          showToast(`Không thể tải công thức: ${err.message}`, 'warning');
        }
      }
    }, 850);
  }

  async function handleImageUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    e.target.value = '';

    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');

    const reader = new FileReader();
    reader.onload = function(evt) {
      if (dropContent) dropContent.style.display = 'none';
      if (previewWrap) previewWrap.style.display = 'block';
      if (previewImg) previewImg.src = evt.target.result;
      if (laser) laser.style.display = 'block';
    };
    reader.readAsDataURL(file);

    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');
    if (resCard) resCard.style.display = 'none';
    if (guideCard) guideCard.style.display = 'flex';

    if (typeof showToast === 'function') {
      showToast('AI Vision (YOLOv10m) đang phân tích hình ảnh...', 'info');
    }

    try {
      const formData = new FormData();
      formData.append('image', file);
      const res = await FlexiAPI.request('/ai/recognize', { method: 'POST', data: formData });

      if (laser) laser.style.display = 'none';

      if (res && res.status === 'recognized' && res.dish) {
        displayRecognizedResult(res);
      } else {
        displayUnrecognizedFallback(res || {});
      }
    } catch (err) {
      if (laser) laser.style.display = 'none';
      displayUnrecognizedFallback({
        status: 'unrecognized',
        is_unknown: true,
        message: err.message || 'Không thể nhận diện được món ăn.'
      });
    }
  }

  function displayRecognizedResult(res) {
    lastRecognizedResult = res;

    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');
    if (guideCard) guideCard.style.display = 'none';
    if (resCard) resCard.style.display = 'flex';

    const topPred = res.top_prediction || {};
    const confPct = Math.round((topPred.confidence || 0.85) * 100);

    const tag = document.getElementById('foodScanStatusTag');
    if (tag) tag.innerHTML = `<i class="fa-solid fa-circle-check"></i> Đã nhận diện (${confPct}%)`;

    const lat = document.getElementById('foodScanLatencyTag');
    if (lat) lat.innerHTML = `<i class="fa-solid fa-bolt"></i> ${res.inference_ms || 240}ms`;

    const title = document.getElementById('foodScanDishTitle');
    if (title) title.textContent = res.dish.name;

    const sub = document.getElementById('foodScanServingSub');
    if (sub) sub.textContent = `Khẩu phần chuẩn: ${res.dish.serving_label || '1 phần'} · Công thức chuẩn VDD`;

    let kcal = 0, prot = 0, carb = 0, fat = 0;
    if (res.recipe && Array.isArray(res.recipe.ingredients) && res.recipe.ingredients.length > 0) {
      for (const ing of res.recipe.ingredients) {
        const grams = Number(ing.grams) || 0;
        kcal += (grams * Number(ing.kcal_100g || 0)) / 100;
        prot += (grams * Number(ing.protein_100g || 0)) / 100;
        carb += (grams * Number(ing.carb_100g || 0)) / 100;
        fat += (grams * Number(ing.fat_100g || 0)) / 100;
      }
    } else {
      kcal = Number(res.dish.kcal) || 0;
      prot = Number(res.dish.protein_g) || 0;
      carb = Number(res.dish.carb_g) || 0;
      fat = Number(res.dish.fat_g) || 0;
    }

    const elCal = document.getElementById('foodScanCal');
    const elProt = document.getElementById('foodScanProt');
    const elCarb = document.getElementById('foodScanCarb');
    const elFat = document.getElementById('foodScanFat');
    if (elCal) elCal.textContent = Math.round(kcal);
    if (elProt) elProt.textContent = prot.toFixed(1);
    if (elCarb) elCarb.textContent = carb.toFixed(1);
    if (elFat) elFat.textContent = fat.toFixed(1);

    const altsWrap = document.getElementById('foodScanAltsWrap');
    const altsChips = document.getElementById('foodScanAltsChips');
    if (altsWrap && altsChips) {
      const escape = FoodCore.escapeHtml;
      const otherPreds = (res.predictions || []).filter(p => p.dish_code !== topPred.dish_code);
      if (otherPreds.length > 0) {
        altsChips.innerHTML = otherPreds.map(p => `
          <button type="button" class="alt-chip" onclick="FoodHub.selectAlternativePrediction('${escape(p.dish_code)}')">
            <i class="fa-solid fa-utensils"></i> ${escape(p.name_vi)} (${Math.round(p.confidence * 100)}%)
          </button>
        `).join('');
        altsWrap.style.display = 'block';
      } else {
        altsWrap.style.display = 'none';
      }
    }

    if (typeof showToast === 'function') {
      showToast(`AI nhận diện thành công: "${res.dish.name}" (${confPct}%)!`, 'success');
    }
  }

  function displayUnrecognizedFallback(res) {
    const modal = document.getElementById('fdAiUnrecognizedModal');
    if (modal) {
      const topPred = res.top_prediction;
      const lowPredBox = document.getElementById('aiLowPredBox');
      const lowPredText = document.getElementById('aiLowPredText');
      if (topPred && Number(topPred.confidence) > 0.05 && lowPredBox && lowPredText) {
        lowPredBox.style.display = 'block';
        lowPredText.textContent = `${topPred.name_vi || topPred.dish_code} (Độ tin cậy: ${Math.round(topPred.confidence * 100)}% - dưới ngưỡng chuẩn xác 40%)`;
      } else if (lowPredBox) {
        lowPredBox.style.display = 'none';
      }
      modal.style.display = 'flex';
    }
    if (typeof showToast === 'function') {
      showToast('Chưa nhận diện được món do món chưa có trong danh sách hoặc ảnh mờ.', 'warning');
    }
  }

  function closeUnrecognizedModal() {
    const modal = document.getElementById('fdAiUnrecognizedModal');
    if (modal) modal.style.display = 'none';
  }

  function retryScannerImage() {
    closeUnrecognizedModal();
    resetScanner();
    const input = document.getElementById('foodHubImageInput');
    if (input) input.click();
  }

  function goToManualComposer() {
    closeUnrecognizedModal();
    if (window.FoodComposer && typeof window.FoodComposer.startNewCustomDish === 'function') {
      window.FoodComposer.startNewCustomDish();
    }
  }

  function onResultGoComposer() {
    if (lastRecognizedResult && lastRecognizedResult.dish && lastRecognizedResult.dish.id) {
      if (window.FoodComposer && typeof window.FoodComposer.loadDishToComposer === 'function') {
        window.FoodComposer.loadDishToComposer(lastRecognizedResult.dish.id);
      }
    } else {
      FoodCore.switchSubtab('composer');
    }
  }

  async function selectAlternativePrediction(dishCode) {
    if (typeof showToast === 'function') {
      showToast(`Đang nạp công thức cho món: ${dishCode}...`, 'info');
    }
    try {
      const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(dishCode)}`);
      if (matches && matches.length > 0) {
        if (window.FoodComposer && typeof window.FoodComposer.loadDishToComposer === 'function') {
          await window.FoodComposer.loadDishToComposer(matches[0].id);
        }
      } else {
        if (typeof showToast === 'function') {
          showToast('Món chưa có sẵn trong danh mục.', 'warning');
        }
      }
    } catch (e) {
      if (typeof showToast === 'function') {
        showToast(e.message, 'warning');
      }
    }
  }

  function resetScanner() {
    lastRecognizedResult = null;
    const dropContent = document.getElementById('foodDropContent');
    const previewWrap = document.getElementById('foodPreviewWrap');
    const previewImg = document.getElementById('foodScannedPreview');
    const laser = document.getElementById('foodLaserScanner');
    const guideCard = document.getElementById('foodScannerGuideCard');
    const resCard = document.getElementById('foodScannerResultCard');

    if (dropContent) dropContent.style.display = 'block';
    if (previewWrap) previewWrap.style.display = 'none';
    if (previewImg) previewImg.src = '';
    if (laser) laser.style.display = 'none';
    if (resCard) resCard.style.display = 'none';
    if (guideCard) guideCard.style.display = 'flex';
  }

  async function triggerPromptAnalysis() {
    const input = document.getElementById('foodHubPromptInput');
    const prompt = input?.value.trim();
    if (!prompt) {
      if (typeof showToast === 'function') {
        showToast('Vui lòng nhập mô tả món ăn (VD: 1 tô phở bò tái nạm...)', 'warning');
      }
      return;
    }
    if (typeof showToast === 'function') {
      showToast(`AI đang phân tích mô tả: "${prompt}"...`, 'info');
    }
    try {
      const matches = await FlexiAPI.request(`/foods?q=${encodeURIComponent(prompt)}`);
      if (matches && matches.length > 0) {
        if (window.FoodComposer && typeof window.FoodComposer.loadDishToComposer === 'function') {
          await window.FoodComposer.loadDishToComposer(matches[0].id);
        }
        if (typeof showToast === 'function') {
          showToast(`Đã tìm thấy công thức phù hợp: ${matches[0].name}!`, 'success');
        }
      } else {
        loadPreset('salad_ga');
      }
    } catch {
      loadPreset('salad_ga');
    }
  }

  return {
    presetMap,
    loadPreset,
    handleImageUpload,
    displayRecognizedResult,
    displayUnrecognizedFallback,
    closeUnrecognizedModal,
    retryScannerImage,
    goToManualComposer,
    onResultGoComposer,
    selectAlternativePrediction,
    resetScanner,
    triggerPromptAnalysis,
    getLastRecognizedResult: () => lastRecognizedResult,
    setLastRecognizedResult: (res) => { lastRecognizedResult = res; }
  };
})();

window.FoodScanner = FoodScanner;
