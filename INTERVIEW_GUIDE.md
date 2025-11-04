# Laravel Developer Interview Guide + Repo-Specific Technical Review

This guide contains a deep technical review of the provided Laravel repository and a comprehensive, research-backed interview corpus for Laravel developers across junior, mid, senior, and staff levels. It includes practical exercises, an evaluation rubric, and references to authoritative Laravel 11 documentation.

---

## How to use this document

- Hiring teams: use the review checklist and question bank to run structured, consistent interviews.
- Candidates: prepare with the Q&A and hands-on exercises; review repo findings for production-grade patterns.
- Engineering leads: adopt the improvement backlog for this codebase or similar Laravel apps.

---

## Executive summary (repo review)

Overall, the project demonstrates solid Laravel 11-era patterns:
- Clear domain modeling around courses → modules → hierarchical contents.
- Strong FormRequest validation with nested structures and custom messages.
- Policy-driven authorization (ownership and role-aware) with sensible defaults.
- Secure video streaming with HTTP Range support and path traversal protections.
- Migration design with foreign keys, cascade deletes, and performance indexes.

Key opportunities to harden and scale:
- Offload large media uploads to S3 with presigned multipart uploads and checksums; serve via temporary URLs.
- Improve update flow to avoid wholesale delete/recreate of the module/content tree; support partial updates via diffs or upserts.
- Expand automated tests (uploads, streaming range requests, policy rules, nested validation edge cases).
- Add operational tooling (queues + Horizon, log tailing with Pail, rate limiting, and cache strategy).

---

## Detailed technical review (repository)

Below, file and responsibility references are drawn from your repository structure.

### Domain model and relationships

- `app/Models/Course.php`
  - Fields: `user_id`, `title`, `description`, `category`, `thumbnail`, `feature_video`.
  - Relationships: `belongsTo(User)`, `hasMany(Module)->orderBy('order')`.
  - Notes: ordering is explicit; consider an index on `order` per course (exists via migrations/perf indexes).

- `app/Models/Module.php`
  - Fields: `course_id`, `title`, `description`, `order`.
  - Relationships: `belongsTo(Course)`, `hasMany(Content)` with two access patterns: `contents()` for root items and `allContents()` for the full set.
  - Notes: validates hierarchical content access; ensure both relations are used intentionally to avoid confusion.

- `app/Models/Content.php`
  - Fields: `module_id`, `parent_id`, `title`, `body`, `type`, `order`, `file_path`.
  - Relationships: self-referential `parent()` and `children()->orderBy('order')`.
  - Notes: Supports nested trees (text/video/document/quiz). Ensure constraints so `type` drives `file_path` optionality.

Schema & indexes (migrations):
- FKs with cascade deletes for `modules` and `contents` ensure clean teardown.
- Indexes on `user_id`, `category`, timestamps, and compound ordering (course/module → order fields) are present for query performance.

### Authorization and roles

- `app/Policies/CoursePolicy.php`
  - Allows viewing broadly; restricts create/update/delete to instructors/admins and owners.
  - `forceDelete` reserved for admins. Patterns align with Laravel 11 policy best practices.
- `app/Models/User.php`
  - Role helpers: `isAdmin()`, `isInstructor()`, `hasRole()`; `hasMany(Course)`; hashed password cast.
  - Suggestion: consider PHP 8.1+ `enum` for roles to reduce typos and centralize role lists.

### Validation

- `app/Http/Requests/StoreCourseRequest.php` and `UpdateCourseRequest.php`
  - Uses `prepareForValidation()` to decode `modules` JSON — good for API or form submissions.
  - Rules enforce structure for modules and nested contents (`children.*`) with allowed types (`text`, `video`, `document`, `quiz`).
  - Media constraints: thumbnails as images; `feature_video` size-limited.
  - Suggestions:
    - Centralize repetitive nested rules (custom Rule objects or dedicated validators) to reduce drift between store/update.
    - Add MIME sniffing + extension checks for uploads; ensure max file sizes are aligned with server/php.ini limits.

### Controllers and media handling

