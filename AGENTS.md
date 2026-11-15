# تعليمات الوكلاء — Maatify-ltd/php-category

## 1. نطاق المشروع

هذا المستودع مكتبة PHP مستقلة قابلة لإعادة الاستخدام عبر Composer، ويمثل
`Category` و`Category Content` و`Content Fields` و`Image Roles` و`Image Assignments`
ضمن حزمة تُوزَّع من جذر المستودع.

القواعد العامة لا تُعاد كتابتها هنا. مصدر اعتماد المعايير المحلي وسجل تركيبها
هو [`docs/php-engineering-standards/STANDARDS_MANIFEST.md`](docs/php-engineering-standards/STANDARDS_MANIFEST.md)،
وتملك آلية الاختيار والتثبيت وثيقة
[`STANDARDS_ADOPTION_STANDARD_AR.md`](docs/php-engineering-standards/standards/STANDARDS_ADOPTION_STANDARD_AR.md).

## 2. نموذج Selective Pinned Adoption

تعتمد المكتبة مجموعة انتقائية مثبتة من مستودع
`Maatify/php-engineering-standards`. يحدد الـManifest المحلي:

- الـ upstream repository والـ exact adoption commit.
- Profile Activations والـ scopes والـ inheritance.
- Pinned Adoption Control Set.
- Pinned Applicable Standards Set وإصداراتها المصدرية.
- Additional Standards والاستثناءات أو الـ overrides.

قبل أي مهمة، اقرأ هذا الملف والـManifest، ثم حدد Profile Scope للمسارات
المتأثرة واقرأ المعايير المنطبقة المسجلة فيه فقط. استخدم النسخ المحلية المثبتة؛
المهمة الهندسية العادية لا تحتاج اتصالًا بـ upstream ولا تعتمد على `main`
المتحرك. عند تنفيذ Adoption أو Upgrade أو Manifest Validation، أعد التحقق من
الـProfile manifests المحلية والـ exact commit المطلوب وفق Adoption Standard.

لا تُعد نسخ مجلد `standards/` كاملًا، ولا تنشئ مصدرًا موازيًا للقواعد داخل
المشروع. لا تُعدّل الملفات المنسوخة من المعايير أثناء بناء المكتبة؛ أي ترقية
لها تغيير مستقل ومراجعة مستقلة. لا توجد `AGENTS.md` تابعة داخل نسخة المعايير؛
ولا تُنسخ audits أو decisions التاريخية إلى adoption set.

## 3. Profile Activations الخاصة بالمشروع

- `repository-governance` على Scope: `/`.
- `composer-package` على Scope: `/`.
- التفعيلان مباشران دون inheritance. يبيّن الـManifest سبب انطباقهما
  على الحزمة المستقلة.

يجب أن يظل الـManifest هو المرجع القابل للتدقيق للنسخ والإصدارات والعلاقات؛ لا
تكرر هذه البيانات أو قائمة المعايير كاملة داخل هذا الملف.

## 4. حدود Category

المكتبة تملك فقط:

- Category وCategory Content وContent Fields وImage Roles وImage Assignments
  والعقود والـDTOs والاستثناءات الخاصة بها.
- orchestration الخاص بالمجال وطبقات PDO والجداول المملوكة للحزمة.
- schema MySQL المملوك للحزمة واختبارات التكامل مع الخدمة الحقيقية.

المضيف يملك Dependency Injection وHTTP وRoutes وPermissions وsemantic Language
validation وfallback/locale policy وPresentation، وأي علاقات مع جداول Host.
وتملك الحزمة syntactic/storage validation الخاصة بقيم `language_code` غير
الفارغة المطلوبة بعقدها وRuntime؛ أما `NULL` فهو هوية المحتوى غير المحلي.
لا تُضاف داخل هذه الحزمة Catalog أو Product أو Pricing أو
Inventory أو Media أو Framework bindings.

