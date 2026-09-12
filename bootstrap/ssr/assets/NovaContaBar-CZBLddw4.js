import { ref, computed, onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderList, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "NovaContaBar",
  __ssrInlineRender: true,
  props: { produtos: Array },
  setup(__props) {
    const props = __props;
    const pontosPadrao = ["Cafe", "Bar 1", "Bar 2"];
    const carrinho = ref([]);
    const pontoBar = ref("");
    const form = useForm({ observacoes: "", items: [], ponto_bar: "" });
    const porCategoria = computed(() => Object.groupBy(props.produtos ?? [], (p) => p.categoria?.nome ?? "Outros"));
    const total = computed(() => carrinho.value.reduce((s, i) => s + Number(i.preco) * i.quantidade, 0));
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    onMounted(() => {
      const params = new URLSearchParams(window.location.search);
      pontoBar.value = params.get("ponto") || localStorage.getItem("santana_ponto_bar") || "";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-blue-50 p-4 text-slate-950 sm:p-6" }, _attrs))}><header class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm"><div><h1 class="text-3xl font-black">Nova Conta Bar</h1><p class="font-semibold text-slate-500">Modo conta: paga no final.</p><label class="mt-3 block text-sm font-black text-slate-700">Ponto de venda <input${ssrRenderAttr("value", pontoBar.value)} list="pontos-bar-conta" class="mt-1 w-full rounded-xl border-slate-300 font-black" placeholder="Cafe, Bar 1, Bar 2..."></label><datalist id="pontos-bar-conta"><!--[-->`);
      ssrRenderList(pontosPadrao, (ponto) => {
        _push(`<option${ssrRenderAttr("value", ponto)}></option>`);
      });
      _push(`<!--]--></datalist></div>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("bar.index", pontoBar.value ? { ponto: pontoBar.value } : {}),
        class: "rounded-xl border px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Voltar`);
          } else {
            return [
              createTextVNode("Voltar")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</header><div class="grid gap-5 lg:grid-cols-[1fr_420px]"><section class="space-y-5"><!--[-->`);
      ssrRenderList(porCategoria.value, (lista, categoria) => {
        _push(`<div class="rounded-3xl bg-white p-4 shadow-sm"><h2 class="mb-3 text-xl font-black">${ssrInterpolate(categoria)}</h2><div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4"><!--[-->`);
        ssrRenderList(lista, (produto) => {
          _push(`<button type="button" class="min-h-24 rounded-2xl border border-blue-100 bg-blue-50 p-3 text-left font-black"><span class="block">${ssrInterpolate(produto.nome)}</span><span class="mt-2 block text-blue-700">${ssrInterpolate(euros(produto.preco))}</span></button>`);
        });
        _push(`<!--]--></div></div>`);
      });
      _push(`<!--]--></section><form class="sticky bottom-3 self-start rounded-3xl bg-white p-4 shadow-xl"><h2 class="mb-3 text-xl font-black">Conta</h2>`);
      if (!pontoBar.value) {
        _push(`<div class="mb-3 rounded-2xl bg-red-50 p-3 font-bold text-red-700">Define o ponto de venda antes de abrir conta.</div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(form).errors.ponto_bar) {
        _push(`<div class="mb-3 rounded-2xl bg-red-50 p-3 font-bold text-red-700">${ssrInterpolate(unref(form).errors.ponto_bar)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<label class="block font-bold">Nome/identificação<input${ssrRenderAttr("value", unref(form).observacoes)} class="mt-1 w-full rounded-xl border-slate-300" placeholder="Mesa 3, João, balcão..."></label><div class="my-4 divide-y rounded-2xl bg-slate-50"><!--[-->`);
      ssrRenderList(carrinho.value, (item) => {
        _push(`<div class="p-3"><div class="font-black">${ssrInterpolate(item.nome)}</div><div class="mt-2 flex items-center justify-between"><div class="flex items-center gap-2"><button type="button" class="h-11 w-11 rounded-xl bg-slate-200 font-black">-</button><strong>${ssrInterpolate(item.quantidade)}</strong><button type="button" class="h-11 w-11 rounded-xl bg-slate-900 font-black text-white">+</button></div><strong>${ssrInterpolate(euros(item.preco * item.quantidade))}</strong></div></div>`);
      });
      _push(`<!--]--></div><div class="rounded-2xl bg-blue-600 p-4 text-white"><div class="text-sm font-bold">Total atual</div><div class="text-4xl font-black">${ssrInterpolate(euros(total.value))}</div></div><button class="mt-4 w-full rounded-2xl bg-blue-600 p-5 text-lg font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(!carrinho.value.length || !pontoBar.value || unref(form).processing) ? " disabled" : ""}>ABRIR CONTA</button></form></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Bar/NovaContaBar.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
