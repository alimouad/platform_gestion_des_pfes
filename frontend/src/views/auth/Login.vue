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

const features = [
  { icon: 'fa-folder-open',      label: 'Cycle PFE complet',       sub: 'Soumission → Soutenance' },
  { icon: 'fa-map-location-dot', label: 'Carte SIG interactive',    sub: 'Zones d\'étude & clustering' },
  { icon: 'fa-comments',         label: 'Messagerie intégrée',      sub: 'Étudiant ↔ Encadrant' },
  { icon: 'fa-chart-pie',        label: 'Statistiques temps réel',  sub: 'Tableau de bord analytique' },
]

const roles = [
  { icon: 'fa-user-shield',     label: 'Administrateur', color: '#7c3aed', bg: '#f5f3ff', border: '#e9d5ff' },
  { icon: 'fa-user-tie',        label: 'Coordinateur',   color: '#0284c7', bg: '#f0f9ff', border: '#bae6fd' },
  { icon: 'fa-chalkboard-user', label: 'Professeur',     color: '#1e4a49', bg: '#f0f5e0', border: '#c6da5a' },
  { icon: 'fa-user-graduate',   label: 'Étudiant',       color: '#ea580c', bg: '#fff7ed', border: '#fed7aa' },
]
</script>

<template>
  <div class="min-h-screen w-full flex font-sans antialiased overflow-hidden">

    <!-- ════════════════════════════════
         LEFT — dark branded panel
    ════════════════════════════════ -->
    <div class="hidden lg:flex flex-col w-[55%] min-h-screen relative overflow-hidden" style="background:#07100f">

      <!-- layered background -->
      <div class="abs-fill topo-lines"></div>
      <div class="abs-fill" style="background:radial-gradient(ellipse 70% 60% at 25% 40%,rgba(30,74,73,0.65) 0%,transparent 65%)"></div>
      <div class="abs-fill" style="background:radial-gradient(ellipse 50% 50% at 80% 80%,rgba(214,232,122,0.06) 0%,transparent 60%)"></div>

      <!-- grid dots -->
      <div class="abs-fill grid-dots"></div>

      <!-- content -->
      <div class="relative z-10 flex flex-col justify-between h-full px-16 py-14">

        <!-- logo -->
        <div class="flex items-center justify-between" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:0ms">
          <div class="flex items-center gap-3.5">
            <div class="logo-gem flex h-12 w-12 items-center justify-center rounded-2xl">
              <i class="fa-solid fa-seedling" style="color:#1e4a49;font-size:1.1rem"></i>
            </div>
            <div>
              <p class="font-black text-white text-base leading-none tracking-tight">GAGE</p>
              <p class="text-[10px] font-bold uppercase tracking-widest mt-0.5" style="color:rgba(214,232,122,0.4)">FSBM · Hassan II · Casablanca</p>
            </div>
          </div>
          <div class="live-badge flex items-center gap-2 rounded-full px-3.5 py-1.5">
            <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background:#d6e87a"></span>
            <span class="text-[9px] font-black uppercase tracking-widest" style="color:rgba(214,232,122,0.8)">En ligne</span>
          </div>
        </div>

        <!-- hero -->
        <div class="space-y-10">
          <div :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:80ms">
            <p class="text-[11px] font-black uppercase tracking-[0.25em] mb-5" style="color:rgba(214,232,122,0.5)">
              Plateforme de gestion · 2024–2026
            </p>
            <h2 class="font-black text-white" style="font-size:clamp(2.8rem,4.5vw,4rem);line-height:1.0;letter-spacing:-0.03em">
              Géomatique<br/>
              <span class="lime-stroke">Appliquée</span><br/>
              <span style="color:rgba(255,255,255,0.2)">aux Géosciences</span>
            </h2>
            <p class="mt-6 text-sm leading-relaxed" style="color:rgba(255,255,255,0.35);max-width:320px">
              Pilotez votre projet de fin d'études de la soumission jusqu'à la soutenance, avec cartographie SIG intégrée.
            </p>
          </div>

          <!-- features -->
          <div class="space-y-2" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:160ms">
            <div v-for="(f, i) in features" :key="f.label"
              class="feature-row flex items-center gap-4 rounded-2xl px-5 py-3.5"
              :style="`transition-delay:${200 + i * 60}ms`"
              :class="mounted ? 'anim-in' : 'opacity-0'">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl feature-icon">
                <i :class="`fa-solid ${f.icon} text-xs`" style="color:#d6e87a"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white">{{ f.label }}</p>
                <p class="text-[11px]" style="color:rgba(255,255,255,0.28)">{{ f.sub }}</p>
              </div>
              <div class="check-dot flex h-5 w-5 items-center justify-center rounded-full">
                <i class="fa-solid fa-check" style="font-size:8px;color:#d6e87a"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- stats -->
        <div class="flex gap-10 pt-8 stats-row" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:500ms">
          <div v-for="s in [{ n:'2 635', l:'Étudiants' }, { n:'29', l:'Professeurs' }, { n:'84+', l:'Projets PFE' }]" :key="s.l"
            class="stat-item">
            <p class="font-black text-white" style="font-size:2rem;line-height:1;letter-spacing:-0.04em">{{ s.n }}</p>
            <p class="text-[10px] font-bold uppercase tracking-widest mt-1.5" style="color:rgba(255,255,255,0.22)">{{ s.l }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════
         RIGHT — form panel
    ════════════════════════════════ -->
    <div class="flex flex-1 flex-col min-h-screen relative overflow-hidden" style="background:#f8f9f4">

      <!-- layered bg -->
      <div class="abs-fill light-dots"></div>
      <div class="abs-fill" style="background:linear-gradient(160deg,rgba(214,232,122,0.13) 0%,transparent 45%,rgba(30,74,73,0.04) 100%)"></div>

      <!-- decorative corner shapes -->
      <div class="pointer-events-none absolute" style="width:320px;height:320px;border-radius:50%;background:conic-gradient(from 180deg,rgba(214,232,122,0.18),rgba(30,74,73,0.08),transparent);top:-80px;right:-80px;filter:blur(2px);opacity:0.7"></div>
      <div class="pointer-events-none absolute" style="width:200px;height:200px;border-radius:50%;border:40px solid rgba(214,232,122,0.12);bottom:60px;left:-60px"></div>
      <div class="pointer-events-none absolute" style="width:120px;height:120px;border-radius:50%;border:24px solid rgba(30,74,73,0.06);bottom:200px;right:30px"></div>
      <!-- floating dots -->
      <div class="pointer-events-none absolute w-2 h-2 rounded-full" style="background:#d6e87a;opacity:0.5;top:18%;right:12%"></div>
      <div class="pointer-events-none absolute w-1.5 h-1.5 rounded-full" style="background:#1e4a49;opacity:0.25;top:35%;right:20%"></div>
      <div class="pointer-events-none absolute w-2.5 h-2.5 rounded-full" style="background:#d6e87a;opacity:0.3;bottom:22%;left:14%"></div>

      <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-8 py-10">
        <div class="w-full" style="max-width:400px">

          <!-- mobile logo -->
          <div class="mb-8 flex items-center gap-3 lg:hidden">
            <div class="logo-gem flex h-10 w-10 items-center justify-center rounded-2xl">
              <i class="fa-solid fa-seedling text-sm" style="color:#1e4a49"></i>
            </div>
            <span class="font-black text-base" style="color:#0b1f1e">GAGE · FSBM</span>
          </div>

          <!-- eyebrow -->
          <div class="mb-6" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:60ms">
            <div class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5" style="background:rgba(30,74,73,0.07);border:1px solid rgba(30,74,73,0.12)">
              <span class="flex h-4 w-4 items-center justify-center rounded-full" style="background:#1e4a49">
                <i class="fa-solid fa-shield-halved" style="color:#d6e87a;font-size:8px"></i>
              </span>
              <span class="text-[10px] font-black uppercase tracking-widest" style="color:#1e4a49">Accès institutionnel sécurisé</span>
            </div>
          </div>

          <!-- heading -->
          <div class="mb-8" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:120ms">

            <p class="mt-3 text-sm font-medium leading-relaxed" style="color:#8a9e9a;max-width:300px">
              Connectez-vous pour accéder à votre espace personnel de gestion PFE.
            </p>
          </div>

          <!-- form -->
          <form @submit.prevent="submit" novalidate
            class="space-y-4" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:180ms">

            <!-- email field -->
            <div class="r-field">
              <label class="r-label">
                <i class="fa-regular fa-envelope" style="color:#1e4a49;font-size:9px"></i>
                Adresse e-mail
              </label>
              <div class="input-shell">
                <input v-model="email" type="email" placeholder="prenom.nom@fsbm.ac.ma" required class="r-input" />
                <div class="input-bar"></div>
              </div>
            </div>

            <!-- password field -->
            <div class="r-field">
              <label class="r-label">
                <i class="fa-solid fa-lock" style="color:#1e4a49;font-size:9px"></i>
                Mot de passe
              </label>
              <div class="input-shell">
                <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" required class="r-input pr-12" />
                <div class="input-bar"></div>
                <button type="button" @click="showPassword = !showPassword" class="eye-toggle">
                  <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                </button>
              </div>
            </div>

            <!-- error -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition-all duration-200" leave-to-class="opacity-0">
              <div v-if="loginError" class="flex items-center gap-3 rounded-2xl px-4 py-3.5" style="background:#fff5f5;border:1.5px solid #fecaca">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background:#fee2e2">
                  <i class="fa-solid fa-exclamation text-[10px]" style="color:#f87171"></i>
                </div>
                <p class="text-sm font-semibold" style="color:#dc2626">{{ loginError }}</p>
              </div>
            </Transition>

            <!-- submit -->
            <button type="submit" :disabled="loading || !email || !password" class="submit-btn w-full">
              <span class="submit-bg"></span>
              <span class="submit-glow"></span>
              <span class="relative z-10 flex items-center justify-center gap-3 text-sm font-black text-white">
                <i v-if="loading" class="fa-solid fa-circle-notch fa-spin"></i>
                <i v-else class="fa-solid fa-arrow-right-to-bracket btn-arrow"></i>
                {{ loading ? 'Connexion en cours…' : 'Se connecter' }}
              </span>
            </button>
          </form>

          <!-- divider -->
          <div class="my-7 flex items-center gap-4" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:260ms">
            <div class="flex-1 h-px" style="background:linear-gradient(90deg,transparent,rgba(30,74,73,0.12))"></div>
            <span class="text-[9px] font-black uppercase tracking-widest px-1" style="color:#aabfb8">Espaces disponibles</span>
            <div class="flex-1 h-px" style="background:linear-gradient(90deg,rgba(30,74,73,0.12),transparent)"></div>
          </div>

          <!-- roles grid -->
          <div class="grid grid-cols-2 gap-3" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:320ms">
            <div v-for="(r,i) in roles" :key="r.label"
              class="role-card"
              :class="mounted ? 'anim-in' : 'opacity-0'"
              :style="`transition-delay:${340 + i*50}ms`">
              <div class="role-card-icon" :style="`background:${r.color}12`">
                <i :class="`fa-solid ${r.icon}`" :style="`color:${r.color};font-size:13px`"></i>
              </div>
              <div>
                <p class="text-xs font-black" :style="`color:${r.color}`">{{ r.label }}</p>
                <p class="text-[9px] font-medium mt-0.5" style="color:#a0b4ae">Espace dédié</p>
              </div>
              <div class="role-dot ml-auto" :style="`background:${r.color}`"></div>
            </div>
          </div>

          <!-- footer -->
          <div class="mt-8 flex flex-col items-center gap-1" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:500ms">
            <div class="flex items-center gap-2">
              <div class="h-px w-8" style="background:rgba(30,74,73,0.15)"></div>
              <i class="fa-solid fa-seedling text-[10px]" style="color:rgba(30,74,73,0.3)"></i>
              <div class="h-px w-8" style="background:rgba(30,74,73,0.15)"></div>
            </div>
            <p class="text-[9px] font-medium text-center" style="color:#b0bfb0">
              Faculté des Sciences Ben M'Sick · Université Hassan II de Casablanca
            </p>
          </div>

        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.abs-fill { position:absolute; inset:0; pointer-events:none; }

/* ── topo contour lines ── */
.topo-lines {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1000' height='1000'%3E%3Cg fill='none' stroke='rgba(214,232,122,0.055)' stroke-width='1'%3E%3Cellipse cx='500' cy='500' rx='480' ry='200'/%3E%3Cellipse cx='500' cy='500' rx='420' ry='172'/%3E%3Cellipse cx='500' cy='500' rx='360' ry='146'/%3E%3Cellipse cx='500' cy='500' rx='300' ry='120'/%3E%3Cellipse cx='500' cy='500' rx='240' ry='96'/%3E%3Cellipse cx='500' cy='500' rx='180' ry='74'/%3E%3Cellipse cx='500' cy='500' rx='120' ry='52'/%3E%3Cellipse cx='500' cy='500' rx='60' ry='30'/%3E%3Cellipse cx='500' cy='500' rx='460' ry='420'/%3E%3Cellipse cx='500' cy='500' rx='400' ry='360'/%3E%3Cellipse cx='500' cy='500' rx='340' ry='300'/%3E%3Cellipse cx='500' cy='500' rx='280' ry='240'/%3E%3Cellipse cx='500' cy='500' rx='220' ry='180'/%3E%3C/g%3E%3C/svg%3E");
  background-size: 115% 115%;
  background-position: center;
  animation: topo-drift 28s ease-in-out infinite alternate;
}
@keyframes topo-drift {
  from { background-position: 42% 42%; background-size: 115% 115%; }
  to   { background-position: 58% 58%; background-size: 120% 120%; }
}

/* ── dot grids ── */
.grid-dots {
  background-image: radial-gradient(circle, rgba(214,232,122,0.12) 1px, transparent 1px);
  background-size: 32px 32px;
  mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 30%, transparent 100%);
}
.light-dots {
  background-image: radial-gradient(circle, rgba(30,74,73,0.07) 1px, transparent 1px);
  background-size: 28px 28px;
}