- `app/Http/Controllers/CourseController.php`
  - `store()` and `update()` wrap writes in transactions. Uploads saved to `public` disk; old files removed on update.
  - `createContentRecursive()` handles nested creation and per-type file storage under folders (videos/documents/files).
  - `show()` and `index()` return JSON with eager-loaded nested relations.
  - Suggestions:
    - Replace synchronous local uploads with S3 presigned uploads (multipart for large videos) to offload PHP workers and reduce timeouts.
    - In `update()`, avoid deleting/recreating all modules/contents. Consider:
      - Identify unchanged items by client-sent IDs, use `upsert()` and targeted deletions.
      - Optionally, version and soft-delete to preserve history and allow rollbacks.
    - Emit events (e.g., `CourseUpdated`, `ContentCreated`) to hook future async tasks (thumbnails, transcodes).

- `app/Http/Controllers/VideoStreamController.php`
  - Path normalization confines streaming to `storage/app/public`. Supports HTTP Range requests with correct headers.
  - Suggestions:
    - If moving to S3, prefer `Storage::disk('s3')->temporaryUrl()` and let the client stream directly.
    - If continuing server-side streaming: add cache headers (ETag/Last-Modified), strong content-type detection, rate limiting, and robust error responses.

### Performance and data access

- Eager-load nested relations in `index()`/`show()` to avoid N+1s; consider depth-aware loading to avoid excessive payloads.
- Add pagination for course lists and possibly lazily load heavy content trees.
- Where appropriate, cache public course payloads keyed by slug/ID and bust on update.

### Observability and operations

- Logging: use contextual logs for uploads/deletes/streaming; adopt Laravel Pail for real-time dev logs.
- Queues: if adding media processing, adopt queues and Horizon for monitoring; segregate queues by workload.
- Rate limiting: protect mutation routes and streaming endpoints.
- Security headers: ensure sensible defaults (can be enforced via middleware or server config).

### Security checklist (targeted)

- Inputs: sanitize and validate all nested arrays; reject unknown content `type`.
- File uploads: enforce MIME/type, extension, and size; scan or restrict dangerous types; never serve from `storage/app` without validation.
- Path traversal: currently mitigated in streaming controller — keep tests for it.
- AuthZ: ensure policy checks guard all mutations and sensitive views.
- CSRF/CORS: ensure correct CORS for APIs; CSRF for web; double-check Sanctum stateful domains if SPA is planned.
- Rate limit: apply to create/update/delete and any expensive endpoints.

### Testing strategy (what to add next)

- Feature: create/update/destroy course with nested modules/contents; verify DB state and file storage using `Storage::fake()`.
- Policy: owner vs non-owner for update/delete; admin overrides; instructor create.
- Validation: nested `children` edge cases, invalid `type` combinations, missing files.
- Streaming: Range request tests (206 responses, correct headers, partial body size) and path traversal rejection.
- Performance: use `expectsDatabaseQueryCount()` in critical endpoints to detect N+1.

---

## Improvement backlog (prioritized)

1) Large media handling and delivery
- Migrate uploads to S3 using presigned multipart uploads for large videos.
- Require checksums (e.g., `x-amz-checksum-sha256`) and set lifecycle rules to abort incomplete multipart uploads.
- Serve via `temporaryUrl()` or CloudFront signed URLs with short TTLs.

2) Update strategy for nested trees
- Accept client-provided IDs for modules/contents; diff and upsert rather than delete/recreate.
- Add `updated_at` comparisons and soft-deletes for safe rollbacks.

3) Operational excellence
- Add queues + Horizon and basic dashboards/alerts.
- Introduce rate limiting on mutations and streaming.
- Adopt Pail for log tailing and structured logging fields (course_id, user_id).

4) Testing and CI
- Expand test coverage as outlined; run parallel on CI; add storage fakes and S3 mocks.
- Add static analysis (Larastan/PHPStan level 6-8) and Pint for code style.

5) Developer experience
- Use `enum` for roles; centralize validation rules; use custom FormRequest `authorize()` with policy checks where appropriate.
- Consider Spatie Medialibrary if you want standardized conversions and multi-disk support.

---

## Interview question bank (with answers)

Below is a curated set of questions and concise answers. Use progressively by level.

