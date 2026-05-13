# local_aistudy

## What is implemented (Phase 1)

- Adds a course-level link: **Study with AI Study**.
- Requires Moodle login and course capability before launch.
- Launches AI Study with deep-link context:
  - `source=moodle`
  - `courseid`
  - `shortname`
  - `fullname`
  - `coursekey=moodle-{courseid}-{shortname}`
  - `subject=coursekey`
  - `moodlehost`
- Keeps existing Moodle authentication unchanged (hybrid auth).

## Plugin settings

- `local_aistudy/baseurl`: Base URL of AI Study app.
- `local_aistudy/openinnewtab`: Open AI Study in a new browser tab.

## Phase 2 contract (planned)

These APIs are expected from AI Study for full integration:

1. `POST /integrations/moodle/v1/documents/sync`
2. `POST /integrations/moodle/v1/sso/exchange`

Planned Moodle-side additions after APIs exist:

- Event observers + queue sync for `create/update/delete` file changes.
- Stable mapping keys:
  - `moodle_course_id`
  - `moodle_cm_id`
  - `document_hash`
  - `owner_email`
- Role guard alignment so only enrolled users can access course workspace.