/* ── logo gem ── */
.logo-gem {
  background: linear-gradient(135deg, #d6e87a 0%, #a8c44a 100%);
  box-shadow: 0 0 0 0 rgba(214,232,122,0.5);
  animation: gem-pulse 3.5s ease-in-out infinite;
}
@keyframes gem-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(214,232,122,0.5); }
  50%      { box-shadow: 0 0 0 14px rgba(214,232,122,0); }
}

/* ── live badge ── */
.live-badge {
  background: rgba(214,232,122,0.08);
  border: 1px solid rgba(214,232,122,0.18);
  backdrop-filter: blur(8px);
}

/* ── lime stroke text ── */
.lime-stroke {
  -webkit-text-stroke: 2px #d6e87a;
  color: transparent;
}

/* ── feature rows ── */
.feature-row {
  border: 1px solid rgba(255,255,255,0.05);
  background: rgba(255,255,255,0.02);
  backdrop-filter: blur(4px);
  transition: background 0.25s, border-color 0.25s, transform 0.25s;
}
.feature-row:hover {
  background: rgba(214,232,122,0.06);
  border-color: rgba(214,232,122,0.15);
  transform: translateX(4px);
}
.feature-icon {
  background: rgba(214,232,122,0.08);
  border: 1px solid rgba(214,232,122,0.14);
  transition: background 0.2s;
}
.feature-row:hover .feature-icon { background: rgba(214,232,122,0.14); }
.check-dot {
  background: rgba(214,232,122,0.08);
  border: 1px solid rgba(214,232,122,0.15);
}