### Junior

- Explain the HTTP lifecycle in Laravel.
  - Request → Route matching → Middleware → Controller/Action → Response → Middleware termination. Kernel registers global and route middleware; service providers boot bindings.

- What are FormRequests and why use them?
  - Custom request classes encapsulating validation and authorization; keep controllers thin; reusable rules and messages.

- Difference between `Gate` and `Policy`?
  - Gates are closures for simple abilities; policies are classes tied to models with methods like `create`, `update`.

- How do you prevent mass assignment?
  - Use `$fillable` or `$guarded`. Prefer `$fillable` with explicit lists.

- Eloquent relationships you’ve used?
  - `belongsTo`, `hasMany`, `hasOne`, `belongsToMany`, polymorphic (`morphOne/Many/To`).

- What is eager loading and why?
  - `with()` loads relations in fewer queries to avoid N+1 problems.

- How do you store a file with the Storage facade?
  - `Storage::disk('public')->putFile('path', $request->file('...'))`; create a symbolic link with `storage:link` to serve.

- CSRF protection in Laravel?
  - CSRF token middleware for web routes; Blade `@csrf` directive; Sanctum for SPA stateful sessions.

### Mid-level

- Explain FormRequest `prepareForValidation()` and `authorize()`.
  - `prepareForValidation()` mutates input pre-validation (e.g., JSON decode); `authorize()` checks per-request access; fail returns 403.

- How to structure validation for nested arrays?
  - Dot notation (`modules.*.contents.*.children.*`) and conditional rules; custom Rule objects for complex constraints.

- Show how to write a policy check in a controller.
  - `$this->authorize('update', $course);` or `Gate::authorize('update', $course)`; middleware `can:update,course` for routes.

- Handling large file uploads safely?
  - Use chunked/multipart uploads (S3), size limits, MIME checks, virus scan if needed; store outside webroot; serve via signed URLs.

- Transactions with Eloquent?
  - `DB::transaction(fn() => ...)` to group writes atomically; handle exceptions and rollbacks.

- Queue basics and when to use Horizon.
  - Jobs run async via drivers (database/redis); Horizon provides metrics, balancing, and monitoring for Redis-based queues.

- How to test file uploads and storage.
  - `Storage::fake('public'); $file = UploadedFile::fake()->image('x.jpg');` then assert with `Storage::disk('public')->exists(...)`.

- What’s new or notable in Laravel 11?
  - Reverb (broadcasting), simplified app structure, `/up` maintenance endpoint, casts-as-method, eager load limit, APP_KEY rotation guidance.

### Senior

- Design a scalable media architecture for course videos.
  - Client-side direct to S3 via presigned multipart; checksum headers; complete multipart; serve via CloudFront signed URLs; background transcodes via queues; store metadata in DB; expiring URLs.

- Improve nested content updates without full rebuild.
  - Accept IDs; compute diff; `upsert()` modules/contents; delete removed; transactional boundary; events for downstream updates.

- Security review for the streaming endpoint.
  - Ensure strict path normalization; rate limit; defend against range abuse; correct content types; add ETag/Last-Modified; log suspicious access; prefer signed S3 URLs.

- Discuss caching strategies.
  - Cache public course payloads keyed by ID; tag-based invalidation; use `remember()`; atomic locks to prevent cache stampede; consider response caching for show pages.

- How to prevent N+1 beyond `with()`?
  - Use `withCount`, `loadMissing`, precompute aggregates, database-level denormalization if safe, and query batching.

- Observability plan.
  - Structured logs (course_id, user_id), error tracking (Sentry/Bugsnag), metrics (queue times, throughput), health checks `/up`, Horizon dashboards, Pail for dev.

- Advanced authorization patterns.
  - Deny-as-404 for private resources; guest user policies; ability caching; team/organization scoping.

### Staff/Principal

- Migration from local storage to S3 with zero downtime.
  - Dual-write period; backfill job; switch read path to S3 with fallback; verify checksums; cutover and remove local. Feature flags for gradual rollout.