التفاصيل المستقرة الخاصة بالعقود والمعمارية موجودة في
[`CATEGORY_PACKAGE_REFERENCE.md`](CATEGORY_PACKAGE_REFERENCE.md)، والـschema في
[`schema/README.md`](schema/README.md). لا تنشئ Package Reference منافسًا داخل
`docs/`.

## 5. القراءة والتنفيذ

ابدأ كل مهمة بإعادة بناء حالة Git الفعلية: branch وHEAD وworking tree وstaged
state والـbase الفعلي والـPRs ذات الصلة. طبّق تعليمات المالك الحالية، ثم
تعليمات هذا الملف، ثم المصادر المرجعية والـstandards المحلولة من الـManifest.
لا تستنتج حالة من تقرير سابق دون إعادة التحقق.

حافظ على النطاق المغلق، وامتلك فقط الملفات اللازمة للمهمة. لا تُعدّل runtime أو
schema أو Composer أو CI أو tests أو الوثائق الأخرى لمجرد تحسين جانبي؛ أي توسع
يحتاج دليلًا مباشرًا وصلاحية صريحة. لا تنفذ Commit أو Push أو Merge أو تفتح PR
إلا عندما تسمح المهمة بذلك. استخدم staging لمسارات صريحة فقط، ولا تستخدم
`git add .` أو `git add -A`.

تُطبق دورة Phase Stack على العمل ذي التغيير: يبدأ من أحدث `main` موثق، ثم Phase
Draft، ثم Work Units وComponent PRs إلى الـDraft. بعد اعتماد Scope، يدير
المساعد القائد التنفيذ والمراجعة والـGates ضمن Standing Execution Authority.
وفق §8.4 من
[`GITHUB_PHASE_STACK_WORKFLOW_AR.md`](docs/php-engineering-standards/standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md)،
كل GitHub Merge يحتاج تصريحًا صريحًا من المالك لذلك الدمج، بما فيه Squash Merge
من Component PR إلى Phase Draft. لا توجد Standing Merge Authority للمساعد
القائد. يظل دمج الـPhase Draft إلى `main` قرارًا مستقلًا ومحصورًا بمالك
المشروع. تبقى عمليات Git المحلية، ومنها `git merge`، خاضعة لصلاحياتها الصريحة.
تُعتبر Verification أو Final Review Component فقط إذا نتج عنها تغيير مستودعي
مستقل.

لغة التعاون والتقارير العربية افتراضيًا، مع إبقاء أسماء الملفات والأوامر وGit
SHAs والمصطلحات التقنية بصيغتها الأصلية عند الحاجة للدقة.

## 6. سياسة RC Hardening المحلية

في هذه المكتبة، يعمل المساعد القائد بصفته Technical Lead / Architect / Reviewer
/ Coordinator: يعيد بناء الحالة، ويحدد الفجوات والنطاق، ويوجه المنفذ، ويراجع
التغييرات والأدلة، ويطلب الإصلاحات، ويدير بيانات PR، ويعتمد Child Work Unit
وينسّق Squash Merge إلى Phase Draft بعد تصريح المالك الصريح لذلك الدمج. تنفيذ
تغييرات المستودع من مسؤولية المنفذ.

أي Work Unit تنتج تغييرًا مستودعيًا في RC Hardening تتبع المسار:

```text
Phase Draft → fresh child branch → Draft Child PR targeting Phase Draft
→ implementation → review/fixes → verification → acceptance
→ explicit Owner authorization for this merge → Squash Merge to Phase Draft
```

تستهدف Child PRs الـPhase Draft النشطة التي اعتمدها المالك للمرحلة الحالية.
يُمنع commit مباشر على Phase Draft، أو Child PR إلى `main`، أو دمج Work Unit
غير مكتملة، أو اعتبار Verification بديلًا عن Acceptance. يظل دمج Phase Draft
إلى `main` قرارًا مستقلًا للمالك.
