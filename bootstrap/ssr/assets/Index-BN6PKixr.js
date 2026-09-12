import { ref, computed, mergeProps, withCtx, createTextVNode, unref, toDisplayString, createVNode, withModifiers, withDirectives, openBlock, createBlock, Fragment, renderList, vModelSelect, vModelCheckbox, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { useForm } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { P as PrimaryButton } from "./PrimaryButton-Dob6vmzN.js";
import { _ as _sfc_main$2 } from "./Modal-DmdYTugb.js";
import { _ as _sfc_main$3, a as _sfc_main$4, b as _sfc_main$5 } from "./TextInput-mzmZGPAG.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    impressoras: {
      type: Array,
      required: true
    },
    secoes: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const showModal = ref(false);
    const isEditing = ref(false);
    const selectedImpressora = ref(null);
    const impressoras = computed(() => props.impressoras ?? []);
    const secoes = computed(() => props.secoes ?? {});
    const form = useForm({
      nome: "",
      secao: "",
      host: "",
      porta: 9100,
      ativa: true
    });
    const modalTitle = computed(() => isEditing.value ? "Editar Impressora" : "Nova Impressora");
    function openCreateModal() {
      isEditing.value = false;
      form.reset();
      form.clearErrors();
      showModal.value = true;
    }
    function openEditModal(impressora) {
      isEditing.value = true;
      selectedImpressora.value = impressora;
      form.nome = impressora.nome;
      form.secao = impressora.secao || "";
      form.host = impressora.host;
      form.porta = impressora.porta;
      form.ativa = impressora.ativa;
      form.clearErrors();
      showModal.value = true;
    }
    function closeModal() {
      showModal.value = false;
      form.reset();
    }
    function submit() {
      if (isEditing.value) {
        form.patch(route("impressoras.update", selectedImpressora.value.id), {
          onSuccess: () => closeModal()
        });
      } else {
        form.post(route("impressoras.store"), {
          onSuccess: () => closeModal()
        });
      }
    }
    function deleteImpressora(impressora) {
      if (confirm(`Tem certeza que deseja remover a impressora "${impressora.nome}"?`)) {
        useForm({}).delete(route("impressoras.destroy", impressora.id));
      }
    }
    const getSectionName = (secao) => {
      if (!secao) return "—";
      return secoes.value[secao] || secao;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({ title: "Impressoras" }, _attrs), {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h2 class="text-xl font-semibold leading-tight text-slate-800"${_scopeId}>Impressoras</h2>`);
          } else {
            return [
              createVNode("h2", { class: "text-xl font-semibold leading-tight text-slate-800" }, "Impressoras")
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8"${_scopeId}><div class="mb-6 flex items-center justify-between"${_scopeId}><p class="text-sm text-slate-600"${_scopeId}> Total de impressoras: <span class="font-semibold"${_scopeId}>${ssrInterpolate(impressoras.value.length)}</span></p>`);
            _push2(ssrRenderComponent(PrimaryButton, { onClick: openCreateModal }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` + Nova Impressora `);
                } else {
                  return [
                    createTextVNode(" + Nova Impressora ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="overflow-hidden rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full"${_scopeId}><thead class="border-b border-slate-200 bg-slate-50"${_scopeId}><tr${_scopeId}><th class="px-6 py-4 text-left text-sm font-semibold text-slate-900"${_scopeId}>Nome</th><th class="px-6 py-4 text-left text-sm font-semibold text-slate-900"${_scopeId}>Secção</th><th class="px-6 py-4 text-left text-sm font-semibold text-slate-900"${_scopeId}>Host:Porta</th><th class="px-6 py-4 text-center text-sm font-semibold text-slate-900"${_scopeId}>Status</th><th class="px-6 py-4 text-right text-sm font-semibold text-slate-900"${_scopeId}>Ações</th></tr></thead><tbody class="divide-y divide-slate-200"${_scopeId}><!--[-->`);
            ssrRenderList(impressoras.value, (impressora) => {
              _push2(`<tr class="hover:bg-slate-50"${_scopeId}><td class="px-6 py-4 font-medium text-slate-900"${_scopeId}>${ssrInterpolate(impressora.nome)}</td><td class="px-6 py-4 text-slate-600"${_scopeId}>${ssrInterpolate(getSectionName(impressora.secao))}</td><td class="px-6 py-4 font-mono text-sm text-slate-600"${_scopeId}>${ssrInterpolate(impressora.host)}:${ssrInterpolate(impressora.porta)}</td><td class="px-6 py-4 text-center"${_scopeId}>`);
              if (impressora.ativa) {
                _push2(`<span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800"${_scopeId}> ● Ativa </span>`);
              } else {
                _push2(`<span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"${_scopeId}> ● Inativa </span>`);
              }
              _push2(`</td><td class="px-6 py-4 text-right"${_scopeId}><button class="mr-2 text-sm text-blue-600 hover:text-blue-900 hover:underline"${_scopeId}> Editar </button><button class="text-sm text-red-600 hover:text-red-900 hover:underline"${_scopeId}> Remover </button></td></tr>`);
            });
            _push2(`<!--]--></tbody></table>`);
            if (impressoras.value.length === 0) {
              _push2(`<div class="px-6 py-12 text-center"${_scopeId}><p class="text-slate-500"${_scopeId}>Nenhuma impressora configurada ainda.</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm"${_scopeId}><div class="flex flex-wrap items-start justify-between gap-4"${_scopeId}><div class="min-w-0"${_scopeId}><h3 class="font-semibold text-slate-900"${_scopeId}>Agente local de impressao</h3><p class="mt-1 text-sm text-slate-600"${_scopeId}> Corre no Raspberry Pi (ou outro PC) dentro da rede das impressoras. Liga a API do servidor e imprime os trabalhos pendentes via ESC/POS (porta TCP 9100). </p><ol class="mt-3 space-y-1 text-sm text-slate-600 list-decimal list-inside"${_scopeId}><li${_scopeId}>Instala Node.js no Raspberry Pi</li><li${_scopeId}>Extrai o ZIP para <code class="rounded bg-slate-100 px-1"${_scopeId}>/opt/ardc-print-agent</code></li><li${_scopeId}>Copia <code class="rounded bg-slate-100 px-1"${_scopeId}>.env.example</code> para <code class="rounded bg-slate-100 px-1"${_scopeId}>.env</code> e define <code class="rounded bg-slate-100 px-1"${_scopeId}>PRINT_AGENT_TOKEN</code></li><li${_scopeId}>Copia <code class="rounded bg-slate-100 px-1"${_scopeId}>ardc-print-agent.service</code> para <code class="rounded bg-slate-100 px-1"${_scopeId}>/etc/systemd/system/</code> e corre <code class="rounded bg-slate-100 px-1"${_scopeId}>systemctl enable --now ardc-print-agent</code></li></ol></div><a${ssrRenderAttr("href", _ctx.route("impressoras.download-agente"))} class="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"${_scopeId}> Download agente (.zip) </a></div></div>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              show: showModal.value,
              onClose: closeModal
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="p-6"${_scopeId2}><h3 class="mb-4 text-lg font-semibold text-slate-900"${_scopeId2}>${ssrInterpolate(modalTitle.value)}</h3><form class="space-y-4"${_scopeId2}><div${_scopeId2}>`);
                  _push3(ssrRenderComponent(_sfc_main$3, {
                    for: "nome",
                    value: "Nome *"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$4, {
                    id: "nome",
                    modelValue: unref(form).nome,
                    "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                    type: "text",
                    class: "mt-1 block w-full",
                    placeholder: "ex: Impressora Bar"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$5, {
                    class: "mt-2",
                    message: unref(form).errors.nome
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><div${_scopeId2}>`);
                  _push3(ssrRenderComponent(_sfc_main$3, {
                    for: "secao",
                    value: "Secção"
                  }, null, _parent3, _scopeId2));
                  _push3(`<select id="secao" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500"${_scopeId2}><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(form).secao) ? ssrLooseContain(unref(form).secao, "") : ssrLooseEqual(unref(form).secao, "")) ? " selected" : ""}${_scopeId2}>Sem secção</option><!--[-->`);
                  ssrRenderList(secoes.value, (label, value) => {
                    _push3(`<option${ssrRenderAttr("value", value)}${ssrIncludeBooleanAttr(Array.isArray(unref(form).secao) ? ssrLooseContain(unref(form).secao, value) : ssrLooseEqual(unref(form).secao, value)) ? " selected" : ""}${_scopeId2}>${ssrInterpolate(label)}</option>`);
                  });
                  _push3(`<!--]--></select>`);
                  _push3(ssrRenderComponent(_sfc_main$5, {
                    class: "mt-2",
                    message: unref(form).errors.secao
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><div${_scopeId2}>`);
                  _push3(ssrRenderComponent(_sfc_main$3, {
                    for: "host",
                    value: "Host/IP *"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$4, {
                    id: "host",
                    modelValue: unref(form).host,
                    "onUpdate:modelValue": ($event) => unref(form).host = $event,
                    type: "text",
                    class: "mt-1 block w-full",
                    placeholder: "ex: 192.168.1.100"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$5, {
                    class: "mt-2",
                    message: unref(form).errors.host
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><div${_scopeId2}>`);
                  _push3(ssrRenderComponent(_sfc_main$3, {
                    for: "porta",
                    value: "Porta *"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$4, {
                    id: "porta",
                    modelValue: unref(form).porta,
                    "onUpdate:modelValue": ($event) => unref(form).porta = $event,
                    modelModifiers: { number: true },
                    type: "number",
                    class: "mt-1 block w-full",
                    placeholder: "ex: 9100",
                    min: "1",
                    max: "65535"
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(_sfc_main$5, {
                    class: "mt-2",
                    message: unref(form).errors.porta
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><div class="flex items-center gap-2"${_scopeId2}><input id="ativa"${ssrIncludeBooleanAttr(Array.isArray(unref(form).ativa) ? ssrLooseContain(unref(form).ativa, null) : unref(form).ativa) ? " checked" : ""} type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"${_scopeId2}><label for="ativa" class="text-sm font-medium text-slate-700"${_scopeId2}>Impressora Ativa</label></div><div class="flex justify-end gap-3 pt-4"${_scopeId2}><button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"${_scopeId2}> Cancelar </button>`);
                  _push3(ssrRenderComponent(PrimaryButton, {
                    type: "submit",
                    disabled: unref(form).processing
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`${ssrInterpolate(isEditing.value ? "Atualizar" : "Criar")}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(isEditing.value ? "Atualizar" : "Criar"), 1)
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`</div></form></div>`);
                } else {
                  return [
                    createVNode("div", { class: "p-6" }, [
                      createVNode("h3", { class: "mb-4 text-lg font-semibold text-slate-900" }, toDisplayString(modalTitle.value), 1),
                      createVNode("form", {
                        onSubmit: withModifiers(submit, ["prevent"]),
                        class: "space-y-4"
                      }, [
                        createVNode("div", null, [
                          createVNode(_sfc_main$3, {
                            for: "nome",
                            value: "Nome *"
                          }),
                          createVNode(_sfc_main$4, {
                            id: "nome",
                            modelValue: unref(form).nome,
                            "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                            type: "text",
                            class: "mt-1 block w-full",
                            placeholder: "ex: Impressora Bar"
                          }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                          createVNode(_sfc_main$5, {
                            class: "mt-2",
                            message: unref(form).errors.nome
                          }, null, 8, ["message"])
                        ]),
                        createVNode("div", null, [
                          createVNode(_sfc_main$3, {
                            for: "secao",
                            value: "Secção"
                          }),
                          withDirectives(createVNode("select", {
                            id: "secao",
                            "onUpdate:modelValue": ($event) => unref(form).secao = $event,
                            class: "mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                          }, [
                            createVNode("option", { value: "" }, "Sem secção"),
                            (openBlock(true), createBlock(Fragment, null, renderList(secoes.value, (label, value) => {
                              return openBlock(), createBlock("option", {
                                key: value,
                                value
                              }, toDisplayString(label), 9, ["value"]);
                            }), 128))
                          ], 8, ["onUpdate:modelValue"]), [
                            [vModelSelect, unref(form).secao]
                          ]),
                          createVNode(_sfc_main$5, {
                            class: "mt-2",
                            message: unref(form).errors.secao
                          }, null, 8, ["message"])
                        ]),
                        createVNode("div", null, [
                          createVNode(_sfc_main$3, {
                            for: "host",
                            value: "Host/IP *"
                          }),
                          createVNode(_sfc_main$4, {
                            id: "host",
                            modelValue: unref(form).host,
                            "onUpdate:modelValue": ($event) => unref(form).host = $event,
                            type: "text",
                            class: "mt-1 block w-full",
                            placeholder: "ex: 192.168.1.100"
                          }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                          createVNode(_sfc_main$5, {
                            class: "mt-2",
                            message: unref(form).errors.host
                          }, null, 8, ["message"])
                        ]),
                        createVNode("div", null, [
                          createVNode(_sfc_main$3, {
                            for: "porta",
                            value: "Porta *"
                          }),
                          createVNode(_sfc_main$4, {
                            id: "porta",
                            modelValue: unref(form).porta,
                            "onUpdate:modelValue": ($event) => unref(form).porta = $event,
                            modelModifiers: { number: true },
                            type: "number",
                            class: "mt-1 block w-full",
                            placeholder: "ex: 9100",
                            min: "1",
                            max: "65535"
                          }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                          createVNode(_sfc_main$5, {
                            class: "mt-2",
                            message: unref(form).errors.porta
                          }, null, 8, ["message"])
                        ]),
                        createVNode("div", { class: "flex items-center gap-2" }, [
                          withDirectives(createVNode("input", {
                            id: "ativa",
                            "onUpdate:modelValue": ($event) => unref(form).ativa = $event,
                            type: "checkbox",
                            class: "h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelCheckbox, unref(form).ativa]
                          ]),
                          createVNode("label", {
                            for: "ativa",
                            class: "text-sm font-medium text-slate-700"
                          }, "Impressora Ativa")
                        ]),
                        createVNode("div", { class: "flex justify-end gap-3 pt-4" }, [
                          createVNode("button", {
                            type: "button",
                            onClick: closeModal,
                            class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                          }, " Cancelar "),
                          createVNode(PrimaryButton, {
                            type: "submit",
                            disabled: unref(form).processing
                          }, {
                            default: withCtx(() => [
                              createTextVNode(toDisplayString(isEditing.value ? "Atualizar" : "Criar"), 1)
                            ]),
                            _: 1
                          }, 8, ["disabled"])
                        ])
                      ], 32)
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8" }, [
                createVNode("div", { class: "mb-6 flex items-center justify-between" }, [
                  createVNode("p", { class: "text-sm text-slate-600" }, [
                    createTextVNode(" Total de impressoras: "),
                    createVNode("span", { class: "font-semibold" }, toDisplayString(impressoras.value.length), 1)
                  ]),
                  createVNode(PrimaryButton, { onClick: openCreateModal }, {
                    default: withCtx(() => [
                      createTextVNode(" + Nova Impressora ")
                    ]),
                    _: 1
                  })
                ]),
                createVNode("div", { class: "overflow-hidden rounded-lg bg-white shadow-sm" }, [
                  createVNode("table", { class: "w-full" }, [
                    createVNode("thead", { class: "border-b border-slate-200 bg-slate-50" }, [
                      createVNode("tr", null, [
                        createVNode("th", { class: "px-6 py-4 text-left text-sm font-semibold text-slate-900" }, "Nome"),
                        createVNode("th", { class: "px-6 py-4 text-left text-sm font-semibold text-slate-900" }, "Secção"),
                        createVNode("th", { class: "px-6 py-4 text-left text-sm font-semibold text-slate-900" }, "Host:Porta"),
                        createVNode("th", { class: "px-6 py-4 text-center text-sm font-semibold text-slate-900" }, "Status"),
                        createVNode("th", { class: "px-6 py-4 text-right text-sm font-semibold text-slate-900" }, "Ações")
                      ])
                    ]),
                    createVNode("tbody", { class: "divide-y divide-slate-200" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(impressoras.value, (impressora) => {
                        return openBlock(), createBlock("tr", {
                          key: impressora.id,
                          class: "hover:bg-slate-50"
                        }, [
                          createVNode("td", { class: "px-6 py-4 font-medium text-slate-900" }, toDisplayString(impressora.nome), 1),
                          createVNode("td", { class: "px-6 py-4 text-slate-600" }, toDisplayString(getSectionName(impressora.secao)), 1),
                          createVNode("td", { class: "px-6 py-4 font-mono text-sm text-slate-600" }, toDisplayString(impressora.host) + ":" + toDisplayString(impressora.porta), 1),
                          createVNode("td", { class: "px-6 py-4 text-center" }, [
                            impressora.ativa ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: "inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800"
                            }, " ● Ativa ")) : (openBlock(), createBlock("span", {
                              key: 1,
                              class: "inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                            }, " ● Inativa "))
                          ]),
                          createVNode("td", { class: "px-6 py-4 text-right" }, [
                            createVNode("button", {
                              onClick: ($event) => openEditModal(impressora),
                              class: "mr-2 text-sm text-blue-600 hover:text-blue-900 hover:underline"
                            }, " Editar ", 8, ["onClick"]),
                            createVNode("button", {
                              onClick: ($event) => deleteImpressora(impressora),
                              class: "text-sm text-red-600 hover:text-red-900 hover:underline"
                            }, " Remover ", 8, ["onClick"])
                          ])
                        ]);
                      }), 128))
                    ])
                  ]),
                  impressoras.value.length === 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "px-6 py-12 text-center"
                  }, [
                    createVNode("p", { class: "text-slate-500" }, "Nenhuma impressora configurada ainda.")
                  ])) : createCommentVNode("", true)
                ])
              ]),
              createVNode("div", { class: "mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm" }, [
                createVNode("div", { class: "flex flex-wrap items-start justify-between gap-4" }, [
                  createVNode("div", { class: "min-w-0" }, [
                    createVNode("h3", { class: "font-semibold text-slate-900" }, "Agente local de impressao"),
                    createVNode("p", { class: "mt-1 text-sm text-slate-600" }, " Corre no Raspberry Pi (ou outro PC) dentro da rede das impressoras. Liga a API do servidor e imprime os trabalhos pendentes via ESC/POS (porta TCP 9100). "),
                    createVNode("ol", { class: "mt-3 space-y-1 text-sm text-slate-600 list-decimal list-inside" }, [
                      createVNode("li", null, "Instala Node.js no Raspberry Pi"),
                      createVNode("li", null, [
                        createTextVNode("Extrai o ZIP para "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, "/opt/ardc-print-agent")
                      ]),
                      createVNode("li", null, [
                        createTextVNode("Copia "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, ".env.example"),
                        createTextVNode(" para "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, ".env"),
                        createTextVNode(" e define "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, "PRINT_AGENT_TOKEN")
                      ]),
                      createVNode("li", null, [
                        createTextVNode("Copia "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, "ardc-print-agent.service"),
                        createTextVNode(" para "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, "/etc/systemd/system/"),
                        createTextVNode(" e corre "),
                        createVNode("code", { class: "rounded bg-slate-100 px-1" }, "systemctl enable --now ardc-print-agent")
                      ])
                    ])
                  ]),
                  createVNode("a", {
                    href: _ctx.route("impressoras.download-agente"),
                    class: "shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                  }, " Download agente (.zip) ", 8, ["href"])
                ])
              ]),
              createVNode(_sfc_main$2, {
                show: showModal.value,
                onClose: closeModal
              }, {
                default: withCtx(() => [
                  createVNode("div", { class: "p-6" }, [
                    createVNode("h3", { class: "mb-4 text-lg font-semibold text-slate-900" }, toDisplayString(modalTitle.value), 1),
                    createVNode("form", {
                      onSubmit: withModifiers(submit, ["prevent"]),
                      class: "space-y-4"
                    }, [
                      createVNode("div", null, [
                        createVNode(_sfc_main$3, {
                          for: "nome",
                          value: "Nome *"
                        }),
                        createVNode(_sfc_main$4, {
                          id: "nome",
                          modelValue: unref(form).nome,
                          "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                          type: "text",
                          class: "mt-1 block w-full",
                          placeholder: "ex: Impressora Bar"
                        }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                        createVNode(_sfc_main$5, {
                          class: "mt-2",
                          message: unref(form).errors.nome
                        }, null, 8, ["message"])
                      ]),
                      createVNode("div", null, [
                        createVNode(_sfc_main$3, {
                          for: "secao",
                          value: "Secção"
                        }),
                        withDirectives(createVNode("select", {
                          id: "secao",
                          "onUpdate:modelValue": ($event) => unref(form).secao = $event,
                          class: "mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500"
                        }, [
                          createVNode("option", { value: "" }, "Sem secção"),
                          (openBlock(true), createBlock(Fragment, null, renderList(secoes.value, (label, value) => {
                            return openBlock(), createBlock("option", {
                              key: value,
                              value
                            }, toDisplayString(label), 9, ["value"]);
                          }), 128))
                        ], 8, ["onUpdate:modelValue"]), [
                          [vModelSelect, unref(form).secao]
                        ]),
                        createVNode(_sfc_main$5, {
                          class: "mt-2",
                          message: unref(form).errors.secao
                        }, null, 8, ["message"])
                      ]),
                      createVNode("div", null, [
                        createVNode(_sfc_main$3, {
                          for: "host",
                          value: "Host/IP *"
                        }),
                        createVNode(_sfc_main$4, {
                          id: "host",
                          modelValue: unref(form).host,
                          "onUpdate:modelValue": ($event) => unref(form).host = $event,
                          type: "text",
                          class: "mt-1 block w-full",
                          placeholder: "ex: 192.168.1.100"
                        }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                        createVNode(_sfc_main$5, {
                          class: "mt-2",
                          message: unref(form).errors.host
                        }, null, 8, ["message"])
                      ]),
                      createVNode("div", null, [
                        createVNode(_sfc_main$3, {
                          for: "porta",
                          value: "Porta *"
                        }),
                        createVNode(_sfc_main$4, {
                          id: "porta",
                          modelValue: unref(form).porta,
                          "onUpdate:modelValue": ($event) => unref(form).porta = $event,
                          modelModifiers: { number: true },
                          type: "number",
                          class: "mt-1 block w-full",
                          placeholder: "ex: 9100",
                          min: "1",
                          max: "65535"
                        }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                        createVNode(_sfc_main$5, {
                          class: "mt-2",
                          message: unref(form).errors.porta
                        }, null, 8, ["message"])
                      ]),
                      createVNode("div", { class: "flex items-center gap-2" }, [
                        withDirectives(createVNode("input", {
                          id: "ativa",
                          "onUpdate:modelValue": ($event) => unref(form).ativa = $event,
                          type: "checkbox",
                          class: "h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelCheckbox, unref(form).ativa]
                        ]),
                        createVNode("label", {
                          for: "ativa",
                          class: "text-sm font-medium text-slate-700"
                        }, "Impressora Ativa")
                      ]),
                      createVNode("div", { class: "flex justify-end gap-3 pt-4" }, [
                        createVNode("button", {
                          type: "button",
                          onClick: closeModal,
                          class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        }, " Cancelar "),
                        createVNode(PrimaryButton, {
                          type: "submit",
                          disabled: unref(form).processing
                        }, {
                          default: withCtx(() => [
                            createTextVNode(toDisplayString(isEditing.value ? "Atualizar" : "Criar"), 1)
                          ]),
                          _: 1
                        }, 8, ["disabled"])
                      ])
                    ], 32)
                  ])
                ]),
                _: 1
              }, 8, ["show"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Impressoras/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