- Multi-tenant evolution strategy.
  - Tenant ID scoping; global scopes; dedicated databases vs schemas; per-tenant queues; data isolation and policy adjustments; perf/cost tradeoffs.

- Designing for eventual consistency.
  - Idempotent jobs and request handlers; outbox pattern for events; retries with backoff; compensating actions; user-facing progress states.

- Compliance and data governance.
  - Data retention rules; PII handling; access logs; encryption at rest/in transit; key rotation; secrets management; audit trails.

- Production incident playbook for failed uploads.
  - Detect via metrics (multipart abort counts); quarantine partials; user guidance to resume; automated cleanup via S3 lifecycle; observability dashboards.

---

## Hands-on practicals and rubric

Use one short, one medium, and one system-level exercise. Time-box and score using the rubric.

1) Short (20–30 min): Add a policy rule and tests
- Task: Add a `restore` rule in `CoursePolicy` with tests for admin vs non-admin.
- Expected: Update policy, wire route/middleware, add feature tests.

2) Medium (60–90 min): Secure streaming hardening
- Task: Add ETag/Last-Modified and Range validation to `VideoStreamController`; add rate limiting.
- Expected: Correct headers and conditional responses; tests for 304/206.

3) System (2–4 hrs, take-home): S3 presigned multipart upload path
- Task: Implement endpoints to initiate multipart upload, return presigned part URLs, and complete upload; store metadata; return temporary playback URL.
- Expected: Correct S3 API usage, checksum support, error handling, and tests with S3 fakes/mocks.

Rubric (score 0–4 per axis):
- Correctness: compiles, tests pass, edge cases considered.
- Design: separation of concerns, idiomatic Laravel, minimal coupling.
- Security: validations, authZ, safe file handling, least privilege.
- Performance: query efficiency, async where appropriate, caching.
- Maintainability: tests, readability, docs, config via env.
- Ownership: scope control, thoughtful trade-offs, docs.

---

## Suggested structured interview flow (60–75 min)

- 5 min: Warm-up and role context.
- 15 min: Fundamentals (routing, requests, Eloquent) — junior/mid.
- 15 min: Architecture (authZ, uploads, queues, caching) — mid/senior.
- 15 min: Systems design scenario (media pipeline) — senior/staff.
- 15 min: Practical walkthrough or code review — candidate explains trade-offs.
- 5 min: Q&A, wrap-up.

---

## Reference links (authoritative sources)

- Laravel 11 docs: Authentication, Authorization (Policies/Gates), Validation (FormRequest), Queues & Horizon, Events & Broadcasting (Reverb), Notifications & Mail, Filesystem & S3 temporary URLs, Caching & Rate Limiting, Logging (Pail), Testing (HTTP/Database/Storage), Errors & Exception throttling, Database (transactions, read/write), Octane, Scheduling.
- Laravel News: Laravel 11 release overview and changes.
- Spatie Laravel Medialibrary: model media, conversions, multi-disk.
- AWS S3 docs: Multipart uploads, presigned URLs, checksum headers, lifecycle abort incomplete.

Tip: keep internal team notes pointing to exact sections you use most often (e.g., Sanctum vs Passport, ShouldBroadcast queues, Atomic Locks, `temporaryUrl()` nuances).

---

## Loom context (optional)

Suggested talking points for a Loom walkthrough (3–5 minutes):
- What’s in this guide and how to apply it during interviews.
- Quick tour of the repo: domain model, validation, policies, streaming.
- Biggest improvement: presigned multipart uploads to S3; how it changes the architecture and ops.
- Hiring angle: practicals + rubric and how to evaluate consistently.

---

## Appendix: quick checklists

Security
- [ ] Validate and sanitize nested inputs
- [ ] Enforce MIME, extension, size for uploads
- [ ] Rate limit mutations and streaming
- [ ] Ensure CSRF/CORS are configured correctly
- [ ] Prefer signed URLs for media

Performance
- [ ] Eager-load relations
- [ ] Add pagination for lists
- [ ] Cache hot endpoints
- [ ] Use queues for heavy work; monitor with Horizon

DX
- [ ] Centralize validation rules
- [ ] Use enums for roles
- [ ] Add CI with tests, static analysis, and Pint

