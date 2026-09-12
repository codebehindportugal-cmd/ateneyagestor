import { ref, computed, onMounted, onBeforeUnmount, unref, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate } from "vue/server-renderer";
import { Head } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const DURACAO = 6e3;
const _sfc_main = {
  __name: "SponsorScreen",
  __ssrInlineRender: true,
  props: {
    patrocinadores: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const indice = ref(0);
    let timer;
    const sequencia = computed(() => {
      const items = [];
      for (const s of props.patrocinadores) {
        const imgs = s.images?.length ? s.images : [{ id: `logo-${s.id}`, url: s.logo_url }];
        for (const img of imgs) {
          items.push({
            key: String(img.id),
            url: img.url,
            empresa: s.empresa,
            logo_url: s.logo_url
          });
        }
      }
      return items;
    });
    const atual = computed(() => sequencia.value[indice.value] ?? null);
    const avancar = () => {
      if (sequencia.value.length <= 1) return;
      indice.value = (indice.value + 1) % sequencia.value.length;
    };
    onMounted(() => {
      timer = window.setInterval(avancar, DURACAO);
    });
    onBeforeUnmount(() => {
      window.clearInterval(timer);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Ecrã de Patrocinadores" }, null, _parent));
      _push(`<main class="sponsor-screen" data-v-81490ef5><div class="sponsor-screen__bg" aria-hidden="true" data-v-81490ef5></div>`);
      if (!sequencia.value.length) {
        _push(`<div class="sponsor-screen__empty" data-v-81490ef5><div data-v-81490ef5><h2 class="text-4xl font-black" data-v-81490ef5>Ainda não há patrocinadores ativos</h2><p class="mt-3 text-lg font-bold text-white/55" data-v-81490ef5>Adicione patrocinadores no backoffice.</p></div></div>`);
      } else {
        _push(`<!--[--><div class="sponsor-screen__brand" data-v-81490ef5><span class="brand-label" data-v-81490ef5>ARDC Santana</span><span class="brand-sep" data-v-81490ef5>·</span><span class="brand-title" data-v-81490ef5>Patrocinadores</span></div><div class="sponsor-screen__slide" data-v-81490ef5><img${ssrRenderAttr("src", atual.value.url)}${ssrRenderAttr("alt", atual.value.empresa)} class="sponsor-screen__img" data-v-81490ef5></div><footer class="sponsor-screen__footer" data-v-81490ef5><div class="footer-inner" data-v-81490ef5><div class="footer-sponsor" data-v-81490ef5><img${ssrRenderAttr("src", atual.value.logo_url)}${ssrRenderAttr("alt", atual.value.empresa)} class="footer-logo" data-v-81490ef5><span class="footer-name" data-v-81490ef5>${ssrInterpolate(atual.value.empresa)}</span></div><span class="footer-counter" data-v-81490ef5>${ssrInterpolate(indice.value + 1)} / ${ssrInterpolate(sequencia.value.length)}</span></div><div class="footer-progress-track" data-v-81490ef5><div class="footer-progress-bar" data-v-81490ef5></div></div></footer><!--]-->`);
      }
      _push(`</main><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Public/SponsorScreen.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const SponsorScreen = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-81490ef5"]]);
export {
  SponsorScreen as default
};
