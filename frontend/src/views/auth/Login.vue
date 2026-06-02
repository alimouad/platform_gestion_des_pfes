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
  { icon: 'fa-user-shield',     label: 'Administrateur', color: '#a78bfa' },
  { icon: 'fa-user-tie',        label: 'Coordinateur',   color: '#38bdf8' },
  { icon: 'fa-chalkboard-user', label: 'Professeur',     color: '#d6e87a' },
  { icon: 'fa-user-graduate',   label: 'Étudiant',       color: '#fb923c' },
]

const steps = [
  { icon: 'fa-file-pen',          label: 'Soumission du sujet',  sub: 'Par le professeur encadrant' },
  { icon: 'fa-user-check',        label: 'Affectation étudiant', sub: 'Sélection par le coordinateur' },
  { icon: 'fa-map-location-dot',  label: 'Zone d\'étude SIG',    sub: 'Cartographie Leaflet interactive' },
  { icon: 'fa-boxes-stacked',     label: 'Dépôt des livrables',  sub: 'Rapports et données validés' },
  { icon: 'fa-graduation-cap',    label: 'Soutenance & note',    sub: 'Jury, salle, date et note /20' },
]
</script>

<template>
  <div class="login-root">

    <!-- ═══════════════════════════════════════
         LEFT PANEL
    ═══════════════════════════════════════ -->
    <div class="left-panel hidden lg:flex flex-col">

      <!-- animated layers -->
      <div class="abs-fill hex-grid"></div>
      <div class="abs-fill radial-vignette"></div>
      <div class="abs-fill scan-line"></div>

      <!-- decorative rings -->
      <div class="deco-ring deco-ring-1"></div>
      <div class="deco-ring deco-ring-2"></div>
      <div class="deco-ring deco-ring-3"></div>

      <!-- floating dots -->
      <div class="fdot" style="width:8px;height:8px;top:18%;right:16%;animation-delay:0s"></div>
      <div class="fdot" style="width:5px;height:5px;top:42%;right:9%;animation-delay:-2.5s;opacity:.5"></div>
      <div class="fdot" style="width:6px;height:6px;bottom:28%;right:22%;animation-delay:-5s;opacity:.6"></div>
      <div class="fdot" style="width:4px;height:4px;bottom:45%;left:20%;animation-delay:-3s;opacity:.4"></div>

      <!-- content -->
      <div class="relative z-10 flex flex-col h-full px-14 py-12 justify-between">


        <!-- middle: title + steps -->
        <div class="space-y-10">
          <div :class="mounted?'fade-up':'opacity-0'" style="--d:80ms">
            <p class="eyebrow-tag mb-5">✦ Plateforme PFE · FSBM Hassan II</p>
            <h2 class="hero-title">
              Gérez votre<br/>
              projet de fin<br/>
              <span class="lime-text">d'études</span>
            </h2>
            <p class="mt-5 text-sm leading-relaxed" style="color:rgba(255,255,255,.32);max-width:300px">
              Un espace unifié : soumission, suivi, cartographie SIG, messagerie et soutenance.
            </p>
          </div>

          <!-- steps timeline -->
          <div>
            <div v-for="(s, i) in steps" :key="s.label"
              class="step-row" :class="mounted?'fade-up':'opacity-0'"
              :style="`--d:${180 + i*65}ms`">
              <div v-if="i < steps.length-1" class="step-connector"></div>
              <div class="step-orb">
                <i :class="`fa-solid ${s.icon}`" style="font-size:10px;color:#d6e87a"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-black text-white leading-none">{{ s.label }}</p>
                <p class="text-[10px] mt-0.5" style="color:rgba(255,255,255,.28)">{{ s.sub }}</p>
              </div>
              <span class="step-num">0{{ i+1 }}</span>
            </div>
          </div>
        </div>

        <!-- bottom: university -->
        <div class="pt-6 border-t border-white/8" :class="mounted?'fade-up':'opacity-0'" style="--d:560ms">
          <div class="flex items-center gap-3.5">
            <div class="univ-icon">
              <i class="fa-solid fa-building-columns" style="color:#d6e87a;font-size:12px"></i>
            </div>
            <div>
              <p class="text-xs font-black text-white leading-none">Faculté des Sciences Ben M'Sick</p>
              <p class="text-[10px] mt-0.5" style="color:rgba(255,255,255,.28)">Université Hassan II de Casablanca</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════════════
         RIGHT PANEL
    ═══════════════════════════════════════ -->
    <div class="right-panel flex flex-1 flex-col">

      <!-- bg texture -->
      <div class="abs-fill cream-dots"></div>
      <div class="abs-fill" style="background:radial-gradient(ellipse 60% 50% at 80% 20%,rgba(214,232,122,.14) 0%,transparent 60%)"></div>

      <!-- corner ornaments -->
      <div class="ornament" style="width:340px;height:340px;border-width:52px;top:-110px;right:-110px;opacity:.12"></div>
      <div class="ornament" style="width:180px;height:180px;border-width:28px;bottom:50px;left:-50px;opacity:.08;border-color:#1e4a49"></div>

      <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-8 py-10">
        <div class="form-card" :class="mounted?'fade-up':'opacity-0'" style="--d:40ms">

          <!-- mobile logo -->
          <div class="mb-8 flex items-center gap-3 lg:hidden">
            <div class="gem-logo-sm">
              <i class="fa-solid fa-seedling text-sm" style="color:#0c1f1e"></i>
            </div>
            <span class="font-black text-base" style="color:#0c1f1e">GAGE · FSBM</span>
          </div>

          <!-- form header -->
          <div class="mb-8" :class="mounted?'fade-up':'opacity-0'" style="--d:100ms">
            <div class="secure-badge">
              <span class="secure-icon"><i class="fa-solid fa-shield-halved" style="color:#d6e87a;font-size:8px"></i></span>
              <span class="text-[10px] font-black uppercase tracking-widest" style="color:#1e4a49">Accès institutionnel sécurisé</span>
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-tight" style="color:#0c1f1e;letter-spacing:-.03em">
              Bon retour <span class="wave">👋</span>
            </h1>
            <p class="mt-2 text-sm" style="color:#7a9490">Connectez-vous pour accéder à votre espace PFE.</p>
          </div>

          <!-- form -->
          <form @submit.prevent="submit" novalidate class="space-y-4" :class="mounted?'fade-up':'opacity-0'" style="--d:160ms">

            <div class="input-card">
              <div class="input-label">
                <i class="fa-regular fa-envelope" style="color:#1e4a49;font-size:9px"></i>
                Adresse e-mail
              </div>
              <div class="input-wrap">
                <input v-model="email" type="email" placeholder="prenom.nom@fsbm.ac.ma" required class="i-field"/>
                <div class="i-bar"></div>
              </div>
            </div>

            <div class="input-card">
              <div class="input-label">
                <i class="fa-solid fa-lock" style="color:#1e4a49;font-size:9px"></i>
                Mot de passe
              </div>
              <div class="input-wrap">
                <input v-model="password" :type="showPassword?'text':'password'" placeholder="••••••••" required class="i-field pr-10"/>
                <div class="i-bar"></div>
                <button type="button" @click="showPassword=!showPassword" class="eye-btn">
                  <i :class="showPassword?'fa-regular fa-eye-slash':'fa-regular fa-eye'"></i>
                </button>
              </div>
            </div>

            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition-all duration-200" leave-to-class="opacity-0">
              <div v-if="loginError" class="err-box">
                <div class="err-icon"><i class="fa-solid fa-exclamation text-[10px]" style="color:#f87171"></i></div>
                <p class="text-sm font-semibold" style="color:#dc2626">{{ loginError }}</p>
              </div>
            </Transition>

            <button type="submit" :disabled="loading||!email||!password" class="cta-btn">
              <span class="cta-bg"></span>
              <span class="cta-shine"></span>
              <span class="relative z-10 flex items-center justify-center gap-3 text-sm font-black text-white">
                <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                <i v-else class="fa-solid fa-arrow-right-to-bracket cta-arrow"></i>
                {{ loading ? 'Connexion…' : 'Se connecter' }}
              </span>
            </button>
          </form>

          <!-- divider -->
          <div class="my-7 flex items-center gap-3" :class="mounted?'fade-up':'opacity-0'" style="--d:240ms">
            <div class="flex-1 h-px" style="background:linear-gradient(90deg,transparent,rgba(30,74,73,.12))"></div>
            <span class="text-[9px] font-black uppercase tracking-widest px-1" style="color:#a8bdb8">Espaces disponibles</span>
            <div class="flex-1 h-px" style="background:linear-gradient(90deg,rgba(30,74,73,.12),transparent)"></div>
          </div>

          <!-- roles -->
          <div class="grid grid-cols-2 gap-2.5" :class="mounted?'fade-up':'opacity-0'" style="--d:300ms">
            <div v-for="(r,i) in roles" :key="r.label"
              class="role-chip" :class="mounted?'fade-up':'opacity-0'" :style="`--d:${320+i*45}ms`">
              <div class="role-orb" :style="`background:${r.color}18`">
                <i :class="`fa-solid ${r.icon}`" :style="`color:${r.color};font-size:11px`"></i>
              </div>
              <div>
                <p class="text-[11px] font-black" :style="`color:#1e2d2c`">{{ r.label }}</p>
                <p class="text-[9px]" style="color:#a0b4ae">Espace dédié</p>
              </div>
              <div class="role-pip ml-auto" :style="`background:${r.color}`"></div>
            </div>
          </div>

          <!-- footer -->
          <p class="mt-8 text-center text-[9px] fade-up" style="color:#b8cac8;--d:480ms">
            FSBM · Université Hassan II de Casablanca
          </p>

        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ─ root ─ */
