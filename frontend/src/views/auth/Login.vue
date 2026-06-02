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
    <div class="hidden lg:flex flex-col w-[52%] min-h-screen relative overflow-hidden" style="background:#1e4a49">

      <!-- ── Background layers ── -->
      <!-- depth overlay -->
      <div class="abs-fill" style="background:radial-gradient(ellipse 70% 60% at 15% 25%,rgba(0,0,0,0.2) 0%,transparent 60%),radial-gradient(ellipse 60% 60% at 85% 85%,rgba(0,0,0,0.25) 0%,transparent 55%)"></div>
      <!-- animated topo -->
      <div class="abs-fill topo-lines"></div>
      <!-- dot grid -->
      <div class="abs-fill grid-dots"></div>
      <!-- vignette -->
      <div class="abs-fill" style="background:radial-gradient(ellipse 100% 100% at 50% 50%,transparent 45%,rgba(0,0,0,0.2) 100%)"></div>

      <!-- ── Decorative geometry ── -->
      <!-- large ring top-right -->
      <div class="pointer-events-none absolute" style="width:420px;height:420px;border-radius:50%;border:1px solid rgba(214,232,122,0.2);top:-140px;right:-100px"></div>
      <div class="pointer-events-none absolute" style="width:280px;height:280px;border-radius:50%;border:1px solid rgba(214,232,122,0.12);top:-80px;right:-40px"></div>
      <!-- small ring bottom-left -->
      <div class="pointer-events-none absolute" style="width:200px;height:200px;border-radius:50%;border:1px solid rgba(214,232,122,0.14);bottom:80px;left:-60px"></div>
      <!-- floating dots -->
      <div class="pointer-events-none absolute w-2 h-2 rounded-full dot-float" style="background:#d6e87a;opacity:0.75;top:22%;right:18%"></div>
      <div class="pointer-events-none absolute w-1.5 h-1.5 rounded-full dot-float" style="background:#d6e87a;opacity:0.45;top:40%;right:8%;animation-delay:-3s"></div>
      <div class="pointer-events-none absolute w-1 h-1 rounded-full dot-float" style="background:#d6e87a;opacity:0.55;bottom:30%;right:25%;animation-delay:-6s"></div>
      <!-- horizontal accent line -->
      <div class="pointer-events-none absolute left-0 right-0" style="top:50%;height:1px;background:linear-gradient(90deg,rgba(214,232,122,0.3),rgba(214,232,122,0.1),transparent);opacity:0.8"></div>

      <!-- ── Content ── -->
      <div class="relative z-10 flex flex-col justify-between h-full px-14 py-12">

        <!-- TOP: logo + university -->
        <div class="flex items-center justify-between" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:0ms">
          <div class="flex items-center gap-3.5">
            <div class="logo-gem flex h-12 w-12 items-center justify-center rounded-2xl shrink-0">
              <i class="fa-solid fa-seedling" style="color:#1e4a49;font-size:1.1rem"></i>
            </div>
          </div>
          <div class="live-badge flex items-center gap-1.5 rounded-full px-3 py-1.5 shrink-0">
            <span class="h-1.5 w-1.5 rounded-full animate-pulse" style="background:#d6e87a"></span>
            <span class="text-[9px] font-black uppercase tracking-widest" style="color:rgba(214,232,122,0.8)">2024–2026</span>
          </div>
        </div>

        <!-- MIDDLE: headline + workflow steps -->
        <div class="space-y-9">

          <!-- headline -->
          <div :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:80ms">
            <p class="text-[10px] font-black uppercase tracking-[0.28em] mb-4" style="color:rgba(214,232,122,0.55)">
              ✦ Plateforme PFE · FSBM Hassan II
            </p>
            <h2 class="font-black text-white" style="font-size:clamp(2.4rem,3.8vw,3.4rem);line-height:1.05;letter-spacing:-0.035em">
              Gérez votre<br/>
              projet de fin<br/>
              <span class="left-lime-fill">d'études</span>
            </h2>
            <p class="mt-4 text-sm leading-relaxed font-medium" style="color:rgba(255,255,255,0.38);max-width:310px">
              Soumission, suivi, cartographie SIG, messagerie avec l'encadrant et soutenance — tout en un seul espace.
            </p>
          </div>

          <!-- workflow timeline -->
          <div class="space-y-0" :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:200ms">
            <div v-for="(step, i) in [
              { icon:'fa-file-pen',          label:'Soumission du sujet',     sub:'Proposé par le professeur encadrant' },
              { icon:'fa-user-check',         label:'Affectation étudiant',    sub:'Postulation et sélection par le coordinateur' },
              { icon:'fa-map-location-dot',   label:'Zone d\'étude SIG',       sub:'Cartographie interactive avec Leaflet' },
              { icon:'fa-boxes-stacked',      label:'Dépôt des livrables',     sub:'Rapports, fichiers et données validés' },
              { icon:'fa-graduation-cap',     label:'Soutenance & note',       sub:'Jury, salle, date et note finale /20' },
            ]" :key="step.label"
              class="step-row flex items-center gap-4 py-3 relative"
              :class="mounted ? 'anim-in' : 'opacity-0'"
              :style="`transition-delay:${240 + i * 60}ms`">
              <!-- connector line -->
              <div v-if="i < 4" class="step-line absolute left-[18px]" style="top:calc(50% + 14px);width:1px;height:calc(100% - 2px)"></div>
              <!-- icon circle -->
              <div class="step-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-full z-10">
                <i :class="`fa-solid ${step.icon}`" style="font-size:11px;color:#d6e87a"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-black text-white leading-none">{{ step.label }}</p>
                <p class="text-[10px] mt-0.5 font-medium" style="color:rgba(255,255,255,0.3)">{{ step.sub }}</p>
              </div>
              <div class="step-num text-[10px] font-black" style="color:rgba(214,232,122,0.4)">0{{ i + 1 }}</div>
            </div>
          </div>
        </div>

        <!-- BOTTOM: institution info -->
        <div :class="mounted ? 'anim-in' : 'opacity-0'" style="transition-delay:580ms">
          <div class="flex items-center gap-4 pt-6" style="border-top:1px solid rgba(255,255,255,0.08)">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background:rgba(214,232,122,0.1);border:1px solid rgba(214,232,122,0.15)">
              <i class="fa-solid fa-building-columns" style="color:#d6e87a;font-size:13px"></i>
            </div>
            <div>
              <p class="text-xs font-black text-white leading-none">Faculté des Sciences Ben M'Sick</p>
              <p class="text-[10px] mt-0.5 font-medium" style="color:rgba(255,255,255,0.3)">Université Hassan II de Casablanca ·</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ════════════════════════════════
         RIGHT — form panel
    ════════════════════════════════ -->
    <div class="flex flex-1 flex-col min-h-screen relative overflow-hidden" style="background:#f0f3eb">

      <!-- layered bg -->
      <div class="abs-fill light-dots"></div>
      <div class="abs-fill" style="background:linear-gradient(160deg,rgba(214,232,122,0.18) 0%,transparent 45%,rgba(30,74,73,0.05) 100%)"></div>

      <!-- decorative corner shapes -->
      <div class="pointer-events-none absolute" style="width:320px;height:320px;border-radius:50%;border:50px solid rgba(214,232,122,0.18);top:-100px;right:-100px"></div>
      <div class="pointer-events-none absolute" style="width:200px;height:200px;border-radius:50%;border:32px solid rgba(30,74,73,0.08);bottom:60px;left:-60px"></div>
      <div class="pointer-events-none absolute" style="width:120px;height:120px;border-radius:50%;border:20px solid rgba(214,232,122,0.12);bottom:200px;right:30px"></div>
      <!-- floating dots -->
      <div class="pointer-events-none absolute w-2 h-2 rounded-full" style="background:#d6e87a;opacity:0.6;top:18%;right:12%"></div>
      <div class="pointer-events-none absolute w-1.5 h-1.5 rounded-full" style="background:#1e4a49;opacity:0.3;top:35%;right:20%"></div>
      <div class="pointer-events-none absolute w-2.5 h-2.5 rounded-full" style="background:#d6e87a;opacity:0.4;bottom:22%;left:14%"></div>

      <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-8 py-10">
        <div class="w-full" style="max-width:400px">

          <!-- mobile logo -->
          <div class="mb-8 flex items-center gap-3 lg:hidden">
            <div class="logo-gem flex h-10 w-10 items-center justify-center rounded-2xl">
              <i class="fa-solid fa-seedling text-sm" style="color:#1e4a49"></i>
            </div>
            <span class="font-black text-base" style="color:#0b1f1e">FSBM</span>
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
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1000' height='1000'%3E%3Cg fill='none' stroke='rgba(214,232,122,0.12)' stroke-width='1'%3E%3Cellipse cx='500' cy='500' rx='480' ry='200'/%3E%3Cellipse cx='500' cy='500' rx='420' ry='172'/%3E%3Cellipse cx='500' cy='500' rx='360' ry='146'/%3E%3Cellipse cx='500' cy='500' rx='300' ry='120'/%3E%3Cellipse cx='500' cy='500' rx='240' ry='96'/%3E%3Cellipse cx='500' cy='500' rx='180' ry='74'/%3E%3Cellipse cx='500' cy='500' rx='120' ry='52'/%3E%3Cellipse cx='500' cy='500' rx='60' ry='30'/%3E%3Cellipse cx='500' cy='500' rx='460' ry='420'/%3E%3Cellipse cx='500' cy='500' rx='400' ry='360'/%3E%3Cellipse cx='500' cy='500' rx='340' ry='300'/%3E%3Cellipse cx='500' cy='500' rx='280' ry='240'/%3E%3Cellipse cx='500' cy='500' rx='220' ry='180'/%3E%3C/g%3E%3C/svg%3E");
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
  background-image: radial-gradient(circle, rgba(214,232,122,0.18) 1px, transparent 1px);
  background-size: 32px 32px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
}
.light-dots {
  background-image: radial-gradient(circle, rgba(30,74,73,0.1) 1px, transparent 1px);
  background-size: 28px 28px;
}

