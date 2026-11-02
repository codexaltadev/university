// Shared header and footer of the static mockups (only for the design review; the theme draws its own).
const header = `
<div class="topbar"><div class="container"><a href="#">Students</a><a href="#">Faculty &amp; Staff</a><a href="#">Alumni</a><a href="#">Careers</a><a href="#" lang="bn">বাংলা</a></div></div>
<header class="header"><div class="container">
  <a class="logo" href="home.html"><span></span>Example University</a>
  <nav class="nav"><a href="programs.html">Academics</a><a href="admission.html">Admission</a><a href="#">Research</a><a href="notices.html">Notices</a><a href="news.html">News &amp; Events</a><a href="#">About</a></nav>
  <a class="icon-btn" href="search.html" aria-label="Search">⌕</a><a class="btn btn--primary" href="admission.html">Apply now</a>
  <a class="icon-btn burger" href="#" aria-label="Menu">☰</a>
</div></header>`;
const footer = `
<footer class="footer"><div class="container">
  <div class="cols">
    <div><a class="logo" style="color:#fff" href="home.html"><span></span>Example University</a><p style="margin-top:16px;max-width:320px">A place to learn, research and grow. Address, phone and e-mail come from Admin settings.</p></div>
    <div><h4>Study</h4><a href="#">Undergraduate</a><a href="#">Postgraduate</a><a href="#">Tuition fees</a><a href="#">Scholarships</a></div>
    <div><h4>University</h4><a href="#">About</a><a href="#">Faculties</a><a href="#">Administration</a><a href="#">Careers</a></div>
    <div><h4>Contact</h4><a href="#">+00 0000 000000</a><a href="#">info@example.edu</a><a href="#">Campus map</a></div>
  </div>
  <div class="bottom"><span>© 2026 Example University</span><span>Facebook · LinkedIn · YouTube</span></div>
</div></footer>`;
document.getElementById('site-header')?.insertAdjacentHTML('afterbegin', header);
document.getElementById('site-footer')?.insertAdjacentHTML('afterbegin', footer);
