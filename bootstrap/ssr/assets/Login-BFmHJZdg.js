import { withCtx, unref, createTextVNode, createVNode, openBlock, createBlock, toDisplayString, createCommentVNode, withModifiers, withDirectives, vModelText, vModelCheckbox, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderStyle, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain } from "vue/server-renderer";
import { G as GuestLayout } from "./GuestLayout-BDzZaDZW.js";
import { useForm, Head, Link } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Login",
  __ssrInlineRender: true,
  props: {
    canResetPassword: Boolean,
    status: String
  },
  setup(__props) {
    const form = useForm({
      email: "",
      password: "",
      remember: false
    });
    const submit = () => {
      form.post(route("login"), {
        onFinish: () => form.reset("password")
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(GuestLayout, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), { title: "Entrar" }, null, _parent2, _scopeId));
            _push2(`<h2 class="login-heading mb-6 text-xl font-bold" data-v-8dbddb8f${_scopeId}>Entrar na plataforma</h2>`);
            if (__props.status) {
              _push2(`<div class="mb-4 rounded-lg border p-3 text-sm font-medium" style="${ssrRenderStyle({ "border-color": "rgba(52,211,153,0.2)", "background": "rgba(52,211,153,0.08)", "color": "#34d399" })}" data-v-8dbddb8f${_scopeId}>${ssrInterpolate(__props.status)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mb-6" data-v-8dbddb8f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pos.login"),
              class: "pos-btn block w-full rounded-xl py-3 text-center text-sm font-bold tracking-wide"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Aceder ao POS / Ecrãs `);
                } else {
                  return [
                    createTextVNode(" Aceder ao POS / Ecrãs ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><form class="space-y-4" data-v-8dbddb8f${_scopeId}><label class="block" data-v-8dbddb8f${_scopeId}><span class="mb-1 block text-sm font-semibold" style="${ssrRenderStyle({ "color": "rgba(255,253,248,0.65)" })}" data-v-8dbddb8f${_scopeId}>Email</span><input id="email"${ssrRenderAttr("value", unref(form).email)} type="email" required autofocus autocomplete="username" class="field-input w-full rounded-md px-3 py-2.5 text-sm" data-v-8dbddb8f${_scopeId}>`);
            if (unref(form).errors.email) {
              _push2(`<p class="mt-1 text-xs" style="${ssrRenderStyle({ "color": "#f87171" })}" data-v-8dbddb8f${_scopeId}>${ssrInterpolate(unref(form).errors.email)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="block" data-v-8dbddb8f${_scopeId}><span class="mb-1 block text-sm font-semibold" style="${ssrRenderStyle({ "color": "rgba(255,253,248,0.65)" })}" data-v-8dbddb8f${_scopeId}>Password</span><input id="password"${ssrRenderAttr("value", unref(form).password)} type="password" required autocomplete="current-password" class="field-input w-full rounded-md px-3 py-2.5 text-sm" data-v-8dbddb8f${_scopeId}>`);
            if (unref(form).errors.password) {
              _push2(`<p class="mt-1 text-xs" style="${ssrRenderStyle({ "color": "#f87171" })}" data-v-8dbddb8f${_scopeId}>${ssrInterpolate(unref(form).errors.password)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><div class="flex items-center justify-between" data-v-8dbddb8f${_scopeId}><label class="flex items-center gap-2 text-sm" style="${ssrRenderStyle({ "color": "rgba(255,253,248,0.55)" })}" data-v-8dbddb8f${_scopeId}><input type="checkbox" name="remember"${ssrIncludeBooleanAttr(Array.isArray(unref(form).remember) ? ssrLooseContain(unref(form).remember, null) : unref(form).remember) ? " checked" : ""} class="rounded" style="${ssrRenderStyle({ "accent-color": "#D4AF37" })}" data-v-8dbddb8f${_scopeId}> Manter sessão </label>`);
            if (__props.canResetPassword) {
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("password.request"),
                class: "text-sm font-semibold transition",
                style: { "color": "#C9A84C" }
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Esqueceu a password? `);
                  } else {
                    return [
                      createTextVNode(" Esqueceu a password? ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><button type="submit" class="submit-btn w-full rounded-md py-2.5 text-sm font-bold shadow-sm transition"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""} data-v-8dbddb8f${_scopeId}>${ssrInterpolate(unref(form).processing ? "A entrar..." : "Entrar")}</button></form>`);
          } else {
            return [
              createVNode(unref(Head), { title: "Entrar" }),
              createVNode("h2", { class: "login-heading mb-6 text-xl font-bold" }, "Entrar na plataforma"),
              __props.status ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mb-4 rounded-lg border p-3 text-sm font-medium",
                style: { "border-color": "rgba(52,211,153,0.2)", "background": "rgba(52,211,153,0.08)", "color": "#34d399" }
              }, toDisplayString(__props.status), 1)) : createCommentVNode("", true),
              createVNode("div", { class: "mb-6" }, [
                createVNode(unref(Link), {
                  href: _ctx.route("pos.login"),
                  class: "pos-btn block w-full rounded-xl py-3 text-center text-sm font-bold tracking-wide"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Aceder ao POS / Ecrãs ")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("form", {
                onSubmit: withModifiers(submit, ["prevent"]),
                class: "space-y-4"
              }, [
                createVNode("label", { class: "block" }, [
                  createVNode("span", {
                    class: "mb-1 block text-sm font-semibold",
                    style: { "color": "rgba(255,253,248,0.65)" }
                  }, "Email"),
                  withDirectives(createVNode("input", {
                    id: "email",
                    "onUpdate:modelValue": ($event) => unref(form).email = $event,
                    type: "email",
                    required: "",
                    autofocus: "",
                    autocomplete: "username",
                    class: "field-input w-full rounded-md px-3 py-2.5 text-sm"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).email]
                  ]),
                  unref(form).errors.email ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "mt-1 text-xs",
                    style: { "color": "#f87171" }
                  }, toDisplayString(unref(form).errors.email), 1)) : createCommentVNode("", true)
                ]),
                createVNode("label", { class: "block" }, [
                  createVNode("span", {
                    class: "mb-1 block text-sm font-semibold",
                    style: { "color": "rgba(255,253,248,0.65)" }
                  }, "Password"),
                  withDirectives(createVNode("input", {
                    id: "password",
                    "onUpdate:modelValue": ($event) => unref(form).password = $event,
                    type: "password",
                    required: "",
                    autocomplete: "current-password",
                    class: "field-input w-full rounded-md px-3 py-2.5 text-sm"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).password]
                  ]),
                  unref(form).errors.password ? (openBlock(), createBlock("p", {
                    key: 0,
                    class: "mt-1 text-xs",
                    style: { "color": "#f87171" }
                  }, toDisplayString(unref(form).errors.password), 1)) : createCommentVNode("", true)
                ]),
                createVNode("div", { class: "flex items-center justify-between" }, [
                  createVNode("label", {
                    class: "flex items-center gap-2 text-sm",
                    style: { "color": "rgba(255,253,248,0.55)" }
                  }, [
                    withDirectives(createVNode("input", {
                      type: "checkbox",
                      name: "remember",
                      "onUpdate:modelValue": ($event) => unref(form).remember = $event,
                      class: "rounded",
                      style: { "accent-color": "#D4AF37" }
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(form).remember]
                    ]),
                    createTextVNode(" Manter sessão ")
                  ]),
                  __props.canResetPassword ? (openBlock(), createBlock(unref(Link), {
                    key: 0,
                    href: _ctx.route("password.request"),
                    class: "text-sm font-semibold transition",
                    style: { "color": "#C9A84C" }
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Esqueceu a password? ")
                    ]),
                    _: 1
                  }, 8, ["href"])) : createCommentVNode("", true)
                ]),
                createVNode("button", {
                  type: "submit",
                  class: "submit-btn w-full rounded-md py-2.5 text-sm font-bold shadow-sm transition",
                  disabled: unref(form).processing
                }, toDisplayString(unref(form).processing ? "A entrar..." : "Entrar"), 9, ["disabled"])
              ], 32)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Auth/Login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Login = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-8dbddb8f"]]);
export {
  Login as default
};
