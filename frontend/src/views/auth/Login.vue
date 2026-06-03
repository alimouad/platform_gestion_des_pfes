<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient, { setAuthToken } from '@/services/api'

const router = useRouter()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const loginError = ref('')
const mounted = ref(false)

onMounted(() => { setTimeout(() => { mounted.value = true }, 50) })

async function submit() {
  loginError.value = ''
  if (!email.value || !password.value) return
  loading.value = true
  try {
    const res = await apiClient.post('/login', {
      courriel: email.value,
      mot_de_passe: password.value,
    })
    if (res.data?.token) {
      setAuthToken(res.data.token)
      const me = await apiClient.get('/me')
      const user = me.data?.data
      localStorage.setItem('admin_user', JSON.stringify(user || {}))
      const routes = {
        superadmin: '/admin/dashboard',
        coordinateur: '/coordinateur/dashboard',
        professeur: '/professeur/dashboard',
        etudiant: '/etudiant/dashboard',
      }
      router.push(routes[user?.role] || '/')
    }
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Email ou mot de passe incorrect.'
  } finally {
    loading.value = false
  }
}

const roles = [
  { icon: 'fa-user-shield',     label: 'Administrateur', color: '#a78bfa', bg: 'rgba(167,139,250,.12)' },
  { icon: 'fa-user-tie',        label: 'Coordinateur',   color: '#38bdf8', bg: 'rgba(56,189,248,.12)' },
  { icon: 'fa-chalkboard-user', label: 'Professeur',     color: '#d6e87a', bg: 'rgba(214,232,122,.12)' },
  { icon: 'fa-user-graduate',   label: 'Étudiant',       color: '#fb923c', bg: 'rgba(251,146,60,.12)' },
]

const steps = [
  { icon: 'fa-file-pen',         label: 'Soumission',   sub: 'Professeur propose le sujet' },
  { icon: 'fa-user-check',       label: 'Affectation',  sub: 'Coordinateur valide' },
  { icon: 'fa-map-location-dot', label: 'Zone SIG',     sub: 'Cartographie Leaflet' },
  { icon: 'fa-boxes-stacked',    label: 'Dépôts',       sub: 'Livrables & données' },
  { icon: 'fa-graduation-cap',   label: 'Soutenance',   sub: 'Note finale /20' },
]
</script>

