# Notification

Core module C-18 (SRS Part 1): admin notifications and the Content Update Reminder. The contracts are in
the kernel (`Platform\Notifications`); this module sends, stores and shows them.

| | |
|---|---|
| Slug | `notification` |
| Tables | `notification_preferences`, `notification_templates` (C), `notification_reminder_rules` (B), `notification_reminder_states` (S) — DB-02 §14; kernel / Laravel `notifications` |
| Permissions | custom `Manage:NotificationTemplate`; resource `…:ReminderRule` (the freshness report needs `ViewAny:ReminderRule`) |
| Settings | none |

**Notifications**

* A module defines a type (`Platform\Notifications\NotificationType`: key, recipients, default subject and
  body with `{placeholders}`, link) and registers it: `$r->notification(InquiryReceived::class)`; it sends with
  `app(Notifications::class)->send(new InquiryReceived([...], $inquiry))`.
* Recipients by permission and scope: `Recipients::permitted('ViewAny:Inquiry', $inquiry)` — CSE's inquiry
  reaches CSE users only — plus Super Admins (FR-NOTI-07).
* In the bell (Filament database notifications, polled every 30 s) and / or by email, queued (FR-NOTI-01/02).
* User menu › My notifications: bell / email per type (FR-NOTI-05). Site › Notification templates: wording
  per language (FR-NOTI-06). Read notifications go after 90 days (FR-NOTI-08).
* Core types: module installed / uninstalled, new admin user, content reminder, weekly summary (FR-NOTI-04).

**Content Update Reminder** (BRD 7.10)

* A module registers how its content belongs to an entity: `$r->freshnessSource(NoticeFreshness::class)`
  (`Platform\Notifications\FreshnessSource`: "Show in" relation or owner, `updated_at` or `published_at`).
  FAQ and Page do now.
* Site › Content reminders: content × entity type, days, interval, mandatory, Latest / Each record, messages.
* Daily 07:00: every active rule × active entity → ok / empty / stale; reminders to the entity's responsible
  users (scope + the content's update permission) at most every N days; ok again stops them. Weekly summary
  (Sunday 08:00) for admins, marking entities without a responsible user.
* Site › Content freshness: entity × content matrix with days and reminders, CSV export, "Check now".
  Dashboard widget for entity users.
