# Deployment Plan

| বিষয় | তথ্য |
|---|---|
| Document | Deployment & Data Migration Plan — Part A: Deployment (SDLC ধাপ ৯) |
| Version | 1.0 |
| Date | 2026-10-08 |
| Status | ✔ Approved |
| Based on | SAD Part B §10–§14 (cache, queue, scheduler, security, deployment, ADR-12), SRS NFR, Test Plan (NFT, smoke test, §7.2 অগ্রাধিকার page) |
| পাঠক | DevOps / server admin, technical lead; নতুন university-র IT team |

### এই ধাপের document

| Part | বিষয় |
|---|---|
| **A** | Deployment — এই document: server, installation, CI/CD, backup, monitoring, maintenance |
| B | [Data Migration](02-Data-Migration-Plan.md) — `project_diu` থেকে data ও file আনা, go-live (cutover) |

## সূচি

1. [Environment](#1-environment)
2. [Server ও software](#2-server-ও-software)
3. [Server setup](#3-server-setup)
4. [নতুন installation (যেকোনো university)](#4-নতুন-installation-যেকোনো-university)
5. [CI/CD ও deploy](#5-cicd-ও-deploy)
6. [Module ও theme production-এ](#6-module-ও-theme-production-এ)
7. [Backup ও restore](#7-backup-ও-restore)
8. [Monitoring ও alert](#8-monitoring-ও-alert)
9. [Security hardening checklist](#9-security-hardening-checklist)
10. [Maintenance](#10-maintenance)
11. [দায়িত্ব](#11-দায়িত্ব)
12. [খোলা প্রশ্ন](#12-খোলা-প্রশ্ন)

---

## 1. Environment

| Environment | Domain (DIU প্রস্তাব) | Data | Deploy | কে ঢুকতে পারে |
|---|---|---|---|---|
| Local | `university.test` | Factory / `TestUniversitySeeder` | Developer নিজে | Developer |
| Staging | `staging.daffodilvarsity.edu.bd` | Migration dry-run-এর data, ব্যক্তিগত তথ্য mask করা | `main` branch-এ merge হলে স্বয়ংক্রিয় | Team + UAT অংশগ্রহণকারী; IP allowlist বা HTTP basic auth; `noindex` |
| Production | `daffodilvarsity.edu.bd` | আসল | Git tag (`v1.x.y`) + manual approval | Visitor; admin panel শুধু HTTPS + 2FA |

Staging ও production-এর software version **হুবহু এক**; শুধু `.env` আলাদা।

---

## 2. Server ও software

### 2.1 Topology (শুরু) — AWS

Hosting: **AWS** (DIU confirmed, DMQ-02)। SAD Part B §14.2-এর **Standard topology** AWS-এ:

| অংশ | AWS service (প্রস্তাব) | মন্তব্য |
|---|---|---|
| Region | `ap-south-1` (Mumbai) | বাংলাদেশের সবচেয়ে কাছে — latency কম |
| Application server | EC2 (৪ vCPU / ৮ GB, যেমন `t3.xlarge` বা `m6i.xlarge`), Ubuntu 24.04 | Nginx + PHP-FPM + Horizon + Cron + Redis |
| Database | একই EC2-তে MySQL (শুরু, খরচ কম) **অথবা** RDS for MySQL (automatic backup, patch) | DIU-র বাজেট অনুযায়ী (DMQ-07) |
| Media | S3 bucket (private) + CloudFront (CDN) | Disk বাড়ানোর ঝামেলা নেই; ছবি দ্রুত; `MEDIA_DISK=s3` |
| Backup | আলাদা S3 bucket, ভিন্ন region (যেমন `ap-southeast-1`), lifecycle rule দিয়ে পুরনো backup মুছে যায় | Section 7 |
| Email | Amazon SES (DMQ-03-এর প্রস্তাব) | Newsletter-এর জন্য উপযুক্ত |
| SSL | ACM certificate (CloudFront / Load Balancer-এ) অথবা Let's Encrypt (EC2-তে সরাসরি) | DMQ-04 |
| Staging | ছোট EC2 (২ vCPU / ৪ GB), কাজ না থাকলে বন্ধ রাখা যায় | খরচ কম |
| Access | Security Group: 80 / 443 সবার জন্য, 22 শুধু DIU IP বা bastion থেকে; DB port বাইরে বন্ধ | Section 3.6 |

Scaled topology-তে যেতে হবে যদি: NFT-06 load test-এ ৫০০ concurrent user লক্ষ্য পূরণ না হয়, বা admission মৌসুমে CPU নিয়মিত ৭০%-এর উপরে থাকে।

### 2.2 Server requirement (DIU production)

| Resource | Production | Staging |
|---|---|---|
| CPU | ৪ vCPU | ২ vCPU |
| RAM | ৮ GB | ৪ GB |
| Disk | ১০০ GB SSD (OS + app + DB) + media-র জন্য আলাদা volume (legacy file-এর আকার × ৩ — conversion ও বৃদ্ধির জন্য; আকার Data Migration DR-1-এ মাপা হবে) | ৫০ GB + media |
| OS | Ubuntu 24.04 LTS | একই |

> চূড়ান্ত sizing: Test Plan NFT-06 (৫০০ concurrent) ও admission spike test-এর পর এই টেবিল update হবে।

### 2.3 Software version

| Software | Version | মন্তব্য |
|---|---|---|
| PHP | 8.4 (FPM) | Extension: `intl`, `gd` বা `imagick`, `redis`, `zip`, `bcmath`, `exif`, `pdo_mysql`, `opcache`, `pcntl` (Horizon) |
| MySQL | 8.0+ (8.4 LTS প্রস্তাবিত) | `utf8mb4`, `utf8mb4_unicode_ci` |
| Redis | 7 | Cache, queue, session — আলাদা DB number |
| Nginx | stable | HTTP/2, gzip / brotli |
| Supervisor | — | Horizon |
| Node | 26 (`.nvmrc`) | শুধু build server / CI-তে; production-এ লাগে না। Node 18-এ Vite চলে না |
| Composer | 2 | — |
| Image tool | `jpegoptim`, `optipng`, `pngquant`, `cwebp` | Media conversion |
| Search (ঐচ্ছিক) | Meilisearch | শুরুতে database driver; data বাড়লে Meilisearch (SAD) |

---

## 3. Server setup

### 3.1 Directory গঠন (zero-downtime)

```
/var/www/uwms/
├── releases/
│   ├── 20261201-0930/        # প্রতিটি deploy একটি নতুন folder
│   └── 20261205-1400/
├── current -> releases/20261205-1400     # Nginx এটি serve করে
└── shared/
    ├── .env
    ├── storage/              # log, cache, media (local disk হলে)
    ├── modules-runtime/      # ZIP দিয়ে install হওয়া module (ADR-12)
    └── themes-runtime/       # ZIP দিয়ে install হওয়া theme
```

প্রতিটি release-এ `storage`, `.env`, `Modules/` runtime অংশ ও runtime theme `shared/`-এ symlink।

### 3.2 Nginx (মূল অংশ)

```nginx
server {
    listen 443 ssl http2;
    server_name daffodilvarsity.edu.bd;
    root /var/www/uwms/current/public;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    # Content-Security-Policy: application middleware থেকে (nonce সহ)

    client_max_body_size 64M;            # module / theme ZIP ও বড় PDF

    # Upload ও media folder-এ PHP কখনো চলবে না
    location ~* ^/(storage|media|themes)/.*\.(php|phtml|phar)$ { deny all; }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~* \.(css|js|webp|jpg|jpeg|png|svg|woff2)$ { expires 30d; access_log off; }
}

server { listen 80; server_name daffodilvarsity.edu.bd www.daffodilvarsity.edu.bd; return 301 https://daffodilvarsity.edu.bd$request_uri; }
```

`$realpath_root` জরুরি — symlink বদলালে PHP নতুন release দেখে।

### 3.3 PHP-FPM ও OPcache

| Setting | মান |
|---|---|
| `pm` | `dynamic`; `pm.max_children` = (RAM − ২ GB) / গড় process memory (≈ ৬০ MB) → ৮ GB-তে ≈ ১০০ |
| `memory_limit` | 256M (CLI 512M — migration ও image) |
| `upload_max_filesize` / `post_max_size` | 64M |
| `opcache.validate_timestamps` | `0` (production) — তাই deploy ও module install-এর পর OPcache reset বাধ্যতামূলক (ADR-12) |
| `opcache.memory_consumption` | 256 |

### 3.4 Horizon ও scheduler

```ini
; /etc/supervisor/conf.d/uwms-horizon.conf
[program:uwms-horizon]
command=php /var/www/uwms/current/artisan horizon
user=www-data
autostart=true
autorestart=true
stopwaitsecs=3600
```

```cron
* * * * * www-data cd /var/www/uwms/current && php artisan schedule:run >> /dev/null 2>&1
```

Queue (SAD Part B §11): `default`, `media` (image conversion), `mail`, `search`, `maintenance` (backup, log cleanup, orphan check)। `media` queue-এ worker সংখ্যা আলাদা — বড় import-এ বাকি কাজ আটকে না যায়।

### 3.5 MySQL

| Setting | মান |
|---|---|
| Character set | `utf8mb4` / `utf8mb4_unicode_ci` (বাংলা) |
| `innodb_buffer_pool_size` | RAM-এর ৫০% (একই server-এ app থাকলে) |
| `slow_query_log` | চালু, `long_query_time = 1` |
| `sort_buffer_size` | App প্রতিটি connection-এ session-এ 8 MB করে (`DB_SORT_BUFFER_SIZE`, `config/database.php`) — DIU-র কিছু Program page-এর rich text 1.4 MB পর্যন্ত; 256 KB default-এ MySQL 8 এমন row `ORDER BY` করতে পারে না (error 1038)। MySQL 8 শুধু যতটুকু লাগে ততটুকু নেয়; server-এ আলাদা কিছু লাগে না |
| Application user | শুধু নিজের database-এ `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES` |
| Legacy (`project_diu`) user | **শুধু `SELECT`** — migration-এর সময় (Part B) |
| Remote access | বন্ধ (localhost / private network) |

### 3.6 Firewall ও SSH

* খোলা port: 22 (শুধু DIU office IP / VPN থেকে), 80, 443।
* SSH: key-only, root login বন্ধ, `fail2ban`।
* Deploy user (`deploy`) আলাদা; `www-data`-র shell নেই।

### 3.7 SSL

DIU-র বিদ্যমান certificate (যদি থাকে) বা Let's Encrypt (`certbot`, auto-renew)। Staging-এ Let's Encrypt।

---

## 4. নতুন installation (যেকোনো university)

এই অংশ অন্য university-র IT team-এর জন্য — DIU-র migration ছাড়া সাধারণ installation।

```bash
git clone <repository> /var/www/uwms/releases/initial
cd /var/www/uwms/releases/initial
composer install --no-dev --optimize-autoloader
npm ci && npm run build                     # বা CI-তে build করা artifact আনুন
cp .env.example /var/www/uwms/shared/.env   # তারপর পূরণ
php artisan uwms:install
```

`uwms:install` ধাপে ধাপে জিজ্ঞেস করে ও করে:

1. University-র নাম (en / bn), short name, domain
2. ভাষা: default ভাষা ও অন্যান্য (যেমন `bn`, `en`)
3. Super Admin user (নাম, email, password; 2FA বাধ্যতামূলক)
4. Database migrate, kernel seed (Standard Role Catalog, default vocabulary, settings)
5. Bundled module install — `default_active` অনুযায়ী (FR-MODM-16)
6. Default theme activate, design token-এর default
7. Storage link, cache warm, sitemap
8. Horizon ও scheduler চালু আছে কিনা যাচাই → "Installation complete" + admin URL

### 4.1 `.env`-এর প্রধান key

| Key | উদাহরণ / নিয়ম |
|---|---|
| `APP_ENV`, `APP_DEBUG` | `production`, `false` (কখনো `true` নয়) |
| `APP_URL` | `https://daffodilvarsity.edu.bd` |
| `APP_KEY` | `php artisan key:generate` — **হারালে encrypted data পড়া যায় না**; backup-এর বাইরে আলাদা নিরাপদ জায়গায় রাখুন |
| `DB_*` | Application user |
| `REDIS_*`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis` | — |
| `MAIL_*` | SMTP / transactional email provider (DMQ-03) |
| `FILESYSTEM_DISK`, `MEDIA_DISK` | `local` (শুরু) বা `s3` |
| `BACKUP_DISK` | Offsite destination (Section 7) |
| `SCOUT_DRIVER` | `database` (শুরু) বা `meilisearch` |
| `UWMS_MODULE_RUNTIME_PATH`, `UWMS_THEME_RUNTIME_PATH` | `/var/www/uwms/shared/modules-runtime`, `.../themes-runtime` |
| `LEGACY_DB_*` | শুধু DIU migration-এর সময়; go-live-এর পর মুছে ফেলা (Part B) |

---

## 5. CI/CD ও deploy

Repository: GitHub (private)। Pipeline: **GitHub Actions**; deploy: SSH-এর মাধ্যমে script (Deployer বা নিজস্ব shell script)।

### 5.1 Pipeline

```
Pull request
  ├─ composer install, npm ci
  ├─ Pint, Larastan, Stylelint (theme), composer / npm audit
  ├─ Pest: unit, feature, architecture, schema, AC traceability (Test Plan §7)
  ├─ Lifecycle test — শুধু পরিবর্তিত module (nightly-তে সব)
  └─ Build (Vite) — core ও default theme

Merge to main
  └─ উপরের সব + build artifact (vendor ছাড়া code + compiled asset) → staging-এ deploy

Tag v1.x.y
  └─ একই artifact → production deploy (manual approval: technical lead)
```

একই artifact staging ও production-এ যায় — production-এ আলাদা build হয় না।

### 5.2 Deploy ধাপ (SAD Part B §14.4)

| # | ধাপ | ব্যর্থ হলে |
|---|---|---|
| 1 | নতুন `releases/{timestamp}` folder-এ artifact upload | বাতিল; live অপরিবর্তিত |
| 2 | `composer install --no-dev --optimize-autoloader` | বাতিল |
| 3 | `shared/` symlink (`.env`, `storage`, module ও theme runtime) | বাতিল |
| 4 | `php artisan down --render=maintenance --retry=30` **শুধু** migration-এ breaking change থাকলে (সাধারণত নয়) | — |
| 5 | Backup: database (spatie backup, শুধু DB) | বাতিল |
| 6 | `php artisan migrate --force` (kernel ও active module) | `migrate:rollback --step` + পুরনো release থাকে |
| 7 | `php artisan optimize` + `uwms:registry:cache` | বাতিল |
| 8 | Health check নতুন release-এ (CLI: `uwms:health`) | বাতিল |
| 9 | `current` symlink বদল (atomic) | — |
| 10 | PHP-FPM reload (OPcache reset), `horizon:terminate` (Supervisor আবার চালু করে) | পুরনো symlink-এ ফেরত |
| 11 | Smoke check: অগ্রাধিকার page (Test Plan §7.2) + homepage + admin login → HTTP 200 | স্বয়ংক্রিয় rollback (ধাপ 12) |
| 12 | Rollback: `current` → আগের release, FPM reload; DB migration-এর জন্য `down()` বা ধাপ ৫-এর backup | Technical lead-কে alert |
| 13 | পুরনো release পরিষ্কার — শেষ ৫টি রাখা | — |

**Deploy সময়:** কম traffic-এর সময় (রাত ১১টা – সকাল ৭টা); admission মৌসুমে শুধু জরুরি fix।

---

## 6. Module ও theme production-এ

| নিয়ম | কারণ |
|---|---|
| নতুন বা update ZIP **আগে staging-এ** install → Test Plan TC-MOD lifecycle → তারপর production | NFR-REL-03 |
| Production-এ install শুধু Super Admin, কম traffic-এর সময় | — |
| Install-এর আগে backup স্বয়ংক্রিয় (FR-MODM-05) | Rollback |
| Install-এর পর OPcache reset ও registry cache rebuild স্বয়ংক্রিয় (ADR-12) | `validate_timestamps=0` |
| ZIP-installed module `shared/modules-runtime/`-এ — deploy-এ হারায় না | ADR-12 |
| Bundled module-এর update আসে core release-এর সাথে (Git), ZIP দিয়ে নয় | এক উৎস |

---

## 7. Backup ও restore

| কী | কখন | রাখা | কোথায় |
|---|---|---|---|
| Database (পূর্ণ dump) | প্রতিদিন রাত ২টা + প্রতিটি deploy ও module install-এর আগে | ১৪ দিন দৈনিক, ৮ সপ্তাহ সাপ্তাহিক (configurable — NFR-REL-02) | Backup S3 bucket (ভিন্ন region) |
| `shared/` (module / theme runtime, `.env` ছাড়া) | সাপ্তাহিক পূর্ণ + দৈনিক incremental | ১৪ দিন | Backup S3 bucket |
| Media (S3-এ থাকলে) | S3 versioning + cross-region replication | ৩০ দিন পুরনো version | Backup region |
| `.env` ও `APP_KEY` | পরিবর্তন হলে | — | Password manager / vault — backup file-এ নয় |
| পুরনো `audit_logs` archive ও legacy DB dump | Migration-এর সময় একবার | ন্যূনতম ১ বছর | Offsite, শুধু পড়ার জন্য |

Tool: `spatie/laravel-backup` (BRD Q-07 অনুযায়ী Backup core-এ) — backup সফল / ব্যর্থ notification ও health check সহ।

**Restore drill (NFT-13):** go-live-এর আগে একবার, তারপর প্রতি ৩ মাসে — নতুন server-এ backup থেকে site চালু করে সময় লিপিবদ্ধ। লক্ষ্য: **RPO ২৪ ঘণ্টা** (সর্বোচ্চ একদিনের data হারানো), **RTO ৪ ঘণ্টা** (৪ ঘণ্টার মধ্যে site আবার চালু)।

---

## 8. Monitoring ও alert

| কী | Tool | Alert শর্ত | কাকে |
|---|---|---|---|
| Uptime | বাইরের uptime monitor (UptimeRobot / Better Stack) — homepage + **অগ্রাধিকার page** (admission, waiver calculator, programs, scholarship) | ২ মিনিট down | Web team (SMS / email) |
| Application error | Laravel log → error tracker (Sentry বা Flare) | নতুন error type; অগ্রাধিকার page-এ যেকোনো 5xx | Developer |
| Queue | Horizon (`/admin/horizon`, শুধু Super Admin) | Wait time > ৫ মিনিট; failed job | Developer |
| Scheduler | Health check (`spatie/laravel-health`) — শেষ run | ৫ মিনিট run নেই | Developer |
| Backup | spatie backup monitor | শেষ সফল backup > ২৬ ঘণ্টা | Web team |
| Disk, CPU, RAM | Server monitoring (Netdata / provider) | Disk > ৮০%; CPU > ৮৫% ১০ মিনিট | DevOps |
| SSL | Uptime monitor | মেয়াদ < ১৪ দিন | DevOps |
| Performance | Lighthouse CI (সাপ্তাহিক, production) | অগ্রাধিকার page < ৯০ | Developer |
| 404 | SEO module-এর 404 log (FR-SEO-06) | দৈনিক শীর্ষ ২০টি report | Web team (redirect তৈরির জন্য) |

**Go-live-এর পর প্রথম ৭ দিন (hypercare):** অগ্রাধিকার page-এর alert threshold কঠোর (১ মিনিট), দৈনিক error review meeting।

**Log:** Laravel log দৈনিক file, ১৪ দিন; activity log (application) ন্যূনতম ৬০ দিন (BR-LOG-05) — দুটি আলাদা।

---

## 9. Security hardening checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS বাধ্যতামূলক, HSTS, security header (NFR-SEC-08)
- [ ] Upload / media / theme folder-এ PHP execute বন্ধ (Nginx)
- [ ] `.env` permission `600`, owner deploy user
- [ ] Database ও Redis শুধু localhost / private network
- [ ] SSH key-only, root login বন্ধ, fail2ban, port 22 শুধু allowlist
- [ ] Super Admin-এর 2FA বাধ্যতামূলক setting চালু (NFR-SEC-04)
- [ ] Horizon, health, log viewer — শুধু Super Admin
- [ ] Staging `noindex` ও সুরক্ষিত; ব্যক্তিগত তথ্য mask করা
- [ ] `composer audit` / `npm audit` CI-তে; OS security update স্বয়ংক্রিয় (`unattended-upgrades`)
- [ ] OWASP ZAP baseline (Test Plan NFT-12) go-live-এর আগে
- [ ] Legacy DB user (`SELECT`-only) ও `LEGACY_DB_*` go-live-এর পর মুছে ফেলা

---

## 10. Maintenance

| কাজ | কত দিন পর পর | কে |
|---|---|---|
| OS ও package security update | স্বয়ংক্রিয় (দৈনিক); kernel update-এ মাসিক reboot (কম traffic-এর সময়) | DevOps |
| Core (platform) update | Release অনুযায়ী — staging → production (Section 5) | Developer |
| Laravel / Filament / package major update | বছরে ১–২ বার, আলাদা branch-এ পূর্ণ test | Developer |
| Restore drill | প্রতি ৩ মাস | DevOps |
| Lighthouse ও accessibility review | মাসিক | Web team |
| 404 report থেকে redirect | সাপ্তাহিক | Web team (admin panel, developer লাগে না) |
| Disk ও media পরিষ্কার (অব্যবহৃত media report) | মাসিক | Web team |

---

## 11. দায়িত্ব

| ভূমিকা | দায়িত্ব |
|---|---|
| Technical lead | Production deploy approval, rollback সিদ্ধান্ত, architecture |
| Developer | CI সবুজ রাখা, release, error সমাধান |
| DevOps / server admin (DIU IT) | Server, backup, monitoring, SSL, security update |
| Web team (DIU) | Content, module install (staging → production), 404 redirect |
| Project Owner | Go-live সিদ্ধান্ত, UAT sign-off |

---

## 12. খোলা প্রশ্ন

| # | প্রশ্ন | প্রস্তাব |
|---|---|---|
| DMQ-02 *(✔ সমাধান)* | Hosting কোথায়? | **AWS** (Section 2.1); backup আলাদা S3 bucket, ভিন্ন region |
| DMQ-03 | Email পাঠানোর মাধ্যম? | **Amazon SES** — hosting AWS-এ, newsletter (৬,৬২৪ + ৪,৮২৮ subscriber)-এর জন্য উপযুক্ত; DIU-র domain-এ SPF / DKIM যোগ করতে হবে |
| DMQ-07 | Database: EC2-তে নিজে MySQL, নাকি RDS? | শুরুতে খরচ কম রাখতে EC2-তে; DIU চাইলে RDS (backup ও patch AWS করে) |
| DMQ-04 | SSL certificate: DIU-র বিদ্যমান (wildcard?) নাকি Let's Encrypt? | যেকোনোটি |

(DMQ-01, DMQ-05, DMQ-06 — [Part B](02-Data-Migration-Plan.md#12-খোলা-প্রশ্ন)-তে)
