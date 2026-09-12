import { ref, computed, unref, withCtx, createVNode, createTextVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { Head, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "PublicShow",
  __ssrInlineRender: true,
  props: {
    evento: Object
  },
  setup(__props) {
    const props = __props;
    const activeIndex = ref(0);
    const activeLightbox = ref(null);
    const media = computed(() => props.evento.media ?? []);
    const photos = computed(() => media.value.filter((item) => item.tipo === "foto"));
    const videos = computed(() => media.value.filter((item) => item.tipo === "video"));
    const facebookEmbedUrl = computed(() => {
      if (!props.evento.facebook_post_url) return null;
      const url = new URL("https://www.facebook.com/plugins/post.php");
      url.searchParams.set("href", props.evento.facebook_post_url);
      url.searchParams.set("show_text", "true");
      url.searchParams.set("width", "500");
      return url.toString();
    });
    const slides = computed(() => {
      const eventMedia = media.value.map((item) => ({ ...item, source: item.caminho }));
      if (eventMedia.length) return eventMedia;
      return props.evento.cartaz ? [{ id: "cartaz", tipo: "foto", source: props.evento.cartaz, caminho: props.evento.cartaz, titulo: props.evento.titulo }] : [];
    });
    const activeSlide = computed(() => slides.value[activeIndex.value] ?? null);
    const dataEvento = computed(() => {
      if (!props.evento.data_inicio) return props.evento.periodo || "Sem data definida";
      if (props.evento.data_fim && props.evento.data_fim !== props.evento.data_inicio) {
        return `${props.evento.data_inicio} a ${props.evento.data_fim}`;
      }
      return props.evento.data_inicio;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `${__props.evento.titulo} | ARDC Santana`
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<meta head-key="description" name="description"${ssrRenderAttr("content", __props.evento.descricao || `Vê fotografias e vídeos do evento ${__props.evento.titulo} da ARDC Santana.`)}${_scopeId}>`);
          } else {
            return [
              createVNode("meta", {
                "head-key": "description",
                name: "description",
                content: __props.evento.descricao || `Vê fotografias e vídeos do evento ${__props.evento.titulo} da ARDC Santana.`
              }, null, 8, ["content"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<main class="min-h-screen bg-amber-50 text-stone-800"><header class="sticky top-0 z-40 border-b border-amber-200/80 bg-amber-50/95 backdrop-blur-xl"><nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3.5 lg:px-8">`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/",
        class: "flex items-center gap-2 text-sm font-bold text-stone-800"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img src="/images/santana-logo.png" alt="" class="h-8 w-8 rounded-full border border-amber-200 bg-white object-contain p-1"${_scopeId}> ARDC Santana `);
          } else {
            return [
              createVNode("img", {
                src: "/images/santana-logo.png",
                alt: "",
                class: "h-8 w-8 rounded-full border border-amber-200 bg-white object-contain p-1"
              }),
              createTextVNode(" ARDC Santana ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: "/#eventos",
        class: "rounded-md border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-amber-50"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` ← Eventos `);
          } else {
            return [
              createTextVNode(" ← Eventos ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</nav></header><section class="relative bg-stone-800 text-white"><div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-16"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-400">${ssrInterpolate(__props.evento.badge || "Evento")}</p><h1 class="mt-3 text-4xl font-bold leading-tight text-white sm:text-5xl">${ssrInterpolate(__props.evento.titulo)}</h1><p class="mt-3 text-xl font-semibold text-stone-300">${ssrInterpolate(__props.evento.subtitulo || __props.evento.localizacao)}</p><p class="mt-4 max-w-2xl leading-relaxed text-stone-300">${ssrInterpolate(__props.evento.descricao)}</p><div class="mt-8 grid gap-3 sm:grid-cols-3"><div class="rounded-lg border border-white/15 bg-white/10 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-400">Data</p><p class="mt-1 font-bold text-white">${ssrInterpolate(dataEvento.value)}</p></div><div class="rounded-lg border border-white/15 bg-white/10 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-400">Local</p><p class="mt-1 font-bold text-white">${ssrInterpolate(__props.evento.localizacao || "Por definir")}</p></div><div class="rounded-lg border border-white/15 bg-white/10 p-4"><p class="text-xs font-bold uppercase tracking-wide text-stone-400">Memórias</p><p class="mt-1 font-bold text-white">${ssrInterpolate(facebookEmbedUrl.value ? "Facebook" : `${media.value.length} ficheiros`)}</p></div></div></div><div class="overflow-hidden rounded-xl shadow-2xl">`);
      if (activeSlide.value) {
        _push(`<button type="button" class="block w-full bg-stone-900">`);
        if (activeSlide.value.tipo === "foto") {
          _push(`<img${ssrRenderAttr("src", activeSlide.value.source)}${ssrRenderAttr("alt", activeSlide.value.titulo || __props.evento.titulo)} class="aspect-[16/10] w-full object-contain">`);
        } else {
          _push(`<video${ssrRenderAttr("src", activeSlide.value.source)} controls class="aspect-[16/10] w-full bg-black object-contain"></video>`);
        }
        _push(`</button>`);
      } else {
        _push(`<div class="grid aspect-[16/10] place-items-center bg-stone-900/60 p-6 text-center font-semibold text-stone-400"> Este evento ainda não tem fotografias ou vídeos publicados. </div>`);
      }
      if (slides.value.length > 1) {
        _push(`<div class="flex items-center justify-between gap-3 bg-white/10 p-3"><button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Anterior</button><p class="text-sm font-semibold text-stone-300">${ssrInterpolate(activeIndex.value + 1)} / ${ssrInterpolate(slides.value.length)}</p><button type="button" class="rounded-md border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Seguinte</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></section><div class="h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent"></div>`);
      if (slides.value.length > 1) {
        _push(`<section class="bg-white py-5"><div class="mx-auto flex max-w-7xl gap-3 overflow-x-auto px-5 lg:px-8"><!--[-->`);
        ssrRenderList(slides.value, (slide, index) => {
          _push(`<button type="button" class="${ssrRenderClass([activeIndex.value === index ? "border-amber-500 opacity-100" : "border-transparent opacity-60 hover:opacity-90", "h-20 w-28 shrink-0 overflow-hidden rounded-lg border-2 transition"])}">`);
          if (slide.tipo === "foto") {
            _push(`<img${ssrRenderAttr("src", slide.source)}${ssrRenderAttr("alt", slide.titulo || __props.evento.titulo)} class="h-full w-full object-cover">`);
          } else {
            _push(`<video${ssrRenderAttr("src", slide.source)} class="h-full w-full object-cover"></video>`);
          }
          _push(`</button>`);
        });
        _push(`<!--]--></div></section>`);
      } else {
        _push(`<!---->`);
      }
      if (facebookEmbedUrl.value) {
        _push(`<section class="bg-amber-50 py-16"><div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[0.85fr_1.15fr] lg:px-8"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Facebook</p><h2 class="mt-3 text-4xl font-bold text-stone-800">Fotos e vídeos no post original.</h2><p class="mt-4 leading-relaxed text-stone-600"> As memórias deste evento estão alojadas no Facebook, para manter o site mais leve e rápido. </p><a${ssrRenderAttr("href", __props.evento.facebook_post_url)} target="_blank" rel="noreferrer" class="mt-6 inline-flex rounded-md bg-amber-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-amber-700"> Abrir no Facebook </a></div><div class="overflow-hidden rounded-xl border border-amber-200 bg-white p-4 shadow-sm"><iframe${ssrRenderAttr("src", facebookEmbedUrl.value)} title="Post do Facebook do evento" class="mx-auto min-h-[560px] w-full max-w-[500px] border-0" scrolling="no" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen></iframe></div></div></section>`);
      } else {
        _push(`<!---->`);
      }
      if (!facebookEmbedUrl.value) {
        _push(`<section class="py-16 bg-amber-50"><div class="mx-auto max-w-7xl px-5 lg:px-8"><div class="mb-10"><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Fotografias</p><h2 class="mt-3 text-4xl font-bold text-stone-800">Momentos registados durante o evento.</h2></div>`);
        if (photos.value.length) {
          _push(`<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><!--[-->`);
          ssrRenderList(photos.value, (photo) => {
            _push(`<button type="button" class="group relative overflow-hidden rounded-xl border border-amber-200 shadow-sm"><img${ssrRenderAttr("src", photo.caminho)}${ssrRenderAttr("alt", photo.titulo || __props.evento.titulo)} class="aspect-square w-full object-cover transition duration-500 group-hover:scale-105"><div class="absolute inset-0 bg-amber-900/0 transition group-hover:bg-amber-900/30"></div><span class="absolute inset-x-0 bottom-0 translate-y-full bg-gradient-to-t from-stone-900/80 p-3 pt-8 text-left text-sm font-semibold text-white transition group-hover:translate-y-0">${ssrInterpolate(photo.titulo || __props.evento.titulo)}</span></button>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="rounded-xl border border-amber-200 bg-white p-8 text-stone-500"> Ainda não existem fotografias publicadas para este evento. </div>`);
        }
        _push(`</div></section>`);
      } else {
        _push(`<!---->`);
      }
      if (!facebookEmbedUrl.value) {
        _push(`<section class="bg-white py-16"><div class="mx-auto max-w-7xl px-5 lg:px-8"><div class="mb-10"><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Vídeos</p><h2 class="mt-3 text-4xl font-bold text-stone-800">Vídeos do evento.</h2></div>`);
        if (videos.value.length) {
          _push(`<div class="grid gap-5 lg:grid-cols-2"><!--[-->`);
          ssrRenderList(videos.value, (video) => {
            _push(`<figure class="overflow-hidden rounded-xl border border-amber-200 bg-amber-50 shadow-sm"><video${ssrRenderAttr("src", video.caminho)} controls class="aspect-video w-full bg-stone-900 object-contain"></video><figcaption class="p-4 font-semibold text-amber-800">${ssrInterpolate(video.titulo || __props.evento.titulo)}</figcaption></figure>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-stone-500"> Ainda não existem vídeos publicados para este evento. </div>`);
        }
        _push(`</div></section>`);
      } else {
        _push(`<!---->`);
      }
      if (activeLightbox.value) {
        _push(`<div class="fixed inset-0 z-50 grid place-items-center bg-stone-900/90 p-5 backdrop-blur-sm"><button type="button" class="absolute right-4 top-4 rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur">Fechar</button><div class="w-full max-w-6xl">`);
        if (activeLightbox.value.tipo === "foto") {
          _push(`<img${ssrRenderAttr("src", activeLightbox.value.caminho)}${ssrRenderAttr("alt", activeLightbox.value.titulo || __props.evento.titulo)} class="mx-auto max-h-[82vh] rounded-xl object-contain shadow-2xl">`);
        } else {
          _push(`<video${ssrRenderAttr("src", activeLightbox.value.caminho)} controls autoplay class="mx-auto max-h-[82vh] w-full rounded-xl bg-black object-contain shadow-2xl"></video>`);
        }
        _push(`<p class="mt-4 text-center font-semibold text-white">${ssrInterpolate(activeLightbox.value.titulo || __props.evento.titulo)}</p></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Eventos/PublicShow.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
