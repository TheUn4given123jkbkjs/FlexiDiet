/**
 * Shared calorie-budget confirmation for every meal entry point.
 * The server remains authoritative and can independently reject an
 * unconfirmed over-budget POST (including stale data in another tab).
 */
const FlexiBudgetConfirm = (() => {
  let isSaving = false;
  let activeDialog = false;
  const round1 = value => Math.round((Number(value) + Number.EPSILON) * 10) / 10;
  const formatKcal = value => new Intl.NumberFormat('vi-VN', {
    maximumFractionDigits: 1
  }).format(Number(value) || 0);

  async function estimateKcal(payload, preferredEstimate) {
    if (Number.isFinite(Number(preferredEstimate)) && preferredEstimate !== undefined && preferredEstimate !== null) {
      return round1(preferredEstimate);
    }
    if (Array.isArray(payload.items)) {
      throw new Error('Chưa có số calo dự kiến của công thức để kiểm tra ngân sách.');
    }
    if (payload.dish_id) {
      // Mirror PHP rounding per ingredient, not just dish kcal * serving factor.
      const recipe = await FlexiAPI.request(`/foods/${Number(payload.dish_id)}/recipe`);
      const factor = Number(payload.serving_factor ?? 1);
      return round1(recipe.ingredients.reduce((sum, ingredient) => {
        const grams = round1(Number(ingredient.grams) * factor);
        return sum + round1(grams * Number(ingredient.kcal_100g) / 100);
      }, 0));
    }
    return round1(payload.total_kcal);
  }

  function confirmOverBudget({name, kcal, budget}) {
    if (activeDialog) return Promise.resolve(false);
    const overlay = document.getElementById('fdBudgetWarning');
    const cancel = document.getElementById('fdBudgetCancel');
    const approve = document.getElementById('fdBudgetApprove');
    const box = document.getElementById('fdBudgetDialog');
    if (!overlay || !cancel || !approve || !box) {
      throw new Error('Không tải được hộp thoại cảnh báo calo. Vui lòng tải lại trang.');
    }
    const remain = Number(budget.remaining_kcal);
    const available = Number(budget.target_kcal) + Number(budget.exercise_credit_kcal);
    const consumed = Number(budget.consumed_kcal);
    const exceeded = Math.max(0, kcal - remain);

    document.getElementById('fdBudgetFoodName').textContent = name || 'Món ăn được chọn';
    document.getElementById('fdBudgetRemaining').textContent = remain >= 0
      ? `${formatKcal(remain)} kcal` : `Đã vượt ${formatKcal(-remain)} kcal`;
    document.getElementById('fdBudgetFoodKcal').textContent = `+${formatKcal(kcal)} kcal`;
    document.getElementById('fdBudgetAfter').textContent = `${formatKcal(consumed + kcal)} / ${formatKcal(available)} kcal`;
    document.getElementById('fdBudgetExceeded').textContent = `${formatKcal(exceeded)} kcal`;
    document.getElementById('fdBudgetMessage').textContent = remain < 0
      ? `Bạn đã vượt ngân sách hôm nay ${formatKcal(-remain)} kcal. Thêm món này sẽ khiến mức vượt tăng lên ${formatKcal(exceeded)} kcal.`
      : `Nếu thêm món này, tổng năng lượng trong ngày sẽ vượt mục tiêu ${formatKcal(exceeded)} kcal.`;

    activeDialog = true;
    const previousFocus = document.activeElement;
    overlay.hidden = false;
    document.body.classList.add('fd-budget-dialog-open');
    cancel.focus();

    return new Promise(resolve => {
      let settled = false;
      function close(accepted) {
        if (settled) return;
        settled = true;
        overlay.hidden = true;
        document.body.classList.remove('fd-budget-dialog-open');
        cancel.removeEventListener('click', onCancel);
        approve.removeEventListener('click', onApprove);
        overlay.removeEventListener('click', onBackdrop);
        document.removeEventListener('keydown', onKeydown, true);
        activeDialog = false;
        if (previousFocus?.isConnected && typeof previousFocus.focus === 'function') previousFocus.focus();
        resolve(accepted);
      }
      function onCancel() { close(false); }
      function onApprove() { close(true); }
      function onBackdrop(e) { if (e.target === overlay) close(false); }
      function onKeydown(e) {
        if (e.key === 'Escape') { e.preventDefault(); close(false); }
        if (e.key === 'Tab') {
          const first = cancel, last = approve;
          if (e.shiftKey && document.activeElement === first) {
            e.preventDefault(); last.focus();
          } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault(); first.focus();
          }
        }
      }
      cancel.addEventListener('click', onCancel);
      approve.addEventListener('click', onApprove);
      overlay.addEventListener('click', onBackdrop);
      document.addEventListener('keydown', onKeydown, true);
    });
  }

  async function saveMeal(payload, {name, estimatedKcal} = {}) {
    // Prevent duplicate writes while the modal is open or a POST is pending.
    if (isSaving) return null;
    isSaving = true;
    try {
      const kcal = await estimateKcal(payload, estimatedKcal);
      if (!Number.isFinite(kcal) || kcal < 0) throw new Error('Không xác định được lượng calo của món.');
      const mealName = name || payload.name || document.getElementById('qfSelectedDishName')?.value || 'Món ăn';
      const day = payload.entry_date || '';
      const today = typeof fdIsoDay === 'function' ? fdIsoDay() : '';
      const isToday = !day || day === today;
      let accepted = false;

      if (isToday) {
        // Always read today's *fresh* MySQL budget, not the rendered dashboard cache.
        const data = await FlexiAPI.request('/dashboard');
        if (kcal > Number(data.budget.remaining_kcal) + 0.0001) {
          accepted = await confirmOverBudget({name:mealName,kcal,budget:data.budget});
          if (!accepted) return null; // Nothing is saved on Cancel, Escape or backdrop click.
        }
      }
      try {
        return await FlexiAPI.request('/meals', {
          method: 'POST',
          data: {...payload, ...(accepted ? {confirm_over_budget:true} : {})}
        });
      } catch (e) {
        // Another tab may have modified the budget between the pre-check and POST.
        // Re-read the authoritative numbers and ask again before retrying once.
        if (e.code !== 'BUDGET_EXCEEDED' || !isToday || accepted) throw e;
        const latest = await FlexiAPI.request('/dashboard');
        const confirmed = await confirmOverBudget({name:mealName,kcal,budget:latest.budget});
        if (!confirmed) return null;
        return await FlexiAPI.request('/meals', {
          method:'POST',data:{...payload,confirm_over_budget:true}
        });
      }
    } finally {
      isSaving = false;
    }
  }

  return {saveMeal,confirmOverBudget,estimateKcal};
})();
