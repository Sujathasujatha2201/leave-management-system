# Leave Management System — Structured Code Package

This package is a formatting/organization pass over the existing working project.
Runtime logic, database schema, routes, and UI behavior are intended to remain unchanged.

## Main folders
- `frontend/` — HTML/CSS/JS UI
- `backend/` — PHP API/backend
- `backend/auth/` — authentication/session endpoints
- `backend/student/` — student APIs
- `backend/advisor/` — advisor APIs, including course-faculty assignment
- `backend/hod/` — HOD APIs, including class/advisor assignment
- `backend/course_faculty/` — course-faculty APIs
- `backend/shared/` — shared bootstrap/helpers/email utilities

## Course Faculty flow
Advisor selects a faculty for each of the five subjects. All active `course_faculty` users are available in each subject selector. Assignments are stored in `course_faculty_assignments`.

## Note
The PHP files were reformatted for readability without intentionally changing application logic.
