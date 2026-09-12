import { mergeProps, unref, useSSRContext, ref, computed, onMounted, onBeforeUnmount, withCtx, createVNode, createTextVNode } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrRenderStyle, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { useForm, Head, Link } from "@inertiajs/vue3";
import { _ as _sfc_main$3 } from "./CookieBanner-Cf0YSWpg.js";
import { _ as _sfc_main$2 } from "./SponsorsSlider-CUXZJoI8.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const associationLogo$1 = "/images/santana-logo.png";
const _sfc_main$1 = {
  __name: "SantanaHeroScene",
  __ssrInlineRender: true,
  props: {
    compact: {
      type: Boolean,
      default: false
    }
  },
  setup(__props) {
    const props = __props;
    const particles = Array.from({ length: 18 }, (_, index) => index);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["logo-3d-scene", { "is-compact": props.compact }],
        "aria-hidden": "true"
      }, _attrs))} data-v-c6852df9><div class="logo-3d-field" data-v-c6852df9><!--[-->`);
      ssrRenderList(unref(particles), (particle) => {
        _push(`<span class="logo-3d-particle" style="${ssrRenderStyle({
          "--i": particle,
          "--delay": `${particle * -0.28}s`,
          "--distance": `${64 + particle % 6 * 18}px`
        })}" data-v-c6852df9></span>`);
      });
      _push(`<!--]--></div><div class="logo-3d-orbit logo-3d-orbit-a" data-v-c6852df9></div><div class="logo-3d-orbit logo-3d-orbit-b" data-v-c6852df9></div><div class="logo-3d-orbit logo-3d-orbit-c" data-v-c6852df9></div><div class="logo-3d-stage" data-v-c6852df9><div class="logo-3d-shadow" data-v-c6852df9></div><div class="logo-3d-card" data-v-c6852df9><img${ssrRenderAttr("src", associationLogo$1)} alt="" class="logo-3d-img logo-3d-img-back" data-v-c6852df9><img${ssrRenderAttr("src", associationLogo$1)} alt="" class="logo-3d-img logo-3d-img-mid" data-v-c6852df9><img${ssrRenderAttr("src", associationLogo$1)} alt="" class="logo-3d-img logo-3d-img-front" data-v-c6852df9></div></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/SantanaHeroScene.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const SantanaHeroScene = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-c6852df9"]]);
const associationLogo = "/images/santana-logo.png";
const santaAnaImage = "/images/santa-ana.png";
const contactEmail = "ardcsantana@outlook.com";
const _sfc_main = {
  __name: "Home",
  __ssrInlineRender: true,
  props: {
    upcomingEvents: Array,
    pastEvents: Array,
    patrocinadores: Array
  },
  setup(__props) {
    const props = __props;
    const currentYear = (/* @__PURE__ */ new Date()).getFullYear();
    const menuOpen = ref(false);
    const selectedEventTab = ref("todos");
    const activeHeroSlide = ref(0);
    const lightboxItem = ref(null);
    const openFaq = ref(null);
    const formSent = ref(false);
    const form = useForm({ name: "", email: "", phone: "", message: "" });
    const errors = ref({});
    const upcoming = computed(() => props.upcomingEvents ?? []);
    const archived = computed(() => props.pastEvents ?? []);
    const allEvents = computed(() => [...upcoming.value, ...archived.value]);
    const heroEvents = computed(() => {
      const source = upcoming.value.length ? upcoming.value : allEvents.value;
      return source.slice(0, 5);
    });
    const activeHeroEvent = computed(() => heroEvents.value[activeHeroSlide.value] ?? null);
    const heroVisual = computed(() => activeHeroEvent.value?.poster || santaAnaImage);
    let heroTimer;
    const navLinks = [
      ["Início", "/"],
      ["Sobre Nós", route("pages.sobre-nos")],
      ["Eventos", "#eventos"],
      ["Patrocínios", route("patrocinios.index")],
      ["Contacto", "#contactos"]
    ];
    const pillars = [
      { icon: "🎭", label: "Cultura", text: "Mantemos vivas as tradições, as festas e os momentos que contam a história de Santana." },
      { icon: "⚽", label: "Desporto", text: "Criamos oportunidades para caminhar, mexer, participar e juntar gerações." },
      { icon: "🤝", label: "Convívio", text: "A associação é uma casa aberta para sócios, famílias, amigos e visitantes." }
    ];
    const eventTabs = computed(() => {
      const badges = [...new Set(allEvents.value.map((e) => e.badge).filter(Boolean))];
      return [
        { key: "todos", label: "Todos" },
        { key: "proximos", label: "Próximos" },
        { key: "anteriores", label: "Anteriores" },
        ...badges.map((b) => ({ key: `badge:${b}`, label: b }))
      ];
    });
    const filteredEvents = computed(() => {
      if (selectedEventTab.value === "proximos") return upcoming.value;
      if (selectedEventTab.value === "anteriores") return archived.value;
      if (selectedEventTab.value.startsWith("badge:")) {
        const badge = selectedEventTab.value.replace("badge:", "");
        return allEvents.value.filter((e) => e.badge === badge);
      }
      return allEvents.value;
    });
    const featuredEvent = computed(() => filteredEvents.value[0] ?? allEvents.value[0] ?? null);
    const galleryItems = computed(() => {
      const media = allEvents.value.flatMap((e) => (e.media ?? []).map((m) => ({ ...m, event: e.title, category: e.badge || "Momentos especiais" }))).filter((m) => m.tipo === "foto");
      const posters = allEvents.value.filter((e) => e.poster).map((e) => ({ tipo: "foto", caminho: e.poster, titulo: e.title, event: e.date, category: e.badge || "Comunidade" }));
      return [...media, ...posters].slice(0, 9);
    });
    const faqs = [
      ["Como posso tornar-me sócio?", "Preenche o formulário nesta página ou contacta a associação por email, telefone ou redes sociais."],
      ["Os eventos são abertos a não sócios?", "Muitas iniciativas são abertas à comunidade. Quando existir inscrição obrigatória, essa indicação aparece no evento."],
      ["Posso ajudar como voluntário?", "Sim. Toda a ajuda conta: preparação de eventos, apoio no bar, divulgação e novas ideias para a associação."],
      ["Onde acompanho novidades?", "Segue a ARDC Santana no Facebook e Instagram para veres cartazes, fotografias e avisos recentes."]
    ];
    const calendarHref = (event) => {
      const title = encodeURIComponent(event.title);
      const details = encodeURIComponent(event.description || "Evento ARDC Santana");
      const location = encodeURIComponent(event.location || event.subtitle || "ARDC Santana");
      return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&details=${details}&location=${location}`;
    };
    const eventHref = (event) => route("eventos.public.show", event.id);
    const selectHeroSlide = (index) => {
      if (!heroEvents.value.length) return;
      activeHeroSlide.value = (index + heroEvents.value.length) % heroEvents.value.length;
    };
    const nextHeroSlide = () => selectHeroSlide(activeHeroSlide.value + 1);
    onMounted(() => {
      const items = document.querySelectorAll(".reveal");
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) e.target.classList.add("is-visible");
        });
      }, { threshold: 0.12 });
      items.forEach((el) => observer.observe(el));
      if (heroEvents.value.length > 1) {
        heroTimer = window.setInterval(nextHeroSlide, 6e3);
      }
    });
    onBeforeUnmount(() => window.clearInterval(heroTimer));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "ARDC Santana | Associação Recreativa, Desportiva e Cultural" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<meta head-key="description" name="description" content="Conhece a ARDC Santana, participa nos nossos eventos, torna-te sócio e ajuda a manter viva a comunidade." data-v-7ea0ea17${_scopeId}>`);
          } else {
            return [
              createVNode("meta", {
                "head-key": "description",
                name: "description",
                content: "Conhece a ARDC Santana, participa nos nossos eventos, torna-te sócio e ajuda a manter viva a comunidade."
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<main class="min-h-screen bg-amber-50 text-stone-800 scroll-smooth" data-v-7ea0ea17><header class="fixed inset-x-0 top-0 z-50 border-b border-amber-200/80 bg-amber-50/95 backdrop-blur-xl" data-v-7ea0ea17><nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3.5 lg:px-8" data-v-7ea0ea17>`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/",
        class: "flex items-center gap-3"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", associationLogo)} alt="Logo ARDC Santana" class="h-10 w-10 rounded-full object-contain bg-white border border-amber-200 p-1" data-v-7ea0ea17${_scopeId}><span class="font-display text-base font-semibold tracking-wide text-stone-800" data-v-7ea0ea17${_scopeId}>ARDC Santana</span>`);
          } else {
            return [
              createVNode("img", {
                src: associationLogo,
                alt: "Logo ARDC Santana",
                class: "h-10 w-10 rounded-full object-contain bg-white border border-amber-200 p-1"
              }),
              createVNode("span", { class: "font-display text-base font-semibold tracking-wide text-stone-800" }, "ARDC Santana")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="hidden items-center gap-0.5 md:flex" data-v-7ea0ea17><!--[-->`);
      ssrRenderList(navLinks, (link) => {
        _push(`<button type="button" class="rounded-md px-3.5 py-2 text-sm font-medium text-stone-600 transition hover:text-stone-900 hover:bg-amber-100" data-v-7ea0ea17>${ssrInterpolate(link[0])}</button>`);
      });
      _push(`<!--]--></div><a${ssrRenderAttr("href", `mailto:${contactEmail}`)} class="hidden lg:inline-flex items-center gap-2 rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700" data-v-7ea0ea17> Contactar </a><button type="button" class="grid h-10 w-10 place-items-center rounded-md text-stone-600 hover:bg-amber-100 md:hidden" aria-label="Abrir menu" data-v-7ea0ea17><span class="${ssrRenderClass([{ open: menuOpen.value }, "hamburger"])}" data-v-7ea0ea17></span></button></nav>`);
      if (menuOpen.value) {
        _push(`<div class="border-t border-amber-200 bg-amber-50 px-5 py-3 md:hidden" data-v-7ea0ea17><!--[-->`);
        ssrRenderList(navLinks, (link) => {
          _push(`<button type="button" class="block w-full rounded-md px-3 py-2.5 text-left text-sm font-medium text-stone-600 hover:text-stone-900 hover:bg-amber-100" data-v-7ea0ea17>${ssrInterpolate(link[0])}</button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</header><section class="relative isolate flex min-h-screen flex-col justify-end overflow-hidden pt-20 bg-gray-950" data-v-7ea0ea17><div class="absolute inset-0 -z-30" data-v-7ea0ea17>`);
      _push(ssrRenderComponent(SantanaHeroScene, null, null, _parent));
      _push(`</div><img${ssrRenderAttr("src", heroVisual.value)} alt="" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-25" data-v-7ea0ea17><div class="absolute inset-0 -z-10 bg-gradient-to-t from-stone-900/95 via-stone-800/40 to-transparent" data-v-7ea0ea17></div><div class="absolute inset-0 -z-10 bg-gradient-to-r from-stone-900/70 via-stone-900/15 to-transparent" data-v-7ea0ea17></div><div class="mx-auto w-full max-w-7xl px-5 pb-16 lg:px-8" data-v-7ea0ea17><div class="reveal max-w-3xl" data-v-7ea0ea17><p class="eyebrow-hero" data-v-7ea0ea17>${ssrInterpolate(activeHeroEvent.value?.badge || "Associação Recreativa, Desportiva e Cultural")}</p><h1 class="font-display mt-4 text-5xl font-bold leading-[1.07] text-white sm:text-6xl lg:text-7xl" data-v-7ea0ea17>${ssrInterpolate(activeHeroEvent.value?.title || "ARDC Santana")}</h1><p class="mt-5 max-w-xl text-lg leading-relaxed text-stone-200" data-v-7ea0ea17>${ssrInterpolate(activeHeroEvent.value?.description || "Cultura, desporto e comunidade numa casa viva, feita por pessoas e para pessoas.")}</p>`);
      if (activeHeroEvent.value?.date) {
        _push(`<p class="mt-2 text-sm font-semibold text-amber-300" data-v-7ea0ea17>${ssrInterpolate(activeHeroEvent.value.date)} · ${ssrInterpolate(activeHeroEvent.value.location || activeHeroEvent.value.subtitle)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="mt-8 flex flex-wrap gap-3" data-v-7ea0ea17>`);
      if (activeHeroEvent.value?.id) {
        _push(ssrRenderComponent(unref(Link), {
          href: eventHref(activeHeroEvent.value),
          class: "rounded-md bg-amber-500 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-amber-600"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Ver evento `);
            } else {
              return [
                createTextVNode(" Ver evento ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<button type="button" class="rounded-md bg-amber-500 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-amber-600" data-v-7ea0ea17> Ver próximos eventos </button><button type="button" class="rounded-md border border-white/30 bg-white/15 px-6 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/25" data-v-7ea0ea17> Tornar-me sócio </button></div></div>`);
      if (heroEvents.value.length > 1) {
        _push(`<div class="mt-10 flex items-center gap-4" data-v-7ea0ea17><button type="button" class="hero-arrow" aria-label="Anterior" data-v-7ea0ea17>‹</button><div class="flex gap-2" data-v-7ea0ea17><!--[-->`);
        ssrRenderList(heroEvents.value, (_, i) => {
          _push(`<button type="button" class="${ssrRenderClass([{ active: activeHeroSlide.value === i }, "hero-dot"])}" data-v-7ea0ea17></button>`);
        });
        _push(`<!--]--></div><button type="button" class="hero-arrow" aria-label="Seguinte" data-v-7ea0ea17>›</button><span class="ml-2 text-xs font-medium text-white/50" data-v-7ea0ea17>${ssrInterpolate(activeHeroSlide.value + 1)} / ${ssrInterpolate(heroEvents.value.length)}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></section><section id="sobre" class="py-24 bg-amber-50" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="reveal mb-14 text-center" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>Sobre nós</p><h2 class="section-title mt-3" data-v-7ea0ea17>Uma casa local com memória,<br data-v-7ea0ea17>agenda e futuro.</h2><p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-stone-500" data-v-7ea0ea17> A ARDC Santana é uma associação recreativa, desportiva e cultural. Nasceu da vontade de criar um ponto de encontro para a terra e continua a ser uma casa aberta para sócios, vizinhos, famílias e amigos. </p></div><div class="grid gap-6 md:grid-cols-3" data-v-7ea0ea17><!--[-->`);
      ssrRenderList(pillars, (pillar) => {
        _push(`<article class="reveal pillar-card" data-v-7ea0ea17><span class="text-3xl" data-v-7ea0ea17>${ssrInterpolate(pillar.icon)}</span><h3 class="mt-4 text-xl font-bold text-stone-800" data-v-7ea0ea17>${ssrInterpolate(pillar.label)}</h3><p class="mt-2 leading-relaxed text-stone-500" data-v-7ea0ea17>${ssrInterpolate(pillar.text)}</p></article>`);
      });
      _push(`<!--]--></div><div class="reveal mt-12 flex flex-wrap items-center gap-8 border-t border-amber-200 pt-10" data-v-7ea0ea17><div class="kpi" data-v-7ea0ea17><span class="kpi__value" data-v-7ea0ea17>1991</span><span class="kpi__label" data-v-7ea0ea17>Fundação</span></div><div class="h-10 w-px bg-amber-200" data-v-7ea0ea17></div><div class="kpi" data-v-7ea0ea17><span class="kpi__value" data-v-7ea0ea17>${ssrInterpolate(upcoming.value.length)}</span><span class="kpi__label" data-v-7ea0ea17>Próximos eventos</span></div><div class="h-10 w-px bg-amber-200" data-v-7ea0ea17></div><div class="kpi" data-v-7ea0ea17><span class="kpi__value" data-v-7ea0ea17>${ssrInterpolate(allEvents.value.length)}</span><span class="kpi__label" data-v-7ea0ea17>Eventos publicados</span></div><div class="ml-auto" data-v-7ea0ea17>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pages.sobre-nos"),
        class: "text-sm font-semibold text-amber-700 hover:text-amber-900 transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Conhecer a associação → `);
          } else {
            return [
              createTextVNode(" Conhecer a associação → ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></section><div class="gold-divider" data-v-7ea0ea17></div><section id="eventos" class="py-24 bg-white" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="reveal mb-10" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>Agenda</p><h2 class="section-title mt-3" data-v-7ea0ea17>Eventos, memórias<br data-v-7ea0ea17>e próximos encontros.</h2></div>`);
      if (eventTabs.value.length) {
        _push(`<div class="mb-8 overflow-x-auto" data-v-7ea0ea17><div class="inline-flex gap-1.5 rounded-lg border border-amber-200 bg-amber-50 p-1.5" data-v-7ea0ea17><!--[-->`);
        ssrRenderList(eventTabs.value, (tab) => {
          _push(`<button type="button" class="${ssrRenderClass([selectedEventTab.value === tab.key ? "bg-amber-600 text-white shadow-sm" : "text-stone-600 hover:text-stone-900 hover:bg-amber-100", "shrink-0 rounded-md px-4 py-2 text-sm font-semibold transition"])}" data-v-7ea0ea17>${ssrInterpolate(tab.label)}</button>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (filteredEvents.value.length) {
        _push(`<div class="grid gap-6 xl:grid-cols-[1fr_1.4fr]" data-v-7ea0ea17>`);
        if (featuredEvent.value) {
          _push(`<article class="reveal event-card-featured" data-v-7ea0ea17><div class="relative overflow-hidden" data-v-7ea0ea17><img${ssrRenderAttr("src", featuredEvent.value.poster || associationLogo)}${ssrRenderAttr("alt", featuredEvent.value.title)} class="aspect-video w-full object-cover" data-v-7ea0ea17><div class="absolute inset-0 bg-gradient-to-t from-stone-900/70 to-transparent" data-v-7ea0ea17></div><span class="absolute left-4 top-4 rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white shadow" data-v-7ea0ea17>${ssrInterpolate(featuredEvent.value.badge || "Destaque")}</span></div><div class="p-6" data-v-7ea0ea17><p class="text-sm font-semibold text-amber-700" data-v-7ea0ea17>${ssrInterpolate(featuredEvent.value.date)} · ${ssrInterpolate(featuredEvent.value.location || featuredEvent.value.subtitle)}</p><h3 class="mt-2 text-2xl font-bold text-stone-800 leading-tight" data-v-7ea0ea17>${ssrInterpolate(featuredEvent.value.title)}</h3><p class="mt-3 leading-relaxed text-stone-500 line-clamp-3" data-v-7ea0ea17>${ssrInterpolate(featuredEvent.value.description || "Mais informações em breve.")}</p><div class="mt-6 flex flex-wrap gap-2" data-v-7ea0ea17>`);
          if (featuredEvent.value.id) {
            _push(ssrRenderComponent(unref(Link), {
              href: eventHref(featuredEvent.value),
              class: "rounded-md bg-amber-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-700 transition shadow-sm"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(` Ver detalhes `);
                } else {
                  return [
                    createTextVNode(" Ver detalhes ")
                  ];
                }
              }),
              _: 1
            }, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`<a${ssrRenderAttr("href", calendarHref(featuredEvent.value))} target="_blank" rel="noreferrer" class="rounded-md border border-amber-200 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition" data-v-7ea0ea17> Calendário </a><button type="button" class="rounded-md border border-amber-200 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition" data-v-7ea0ea17> Partilhar </button></div></div></article>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="grid content-start gap-3" data-v-7ea0ea17><!--[-->`);
        ssrRenderList(filteredEvents.value, (event) => {
          _push(`<article class="reveal event-card-row" data-v-7ea0ea17><img${ssrRenderAttr("src", event.poster || associationLogo)}${ssrRenderAttr("alt", event.title)} class="h-24 w-24 shrink-0 rounded-md object-cover sm:h-28 sm:w-28" data-v-7ea0ea17><div class="flex min-w-0 flex-col justify-between gap-2 py-1" data-v-7ea0ea17><div data-v-7ea0ea17><div class="flex flex-wrap items-center gap-2" data-v-7ea0ea17><span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800" data-v-7ea0ea17>${ssrInterpolate(event.badge || "Evento")}</span><span class="text-xs text-stone-400" data-v-7ea0ea17>${ssrInterpolate(event.date)}</span></div><h3 class="mt-1.5 text-base font-bold text-stone-800 leading-snug" data-v-7ea0ea17>${ssrInterpolate(event.title)}</h3><p class="mt-0.5 text-sm text-stone-400" data-v-7ea0ea17>${ssrInterpolate(event.location || event.subtitle)}</p></div><div class="flex flex-wrap gap-2" data-v-7ea0ea17>`);
          if (event.id) {
            _push(ssrRenderComponent(unref(Link), {
              href: eventHref(event),
              class: "rounded-md bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-700 transition"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(` Saber mais `);
                } else {
                  return [
                    createTextVNode(" Saber mais ")
                  ];
                }
              }),
              _: 2
            }, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`<button type="button" class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-amber-50 transition" data-v-7ea0ea17> Copiar link </button></div></div></article>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<div class="reveal rounded-lg border border-dashed border-amber-300 bg-amber-50 p-12 text-center" data-v-7ea0ea17><p class="text-lg font-bold text-stone-800" data-v-7ea0ea17>Sem eventos nesta seleção.</p><button type="button" class="mt-4 rounded-md bg-amber-600 px-4 py-2.5 text-sm font-bold text-white" data-v-7ea0ea17> Ver todos </button></div>`);
      }
      _push(`</div></section><div class="gold-divider" data-v-7ea0ea17></div><section id="socios" class="py-24 bg-amber-50" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="grid gap-12 lg:grid-cols-2 lg:items-start" data-v-7ea0ea17><div class="reveal" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>Torna-te sócio</p><h2 class="section-title mt-3" data-v-7ea0ea17>Faz parte<br data-v-7ea0ea17>da associação.</h2><p class="mt-5 text-lg leading-relaxed text-stone-500" data-v-7ea0ea17> Ser sócio é participar, apoiar a manutenção da associação e ajudar a manter viva esta casa comunitária. Toda a contribuição faz diferença. </p><ul class="mt-8 space-y-3" data-v-7ea0ea17><!--[-->`);
      ssrRenderList(["Participar em eventos", "Tornar-se sócio", "Voluntariado", "Apoio a iniciativas", "Partilhar nas redes sociais"], (item) => {
        _push(`<li class="flex items-center gap-3 text-stone-600" data-v-7ea0ea17><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500" data-v-7ea0ea17></span> ${ssrInterpolate(item)}</li>`);
      });
      _push(`<!--]--></ul><div class="mt-8 flex flex-wrap gap-3" data-v-7ea0ea17><a href="https://www.facebook.com/ardcsantana" target="_blank" rel="noreferrer" class="rounded-md border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-100 transition shadow-sm" data-v-7ea0ea17>Facebook</a><a href="https://www.instagram.com/ardcsantana/" target="_blank" rel="noreferrer" class="rounded-md border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-100 transition shadow-sm" data-v-7ea0ea17>Instagram</a></div></div><form class="reveal rounded-xl border border-amber-200 bg-white p-8 shadow-md" novalidate data-v-7ea0ea17><h3 class="text-xl font-bold text-stone-800" data-v-7ea0ea17>Contacta-nos</h3><p class="mt-1 text-sm text-stone-500" data-v-7ea0ea17>Responderemos o mais brevemente possível.</p><div class="mt-6 grid gap-4 sm:grid-cols-2" data-v-7ea0ea17><label class="field" data-v-7ea0ea17><span data-v-7ea0ea17>Nome</span><input${ssrRenderAttr("value", unref(form).name)} type="text" autocomplete="name" data-v-7ea0ea17>`);
      if (errors.value.name) {
        _push(`<span class="error" data-v-7ea0ea17>${ssrInterpolate(errors.value.name)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</label><label class="field" data-v-7ea0ea17><span data-v-7ea0ea17>Email</span><input${ssrRenderAttr("value", unref(form).email)} type="email" autocomplete="email" data-v-7ea0ea17>`);
      if (errors.value.email) {
        _push(`<span class="error" data-v-7ea0ea17>${ssrInterpolate(errors.value.email)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</label><label class="field sm:col-span-2" data-v-7ea0ea17><span data-v-7ea0ea17>Telefone</span><input${ssrRenderAttr("value", unref(form).phone)} type="tel" autocomplete="tel" data-v-7ea0ea17>`);
      if (errors.value.phone) {
        _push(`<span class="error" data-v-7ea0ea17>${ssrInterpolate(errors.value.phone)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</label><label class="field sm:col-span-2" data-v-7ea0ea17><span data-v-7ea0ea17>Mensagem</span><textarea rows="4" data-v-7ea0ea17>${ssrInterpolate(unref(form).message)}</textarea>`);
      if (errors.value.message) {
        _push(`<span class="error" data-v-7ea0ea17>${ssrInterpolate(errors.value.message)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</label></div><button type="submit" class="mt-5 w-full rounded-md bg-amber-600 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700 disabled:opacity-50"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""} data-v-7ea0ea17>${ssrInterpolate(unref(form).processing ? "A enviar..." : "Enviar mensagem")}</button>`);
      if (formSent.value) {
        _push(`<p class="mt-3 rounded-md bg-emerald-50 border border-emerald-200 p-3 text-sm font-medium text-emerald-800" data-v-7ea0ea17> Obrigado. A tua mensagem foi recebida. </p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</form></div></div></section><section id="galeria" class="py-24 bg-white border-t border-amber-100" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="reveal mb-10 text-center" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>Galeria</p><h2 class="section-title mt-3" data-v-7ea0ea17>Momentos especiais.</h2></div>`);
      if (galleryItems.value.length) {
        _push(`<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" data-v-7ea0ea17><!--[-->`);
        ssrRenderList(galleryItems.value, (item) => {
          _push(`<button type="button" class="group relative overflow-hidden rounded-xl shadow-sm" data-v-7ea0ea17><img${ssrRenderAttr("src", item.caminho)}${ssrRenderAttr("alt", item.titulo || item.event)} class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105" data-v-7ea0ea17><div class="absolute inset-0 bg-amber-900/0 transition duration-300 group-hover:bg-amber-900/40" data-v-7ea0ea17></div><div class="absolute inset-0 flex items-end p-4 opacity-0 transition group-hover:opacity-100" data-v-7ea0ea17><p class="text-sm font-semibold text-white drop-shadow" data-v-7ea0ea17>${ssrInterpolate(item.titulo || item.event)}</p></div></button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="rounded-xl border border-dashed border-amber-200 bg-amber-50 p-12 text-center" data-v-7ea0ea17><p class="text-stone-500" data-v-7ea0ea17>Ainda não há fotografias publicadas.</p></div>`);
      }
      _push(`</div></section><section class="py-24 bg-amber-50 border-t border-amber-100" data-v-7ea0ea17><div class="mx-auto max-w-3xl px-5 lg:px-8" data-v-7ea0ea17><div class="reveal mb-10 text-center" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>FAQ</p><h2 class="section-title mt-3" data-v-7ea0ea17>Perguntas frequentes.</h2></div><div class="space-y-2" data-v-7ea0ea17><!--[-->`);
      ssrRenderList(faqs, (faq, index) => {
        _push(`<article class="reveal overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm" data-v-7ea0ea17><button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-semibold text-stone-800 hover:bg-amber-50 transition" data-v-7ea0ea17>${ssrInterpolate(faq[0])} <span class="shrink-0 text-amber-600 text-xl font-bold leading-none" data-v-7ea0ea17>${ssrInterpolate(openFaq.value === index ? "−" : "+")}</span></button>`);
        if (openFaq.value === index) {
          _push(`<p class="border-t border-amber-100 px-6 py-5 leading-relaxed text-stone-500" data-v-7ea0ea17>${ssrInterpolate(faq[1])}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</article>`);
      });
      _push(`<!--]--></div></div></section><section id="contactos" class="py-24 bg-white border-t border-amber-100" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="grid gap-10 lg:grid-cols-[1fr_1.5fr] lg:items-start" data-v-7ea0ea17><div class="reveal" data-v-7ea0ea17><p class="eyebrow" data-v-7ea0ea17>Contactos</p><h2 class="section-title mt-3" data-v-7ea0ea17>Fala connosco.</h2><div class="mt-8 space-y-4 text-stone-500" data-v-7ea0ea17><p class="font-medium text-stone-700" data-v-7ea0ea17>Santana, Carvalhal Benfeito<br data-v-7ea0ea17>Caldas da Rainha</p><p data-v-7ea0ea17><a${ssrRenderAttr("href", `mailto:${contactEmail}`)} class="font-semibold text-amber-700 hover:text-amber-900 transition" data-v-7ea0ea17>${ssrInterpolate(contactEmail)}</a></p></div><div class="mt-8 flex flex-wrap gap-3" data-v-7ea0ea17><a href="https://www.facebook.com/ardcsantana" target="_blank" rel="noreferrer" class="rounded-md border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition shadow-sm" data-v-7ea0ea17>Facebook</a><a href="https://www.instagram.com/ardcsantana/" target="_blank" rel="noreferrer" class="rounded-md border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition shadow-sm" data-v-7ea0ea17>Instagram</a></div></div><div class="reveal overflow-hidden rounded-xl border border-amber-200 shadow-md" data-v-7ea0ea17><iframe title="Localização da ARDC Santana" class="h-[400px] w-full" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=-8.965%2C39.363%2C-8.939%2C39.378&amp;layer=mapnik&amp;marker=39.3704%2C-8.9521" data-v-7ea0ea17></iframe></div></div></div></section>`);
      _push(ssrRenderComponent(_sfc_main$2, {
        patrocinadores: props.patrocinadores || []
      }, null, _parent));
      _push(`<footer class="border-t border-amber-200 bg-stone-800 py-10 text-stone-300" data-v-7ea0ea17><div class="mx-auto max-w-7xl px-5 lg:px-8" data-v-7ea0ea17><div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between" data-v-7ea0ea17><div class="flex items-center gap-3" data-v-7ea0ea17><img${ssrRenderAttr("src", associationLogo)} alt="" class="h-9 w-9 rounded-full object-contain bg-stone-700 border border-amber-700/40 p-1" data-v-7ea0ea17><span class="font-semibold text-white" data-v-7ea0ea17>ARDC Santana</span></div><div class="flex flex-wrap gap-4 text-sm text-stone-400" data-v-7ea0ea17><!--[-->`);
      ssrRenderList(navLinks, (link) => {
        _push(`<button type="button" class="hover:text-amber-300 transition" data-v-7ea0ea17>${ssrInterpolate(link[0])}</button>`);
      });
      _push(`<!--]--></div></div><div class="mt-8 flex flex-col gap-3 border-t border-stone-700 pt-6 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between" data-v-7ea0ea17><div class="flex flex-wrap gap-4" data-v-7ea0ea17>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.privacidade"),
        class: "hover:text-amber-300 transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Política de Privacidade`);
          } else {
            return [
              createTextVNode("Política de Privacidade")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.termos"),
        class: "hover:text-amber-300 transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Termos e Condições`);
          } else {
            return [
              createTextVNode("Termos e Condições")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("legal.cookies"),
        class: "hover:text-amber-300 transition"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Política de Cookies`);
          } else {
            return [
              createTextVNode("Política de Cookies")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<a href="https://www.livroreclamacoes.pt" target="_blank" rel="noopener" class="hover:text-amber-300 transition" data-v-7ea0ea17>Livro de Reclamações</a></div><div class="flex items-center gap-4" data-v-7ea0ea17><p data-v-7ea0ea17>© ${ssrInterpolate(unref(currentYear))} Associação de Santana.</p><a href="https://ateneya.com/" target="_blank" rel="noopener" class="font-medium text-stone-400 hover:text-amber-300 transition" data-v-7ea0ea17>#CreatingDevelopingImproving4you</a></div></div></div></footer>`);
      _push(ssrRenderComponent(_sfc_main$3, null, null, _parent));
      if (lightboxItem.value) {
        _push(`<div class="fixed inset-0 z-[60] grid place-items-center bg-stone-900/85 p-5 backdrop-blur-sm" data-v-7ea0ea17><div class="max-w-4xl w-full overflow-hidden rounded-xl border border-amber-200 shadow-2xl" data-v-7ea0ea17><img${ssrRenderAttr("src", lightboxItem.value.caminho)}${ssrRenderAttr("alt", lightboxItem.value.titulo || lightboxItem.value.event)} class="max-h-[70vh] w-full object-contain bg-stone-100" data-v-7ea0ea17><div class="flex items-center justify-between gap-4 bg-white p-4" data-v-7ea0ea17><div data-v-7ea0ea17><p class="text-xs font-semibold uppercase tracking-wide text-amber-600" data-v-7ea0ea17>${ssrInterpolate(lightboxItem.value.category)}</p><h3 class="mt-0.5 font-bold text-stone-800" data-v-7ea0ea17>${ssrInterpolate(lightboxItem.value.titulo || lightboxItem.value.event)}</h3></div><button type="button" class="rounded-md border border-amber-200 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50 transition" data-v-7ea0ea17>Fechar</button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</main><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Home.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Home = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-7ea0ea17"]]);
export {
  Home as default
};
