/* loading-orb.js — <hn-orb> لودر ارگانیک (پورت وانیلا از LoadingOrb ری‌اکت)
   ویژگی‌ها: size، speed (ms هر فاز morph)، motion (morph|pulse|spin)،
   radius، gap، count، ghost (پیشفرض روشن) — رنگ از کلاس/متن میزبان خوانده می‌شود. */
(function () {
  'use strict';

  const REDUCE = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  }
  function parseColor(css) {
    const m = /rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/.exec(css || '');
    if (m) return [+m[1], +m[2], +m[3], m[4] === undefined ? 1 : +m[4]];
    return [46, 125, 79, 1];
  }

  /* نقطه‌های گذاشته‌شده روی محیط چندضلعی منتظم (_vertex بالا، مربع راست‌گوشه) */
  function polygonDots(n, sides, gap) {
    const offset = sides === 4 ? Math.PI / 4 : 0;
    const verts = [];
    for (let k = 0; k < sides; k++) {
      const a = -Math.PI / 2 + offset + (k * 2 * Math.PI) / sides;
      verts.push([Math.cos(a), Math.sin(a)]);
    }
    const edges = [], lens = [];
    let total = 0;
    for (let k = 0; k < sides; k++) {
      const a = verts[k], b = verts[(k + 1) % sides];
      const l = Math.hypot(b[0] - a[0], b[1] - a[1]);
      edges.push([a, b]); lens.push(l); total += l;
    }
    const spread = 1 + gap / 12;               // gap = فاصله اضافه شکل‌ها
    const out = [];
    for (let i = 0; i < n; i++) {
      let s = (i / n) * total;
      let k = 0;
      while (k < sides - 1 && s > lens[k]) { s -= lens[k]; k++; }
      const [a, b] = edges[k], l = lens[k], t = l ? s / l : 0;
      out.push([
        (a[0] + (b[0] - a[0]) * t) * spread,
        (a[1] + (b[1] - a[1]) * t) * spread,
      ]);
    }
    return out;
  }

  function ringDots(n, gap) {
    const spread = 1 + gap / 14;
    return Array.from({ length: n }, (_, i) => {
      const a = -Math.PI / 2 + (i * 2 * Math.PI) / n;
      return [Math.cos(a) * spread, Math.sin(a) * spread];
    });
  }

  function lineDots(n, gap) {
    const half = 1.1 + gap / 10;
    return Array.from({ length: n }, (_, i) => {
      const x = n === 1 ? 0 : (i / (n - 1) - 0.5) * 2 * half;
      return [x, 0];
    });
  }

  /* فازهای morph: حلقه ← خط ← مثلث ← مربع ← حلقه */
  const MORPH_SHAPES = [ringDots, lineDots, n => polygonDots(n, 3, 3), n => polygonDots(n, 4, 3), ringDots];

  class HNOrb extends HTMLElement {
    static get observedAttributes() {
      return ['size', 'speed', 'motion', 'radius', 'gap', 'count', 'ghost'];
    }
    constructor() {
      super();
      this._raf = null;
      this._history = [];
      this._t0 = performance.now();
    }
    connectedCallback() { this._render(true); }
    disconnectedCallback() { this._stop(); }
    attributeChangedCallback() { if (this.isConnected) this._render(true); }

    get size() { const v = parseFloat(this.getAttribute('size')); return isFinite(v) && v > 12 ? v : 64; }
    get speed() { const v = parseFloat(this.getAttribute('speed')); return isFinite(v) && v >= 200 ? v : 750; }
    get motion() { return ['morph', 'pulse', 'spin'].includes(this.getAttribute('motion')) ? this.getAttribute('motion') : 'morph'; }
    get radius() { const v = parseFloat(this.getAttribute('radius')); return isFinite(v) ? v : 3; }
    get gap() { const v = parseFloat(this.getAttribute('gap')); return isFinite(v) ? v : 3; }
    get count() { const v = parseInt(this.getAttribute('count'), 10); return isFinite(v) ? Math.min(Math.max(v, 3), 40) : 10; }
    get ghost() { return this.getAttribute('ghost') !== 'false'; }

    _render(reset) {
      this.attachShadow({ mode: 'open' }).innerHTML = '<canvas part="orb" style="display:block"></canvas>';
      const cv = this.shadowRoot.querySelector('canvas');
      const dpr = Math.min(devicePixelRatio || 1, 2);
      const s = this.size;
      cv.style.width = s + 'px'; cv.style.height = s + 'px';
      cv.width = Math.round(s * dpr); cv.height = Math.round(s * dpr);
      this._cv = cv; this._ctx = cv.getContext('2d'); this._dpr = dpr;
      if (reset) { this._history = new Array(this.count).fill(null).map(() => []); this._t0 = performance.now(); }
      this._stop();
      if (REDUCE) { this._frame(performance.now()); return; }
      const loop = (t) => { this._frame(t); this._raf = requestAnimationFrame(loop); };
      this._raf = requestAnimationFrame(loop);
    }
    _stop() { if (this._raf) cancelAnimationFrame(this._raf); this._raf = null; }

    /* مختصات واحد (با پیش‌感روی موج گهواره) */
    _targets(t) {
      const c = this.count;
      const k = 0.42 + Math.min(this.radius, 10) * 0.06;
      const bob = Math.sin(t / 1900) * 0.05;
      if (this.motion === 'pulse') {
        return ringDots(c, this.gap).map(([x, y], i) => {
          const r = 0.78 + 0.2 * Math.sin(t / this.speed * Math.PI * 2 - i * 0.55);
          return [x * r * k, y * r * k + bob];
        });
      }
      if (this.motion === 'spin') {
        return Array.from({ length: c }, (_, i) => {
          const a = (i * 2 * Math.PI) / c + (t / 1000) * (1.1 + 0.35 * Math.sin(t / 900 + i * 1.7));
          return [Math.cos(a) * k, Math.sin(a) * k + bob];
        });
      }
      // morph — هر نقطه اول کمی دیرتر از قبلی جابه‌جا می‌شود (شکاف ارگانیک)
      const seg = Math.floor(t / this.speed), segLen = this.speed;
      const from = MORPH_SHAPES[((seg % MORPH_SHAPES.length) + MORPH_SHAPES.length) % MORPH_SHAPES.length](c, this.gap);
      const to = MORPH_SHAPES[(((seg + 1) % MORPH_SHAPES.length) + MORPH_SHAPES.length) % MORPH_SHAPES.length](c, this.gap);
      return from.map((f, i) => {
        const local = Math.min(Math.max((t % segLen - Math.min(i * 24, segLen * 0.3)) / segLen, 0), 1);
        const e = easeInOutCubic(local);
        return [
          (f[0] + (to[i][0] - f[0]) * e) * k,
          (f[1] + (to[i][1] - f[1]) * e) * k + bob,
        ];
      });
    }

    _frame(t) {
      const ctx = this._ctx, dpr = this._dpr, s = this.size;
      const col = parseColor(getComputedStyle(this).color);
      const unit = (s / 2 - Math.max(1.8, s * 0.05) - Math.max(3, s * 0.07)) * 0.96;
      const dotR = Math.max(1.8, s * 0.05);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, s, s);
      const pts = this._targets(REDUCE ? 0 : t - this._t0).map(([x, y]) => [s / 2 + x * unit, s / 2 + y * unit]);

      /* دنباله شبح (ghost) */
      if (this.ghost && !REDUCE) {
        for (let i = 0; i < pts.length; i++) {
          const h = this._history[i];
          h.push(pts[i]);
          if (h.length > 9) h.shift();
          for (let q = 0; q < h.length - 1; q++) {
            const a = ((q + 1) / h.length) * 0.2;
            ctx.globalAlpha = a * a;
            ctx.fillStyle = `rgba(${col[0]},${col[1]},${col[2]},1)`;
            ctx.beginPath();
            ctx.arc(h[q][0], h[q][1], dotR * (0.85 - 0.05 * (h.length - q)), 0, 7);
            ctx.fill();
          }
        }
      }

      /* نقطه‌های اصلی با هاله نرم */
      ctx.globalAlpha = 1;
      ctx.shadowColor = `rgba(${col[0]},${col[1]},${col[2]},0.9)`;
      ctx.shadowBlur = dotR * 2.6;
      ctx.fillStyle = `rgba(${col[0]},${col[1]},${col[2]},1)`;
      for (const [x, y] of pts) {
        ctx.beginPath();
        ctx.arc(x, y, dotR, 0, 7);
        ctx.fill();
      }
      ctx.shadowBlur = 0;
    }
  }

  customElements.define('hn-orb', HNOrb);
  window.HNOrb = HNOrb;
})();
