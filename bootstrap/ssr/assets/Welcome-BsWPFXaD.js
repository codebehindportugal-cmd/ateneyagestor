import { ref, computed, onMounted, onBeforeUnmount, unref, withCtx, createVNode, createTextVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrRenderStyle } from "vue/server-renderer";
import { Head, Link } from "@inertiajs/vue3";
import AOS from "aos";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const heroSubFull1 = "Cultura, desporto e tradição no coração de Caldas da Rainha.";
const heroSubFull2 = "Junte-se à nossa comunidade.";
const _sfc_main = {
  __name: "Welcome",
  __ssrInlineRender: true,
  props: {
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
    patrocinadores: { type: Array, default: () => [] }
  },
  setup(__props) {
    const scrolled = ref(false);
    const mobileOpen = ref(false);
    const year = (/* @__PURE__ */ new Date()).getFullYear();
    function onScroll() {
      scrolled.value = window.scrollY > 60;
    }
    const stats = ref([
      { end: 30, current: 0, suffix: "+", label: "Anos de história" },
      { end: 500, current: 0, suffix: "+", label: "Sócios activos" },
      { end: 20, current: 0, suffix: "+", label: "Eventos por ano" }
    ]);
    function runCounter(stat) {
      const duration = 1800;
      const steps = 55;
      const step = stat.end / steps;
      let n = 0;
      const id = setInterval(() => {
        n = Math.min(n + step, stat.end);
        stat.current = Math.round(n);
        if (n >= stat.end) clearInterval(id);
      }, duration / steps);
    }
    let statsObserver = null;
    const particlesCanvas = ref(null);
    let particlesRAF = null;
    let particlesResizeCleanup = null;
    function initParticles(canvas) {
      const ctx = canvas.getContext("2d");
      let W = canvas.width = canvas.offsetWidth;
      let H = canvas.height = canvas.offsetHeight;
      const COUNT = 85;
      const pts = Array.from({ length: COUNT }, () => ({
        x: Math.random() * W,
        y: Math.random() * H,
        vx: (Math.random() - 0.5) * 0.38,
        vy: (Math.random() - 0.5) * 0.38,
        r: Math.random() * 1.4 + 0.4,
        a: Math.random()
      }));
      function draw() {
        ctx.clearRect(0, 0, W, H);
        for (const p of pts) {
          p.x += p.vx;
          p.y += p.vy;
          if (p.x < 0) p.x = W;
          if (p.x > W) p.x = 0;
          if (p.y < 0) p.y = H;
          if (p.y > H) p.y = 0;
        }
        for (let i = 0; i < pts.length; i++) {
          for (let j = i + 1; j < pts.length; j++) {
            const dx = pts[i].x - pts[j].x;
            const dy = pts[i].y - pts[j].y;
            const d = Math.sqrt(dx * dx + dy * dy);
            if (d < 115) {
              ctx.beginPath();
              ctx.strokeStyle = `rgba(201,168,76,${0.14 * (1 - d / 115)})`;
              ctx.lineWidth = 0.5;
              ctx.moveTo(pts[i].x, pts[i].y);
              ctx.lineTo(pts[j].x, pts[j].y);
              ctx.stroke();
            }
          }
        }
        for (const p of pts) {
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
          ctx.fillStyle = `rgba(201,168,76,${0.3 + p.a * 0.4})`;
          ctx.fill();
        }
        particlesRAF = requestAnimationFrame(draw);
      }
      draw();
      function onResize() {
        W = canvas.width = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
      }
      window.addEventListener("resize", onResize, { passive: true });
      particlesResizeCleanup = () => window.removeEventListener("resize", onResize);
    }
    const typedRaw = ref("");
    const showCursor = ref(true);
    let typingInterval = null;
    let cursorInterval = null;
    const typedLine1 = computed(() => {
      const nl = typedRaw.value.indexOf("\n");
      return nl === -1 ? typedRaw.value : typedRaw.value.slice(0, nl);
    });
    const typedLine2 = computed(() => {
      const nl = typedRaw.value.indexOf("\n");
      return nl === -1 ? "" : typedRaw.value.slice(nl + 1);
    });
    function startTyping() {
      const full = heroSubFull1 + "\n" + heroSubFull2;
      let i = 0;
      cursorInterval = setInterval(() => {
        showCursor.value = !showCursor.value;
      }, 530);
      setTimeout(() => {
        typingInterval = setInterval(() => {
          typedRaw.value = full.slice(0, ++i);
          if (i >= full.length) clearInterval(typingInterval);
        }, 30);
      }, 1300);
    }
    onMounted(() => {
      AOS.init({ duration: 900, easing: "ease-out-cubic", once: true, offset: 70 });
      window.addEventListener("scroll", onScroll, { passive: true });
      const statsEl = document.querySelector(".stats-row");
      if (statsEl) {
        statsObserver = new IntersectionObserver(([e]) => {
          if (e.isIntersecting) {
            stats.value.forEach(runCounter);
            statsObserver.disconnect();
          }
        }, { threshold: 0.3 });
        statsObserver.observe(statsEl);
      }
      if (particlesCanvas.value) initParticles(particlesCanvas.value);
      startTyping();
    });
    onBeforeUnmount(() => {
      window.removeEventListener("scroll", onScroll);
      statsObserver?.disconnect();
      if (particlesRAF) cancelAnimationFrame(particlesRAF);
      if (particlesResizeCleanup) particlesResizeCleanup();
      if (typingInterval) clearInterval(typingInterval);
      if (cursorInterval) clearInterval(cursorInterval);
    });
    const navLinks = [
      ["Sobre Nós", "#sobre"],
      ["Eventos", "#eventos"],
      ["Patrocinadores", "#patrocinadores"],
      ["Contacto", "#contacto"]
    ];
    const events = [
      {
        img: "/images/events/santana-2026-cartaz.png",
        date: "Julho 2026",
        title: "Festa de Santana 2026",
        desc: "O maior evento cultural da região, com música ao vivo, gastronomia típica e tradição.",
        tag: "Evento Principal"
      },
      {
        img: "/images/events/afro-tropical-night-cartaz.png",
        date: "Agosto 2026",
        title: "Afro Tropical Night",
        desc: "Uma noite vibrante de ritmos africanos e tropicais no coração de Santana.",
        tag: "Música & Dança"
      },
      {
        img: "/images/events/caminhada-primavera-2026.jpeg",
        date: "Maio 2026",
        title: "Caminhada da Primavera",
        desc: "Descobre a beleza natural da Serra da Candeeiros numa caminhada inesquecível.",
        tag: "Desporto & Natureza"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Associação Recreativa de Santana" }, null, _parent));
      _push(`<link rel="preconnect" href="https://fonts.googleapis.com" data-v-336e3ced><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin data-v-336e3ced><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,400;1,700&amp;family=Inter:wght@300;400;500;600;700&amp;display=swap" data-v-336e3ced><div class="pg-root" data-v-336e3ced><header class="${ssrRenderClass([{ "nav-solid": scrolled.value }, "site-nav"])}" data-v-336e3ced><div class="nav-inner" data-v-336e3ced>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("home"),
        class: "logo-wrap"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img src="/images/santana-logo.png" alt="ARDC Santana" class="logo-img" data-v-336e3ced${_scopeId}><span class="logo-name" data-v-336e3ced${_scopeId}>ARDC Santana</span>`);
          } else {
            return [
              createVNode("img", {
                src: "/images/santana-logo.png",
                alt: "ARDC Santana",
                class: "logo-img"
              }),
              createVNode("span", { class: "logo-name" }, "ARDC Santana")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<nav class="desk-links" aria-label="Navegação principal" data-v-336e3ced><!--[-->`);
      ssrRenderList(navLinks, ([label, href]) => {
        _push(`<a${ssrRenderAttr("href", href)} class="nav-a" data-v-336e3ced>${ssrInterpolate(label)}</a>`);
      });
      _push(`<!--]-->`);
      if (__props.canLogin && !_ctx.$page.props.auth?.user) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("login"),
          class: "btn-entrar"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Entrar`);
            } else {
              return [
                createTextVNode("Entrar")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (_ctx.$page.props.auth?.user) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("dashboard"),
          class: "btn-entrar btn-entrar-filled"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Dashboard`);
            } else {
              return [
                createTextVNode("Dashboard")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</nav><button type="button" class="${ssrRenderClass([{ "is-open": mobileOpen.value }, "hamburger"])}" aria-label="Abrir menu" data-v-336e3ced><span class="hb-line" data-v-336e3ced></span><span class="hb-line" data-v-336e3ced></span><span class="hb-line" data-v-336e3ced></span></button></div>`);
      if (mobileOpen.value) {
        _push(`<div class="mobile-panel" data-v-336e3ced><!--[-->`);
        ssrRenderList(navLinks, ([label, href]) => {
          _push(`<a${ssrRenderAttr("href", href)} class="mp-link" data-v-336e3ced>${ssrInterpolate(label)}</a>`);
        });
        _push(`<!--]-->`);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("login"),
          class: "mp-entrar",
          onClick: ($event) => mobileOpen.value = false
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Entrar`);
            } else {
              return [
                createTextVNode("Entrar")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</header><section class="hero-sec" data-v-336e3ced><div class="hero-bg-base" aria-hidden="true" data-v-336e3ced></div><div class="hero-bg-glow" aria-hidden="true" data-v-336e3ced></div><canvas class="hero-canvas" aria-hidden="true" data-v-336e3ced></canvas><div class="hero-ornament" aria-hidden="true" data-v-336e3ced><div class="orn-ring orn-ring-1" data-v-336e3ced></div><div class="orn-ring orn-ring-2" data-v-336e3ced></div><div class="orn-ring orn-ring-3" data-v-336e3ced></div><div class="orn-line orn-line-h" data-v-336e3ced></div><div class="orn-line orn-line-v" data-v-336e3ced></div></div><div class="hero-overlay" aria-hidden="true" data-v-336e3ced></div><div class="hero-body" data-v-336e3ced><p class="hero-eyebrow" data-aos="fade-down" data-aos-delay="100" data-v-336e3ced><span class="eyebrow-pulse" data-v-336e3ced></span> Santana · Caldas da Rainha </p><h1 class="hero-title" data-aos="fade-up" data-aos-delay="200" data-v-336e3ced><span class="ht-top" data-v-336e3ced>Associação</span><span class="ht-bottom" data-v-336e3ced>de Santana</span></h1><div class="hero-rule" data-aos="zoom-in" data-aos-delay="380" aria-hidden="true" data-v-336e3ced></div><p class="hero-sub hero-sub-typed" data-aos="fade-up" data-aos-delay="440" data-v-336e3ced>${ssrInterpolate(typedLine1.value)}`);
      if (typedLine2.value) {
        _push(`<br data-v-336e3ced>`);
      } else {
        _push(`<!---->`);
      }
      _push(`${ssrInterpolate(typedLine2.value)}<span class="${ssrRenderClass([{ "cursor-off": !showCursor.value }, "hero-cursor"])}" aria-hidden="true" data-v-336e3ced>|</span></p><div class="hero-ctas" data-aos="fade-up" data-aos-delay="580" data-v-336e3ced><a href="#sobre" class="cta-ghost" data-v-336e3ced>Saber Mais</a>`);
      if (__props.canLogin) {
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("login"),
          class: "cta-gold"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Entrar na plataforma`);
            } else {
              return [
                createTextVNode("Entrar na plataforma")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="scroll-cue" aria-hidden="true" data-v-336e3ced><span class="sc-label" data-v-336e3ced>Scroll</span><span class="sc-bar" data-v-336e3ced></span></div><div class="hero-fade-bottom" aria-hidden="true" data-v-336e3ced></div></section><section id="sobre" class="sobre-sec" data-v-336e3ced><div class="sobre-inner" data-v-336e3ced><div class="sobre-text" data-aos="fade-right" data-aos-duration="1000" data-v-336e3ced><p class="sec-label" data-v-336e3ced>Quem Somos</p><h2 class="sec-h2 dark-h" data-v-336e3ced> Uma Associação<br data-v-336e3ced>ao Serviço<br data-v-336e3ced><em data-v-336e3ced>da Comunidade</em></h2><div class="sobre-rule" aria-hidden="true" data-v-336e3ced></div><p class="sobre-para" data-v-336e3ced> A Associação Recreativa e Desportiva do Carvalhal de Santana (ARDC Santana) é uma instituição sem fins lucrativos dedicada à promoção da cultura, desporto e bem-estar da comunidade de Santana, em Caldas da Rainha. </p><p class="sobre-para mt-4" data-v-336e3ced> Fundada com a missão de preservar e promover as tradições locais, organizamos eventos culturais, actividades desportivas e iniciativas que fortalecem os laços comunitários. </p><div class="stats-row" data-v-336e3ced><!--[-->`);
      ssrRenderList(stats.value, (s) => {
        _push(`<div class="stat-item" data-v-336e3ced><span class="stat-num" data-v-336e3ced>${ssrInterpolate(s.current)}${ssrInterpolate(s.suffix)}</span><span class="stat-lbl" data-v-336e3ced>${ssrInterpolate(s.label)}</span></div>`);
      });
      _push(`<!--]--></div><a${ssrRenderAttr("href", _ctx.route("pages.sobre-nos"))} class="link-arrow" data-v-336e3ced> Conhecer a Associação <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" data-v-336e3ced><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" data-v-336e3ced></path></svg></a></div><div class="sobre-img-col" data-aos="fade-left" data-aos-delay="150" data-aos-duration="1000" data-v-336e3ced><div class="sobre-frame" data-v-336e3ced><img src="/images/santa-ana-transparent.png" alt="Associação de Santana" class="sobre-img" data-v-336e3ced><span class="fc fc-tl" aria-hidden="true" data-v-336e3ced></span><span class="fc fc-tr" aria-hidden="true" data-v-336e3ced></span><span class="fc fc-bl" aria-hidden="true" data-v-336e3ced></span><span class="fc fc-br" aria-hidden="true" data-v-336e3ced></span></div></div></div></section><section id="eventos" class="eventos-sec" data-v-336e3ced><div class="eventos-inner" data-v-336e3ced><div class="sec-header" data-aos="fade-up" data-v-336e3ced><p class="sec-label light-lbl" data-v-336e3ced>Próximos</p><h2 class="sec-h2 light-h" data-v-336e3ced>Eventos &amp; Actividades</h2><div class="sec-rule" aria-hidden="true" data-v-336e3ced></div></div><div class="eventos-grid" data-v-336e3ced><!--[-->`);
      ssrRenderList(events, (ev, i) => {
        _push(`<article class="ev-card" data-aos="fade-up"${ssrRenderAttr("data-aos-delay", i * 120)} data-v-336e3ced><div class="ev-img-wrap" data-v-336e3ced><img${ssrRenderAttr("src", ev.img)}${ssrRenderAttr("alt", ev.title)} class="ev-img" loading="lazy" data-v-336e3ced><div class="ev-img-grad" aria-hidden="true" data-v-336e3ced></div><span class="ev-tag" data-v-336e3ced>${ssrInterpolate(ev.tag)}</span></div><div class="ev-body" data-v-336e3ced><time class="ev-date" data-v-336e3ced>${ssrInterpolate(ev.date)}</time><h3 class="ev-title" data-v-336e3ced>${ssrInterpolate(ev.title)}</h3><p class="ev-desc" data-v-336e3ced>${ssrInterpolate(ev.desc)}</p><span class="ev-link" data-v-336e3ced> Ver detalhes <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true" data-v-336e3ced><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" data-v-336e3ced></path></svg></span></div></article>`);
      });
      _push(`<!--]--></div><div class="ev-more" data-aos="fade-up" data-aos-delay="200" data-v-336e3ced><a href="#eventos" class="btn-outline-gold" data-v-336e3ced>Ver todos os eventos</a></div></div></section><section id="patrocinadores" class="sponsors-sec" data-v-336e3ced><div class="sponsors-header" data-aos="fade-up" data-v-336e3ced><p class="sec-label" data-v-336e3ced>Com o apoio de</p><h2 class="sec-h2 dark-h" style="${ssrRenderStyle({ "font-size": "clamp(1.8rem,4vw,3rem)" })}" data-v-336e3ced> Os Nossos Parceiros </h2><div class="sobre-rule" aria-hidden="true" data-v-336e3ced></div></div>`);
      if (__props.patrocinadores.length) {
        _push(`<div class="marquee-wrap" data-aos="fade-up" data-aos-delay="100" data-v-336e3ced><div class="marquee-track" data-v-336e3ced><!--[-->`);
        ssrRenderList([...__props.patrocinadores, ...__props.patrocinadores, ...__props.patrocinadores], (s, i) => {
          _push(`<span class="sp-item" data-v-336e3ced>`);
          if (s.logo_url) {
            _push(`<img${ssrRenderAttr("src", s.logo_url)}${ssrRenderAttr("alt", s.empresa)} class="sp-logo" loading="lazy" data-v-336e3ced>`);
          } else {
            _push(`<span data-v-336e3ced>${ssrInterpolate(s.empresa)}</span>`);
          }
          _push(`</span>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="sp-cta" data-aos="fade-up" data-aos-delay="150" data-v-336e3ced><a${ssrRenderAttr("href", _ctx.route("patrocinios.index"))} class="link-arrow dark-arrow" data-v-336e3ced> Ver todos os patrocinadores <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" data-v-336e3ced><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" data-v-336e3ced></path></svg></a></div></section><section id="contacto" class="contacto-sec" data-v-336e3ced><div class="contacto-inner" data-v-336e3ced><div class="sec-header" data-aos="fade-up" data-v-336e3ced><p class="sec-label light-lbl" data-v-336e3ced>Contacto</p><h2 class="sec-h2 light-h" data-v-336e3ced>Fale Connosco</h2><div class="sec-rule" aria-hidden="true" data-v-336e3ced></div><p class="contacto-sub" data-v-336e3ced> Questões, sugestões ou interesse em tornar-se sócio?<br data-v-336e3ced> Estamos ao seu dispor. </p></div><div class="contacto-grid" data-v-336e3ced><div class="ct-card" data-aos="fade-up" data-aos-delay="0" data-v-336e3ced><div class="ct-icon" data-v-336e3ced>✉</div><p class="ct-label" data-v-336e3ced>Email</p><a href="mailto:ardcsantana@outlook.com" class="ct-val" data-v-336e3ced>ardcsantana@outlook.com</a></div><div class="ct-card" data-aos="fade-up" data-aos-delay="100" data-v-336e3ced><div class="ct-icon" data-v-336e3ced>📍</div><p class="ct-label" data-v-336e3ced>Localização</p><span class="ct-val" data-v-336e3ced>Santana, Carvalhal Benfeito<br data-v-336e3ced>Caldas da Rainha</span></div><div class="ct-card" data-aos="fade-up" data-aos-delay="200" data-v-336e3ced><div class="ct-icon" data-v-336e3ced>📱</div><p class="ct-label" data-v-336e3ced>Redes Sociais</p><a href="#" class="ct-val" data-v-336e3ced>@ardcsantana</a></div></div></div></section><footer class="site-footer" data-v-336e3ced><div class="footer-gold-bar" aria-hidden="true" data-v-336e3ced></div><div class="footer-inner" data-v-336e3ced><div class="ft-col ft-brand" data-v-336e3ced>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("home"),
        class: "ft-logo-wrap"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img src="/images/santana-logo.png" alt="ARDC Santana" class="ft-logo" data-v-336e3ced${_scopeId}><span class="ft-logo-name" data-v-336e3ced${_scopeId}>ARDC Santana</span>`);
          } else {
            return [
              createVNode("img", {
                src: "/images/santana-logo.png",
                alt: "ARDC Santana",
                class: "ft-logo"
              }),
              createVNode("span", { class: "ft-logo-name" }, "ARDC Santana")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<p class="ft-tagline" data-v-336e3ced> A unir a comunidade através<br data-v-336e3ced>da cultura, desporto e convívio. </p><div class="ft-socials" data-v-336e3ced><a href="#" class="ft-social-btn" aria-label="Facebook" data-v-336e3ced><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" data-v-336e3ced><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" data-v-336e3ced></path></svg></a><a href="#" class="ft-social-btn" aria-label="Instagram" data-v-336e3ced><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" data-v-336e3ced><rect x="2" y="2" width="20" height="20" rx="5" ry="5" data-v-336e3ced></rect><circle cx="12" cy="12" r="4" data-v-336e3ced></circle><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" data-v-336e3ced></circle></svg></a></div></div><div class="ft-col" data-v-336e3ced><p class="ft-heading" data-v-336e3ced>Navegação</p><ul class="ft-list" data-v-336e3ced><!--[-->`);
      ssrRenderList(navLinks, ([label, href]) => {
        _push(`<li data-v-336e3ced><a${ssrRenderAttr("href", href)} data-v-336e3ced>${ssrInterpolate(label)}</a></li>`);
      });
      _push(`<!--]--></ul></div><div class="ft-col" data-v-336e3ced><p class="ft-heading" data-v-336e3ced>Legal &amp; Contacto</p><ul class="ft-list" data-v-336e3ced><li data-v-336e3ced>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.privacidade")
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Privacidade`);
          } else {
            return [
              createTextVNode("Privacidade")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li data-v-336e3ced>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.termos")
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Termos`);
          } else {
            return [
              createTextVNode("Termos")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li data-v-336e3ced>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.cookies")
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Cookies`);
          } else {
            return [
              createTextVNode("Cookies")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li data-v-336e3ced><a href="mailto:ardcsantana@outlook.com" data-v-336e3ced>Email</a></li></ul></div></div><div class="footer-copy" data-v-336e3ced> © ${ssrInterpolate(unref(year))} Associação de Santana · Desenvolvido por <a href="https://ateneya.com/" target="_blank" rel="noopener noreferrer" data-v-336e3ced>Ateneya</a></div></footer></div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Welcome.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Welcome = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-336e3ced"]]);
export {
  Welcome as default
};
