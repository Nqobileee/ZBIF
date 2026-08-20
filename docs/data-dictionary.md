# Data dictionary (summary)

Full schema: `database/schema.sql`.

| Table | Purpose |
|---|---|
| events | Forum editions (year-round marketplace) |
| users / organizations / organization_user | Accounts and memberships |
| participation_profiles | Persona fields per event |
| roles / permissions / role_permission / model_has_roles | RBAC |
| challenges / challenge_state_events | Challenge lifecycle + audit |
| solutions | Challenge-tied and standalone products |
| development_workspaces (+ mentors, milestones, feedback, coaching_sessions) | Pre-forum build phase |
| embeddings / matches | Matching artefacts |
| connection_requests / deal_rooms / messages / files / stage_events / outcomes | Deal flow |
| investor_notes / investor_watchlist | Investor tools |
| programme_sessions / agenda_items / meetings | Agenda and networking |
| sponsors / partners / partner_inquiries | Sponsorship and partners |
| exhibitor_booths / leads | Exhibition + QR capture |
| award_* | Awards module |
| survey_* | Survey engine |
| sponsorship_applications | Innovator shortlist board |
| notifications / notification_preferences / jobs | Comms + queue |
| chat_sessions / ai_request_logs / knowledge_chunks | Nova + RAG |
| audit_logs / pages / content_blocks / faqs / news_posts / feature_flags | Ops + CMS |
| registration_drafts / magic_links / otp_codes / consents / rate_limits | Onboarding and safety |
