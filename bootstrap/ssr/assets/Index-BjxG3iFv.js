import { ref, withCtx, unref, createVNode, withModifiers, withDirectives, vModelText, openBlock, createBlock, Fragment, renderList, toDisplayString, vModelSelect, createCommentVNode, vModelCheckbox, createTextVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrInterpolate, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { users: Array, roles: Array, posTerminais: Array },
  setup(__props) {
    const props = __props;
    const editingId = ref(null);
    const editingPosId = ref(null);
    const createForm = useForm({ name: "", email: "", password: "", role: props.roles?.[0] ?? "" });
    const editForm = useForm({ name: "", email: "", password: "", role: "" });
    const posForm = useForm({ nome: "", pin: "", localizacao: "", tipo: "bar", ativo: true });
    const editPosForm = useForm({ nome: "", pin: "", localizacao: "", tipo: "bar", ativo: true });
    const tiposPos = [
      ["bar", "Bar"],
      ["cafe", "Cafe"],
      ["restaurante", "Restaurante"],
      ["reservas", "Reservas"],
      ["cotas", "Cotas"]
    ];
    const criar = () => {
      createForm.post(route("users.store"), {
        preserveScroll: true,
        onSuccess: () => createForm.reset("name", "email", "password")
      });
    };
    const editar = (user) => {
      editingId.value = user.id;
      editForm.name = user.name;
      editForm.email = user.email;
      editForm.password = "";
      editForm.role = user.roles?.[0]?.name ?? props.roles?.[0] ?? "";
    };
    const cancelar = () => {
      editingId.value = null;
      editForm.reset();
    };
    const guardar = (user) => {
      editForm.patch(route("users.update", user.id), {
        preserveScroll: true,
        onSuccess: cancelar
      });
    };
    const apagar = (user) => {
      if (confirm(`Apagar o utilizador ${user.name}?`)) {
        useForm({}).delete(route("users.destroy", user.id), { preserveScroll: true });
      }
    };
    const criarPos = () => {
      posForm.post(route("users.pos.store"), {
        preserveScroll: true,
        onSuccess: () => posForm.reset("nome", "pin", "localizacao")
      });
    };
    const editarPos = (terminal) => {
      editingPosId.value = terminal.id;
      editPosForm.nome = terminal.nome;
      editPosForm.pin = "";
      editPosForm.localizacao = terminal.localizacao ?? "";
      editPosForm.tipo = terminal.tipo;
      editPosForm.ativo = Boolean(terminal.ativo);
    };
    const cancelarPos = () => {
      editingPosId.value = null;
      editPosForm.reset();
    };
    const guardarPos = (terminal) => {
      editPosForm.patch(route("users.pos.update", terminal.id), {
        preserveScroll: true,
        onSuccess: cancelarPos
      });
    };
    const apagarPos = (terminal) => {
      if (confirm(`Apagar o acesso POS ${terminal.nome}?`)) {
        useForm({}).delete(route("users.pos.destroy", terminal.id), { preserveScroll: true });
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6"${_scopeId}><h1 class="text-2xl font-bold"${_scopeId}>Utilizadores</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Criar acessos e gerir permissões de cada pessoa.</p></div><form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_1fr_180px_180px_auto]"${_scopeId}><input${ssrRenderAttr("value", unref(createForm).name)} class="rounded-md border-slate-300" placeholder="Nome"${_scopeId}><input${ssrRenderAttr("value", unref(createForm).email)} type="email" class="rounded-md border-slate-300" placeholder="Email"${_scopeId}><input${ssrRenderAttr("value", unref(createForm).password)} type="password" class="rounded-md border-slate-300" placeholder="Password"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><!--[-->`);
            ssrRenderList(__props.roles, (role) => {
              _push2(`<option${ssrRenderAttr("value", role)}${ssrIncludeBooleanAttr(Array.isArray(unref(createForm).role) ? ssrLooseContain(unref(createForm).role, role) : ssrLooseEqual(unref(createForm).role, role)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(role)}</option>`);
            });
            _push2(`<!--]--></select><button class="rounded-md bg-slate-900 px-4 py-2 font-bold text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(createForm).processing) ? " disabled" : ""}${_scopeId}>Criar</button>`);
            if (Object.keys(unref(createForm).errors).length) {
              _push2(`<div class="text-sm font-bold text-red-700 md:col-span-5"${_scopeId}><!--[-->`);
              ssrRenderList(unref(createForm).errors, (erro) => {
                _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</form><div class="overflow-hidden rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Nome</th><th${_scopeId}>Email</th><th${_scopeId}>Perfil</th><th class="pr-3 text-right"${_scopeId}>Ações</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.users, (user) => {
              _push2(`<tr class="border-t align-top"${_scopeId}>`);
              if (editingId.value === user.id) {
                _push2(`<!--[--><td class="p-3"${_scopeId}><input${ssrRenderAttr("value", unref(editForm).name)} class="w-full rounded-md border-slate-300"${_scopeId}></td><td class="py-3"${_scopeId}><input${ssrRenderAttr("value", unref(editForm).email)} type="email" class="w-full rounded-md border-slate-300"${_scopeId}></td><td class="py-3"${_scopeId}><select class="w-full rounded-md border-slate-300"${_scopeId}><!--[-->`);
                ssrRenderList(__props.roles, (role) => {
                  _push2(`<option${ssrRenderAttr("value", role)}${ssrIncludeBooleanAttr(Array.isArray(unref(editForm).role) ? ssrLooseContain(unref(editForm).role, role) : ssrLooseEqual(unref(editForm).role, role)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(role)}</option>`);
                });
                _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(editForm).password)} type="password" class="mt-2 w-full rounded-md border-slate-300" placeholder="Nova password opcional"${_scopeId}></td><td class="space-x-2 p-3 text-right"${_scopeId}><button type="button" class="rounded-md border px-3 py-2 font-bold"${_scopeId}>Cancelar</button><button type="button" class="rounded-md bg-emerald-700 px-3 py-2 font-bold text-white"${_scopeId}>Guardar</button></td><!--]-->`);
              } else {
                _push2(`<!--[--><td class="p-3 font-semibold"${_scopeId}>${ssrInterpolate(user.name)}</td><td class="py-3 text-slate-600"${_scopeId}>${ssrInterpolate(user.email)}</td><td class="py-3"${_scopeId}><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black"${_scopeId}>${ssrInterpolate(user.roles?.[0]?.name ?? "sem perfil")}</span></td><td class="space-x-2 p-3 text-right"${_scopeId}><button type="button" class="rounded-md border px-3 py-2 font-bold"${_scopeId}>Editar</button><button type="button" class="rounded-md bg-red-600 px-3 py-2 font-bold text-white"${_scopeId}>Apagar</button></td><!--]-->`);
              }
              _push2(`</tr>`);
            });
            _push2(`<!--]--></tbody></table></div><div class="mb-6 mt-10"${_scopeId}><h2 class="text-2xl font-bold"${_scopeId}>Acessos POS</h2><p class="mt-1 text-sm text-slate-500"${_scopeId}>Gerir terminais, localizações e PINs usados no login do POS.</p></div><form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_1fr_160px_140px_120px_auto]"${_scopeId}><input${ssrRenderAttr("value", unref(posForm).nome)} class="rounded-md border-slate-300" placeholder="Nome do terminal"${_scopeId}><input${ssrRenderAttr("value", unref(posForm).localizacao)} class="rounded-md border-slate-300" placeholder="Localização/ponto"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><!--[-->`);
            ssrRenderList(tiposPos, ([valor, label]) => {
              _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(posForm).tipo) ? ssrLooseContain(unref(posForm).tipo, valor) : ssrLooseEqual(unref(posForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
            });
            _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(posForm).pin)} type="password" class="rounded-md border-slate-300" placeholder="PIN"${_scopeId}><label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(posForm).ativo) ? ssrLooseContain(unref(posForm).ativo, null) : unref(posForm).ativo) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Ativo </label><button class="rounded-md bg-slate-900 px-4 py-2 font-bold text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(posForm).processing) ? " disabled" : ""}${_scopeId}>Criar POS</button>`);
            if (Object.keys(unref(posForm).errors).length) {
              _push2(`<div class="text-sm font-bold text-red-700 md:col-span-6"${_scopeId}><!--[-->`);
              ssrRenderList(unref(posForm).errors, (erro) => {
                _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</form><div class="overflow-hidden rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Terminal</th><th${_scopeId}>Localização</th><th${_scopeId}>Tipo</th><th${_scopeId}>Estado</th><th class="pr-3 text-right"${_scopeId}>Ações</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.posTerminais, (terminal) => {
              _push2(`<tr class="border-t align-top"${_scopeId}>`);
              if (editingPosId.value === terminal.id) {
                _push2(`<!--[--><td class="p-3"${_scopeId}><input${ssrRenderAttr("value", unref(editPosForm).nome)} class="w-full rounded-md border-slate-300"${_scopeId}></td><td class="py-3"${_scopeId}><input${ssrRenderAttr("value", unref(editPosForm).localizacao)} class="w-full rounded-md border-slate-300"${_scopeId}></td><td class="py-3"${_scopeId}><select class="w-full rounded-md border-slate-300"${_scopeId}><!--[-->`);
                ssrRenderList(tiposPos, ([valor, label]) => {
                  _push2(`<option${ssrRenderAttr("value", valor)}${ssrIncludeBooleanAttr(Array.isArray(unref(editPosForm).tipo) ? ssrLooseContain(unref(editPosForm).tipo, valor) : ssrLooseEqual(unref(editPosForm).tipo, valor)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(label)}</option>`);
                });
                _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(editPosForm).pin)} type="password" class="mt-2 w-full rounded-md border-slate-300" placeholder="Novo PIN opcional"${_scopeId}></td><td class="py-3"${_scopeId}><label class="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(editPosForm).ativo) ? ssrLooseContain(unref(editPosForm).ativo, null) : unref(editPosForm).ativo) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Ativo </label></td><td class="space-x-2 p-3 text-right"${_scopeId}><button type="button" class="rounded-md border px-3 py-2 font-bold"${_scopeId}>Cancelar</button><button type="button" class="rounded-md bg-emerald-700 px-3 py-2 font-bold text-white"${_scopeId}>Guardar</button></td><!--]-->`);
              } else {
                _push2(`<!--[--><td class="p-3 font-semibold"${_scopeId}>${ssrInterpolate(terminal.nome)}</td><td class="py-3 text-slate-600"${_scopeId}>${ssrInterpolate(terminal.localizacao ?? "-")}</td><td class="py-3"${_scopeId}><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black"${_scopeId}>${ssrInterpolate(terminal.tipo)}</span></td><td class="py-3"${_scopeId}><span class="${ssrRenderClass([terminal.ativo ? "bg-emerald-100 text-emerald-800" : "bg-slate-200 text-slate-600", "rounded-full px-3 py-1 text-xs font-black"])}"${_scopeId}>${ssrInterpolate(terminal.ativo ? "ativo" : "inativo")}</span></td><td class="space-x-2 p-3 text-right"${_scopeId}><button type="button" class="rounded-md border px-3 py-2 font-bold"${_scopeId}>Editar</button><button type="button" class="rounded-md bg-red-600 px-3 py-2 font-bold text-white"${_scopeId}>Apagar</button></td><!--]-->`);
              }
              _push2(`</tr>`);
            });
            _push2(`<!--]--></tbody></table></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6" }, [
                createVNode("h1", { class: "text-2xl font-bold" }, "Utilizadores"),
                createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Criar acessos e gerir permissões de cada pessoa.")
              ]),
              createVNode("form", {
                class: "mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_1fr_180px_180px_auto]",
                onSubmit: withModifiers(criar, ["prevent"])
              }, [
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(createForm).name = $event,
                  class: "rounded-md border-slate-300",
                  placeholder: "Nome"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(createForm).name]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(createForm).email = $event,
                  type: "email",
                  class: "rounded-md border-slate-300",
                  placeholder: "Email"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(createForm).email]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(createForm).password = $event,
                  type: "password",
                  class: "rounded-md border-slate-300",
                  placeholder: "Password"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(createForm).password]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(createForm).role = $event,
                  class: "rounded-md border-slate-300"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.roles, (role) => {
                    return openBlock(), createBlock("option", {
                      key: role,
                      value: role
                    }, toDisplayString(role), 9, ["value"]);
                  }), 128))
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(createForm).role]
                ]),
                createVNode("button", {
                  class: "rounded-md bg-slate-900 px-4 py-2 font-bold text-white disabled:opacity-50",
                  disabled: unref(createForm).processing
                }, "Criar", 8, ["disabled"]),
                Object.keys(unref(createForm).errors).length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-sm font-bold text-red-700 md:col-span-5"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(createForm).errors, (erro) => {
                    return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                  }), 128))
                ])) : createCommentVNode("", true)
              ], 32),
              createVNode("div", { class: "overflow-hidden rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full text-left text-sm" }, [
                  createVNode("thead", { class: "bg-slate-50" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "p-3" }, "Nome"),
                      createVNode("th", null, "Email"),
                      createVNode("th", null, "Perfil"),
                      createVNode("th", { class: "pr-3 text-right" }, "Ações")
                    ])
                  ]),
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.users, (user) => {
                      return openBlock(), createBlock("tr", {
                        key: user.id,
                        class: "border-t align-top"
                      }, [
                        editingId.value === user.id ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                          createVNode("td", { class: "p-3" }, [
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editForm).name = $event,
                              class: "w-full rounded-md border-slate-300"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editForm).name]
                            ])
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editForm).email = $event,
                              type: "email",
                              class: "w-full rounded-md border-slate-300"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editForm).email]
                            ])
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            withDirectives(createVNode("select", {
                              "onUpdate:modelValue": ($event) => unref(editForm).role = $event,
                              class: "w-full rounded-md border-slate-300"
                            }, [
                              (openBlock(true), createBlock(Fragment, null, renderList(__props.roles, (role) => {
                                return openBlock(), createBlock("option", {
                                  key: role,
                                  value: role
                                }, toDisplayString(role), 9, ["value"]);
                              }), 128))
                            ], 8, ["onUpdate:modelValue"]), [
                              [vModelSelect, unref(editForm).role]
                            ]),
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editForm).password = $event,
                              type: "password",
                              class: "mt-2 w-full rounded-md border-slate-300",
                              placeholder: "Nova password opcional"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editForm).password]
                            ])
                          ]),
                          createVNode("td", { class: "space-x-2 p-3 text-right" }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border px-3 py-2 font-bold",
                              onClick: cancelar
                            }, "Cancelar"),
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md bg-emerald-700 px-3 py-2 font-bold text-white",
                              onClick: ($event) => guardar(user)
                            }, "Guardar", 8, ["onClick"])
                          ])
                        ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                          createVNode("td", { class: "p-3 font-semibold" }, toDisplayString(user.name), 1),
                          createVNode("td", { class: "py-3 text-slate-600" }, toDisplayString(user.email), 1),
                          createVNode("td", { class: "py-3" }, [
                            createVNode("span", { class: "rounded-full bg-slate-100 px-3 py-1 text-xs font-black" }, toDisplayString(user.roles?.[0]?.name ?? "sem perfil"), 1)
                          ]),
                          createVNode("td", { class: "space-x-2 p-3 text-right" }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border px-3 py-2 font-bold",
                              onClick: ($event) => editar(user)
                            }, "Editar", 8, ["onClick"]),
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md bg-red-600 px-3 py-2 font-bold text-white",
                              onClick: ($event) => apagar(user)
                            }, "Apagar", 8, ["onClick"])
                          ])
                        ], 64))
                      ]);
                    }), 128))
                  ])
                ])
              ]),
              createVNode("div", { class: "mb-6 mt-10" }, [
                createVNode("h2", { class: "text-2xl font-bold" }, "Acessos POS"),
                createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Gerir terminais, localizações e PINs usados no login do POS.")
              ]),
              createVNode("form", {
                class: "mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-[1fr_1fr_160px_140px_120px_auto]",
                onSubmit: withModifiers(criarPos, ["prevent"])
              }, [
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(posForm).nome = $event,
                  class: "rounded-md border-slate-300",
                  placeholder: "Nome do terminal"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(posForm).nome]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(posForm).localizacao = $event,
                  class: "rounded-md border-slate-300",
                  placeholder: "Localização/ponto"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(posForm).localizacao]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(posForm).tipo = $event,
                  class: "rounded-md border-slate-300"
                }, [
                  (openBlock(), createBlock(Fragment, null, renderList(tiposPos, ([valor, label]) => {
                    return createVNode("option", {
                      key: valor,
                      value: valor
                    }, toDisplayString(label), 9, ["value"]);
                  }), 64))
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(posForm).tipo]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(posForm).pin = $event,
                  type: "password",
                  class: "rounded-md border-slate-300",
                  placeholder: "PIN"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(posForm).pin]
                ]),
                createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 font-bold" }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(posForm).ativo = $event,
                    type: "checkbox",
                    class: "rounded border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelCheckbox, unref(posForm).ativo]
                  ]),
                  createTextVNode(" Ativo ")
                ]),
                createVNode("button", {
                  class: "rounded-md bg-slate-900 px-4 py-2 font-bold text-white disabled:opacity-50",
                  disabled: unref(posForm).processing
                }, "Criar POS", 8, ["disabled"]),
                Object.keys(unref(posForm).errors).length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "text-sm font-bold text-red-700 md:col-span-6"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(posForm).errors, (erro) => {
                    return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                  }), 128))
                ])) : createCommentVNode("", true)
              ], 32),
              createVNode("div", { class: "overflow-hidden rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full text-left text-sm" }, [
                  createVNode("thead", { class: "bg-slate-50" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "p-3" }, "Terminal"),
                      createVNode("th", null, "Localização"),
                      createVNode("th", null, "Tipo"),
                      createVNode("th", null, "Estado"),
                      createVNode("th", { class: "pr-3 text-right" }, "Ações")
                    ])
                  ]),
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.posTerminais, (terminal) => {
                      return openBlock(), createBlock("tr", {
                        key: terminal.id,
                        class: "border-t align-top"
                      }, [
                        editingPosId.value === terminal.id ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                          createVNode("td", { class: "p-3" }, [
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editPosForm).nome = $event,
                              class: "w-full rounded-md border-slate-300"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editPosForm).nome]
                            ])
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editPosForm).localizacao = $event,
                              class: "w-full rounded-md border-slate-300"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editPosForm).localizacao]
                            ])
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            withDirectives(createVNode("select", {
                              "onUpdate:modelValue": ($event) => unref(editPosForm).tipo = $event,
                              class: "w-full rounded-md border-slate-300"
                            }, [
                              (openBlock(), createBlock(Fragment, null, renderList(tiposPos, ([valor, label]) => {
                                return createVNode("option", {
                                  key: valor,
                                  value: valor
                                }, toDisplayString(label), 9, ["value"]);
                              }), 64))
                            ], 8, ["onUpdate:modelValue"]), [
                              [vModelSelect, unref(editPosForm).tipo]
                            ]),
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => unref(editPosForm).pin = $event,
                              type: "password",
                              class: "mt-2 w-full rounded-md border-slate-300",
                              placeholder: "Novo PIN opcional"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, unref(editPosForm).pin]
                            ])
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            createVNode("label", { class: "inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 font-bold" }, [
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => unref(editPosForm).ativo = $event,
                                type: "checkbox",
                                class: "rounded border-slate-300"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelCheckbox, unref(editPosForm).ativo]
                              ]),
                              createTextVNode(" Ativo ")
                            ])
                          ]),
                          createVNode("td", { class: "space-x-2 p-3 text-right" }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border px-3 py-2 font-bold",
                              onClick: cancelarPos
                            }, "Cancelar"),
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md bg-emerald-700 px-3 py-2 font-bold text-white",
                              onClick: ($event) => guardarPos(terminal)
                            }, "Guardar", 8, ["onClick"])
                          ])
                        ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                          createVNode("td", { class: "p-3 font-semibold" }, toDisplayString(terminal.nome), 1),
                          createVNode("td", { class: "py-3 text-slate-600" }, toDisplayString(terminal.localizacao ?? "-"), 1),
                          createVNode("td", { class: "py-3" }, [
                            createVNode("span", { class: "rounded-full bg-slate-100 px-3 py-1 text-xs font-black" }, toDisplayString(terminal.tipo), 1)
                          ]),
                          createVNode("td", { class: "py-3" }, [
                            createVNode("span", {
                              class: ["rounded-full px-3 py-1 text-xs font-black", terminal.ativo ? "bg-emerald-100 text-emerald-800" : "bg-slate-200 text-slate-600"]
                            }, toDisplayString(terminal.ativo ? "ativo" : "inativo"), 3)
                          ]),
                          createVNode("td", { class: "space-x-2 p-3 text-right" }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border px-3 py-2 font-bold",
                              onClick: ($event) => editarPos(terminal)
                            }, "Editar", 8, ["onClick"]),
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md bg-red-600 px-3 py-2 font-bold text-white",
                              onClick: ($event) => apagarPos(terminal)
                            }, "Apagar", 8, ["onClick"])
                          ])
                        ], 64))
                      ]);
                    }), 128))
                  ])
                ])
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Users/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