<template>
  <div class="root">

    <!-- ═══ LEFT PANEL ═══ -->
    <div class="left-panel hidden lg:flex flex-col">

      <!-- bg layers -->
      <div class="abs-fill" style="background:radial-gradient(ellipse 80% 60% at 20% 30%,rgba(0,0,0,.25) 0%,transparent 60%)"></div>
      <div class="abs-fill mesh"></div>
      <div class="abs-fill" style="background:radial-gradient(ellipse 60% 80% at 85% 80%,rgba(0,0,0,.2) 0%,transparent 55%)"></div>

      <!-- geometric accents -->
      <div class="geo-ring" style="width:480px;height:480px;top:-160px;right:-120px;border-color:rgba(214,232,122,.12)"></div>
      <div class="geo-ring" style="width:300px;height:300px;top:-90px;right:-55px;border-color:rgba(214,232,122,.07)"></div>
      <div class="geo-ring" style="width:180px;height:180px;bottom:80px;left:-50px;border-color:rgba(214,232,122,.08)"></div>
      <div class="geo-ring" style="width:90px;height:90px;bottom:160px;left:20px;border-color:rgba(214,232,122,.06)"></div>

      <!-- floating particles -->
      <div class="particle" style="width:7px;height:7px;top:19%;right:17%;animation-delay:0s"></div>
      <div class="particle" style="width:4px;height:4px;top:44%;right:8%;animation-delay:-2s;opacity:.45"></div>
      <div class="particle" style="width:5px;height:5px;bottom:30%;right:24%;animation-delay:-4.5s;opacity:.55"></div>
      <div class="particle" style="width:3px;height:3px;bottom:48%;left:18%;animation-delay:-7s;opacity:.35"></div>

      <!-- horizontal scan line -->
      <div class="abs-fill scan"></div>

      <!-- CONTENT -->
      <div class="relative z-10 flex flex-col h-full px-12 py-10 justify-between">

        <!-- top: logo -->
        <div class="flex items-center justify-between" :class="mounted?'slide-in':'opacity-0'" style="--d:0ms">
          <div class="flex items-center gap-3.5">
            <div class="brand-gem">
              <i class="fa-solid fa-seedling" style="color:#0c1f1e;font-size:1rem"></i>
            </div>
            <div>
              <p class="font-black text-white text-lg leading-none tracking-tight">GeoGrad</p>
              <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.18em;color:rgba(214,232,122,.5)">Gestion PFE · FSBM</p>
            </div>
          </div>
          <div class="live-chip">
            <span class="live-dot"></span>
            <span style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.18em;color:rgba(214,232,122,.65)">2024–2026</span>
          </div>
        </div>

        <!-- center: headline + steps -->
        <div class="space-y-8">
          <div :class="mounted?'slide-in':'opacity-0'" style="--d:70ms">
            <p style="font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.28em;color:rgba(214,232,122,.45);margin-bottom:1.2rem">
              ✦ Plateforme académique · Hassan II
            </p>
            <h2 class="headline">
              Pilotez votre<br/>
              PFE du début<br/>
              <em class="lime-em">à la fin.</em>
            </h2>
            <p style="margin-top:1.1rem;font-size:13px;line-height:1.7;color:rgba(255,255,255,.28);max-width:290px;font-weight:500">
              Un espace unifié pour étudiants, professeurs et coordinateurs — avec cartographie SIG intégrée.
            </p>
          </div>

          <!-- timeline -->
          <div class="space-y-0">
            <div v-for="(step, i) in steps" :key="step.label"
              class="step" :class="mounted?'slide-in':'opacity-0'"
              :style="`--d:${160+i*60}ms`">
              <div v-if="i < steps.length-1" class="step-line"></div>
              <div class="step-orb">
                <i :class="`fa-solid ${step.icon}`" style="font-size:9px;color:#d6e87a"></i>
              </div>
              <div class="flex-1">
                <p style="font-size:12.5px;font-weight:900;color:#fff;line-height:1">{{ step.label }}</p>
                <p style="font-size:10px;font-weight:500;color:rgba(255,255,255,.28);margin-top:2px">{{ step.sub }}</p>
              </div>
              <span style="font-size:9px;font-weight:900;color:rgba(214,232,122,.3);min-width:20px;text-align:right">0{{ i+1 }}</span>
            </div>
          </div>
        </div>

        <!-- bottom: university -->
        <div :class="mounted?'slide-in':'opacity-0'" style="--d:540ms;border-top:1px solid rgba(255,255,255,.07);padding-top:1.4rem">
          <div class="flex items-center gap-3.5">
            <div class="univ-badge">
              <i class="fa-solid fa-building-columns" style="color:#d6e87a;font-size:11px"></i>
            </div>
            <div>
              <p style="font-size:11.5px;font-weight:900;color:#fff;line-height:1">Faculté des Sciences Ben M'Sick</p>
              <p style="font-size:10px;font-weight:500;color:rgba(255,255,255,.28);margin-top:3px">Université Hassan II de Casablanca</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ═══ RIGHT PANEL ═══ -->
    <div class="right-panel flex flex-1 flex-col">

      <!-- background -->
      <div class="abs-fill" style="background:radial-gradient(ellipse 70% 55% at 85% 15%,rgba(214,232,122,.13) 0%,transparent 55%)"></div>
      <div class="abs-fill" style="background:radial-gradient(ellipse 50% 45% at 10% 90%,rgba(30,74,73,.07) 0%,transparent 55%)"></div>
      <div class="abs-fill noise-dots"></div>

      <!-- corner shapes -->
      <div class="corner-ring" style="width:360px;height:360px;border-width:56px;top:-115px;right:-115px;opacity:.11"></div>
      <div class="corner-ring" style="width:200px;height:200px;border-width:32px;bottom:55px;left:-55px;opacity:.07;border-color:#1e4a49"></div>

      <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-8 py-10">
        <div style="width:100%;max-width:400px">

          <!-- mobile logo -->
          <div class="mb-8 flex items-center gap-3 lg:hidden" :class="mounted?'slide-in':'opacity-0'" style="--d:0ms">
            <div class="brand-gem-sm">
              <i class="fa-solid fa-seedling" style="color:#0c1f1e;font-size:.85rem"></i>
            </div>
            <span style="font-weight:900;font-size:1rem;color:#0c1f1e">GeoGrad</span>
          </div>

          <!-- form header -->
          <div class="mb-7" :class="mounted?'slide-in':'opacity-0'" style="--d:80ms">
            <div class="access-tag">
              <div class="access-icon">
                <i class="fa-solid fa-shield-halved" style="color:#d6e87a;font-size:7px"></i>
              </div>
              <span style="font-size:9.5px;font-weight:900;text-transform:uppercase;letter-spacing:.14em;color:#1e4a49">Accès sécurisé — GeoGrad</span>
            </div>
            <h1 class="form-title">Connexion</h1>
            <p style="font-size:13px;color:#7a9490;margin-top:6px;font-weight:500">Accédez à votre espace de gestion PFE.</p>
          </div>

          <!-- form -->
          <form @submit.prevent="submit" novalidate
            class="space-y-4" :class="mounted?'slide-in':'opacity-0'" style="--d:150ms">

            <div class="field-card" :class="email ? 'field-filled' : ''">
              <label class="field-label">
                <i class="fa-regular fa-envelope" style="font-size:8px;color:#1e4a49"></i>
                Adresse e-mail
              </label>
              <div class="field-inner">
                <input v-model="email" type="email" placeholder="prenom.nom@fsbm.ac.ma" required class="field-input" />
                <div class="field-bar"></div>
              </div>
            </div>

            <div class="field-card" :class="password ? 'field-filled' : ''">
              <label class="field-label">
                <i class="fa-solid fa-lock" style="font-size:8px;color:#1e4a49"></i>
                Mot de passe
              </label>
              <div class="field-inner" style="position:relative">
                <input v-model="password" :type="showPassword?'text':'password'" placeholder="••••••••" required class="field-input" style="padding-right:2rem" />
                <div class="field-bar"></div>
                <button type="button" @click="showPassword=!showPassword" class="eye-btn">
                  <i :class="showPassword?'fa-regular fa-eye-slash':'fa-regular fa-eye'" style="font-size:12px"></i>
                </button>
              </div>
            </div>

            <Transition
              enter-active-class="transition-all duration-300"
              enter-from-class="opacity-0 -translate-y-1"
              leave-active-class="transition-all duration-200"
              leave-to-class="opacity-0">
              <div v-if="loginError" class="err-box">
                <div class="err-icon"><i class="fa-solid fa-triangle-exclamation" style="font-size:10px;color:#ef4444"></i></div>
                <p style="font-size:13px;font-weight:600;color:#dc2626">{{ loginError }}</p>
              </div>
            </Transition>

            <button type="submit" :disabled="loading||!email||!password" class="cta">
              <span class="cta-fill"></span>
              <span class="cta-glow"></span>
              <span class="cta-shimmer"></span>
              <span class="relative z-10 flex items-center justify-center gap-3" style="font-size:14px;font-weight:900;color:#fff">
                <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                <i v-else class="fa-solid fa-arrow-right-to-bracket cta-icon"></i>
                {{ loading ? 'Connexion…' : 'Se connecter' }}
              </span>
            </button>
          </form>

          <!-- divider -->
          <div class="my-6 flex items-center gap-3" :class="mounted?'slide-in':'opacity-0'" style="--d:240ms">
            <div style="flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(30,74,73,.1))"></div>
            <span style="font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.16em;color:#a8bcb8">Espaces disponibles</span>
            <div style="flex:1;height:1px;background:linear-gradient(90deg,rgba(30,74,73,.1),transparent)"></div>
          </div>

          <!-- roles -->
          <div class="grid grid-cols-2 gap-2.5" :class="mounted?'slide-in':'opacity-0'" style="--d:300ms">
            <div v-for="(r,i) in roles" :key="r.label"
              class="role-tile slide-in" :style="`--d:${320+i*45}ms`">
              <div class="role-icon" :style="`background:${r.bg}`">
                <i :class="`fa-solid ${r.icon}`" :style="`color:${r.color};font-size:12px`"></i>
              </div>
              <div style="flex:1">
                <p style="font-size:11px;font-weight:900;color:#162320">{{ r.label }}</p>
                <p style="font-size:9px;font-weight:500;color:#9ab4ae;margin-top:1px">Espace dédié</p>
              </div>
              <div class="role-dot" :style="`background:${r.color}`"></div>
            </div>
          </div>

          <!-- footer -->
          <p class="mt-7 text-center slide-in" style="font-size:9px;color:#b0c8c4;--d:480ms">
            GeoGrad · FSBM · Université Hassan II de Casablanca
          </p>

        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ── root ── */
