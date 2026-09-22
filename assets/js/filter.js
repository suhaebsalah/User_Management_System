// filter.js
// كود البحث والتصفية في جدول المستخدمين
// هذا الملف يجب ربطه في صفحة index.php بعد تحميل jQuery

$(document).ready(function() {
    // البحث المباشر + التحميل التدريجي (10 سجلات في كل مرة)
    let debounceTimer = null;
    // يمنع إرسال أكثر من طلب في نفس اللحظة
    let isLoading = false;
    // هل توجد صفوف إضافية يمكن تحميلها؟
    let hasMoreRows = true;
    // آخر نص بحث تم عرضه فعليا في الجدول
    let displayedQuery = "";
    // أقصى راتب افتراضي مأخوذ من خصائص السلايدر
    const salaryMaxDefault = parseInt($("#sal-max").attr("max"), 10) || 10000;
    // الفلاتر الحالية المطبقة على النتائج المعروضة
    let displayedFilters = {
        department: "",
        status: "",
        salary_min: 0,
        salary_max: salaryMaxDefault
    };

    const $search = $("#search");
    const $tableBody = $("#table-body");
    const $tableWrap = $(".table-wrap");

    // قراءة قيم الفلاتر الحالية من عناصر الواجهة
    function getCurrentFilters() {
        const maxSalary = parseInt($("#sal-max").attr("max"), 10) || salaryMaxDefault;
        return {
            department: ($("#dept-filter").val() || "").trim(),
            status: ($("#status-filter").val() || "").trim(),
            salary_min: parseInt($("#sal-min").val(), 10) || 0,
            salary_max: parseInt($("#sal-max").val(), 10) || maxSalary
        };
    }

    // جلب حجم الصفحة (عدد السجلات في كل طلب)
    function getLimit() {
        return parseInt($tableBody.attr("data-limit"), 10) || 10;
    }

    // جلب موضع البداية الحالي للتحميل اللانهائي
    function getOffset() {
        return parseInt($tableBody.attr("data-offset"), 10) || 0;
    }

    // تحديث النص أعلى الجدول: عدد المعروض من الإجمالي
    function setCount(loaded, total) {
        $(".result-count").text("Showing " + loaded + " of " + total + " users");
    }

    // إظهار صف تحميل داخل الجدول
    // mode=replace: عند بحث/فلتر جديد
    // mode=append: عند تحميل المزيد في الأسفل
    function setLoadingRow(mode) {
        if (mode === "replace") {
            $tableBody.html("<tr class='loading-row'><td colspan='7'><div class='table-loading'><span class='table-loading-spinner'></span><span class='table-loading-text'>Loading users...</span></div></td></tr>");
            return;
        }
        if ($tableBody.find(".loading-row.loading-more").length === 0) {
            $tableBody.append("<tr class='loading-row loading-more'><td colspan='7'><div class='table-loading'><span class='table-loading-spinner'></span><span class='table-loading-text'>Loading more users...</span></div></td></tr>");
        }
    }

    // يقرر إن كانت الصفحة تحتاج تحميل تلقائي إضافي
    // مثال: إذا كانت النتائج قليلة ولا يوجد Scroll كافٍ
    function shouldAutoLoadMore() {
        const doc = document.documentElement;
        const pageShort = doc.scrollHeight <= (window.innerHeight + 20);
        const wrap = $tableWrap.get(0);
        let wrapHasNoVerticalScroll = false;
        if (wrap) {
            wrapHasNoVerticalScroll = wrap.scrollHeight <= (wrap.clientHeight + 2);
        }
        return pageShort || wrapHasNoVerticalScroll;
    }

    // نقطة موحدة لتحفيز تحميل المزيد عند الوصول للأسفل
    function handleBottomLoadTrigger() {
        if (!hasMoreRows || isLoading) return;
        loadUsers(false);
    }

    // الدالة الأساسية: تجلب المستخدمين من السيرفر حسب البحث والفلاتر
    // resetList=true  => استبدال النتائج الحالية بالكامل
    // resetList=false => إضافة نتائج جديدة أسفل النتائج الحالية
    function loadUsers(resetList, queryOverride, filtersOverride) {
        if (isLoading) return;
        if (!resetList && !hasMoreRows) return;
        const value = (typeof queryOverride === "string") ? queryOverride : displayedQuery;
        const filters = filtersOverride || displayedFilters;
        const limit = getLimit();
        const offset = resetList ? 0 : getOffset();
        isLoading = true;
        setLoadingRow(resetList ? "replace" : "append");
        $.ajax({
            // endpoint المسؤول عن البحث الحي من السيرفر
            url: "seach_living.php",
            method: "POST",
            dataType: "json",
            data: {
                value: value,
                department: filters.department,
                status: filters.status,
                salary_min: filters.salary_min,
                salary_max: filters.salary_max,
                offset: offset,
                limit: limit,
                format: "json"
            },
            success: function(response) {
                // قراءة البيانات المرجعة مع قيم افتراضية آمنة
                const html = response && response.html ? response.html : "";
                const loaded = response && response.loaded ? parseInt(response.loaded, 10) : 0;
                const total = response && response.total ? parseInt(response.total, 10) : 0;
                if (resetList) {
                    // بحث/فلتر جديد => استبدال كل الصفوف
                    $tableBody.html(html);
                    displayedQuery = value;
                    displayedFilters = filters;
                } else {
                    // تحميل إضافي => إزالة مؤشر التحميل ثم إلحاق النتائج
                    $tableBody.find(".loading-row.loading-more").remove();
                    $tableBody.append(html);
                }

                // تحديث حالة الصفحات والعدادات
                const newOffset = offset + loaded;
                $tableBody.attr("data-offset", newOffset);
                $tableBody.attr("data-total-users", total);
                hasMoreRows = newOffset < total;
                setCount(newOffset, total);

                // استدعاء render العامة (إن وجدت) للحفاظ على اتساق الواجهة
                if (typeof render === "function") {
                    render();
                }

                // إدارة حالة الجدول الفارغ
                if (newOffset === 0) {
                    $(".table-wrap").hide();
                    $("#empty-state").addClass("visible");
                } else {
                    $(".table-wrap").show();
                    $("#empty-state").removeClass("visible");
                }
            },
            complete: function() {
                // complete تعمل سواء success أو error
                isLoading = false;
                $tableBody.find(".loading-row.loading-more").remove();

                // إذا لا تزال المساحة فارغة وهناك نتائج، حمّل دفعة أخرى تلقائيا
                if (hasMoreRows && shouldAutoLoadMore()) {
                    setTimeout(function() {
                        handleBottomLoadTrigger();
                    }, 0);
                }
            }
        });
    }

    // البحث أثناء الكتابة مع debounce (تقليل عدد الطلبات)
    $search.on("keyup", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            hasMoreRows = true;
            const typedQuery = $search.val().toLowerCase().trim();
            loadUsers(true, typedQuery, getCurrentFilters());
        }, 250);
    });

    // عند تغيير أي فلتر: إعادة تحميل النتائج من أول صفحة
    $("#dept-filter, #status-filter, #sal-min, #sal-max").on("change input", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            hasMoreRows = true;
            const typedQuery = $search.val().toLowerCase().trim();
            loadUsers(true, typedQuery, getCurrentFilters());
        }, 120);
    });

    // التحميل اللانهائي عند الوصول لأسفل حاوية الجدول
    $tableWrap.on("scroll", function() {
        const el = this;
        const isAtBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;
        if (isAtBottom) {
            handleBottomLoadTrigger();
        }
    });

    // دعم إضافي: التحميل اللانهائي عند التمرير لأسفل الصفحة ككل
    $(window).on("scroll", function() {
        const nearPageBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 20;
        if (nearPageBottom) {
            handleBottomLoadTrigger();
        }
    });
});

// شرح بالعربي:
// هذا الكود يرسل طلبات بحث وفلاتر إلى السيرفر ويعرض النتائج مباشرة في الجدول بدون إعادة تحميل الصفحة.
// يجب تضمين jQuery في صفحتك قبل هذا الملف، ويجب أن تكون عناصر البحث والفلاتر موجودة في HTML.

