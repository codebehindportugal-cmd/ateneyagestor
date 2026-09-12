import { ref, computed, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderAttr } from "vue/server-renderer";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Show",
  __ssrInlineRender: true,
  props: { pedido: Object, produtos: Array },
  setup(__props) {
    const props = __props;
    const mostrarProdutos = ref(true);
    const valorRecebido = ref("");
    const troco = ref("");
    useForm({ pedido_id: props.pedido.id, produto_id: "", quantidade: 1 });
    useForm({ valor_recebido: "", troco: 0 });
    const porCategoria = computed(() => Object.groupBy(props.produtos ?? [], (p) => p.categoria?.nome ?? "Outros"));
    const total = computed(() => Number(props.pedido.total_calculado ?? props.pedido.total ?? 0));
    const trocoCalc = computed(() => Math.max(0, Number(valorRecebido.value || total.value) - total.value));
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-blue-50 p-4 text-slate-950 sm:p-6" }, _attrs))}><header class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-white p-4 shadow-sm"><div><h1 class="text-3xl font-black">Conta Bar #${ssrInterpolate(__props.pedido.id)}</h1><p class="font-semibold text-slate-500">${ssrInterpolate(__props.pedido.observacoes || "Sem identificação")} · ${ssrInterpolate(__props.pedido.estado)}</p></div>`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("bar.index"),
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
      _push(`</header><div class="grid gap-5 lg:grid-cols-[1fr_420px]"><section class="rounded-3xl bg-white p-4 shadow-sm"><h2 class="mb-3 text-xl font-black">Itens já adicionados</h2><div class="divide-y rounded-2xl bg-slate-50"><!--[-->`);
      ssrRenderList(__props.pedido.items, (item) => {
        _push(`<div class="flex items-center justify-between p-3"><div><strong>${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)}</strong><div class="text-sm text-slate-500">${ssrInterpolate(item.produto?.categoria?.nome)}</div></div><strong>${ssrInterpolate(euros(item.quantidade * item.preco_unitario))}</strong></div>`);
      });
      _push(`<!--]--></div><button class="mt-4 w-full rounded-2xl bg-slate-900 p-4 font-black text-white">Adicionar Itens</button>`);
      if (mostrarProdutos.value) {
        _push(`<div class="mt-5 space-y-5"><!--[-->`);
        ssrRenderList(porCategoria.value, (lista, categoria) => {
          _push(`<div><h3 class="mb-2 font-black">${ssrInterpolate(categoria)}</h3><div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4"><!--[-->`);
          ssrRenderList(lista, (produto) => {
            _push(`<button type="button" class="min-h-24 rounded-2xl border border-blue-100 bg-blue-50 p-3 text-left font-black">${ssrInterpolate(produto.nome)}<span class="mt-2 block text-blue-700">${ssrInterpolate(euros(produto.preco))}</span></button>`);
          });
          _push(`<!--]--></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</section><form class="sticky bottom-3 self-start rounded-3xl bg-white p-4 shadow-xl"><div class="rounded-2xl bg-blue-600 p-4 text-white"><div class="text-sm font-bold">Total atual</div><div class="text-4xl font-black">${ssrInterpolate(euros(total.value))}</div></div><label class="mt-4 block font-bold">Valor recebido<input${ssrRenderAttr("value", valorRecebido.value)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"></label><label class="mt-3 block font-bold">Troco entregue<input${ssrRenderAttr("value", troco.value)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"${ssrRenderAttr("placeholder", euros(trocoCalc.value))}></label><button class="mt-4 w-full rounded-2xl bg-blue-600 p-5 text-lg font-black text-white">FECHAR E IMPRIMIR</button></form></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Bar/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