/* ── stats ── */
.stats-row { border-top: 1px solid rgba(255,255,255,0.06); }
.stat-item { position: relative; }
.stat-item::after {
  content:'';
  position:absolute;
  right:-20px; top:10%; height:80%;
  width:1px;
  background: rgba(255,255,255,0.07);
}
.stat-item:last-child::after { display:none; }

/* ── heading accent ── */
.heading-accent {
  background: linear-gradient(90deg, #1e4a49 0%, #3a8a88 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── field ── */
.r-field {
  background: white;
  border: 1.5px solid #e8ede8;
  border-radius: 1rem;
  padding: 0.875rem 1.1rem 0.6rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.r-field:focus-within {
  border-color: #1e4a49;
  box-shadow: 0 0 0 4px rgba(30,74,73,0.07);
}
.r-label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #8aaa9a;
  margin-bottom: 4px;
  transition: color 0.2s;
}
.r-field:focus-within .r-label { color: #1e4a49; }

.input-shell { position: relative; }
.r-input {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 0.9rem;
  font-weight: 600;
  color: #0f2320;
  outline: none;
  padding: 0.1rem 0;
}
.r-input::placeholder { color: #c4d4cc; }
.input-bar {
  position: absolute;
  bottom: -2px;
  left: 0;
  height: 1.5px;
  width: 0%;
  background: linear-gradient(90deg, #1e4a49, #d6e87a);
  border-radius: 2px;
  transition: width 0.3s ease;
}
.input-shell:focus-within .input-bar { width: 100%; }

.eye-toggle {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  color: #b0c4bc;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 4px;
}
.eye-toggle:hover { color: #1e4a49; }

/* ── submit ── */
.submit-btn {
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
  padding: 1.05rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 24px rgba(30,74,73,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
}
.submit-btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 36px rgba(30,74,73,0.38), inset 0 1px 0 rgba(255,255,255,0.15);
}
.submit-btn:not(:disabled):hover .btn-arrow { transform: translateX(3px); }
.submit-btn:not(:disabled):active { transform: translateY(0); }
.submit-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.submit-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #1e4a49 0%, #2d7a78 50%, #1e4a49 100%);
  background-size: 200% 100%;
  background-position: 0% 0%;
  transition: background-position 0.5s ease;
}
.submit-btn:not(:disabled):hover .submit-bg { background-position: 100% 0%; }
.submit-glow {
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(255,255,255,0.14) 0%, transparent 50%);
}
.btn-arrow { transition: transform 0.25s ease; }

/* ── roles ── */
.role-card {
  display: flex;
  align-items: center;
  gap: 10px;
  background: white;
  border: 1.5px solid #edf0ea;
  border-radius: 1rem;
  padding: 0.875rem 1rem;
  cursor: default;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.07);
  border-color: #d0dcd0;
}
.role-card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  width: 34px;
  border-radius: 10px;
  flex-shrink: 0;
}
.role-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  opacity: 0.5;
  flex-shrink: 0;
}

/* ── entrance animation ── */
.anim-in {
  animation: slide-up 0.65s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes slide-up {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ── autofill ── */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px #fafbfa inset;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