.root {
  min-height: 100vh; width: 100%;
  display: flex;
  font-family: system-ui, -apple-system, sans-serif;
  -webkit-font-smoothing: antialiased;
  overflow: hidden;
}
.abs-fill { position: absolute; inset: 0; pointer-events: none; }

/* ── LEFT ── */
.left-panel {
  width: 50%;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  flex-direction: column;
  background: linear-gradient(150deg, #0e2322 0%, #1a3d3b 35%, #122b2a 70%, #0a1918 100%);
}

/* mesh grid */
.mesh {
  background-image:
    linear-gradient(rgba(214,232,122,.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(214,232,122,.035) 1px, transparent 1px);
  background-size: 44px 44px;
}

/* scan animation */
.scan {
  background: linear-gradient(to bottom, transparent, rgba(214,232,122,.025) 50%, transparent);
  background-size: 100% 180px;
  animation: scan 9s linear infinite;
}
@keyframes scan {
  from { background-position: 0 -180px; }
  to   { background-position: 0 110vh; }
}

/* geometric rings */
.geo-ring {
  position: absolute; border-radius: 50%;
  border: 1px solid; pointer-events: none;
}

/* floating particles */
.particle {
  position: absolute; border-radius: 50%;
  background: #d6e87a; opacity: .65;
  animation: float 7s ease-in-out infinite alternate;
}
@keyframes float {
  from { transform: translateY(0) scale(1); opacity: .6; }
  to   { transform: translateY(-12px) scale(.85); opacity: .15; }
}

/* brand gem */
.brand-gem {
  width: 44px; height: 44px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #d6e87a 0%, #b2c840 100%);
  box-shadow: 0 0 0 0 rgba(214,232,122,.3), 0 4px 18px rgba(214,232,122,.18);
  animation: glow 3.5s ease-in-out infinite; flex-shrink: 0;
}
.brand-gem-sm {
  width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #d6e87a 0%, #b2c840 100%);
}
@keyframes glow {
  0%,100% { box-shadow: 0 0 0 0 rgba(214,232,122,.3), 0 4px 18px rgba(214,232,122,.15); }
  50%      { box-shadow: 0 0 0 10px rgba(214,232,122,0), 0 4px 28px rgba(214,232,122,.28); }
}

/* live chip */
.live-chip {
  display: flex; align-items: center; gap: 7px;
  padding: 5px 12px; border-radius: 100px;
  background: rgba(214,232,122,.06);
  border: 1px solid rgba(214,232,122,.14);
  backdrop-filter: blur(12px);
}
.live-dot {
  width: 6px; height: 6px; border-radius: 50%; background: #d6e87a;
  animation: pulse 2.2s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.35;transform:scale(.75)} }

/* headline */
.headline {
  font-size: clamp(2.4rem, 3.6vw, 3.3rem);
  font-weight: 900; color: #fff;
  line-height: 1.04; letter-spacing: -.04em;
  font-style: normal;
}
.lime-em {
  font-style: normal;
  background: linear-gradient(135deg, #d6e87a 20%, #c0d840 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* timeline */
.step {
  display: flex; align-items: center; gap: 14px;
  padding: 10px 0; position: relative; cursor: default;
}
.step-line {
  position: absolute; left: 16px; top: calc(50% + 15px);
  width: 1px; height: calc(100% - 3px);
  background: linear-gradient(to bottom, rgba(214,232,122,.18), rgba(214,232,122,.03));
}
.step-orb {
  width: 33px; height: 33px; border-radius: 50%; flex-shrink: 0; z-index: 1;
  display: flex; align-items: center; justify-content: center;
  background: rgba(214,232,122,.08); border: 1px solid rgba(214,232,122,.15);
  transition: background .2s, transform .2s;
}
.step:hover .step-orb { background: rgba(214,232,122,.18); transform: scale(1.1); }

/* univ badge */
.univ-badge {
  width: 36px; height: 36px; border-radius: 11px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(214,232,122,.08); border: 1px solid rgba(214,232,122,.12);
}

/* ── RIGHT ── */
.right-panel {
  flex: 1; min-height: 100vh;
  position: relative; overflow: hidden;
  background: #f0f5ed;
}

/* noise dots */
.noise-dots {
  background-image: radial-gradient(circle, rgba(30,74,73,.07) 1px, transparent 1px);
  background-size: 26px 26px;
}

/* corner ring */
.corner-ring {
  position: absolute; border-radius: 50%;
  border-style: solid; border-color: #d6e87a; pointer-events: none;
}

/* access tag */
.access-tag {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 100px;
  background: rgba(30,74,73,.07); border: 1px solid rgba(30,74,73,.12);
  margin-bottom: 16px;
}
.access-icon {
  width: 18px; height: 18px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #1e4a49;
}

/* form title */
.form-title {
  font-size: 2.2rem; font-weight: 900; color: #0c1f1e;
  letter-spacing: -.03em; line-height: 1;
}

/* field card */
.field-card {
  background: white;
  border: 1.5px solid #e4ede4;
  border-radius: 15px;
  padding: 13px 16px 9px;
  transition: border-color .22s, box-shadow .22s;
}
.field-card:focus-within {
  border-color: #1e4a49;
  box-shadow: 0 0 0 4px rgba(30,74,73,.07);
}
.field-card.field-filled { border-color: rgba(30,74,73,.3); }

.field-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 8.5px; font-weight: 900;
  text-transform: uppercase; letter-spacing: .13em;
  color: #8aaa9a; margin-bottom: 5px;
  transition: color .2s;
}
.field-card:focus-within .field-label { color: #1e4a49; }

.field-inner { position: relative; }
.field-input {
  width: 100%; border: none; background: transparent;
  font-size: 14.5px; font-weight: 600; color: #0c1f1e;
  outline: none; padding: 2px 0;
}
.field-input::placeholder { color: #c4d4cc; }

.field-bar {
  position: absolute; bottom: -2px; left: 0;
  height: 1.5px; width: 0%;
  background: linear-gradient(90deg, #1e4a49, #d6e87a);
  border-radius: 2px; transition: width .32s ease;
}
.field-inner:focus-within .field-bar { width: 100%; }

.eye-btn {
  position: absolute; right: 0; top: 50%; transform: translateY(-50%);
  color: #b0c4bc; background: none; border: none; cursor: pointer;
  transition: color .2s; padding: 4px;
}
.eye-btn:hover { color: #1e4a49; }

/* error */
.err-box {
  display: flex; align-items: center; gap: 12px;
  border-radius: 14px; padding: 12px 16px;
  background: #fff5f5; border: 1.5px solid #fecaca;
}
.err-icon {
  width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: #fee2e2;
}

/* CTA */
.cta {
  position: relative; overflow: hidden;
  border-radius: 14px; padding: 16.5px;
  width: 100%; border: none; cursor: pointer;
  transition: transform .3s ease, box-shadow .3s ease;
  box-shadow: 0 6px 28px rgba(30,74,73,.32), inset 0 1px 0 rgba(255,255,255,.08);
}
.cta:not(:disabled):hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(30,74,73,.4), inset 0 1px 0 rgba(255,255,255,.12); }
.cta:not(:disabled):hover .cta-icon { transform: translateX(4px); }
.cta:not(:disabled):active { transform: translateY(0); }
.cta:disabled { opacity: .38; cursor: not-allowed; }
.cta-fill {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #1a3d3b 0%, #2d7a76 45%, #1e4a49 100%);
  background-size: 200% 100%; background-position: 0%;
  transition: background-position .55s ease;
}
.cta:not(:disabled):hover .cta-fill { background-position: 100%; }
.cta-glow {
  position: absolute; inset: 0;
  background: linear-gradient(120deg, rgba(255,255,255,.13) 0%, transparent 50%);
}
.cta-shimmer {
  position: absolute; top: 0; left: -100%;
  width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent);
  animation: shimmer 3s ease-in-out infinite;
}
@keyframes shimmer { from{left:-100%} to{left:200%} }
.cta-icon { transition: transform .25s ease; }

/* roles */
.role-tile {
  display: flex; align-items: center; gap: 10px;
  background: white; border: 1.5px solid #eaeee8;
  border-radius: 14px; padding: 11px 13px;
  cursor: default;
  transition: transform .2s, box-shadow .2s, border-color .2s;
}
.role-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.07); border-color: #d4dcd0; }
.role-icon {
  width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.role-dot { width: 6px; height: 6px; border-radius: 50%; opacity: .45; flex-shrink: 0; }

/* entrance animation */
.slide-in { animation: slideIn .65s cubic-bezier(.16,1,.3,1) both; animation-delay: var(--d,0ms); }
@keyframes slideIn {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* autofill */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px white inset;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
