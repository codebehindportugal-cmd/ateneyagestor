import { ref, computed, onMounted, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderList, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "NovoPrepago",
  __ssrInlineRender: true,
  props: { produtos: Array },
  setup(__props) {
    const props = __props;
    const pontosPadrao = ["Cafe", "Bar 1", "Bar 2"];
    const carrinho = ref([]);
    const valorRecebido = ref("");
    const trocoEntregue = ref("");
    const pontoBar = ref("");
    const form = useForm({ items: [], valor_recebido: 0, troco: 0, ponto_bar: "" });
    const porCategoria = computed(() => Object.groupBy(props.produtos ?? [], (p) => p.categoria?.nome ?? "Outros"));
    const total = computed(() => carrinho.value.reduce((s, i) => s + Number(i.preco) * i.quantidade, 0));
    const troco = computed(() => Math.max(0, Number(valorRecebido.value || 0) - total.value));
    const trocoRegistado = computed(() => trocoEntregue.value === "" ? troco.value : Number(trocoEntregue.value || 0));
    const doacao = computed(() => Math.max(0, troco.value - trocoRegistado.value));
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    onMounted(() => {
      const params = new URLSearchParams(window.location.search);
      pontoBar.value = params.get("ponto") || localStorage.getItem("santana_ponto_bar") || "";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-emerald-50 p-4 text-slate-950 sm:p-6" }, _attrs))}><header class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-4 shadow-sm"><div><h1 class="text-3xl font-black">Pré-Pagamento</h1><p class="font-bold text-emerald-700">O cliente paga ANTES de levantar.</p><label class="mt-3 block text-sm font-black text-slate-700">Ponto de venda <input${ssrRenderAttr("value", pontoBar.value)} list="pontos-bar-prepago" class="mt-1 w-full rounded-xl border-slate-300 font-black" placeholder="Cafe, Bar 1, Bar 2..."></label><datalist id="pontos-bar-prepago"><!--[-->`);
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
          _push(`<button type="button" class="min-h-24 rounded-2xl border border-emerald-100 bg-emerald-50 p-3 text-left font-black"><span class="block">${ssrInterpolate(produto.nome)}</span><span class="mt-2 block text-emerald-700">${ssrInterpolate(euros(produto.preco))}</span></button>`);
        });
        _push(`<!--]--></div></div>`);
      });
      _push(`<!--]--></section><form class="sticky bottom-3 self-start rounded-3xl bg-white p-4 shadow-xl"><h2 class="mb-3 text-xl font-black">Carrinho</h2>`);
      if (!pontoBar.value) {
        _push(`<div class="mb-3 rounded-2xl bg-red-50 p-3 font-bold text-red-700">Define o ponto de venda antes de cobrar.</div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(form).errors.ponto_bar) {
        _push(`<div class="mb-3 rounded-2xl bg-red-50 p-3 font-bold text-red-700">${ssrInterpolate(unref(form).errors.ponto_bar)}</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!carrinho.value.length) {
        _push(`<div class="rounded-2xl bg-slate-50 p-6 text-center font-semibold text-slate-500">Escolhe produtos.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(carrinho.value, (item) => {
        _push(`<div class="mb-3 rounded-2xl bg-slate-50 p-3"><div class="font-black">${ssrInterpolate(item.nome)}</div><div class="mt-2 flex items-center justify-between"><div class="flex items-center gap-2"><button type="button" class="h-11 w-11 rounded-xl bg-slate-200 font-black">-</button><strong>${ssrInterpolate(item.quantidade)}</strong><button type="button" class="h-11 w-11 rounded-xl bg-slate-900 font-black text-white">+</button></div><strong>${ssrInterpolate(euros(item.preco * item.quantidade))}</strong></div></div>`);
      });
      _push(`<!--]--><div class="my-4 rounded-2xl bg-emerald-600 p-4 text-white"><div class="text-sm font-bold">Total</div><div class="text-4xl font-black">${ssrInterpolate(euros(total.value))}</div></div><label class="block font-bold">Valor recebido<input${ssrRenderAttr("value", valorRecebido.value)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"></label><label class="mt-3 block font-bold">Troco entregue<input${ssrRenderAttr("value", trocoEntregue.value)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"${ssrRenderAttr("placeholder", euros(troco.value))}></label><div class="mt-3 grid grid-cols-2 gap-2 text-lg font-black"><div class="rounded-2xl bg-emerald-50 p-3 text-emerald-700">Troco: ${ssrInterpolate(euros(trocoRegistado.value))}</div><div class="rounded-2xl bg-amber-50 p-3 text-amber-700">Doação: ${ssrInterpolate(euros(doacao.value))}</div></div><button type="button" class="mt-3 w-full rounded-2xl bg-amber-400 p-3 font-black text-amber-950">CLIENTE DOA O TROCO</button>`);
      if (unref(form).errors.valor_recebido || unref(form).errors.troco) {
        _push(`<div class="mt-3 rounded-2xl bg-red-50 p-3 font-bold text-red-700">${ssrInterpolate(unref(form).errors.valor_recebido || unref(form).errors.troco)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="mt-4 w-full rounded-2xl bg-emerald-600 p-5 text-lg font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(!carrinho.value.length || !pontoBar.value || unref(form).processing) ? " disabled" : ""}>COBRAR E IMPRIMIR SENHA</button></form></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Bar/NovoPrepago.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