/* ── logo gem ── */
.logo-gem {
  background: linear-gradient(135deg, #d6e87a 0%, #a8c44a 100%);
  box-shadow: 0 0 0 0 rgba(214,232,122,0.4), 0 4px 16px rgba(214,232,122,0.2);
  animation: gem-pulse 3.5s ease-in-out infinite;
}
@keyframes gem-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(214,232,122,0.4), 0 4px 16px rgba(214,232,122,0.2); }
  50%      { box-shadow: 0 0 0 12px rgba(214,232,122,0), 0 4px 24px rgba(214,232,122,0.3); }
}

/* ── live badge ── */
.live-badge {
  background: rgba(214,232,122,0.07);
  border: 1px solid rgba(214,232,122,0.16);
  backdrop-filter: blur(10px);
}

/* ── lime filled text ── */
.left-lime-fill {
  background: linear-gradient(135deg, #d6e87a 0%, #c8e060 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── floating dots ── */
.dot-float {
  animation: dot-float 6s ease-in-out infinite alternate;
}
@keyframes dot-float {
  from { transform: translateY(0px); opacity: 0.5; }
  to   { transform: translateY(-8px); opacity: 0.15; }
}

/* ── workflow steps ── */
.step-row { cursor: default; }
.step-icon {
  background: rgba(214,232,122,0.1);
  border: 1px solid rgba(214,232,122,0.18);
  transition: background 0.2s, border-color 0.2s, transform 0.2s;
}
.step-row:hover .step-icon {
  background: rgba(214,232,122,0.18);
  border-color: rgba(214,232,122,0.35);
  transform: scale(1.08);
}
.step-line {
  background: linear-gradient(to bottom, rgba(214,232,122,0.2), rgba(214,232,122,0.04));
}
.step-num { min-width: 20px; text-align: right; }

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
