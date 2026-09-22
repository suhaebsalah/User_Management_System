function closeEditModal() {
	if (window.enableAjaxCrud) {
		const modal = document.getElementById('edit-modal-bg');
		if (modal) modal.classList.remove('open');
		return;
	}
	window.location.href = "index.php";
}

// ===== THEME =====
function toggleTheme() {
	const html = document.documentElement;
	const isDark = html.classList.toggle('dark');
	localStorage.setItem('theme', isDark ? 'dark' : 'light');
	
	const iconSun = document.getElementById('icon-sun');
	const iconMoon = document.getElementById('icon-moon');
	if (iconSun) iconSun.style.display = isDark ? 'block' : 'none';
	if (iconMoon) iconMoon.style.display = isDark ? 'none' : 'block';
}

(function () {
	const savedTheme = localStorage.getItem('theme');
	const isDark = savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches);
	if (isDark) {
		document.documentElement.classList.add('dark');
		const iconSun = document.getElementById('icon-sun');
		const iconMoon = document.getElementById('icon-moon');
		if (iconSun) iconSun.style.display = 'block';
		if (iconMoon) iconMoon.style.display = 'none';
	} else {
		document.documentElement.classList.remove('dark');
		const iconSun = document.getElementById('icon-sun');
		const iconMoon = document.getElementById('icon-moon');
		if (iconSun) iconSun.style.display = 'none';
		if (iconMoon) iconMoon.style.display = 'block';
	}
})();

function openModal() {
	const modal = document.getElementById('modal-bg');
	if (modal) {
		modal.classList.add('open');
	}
}

function closeModal() {
	const modal = document.getElementById('modal-bg');
	if (modal) modal.classList.remove('open');
}

// ==================== Avatar Preview ====================
// تحديث الأحرف الأولى من الاسم في معاينة الصورة الرمزية
function updateInitials() {
	if (!imgData) {
		setAvatarPreview(document.getElementById('f-first').value, document.getElementById('f-last').value, '');
	}
}

// تعيين معاينة الصورة الرمزية - إما صورة أو أحرف أولية
function setAvatarPreview(first, last, img) {
	const p = document.getElementById('av-preview');
	if (img) {
		p.innerHTML = `<img src="${img}" alt="">`;
		return;
	}
	const ini = ((first || '')[0] || '') + ((last || '')[0] || '');
	p.innerHTML = `<span id="av-initials">${ini || '?'}</span>`;
}

// عرض رسالة خطأ تحقق الصورة
function showImageError(message) {
	const errorEl = document.getElementById('img-error');
	if (!errorEl) return;
	errorEl.textContent = message || '';
}

// معالجة اختيار صورة المستخدم - التحقق من النوع والحجم وقراءة كـ Base64
function handleImg(input) {
	const file = input.files[0];
	if (!file) {
		showImageError('');
		return;
	}

	const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
	const maxSize = 2 * 1024 * 1024;

	if (!allowedTypes.includes(file.type)) {
		showImageError('Invalid image type. Use JPG, PNG, GIF, or WEBP.');
		input.value = '';
		imgData = '';
		sessionStorage.removeItem('pending_user_avatar');
		syncPendingAvatarField('');
		setAvatarPreview('', '', '');
		return;
	}

	if (file.size > maxSize) {
		showImageError('Image size must be 2MB or less.');
		input.value = '';
		imgData = '';
		sessionStorage.removeItem('pending_user_avatar');
		syncPendingAvatarField('');
		setAvatarPreview('', '', '');
		return;
	}

	showImageError('');
	const r = new FileReader();
	r.onload = (e) => {
		imgData = e.target.result;
		sessionStorage.setItem('pending_user_avatar', imgData);
		syncPendingAvatarField(imgData);
		setAvatarPreview('', '', imgData);
		const btnRemove = document.getElementById('btn-remove-add-img');
		if(btnRemove) btnRemove.style.display = 'inline-block';
	};
	r.readAsDataURL(file);
}

