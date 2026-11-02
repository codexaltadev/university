# Cloud session-এ কাজ — নির্দেশিকা

Claude Code-এর cloud session (claude.ai/code) দিয়ে এই project-এ কাজ করার ধাপ। Cloud session-এ Claude
একটা আলাদা Linux machine-এ GitHub থেকে repo নিয়ে কাজ করে; আপনার WSL machine চালু রাখার দরকার নেই।

## ১. প্রথমবার

1. **claude.ai/code** খুলুন → GitHub account যুক্ত করুন (codexaltadev organization-এর `university` repo-তে access দিন)।
2. **Environment** তৈরি করুন:
   - Repository: `codexaltadev/university`, branch `main`
   - **Setup script**-এ লিখুন:
     ```bash
     bash scripts/cloud-setup.sh
     ```
     এটা PHP 8.4, Composer, Node 26, MySQL, dependency, `.env`, database migrate + seed করে দেয়
     (প্রথমবার ৫–১০ মিনিট লাগতে পারে)।
   - Network access: package install-এর জন্য internet চালু রাখুন (GitHub, packagist, npm, Ubuntu apt)।
   - **কোনো secret দেবেন না** — বিশেষ করে `FMS_API_TOKEN` cloud-এ দরকার নেই।
3. Session শুরু করে প্রথম বার্তায় কাজটা লিখুন। Claude নিজে repo-র `CLAUDE.md` পড়ে নেয় — সেখানে সব নিয়ম আছে
   (বাংলায় উত্তর, push না করা, attribution না দেওয়া, targeted test ইত্যাদি)।

## ২. প্রতিদিনের কাজ

- এক session = এক কাজ (যেমন "Faculty Workspace — UI/UX §4.3 অনুযায়ী")। কাজ শেষে Claude পরিবর্তনের তালিকা ও test ফল জানাবে।
- Cloud session নিজের **আলাদা branch**-এ কাজ করে। দেখে ঠিক থাকলে বলুন "commit" / "push" — তখন সেই branch push
  হবে, GitHub-এ Pull Request খুলে `main`-এ merge করবেন।
- নিজের machine-এ সেই কাজ আনতে: `git fetch && git checkout <branch>` বা merge-এর পরে `git pull`।
- একই সময়ে local আর cloud-এ একই file-এ কাজ করবেন না — conflict হবে।

## ৩. Cloud-এ যা হয় না

| জিনিস | কেন | কোথায় করবেন |
|---|---|---|
| `legacy:migrate`, dry-run (DR-1..3), golden master | `project_diu` database ও legacy file শুধু আপনার machine-এ | নিজের WSL |
| FMS API থেকে শিক্ষক sync | token শুধু local `.env`-এ | নিজের WSL |
| DIU live frontend-এর সাথে মিলিয়ে দেখা (localhost:3000) | `DIUFronend` repo-র বাইরে | নিজের WSL |
| আপনার dev data দিয়ে পাতা দেখা | cloud-এর database নতুন, শুধু seed | নিজের WSL |

Code, test, doc, নতুন feature, bug fix — সব cloud-এ করা যায়।

## ৪. Credit

Cloud session credit আলাদা (দেওয়া credit শেষ বা মেয়াদ পেরোলে plan-এর সাধারণ usage থেকে কাটে)। লম্বা full test suite
(~৩০ মিনিট) বারবার চালালে বেশি খরচ হয় — তাই CLAUDE.md অনুযায়ী শুধু সংশ্লিষ্ট module-এর test চলে।

## ৫. সমস্যা হলে

- Setup script ব্যর্থ হলে session-এ বলুন: "scripts/cloud-setup.sh আবার চালাও এবং error ঠিক করো"।
- Database নতুন করে চাইলে: `php artisan migrate:fresh --seed`।
- Admin login: `admin@university.test`; password `db:seed` একবার দেখায় (session log-এ)।
