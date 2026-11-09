/* ============================================================
   ETALASE DIGITAL SKILLS — Efek interaktif & animasi
   ============================================================ */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- 1. Partikel latar belakang (canvas) ---------- */
    function initParticles() {
        var canvas = document.getElementById('bg-canvas');
        if (!canvas || reduceMotion) return;
        var ctx = canvas.getContext('2d');
        var w, h, particles = [];
        var mouse = { x: -999, y: -999 };

        function resize() {
            w = canvas.width = window.innerWidth;
            h = canvas.height = window.innerHeight;
            var count = Math.min(90, Math.floor((w * h) / 18000));
            particles = [];
            for (var i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * w,
                    y: Math.random() * h,
                    vx: (Math.random() - 0.5) * 0.4,
                    vy: (Math.random() - 0.5) * 0.4,
                    r: Math.random() * 1.8 + 0.6
                });
            }
        }

        window.addEventListener('resize', resize);
        window.addEventListener('mousemove', function (e) {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        function draw() {
            ctx.clearRect(0, 0, w, h);
            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > w) p.vx *= -1;
                if (p.y < 0 || p.y > h) p.vy *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(160, 170, 255, 0.7)';
                ctx.fill();

                for (var j = i + 1; j < particles.length; j++) {
                    var q = particles[j];
                    var dx = p.x - q.x, dy = p.y - q.y;
                    var d = Math.sqrt(dx * dx + dy * dy);
                    if (d < 120) {
                        ctx.strokeStyle = 'rgba(124, 92, 255,' + (0.22 * (1 - d / 120)) + ')';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(q.x, q.y);
                        ctx.stroke();
                    }
                }

                var mx = p.x - mouse.x, my = p.y - mouse.y;
                var md = Math.sqrt(mx * mx + my * my);
                if (md < 150) {
                    ctx.strokeStyle = 'rgba(34, 211, 238,' + (0.5 * (1 - md / 150)) + ')';
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.stroke();
                }
            }
            requestAnimationFrame(draw);
        }

        resize();
        draw();
    }

    /* ---------- 2. Efek tilt 3D pada kartu ---------- */
    function initTilt() {
        if (reduceMotion) return;
        var els = document.querySelectorAll('[data-tilt]');
        els.forEach(function (el) {
            var max = parseFloat(el.getAttribute('data-tilt')) || 8;

            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width;
                var py = (e.clientY - r.top) / r.height;
                var rx = (0.5 - py) * max * 2;
                var ry = (px - 0.5) * max * 2;
                el.style.transition = 'transform 0.08s linear';
                el.style.transform =
                    'perspective(900px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateZ(8px)';
                el.style.boxShadow = '0 24px 60px rgba(124, 92, 255, 0.35)';
            });

            el.addEventListener('mouseleave', function () {
                el.style.transition = 'transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.5s';
                el.style.transform = '';
                el.style.boxShadow = '';
            });
        });
    }

    /* ---------- 3. Kubus 3D mengikuti kursor ---------- */
    function initCubeParallax() {
        if (reduceMotion) return;
        var wraps = document.querySelectorAll('.cube-wrap');
        if (!wraps.length) return;
        window.addEventListener('mousemove', function (e) {
            var x = e.clientX / window.innerWidth - 0.5;
            var y = e.clientY / window.innerHeight - 0.5;
            wraps.forEach(function (wrap) {
                wrap.style.setProperty('--ry', (x * 40).toFixed(1) + 'deg');
                wrap.style.setProperty('--rx', (-y * 30).toFixed(1) + 'deg');
            });
        });
    }

    /* ---------- 4. Muncul saat di-scroll ---------- */
    function initReveal() {
        var items = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var siblings = Array.prototype.slice.call(entry.target.parentNode.children);
                    var idx = siblings.indexOf(entry.target);
                    entry.target.style.transitionDelay = Math.min(idx, 6) * 80 + 'ms';
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    }

    /* ---------- 5. Angka berhitung naik ---------- */
    function initCounters() {
        var nums = document.querySelectorAll('[data-count]');
        nums.forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (reduceMotion || target === 0) return;
            var start = null;
            var duration = 1200;
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / duration, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased);
                if (p < 1) requestAnimationFrame(step);
            }
            el.textContent = '0';
            requestAnimationFrame(step);
        });
    }

    /* ---------- 6. Teks mengetik otomatis ---------- */
    function initTyping() {
        var el = document.querySelector('[data-typing]');
        if (!el || reduceMotion) return;
        var words;
        try { words = JSON.parse(el.getAttribute('data-typing')); } catch (e) { return; }
        if (!words || !words.length) return;

        var w = 0, c = 0, deleting = false;
        function tick() {
            var word = words[w];
            c += deleting ? -1 : 1;
            el.textContent = word.substring(0, c);
            var delay = deleting ? 45 : 95;
            if (!deleting && c === word.length) { delay = 1700; deleting = true; }
            else if (deleting && c === 0) { deleting = false; w = (w + 1) % words.length; delay = 400; }
            setTimeout(tick, delay);
        }
        tick();
    }

    /* ---------- 7. Preview gambar sebelum upload ---------- */
    function initImagePreview() {
        var input = document.getElementById('image-input');
        var preview = document.getElementById('image-preview');
        if (!input || !preview) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }

    /* ---------- 8. Hilangkan notifikasi otomatis ---------- */
    function initAlerts() {
        document.querySelectorAll('.alert').forEach(function (a) {
            setTimeout(function () {
                a.style.transition = 'opacity 0.5s, transform 0.5s';
                a.style.opacity = '0';
                a.style.transform = 'translateY(-8px)';
                setTimeout(function () { a.remove(); }, 500);
            }, 5000);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initParticles();
        initTilt();
        initCubeParallax();
        initReveal();
        initCounters();
        initTyping();
        initImagePreview();
        initAlerts();
    });
})();