// معالجة اختيار صورة المستخدم في نافذة التعديل
function handleImgEdit(input) {
	const file = input.files[0];
	const errorEl = document.getElementById('e-img-error');
	if (!file) {
		if (errorEl) errorEl.textContent = '';
		return;
	}

	const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
	const maxSize = 2 * 1024 * 1024;

	if (!allowedTypes.includes(file.type)) {
		if (errorEl) errorEl.textContent = 'Invalid image type. Use JPG, PNG, GIF, or WEBP.';
		input.value = '';
		document.getElementById('e-pending-avatar-data').value = '';
		setEditAvatarPreview('', '', '');
		return;
	}

	if (file.size > maxSize) {
		if (errorEl) errorEl.textContent = 'Image size must be 2MB or less.';
		input.value = '';
		document.getElementById('e-pending-avatar-data').value = '';
		setEditAvatarPreview('', '', '');
		return;
	}

	if (errorEl) errorEl.textContent = '';
	const r = new FileReader();
	r.onload = (e) => {
		const result = e.target.result;
		document.getElementById('e-pending-avatar-data').value = result;
		setEditAvatarPreview('', '', result);
		const btnRemove = document.getElementById('btn-remove-edit-img');
		if(btnRemove) btnRemove.style.display = 'inline-block';
		document.getElementById('e-remove-image').value = '0';
	};
	r.readAsDataURL(file);
}

function removeSelectedImage(type) {
	if (type === 'add') {
		document.getElementById('img-input').value = '';
		imgData = '';
		sessionStorage.removeItem('pending_user_avatar');
		syncPendingAvatarField('');
		setAvatarPreview(document.getElementById('f-first').value, document.getElementById('f-last').value, '');
		const btnRemove = document.getElementById('btn-remove-add-img');
		if(btnRemove) btnRemove.style.display = 'none';
	} else if (type === 'edit') {
		document.getElementById('e-img-input').value = '';
		document.getElementById('e-pending-avatar-data').value = '';
		setEditAvatarPreview('', '', '');
		const btnRemove = document.getElementById('btn-remove-edit-img');
		if(btnRemove) btnRemove.style.display = 'none';
		document.getElementById('e-remove-image').value = '1';
	}
}

function setEditAvatarPreview(first, last, img) {
	const p = document.getElementById('e-av-preview');
	if (img) {
		p.innerHTML = `<img src="${img}" alt="">`;
		return;
	}
	const elFirst = document.getElementById('e-first');
	const elLast = document.getElementById('e-last');
	const f = first || (elFirst ? elFirst.value : '');
	const l = last || (elLast ? elLast.value : '');
	const ini = (f[0] || '') + (l[0] || '');
	p.innerHTML = `<span id="e-av-initials">${ini || '?'}</span>`;
}

// استرجاع الصورة المختارة المحفوظة في الجلسة عند فتح النافذة
// (في حالة حدوث خطأ في التحقق من البيانات ولم تُغلق النافذة)
(function restorePendingAvatar() {
	const modalBg = document.getElementById('modal-bg');
	if (!modalBg) return;

	const cachedAvatar = sessionStorage.getItem('pending_user_avatar');
	if (modalBg.classList.contains('open') && cachedAvatar) {
		imgData = cachedAvatar;
		syncPendingAvatarField(cachedAvatar);
		setAvatarPreview('', '', cachedAvatar);
		return;
	}

	sessionStorage.removeItem('pending_user_avatar');
	syncPendingAvatarField('');
})();

// ==================== Sync Pending Avatar Field ====================
function syncPendingAvatarField(data) {
	const field = document.getElementById('pending-avatar-data');
	if (field) {
		field.value = data || '';
	}
}

// ==================== Toast ====================
// عرض رسالة إخطار مؤقتة في أسفل الشاشة
function showToast(msg) {
	const t = document.getElementById('toast');
	document.getElementById('toast-msg').textContent = msg;
	t.classList.add('visible');
	setTimeout(() => t.classList.remove('visible'), 2500);
}

// ==================== Delete Confirm ====================
// فتح نافذة تأكيد حذف المستخدم من الجدول
function openDeleteConfirmFromRow(row) {
	selectedRow = row; // حفظ الصف الحالي الذي ضغط عليه المستخدم
	deleteId = row.dataset.id; // أخذ معرف المستخدم من data-id داخل صف الجدول
	document.getElementById('confirm-bg').classList.add('open'); // فتح نافذة تأكيد الحذف
}

function closeConfirm() {
	document.getElementById('confirm-bg').classList.remove('open'); // إغلاق نافذة تأكيد الحذف
	selectedRow = null; // تفريغ الصف المحدد بعد الإغلاق
}

// ==================== Keyboard Escape ====================
// إغلاق النوافذ عند الضغط على زر Escape
document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') {
		closeModal();
		closeEditModal();
		closeConfirm();
	}
});