.login-root {
  min-height: 100vh;
  width: 100%;
  display: flex;
  font-family: system-ui, -apple-system, sans-serif;
  -webkit-font-smoothing: antialiased;
  overflow: hidden;
}
.abs-fill { position: absolute; inset: 0; pointer-events: none; }

/* ─ LEFT ─ */
.left-panel {
  width: 52%;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #0c1f1e 0%, #122d2b 40%, #0a1a19 100%);
  flex-direction: column;
}

/* hex grid bg */
.hex-grid {
  background-image:
    linear-gradient(rgba(214,232,122,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(214,232,122,.04) 1px, transparent 1px);
  background-size: 40px 40px;
}

/* radial vignette */
.radial-vignette {
  background: radial-gradient(ellipse 100% 100% at 50% 50%, transparent 40%, rgba(0,0,0,.55) 100%);
}

/* scanning line animation */
.scan-line {
  background: linear-gradient(to bottom, transparent 0%, rgba(214,232,122,.03) 50%, transparent 100%);
  background-size: 100% 200px;
  animation: scan 8s linear infinite;
}
@keyframes scan {
  from { background-position: 0 -200px; }
  to   { background-position: 0 100vh; }
}

/* rings */
.deco-ring {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  border: 1px solid rgba(214,232,122,.1);
}
.deco-ring-1 { width: 500px; height: 500px; top: -180px; right: -130px; }
.deco-ring-2 { width: 320px; height: 320px; top: -100px; right: -60px; border-color: rgba(214,232,122,.06); }
.deco-ring-3 { width: 220px; height: 220px; bottom: 60px; left: -70px; border-color: rgba(214,232,122,.07); }

/* floating dots */
.fdot {
  position: absolute;
  border-radius: 50%;
  background: #d6e87a;
  opacity: .7;
  animation: fdrift 7s ease-in-out infinite alternate;
}
@keyframes fdrift {
  from { transform: translateY(0); opacity: .6; }
  to   { transform: translateY(-10px); opacity: .15; }
}

/* gem logo */
.gem-logo {
  display: flex; align-items: center; justify-content: center;
  width: 44px; height: 44px; border-radius: 14px;
  background: linear-gradient(135deg, #d6e87a 0%, #b0c840 100%);
  box-shadow: 0 0 0 0 rgba(214,232,122,.35), 0 4px 16px rgba(214,232,122,.18);
  animation: gempulse 3.5s ease-in-out infinite;
  flex-shrink: 0;
}
.gem-logo-sm {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 12px;
  background: linear-gradient(135deg, #d6e87a 0%, #b0c840 100%);
}
@keyframes gempulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(214,232,122,.35), 0 4px 16px rgba(214,232,122,.15); }
  50%      { box-shadow: 0 0 0 10px rgba(214,232,122,0), 0 4px 24px rgba(214,232,122,.25); }
}

/* pill badge */
.pill-badge {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 12px; border-radius: 100px;
  background: rgba(214,232,122,.06);
  border: 1px solid rgba(214,232,122,.14);
  backdrop-filter: blur(10px);
}
.pulse-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #d6e87a;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%,100% { opacity: 1; transform: scale(1); }
  50%      { opacity: .4; transform: scale(.8); }
}

