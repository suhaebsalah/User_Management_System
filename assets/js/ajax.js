
// هذا الملف مسؤول عن كل عمليات AJAX الخاصة بالمستخدمين (إضافة - تعديل - حذف - جلب البيانات)
$(document).ready(function () {
	// تفعيل وضع CRUD عبر AJAX حتى تتوافق الدوال في app.js مع هذا السلوك
	window.enableAjaxCrud = true;

	// مسار واجهة الـ API الرئيسية التي تستقبل كل الأوامر
	const apiUrl = "api.php";

	// دالة عرض تنبيه أعلى الصفحة (نجاح/خطأ)
	function showAjaxAlert(message, type) {
		// تحديد كلاس CSS المناسب حسب نوع الرسالة
		const cssType = type === "success" ? "app-alert-success" : "app-alert-error";
		// البحث عن صندوق التنبيه الحالي إذا كان موجودا
		let alertBox = $("#global-alert");

		// إذا لم يكن موجودا ننشئه ونضيفه أعلى المحتوى الرئيسي
		if (!alertBox.length) {
			alertBox = $("<div id='global-alert' class='app-alert'></div>");
			$("main.main").prepend(alertBox);
		}

		// تحديث الشكل والنص ثم إظهار التنبيه
		alertBox.removeClass("app-alert-success app-alert-error hide").addClass(cssType).text(message).show();

		// إخفاء التنبيه تلقائيا بعد مدة قصيرة ثم حذفه من الـ DOM
		setTimeout(function () {
			alertBox.addClass("hide");
			setTimeout(function () {
				alertBox.remove();
			}, 260);
		}, type === "success" ? 2200 : 4200);
	}

	// دالة حقن رسائل الأخطاء أسفل الحقول داخل الفورم
	function setFieldErrors(formSelector, errors) {
		// الحصول على الفورم المطلوب (إضافة أو تعديل)
		const $form = $(formSelector);
		// مسح أي أخطاء سابقة قبل كتابة الأخطاء الجديدة
		$form.find(".form-error").text("");
		// إذا لم توجد أخطاء نخرج مباشرة
		if (!errors) {
			return;
		}

		// المرور على كل حقل فيه خطأ وإظهاره في مكانه الصحيح
		Object.keys(errors).forEach(function (field) {
			const msg = errors[field] || "";

			// معالجة خاصة بخطأ الصورة لأن مكانه ليس داخل form-field عادي
			if (field === "upload_image") {
				if (formSelector === "#add-user-form") {
					$("#img-error").text(msg);
				} else {
					$("#e-img-error").text(msg);
				}
				return;
			}

			// الوصول للحقل بالاسم نفسه
			const $field = $form.find("[name='" + field + "']").first();
			if ($field.length) {
				// جلب أول عنصر form-error في نفس البلوك وكتابة الرسالة
				const $error = $field.closest(".form-field").find(".form-error").first();
				if ($error.length) {
					$error.text(msg);
				}
			}
		});
	}

	// دالة تحقق سريعة قبل الإرسال: الحقول الإلزامية لا تكون فارغة
	function validateRequiredFields(formSelector) {
		const $form = $(formSelector);
		// أسماء الحقول الإلزامية المطلوبة في الفورم
		const requiredNames = ["first_name", "last_name", "email", "department_id", "role_id", "salary"];
		const errors = {};

		// المرور على كل حقل إلزامي وفحص قيمته
		requiredNames.forEach(function (name) {
			const $field = $form.find("[name='" + name + "']").first();
			if (!$field.length) {
				return;
			}

			const rawValue = $field.val();
			const value = (rawValue === null || rawValue === undefined) ? "" : String(rawValue).trim();
			if (value === "") {
				errors[name] = "Please, the input is empty.";
			}
		});

		// إذا وجدنا أخطاء نعرضها ونمنع الإرسال
		if (Object.keys(errors).length > 0) {
			setFieldErrors(formSelector, errors);
			return false;
		}

		// إذا لا توجد أخطاء نسمح بالإرسال
		return true;
	}

	// تنسيق الأرقام بفواصل (مثال: 12345 -> 12,345)
	function formatNumber(value) {
		return Number(value || 0).toLocaleString("en-US");
	}

	// تحديث بطاقات الإحصائيات أعلى الصفحة بدون عمل Refresh كامل
	function refreshDashboardStats() {
		$.ajax({
			url: apiUrl,
			method: "POST",
			dataType: "json",
			// طلب إجراء جلب الإحصائيات من API
			data: { action: "get_stats" },
			success: function (response) {
				// حماية: إذا الرد غير صالح لا نفعل شيئا
				if (!response || response.status !== "success" || !response.stats) {
					return;
				}

				// كتابة القيم الجديدة في بطاقات الإحصائيات
				$(".stat-value.total").text(response.stats.total || 0);
				$(".stat-value.active").text(response.stats.active || 0);
				$(".stat-value.inactive").text(response.stats.inactive || 0);
				$(".stat-value.salary").text("$" + formatNumber(response.stats.salary_sum || 0));
			}
		});
	}

	// تحديث صفوف الجدول (المستخدمون) بنفس فلترة البحث الحالية
	function refreshUsersList() {
		const $tableBody = $("#table-body");
		if (!$tableBody.length) {
			return;
		}

		// تجهيز باراميترات الفلترة الحالية لإعادة تحميل النتائج
		const payload = {
			value: ($("#search").val() || "").toString().trim().toLowerCase(),
			department: ($("#dept-filter").val() || "").toString().trim(),
			status: ($("#status-filter").val() || "").toString().trim(),
			salary_min: parseInt($("#sal-min").val(), 10) || 0,
			salary_max: parseInt($("#sal-max").val(), 10) || (parseInt($("#sal-max").attr("max"), 10) || 10000),
			offset: 0,
			limit: parseInt($tableBody.attr("data-limit"), 10) || 10,
			format: "json"
		};

		$.ajax({
			url: "seach_living.php",
			method: "POST",
			dataType: "json",
			data: payload,
			success: function (response) {
				// استخراج HTML الصفوف + أرقام التحميل من الرد
				const html = response && response.html ? response.html : "";
				const total = response && response.total ? parseInt(response.total, 10) : 0;
				const loaded = response && response.loaded ? parseInt(response.loaded, 10) : 0;

				// استبدال الصفوف الحالية بنتائج جديدة
				$tableBody.html(html);
				// حفظ أرقام المساعدة للتحميل/العد
				$tableBody.attr("data-offset", loaded);
				$tableBody.attr("data-total-users", total);

				// تحديث نص العداد أعلى الجدول
				$(".result-count").text("Showing " + loaded + " of " + total + " users");
				// تحديث إجمالي المستخدمين في البطاقة الأولى بشكل مباشر
				$(".stat-value.total").text(total);

				// إظهار حالة فارغة إذا لا توجد نتائج
				if (loaded === 0) {
					$(".table-wrap").hide();
					$("#empty-state").addClass("visible");
				} else {
					$(".table-wrap").show();
					$("#empty-state").removeClass("visible");
				}

				// إعادة تطبيق دالة render العامة لو موجودة
				if (typeof render === "function") {
					render();
				}
			}
		});
	}

	// تعبئة نموذج التعديل ببيانات المستخدم القادمة من السيرفر
	function fillEditForm(user) {
		$("#e-id").val(user.id || "");
		$("#e-first").val(user.first_name || "");
		$("#e-last").val(user.last_name || "");
		$("#e-email").val(user.email || "");
		$("#e-dept").val(user.department_id || "");
		$("#e-role").val(user.role_id || "");
		$("#e-salary").val(user.salary || "");
		$("#edit-user-form select[name='status']").val(user.status || "Inactive");
		$("#e-info").val(user.info || "");
		$("#e-old-image").val(user.image_name || "");
		$("#e-remove-image").val("0");
		$("#e-pending-avatar-data").val("");
		$("#e-img-input").val("");
		$("#e-img-error").text("");

		// إذا المستخدم لديه صورة نعرضها
		if (user.image_name) {
			const safeImage = String(user.image_name).replace(/"/g, "&quot;");
			$("#e-av-preview").html("<img src='assets/uploads/thumbs/" + safeImage + "' alt='Avatar' width='70' height='70' loading='lazy' onerror=\"this.onerror=null;this.src='assets/uploads/" + safeImage + "';\">");
			$("#btn-remove-edit-img").show();
		} else {
			// إذا لا توجد صورة نعرض الأحرف الأولى للاسم
			const first = (user.first_name || "").toString().charAt(0);
			const last = (user.last_name || "").toString().charAt(0);
			const initials = (first + last) || "?";
			$("#e-av-preview").html("<span id='e-av-initials'>" + initials + "</span>");
			$("#btn-remove-edit-img").hide();
		}
	}

	// جلب بيانات مستخدم واحد بالـ id ثم فتح مودال التعديل
	function openEditById(id) {
		if (!id) {
			return;
		}

		$.ajax({
			url: apiUrl,
			method: "POST",
			dataType: "json",
			data: {
				action: "get_user",
				id: id
			},
			success: function (response) {
				// لو فشل الجلب نظهر رسالة خطأ
				if (!response || response.status !== "success" || !response.user) {
					showAjaxAlert((response && response.message) ? response.message : "Could not load user data.", "error");
					return;
				}

				// تعبئة البيانات وفتح المودال
				fillEditForm(response.user);
				$("#edit-modal-bg").addClass("open");
			},
			error: function () {
				showAjaxAlert("Could not load user data.", "error");
			}
		});
	}

	// التقاط الحدث القادم من app.js عند الضغط على زر Edit
	document.addEventListener("ajax:open-edit", function (event) {
		const id = event && event.detail ? event.detail.id : "";
		openEditById(id);
	});

	// عند إرسال نموذج الإضافة
	$("#add-user-form").on("submit", function (e) {
		// منع الإرسال التقليدي
		e.preventDefault();

		// تنظيف الأخطاء السابقة
		setFieldErrors("#add-user-form", null);
		$("#img-error").text("");
		// تحقق محلي قبل الإرسال
		if (!validateRequiredFields("#add-user-form")) {
			return;
		}

		// جمع بيانات الفورم (يشمل الملفات)
		const formData = new FormData(this);
		// تحديد الإجراء المطلوب في API
		formData.append("action", "insert_user");

		$.ajax({
			url: apiUrl,
			method: "POST",
			data: formData,
			dataType: "json",
			// مهم مع FormData
			processData: false,
			contentType: false,
			success: function (response) {
				// معالجة حالة فشل الإضافة
				if (!response || response.status !== "success") {
					setFieldErrors("#add-user-form", response ? response.errors : null);
					if (!(response && response.errors)) {
						showAjaxAlert((response && response.message) ? response.message : "Could not insert user.", "error");
					}
					return;
				}

				// في حالة النجاح: تصفير الفورم ومسح بيانات الصورة المؤقتة
				$("#add-user-form")[0].reset();
				$("#pending-avatar-data").val("");
				sessionStorage.removeItem("pending_user_avatar");
				if (typeof setAvatarPreview === "function") {
					setAvatarPreview("", "", "");
				}
				$("#btn-remove-add-img").hide();
				// إغلاق مودال الإضافة
				closeModal();

				// إظهار رسالة نجاح وتحديث الجدول والإحصائيات
				showAjaxAlert(response.message || "User added successfully.", "success");
				refreshUsersList();
				refreshDashboardStats();
			},
			error: function () {
				showAjaxAlert("Could not insert user.", "error");
			}
		});
	});

	// عند إرسال نموذج التعديل
	$("#edit-user-form").on("submit", function (e) {
		// منع الإرسال التقليدي
		e.preventDefault();

		// تنظيف الأخطاء السابقة
		setFieldErrors("#edit-user-form", null);
		$("#e-img-error").text("");
		// تحقق محلي قبل الإرسال
		if (!validateRequiredFields("#edit-user-form")) {
			return;
		}

		// جمع بيانات فورم التعديل
		const formData = new FormData(this);
		// تحديد الإجراء المطلوب في API
		formData.append("action", "update_user");

		$.ajax({
			url: apiUrl,
			method: "POST",
			data: formData,
			dataType: "json",
			processData: false,
			contentType: false,
			success: function (response) {
				// معالجة حالة فشل التعديل
				if (!response || response.status !== "success") {
					setFieldErrors("#edit-user-form", response ? response.errors : null);
					if (!(response && response.errors)) {
						showAjaxAlert((response && response.message) ? response.message : "Could not update user.", "error");
					}
					return;
				}

				// في حالة النجاح: إغلاق المودال وتحديث الجدول والإحصائيات
				closeEditModal();
				showAjaxAlert(response.message || "User updated successfully.", "success");
				refreshUsersList();
				refreshDashboardStats();
			},
			error: function () {
				showAjaxAlert("Could not update user.", "error");
			}
		});
	});

	// عند الضغط على تأكيد الحذف
	$("#confirm-btn").on("click.ajaxcrud", function (e) {
		// منع السلوك الافتراضي للزر
		e.preventDefault();

		// جلب id العنصر المحدد للحذف
		const id = (window.deleteId || "").toString();
		if (!id) {
			return;
		}

		$.ajax({
			url: apiUrl,
			method: "POST",
			dataType: "json",
			data: {
				action: "delete_user",
				id: id
			},
			success: function (response) {
				// إذا فشل الحذف نعرض رسالة خطأ
				if (!response || response.status !== "success") {
					showAjaxAlert((response && response.message) ? response.message : "Could not delete user.", "error");
					return;
				}

				// إغلاق مودال التأكيد إذا الدالة موجودة
				if (typeof closeConfirm === "function") {
					closeConfirm();
				}

				// تصفير متغيرات الحذف العامة
				window.deleteId = "";
				window.selectedRow = null;

				// إظهار نجاح وتحديث الجدول والإحصائيات
				showAjaxAlert(response.message || "User deleted successfully.", "success");
				refreshUsersList();
				refreshDashboardStats();
			},
			error: function () {
				showAjaxAlert("Could not delete user.", "error");
			}
		});
	});
});