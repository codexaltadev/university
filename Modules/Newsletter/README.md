# Newsletter

Subscribers, lists and campaigns (M-39, DB-06 v1.4 §10, SRS Services FR-NLT-01..06, 08).

| | |
|---|---|
| Slug | `newsletter` |
| Tables | `newsletter_lists`, `newsletter_subscribers` (pending / subscribed / unsubscribed / bounced, token), `newsletter_list_subscriber`, `newsletter_campaigns`, `newsletter_campaign_lists`, `newsletter_deliveries` (pruned after 90 days) |
| Settings | `per_minute`, `intro`, `footer_note` |
| Schedule | `newsletter:dispatch` every minute (scheduled campaigns), `model:prune` deliveries daily |
| Queue | `SendCampaignBatch` — `per_minute` emails, then itself a minute later |
| Livewire | `newsletter.subscribe` (email, name, public lists; honeypot, 5 a minute) |
| Footer block | `newsletter.subscribe` |
| Routes | `newsletter.confirm` (signed, 7 days), `newsletter.unsubscribe` (one click, token), `newsletter.unsubscribe.reason` |
| Admin | Communication › Campaigns (Send test, Send, Copy, report), Subscribers (Import, Export, Unsubscribe), Newsletter lists |
| Legacy | `--step=S18N` — `newsletters` → General list, source legacy |

An unsubscribed email is a suppression: no campaign, and an import cannot subscribe it again (BRL-NLT-02).
With Admission installed, its confirmed email subscribers join "Admission Updates" (FR-NLT-08).
