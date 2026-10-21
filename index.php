<?php
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#28332b">
  <meta name="description" content="Join ADE &amp; OLA for the wedding of the century. Please RSVP for the celebration.">
  <title>ADE &amp; OLA | The Wedding of the Century</title>
  <link rel="icon" type="image/png" href="/assets/adeola-favicon.png?v=3">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/wedding.css">
</head>
<body>
  <header class="site-header">
    <a class="wordmark" href="#top" aria-label="ADE & OLA home"><img class="site-logo" src="/assets/adeola-2026-logo.png" alt="Ade and Ola wedding logo"></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Open navigation">
      <span></span><span></span>
    </button>
    <nav class="site-nav" id="site-nav" aria-label="Main navigation">
      <a href="#story">Our story</a>
      <a href="#details">Details</a>
      <a href="#schedule">Schedule</a>
      <a href="#registry">Registry</a>
      <a class="nav-rsvp" href="/register">RSVP</a>
    </nav>
  </header>

  <main>
    <section class="hero" id="top" aria-labelledby="hero-title">
      <div class="hero-shade"></div>
      <div class="hero-content">
        <p class="eyebrow hero-eyebrow">A BEAUTIFUL BEGINNING</p>
        <h1 id="hero-title">ADE &amp; OLA</h1>
        <p class="hero-script">The wedding of the century</p>
        <p class="hero-note">A day for love, family, and the people who make life extraordinary.</p>
        <a class="button button-gold" href="/register">Kindly RSVP <span aria-hidden="true">↗</span></a>
        <div class="hero-countdown" role="timer" aria-label="Countdown to the wedding on November 14, 2026">
          <div class="countdown-grid" aria-hidden="true">
            <div><span data-countdown-days>--</span><small>Days</small></div>
            <div><span data-countdown-hours>--</span><small>Hours</small></div>
            <div><span data-countdown-minutes>--</span><small>Minutes</small></div>
            <div><span data-countdown-seconds>--</span><small>Seconds</small></div>
          </div>
          <p class="countdown-finished" hidden>Today is the day!</p>
        </div>
      </div>
      <a class="scroll-cue" href="#story"><span></span> DISCOVER THE DAY</a>
    </section>

    <section class="story section-light" id="story">
      <div class="story-mark" aria-hidden="true">A</div>
      <div class="section-copy">
        <p class="eyebrow">WITH FULL HEARTS</p>
        <h2>A celebration<br>shared with you.</h2>
        <p>We’re gathering our favourite people for a day filled with warmth, good company, and memories we’ll carry with us. It wouldn’t be the same without you.</p>
      </div>
      <figure class="story-photo">
        <img src="https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=85" alt="A couple celebrating together on their wedding day" loading="lazy">
        <figcaption>A day to remember</figcaption>
      </figure>
    </section>

    <section class="details section-green" id="details">
      <div class="section-heading">
        <p class="eyebrow">WE ARE LOOKING FORWARD TO CELEBRATING WITH YOU</p>
        <h2>The details</h2>
      </div>
      <div class="detail-list">
        <div class="detail-row"><span>01</span><h3>Date</h3><p>Saturday, November 14, 2026</p></div>
        <div class="detail-row"><span>02</span><h3>Time</h3><p>12:00 PM</p></div>
        <div class="detail-row"><span>03</span><h3>Venue</h3><p>Zion Hall and Events<br>Ire-Akari, Isolo, Lagos State<br><a class="venue-map-link" href="<?= htmlspecialchars(EVENT_MAPS_URL, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Get directions in Google Maps</a></p></div>
        <div class="detail-row"><span>04</span><h3>Dress code</h3><p>Traditional attire<br>Champagne gold and teal</p></div>
      </div>
    </section>

    <section class="schedule section-light" id="schedule">
      <div class="section-heading">
        <h2>A day together</h2>
        <p>Join us from 12:00 PM at Zion Hall and Events.</p>
      </div>
      <div class="schedule-line">
        <article><span class="schedule-dot"></span><p class="eyebrow">SATURDAY, NOVEMBER 14</p><h3>Celebration</h3><p>12:00 PM</p></article>
        <article><span class="schedule-dot"></span><p class="eyebrow">THE VENUE</p><h3>Zion Hall and Events</h3><p>Ire-Akari, Isolo, Lagos State</p></article>
        <article><span class="schedule-dot"></span><p class="eyebrow">WE’RE LOOKING FORWARD TO IT</p><h3>See you there</h3><p>Kindly RSVP</p></article>
      </div>
    </section>

    <section class="registry" id="registry">
      <div class="registry-shade"></div>
      <div class="registry-copy">
        <p class="eyebrow">WITH LOVE AND GRATITUDE</p>
        <h2>Our registry</h2>
        <p>Your presence at our celebration is the greatest gift. For those who wish to give more, please bless us through our registry. We are grateful for your love and generosity.</p>
        <a class="button button-gold" href="<?= htmlspecialchars(GIFT_REGISTRY_URL, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View our gifts <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="rsvp-band">
      <p class="eyebrow">WE HOPE YOU CAN JOIN US</p>
      <h2>Be part of our day.</h2>
      <a class="button button-dark" href="/register">RSVP now <span aria-hidden="true">↗</span></a>
    </section>
  </main>

  <footer class="site-footer">
    <a class="wordmark" href="#top" aria-label="ADE & OLA home">ADE <span>&amp;</span> OLA</a>
    <span>ADE &amp; OLA@2026</span>
  </footer>
  <script>
    const countdown = document.querySelector('.hero-countdown');
    const countdownTarget = new Date('2026-11-14T12:00:00+01:00').getTime();
    const countdownGrid = countdown.querySelector('.countdown-grid');
    const countdownFinished = countdown.querySelector('.countdown-finished');
    const countdownParts = {
      days: countdown.querySelector('[data-countdown-days]'),
      hours: countdown.querySelector('[data-countdown-hours]'),
      minutes: countdown.querySelector('[data-countdown-minutes]'),
      seconds: countdown.querySelector('[data-countdown-seconds]'),
    };

    function updateCountdown() {
      const remaining = countdownTarget - Date.now();
      if (remaining <= 0) {
        countdownGrid.hidden = true;
        countdownFinished.hidden = false;
        return;
      }

      countdownParts.days.textContent = Math.floor(remaining / 86400000);
      countdownParts.hours.textContent = String(Math.floor((remaining % 86400000) / 3600000)).padStart(2, '0');
      countdownParts.minutes.textContent = String(Math.floor((remaining % 3600000) / 60000)).padStart(2, '0');
      countdownParts.seconds.textContent = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');
    }

    updateCountdown();
    window.setInterval(updateCountdown, 1000);

    const navToggle = document.querySelector('.nav-toggle');
    const siteNav = document.querySelector('.site-nav');
    navToggle.addEventListener('click', () => {
      const open = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!open));
      siteNav.classList.toggle('is-open', !open);
    });
    siteNav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      navToggle.setAttribute('aria-expanded', 'false');
      siteNav.classList.remove('is-open');
    }));
  </script>
</body>
</html>