/* eyebrow */
.eyebrow-tag {
  font-size: 10px; font-weight: 900;
  text-transform: uppercase; letter-spacing: .25em;
  color: rgba(214,232,122,.45);
}

/* hero title */
.hero-title {
  font-size: clamp(2.5rem, 3.8vw, 3.5rem);
  font-weight: 900;
  color: white;
  line-height: 1.03;
  letter-spacing: -.04em;
}
.lime-text {
  background: linear-gradient(135deg, #d6e87a 0%, #c0d84a 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* steps */
.step-row {
  display: flex; align-items: center; gap: 14px;
  padding: 11px 0; position: relative;
  cursor: default;
}
.step-connector {
  position: absolute;
  left: 17px;
  top: calc(50% + 16px);
  width: 1px;
  height: calc(100% - 4px);
  background: linear-gradient(to bottom, rgba(214,232,122,.2), rgba(214,232,122,.03));
}
.step-orb {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 50%;
  background: rgba(214,232,122,.08);
  border: 1px solid rgba(214,232,122,.16);
  flex-shrink: 0; z-index: 1;
  transition: background .2s, transform .2s;
}
.step-row:hover .step-orb {
  background: rgba(214,232,122,.18);
  transform: scale(1.1);
}
.step-num {
  font-size: 10px; font-weight: 900;
  color: rgba(214,232,122,.3);
  min-width: 22px; text-align: right;
}

/* univ icon */
.univ-icon {
  display: flex; align-items: center; justify-content: center;
  width: 38px; height: 38px; border-radius: 12px;
  background: rgba(214,232,122,.08);
  border: 1px solid rgba(214,232,122,.12);
  flex-shrink: 0;
}

/* ─ RIGHT ─ */
.right-panel {
  flex: 1;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  background: #f2f5ed;
}

.cream-dots {
  background-image: radial-gradient(circle, rgba(30,74,73,.08) 1px, transparent 1px);
  background-size: 26px 26px;
}

.ornament {
  position: absolute;
  border-radius: 50%;
  border-style: solid;
  border-color: #d6e87a;
  pointer-events: none;
}

/* form card */
.form-card {
  width: 100%;
  max-width: 400px;
}

/* secure badge */
.secure-badge {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px; border-radius: 100px;
  background: rgba(30,74,73,.07);
  border: 1px solid rgba(30,74,73,.12);
}
.secure-icon {
  display: flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  background: #1e4a49;
}

/* wave */
.wave { display: inline-block; animation: wave 2.5s ease-in-out infinite; transform-origin: 70% 70%; }
@keyframes wave {
  0%,100% { transform: rotate(0deg); }
  20%      { transform: rotate(-15deg); }
  40%      { transform: rotate(10deg); }
  60%      { transform: rotate(-8deg); }
  80%      { transform: rotate(6deg); }
}

/* input card */
.input-card {
  background: white;
  border: 1.5px solid #e4ede4;
  border-radius: 16px;
  padding: 14px 18px 10px;
  transition: border-color .2s, box-shadow .2s;
}
.input-card:focus-within {
  border-color: #1e4a49;
  box-shadow: 0 0 0 4px rgba(30,74,73,.07);
}
.input-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 9px; font-weight: 900;
  text-transform: uppercase; letter-spacing: .12em;
  color: #8aaa9a; margin-bottom: 5px;
  transition: color .2s;
}
.input-card:focus-within .input-label { color: #1e4a49; }
.input-wrap { position: relative; }
.i-field {
  width: 100%; border: none; background: transparent;
  font-size: .9rem; font-weight: 600; color: #0c1f1e;
  outline: none; padding: 2px 0;
}
.i-field::placeholder { color: #c4d4cc; }
.i-bar {
  position: absolute; bottom: -2px; left: 0;
  height: 1.5px; width: 0%;
  background: linear-gradient(90deg, #1e4a49, #d6e87a);
  border-radius: 2px;
  transition: width .3s ease;
}
.input-wrap:focus-within .i-bar { width: 100%; }
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
  display: flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 50%;
  background: #fee2e2; flex-shrink: 0;
}

/* CTA button */
.cta-btn {
  position: relative; overflow: hidden;
  border-radius: 14px; padding: 17px;
  width: 100%; border: none; cursor: pointer;
  transition: all .3s ease;
  box-shadow: 0 6px 24px rgba(30,74,73,.28), inset 0 1px 0 rgba(255,255,255,.1);
}
.cta-btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 38px rgba(30,74,73,.36), inset 0 1px 0 rgba(255,255,255,.15);
}
.cta-btn:not(:disabled):hover .cta-arrow { transform: translateX(4px); }
.cta-btn:not(:disabled):active { transform: translateY(0); }
.cta-btn:disabled { opacity: .4; cursor: not-allowed; }
.cta-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #1e4a49 0%, #2e7a76 50%, #1e4a49 100%);
  background-size: 200% 100%; background-position: 0% 0%;
  transition: background-position .5s ease;
}
.cta-btn:not(:disabled):hover .cta-bg { background-position: 100% 0%; }
.cta-shine {
  position: absolute; inset: 0;
  background: linear-gradient(120deg, rgba(255,255,255,.15) 0%, transparent 50%);
}
.cta-arrow { transition: transform .25s ease; }

/* roles */
.role-chip {
  display: flex; align-items: center; gap: 10px;
  background: white;
  border: 1.5px solid #eaeee8;
  border-radius: 14px; padding: 12px 14px;
  cursor: default;
  transition: transform .2s, box-shadow .2s, border-color .2s;
}
.role-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,.07);
  border-color: #d0dcd0;
}
.role-orb {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
}
.role-pip {
  width: 6px; height: 6px; border-radius: 50%;
  opacity: .5; flex-shrink: 0;
}

/* entrance */
.fade-up { animation: fadeUp .65s cubic-bezier(.16,1,.3,1) both; animation-delay: var(--d, 0ms); }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* autofill */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px white inset;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
