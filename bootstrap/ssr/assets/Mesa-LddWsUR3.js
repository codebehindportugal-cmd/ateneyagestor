import { ref, computed, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { useForm } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Mesa",
  __ssrInlineRender: true,
  props: {
    token: String,
    pedido: Object,
    produtos: Object,
    itemsEnviados: Array
  },
  setup(__props) {
    const props = __props;
    const categoriaAtual = ref(Object.keys(props.produtos ?? {})[0] || "");
    const separadorAtual = ref("produtos");
    const quantidades = ref({});
    const observacoes = ref({});
    const carrinho = ref([]);
    const aviso = ref("");
    const form = useForm({ items: [] });
    const categorias = computed(() => Object.keys(props.produtos ?? {}));
    const lista = computed(() => props.produtos?.[categoriaAtual.value] ?? []);
    const totalItens = computed(() => carrinho.value.reduce((soma, item) => soma + Number(item.quantidade), 0));
    const totalEnviados = computed(() => (props.itemsEnviados ?? []).reduce((soma, item) => soma + Number(item.quantidade), 0));
    const quantidade = (produto) => Number(quantidades.value[produto.id] ?? 1);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-slate-950 text-white" }, _attrs))}><header class="sticky top-0 z-20 border-b border-white/10 bg-slate-950/95 px-4 py-4 backdrop-blur"><div class="mx-auto flex max-w-xl items-center justify-between gap-3"><div><div class="text-xs font-black uppercase tracking-wide text-emerald-300">ARDC Santana</div><h1 class="text-2xl font-black">Mesa ${ssrInterpolate(__props.pedido.mesa)}</h1></div><button type="button" class="rounded-full bg-white/10 px-3 py-2 text-xs font-black">Enviados</button></div></header><section class="mx-auto max-w-xl px-4 py-5">`);
      if (!__props.pedido.disponivel) {
        _push(`<div class="rounded-2xl border border-amber-400/40 bg-amber-400/10 p-5 text-center"><h2 class="text-xl font-black">Pedido indisponivel</h2><p class="mt-2 text-sm text-amber-100">Este pedido ja foi fechado ou cancelado. Chame um elemento da equipa.</p></div>`);
      } else {
        _push(`<!--[--><div class="mb-4 overflow-x-auto rounded-2xl bg-white/10 p-1"><div class="flex min-w-max gap-2"><button type="button" class="${ssrRenderClass([separadorAtual.value === "produtos" ? "bg-white text-slate-950" : "text-white", "min-h-12 shrink-0 rounded-xl px-4 py-3 text-sm font-black"])}"> Produtos </button><button type="button" class="${ssrRenderClass([separadorAtual.value === "envio" ? "bg-white text-slate-950" : "text-white", "min-h-12 shrink-0 rounded-xl px-4 py-3 text-sm font-black"])}"> Validar e enviar `);
        if (totalItens.value) {
          _push(`<span class="ml-1 rounded-full bg-emerald-500 px-2 py-0.5 text-xs text-slate-950">${ssrInterpolate(totalItens.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button><button type="button" class="${ssrRenderClass([separadorAtual.value === "enviados" ? "bg-white text-slate-950" : "text-white", "min-h-12 shrink-0 rounded-xl px-4 py-3 text-sm font-black"])}"> Enviados `);
        if (totalEnviados.value) {
          _push(`<span class="ml-1 rounded-full bg-sky-400 px-2 py-0.5 text-xs text-slate-950">${ssrInterpolate(totalEnviados.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button></div></div>`);
        if (unref(form).errors.pedido) {
          _push(`<div class="mb-4 rounded-xl bg-red-600 p-3 text-sm font-bold">${ssrInterpolate(unref(form).errors.pedido)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(form).errors.items) {
          _push(`<div class="mb-4 rounded-xl bg-red-600 p-3 text-sm font-bold"> Não foi possível enviar esse pedido. </div>`);
        } else {
          _push(`<!---->`);
        }
        if (aviso.value) {
          _push(`<div class="mb-4 rounded-xl border border-emerald-400/40 bg-emerald-400/15 p-3 text-sm font-black text-emerald-100">${ssrInterpolate(aviso.value)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "produtos") {
          _push(`<div class="mb-4 overflow-x-auto pb-2"><div class="flex min-w-max gap-2"><!--[-->`);
          ssrRenderList(categorias.value, (categoria) => {
            _push(`<button type="button" class="${ssrRenderClass([categoria === categoriaAtual.value ? "bg-emerald-500 text-slate-950" : "bg-white/10 text-white", "min-h-11 shrink-0 rounded-full px-4 py-2 text-sm font-black"])}">${ssrInterpolate(categoria)}</button>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "produtos") {
          _push(`<div class="grid gap-3"><!--[-->`);
          ssrRenderList(lista.value, (produto) => {
            _push(`<article class="rounded-2xl bg-white p-4 text-slate-950 shadow-sm"><div class="mb-4"><h2 class="text-lg font-black">${ssrInterpolate(produto.nome)}</h2><p class="mt-1 text-sm font-semibold text-slate-500">${ssrInterpolate(produto.categoria?.nome || "Produto")}</p></div><label class="mb-4 block"><span class="text-xs font-black uppercase text-slate-500">Observações</span><textarea rows="2" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Ex.: sem cebola, bem passado">${ssrInterpolate(observacoes.value[produto.id])}</textarea></label><div class="flex items-center justify-between gap-3"><div class="flex items-center overflow-hidden rounded-full border border-slate-200"><button type="button" class="h-11 w-12 bg-slate-100 text-xl font-black">-</button><span class="w-12 text-center text-lg font-black">${ssrInterpolate(quantidade(produto))}</span><button type="button" class="h-11 w-12 bg-slate-100 text-xl font-black">+</button></div><button type="button" class="min-h-11 flex-1 rounded-full bg-emerald-500 px-4 py-3 text-sm font-black text-slate-950 disabled:opacity-60"> Adicionar à lista </button></div></article>`);
          });
          _push(`<!--]--></div>`);
        } else if (separadorAtual.value === "envio") {
          _push(`<div class="rounded-2xl bg-white p-4 text-slate-950 shadow-sm"><div class="mb-4"><h2 class="text-xl font-black">Confirmar pedido</h2><p class="mt-1 text-sm font-semibold text-slate-500">Revê as escolhas antes de enviar para a equipa.</p></div>`);
          if (!carrinho.value.length) {
            _push(`<div class="rounded-2xl bg-slate-100 p-5 text-center text-sm font-bold text-slate-500"> Ainda não escolheste produtos. <button type="button" class="mt-3 block w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white"> Escolher produtos </button></div>`);
          } else {
            _push(`<div class="grid gap-3"><!--[-->`);
            ssrRenderList(carrinho.value, (item, index) => {
              _push(`<article class="rounded-2xl bg-slate-100 p-3"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black">${ssrInterpolate(item.nome)}</h3>`);
              if (item.observacoes) {
                _push(`<p class="mt-1 text-sm font-semibold text-slate-600">${ssrInterpolate(item.observacoes)}</p>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div><button type="button" class="rounded-full bg-red-600 px-3 py-2 text-sm font-black text-white"> Remover </button></div><div class="mt-3 flex items-center justify-between"><div class="flex items-center overflow-hidden rounded-full border border-slate-200 bg-white"><button type="button" class="h-10 w-11 bg-slate-100 text-xl font-black">-</button><span class="w-12 text-center text-lg font-black">${ssrInterpolate(item.quantidade)}</span><button type="button" class="h-10 w-11 bg-slate-100 text-xl font-black">+</button></div><span class="rounded-full bg-slate-950 px-3 py-1 text-sm font-black text-white">${ssrInterpolate(item.quantidade)}x</span></div></article>`);
            });
            _push(`<!--]--><button type="button" class="rounded-2xl bg-emerald-500 px-5 py-4 text-lg font-black text-slate-950 disabled:opacity-50"${ssrIncludeBooleanAttr(!carrinho.value.length || unref(form).processing) ? " disabled" : ""}>${ssrInterpolate(unref(form).processing ? "A enviar..." : "Enviar pedido")}</button></div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<div class="rounded-2xl bg-white p-4 text-slate-950 shadow-sm"><div class="mb-4"><h2 class="text-xl font-black">Produtos enviados</h2><p class="mt-1 text-sm font-semibold text-slate-500">Aqui aparecem os produtos que já foram enviados para a equipa.</p></div>`);
          if (!__props.itemsEnviados?.length) {
            _push(`<div class="rounded-2xl bg-slate-100 p-5 text-center text-sm font-bold text-slate-500"> Ainda não foram enviados produtos. <button type="button" class="mt-3 block w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white"> Escolher produtos </button></div>`);
          } else {
            _push(`<div class="grid gap-3"><!--[-->`);
            ssrRenderList(__props.itemsEnviados, (item) => {
              _push(`<article class="rounded-2xl bg-slate-100 p-3"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black">${ssrInterpolate(item.nome)}</h3><p class="mt-1 text-xs font-semibold uppercase text-slate-500">${ssrInterpolate(item.hora || "Enviado")} `);
              if (item.estado) {
                _push(`<span> · ${ssrInterpolate(item.estado)}</span>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</p>`);
              if (item.observacoes) {
                _push(`<p class="mt-1 text-sm font-semibold text-slate-700">${ssrInterpolate(item.observacoes)}</p>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div><span class="shrink-0 rounded-full bg-slate-950 px-3 py-1 text-sm font-black text-white">${ssrInterpolate(item.quantidade)}x</span></div></article>`);
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div>`);
        }
        _push(`<!--]-->`);
      }
      _push(`</section></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Cliente/Mesa.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
