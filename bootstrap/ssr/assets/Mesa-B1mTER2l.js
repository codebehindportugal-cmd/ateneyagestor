import { ref, computed, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, toDisplayString, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { usePage, useForm, Link } from "@inertiajs/vue3";
import "qrcode";
const limiteAnulacaoMs = 2 * 60 * 1e3;
const _sfc_main = {
  __name: "Mesa",
  __ssrInlineRender: true,
  props: { mesa: Object, pedido: Object, produtos: Object },
  setup(__props) {
    const props = __props;
    const page = usePage();
    const categoriaAtual = ref(Object.keys(props.produtos ?? {})[0] || "");
    const separadorAtual = ref("produtos");
    const pagamentoAberto = ref(false);
    const metodo = ref("dinheiro");
    const recebido = ref("");
    const lugaresOcupados = ref("");
    const letraSubmesaNova = ref("");
    const carrinho = ref([]);
    const aviso = ref("");
    const qrAberto = ref(false);
    const qrDataUrl = ref("");
    const qrTitulo = ref("");
    const qrSubtitulo = ref("");
    const qrLink = ref("");
    const lugaresAtuais = ref(props.pedido?.mesa?.capacidade ?? props.mesa?.capacidade ?? "");
    const agora = ref(Date.now());
    const novoForm = useForm({ lugares_ocupados: null, submesa_letra: null, mesas_grupo: null });
    const mesasGrupo = ref("");
    const submesaLetras = ["A", "B", "C", "D"];
    const itemForm = useForm({ items: [] });
    const lugaresForm = useForm({ lugares_ocupados: lugaresAtuais.value });
    useForm({ metodo_pagamento: "dinheiro", valor_recebido: 0, troco: 0 });
    let avisoTimer;
    let relogioAnulacaoTimer;
    const categorias = computed(() => Object.keys(props.produtos ?? {}));
    const lista = computed(() => props.produtos?.[categoriaAtual.value] ?? []);
    const total = computed(() => (props.pedido?.items ?? []).reduce((s, i) => s + Number(i.preco_unitario) * i.quantidade, 0));
    const totalCarrinho = computed(() => carrinho.value.reduce((s, item) => s + Number(item.preco) * Number(item.quantidade), 0));
    const troco = computed(() => Math.max(0, Number(recebido.value || total.value) - total.value));
    const mesaDividida = computed(() => !props.mesa?.mesa_principal_id && props.mesa?.submesas?.length > 0);
    computed(() => !props.pedido && !mesaDividida.value && Number(props.mesa?.capacidade ?? 0) > 1);
    const pedidoAutor = computed(() => props.pedido?.operador_nome ?? props.pedido?.user?.name ?? props.pedido?.pos?.nome ?? "Sem utilizador");
    const erroItem = computed(() => page.props.errors?.item);
    const podeSelfOrder = computed(() => props.pedido?.cliente_token && ["pendente", "preparacao"].includes(props.pedido?.estado));
    computed(() => podeSelfOrder.value ? route("cliente.mesa", props.pedido.cliente_token) : "");
    computed(() => route("precario"));
    const capacidadeMesa = computed(() => Number(props.mesa?.capacidade ?? 0));
    const lugaresNumero = computed(() => Number(lugaresOcupados.value || 0));
    const extrairLetraSubmesa = (mesa) => {
      const base = String(mesa?.mesa_principal?.numero ?? props.mesa?.mesa_principal?.numero ?? props.mesa?.numero ?? "");
      const designacao = String(mesa?.designacao ?? mesa?.nome ?? "");
      return designacao.replace(new RegExp(`^Mesa\\s*${base}`, "i"), "").trim().toUpperCase();
    };
    const letraSubmesaAtual = computed(() => props.mesa?.mesa_principal_id ? extrairLetraSubmesa(props.mesa) : "");
    const letrasSubmesaUsadas = computed(() => (props.mesa?.submesas ?? []).map((submesa) => extrairLetraSubmesa(submesa)).filter(Boolean));
    const submesaLetrasDisponiveis = computed(() => submesaLetras.filter((letra) => {
      if (letra === letraSubmesaAtual.value) {
        return true;
      }
      return !letrasSubmesaUsadas.value.includes(letra);
    }));
    const precisaSubmesa = computed(() => !props.pedido && lugaresNumero.value > 0 && lugaresNumero.value < capacidadeMesa.value);
    const precisaMesasGrupo = computed(() => !props.pedido && lugaresNumero.value > capacidadeMesa.value);
    const podeAbrirPedido = computed(() => !mesaDividida.value && lugaresNumero.value > 0 && (!precisaSubmesa.value || letraSubmesaNova.value.trim()) && (!precisaMesasGrupo.value || mesasGrupo.value.trim()));
    const separadores = computed(() => [
      { key: "conta", label: "Conta", count: props.pedido?.items?.length ?? 0 },
      { key: "produtos", label: "Produtos", count: null },
      { key: "envio", label: "Envio", count: carrinho.value.reduce((soma, item) => soma + Number(item.quantidade), 0) },
      { key: "qrs", label: "QRs", count: podeSelfOrder.value ? 2 : 1 }
    ]);
    const itemDentroPrazoAnulacao = (item) => {
      if (!item?.created_at) return false;
      const criadoEm = new Date(item.created_at).getTime();
      if (Number.isNaN(criadoEm)) return false;
      return agora.value - criadoEm <= limiteAnulacaoMs;
    };
    const euros = (v) => Number(v ?? 0).toFixed(2) + "€";
    const secaoClasse = (produto) => ({
      bebidas: "bg-blue-600",
      frango: "bg-red-700",
      cozinha: "bg-orange-600",
      comida: "bg-orange-600",
      acompanhamentos: "bg-emerald-700",
      sobremesas: "bg-purple-600"
    })[produto.categoria?.secao] || "bg-gray-700";
    onMounted(() => {
      relogioAnulacaoTimer = window.setInterval(() => {
        agora.value = Date.now();
      }, 1e4);
    });
    onBeforeUnmount(() => {
      window.clearInterval(relogioAnulacaoTimer);
      window.clearTimeout(avisoTimer);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-gray-900 p-3 text-white sm:p-4" }, _attrs))}><header class="mb-4 flex items-center justify-between">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("pos.rest.mesas"),
        class: "rounded-lg bg-gray-800 px-4 py-3 font-black"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`← MESAS`);
          } else {
            return [
              createTextVNode("← MESAS")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<h1 class="text-3xl font-black">MESA ${ssrInterpolate(__props.mesa.numero)}</h1></header>`);
      if (!__props.pedido) {
        _push(`<div class="min-w-0 space-y-4"><section class="min-w-0 rounded-2xl bg-gray-800 p-4 shadow-lg sm:p-5"><h2 class="break-words text-2xl font-black sm:text-3xl">${ssrInterpolate(__props.mesa.designacao || `MESA ${__props.mesa.numero}`)}</h2><p class="mt-2 rounded-lg bg-gray-900 p-3 text-sm font-bold text-gray-300">Antes de escolher produtos, abre o pedido com o numero de pessoas.</p>`);
        if (!mesaDividida.value) {
          _push(`<div class="mt-5 space-y-4"><label class="block font-black">Numero de pessoas <input${ssrRenderAttr("value", lugaresOcupados.value)} type="number" min="1" max="80" class="mt-2 w-full rounded-lg border-gray-700 bg-gray-900 p-4 text-2xl font-black text-white" placeholder="Obrigatorio"></label>`);
          if (precisaSubmesa.value) {
            _push(`<label class="block font-black">Letra da submesa <select class="mt-2 w-full rounded-lg border-gray-700 bg-gray-900 p-4 text-2xl font-black uppercase text-white"><option value=""${ssrIncludeBooleanAttr(Array.isArray(letraSubmesaNova.value) ? ssrLooseContain(letraSubmesaNova.value, "") : ssrLooseEqual(letraSubmesaNova.value, "")) ? " selected" : ""}>Escolher letra</option><!--[-->`);
            ssrRenderList(submesaLetrasDisponiveis.value, (letra) => {
              _push(`<option${ssrRenderAttr("value", letra)}${ssrIncludeBooleanAttr(Array.isArray(letraSubmesaNova.value) ? ssrLooseContain(letraSubmesaNova.value, letra) : ssrLooseEqual(letraSubmesaNova.value, letra)) ? " selected" : ""}>${ssrInterpolate(letra)}</option>`);
            });
            _push(`<!--]--></select></label>`);
          } else {
            _push(`<!---->`);
          }
          if (precisaMesasGrupo.value) {
            _push(`<label class="block font-black">Mesas do grupo <input${ssrRenderAttr("value", mesasGrupo.value)} type="text" class="mt-2 w-full rounded-lg border-gray-700 bg-gray-900 p-4 text-2xl font-black text-white" placeholder="Ex.: 32 33 34"></label>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="w-full rounded-lg bg-emerald-600 p-4 text-lg font-black disabled:opacity-40"${ssrIncludeBooleanAttr(!podeAbrirPedido.value || unref(novoForm).processing) ? " disabled" : ""}>${ssrInterpolate(unref(novoForm).processing ? "A ABRIR..." : "ABRIR PEDIDO")}</button></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.mesa.submesas?.length) {
          _push(`<div class="mt-6 space-y-2"><p class="mb-3 font-bold text-gray-400">SUBMESAS:</p><!--[-->`);
          ssrRenderList(__props.mesa.submesas, (submesa) => {
            _push(ssrRenderComponent(unref(Link), {
              key: submesa.id,
              href: _ctx.route("pos.rest.mesa", submesa.id),
              class: ["block rounded-lg p-3 font-black", submesa.estado === "ocupada" ? "bg-red-700" : "bg-gray-700"]
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`${ssrInterpolate(submesa.designacao)} · ${ssrInterpolate(submesa.capacidade)} lugares · ${ssrInterpolate(submesa.estado)}`);
                } else {
                  return [
                    createTextVNode(toDisplayString(submesa.designacao) + " · " + toDisplayString(submesa.capacidade) + " lugares · " + toDisplayString(submesa.estado), 1)
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(novoForm).errors.mesa_id) {
          _push(`<div class="mt-4 rounded bg-red-700 p-3 font-bold">${ssrInterpolate(unref(novoForm).errors.mesa_id)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(novoForm).errors.submesa_letra) {
          _push(`<div class="mt-4 rounded bg-red-700 p-3 font-bold">${ssrInterpolate(unref(novoForm).errors.submesa_letra)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(novoForm).errors.lugares_ocupados) {
          _push(`<div class="mt-4 rounded bg-red-700 p-3 font-bold">${ssrInterpolate(unref(novoForm).errors.lugares_ocupados)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(novoForm).errors.mesas_grupo) {
          _push(`<div class="mt-4 rounded bg-red-700 p-3 font-bold">${ssrInterpolate(unref(novoForm).errors.mesas_grupo)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</section><section class="min-w-0 overflow-hidden rounded-lg bg-gray-800 p-3 opacity-50 sm:p-4"><div class="mb-4 rounded bg-gray-900 p-3 text-center text-sm font-black text-gray-300">Abre o pedido para adicionar produtos.</div><div class="mb-4 flex max-w-full gap-2 overflow-x-auto pb-2"><!--[-->`);
        ssrRenderList(categorias.value, (cat) => {
          _push(`<button class="${ssrRenderClass([cat === categoriaAtual.value ? "bg-emerald-600" : "bg-gray-700", "min-h-12 shrink-0 whitespace-nowrap rounded-lg px-4 py-3 font-black"])}">${ssrInterpolate(cat)}</button>`);
        });
        _push(`<!--]--></div><div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"><!--[-->`);
        ssrRenderList(lista.value, (produto) => {
          _push(`<button class="${ssrRenderClass([secaoClasse(produto), "min-h-24 min-w-0 rounded-lg p-4 text-left font-black sm:min-h-28"])}"><span class="block text-lg">${ssrInterpolate(produto.nome)}</span><span class="mt-2 block text-2xl">${ssrInterpolate(euros(produto.preco))}</span></button>`);
        });
        _push(`<!--]--></div></section></div>`);
      } else {
        _push(`<div class="min-w-0 space-y-4"><nav class="overflow-x-auto rounded-2xl bg-gray-800 p-1"><div class="flex min-w-max gap-2"><!--[-->`);
        ssrRenderList(separadores.value, (separador) => {
          _push(`<button type="button" class="${ssrRenderClass([separadorAtual.value === separador.key ? "bg-white text-gray-950" : "text-gray-200", "min-h-12 min-w-28 shrink-0 rounded-xl px-4 py-2 text-sm font-black"])}">${ssrInterpolate(separador.label)} `);
          if (separador.count) {
            _push(`<span class="ml-1 rounded-full bg-emerald-500 px-2 py-0.5 text-xs text-gray-950">${ssrInterpolate(separador.count)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button>`);
        });
        _push(`<!--]--></div></nav>`);
        if (aviso.value || unref(page).props.flash?.success) {
          _push(`<div class="rounded-xl border border-emerald-500/40 bg-emerald-500/15 p-3 text-sm font-black text-emerald-100">${ssrInterpolate(aviso.value || unref(page).props.flash.success)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "produtos") {
          _push(`<section class="min-w-0 overflow-hidden rounded-lg bg-gray-800 p-3 sm:p-4"><div class="mb-4 flex max-w-full gap-2 overflow-x-auto pb-2"><!--[-->`);
          ssrRenderList(categorias.value, (cat) => {
            _push(`<button class="${ssrRenderClass([cat === categoriaAtual.value ? "bg-emerald-600" : "bg-gray-700", "min-h-12 shrink-0 whitespace-nowrap rounded-lg px-4 py-3 font-black"])}">${ssrInterpolate(cat)}</button>`);
          });
          _push(`<!--]--></div><div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"><!--[-->`);
          ssrRenderList(lista.value, (produto) => {
            _push(`<button class="${ssrRenderClass([secaoClasse(produto), "min-h-24 min-w-0 rounded-lg p-4 text-left font-black sm:min-h-28"])}"><span class="block break-words text-lg">${ssrInterpolate(produto.nome)}</span><span class="mt-2 block text-2xl">${ssrInterpolate(euros(produto.preco))}</span></button>`);
          });
          _push(`<!--]--></div></section>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "envio") {
          _push(`<section class="min-w-0 rounded-lg border-2 border-emerald-500 bg-gray-800 p-4"><div class="mb-3 flex items-center justify-between gap-2"><h2 class="text-2xl font-black">Para enviar</h2><strong class="text-2xl text-emerald-400">${ssrInterpolate(euros(totalCarrinho.value))}</strong></div>`);
          if (!carrinho.value.length) {
            _push(`<div class="rounded bg-gray-900 p-5 text-center font-bold text-gray-400"> Escolhe produtos no separador Produtos. </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(carrinho.value, (item, index) => {
            _push(`<div class="mb-2 rounded bg-gray-900 p-3"><div class="mb-2 flex items-center justify-between gap-2"><strong>${ssrInterpolate(item.nome)}</strong><button class="rounded bg-red-700 px-3 py-2 font-black">-</button></div><div class="flex items-center justify-between gap-2"><span class="font-mono text-lg">${ssrInterpolate(item.quantidade)} x ${ssrInterpolate(euros(item.preco))}</span><button class="rounded bg-emerald-700 px-3 py-2 font-black">+</button></div><label class="mt-2 flex items-center gap-2 text-sm font-black text-amber-300"><input${ssrIncludeBooleanAttr(Array.isArray(item.prioridade) ? ssrLooseContain(item.prioridade, null) : item.prioridade) ? " checked" : ""} type="checkbox" class="rounded border-gray-600 bg-gray-800 text-amber-500"> A terminar </label><textarea rows="2" maxlength="255" class="mt-2 w-full rounded-lg border-gray-700 bg-gray-800 p-3 text-sm font-bold text-white placeholder:text-gray-500" placeholder="Observações: sem cebola, bem passado...">${ssrInterpolate(item.observacoes)}</textarea></div>`);
          });
          _push(`<!--]-->`);
          if (unref(itemForm).errors.items) {
            _push(`<div class="mt-2 rounded bg-red-700 p-3 text-sm font-bold">${ssrInterpolate(unref(itemForm).errors.items)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="mt-2 w-full rounded-lg bg-emerald-600 p-4 text-lg font-black disabled:opacity-50"${ssrIncludeBooleanAttr(!carrinho.value.length || unref(itemForm).processing) ? " disabled" : ""}>${ssrInterpolate(unref(itemForm).processing ? "A ENVIAR..." : "ENVIAR PEDIDO")}</button></section>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "conta") {
          _push(`<section class="min-w-0 rounded-lg bg-gray-800 p-4"><h2 class="break-words text-3xl font-black">${ssrInterpolate(__props.mesa.designacao || `MESA ${__props.mesa.numero}`)}</h2><div class="my-4 inline-flex rounded bg-blue-600 px-3 py-1 text-sm font-black uppercase">${ssrInterpolate(__props.pedido.estado)}</div><div class="mb-4 rounded-lg bg-gray-900 p-3"><div class="text-sm font-bold text-gray-400">Pedido feito por</div><div class="text-xl font-black">${ssrInterpolate(pedidoAutor.value)}</div></div><div class="mb-4 rounded-lg bg-gray-900 p-3"><label class="block font-black"> Pessoas na mesa <input${ssrRenderAttr("value", lugaresAtuais.value)} type="number" min="1" class="mt-2 w-full rounded-lg border-gray-700 bg-gray-800 p-3 text-white"></label><button class="mt-2 w-full rounded bg-gray-700 p-3 font-black">ATUALIZAR PESSOAS</button>`);
          if (unref(lugaresForm).errors.lugares_ocupados) {
            _push(`<div class="mt-2 rounded bg-red-700 p-2 text-sm font-bold">${ssrInterpolate(unref(lugaresForm).errors.lugares_ocupados)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="space-y-3">`);
          if (erroItem.value) {
            _push(`<div class="rounded bg-red-700 p-3 text-sm font-black">${ssrInterpolate(erroItem.value)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(__props.pedido.items, (item) => {
            _push(`<div class="${ssrRenderClass([item.prioridade ? "animate-pulse border-amber-500" : "border-gray-700", "rounded-lg border bg-gray-900 p-3"])}"><div class="flex items-start justify-between gap-3"><strong>${ssrInterpolate(item.produto?.nome)}</strong>`);
            if (itemDentroPrazoAnulacao(item)) {
              _push(`<button class="rounded bg-red-700 px-3 py-2 font-black">-1</button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="mt-3 flex items-center justify-between gap-2"><div class="font-mono text-lg">${ssrInterpolate(item.quantidade)} × ${ssrInterpolate(euros(item.preco_unitario))} = ${ssrInterpolate(euros(item.quantidade * item.preco_unitario))}</div><button class="${ssrRenderClass([item.prioridade ? "bg-amber-600" : "bg-gray-700", "rounded px-3 py-2 font-black"])}">Fim</button></div>`);
            if (item.observacoes) {
              _push(`<div class="mt-3 rounded bg-amber-500 px-3 py-2 text-sm font-black text-gray-950">${ssrInterpolate(item.observacoes)}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          });
          _push(`<!--]--></div><div class="my-5 text-right text-3xl font-black text-emerald-400">${ssrInterpolate(euros(total.value))}</div><button class="w-full rounded-lg bg-emerald-600 p-5 text-xl font-black">FECHAR CONTA</button><button class="mt-3 w-full rounded-lg bg-red-700 p-3 font-black">CANCELAR PEDIDO</button></section>`);
        } else {
          _push(`<!---->`);
        }
        if (separadorAtual.value === "qrs") {
          _push(`<section class="grid min-w-0 gap-4 md:grid-cols-2">`);
          if (podeSelfOrder.value) {
            _push(`<article class="rounded-lg border-2 border-cyan-500 bg-gray-800 p-4"><h2 class="text-xl font-black">SELF-ORDER DO CLIENTE</h2><p class="mt-1 text-sm font-bold text-gray-300">Mostra o QR ao cliente para adicionar itens pelo telemóvel.</p><button class="mt-3 w-full rounded-lg bg-cyan-600 p-4 text-lg font-black">MOSTRAR QR AO CLIENTE</button><button class="mt-2 w-full rounded-lg bg-gray-900 p-3 text-sm font-black">COPIAR LINK</button></article>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<article class="rounded-lg border-2 border-emerald-500 bg-gray-800 p-4"><h2 class="text-xl font-black">PREÇÁRIO DO SITE</h2><p class="mt-1 text-sm font-bold text-gray-300">Mostra o QR para o cliente consultar produtos e preços.</p><button class="mt-3 w-full rounded-lg bg-emerald-600 p-4 text-lg font-black">MOSTRAR QR PREÇÁRIO</button></article></section>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      if (pagamentoAberto.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-auto bg-gray-950 p-5"><div class="mx-auto max-w-xl"><h2 class="mb-4 text-center text-4xl font-black text-emerald-400">${ssrInterpolate(euros(total.value))}</h2><div class="mb-4 grid grid-cols-3 gap-3"><!--[-->`);
        ssrRenderList(["dinheiro", "mbway", "multibanco"], (m) => {
          _push(`<button class="${ssrRenderClass([metodo.value === m ? "bg-emerald-600" : "bg-gray-800", "rounded-lg p-3 text-sm font-black uppercase leading-tight sm:p-4 sm:text-base"])}">${ssrInterpolate(m)}</button>`);
        });
        _push(`<!--]--></div><input${ssrRenderAttr("value", recebido.value)} class="mb-3 w-full rounded-lg border-gray-700 bg-gray-800 p-4 text-center text-3xl font-black text-white" placeholder="Recebido"><div class="grid grid-cols-3 gap-2"><!--[-->`);
        ssrRenderList(["1", "2", "3", "4", "5", "6", "7", "8", "9", ".", "0", "del"], (n) => {
          _push(`<button class="rounded-lg bg-gray-800 p-5 text-2xl font-black">${ssrInterpolate(n === "del" ? "←" : n)}</button>`);
        });
        _push(`<!--]--></div><div class="my-4 text-center text-2xl font-black text-emerald-400">Troco: ${ssrInterpolate(euros(troco.value))}</div><button class="w-full rounded-lg bg-emerald-600 p-5 text-xl font-black">✅ CONFIRMAR PAGAMENTO</button><button class="mt-3 w-full rounded-lg bg-gray-700 p-4 font-black">✕ CANCELAR</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (qrAberto.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-auto bg-gray-950 p-5"><div class="mx-auto max-w-md rounded-2xl bg-white p-5 text-center text-slate-950"><h2 class="text-2xl font-black">${ssrInterpolate(qrTitulo.value)}</h2><p class="mt-1 text-sm font-semibold text-slate-500">${ssrInterpolate(qrSubtitulo.value)}</p>`);
        if (qrDataUrl.value) {
          _push(`<img${ssrRenderAttr("src", qrDataUrl.value)} alt="QR code self-order" class="mx-auto my-5 h-72 w-72 rounded-xl border p-3">`);
        } else {
          _push(`<!---->`);
        }
        _push(`<input${ssrRenderAttr("value", qrLink.value)} readonly class="w-full rounded-lg border-slate-300 text-xs"><button class="mt-3 w-full rounded-lg bg-slate-900 p-3 font-black text-white">COPIAR LINK</button><button class="mt-3 w-full rounded-lg bg-gray-200 p-3 font-black text-slate-950">FECHAR</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosRest/Mesa.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
