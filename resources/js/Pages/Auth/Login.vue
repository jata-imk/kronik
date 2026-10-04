<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: "", password: "", remember: false });
const submit = () => {
    form.transform((data) => ({ ...data, remember: form.remember ? "on" : "" }))
        .post(route("login"), { onFinish: () => form.reset("password") });
};
</script>

<template>
    <Head title="Iniciar sesión" />
    <div class="k-public k-login-shell">
        <header class="k-public-nav k-login-nav"><Link href="/" class="k-public-brand" aria-label="Kronik, inicio"><span class="k-brand-mark">K</span><span>Kronik</span></Link><Link href="/" class="k-public-login"><i class="pi pi-arrow-left" aria-hidden="true" /> Volver al inicio</Link></header>
        <main class="k-login-main">
            <aside class="k-login-story" aria-label="Kronik"><p class="k-eyebrow">Tu espacio de trabajo</p><h1>Bienvenido <em class="k-display-accent">de vuelta.</em></h1><p>Continúa donde quedó tu operación. Tus clientes, solicitudes y próximos pasos están en un mismo lugar.</p><div class="k-login-story-card"><i class="pi pi-sparkles" aria-hidden="true" /><span>Claridad para decidir. Continuidad para avanzar.</span></div></aside>
            <section class="k-login-card" aria-labelledby="login-title"><p class="k-eyebrow">Acceso a Kronik</p><h2 id="login-title">Inicia sesión</h2><p class="k-login-subtitle">Ingresa con la cuenta de tu equipo.</p>
                <div v-if="status" class="k-login-status" role="status">{{ status }}</div>
                <form class="k-login-form" @submit.prevent="submit">
                    <div><InputLabel for="email" value="Correo electrónico" /><TextInput id="email" v-model="form.email" type="email" class="mt-2 block w-full" required autofocus autocomplete="username" /><InputError class="mt-2" :message="form.errors.email" /></div>
                    <div><div class="k-login-label-row"><InputLabel for="password" value="Contraseña" /><Link v-if="canResetPassword" :href="route('password.request')" class="k-login-inline-link">¿La olvidaste?</Link></div><TextInput id="password" v-model="form.password" type="password" class="mt-2 block w-full" required autocomplete="current-password" /><InputError class="mt-2" :message="form.errors.password" /></div>
                    <label class="k-login-remember"><Checkbox v-model:checked="form.remember" name="remember" /><span>Recordar sesión</span></label>
                    <button type="submit" class="k-action-link k-login-submit" :disabled="form.processing">{{ form.processing ? 'Entrando…' : 'Entrar a Kronik' }} <i class="pi pi-arrow-right" aria-hidden="true" /></button>
                </form>
            </section>
        </main>
        <footer class="k-public-footer">Kronik · Gestión clara de cada etapa del crédito</footer>
    </div>
</template>
