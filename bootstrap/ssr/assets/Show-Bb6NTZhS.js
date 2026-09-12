import { ref, computed, watch, onMounted, withCtx, unref, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, withModifiers, withDirectives, Fragment, renderList, vModelSelect, vModelText, vModelCheckbox, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { usePage, useForm, Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Show",
  __ssrInlineRender: true,
  props: { pedido: Object, mesas: Array, produtos: Array, paraLevar: Boolean },
  setup(__props) {
    const props = __props;
    const page = usePage();
    const pedidoForm = useForm({
      tipo_atendimento: props.paraLevar ? "para_levar" : "mesa",
      mesa_id: props.mesas?.[0]?.id ?? "",
      lugares_ocupados: "",
      submesa_letra: "",
      observacoes: ""
    });
    const itemForm = useForm({ pedido_id: props.pedido?.id, produto_id: "", quantidade: 1, prioridade: false, observacoes: "" });
    const estadoForm = useForm({ estado: props.pedido?.estado ?? "pendente" });
    const cancelamentoForm = useForm({ estado: "cancelado" });
    const fecharContaForm = useForm({ metodo_pagamento: "dinheiro", valor_recebido: "", troco: 0 });
    const quantidade = ref(1);
    const termo = ref("");
    const caixaRef = ref(null);
    const submesaLetras = ["A", "B", "C", "D"];
    const aviso = ref("");
    let avisoTimer;
    const secoes = [
      ["todos", "Todos"],
      ["bebidas", "Bebidas"],
      ["frango", "Frango"],
      ["acompanhamentos", "Acompanhamentos"],
      ["comida", "Comida"],
      ["sobremesas", "Sobremesas"]
    ];
    const secaoAtiva = ref("todos");
    const produtosFiltrados = computed(() => {
      const pesquisa = termo.value.trim().toLowerCase();
      return (props.produtos ?? []).filter((produto) => {
        const secao = produto.categoria?.secao;
        const passaSecao = secaoAtiva.value === "todos" || secao === secaoAtiva.value;
        const passaPesquisa = !pesquisa || produto.nome.toLowerCase().includes(pesquisa);
        return passaSecao && passaPesquisa;
      });
    });
    const mostrarAviso = (mensagem) => {
      aviso.value = mensagem;
      window.clearTimeout(avisoTimer);
      avisoTimer = window.setTimeout(() => {
        aviso.value = "";
      }, 4e3);
    };
    const totalPedido = computed(() => Number(props.pedido?.total ?? props.pedido?.total_calculado ?? 0));
    const pedidoFechado = computed(() => ["entregue", "cancelado"].includes(props.pedido?.estado));
    const valorRecebido = computed(() => Number(fecharContaForm.valor_recebido || totalPedido.value));
    const valorTroco = computed(() => Number(fecharContaForm.troco || 0));
    const trocoADevolver = computed(() => Math.max(0, valorRecebido.value - totalPedido.value));
    const doacaoEstimada = computed(() => Math.max(0, valorRecebido.value - totalPedido.value - valorTroco.value));
    const criadoPor = computed(() => props.pedido?.operador_nome ?? props.pedido?.user?.name ?? props.pedido?.pos?.nome ?? "Sem utilizador");
    const mostrarEstadoItems = computed(() => Boolean(page.props.restaurante?.mostrar_estado_items));
    const erroItem = computed(() => page.props.errors?.item);
    const adicionarProduto = (produto) => {
      if (pedidoFechado.value) {
        return;
      }
      itemForm.pedido_id = props.pedido?.id;
      itemForm.produto_id = produto.id;
      itemForm.quantidade = quantidade.value || 1;
      mostrarAviso("A enviar produto para a secção...");
      itemForm.post(route("pedido-items.store"), {
        preserveScroll: true,
        onSuccess: () => {
          itemForm.produto_id = "";
          quantidade.value = 1;
          itemForm.prioridade = false;
          itemForm.observacoes = "";
          mostrarAviso("Produto enviado para a secção.");
        },
        onError: () => mostrarAviso("Nao foi possivel enviar o produto. Confirma o pedido e tenta novamente.")
      });
    };
    const alternarUrgente = (item) => {
      router.patch(route("pedido-items.update", item.id), { prioridade: !item.prioridade }, { preserveScroll: true });
    };
    const anularItem = (item) => {
      if (!confirm(`Anular 1x ${item.produto?.nome ?? "produto"} deste pedido?`)) {
        return;
      }
      router.delete(route("pedido-items.destroy", item.id), { preserveScroll: true });
    };
    const cancelarPedido = () => {
      if (!confirm("Cancelar este pedido e libertar a mesa?")) {
        return;
      }
      mostrarAviso("A cancelar pedido...");
      cancelamentoForm.patch(route("pedidos.estado", props.pedido.id), {
        preserveScroll: true,
        onSuccess: () => mostrarAviso("Pedido cancelado e mesa libertada."),
        onError: () => mostrarAviso("Nao foi possivel cancelar o pedido. Tenta novamente.")
      });
    };
    const fecharConta = () => {
      fecharContaForm.transform((dados) => ({
        metodo_pagamento: dados.metodo_pagamento,
        valor_recebido: dados.valor_recebido || totalPedido.value,
        troco: dados.troco || 0
      })).patch(route("pedidos.fecharConta", props.pedido.id), {
        onFinish: () => fecharContaForm.transform((dados) => dados)
      });
    };
    const pagamentoCerto = () => {
      fecharContaForm.valor_recebido = totalPedido.value.toFixed(2);
      fecharContaForm.troco = 0;
    };
    const entregarTroco = () => {
      fecharContaForm.troco = trocoADevolver.value.toFixed(2);
    };
    const doarTroco = () => {
      fecharContaForm.troco = 0;
    };
    const formatarPreco = (valor) => `${Number(valor ?? 0).toFixed(2)}€`;
    const escolherTipoAtendimento = (tipo) => {
      pedidoForm.tipo_atendimento = tipo;
      if (tipo === "para_levar") {
        pedidoForm.mesa_id = "";
        pedidoForm.lugares_ocupados = "";
        pedidoForm.submesa_letra = "";
        return;
      }
      pedidoForm.mesa_id = pedidoForm.mesa_id || props.mesas?.[0]?.id || "";
    };
    const criarPedido = () => {
      pedidoForm.transform((dados) => ({
        ...dados,
        mesa_id: dados.tipo_atendimento === "para_levar" ? null : dados.mesa_id,
        lugares_ocupados: dados.tipo_atendimento === "para_levar" ? null : dados.lugares_ocupados,
        submesa_letra: dados.tipo_atendimento === "para_levar" ? null : dados.submesa_letra ? dados.submesa_letra.toUpperCase() : null
      })).post(route("pedidos.store"), {
        onFinish: () => pedidoForm.transform((dados) => dados)
      });
    };
    watch(() => props.pedido?.id, (pedidoId) => {
      itemForm.pedido_id = pedidoId;
      estadoForm.estado = props.pedido?.estado ?? "pendente";
    });
    onMounted(() => {
      if (new URLSearchParams(window.location.search).get("caixa") === "1") {
        setTimeout(() => caixaRef.value?.scrollIntoView({ behavior: "smooth", block: "start" }), 150);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(__props.pedido ? `Pedido #${__props.pedido.id}` : "Novo pedido")}</h1>`);
            if (__props.pedido) {
              _push2(`<p class="mt-1 text-sm text-slate-500"${_scopeId}>${ssrInterpolate(__props.pedido.mesa?.designacao ?? "Para levar")} · ${ssrInterpolate(__props.pedido.estado)} · ${ssrInterpolate(criadoPor.value)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="flex flex-wrap gap-2"${_scopeId}>`);
            if (__props.pedido) {
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("pedidos.talao", __props.pedido.id),
                class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Talão`);
                  } else {
                    return [
                      createTextVNode("Talão")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pedidos.index"),
              class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Voltar`);
                } else {
                  return [
                    createTextVNode("Voltar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
            if (aviso.value) {
              _push2(`<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm"${_scopeId}>${ssrInterpolate(aviso.value)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (!__props.pedido) {
              _push2(`<form class="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow-sm"${_scopeId}><div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-2"${_scopeId}><button type="button" class="${ssrRenderClass([unref(pedidoForm).tipo_atendimento === "mesa" ? "bg-slate-900 text-white" : "bg-white text-slate-700", "rounded-md px-4 py-3 font-black"])}"${_scopeId}>Mesa</button><button type="button" class="${ssrRenderClass([unref(pedidoForm).tipo_atendimento === "para_levar" ? "bg-slate-900 text-white" : "bg-white text-slate-700", "rounded-md px-4 py-3 font-black"])}"${_scopeId}>Para levar</button></div>`);
              if (unref(pedidoForm).tipo_atendimento === "mesa") {
                _push2(`<label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Mesa ou submesa</span><select class="w-full rounded-md border-slate-300"${_scopeId}><!--[-->`);
                ssrRenderList(__props.mesas, (mesa) => {
                  _push2(`<option${ssrRenderAttr("value", mesa.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(pedidoForm).mesa_id) ? ssrLooseContain(unref(pedidoForm).mesa_id, mesa.id) : ssrLooseEqual(unref(pedidoForm).mesa_id, mesa.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(mesa.designacao)}${ssrInterpolate(mesa.lugares ? ` · lugares ${mesa.lugares}` : "")} · ${ssrInterpolate(mesa.capacidade)} pessoas </option>`);
                });
                _push2(`<!--]--></select></label>`);
              } else {
                _push2(`<!---->`);
              }
              if (unref(pedidoForm).tipo_atendimento === "mesa") {
                _push2(`<label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Lugares ocupados</span><input${ssrRenderAttr("value", unref(pedidoForm).lugares_ocupados)} type="number" min="1" class="w-full rounded-md border-slate-300" placeholder="Vazio = mesa completa"${_scopeId}></label>`);
              } else {
                _push2(`<!---->`);
              }
              if (unref(pedidoForm).tipo_atendimento === "mesa" && unref(pedidoForm).lugares_ocupados) {
                _push2(`<label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Letra da submesa</span><select class="w-full rounded-md border-slate-300 uppercase"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(pedidoForm).submesa_letra) ? ssrLooseContain(unref(pedidoForm).submesa_letra, "") : ssrLooseEqual(unref(pedidoForm).submesa_letra, "")) ? " selected" : ""}${_scopeId}>Escolher letra</option><!--[-->`);
                ssrRenderList(submesaLetras, (letra) => {
                  _push2(`<option${ssrRenderAttr("value", letra)}${ssrIncludeBooleanAttr(Array.isArray(unref(pedidoForm).submesa_letra) ? ssrLooseContain(unref(pedidoForm).submesa_letra, letra) : ssrLooseEqual(unref(pedidoForm).submesa_letra, letra)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(letra)}</option>`);
                });
                _push2(`<!--]--></select>`);
                if (unref(pedidoForm).errors.submesa_letra) {
                  _push2(`<div class="mt-1 text-sm font-semibold text-red-600"${_scopeId}>${ssrInterpolate(unref(pedidoForm).errors.submesa_letra)}</div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</label>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<textarea class="w-full rounded-md border-slate-300" placeholder="Observações"${_scopeId}>${ssrInterpolate(unref(pedidoForm).observacoes)}</textarea><button class="rounded-md bg-slate-900 px-4 py-2 text-white"${_scopeId}>Criar pedido</button></form>`);
            } else {
              _push2(`<div class="grid gap-6 xl:grid-cols-[1fr_420px]"${_scopeId}><section class="space-y-4"${_scopeId}>`);
              if (!pedidoFechado.value) {
                _push2(`<div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>Mesa</div><div class="text-xl font-bold"${_scopeId}>${ssrInterpolate(__props.pedido.mesa?.designacao ?? "Para levar")}</div></div><div${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>Pedido feito por</div><div class="text-xl font-bold"${_scopeId}>${ssrInterpolate(criadoPor.value)}</div></div><div class="text-right"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>Total</div><div class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(formatarPreco(totalPedido.value))}</div></div></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="rounded-lg bg-white shadow-sm"${_scopeId}><div class="border-b border-slate-200 px-5 py-4 font-semibold"${_scopeId}>Itens do pedido</div>`);
              if (erroItem.value) {
                _push2(`<div class="mx-5 mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-700"${_scopeId}>${ssrInterpolate(erroItem.value)}</div>`);
              } else {
                _push2(`<!---->`);
              }
              if (__props.pedido.items?.length) {
                _push2(`<div class="divide-y divide-slate-100"${_scopeId}><!--[-->`);
                ssrRenderList(__props.pedido.items, (item) => {
                  _push2(`<div class="${ssrRenderClass([item.prioridade ? "bg-red-50" : "", "flex items-center justify-between gap-4 px-5 py-4"])}"${_scopeId}><div${_scopeId}><div class="font-semibold"${_scopeId}>${ssrInterpolate(item.quantidade)}x ${ssrInterpolate(item.produto?.nome)} `);
                  if (item.prioridade) {
                    _push2(`<span class="ml-2 rounded-full bg-amber-600 px-2 py-1 text-xs font-black text-white"${_scopeId}>A TERMINAR</span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="text-sm text-slate-500"${_scopeId}>${ssrInterpolate(item.produto?.categoria?.nome)} · ${ssrInterpolate(formatarPreco(item.preco_unitario))} cada</div>`);
                  if (item.observacoes) {
                    _push2(`<div class="mt-2 rounded-md bg-amber-100 px-3 py-2 text-sm font-bold text-amber-900"${_scopeId}> Info: ${ssrInterpolate(item.observacoes)}</div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="flex items-center gap-2"${_scopeId}><button type="button" class="${ssrRenderClass([item.prioridade ? "bg-amber-600 text-white" : "bg-slate-100 text-slate-700", "rounded-full px-3 py-2 text-xs font-black"])}"${_scopeId}>A terminar</button>`);
                  if (!pedidoFechado.value) {
                    _push2(`<button type="button" class="rounded-full bg-red-100 px-3 py-2 text-xs font-black text-red-700"${_scopeId}>${ssrInterpolate(item.quantidade > 1 ? "-1" : "Anular")}</button>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (mostrarEstadoItems.value) {
                    _push2(`<div class="${ssrRenderClass([item.estado === "pronto" ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-800", "rounded-full px-3 py-1 text-xs font-semibold"])}"${_scopeId}>${ssrInterpolate(item.estado)}</div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="px-5 py-8 text-center text-sm text-slate-500"${_scopeId}>Ainda não há comida ou bebidas neste pedido.</div>`);
              }
              _push2(`</div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex items-center justify-between gap-3"${_scopeId}><h2 class="font-semibold"${_scopeId}>Adicionar produtos</h2><input${ssrRenderAttr("value", quantidade.value)} type="number" min="1" class="w-20 rounded-md border-slate-300 text-center"${_scopeId}></div><label class="mb-4 flex items-center gap-3 rounded-lg bg-amber-50 p-3 font-bold text-amber-800"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(itemForm).prioridade) ? ssrLooseContain(unref(itemForm).prioridade, null) : unref(itemForm).prioridade) ? " checked" : ""} type="checkbox" class="rounded border-amber-300 text-amber-600"${_scopeId}> Marcar novo item como a terminar </label><label class="mb-4 block rounded-lg bg-amber-50 p-3"${_scopeId}><span class="mb-1 block text-sm font-black text-amber-900"${_scopeId}>Informação para a secção</span><textarea rows="2" class="w-full rounded-md border-amber-200 text-sm" placeholder="Ex.: sem picante, alergia, sem molho..."${_scopeId}>${ssrInterpolate(unref(itemForm).observacoes)}</textarea></label><div class="mb-3 flex flex-wrap gap-2"${_scopeId}><!--[-->`);
              ssrRenderList(secoes, ([valor, label]) => {
                _push2(`<button type="button" class="${ssrRenderClass([secaoAtiva.value === valor ? "border-slate-900 bg-slate-900 text-white" : "border-slate-300 bg-white text-slate-700", "rounded-md border px-3 py-2 text-sm font-semibold"])}"${_scopeId}>${ssrInterpolate(label)}</button>`);
              });
              _push2(`<!--]--></div><input${ssrRenderAttr("value", termo.value)} class="mb-4 w-full rounded-md border-slate-300" placeholder="Procurar produto"${_scopeId}><div class="grid max-h-[58vh] gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(produtosFiltrados.value, (produto) => {
                _push2(`<button type="button" class="rounded-lg border border-slate-200 p-3 text-left hover:border-emerald-500 hover:bg-emerald-50"${_scopeId}><div class="font-semibold"${_scopeId}>${ssrInterpolate(produto.nome)}</div><div class="mt-1 flex items-center justify-between text-sm text-slate-500"${_scopeId}><span${_scopeId}>${ssrInterpolate(produto.categoria?.nome)}</span><span class="font-semibold text-slate-900"${_scopeId}>${ssrInterpolate(formatarPreco(produto.preco))}</span></div></button>`);
              });
              _push2(`<!--]--></div></div></section><aside class="space-y-4"${_scopeId}><form class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Estado do pedido</span><select class="w-full rounded-md border-slate-300"${_scopeId}><option${ssrIncludeBooleanAttr(Array.isArray(unref(estadoForm).estado) ? ssrLooseContain(unref(estadoForm).estado, null) : ssrLooseEqual(unref(estadoForm).estado, null)) ? " selected" : ""}${_scopeId}>pendente</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(estadoForm).estado) ? ssrLooseContain(unref(estadoForm).estado, null) : ssrLooseEqual(unref(estadoForm).estado, null)) ? " selected" : ""}${_scopeId}>preparacao</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(estadoForm).estado) ? ssrLooseContain(unref(estadoForm).estado, null) : ssrLooseEqual(unref(estadoForm).estado, null)) ? " selected" : ""}${_scopeId}>entregue</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(estadoForm).estado) ? ssrLooseContain(unref(estadoForm).estado, null) : ssrLooseEqual(unref(estadoForm).estado, null)) ? " selected" : ""}${_scopeId}>cancelado</option></select></label><button class="mt-3 rounded-md bg-emerald-700 px-4 py-2 text-white"${_scopeId}>Mudar estado</button></form>`);
              if (!pedidoFechado.value) {
                _push2(`<div class="space-y-3 xl:sticky xl:top-5 xl:z-10"${_scopeId}><form class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 rounded-lg bg-slate-900 p-4 text-white"${_scopeId}><div class="text-sm font-bold text-white/70"${_scopeId}>Total a receber</div><div class="mt-1 text-5xl font-black"${_scopeId}>${ssrInterpolate(formatarPreco(totalPedido.value))}</div></div><h2 class="mb-3 font-semibold"${_scopeId}>Fechar conta</h2><div class="grid gap-3"${_scopeId}><label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Método de pagamento</span><select class="w-full rounded-md border-slate-300"${_scopeId}><option value="dinheiro"${ssrIncludeBooleanAttr(Array.isArray(unref(fecharContaForm).metodo_pagamento) ? ssrLooseContain(unref(fecharContaForm).metodo_pagamento, "dinheiro") : ssrLooseEqual(unref(fecharContaForm).metodo_pagamento, "dinheiro")) ? " selected" : ""}${_scopeId}>Dinheiro</option><option value="mbway"${ssrIncludeBooleanAttr(Array.isArray(unref(fecharContaForm).metodo_pagamento) ? ssrLooseContain(unref(fecharContaForm).metodo_pagamento, "mbway") : ssrLooseEqual(unref(fecharContaForm).metodo_pagamento, "mbway")) ? " selected" : ""}${_scopeId}>MBWay</option><option value="multibanco"${ssrIncludeBooleanAttr(Array.isArray(unref(fecharContaForm).metodo_pagamento) ? ssrLooseContain(unref(fecharContaForm).metodo_pagamento, "multibanco") : ssrLooseEqual(unref(fecharContaForm).metodo_pagamento, "multibanco")) ? " selected" : ""}${_scopeId}>Multibanco</option><option value="transferencia"${ssrIncludeBooleanAttr(Array.isArray(unref(fecharContaForm).metodo_pagamento) ? ssrLooseContain(unref(fecharContaForm).metodo_pagamento, "transferencia") : ssrLooseEqual(unref(fecharContaForm).metodo_pagamento, "transferencia")) ? " selected" : ""}${_scopeId}>Transferência</option></select></label><label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Valor recebido</span><input${ssrRenderAttr("value", unref(fecharContaForm).valor_recebido)} type="number" min="0" step="0.01" class="w-full rounded-md border-slate-300"${ssrRenderAttr("placeholder", formatarPreco(totalPedido.value))}${_scopeId}></label><div class="grid grid-cols-2 gap-2"${_scopeId}><button type="button" class="rounded-md bg-emerald-700 px-3 py-3 text-sm font-black text-white"${_scopeId}>Valor certo</button><button type="button" class="rounded-md border border-slate-300 px-3 py-3 text-sm font-black"${_scopeId}>Entregar troco</button></div><label class="block"${_scopeId}><span class="mb-1 block text-sm font-semibold text-slate-700"${_scopeId}>Troco entregue</span><input${ssrRenderAttr("value", unref(fecharContaForm).troco)} type="number" min="0" step="0.01" class="w-full rounded-md border-slate-300"${_scopeId}></label><button type="button" class="rounded-md bg-amber-100 px-3 py-3 text-sm font-black text-amber-900"${_scopeId}> Cliente deixa o troco como doação </button></div><div class="mt-3 rounded-md bg-slate-50 p-3 text-sm"${_scopeId}><div class="flex justify-between"${_scopeId}><span${_scopeId}>Total</span><strong${_scopeId}>${ssrInterpolate(formatarPreco(totalPedido.value))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>Recebido</span><strong${_scopeId}>${ssrInterpolate(formatarPreco(valorRecebido.value))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>Troco possível</span><strong${_scopeId}>${ssrInterpolate(formatarPreco(trocoADevolver.value))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>Troco entregue</span><strong${_scopeId}>${ssrInterpolate(formatarPreco(valorTroco.value))}</strong></div><div class="flex justify-between text-amber-800"${_scopeId}><span${_scopeId}>Doação</span><strong${_scopeId}>${ssrInterpolate(formatarPreco(doacaoEstimada.value))}</strong></div></div>`);
                if (Object.keys(unref(fecharContaForm).errors).length) {
                  _push2(`<div class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700"${_scopeId}><!--[-->`);
                  ssrRenderList(unref(fecharContaForm).errors, (erro) => {
                    _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<button type="submit" class="mt-3 w-full rounded-lg bg-slate-900 p-4 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"${ssrIncludeBooleanAttr(unref(fecharContaForm).processing) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(fecharContaForm).processing ? "A fechar..." : "Receber e imprimir talão")}</button></form><button type="button" class="w-full rounded-lg border border-red-300 bg-white p-4 text-sm font-bold text-red-700 shadow-sm hover:bg-red-50"${_scopeId}> Cancelar pedido </button></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</aside></div>`);
            }
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-center justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-bold" }, toDisplayString(__props.pedido ? `Pedido #${__props.pedido.id}` : "Novo pedido"), 1),
                  __props.pedido ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "mt-1 text-sm text-slate-500"
                  }, toDisplayString(__props.pedido.mesa?.designacao ?? "Para levar") + " · " + toDisplayString(__props.pedido.estado) + " · " + toDisplayString(criadoPor.value), 1)) : createCommentVNode("", true)
                ]),
                createVNode("div", { class: "flex flex-wrap gap-2" }, [
                  __props.pedido ? (openBlock(), createBlock(unref(Link), {
                    key: 0,
                    href: _ctx.route("pedidos.talao", __props.pedido.id),
                    class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Talão")
                    ]),
                    _: 1
                  }, 8, ["href"])) : createCommentVNode("", true),
                  createVNode(unref(Link), {
                    href: _ctx.route("pedidos.index"),
                    class: "rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Voltar")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ]),
              aviso.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-sm"
              }, toDisplayString(aviso.value), 1)) : createCommentVNode("", true),
              !__props.pedido ? (openBlock(), createBlock("form", {
                key: 1,
                class: "max-w-xl space-y-4 rounded-lg bg-white p-6 shadow-sm",
                onSubmit: withModifiers(criarPedido, ["prevent"])
              }, [
                createVNode("div", { class: "grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-2" }, [
                  createVNode("button", {
                    type: "button",
                    class: ["rounded-md px-4 py-3 font-black", unref(pedidoForm).tipo_atendimento === "mesa" ? "bg-slate-900 text-white" : "bg-white text-slate-700"],
                    onClick: ($event) => escolherTipoAtendimento("mesa")
                  }, "Mesa", 10, ["onClick"]),
                  createVNode("button", {
                    type: "button",
                    class: ["rounded-md px-4 py-3 font-black", unref(pedidoForm).tipo_atendimento === "para_levar" ? "bg-slate-900 text-white" : "bg-white text-slate-700"],
                    onClick: ($event) => escolherTipoAtendimento("para_levar")
                  }, "Para levar", 10, ["onClick"])
                ]),
                unref(pedidoForm).tipo_atendimento === "mesa" ? (openBlock(), createBlock("label", {
                  key: 0,
                  class: "block"
                }, [
                  createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Mesa ou submesa"),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => unref(pedidoForm).mesa_id = $event,
                    class: "w-full rounded-md border-slate-300"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.mesas, (mesa) => {
                      return openBlock(), createBlock("option", {
                        key: mesa.id,
                        value: mesa.id
                      }, toDisplayString(mesa.designacao) + toDisplayString(mesa.lugares ? ` · lugares ${mesa.lugares}` : "") + " · " + toDisplayString(mesa.capacidade) + " pessoas ", 9, ["value"]);
                    }), 128))
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, unref(pedidoForm).mesa_id]
                  ])
                ])) : createCommentVNode("", true),
                unref(pedidoForm).tipo_atendimento === "mesa" ? (openBlock(), createBlock("label", {
                  key: 1,
                  class: "block"
                }, [
                  createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Lugares ocupados"),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(pedidoForm).lugares_ocupados = $event,
                    type: "number",
                    min: "1",
                    class: "w-full rounded-md border-slate-300",
                    placeholder: "Vazio = mesa completa"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(pedidoForm).lugares_ocupados]
                  ])
                ])) : createCommentVNode("", true),
                unref(pedidoForm).tipo_atendimento === "mesa" && unref(pedidoForm).lugares_ocupados ? (openBlock(), createBlock("label", {
                  key: 2,
                  class: "block"
                }, [
                  createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Letra da submesa"),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => unref(pedidoForm).submesa_letra = $event,
                    class: "w-full rounded-md border-slate-300 uppercase"
                  }, [
                    createVNode("option", { value: "" }, "Escolher letra"),
                    (openBlock(), createBlock(Fragment, null, renderList(submesaLetras, (letra) => {
                      return createVNode("option", {
                        key: letra,
                        value: letra
                      }, toDisplayString(letra), 9, ["value"]);
                    }), 64))
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, unref(pedidoForm).submesa_letra]
                  ]),
                  unref(pedidoForm).errors.submesa_letra ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "mt-1 text-sm font-semibold text-red-600"
                  }, toDisplayString(unref(pedidoForm).errors.submesa_letra), 1)) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                withDirectives(createVNode("textarea", {
                  "onUpdate:modelValue": ($event) => unref(pedidoForm).observacoes = $event,
                  class: "w-full rounded-md border-slate-300",
                  placeholder: "Observações"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(pedidoForm).observacoes]
                ]),
                createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-white" }, "Criar pedido")
              ], 32)) : (openBlock(), createBlock("div", {
                key: 2,
                class: "grid gap-6 xl:grid-cols-[1fr_420px]"
              }, [
                createVNode("section", { class: "space-y-4" }, [
                  !pedidoFechado.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "rounded-lg bg-white p-5 shadow-sm"
                  }, [
                    createVNode("div", { class: "flex flex-wrap items-center justify-between gap-3" }, [
                      createVNode("div", null, [
                        createVNode("div", { class: "text-sm text-slate-500" }, "Mesa"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(__props.pedido.mesa?.designacao ?? "Para levar"), 1)
                      ]),
                      createVNode("div", null, [
                        createVNode("div", { class: "text-sm text-slate-500" }, "Pedido feito por"),
                        createVNode("div", { class: "text-xl font-bold" }, toDisplayString(criadoPor.value), 1)
                      ]),
                      createVNode("div", { class: "text-right" }, [
                        createVNode("div", { class: "text-sm text-slate-500" }, "Total"),
                        createVNode("div", { class: "text-2xl font-bold" }, toDisplayString(formatarPreco(totalPedido.value)), 1)
                      ])
                    ])
                  ])) : createCommentVNode("", true),
                  createVNode("div", { class: "rounded-lg bg-white shadow-sm" }, [
                    createVNode("div", { class: "border-b border-slate-200 px-5 py-4 font-semibold" }, "Itens do pedido"),
                    erroItem.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mx-5 mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-700"
                    }, toDisplayString(erroItem.value), 1)) : createCommentVNode("", true),
                    __props.pedido.items?.length ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "divide-y divide-slate-100"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(__props.pedido.items, (item) => {
                        return openBlock(), createBlock("div", {
                          key: item.id,
                          class: ["flex items-center justify-between gap-4 px-5 py-4", item.prioridade ? "bg-red-50" : ""]
                        }, [
                          createVNode("div", null, [
                            createVNode("div", { class: "font-semibold" }, [
                              createTextVNode(toDisplayString(item.quantidade) + "x " + toDisplayString(item.produto?.nome) + " ", 1),
                              item.prioridade ? (openBlock(), createBlock("span", {
                                key: 0,
                                class: "ml-2 rounded-full bg-amber-600 px-2 py-1 text-xs font-black text-white"
                              }, "A TERMINAR")) : createCommentVNode("", true)
                            ]),
                            createVNode("div", { class: "text-sm text-slate-500" }, toDisplayString(item.produto?.categoria?.nome) + " · " + toDisplayString(formatarPreco(item.preco_unitario)) + " cada", 1),
                            item.observacoes ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "mt-2 rounded-md bg-amber-100 px-3 py-2 text-sm font-bold text-amber-900"
                            }, " Info: " + toDisplayString(item.observacoes), 1)) : createCommentVNode("", true)
                          ]),
                          createVNode("div", { class: "flex items-center gap-2" }, [
                            createVNode("button", {
                              type: "button",
                              class: ["rounded-full px-3 py-2 text-xs font-black", item.prioridade ? "bg-amber-600 text-white" : "bg-slate-100 text-slate-700"],
                              onClick: ($event) => alternarUrgente(item)
                            }, "A terminar", 10, ["onClick"]),
                            !pedidoFechado.value ? (openBlock(), createBlock("button", {
                              key: 0,
                              type: "button",
                              class: "rounded-full bg-red-100 px-3 py-2 text-xs font-black text-red-700",
                              onClick: ($event) => anularItem(item)
                            }, toDisplayString(item.quantidade > 1 ? "-1" : "Anular"), 9, ["onClick"])) : createCommentVNode("", true),
                            mostrarEstadoItems.value ? (openBlock(), createBlock("div", {
                              key: 1,
                              class: ["rounded-full px-3 py-1 text-xs font-semibold", item.estado === "pronto" ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-800"]
                            }, toDisplayString(item.estado), 3)) : createCommentVNode("", true)
                          ])
                        ], 2);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 2,
                      class: "px-5 py-8 text-center text-sm text-slate-500"
                    }, "Ainda não há comida ou bebidas neste pedido."))
                  ]),
                  createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                    createVNode("div", { class: "mb-4 flex items-center justify-between gap-3" }, [
                      createVNode("h2", { class: "font-semibold" }, "Adicionar produtos"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => quantidade.value = $event,
                        type: "number",
                        min: "1",
                        class: "w-20 rounded-md border-slate-300 text-center"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [
                          vModelText,
                          quantidade.value,
                          void 0,
                          { number: true }
                        ]
                      ])
                    ]),
                    createVNode("label", { class: "mb-4 flex items-center gap-3 rounded-lg bg-amber-50 p-3 font-bold text-amber-800" }, [
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(itemForm).prioridade = $event,
                        type: "checkbox",
                        class: "rounded border-amber-300 text-amber-600"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelCheckbox, unref(itemForm).prioridade]
                      ]),
                      createTextVNode(" Marcar novo item como a terminar ")
                    ]),
                    createVNode("label", { class: "mb-4 block rounded-lg bg-amber-50 p-3" }, [
                      createVNode("span", { class: "mb-1 block text-sm font-black text-amber-900" }, "Informação para a secção"),
                      withDirectives(createVNode("textarea", {
                        "onUpdate:modelValue": ($event) => unref(itemForm).observacoes = $event,
                        rows: "2",
                        class: "w-full rounded-md border-amber-200 text-sm",
                        placeholder: "Ex.: sem picante, alergia, sem molho..."
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, unref(itemForm).observacoes]
                      ])
                    ]),
                    createVNode("div", { class: "mb-3 flex flex-wrap gap-2" }, [
                      (openBlock(), createBlock(Fragment, null, renderList(secoes, ([valor, label]) => {
                        return createVNode("button", {
                          key: valor,
                          type: "button",
                          class: ["rounded-md border px-3 py-2 text-sm font-semibold", secaoAtiva.value === valor ? "border-slate-900 bg-slate-900 text-white" : "border-slate-300 bg-white text-slate-700"],
                          onClick: ($event) => secaoAtiva.value = valor
                        }, toDisplayString(label), 11, ["onClick"]);
                      }), 64))
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => termo.value = $event,
                      class: "mb-4 w-full rounded-md border-slate-300",
                      placeholder: "Procurar produto"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, termo.value]
                    ]),
                    createVNode("div", { class: "grid max-h-[58vh] gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(produtosFiltrados.value, (produto) => {
                        return openBlock(), createBlock("button", {
                          key: produto.id,
                          type: "button",
                          class: "rounded-lg border border-slate-200 p-3 text-left hover:border-emerald-500 hover:bg-emerald-50",
                          onClick: ($event) => adicionarProduto(produto)
                        }, [
                          createVNode("div", { class: "font-semibold" }, toDisplayString(produto.nome), 1),
                          createVNode("div", { class: "mt-1 flex items-center justify-between text-sm text-slate-500" }, [
                            createVNode("span", null, toDisplayString(produto.categoria?.nome), 1),
                            createVNode("span", { class: "font-semibold text-slate-900" }, toDisplayString(formatarPreco(produto.preco)), 1)
                          ])
                        ], 8, ["onClick"]);
                      }), 128))
                    ])
                  ])
                ]),
                createVNode("aside", { class: "space-y-4" }, [
                  createVNode("form", {
                    class: "rounded-lg bg-white p-5 shadow-sm",
                    onSubmit: withModifiers(($event) => unref(estadoForm).patch(_ctx.route("pedidos.estado", __props.pedido.id)), ["prevent"])
                  }, [
                    createVNode("label", { class: "block" }, [
                      createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Estado do pedido"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(estadoForm).estado = $event,
                        class: "w-full rounded-md border-slate-300"
                      }, [
                        createVNode("option", null, "pendente"),
                        createVNode("option", null, "preparacao"),
                        createVNode("option", null, "entregue"),
                        createVNode("option", null, "cancelado")
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, unref(estadoForm).estado]
                      ])
                    ]),
                    createVNode("button", { class: "mt-3 rounded-md bg-emerald-700 px-4 py-2 text-white" }, "Mudar estado")
                  ], 40, ["onSubmit"]),
                  !pedidoFechado.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-3 xl:sticky xl:top-5 xl:z-10"
                  }, [
                    createVNode("form", {
                      ref_key: "caixaRef",
                      ref: caixaRef,
                      class: "rounded-lg bg-white p-5 shadow-sm",
                      onSubmit: withModifiers(fecharConta, ["prevent"])
                    }, [
                      createVNode("div", { class: "mb-4 rounded-lg bg-slate-900 p-4 text-white" }, [
                        createVNode("div", { class: "text-sm font-bold text-white/70" }, "Total a receber"),
                        createVNode("div", { class: "mt-1 text-5xl font-black" }, toDisplayString(formatarPreco(totalPedido.value)), 1)
                      ]),
                      createVNode("h2", { class: "mb-3 font-semibold" }, "Fechar conta"),
                      createVNode("div", { class: "grid gap-3" }, [
                        createVNode("label", { class: "block" }, [
                          createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Método de pagamento"),
                          withDirectives(createVNode("select", {
                            "onUpdate:modelValue": ($event) => unref(fecharContaForm).metodo_pagamento = $event,
                            class: "w-full rounded-md border-slate-300"
                          }, [
                            createVNode("option", { value: "dinheiro" }, "Dinheiro"),
                            createVNode("option", { value: "mbway" }, "MBWay"),
                            createVNode("option", { value: "multibanco" }, "Multibanco"),
                            createVNode("option", { value: "transferencia" }, "Transferência")
                          ], 8, ["onUpdate:modelValue"]), [
                            [vModelSelect, unref(fecharContaForm).metodo_pagamento]
                          ])
                        ]),
                        createVNode("label", { class: "block" }, [
                          createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Valor recebido"),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(fecharContaForm).valor_recebido = $event,
                            type: "number",
                            min: "0",
                            step: "0.01",
                            class: "w-full rounded-md border-slate-300",
                            placeholder: formatarPreco(totalPedido.value)
                          }, null, 8, ["onUpdate:modelValue", "placeholder"]), [
                            [
                              vModelText,
                              unref(fecharContaForm).valor_recebido,
                              void 0,
                              { number: true }
                            ]
                          ])
                        ]),
                        createVNode("div", { class: "grid grid-cols-2 gap-2" }, [
                          createVNode("button", {
                            type: "button",
                            class: "rounded-md bg-emerald-700 px-3 py-3 text-sm font-black text-white",
                            onClick: pagamentoCerto
                          }, "Valor certo"),
                          createVNode("button", {
                            type: "button",
                            class: "rounded-md border border-slate-300 px-3 py-3 text-sm font-black",
                            onClick: entregarTroco
                          }, "Entregar troco")
                        ]),
                        createVNode("label", { class: "block" }, [
                          createVNode("span", { class: "mb-1 block text-sm font-semibold text-slate-700" }, "Troco entregue"),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(fecharContaForm).troco = $event,
                            type: "number",
                            min: "0",
                            step: "0.01",
                            class: "w-full rounded-md border-slate-300"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [
                              vModelText,
                              unref(fecharContaForm).troco,
                              void 0,
                              { number: true }
                            ]
                          ])
                        ]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md bg-amber-100 px-3 py-3 text-sm font-black text-amber-900",
                          onClick: doarTroco
                        }, " Cliente deixa o troco como doação ")
                      ]),
                      createVNode("div", { class: "mt-3 rounded-md bg-slate-50 p-3 text-sm" }, [
                        createVNode("div", { class: "flex justify-between" }, [
                          createVNode("span", null, "Total"),
                          createVNode("strong", null, toDisplayString(formatarPreco(totalPedido.value)), 1)
                        ]),
                        createVNode("div", { class: "flex justify-between" }, [
                          createVNode("span", null, "Recebido"),
                          createVNode("strong", null, toDisplayString(formatarPreco(valorRecebido.value)), 1)
                        ]),
                        createVNode("div", { class: "flex justify-between" }, [
                          createVNode("span", null, "Troco possível"),
                          createVNode("strong", null, toDisplayString(formatarPreco(trocoADevolver.value)), 1)
                        ]),
                        createVNode("div", { class: "flex justify-between" }, [
                          createVNode("span", null, "Troco entregue"),
                          createVNode("strong", null, toDisplayString(formatarPreco(valorTroco.value)), 1)
                        ]),
                        createVNode("div", { class: "flex justify-between text-amber-800" }, [
                          createVNode("span", null, "Doação"),
                          createVNode("strong", null, toDisplayString(formatarPreco(doacaoEstimada.value)), 1)
                        ])
                      ]),
                      Object.keys(unref(fecharContaForm).errors).length ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700"
                      }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(unref(fecharContaForm).errors, (erro) => {
                          return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                        }), 128))
                      ])) : createCommentVNode("", true),
                      createVNode("button", {
                        type: "submit",
                        class: "mt-3 w-full rounded-lg bg-slate-900 p-4 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60",
                        disabled: unref(fecharContaForm).processing
                      }, toDisplayString(unref(fecharContaForm).processing ? "A fechar..." : "Receber e imprimir talão"), 9, ["disabled"])
                    ], 544),
                    createVNode("button", {
                      type: "button",
                      class: "w-full rounded-lg border border-red-300 bg-white p-4 text-sm font-bold text-red-700 shadow-sm hover:bg-red-50",
                      onClick: cancelarPedido
                    }, " Cancelar pedido ")
                  ])) : createCommentVNode("", true)
                ])
              ]))
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Pedidos/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