// ==================== Confirm Delete Button ====================
// تنفيذ عملية الحذف عند النقر على زر تأكيد الحذف
document.getElementById('confirm-btn').addEventListener('click', () => {
	if (window.enableAjaxCrud) {
		return;
	}
	if (deleteId) { // التأكد أن هناك معرف صالح للحذف
		window.location.href = "delete.php?id=" + deleteId; // الانتقال لملف الحذف لتنفيذ حذف السجل نهائيا
	}
});

// ==================== Table Button Delegation ====================
// معالجة جميع نقرات الأزرار في جدول المستخدمين (تحرير/حذف)
document.addEventListener('click', (e) => {
	const btn = e.target.closest('.actions-cell .btn');
	if (!btn) return;
	const row = btn.closest('tr');
	if (!row) return;
	const action = btn.dataset.action;

	if (action === 'edit') {
		e.preventDefault();
		// استخدم id مباشرة من data-id
		const id = row.dataset.id || btn.dataset.id;
		openEditModal(id);
		return;
	}

	if (action === 'delete') {
		openDeleteConfirmFromRow(row); // عند الضغط على زر Delete يتم فتح مودال التأكيد
	}
});
// فتح نافذة التعديل عبر id فقط (النمط الجديد)
function openEditModal(idOrRow) {
	if (window.enableAjaxCrud && (typeof idOrRow === 'number' || (typeof idOrRow === 'string' && idOrRow !== ''))) {
		const event = new CustomEvent('ajax:open-edit', { detail: { id: String(idOrRow) } });
		document.dispatchEvent(event);
		return;
	}

	// دعم النمط الجديد: ID مباشر
	if (typeof idOrRow === 'number' || (typeof idOrRow === 'string' && idOrRow !== '')) {
		window.location.href = "index.php?edit_id=" + encodeURIComponent(idOrRow);
		return;
	}

	// دعم احتياطي للنمط القديم إذا جاء row وفيه data-id
	if (idOrRow && idOrRow.dataset && idOrRow.dataset.id) {
		window.location.href = "index.php?edit_id=" + encodeURIComponent(idOrRow.dataset.id);
		return;
	}

	// فتح محلي فقط عند الحاجة
	const modal = document.getElementById('edit-modal-bg');
	if (modal) modal.classList.add('open');
}

// تحديث نص نطاق الراتب الظاهر للمستخدم
function updateSalLabel() {
	const minInput = document.getElementById('sal-min');
	const maxInput = document.getElementById('sal-max');
	const label = document.getElementById('sal-range-lbl');
	if (!minInput || !maxInput || !label) return;

	let min = parseInt(minInput.value, 10) || 0;
	let max = parseInt(maxInput.value, 10) || 0;

	
	if (min > max) {
		const temp = min;
		min = max;
		max = temp;
	}

	label.textContent = min.toLocaleString('en-US').replace(/,/g, ' ') + ' - ' + max.toLocaleString('en-US').replace(/,/g, ' ') + '$';
}

function render() {
	const body = document.getElementById('table-body');
	if (!body) return;
	const rows = body.querySelectorAll('tr');
	const visibleRows = rows.length;

	const resultCount = document.querySelector('.result-count');
	if (resultCount) {
		const total = parseInt(body.getAttribute('data-total-users'), 10) || rows.length;
		resultCount.textContent = 'Showing ' + visibleRows + ' of ' + total + ' users';
	}

	const tableWrap = document.querySelector('.table-wrap');
	const emptyState = document.getElementById('empty-state');
	if (tableWrap && emptyState) {
		if (rows.length === 0) {
			tableWrap.style.display = 'none';
			emptyState.classList.add('visible');
		} else {
			tableWrap.style.display = '';
			emptyState.classList.remove('visible');
		}
	}
}

function resetFilters() {
	const dept = document.getElementById('dept-filter');
	const status = document.getElementById('status-filter');
	const salMin = document.getElementById('sal-min');
	const salMax = document.getElementById('sal-max');

	if (dept) dept.value = '';
	if (status) status.value = '';
	if (salMin) salMin.value = '0';
	if (salMax) salMax.value = salMax.max || '10000';
	updateSalLabel();
	const search = document.getElementById('search');
	if (search) search.value = '';
	render();
}
  
// ==================== Initialize ====================
updateSalLabel();
render();

